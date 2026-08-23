# YakuCore — Plan de implementación por fases (para Claude Code / Sonnet)

> **Cómo usar este plan (protocolo obligatorio para Claude Code):**
> 1. Ejecuta **una fase a la vez**, en orden. No inicies una fase si la anterior no tiene todos sus tests en verde (`php artisan test`).
> 2. Cada tarea es un commit (o grupo pequeño de commits) con prefijo de fase: `F3: servicio de número de recibo`.
> 3. **Nunca edites una migración ya ejecutada**; crea una migración nueva.
> 4. No introduzcas dependencias fuera del stack fijado (Laravel 12 / PHP 8.2 / MySQL:3307 / Pest / Inertia v2 / React 19 / TS / Tailwind v4 / shadcn-Radix / Vite). Sin Redux/Zustand (spec §2).
> 5. Los cálculos de dinero y lecturas se hacen **siempre en el servidor**; el frontend solo muestra.
> 6. Toda cadena visible por el usuario va en **español** (spec §7.4).
> 7. Si encuentras una ambigüedad no resuelta por la spec §8 ni por la sección 7 de este plan ("Preguntas al autor"), **detente y pregunta**; no asumas.
> 8. Las decisiones citadas como `(8.x)` refieren a la sección 8 de la spec `YakuCore-Spec-para-Fable5.md`.

---

## 1. Mapa de módulos y límites (monolito modular)

Se sigue la convención estándar de Laravel 12 con **agrupación por módulo en namespaces**, en lugar de una capa `app/Domain/*` estilo DDD completo. Motivo: con Eloquent, Policies, FormRequests y el starter de Inertia, la convención del framework reduce fricción, mantiene el tooling (route:list, ide-helper, policies auto-descubiertas) funcionando sin configuración extra, y minimiza el riesgo de que Claude Code rompa convenciones al avanzar. La modularidad se logra por namespace + servicios de dominio, no por reubicar Eloquent.

```
app/
├── Enums/                     Rol, TipoPago, EstadoReunion, EstadoAnuncio,
│                              TipoComprobante, Periodicidad, EstadoAsistencia
├── Models/                    Jass, User, Socio, Vivienda, Medidor, Pago,
│                              Gasto, Reunion, Asistencia, Anuncio, Auditoria
├── Http/
│   ├── Controllers/
│   │   ├── Auth/              (starter kit, adaptado a español + redirect por rol)
│   │   ├── Superadmin/        JassController, EstadisticasGlobalesController,
│   │   │                      SupervisionController
│   │   ├── Panel/             DashboardController, SocioController, ViviendaController,
│   │   │                      MedidorController, UsuarioController,
│   │   │                      Pagos/{MantenimientoController, ConsumoMedidorController,
│   │   │                             ConsumoDirectoController, ReciboController},
│   │   │                      MorosidadController, GastoController, ReunionController,
│   │   │                      AsistenciaController, AnuncioController, AuditoriaController
│   │   └── Publico/           PortalController, TransparenciaController, ConsultaDeudaController
│   ├── Middleware/            CabecerasSeguridad, ResolverJassActual, AsegurarRol
│   └── Requests/              agrupados por módulo (Socios/, Pagos/, Reuniones/, ...)
├── Policies/                  una por modelo (ver sección 3)
├── Services/
│   ├── Tenancy/ContextoJass.php          (singleton: JASS activa del request)
│   ├── Pagos/GeneradorNumeroRecibo.php
│   ├── Pagos/RegistradorPago.php         (mantenimiento / consumo medidor / consumo directo)
│   ├── Pagos/CalculadoraConsumo.php
│   ├── Medidores/ReemplazoMedidor.php    (flujo 8.3)
│   ├── Morosidad/EstadoCuenta.php        (periodos esperados vs pagados, 8.8)
│   └── Auditoria/RegistradorAuditoria.php
├── Console/Commands/MarcarMorosos.php    (scheduler diario, 8.8)
└── Observers/                 solo si un módulo lo justifica; el registro de auditoría
                               de acciones de negocio se hace EXPLÍCITO en los servicios
resources/js/
├── pages/                     espejo de los controllers (superadmin/, panel/, publico/)
├── components/ui/             shadcn
├── components/yaku/           componentes propios (TablaResponsiva, TarjetaKpi, Recibo, ...)
└── layouts/                   PanelLayout, SuperadminLayout, PublicoLayout
docs/design-system.md          se crea en Fase 1.5 (spec §7.2)
CLAUDE.md                      reglas cortas del repo (se entrega junto a este plan)
```

**Reglas de dependencia entre módulos:**
- Los controllers no contienen lógica de negocio con dinero/lecturas/correlativo: delegan a `Services/`.
- `Pagos` puede leer `Medidores` y `Viviendas`; nada depende de `Pagos` salvo `Morosidad` (solo lectura) y los dashboards.
- `Auditoria` es receptor universal (todos la llaman vía `RegistradorAuditoria`), pero no llama a nadie.
- El portal público (`Publico/`) solo consume consultas agregadas de solo lectura; jamás modelos con datos personales completos.

---

## 2. Esquema de base de datos

BD: **`BDyakucore`** (8.1), MySQL en puerto **3307**, motor InnoDB, charset `utf8mb4`. Montos `DECIMAL(10,2)`, lecturas `DECIMAL(10,2)` (m³ con decimales). Todas las FKs con `constrained()` + `restrictOnDelete()` salvo indicación.

### `jass`
| Columna | Tipo | Notas |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| nombre | VARCHAR(120) UNIQUE | |
| prefijo | VARCHAR(30) UNIQUE | autogenerado del nombre al crear (sin espacios, ej. `JASSHuaraz`); base del código de medidor (regla 5.4) |
| comunidad | VARCHAR(120) | |
| distrito | VARCHAR(120) | |
| tiene_medidor | BOOLEAN | |
| tarifa_base_m3 | DECIMAL(8,2) NULL | requerida por validación solo si `tiene_medidor` |
| periodicidad_mantenimiento | ENUM(mensual, trimestral, semestral, anual) | |
| dia_vencimiento | TINYINT UNSIGNED, default 15 | 1–28; entrada del cálculo de morosidad (8.8) |
| ultimo_numero_recibo | INT UNSIGNED, default 0 | contador del correlativo por JASS (regla 5.8) |
| timestamps, deleted_at | | soft delete: el histórico de una JASS eliminada no se destruye |

### `users`
| Columna | Tipo | Notas |
|---|---|---|
| id | PK | |
| jass_id | FK NULL → jass | NULL ⇔ rol superadmin (invariante validada en FormRequest y en el modelo) |
| rol | ENUM(superadmin, presidente, tesorero, vocal) | |
| name | VARCHAR(120) | |
| email | VARCHAR UNIQUE | credencial de login (ver Pregunta P2) |
| password, remember_token, timestamps, deleted_at | | hash bcrypt por defecto de Laravel |

Índice: `(jass_id)`.

### `socios`
| Columna | Tipo | Notas |
|---|---|---|
| id | PK | |
| jass_id | FK → jass | |
| nombres / apellidos | VARCHAR(100) | |
| dni | CHAR(8) | `UNIQUE (jass_id, dni)` — mismo DNI puede existir en otra JASS (ver P7) |
| telefono | VARCHAR(15) NULL | |
| direccion | VARCHAR(180) | |
| timestamps, deleted_at | | |

Índices: `(jass_id, apellidos)`, unique `(jass_id, dni)`.

### `viviendas`
| Columna | Tipo | Notas |
|---|---|---|
| id | PK | |
| jass_id | FK → jass | **denormalizada a propósito**: es la clave de scoping multi-tenant; evita joins en cada policy/scope y permite índices y uniques por tenant. La consistencia (`vivienda.jass_id === socio.jass_id`) se garantiza en el servicio de creación y con un test |
| socio_id | FK → socios | un socio tiene N viviendas (regla 5.1) |
| direccion | VARCHAR(180) | |
| activa | BOOLEAN default true | |
| morosa_desde | DATE NULL | cache que escribe el job de morosidad (8.8); la fuente de verdad es `Services/Morosidad/EstadoCuenta` |
| timestamps, deleted_at | | |

Índices: `(jass_id)`, `(socio_id)`.

### `medidores`
| Columna | Tipo | Notas |
|---|---|---|
| id | PK | |
| jass_id | FK → jass | denormalizada (mismo motivo que viviendas) |
| vivienda_id | FK → viviendas | 0..1 medidor activo por vivienda (regla 5.2) |
| correlativo | INT UNSIGNED | lo digita el usuario (regla 5.4); `UNIQUE (jass_id, correlativo)` |
| codigo | VARCHAR(50) UNIQUE | persistido: `{prefijo}-MED-{correlativo}`; se genera en el modelo, nunca lo escribe el usuario |
| lectura_inicial | DECIMAL(10,2) default 0 | (8.2) editable al registrar si el medidor traía acumulado |
| reiniciado_en | DATETIME NULL | lo escribe el flujo de reemplazo (8.3); el autocompletado de lectura anterior ignora pagos anteriores a esta fecha |
| activo | BOOLEAN default true | |
| fecha_instalacion | DATE NULL | |
| timestamps | | |

### `pagos` (tabla única para los tres tipos)
Una sola tabla en vez de tres: comparte el 90 % de columnas, hace trivial el correlativo único por JASS (`UNIQUE (jass_id, numero_recibo)` cubre los tres tipos, regla 5.8) y simplifica morosidad, transparencia y dashboards. Los campos exclusivos de consumo con medidor son NULL en los demás tipos; la validación por tipo vive en `RegistradorPago` + FormRequests (normalización pragmática: la alternativa de 3 tablas + tabla de recibos añade joins sin eliminar redundancia real).

| Columna | Tipo | Notas |
|---|---|---|
| id | PK | |
| jass_id | FK → jass | |
| socio_id | FK → socios | |
| vivienda_id | FK → viviendas | **siempre presente**, incluido mantenimiento (8.6: mantenimiento se cobra por vivienda) |
| medidor_id | FK NULL → medidores | solo `consumo_medidor` |
| usuario_id | FK → users | quién cobró |
| tipo | ENUM(mantenimiento, consumo_medidor, consumo_directo) | |
| numero_recibo | INT UNSIGNED | `UNIQUE (jass_id, numero_recibo)` |
| periodo | CHAR(7) | `YYYY-MM` del periodo cubierto; en periodicidad trimestral/semestral/anual se usa el mes inicial del periodo (documentado en el servicio) |
| concepto | VARCHAR(60) | etiqueta legible del recibo, ej. "Mantenimiento trimestre 3-2026" |
| lectura_anterior / lectura_actual | DECIMAL(10,2) NULL | solo `consumo_medidor`; `lectura_actual >= lectura_anterior` validado en servidor (regla 5.6) |
| monto | DECIMAL(10,2) | consumo con medidor: **calculado en servidor** `(actual − anterior) × tarifa_base_m3` (regla 5.7); demás tipos: manual |
| fecha_pago | DATE | |
| timestamps | | sin update/delete por rutas: un pago emitido no se edita (ver P6 sobre anulación) |

Índices: unique `(jass_id, numero_recibo)`, `(vivienda_id, tipo, periodo)` (consulta clave de morosidad), `(medidor_id)`, `(jass_id, fecha_pago)`.

### `gastos`
`id, jass_id FK, usuario_id FK, descripcion VARCHAR(200), monto DECIMAL(10,2), tipo_comprobante ENUM(boleta, factura, declaracion_jurada), comprobante_path VARCHAR(255) NULL, fecha DATE, timestamps`. Índice `(jass_id, fecha)`. `comprobante_path` apunta a disco **privado** (ver checklist S7).

### `reuniones`
`id, jass_id FK, titulo VARCHAR(150), descripcion TEXT, fecha DATE, hora TIME, lugar VARCHAR(150), puntos_a_tratar TEXT, estado ENUM(programada, cancelada, finalizada) default programada, timestamps`. Índice `(jass_id, fecha)`. La regla "fecha ≥ hoy" es de **validación al crear**, no constraint (las reuniones pasadas deben poder existir).

### `asistencias`
`id, reunion_id FK, socio_id FK, estado ENUM(presente, ausente), registrado_por FK → users, created_at` (**sin** `updated_at`: el registro es inmutable, spec §6.10). `UNIQUE (reunion_id, socio_id)`. Inmutabilidad garantizada por: (a) no existen rutas update/delete, (b) `AsistenciaPolicy` niega `update/delete` a todos los roles, (c) el modelo no expone métodos de mutación.

### `anuncios`
`id, jass_id FK, titulo VARCHAR(150), contenido TEXT, fecha_publicacion DATE, estado ENUM(visible, inactivo), timestamps`. Índice `(jass_id, estado, fecha_publicacion)`.

### `auditoria`
`id, jass_id FK NULL (NULL = acción global de superadmin), usuario_id FK, accion VARCHAR(60), entidad_tipo VARCHAR(80), entidad_id BIGINT NULL, detalles JSON NULL, ip VARCHAR(45) NULL, created_at` (sin `updated_at`). Índices `(jass_id, created_at)`, `(usuario_id)`. Append-only a nivel de aplicación: cero rutas de mutación, policy que niega todo salvo `viewAny/view`, y el modelo solo se escribe vía `RegistradorAuditoria`.

**Nota de normalización general:** el esquema está en 3FN con dos denormalizaciones deliberadas y acotadas — `jass_id` replicada en viviendas/medidores/pagos (clave de tenant, evita joins en cada consulta escopada) y `viviendas.morosa_desde` (cache de un cálculo derivado, con fuente de verdad en servicio). No hay otra redundancia: el código de medidor se persiste pero es función determinística de columnas propias, y los montos de pago son hechos históricos (deben congelarse aunque cambie la tarifa después — no es redundancia, es registro contable).

---

## 3. Estrategia de autorización

**Capa 1 — Rol (middleware):** enum `App\Enums\Rol` + middleware `AsegurarRol` para agrupar rutas: `/superadmin/*` solo superadmin; `/panel/*` presidente/tesorero/vocal (y superadmin en modo supervisión, ver abajo).

**Capa 2 — Tenant (contexto + scope global):**
- `ResolverJassActual` (middleware de `/panel/*` y `/supervision/{jass}/*`): fija en el singleton `ContextoJass` la JASS del usuario autenticado; para el superadmin, la JASS del segmento de ruta de supervisión.
- Trait `PerteneceAJass` en Socio, Vivienda, Medidor, Pago, Gasto, Reunion, Anuncio, Auditoria: agrega `TenantScope` global (`where jass_id = ContextoJass::id()`) y asigna `jass_id` automáticamente al crear. Resultado: un query "olvidado" sin filtro **no puede** filtrar datos de otra JASS.

**Capa 3 — Policies (defensa en profundidad, no confiar solo en el scope):** toda policy que recibe modelo verifica además `$user->jass_id === $model->jass_id` (o contexto de supervisión para superadmin). Matriz (spec §3, decisiones 8.4 y regla 5.13):

| Policy | superadmin | presidente | tesorero | vocal |
|---|---|---|---|---|
| Jass | CRUD total | — | — | — |
| User | CRUD en cualquier JASS; único que crea `presidente` | CRUD **solo tesorero/vocal** de su JASS | ✗ | ✗ |
| Socio / Vivienda / Medidor | **solo lectura** (5.13: no gestiona socios) | CRUD | CRUD | solo lectura |
| Pago (crear/ver) | solo lectura | ✓ | ✓ | solo lectura (consulta de deudas §3) |
| Gasto / Reunion / Anuncio | solo lectura | CRUD | CRUD | solo lectura |
| Asistencia | ver | crear (nunca editar) | crear (nunca editar) | ver |
| Auditoria | viewAny global | viewAny su JASS | viewAny su JASS | ✗ (8.4) |

**Supervisión del superadmin:** rutas `/supervision/{jass}/...` reutilizan los mismos controllers de `/panel/...` (mismo componente React); las policies devuelven solo permisos de lectura para superadmin en módulos operativos, así el "modo supervisión" (spec §6.1) es estructuralmente de solo lectura sin ifs dispersos en la UI (la UI oculta botones consultando `can` compartido por Inertia).

**Confirmación de contraseña:** middleware `password.confirm` en: guardar asistencia (spec §7.1), reemplazo de medidor (8.3), eliminación de JASS, socios y usuarios.

---

## 4. Plan por fases

> Formato: tareas atómicas y verificables. "DoD" = condición para cerrar la fase. Los tests de cada fase están detallados en la sección 5.

### Fase 0 — Fundaciones (sin features de negocio)
1. Verificar `.env`: `DB_PORT=3307`, `DB_DATABASE=BDyakucore` (8.1), `APP_LOCALE=es`, `APP_FAKER_LOCALE=es_PE`. Crear la BD si no existe y correr `php artisan migrate` del starter.
2. Instalar `laravel-lang/lang`, publicar traducciones `es`; verificar que un error de validación llega en español.
3. Confirmar Pest operativo: `php artisan test` en verde con el test de ejemplo.
4. Crear middleware global `CabecerasSeguridad` (X-Frame-Options: DENY, X-Content-Type-Options: nosniff, Referrer-Policy, CSP base compatible con Vite dev, HSTS condicionado a HTTPS) + test que asserta las cabeceras.
5. Configurar Laravel Pint; añadir scripts composer `test` y `lint`.
6. Colocar `CLAUDE.md` (entregado junto a este plan) en la raíz del repo.
7. Definir en `resources/css/app.css` los tokens Tailwind v4 (`@theme`) con la paleta obligatoria `#021F4A #06396C #065B96 #6496B1 #B1CCDF` + neutros derivados y estados éxito/error/advertencia armonizados (spec §7.2). Solo tokens; el diseño completo es Fase 1.5.
8. Crear los enums de `app/Enums/` listados en la sección 1.

**DoD:** suite verde, cabeceras presentes, app carga en español, tokens definidos.

### Fase 1 — Núcleo: JASS, usuarios, autenticación, tenancy, auditoría
1. Migración `jass` completa (sección 2), modelo con generación automática de `prefijo` al crear.
2. Migración que modifica `users` (jass_id, rol, deleted_at); modelo con helpers (`esSuperadmin()`, `esPresidente()`, ...) e invariante rol↔jass_id.
3. Factories + seeders: 1 superadmin, 2 JASS demo (una con medidor y otra sin), presidente/tesorero/vocal por JASS, credenciales demo documentadas en README.
4. Adaptar el login del starter: textos en español, sin copy genérico de SaaS (spec §7.2), redirección post-login por rol (superadmin → `/superadmin`, resto → `/panel`).
5. Middleware `AsegurarRol` + grupos de rutas `/superadmin` y `/panel`.
6. `ContextoJass` + middleware `ResolverJassActual` + trait `PerteneceAJass` con `TenantScope` (sección 3).
7. Migración `auditoria` + modelo + `RegistradorAuditoria` (`registrar(accion, entidad, detalles)` tomando usuario, JASS del contexto e IP) + `AuditoriaPolicy`.
8. CRUD de JASS (superadmin): index/create/edit/delete con soft delete, `password.confirm` en delete, auditoría en cada mutación, `JassPolicy`.
9. CRUD de usuarios: superadmin en cualquier JASS (único que crea rol presidente); presidente solo tesorero/vocal de su JASS; `UserPolicy`; auditoría.
10. Rutas `/supervision/{jass}` (superadmin): fija contexto y entra al panel en modo lectura.
11. Panel superadmin mínimo: listado de JASS con botón "Supervisar" (KPIs reales en Fase 7).

**DoD:** tests de la fase en verde, incluidos los de scoping cruzado (sección 5).

### Fase 1.5 — Sistema de diseño (`docs/design-system.md`)
1. Redactar `docs/design-system.md` (spec §7.2 exige fase dedicada; **no improvisar**): elección tipográfica definitiva partiendo de la sugerencia de la spec (Archivo o IBM Plex Sans + IBM Plex Mono para cifras/recibos), self-hosteadas vía `@fontsource-variable/*` (npm; sin CDN externo), iconografía Lucide, uso de la paleta por rol semántico, espaciados, componentes base (botón, input, tabla, tarjeta, badge de estado), patrón mobile para tablas densas (spec §7.3): **tabla en ≥ md, tarjetas apiladas en < md**, plantilla visual del recibo imprimible (CSS `@media print`, espacio de firma física — regla 5.9), y la lista de prohibiciones de §7.2.
2. Implementar `PanelLayout`, `SuperadminLayout` y `PublicoLayout` + componentes `components/yaku/` (incluye `TablaResponsiva`) siguiendo el documento.
3. Re-estilizar login y CRUDs de Fase 1 con el sistema.

**DoD:** ninguna vista usa colores/tipografía por defecto de Tailwind/shadcn; el patrón responsive de tablas queda encapsulado en `TablaResponsiva`.

### Fase 2 — Socios, viviendas y medidores
1. Migraciones `socios`, `viviendas`, `medidores` (sección 2).
2. Modelos con `PerteneceAJass` y relaciones; en Vivienda/Medidor, el servicio de creación garantiza coherencia de `jass_id` con el padre.
3. CRUD Socios: FormRequests (DNI: 8 dígitos, unique por JASS con `Rule::unique` escopada; teléfono opcional), `SocioPolicy` (vocal lectura), auditoría, UI con `TablaResponsiva` + buscador por DNI/apellidos.
4. Gestión de viviendas dentro de la ficha del socio (N viviendas, regla 5.1); `ViviendaPolicy`; auditoría.
5. Medidores (solo si `jass.tiene_medidor`; en JASS sin medidor las rutas devuelven 404): alta con correlativo manual, código autogenerado visible en vivo en el form (prefijo fijo + input numérico, regla 5.4), `lectura_inicial` editable con default 0 (8.2), unique `(jass_id, correlativo)` con mensaje en español.
6. Flujo "Reemplazar medidor" (8.3): `ReemplazoMedidor` service — requiere `password.confirm`, registra en auditoría las lecturas previas y el motivo, setea `lectura_inicial = 0` y `reiniciado_en = now()`. No bloquea pagos futuros; los habilita con base reiniciada.

**DoD:** tests de la fase en verde; un usuario de JASS sin medidor no ve ni alcanza nada de medidores.

### Fase 3 — Motor de pagos y recibos
> ⚠️ Antes de empezar: confirmar con el autor la **P1 (= decisión 8.6)**. Este plan implementa el default: mantenimiento **por vivienda**.

1. Migración `pagos` (sección 2).
2. `GeneradorNumeroRecibo`: dentro de `DB::transaction`, `Jass::whereKey($id)->lockForUpdate()` e incremento de `ultimo_numero_recibo`. Correlativo por JASS, compartido por los tres tipos (regla 5.8).
3. `CalculadoraConsumo`: dado un medidor, resuelve lectura anterior = última `lectura_actual` de pagos del medidor **posteriores a `reiniciado_en`**; sin pagos → `lectura_inicial` (8.2). Calcula `monto = (actual − anterior) × tarifa_base_m3` (regla 5.7).
4. `RegistradorPago` con tres métodos (mantenimiento / consumoMedidor / consumoDirecto): valida tipo↔JASS (consumo_medidor solo si `tiene_medidor`, consumo_directo solo si no), valida `lectura_actual >= lectura_anterior` (regla 5.6), normaliza `periodo` según periodicidad, crea el pago con recibo dentro de la misma transacción, audita.
5. Cobro de mantenimiento (UI): selector socio → **vivienda** (8.6) → periodo, monto, fecha; FormRequest; `PagoPolicy`.
6. Cobro consumo con medidor (UI): socio → medidor (puede tener varios, spec §6.6); lectura anterior autocompletada de solo lectura; lectura actual manual; monto calculado mostrado por el servidor (round-trip Inertia `partial reload`, sin cálculo en JS como fuente de verdad).
7. Cobro consumo sin medidor (UI): socio → vivienda, monto manual (spec §6.7).
8. Recibo imprimible: página `recibos/{pago}` con número, JASS, socio, vivienda/medidor, detalle, concepto/periodo y espacio de firma física (regla 5.9), estilada según design-system (`@media print`); botón Imprimir.
9. Historial de pagos por socio y por vivienda (pestañas en la ficha del socio).

**DoD:** tests de cálculo, correlativo (incluida concurrencia) y scoping en verde.

### Fase 4 — Morosidad
1. `EstadoCuenta` service: para una vivienda, genera los periodos esperados desde su fecha de registro (ver P4) hasta hoy según `periodicidad_mantenimiento` + `dia_vencimiento` de la JASS (8.8), y los cruza contra `pagos (vivienda_id, tipo, periodo)`. Devuelve periodos pendientes de **mantenimiento** y de **consumo** (con o sin medidor). La deuda se expresa como *periodos pendientes*, no como monto en S/. (los montos no son fijos — ver P3).
2. Comando `yaku:marcar-morosos` + Scheduler diario (8.8): actualiza `viviendas.morosa_desde` (fecha del vencimiento impago más antiguo; NULL si está al día). Idempotente.
3. Vista Morosidad (spec §6.8): listado de viviendas morosas (socio, vivienda, periodos vencidos por tipo, antigüedad), filtros, y acción **"Cobrar"** que abre el formulario de pago correspondiente con socio/vivienda/periodo precargados (reutiliza Fase 3 — no hay un "pago de morosidad" separado: cobrar la deuda ES registrar el pago del periodo vencido).
4. Tras registrar un pago, recalcular en línea el estado de esa vivienda (no esperar al job nocturno).

**DoD:** tests de derivación de periodos y del job en verde.

### Fase 5 — Gastos
1. Migración `gastos` + modelo + `GastoPolicy` + auditoría.
2. FormRequest: descripción, monto > 0, tipo de comprobante, foto opcional — validación `File::types(['jpg','jpeg','png','webp','pdf'])->max(5 * 1024)` (MIME real, spec §7.1).
3. Almacenamiento en disco privado `storage/app/private/comprobantes/{jass_id}/...`; descarga únicamente vía controller con `authorize` (jamás URL pública, spec §7.1).
4. UI de registro y listado con totales por mes.

**DoD:** tests de upload y de acceso al comprobante en verde.

### Fase 6 — Reuniones, asistencia y anuncios
1. Migraciones `reuniones`, `asistencias`, `anuncios`.
2. CRUD Reuniones: validación `fecha >= hoy` **solo al crear**; editable/cancelable mientras `programada` (ver P10); `ReunionPolicy`; auditoría.
3. Tomar asistencia (spec §6.10): pantalla con socios activos de la JASS y toggle presente/ausente; guardar exige `password.confirm`; en una transacción: inserta todas las filas, pasa la reunión a `finalizada`, audita. Sin rutas de edición/borrado; `AsistenciaPolicy` niega update/delete a todo rol; reunión `finalizada` o `cancelada` no admite tomar asistencia de nuevo (unique `(reunion_id, socio_id)` como red final).
4. CRUD Anuncios con estado visible/inactivo; auditoría.

**DoD:** tests de inmutabilidad y transición de estados en verde.

### Fase 7 — Dashboards y vista de auditoría
1. Dashboard de JASS (set exacto de KPIs, spec §6.3): socios activos, viviendas activas, recaudado del mes y del año por tipo (mantenimiento/consumo), gastos del mes y del año, balance del año (recaudado − gastos), nº y % de viviendas morosas, próxima reunión programada, últimos 10 pagos. Gráfico simple de recaudación mensual del año (Recharts, ya disponible con el stack).
2. Dashboard superadmin (spec §6.1): tabla de JASS con socios afiliados, recaudación del año, % morosidad, y acceso a supervisión; totales globales arriba.
3. Vista Auditoría (presidente/tesorero; vocal excluido — 8.4): tabla con filtros por usuario, acción y rango de fechas; solo lectura absoluta.
4. Optimizar consultas agregadas (selects con `sum/count` agrupados; nada de N+1 — verificar con `Model::preventLazyLoading()` en local, activado desde esta fase en `AppServiceProvider`).

**DoD:** tests de escopado de KPIs y de solo-lectura del superadmin en verde.

### Fase 8 — Portal público
1. Landing pública (spec §6.13): información general + botón "Acceder al sistema". Sin datos de ninguna JASS.
2. Buscador de JASS por nombre → página pública de la JASS: anuncios `visible`, reuniones `programada`, y portal de transparencia: recaudado por tipo de cobro y gastos (ver P9 sobre detalle/rango; default del plan: totales por mes del año en curso con selector de año, gastos listados con descripción/monto/tipo, **sin** foto ni datos de socios).
3. Buscador de deuda personal (spec §6.13 + 8.5): formulario JASS + DNI + correlativo de medidor (u opción "no tengo medidor"). Verificación: el DNI debe existir en esa JASS y, si aporta medidor, corresponderle. Respuesta: estado de cuenta por vivienda (periodos pendientes) sin teléfono ni dirección.
4. Rate limiting (8.5): `RateLimiter::for('consulta-deuda')` con clave `ip|jass_id|dni`, 3 intentos **fallidos**, bloqueo 15 min, mensaje en español con tiempo restante. Rate limit también en login (`throttle` del starter, verificar en español).
5. Las rutas públicas usan consultas dedicadas de solo lectura (nunca serializar modelos completos hacia Inertia — solo los campos listados).

**DoD:** tests de rate limit y de no-exposición de datos en verde.

### Fase 9 — Endurecimiento y cierre
1. Recorrer el checklist de seguridad (sección 6) ítem por ítem y marcar evidencia (test o archivo).
2. Auditoría de idioma: buscar strings en inglés visibles (grep en `resources/js` y mensajes de excepción renderizados) y corregir.
3. Revisión responsive de cada módulo en viewport 375 px (tablas → tarjetas).
4. Seeders demo completos (socios, viviendas, medidores, pagos históricos de varios meses, gastos, reuniones con asistencia, anuncios) para demo realista.
5. `php artisan test` completo + Pint + `npm run build` sin warnings.
6. README: setup local (puerto 3307, BDyakucore), credenciales demo, comandos.

---

## 5. Estrategia de testing con Pest (por fase)

Infraestructura común (Fase 0/1): `RefreshDatabase` sobre una BD de test (`BDyakucore_test`, mismo puerto 3307, definida en `phpunit.xml`); helpers Pest `actingComoSuperadmin() / actingComoPresidente(Jass) / ...`; helper `esperaScopingPorJass(ruta, metodo)` reutilizable que crea dos JASS y verifica 403/404 cruzado.

| Fase | Tests mínimos antes de avanzar |
|---|---|
| 0 | Cabeceras de seguridad presentes en una respuesta; validación de ejemplo responde en español. |
| 1 | Redirección post-login por cada rol; presidente no puede crear rol presidente ni usuarios de otra JASS; tesorero recibe 403 en gestión de usuarios; superadmin crea/edita/elimina JASS; scoping cruzado en usuarios; toda mutación de JASS/usuarios genera fila de auditoría; nadie puede mutar auditoría (403/405). |
| 2 | DNI duplicado en la misma JASS falla y en otra JASS pasa; vocal no puede crear/editar socio (sí listar); socio con 2 viviendas; rutas de medidores → 404 en JASS sin medidor; unique de correlativo por JASS; reemplazo de medidor resetea base, exige confirmación de contraseña y audita; scoping cruzado en socios/viviendas/medidores. |
| 3 | Cálculo del monto de consumo (dataset con casos: primera lectura con inicial 0 y con inicial > 0 (8.2), lectura igual → monto 0, decimales); lectura actual < anterior → error de validación; autocompletado ignora pagos previos a `reiniciado_en`; correlativo: secuencial por JASS, independiente entre JASS, compartido entre tipos, y **test de concurrencia** (dos registros simultáneos → números distintos, sin hueco); mantenimiento persiste `vivienda_id` (8.6) y permite 2 pagos del mismo socio en 2 viviendas para el mismo periodo; vocal no puede cobrar; tesorero de JASS A no cobra en JASS B; cada pago audita. |
| 4 | Vivienda sin pago de periodo vencido → morosa con `morosa_desde` correcto; con pago del periodo → no morosa; periodicidad trimestral genera 4 periodos/año; job idempotente (dos corridas, mismo resultado); pagar desde morosidad limpia el estado en línea. |
| 5 | Upload con extensión válida pero MIME falso rechazado; > 5 MB rechazado; comprobante no accesible por URL directa de storage; usuario de otra JASS recibe 403 al descargar. |
| 6 | Crear reunión con fecha pasada falla; editar reunión `finalizada` falla; guardar asistencia sin confirmación de contraseña → redirección a confirmar; tras guardar: reunión `finalizada`, filas creadas, e intento de re-guardar o modificar cualquier asistencia falla para todo rol incluido superadmin; asistencia audita. |
| 7 | KPIs del dashboard solo suman datos de la JASS del contexto; superadmin en supervisión recibe 403 al intentar POST de socio/pago; vocal recibe 403 en auditoría (8.4). |
| 8 | Consulta de deuda: DNI válido devuelve periodos pendientes y NO devuelve teléfono/dirección (assert sobre el payload); 3 fallos → 429 con bloqueo, mismo DNI en otra JASS no bloqueado, otra IP no bloqueada (clave 8.5); página de transparencia no expone nombres/DNI de socios; login con throttle. |
| 9 | Suite completa en verde; smoke test de cada ruta GET autenticada por rol (status 200) usando un dataset de rutas. |

---

## 6. Checklist de seguridad → feature concreta de Laravel

| # | Requisito (spec §7.1) | Implementación concreta | Fase |
|---|---|---|---|
| S1 | SQL Injection | Solo Eloquent/Query Builder; prohibido `DB::raw` con input; los agregados de dashboards usan `selectRaw` únicamente con literales, jamás con variables | Todas |
| S2 | XSS | Escape por defecto de React; prohibido `dangerouslySetInnerHTML` (regla en CLAUDE.md; el contenido de anuncios se renderiza como texto plano con saltos de línea) | Todas |
| S3 | CSRF | Middleware `ValidateCsrfToken` + manejo automático de Inertia; no eximir rutas | 0 |
| S4 | Autorización por rol y tenant | 3 capas: middleware `AsegurarRol`, `TenantScope` global, Policies con verificación explícita de `jass_id` (sección 3) | 1+ |
| S5 | Rate limiting | `throttle` en login (starter) + `RateLimiter::for('consulta-deuda')` clave `ip\|jass\|dni`, 3 intentos / 15 min (8.5) | 1, 8 |
| S6 | Cabeceras de seguridad en el código | Middleware `CabecerasSeguridad` global (no config del servidor web) | 0 |
| S7 | Archivos sensibles | Comprobantes en `storage/app/private` (disco `local`), servidos por controller con `authorize`; nada en `public/`; `.env`, `storage/`, `.git` fuera de webroot (documentado en README de despliegue) | 5 |
| S8 | Validación de uploads | `File::types([...])->max(5120)` — Laravel valida MIME real, no solo extensión | 5 |
| S9 | Contraseñas | Hash bcrypt por defecto; `password` en `$hidden`; nunca en logs ni en auditoría (`detalles` JSON jamás incluye credenciales) | 1 |
| S10 | Auditoría inmutable | Tabla append-only: sin rutas de mutación, policy negadora, escritura solo vía servicio | 1 |
| S11 | Confirmación de contraseña | `password.confirm` en: asistencia, reemplazo de medidor, eliminar JASS/socio/usuario | 1, 2, 6 |
| S12 | Cálculos confiables | Monto de consumo y correlativo de recibo calculados exclusivamente en servidor, dentro de transacción con `lockForUpdate` | 3 |
| S13 | Exposición mínima en público | Rutas públicas serializan arrays explícitos de campos, nunca modelos completos | 8 |
| S14 | Mass assignment | `$fillable` estricto en todos los modelos; `jass_id` lo asigna el trait, nunca el request | 1+ |

---

## 7. Preguntas al autor del proyecto

Ambigüedades **no** cubiertas por la sección 8 de la spec. El plan avanza con el default indicado; ninguna bloquea el inicio salvo P1.

- **P1 (bloqueante antes de Fase 3, es la propia 8.6):** confirmar que el mantenimiento se cobra **por vivienda** (default aplicado en el esquema: `pagos.vivienda_id` obligatorio).
- **P2 — Credencial de login:** la spec dice "credenciales" sin definir el identificador. Default: **email** (estándar Laravel). Si los dirigentes no usan correo, la alternativa es usuario o DNI — cambiarlo después de Fase 1 es barato, pero conviene decidirlo ya.
- **P3 — ¿Existe tarifa fija de mantenimiento (y de consumo sin medidor) por JASS?** El brief los deja como monto manual en cada cobro; por eso la morosidad se expresa como *periodos pendientes* y no como monto en S/. Si hubiera tarifa fija por JASS, se podría mostrar deuda en soles (cambio pequeño: 2 columnas en `jass`).
- **P4 — ¿Desde qué fecha se computa la deuda de una vivienda?** Default: desde su fecha de registro en el sistema. Alternativa: campo "inicio de cobros" editable por vivienda (para no marcar como morosos periodos previos a la adopción del sistema).
- **P5 — ¿La morosidad cubre también consumo o solo mantenimiento?** La spec §6.8 dice "mantenimiento o consumo"; default: ambos, como periodos sin pago registrado.
- **P6 — ¿Se puede anular un recibo emitido por error de digitación?** La spec no lo contempla y los pagos son inmutables por rutas. Default del plan: sin anulación en el MVP; si se necesita, proponer "anulación con motivo + auditoría" (nunca edición/borrado).
- **P7 — ¿Un mismo DNI puede existir en dos JASS?** Default: sí (unicidad por JASS), porque una persona podría tener viviendas en dos comunidades.
- **P8 — ¿El vocal ve la ficha completa del socio (teléfono, dirección) o solo su estado de deuda?** Default: ficha completa en solo lectura.
- **P9 — Transparencia pública:** ¿rango temporal y detalle de gastos? Default: año en curso con selector de año; gastos con descripción/monto/tipo, sin fotos.
- **P10 — ¿Una reunión cancelada puede reprogramarse?** Default: no; se edita solo mientras está `programada`, una cancelada queda cerrada y se crea otra.

---

✅ Fase 0 completada — 39 tests en verde — d3e4f5b73c6d066c63c2f7de7af49b967d0d23c0
