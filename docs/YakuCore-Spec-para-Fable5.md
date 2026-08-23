# YakuCore — Especificación funcional y técnica para planificación

> **Instrucción para ti (Claude Fable 5):** actúa como el mejor arquitecto e ingeniero de software. A partir de este documento debes producir un **plan de implementación detallado, por fases, sin ambigüedades**, para que **Claude Code (Sonnet 5)** lo ejecute sin errores ni retrabajo. No inventes funcionalidades que no estén aquí. Si una regla de negocio no está clara, revisa la sección 8 ("Decisiones abiertas") antes de asumir nada — ahí ya te doy la decisión por defecto que debes tomar si no te confirman lo contrario. Al final del documento (sección 10) te digo exactamente qué formato debe tener tu plan.

---

## 1. Resumen del proyecto

**Nombre:** YakuCore
**Qué es:** sistema web de gestión para las JASS (Juntas Administradoras de Servicios de Saneamiento) de la zona de Huaraz. Cada JASS administra el cobro de agua y mantenimiento a sus socios, sus reuniones, sus anuncios y sus gastos. YakuCore centraliza esto para múltiples JASS bajo un mismo sistema, con un superadministrador que supervisa todas, y una página pública informativa/de transparencia.


**Analogía de dominio:** es un sistema multi-tenant donde cada "tenant" es una JASS. Un socio pertenece a una JASS. Un socio puede tener varias viviendas. Cada vivienda puede tener o no un medidor, dependiendo de si esa JASS trabaja con medición o no.

---

## 2. Stack tecnológico (fijo, no proponer alternativas)

| Capa | Tecnología |
|---|---|
| Backend | Laravel 12, PHP 8.2 |
| Base de datos | MySQL, puerto **3307** (no 3306 — ya hay otro servicio local usando ese puerto) |
| Nombre de la base de datos | **⚠️ Sin definir en el brief original — ver sección 8.1** |
| Testing backend | Pest |
| Frontend | Inertia.js v2 + React 19 + TypeScript |
| Estilos | Tailwind CSS v4 |
| Componentes UI | shadcn/ui sobre Radix UI |
| Build tool | Vite |

No se debe introducir un framework de estado adicional (Redux, Zustand, etc.) salvo que el plan justifique por qué Inertia + React state no alcanza para un caso puntual.

---

## 3. Actores y permisos

| Acción / módulo | Superadmin | Presidente | Tesorero | Vocal |
|---|---|---|---|---|
| Crear / editar / eliminar JASS | ✅ | ❌ | ❌ | ❌ |
| Ver estadísticas globales de todas las JASS | ✅ | ❌ | ❌ | ❌ |
| Entrar a supervisar cualquier JASS | ✅ | ❌ | ❌ | ❌ |
| Crear / editar / eliminar usuarios (tesorero, vocal) | ✅ (dentro de su JASS) | ✅ | ❌ | ❌ |
| Registrar / editar / eliminar socios | — | ✅ | ✅ | ❌ (solo lectura) |
| Cobrar mantenimiento | — | ✅ | ✅ | ❌ |
| Cobrar consumo de agua | — | ✅ | ✅ | ❌ |
| Cobrar morosidad | — | ✅ | ✅ | ❌ |
| Consultar deudas (cuando se acerca un poblador) | — | ✅ | ✅ | ✅ |
| Crear / editar / eliminar reuniones | — | ✅ | ✅ | ❌ |
| Tomar asistencia | — | ✅ | ✅ | ❌ |
| Crear anuncios | — | ✅ | ✅ | ❌ |
| Registrar gastos | — | ✅ | ✅ | ❌ |
| Ver auditoría | — | ✅ | ✅ | ❌ (a confirmar, ver 8.4) |

Un usuario (presidente/tesorero/vocal) pertenece a **una sola JASS**. El superadmin no pertenece a ninguna JASS, es global.

---

## 4. Modelo de dominio (entidades y relaciones)

Esto describe el dominio en lenguaje natural — el diseño exacto de tablas, claves foráneas, índices y migraciones es responsabilidad de tu plan (sección 10).

- **JASS**: nombre, comunidad/centro poblado, distrito, `tiene_medidor` (booleano), tarifa base por m³, periodicidad de cobro de mantenimiento (mensual, trimestral, etc.).
- **Usuario**: pertenece a una JASS (excepto superadmin), rol (superadmin | presidente | tesorero | vocal), nombre, credenciales.
- **Socio** (poblador): pertenece a una JASS, nombres, apellidos, DNI, teléfono, dirección.
- **Vivienda**: pertenece a un socio. Un socio puede tener **más de una vivienda**. Si la JASS tiene medidor, cada vivienda puede tener un medidor asociado.
- **Medidor**: pertenece a una vivienda (si la JASS usa medición). Código con formato `{PrefijoJASS}-MED-{correlativo}` — el prefijo se genera automáticamente a partir de la JASS (ej. `JASSHuaraz-MED-`), el usuario solo ingresa el número.
- **PagoMantenimiento**: socio, día de pago, monto, concepto (mensual/trimestral/etc.), número de recibo autogenerado.
- **PagoConsumoAgua (con medidor)**: socio, medidor, lectura anterior (autocompletada con la última lectura registrada), lectura actual, monto calculado = `(lectura_actual - lectura_anterior) * tarifa_base`, día de pago, recibo autogenerado.
- **PagoConsumoAgua (sin medidor)**: socio, vivienda, monto (ingresado manualmente), día de pago, recibo autogenerado.
- **Morosidad**: derivada — un socio/vivienda queda como moroso cuando no ha pagado dentro del periodo esperado. Desde este módulo también se realiza el cobro de lo adeudado.
- **Gasto**: JASS, descripción, monto, comprobante (boleta/factura/declaración jurada), foto adjunta (opcional).
- **Reunión**: título, descripción, fecha (no puede ser anterior a hoy), hora, lugar, puntos a tratar, estado (`programada` | `cancelada` | `finalizada`).
- **Asistencia**: por reunión, por socio, estado (`presente` | `ausente`). Se guarda una sola vez, requiere reconfirmar la contraseña del usuario que la registra, y **queda inmutable** tras guardar.
- **Anuncio**: JASS, título, contenido, fecha de publicación, estado (`visible` | `inactivo`).
- **Auditoría**: usuario, acción, entidad afectada, fecha/hora. Registro de solo lectura (append-only).

---

## 5. Reglas de negocio críticas

1. Un socio puede tener **N viviendas**. Cada vivienda es una unidad de cobro independiente.
2. Si la JASS **tiene medidor**: cada vivienda con conexión puede tener su propio medidor (un socio con 2 viviendas puede tener 2 medidores). El consumo se cobra por medidor.
3. Si la JASS **no tiene medidor**: el cobro de agua es por vivienda. Si un socio tiene 2 viviendas, hace 2 pagos independientes.
4. El código del medidor se autogenera con el prefijo de la JASS; el usuario solo digita el correlativo.
5. En el pago de consumo con medidor, la lectura anterior se autocompleta con la última lectura registrada para ese medidor (si no hay pago previo, se ingresa manualmente o se define como 0 — ver 8.2).
6. El sistema debe **validar** que la lectura actual no sea menor a la lectura anterior (evitar montos negativos), salvo un flujo explícito de "cambio de medidor" (ver 8.3).
7. El monto de consumo de agua = `(lectura_actual − lectura_anterior) × tarifa_base_m³`.
8. Cada pago genera un número de recibo autogenerado y correlativo por JASS.
9. Los recibos son imprimibles, con espacio para firma manual del tesorero o presidente (la firma no es digital, es física sobre el papel impreso).
10. El presidente puede hacer todo lo que hace el tesorero, más gestionar usuarios (crear/editar/eliminar tesoreros y vocales).
11. El tesorero puede hacer todo lo mismo que el presidente **excepto** gestionar usuarios.
12. El vocal solo tiene acceso de lectura, principalmente para resolver consultas de pobladores sobre deudas.
13. El superadmin no gestiona socios directamente; su rol es de creación/configuración de JASS, supervisión y estadísticas globales, y puede entrar a cualquier JASS para revisar su operación.
14. Todos los datos deben reflejarse de inmediato en las demás vistas relevantes tras un registro (ver alcance exacto en 7.5 — no implica necesariamente WebSockets).
15. Toda acción relevante de los usuarios queda registrada en auditoría, de forma inmutable.
16. La búsqueda pública de deudas se limita a 3 intentos (ver mecanismo exacto en 8.5).

---

## 6. Módulos funcionales

### 6.1 Autenticación y superadmin
- Login único para todos los roles; el sistema redirige según el rol.
- El superadmin ve un panel con estadísticas agregadas de todas las JASS (número de socios afiliados por JASS, recaudación, etc.) y puede entrar al panel de cualquier JASS en modo supervisión.

### 6.2 Gestión de JASS (superadmin)
- Alta de JASS: nombre, comunidad/centro poblado, distrito, si tiene medidor, tarifa base por m³, periodicidad de cobro de mantenimiento.
- CRUD de los 3 usuarios operativos de cada JASS (presidente, tesoreros, vocales) — realizado por el superadmin o por el presidente de esa JASS.

### 6.3 Dashboard de JASS
- Estadísticas propias de esa JASS únicamente: indicadores, montos recaudados, número de socios, morosidad, etc. (definir el set exacto de KPIs en el plan, con base en las entidades del módulo 4).

### 6.4 Socios / pobladores
- Registro: nombres, apellidos, DNI, teléfono, dirección.
- Gestión de viviendas asociadas al socio (una o varias).
- Gestión de medidores por vivienda (si la JASS tiene medidor).

### 6.5 Mantenimiento
- Selección de socio (ver 8.6 sobre si es por socio o por vivienda), día de pago, monto, concepto (mensual/trimestral, etc.), número de recibo autogenerado.

### 6.6 Consumo de agua — con medidor
- Selección de socio → medidor asociado (puede tener más de uno).
- Lectura anterior (autocompletada), lectura actual (manual), cálculo automático del monto, día de pago.
- Recibo imprimible con espacio de firma.

### 6.7 Consumo de agua — sin medidor
- Selección de socio → vivienda, monto manual, día de pago, recibo imprimible con espacio de firma.

### 6.8 Morosidad / deudores
- Listado de socios/viviendas con pagos pendientes (mantenimiento o consumo).
- Desde aquí se realiza el cobro de lo adeudado.

### 6.9 Gastos
- Registro de gastos de los dirigentes: descripción, monto, tipo de comprobante (boleta, factura, declaración jurada), foto del comprobante (opcional).

### 6.10 Reuniones y asistencia
- CRUD de reuniones: título, descripción, fecha (≥ hoy), hora, lugar, puntos a tratar.
- Estados: `programada`, `cancelada`, `finalizada`.
- Botón "tomar asistencia": lista de socios, marcar presente/ausente. Al guardar, se exige la contraseña del usuario para confirmar, y el registro queda **bloqueado** (no editable) desde ese momento. Al guardar la asistencia, la reunión pasa a `finalizada`.

### 6.11 Anuncios
- Título, contenido, fecha de publicación, estado `visible`/`inactivo`.

### 6.12 Auditoría
- Bitácora de todas las acciones relevantes por usuario (crear, editar, eliminar, cobrar, tomar asistencia, etc.), con fecha/hora. Solo lectura, ni el superadmin puede editarla o borrarla.

### 6.13 Portal público
- Página general (sin login) con botón "Acceder al sistema".
- Buscador de JASS por nombre → muestra anuncios, reuniones y un **portal de transparencia** (dinero recaudado por tipo de cobro, y gastos) de esa JASS.
- Buscador de deuda personal: el poblador elige su JASS, ingresa DNI y número de medidor (si no tiene, lo indica), y ve su estado de cuenta. Máximo 3 intentos por motivos de seguridad (mecanismo detallado en 8.5).

---

## 7. Requisitos no funcionales

### 7.1 Seguridad
- Prevención de SQL Injection: uso exclusivo de Eloquent/Query Builder con bindings, cero SQL crudo concatenado.
- Prevención de XSS: Blade/React ya escapan por defecto — prohibido usar `dangerouslySetInnerHTML` o `{!! !!}` salvo justificación explícita revisada en el plan.
- CSRF: tokens de Laravel/Inertia activos en todos los formularios.
- Autorización: Policies/Gates de Laravel por rol y por JASS (un tesorero de la JASS A nunca debe poder ver ni mutar datos de la JASS B — scoping estricto por `jass_id`).
- Rate limiting en login y en el buscador público de deudas.
- Cabeceras de seguridad vía Middleware de Laravel (no depender de config de Nginx/Apache, para que viaje con el código): `X-Frame-Options`, `X-Content-Type-Options`, `Content-Security-Policy`, `Referrer-Policy`, `Strict-Transport-Security` (cuando haya HTTPS).
- Contraseñas con hashing (bcrypt/argon2 vía Laravel por defecto), nunca en texto plano ni en logs.
- Ningún archivo sensible expuesto: `.env`, `storage/`, backups, `.git`, no accesibles públicamente. Las fotos de comprobantes deben ir en `storage` con acceso controlado, no en `public` directo si contienen datos sensibles.
- Validación de subida de archivos (tipo MIME real, tamaño máximo) para las fotos de facturas.
- Auditoría inmutable como mecanismo de trazabilidad ante disputas o mal uso.
- Confirmación de contraseña (`password.confirm` de Laravel) para acciones irreversibles: registrar asistencia, y cualquier otra acción que el plan identifique como crítica.

### 7.2 Diseño / UI
- **Prohibido:** gradientes decorativos, emojis, colores tipo "cyberpunk"/neón, animaciones genéricas de plantilla, copy genérico de SaaS ("¡Bienvenido de nuevo! 🎉").
- **Paleta obligatoria** (institucional, tono agua/Andes): `#021F4A`, `#06396C`, `#065B96`, `#6496B1`, `#B1CCDF` (usar como base, se pueden derivar neutros y estados como éxito/error/advertencia que armonicen con esta paleta, no colores saturados genéricos).
- Tipografía e iconografía "de internet" (reales, con licencia abierta), **no genéricas por defecto de Tailwind/shadcn sin decisión intencional**. Sugerencia de partida a evaluar en la fase de diseño: una tipografía utilitaria/técnica (ej. Archivo, IBM Plex Sans) en vez de Inter/Roboto por defecto, con una monoespaciada (ej. IBM Plex Mono) para cifras y recibos. Iconos: Lucide (ya integrable con el stack shadcn) o Phosphor, nunca emoji.
- Este documento no fija el sistema de diseño completo a propósito: eso se define en una fase dedicada (`docs/design-system.md`) siguiendo el flujo de trabajo habitual del autor (CLAUDE.md corto + design-system.md aparte), no debe improvisarse dentro del plan de arquitectura.

### 7.3 Responsivo
- Todo el sistema, incluyendo tablas de datos densas (socios, pagos), debe ser usable en celular. El plan debe indicar el patrón a seguir para tablas complejas en mobile (ej. tarjetas apiladas en vez de tabla horizontal con scroll).

### 7.4 Idioma
- Toda la interfaz, mensajes de validación, errores y notificaciones en **español**. Nada de mensajes de Laravel/JS por defecto en inglés llegando al usuario final.

### 7.5 "Tiempo real" — alcance exacto
El brief original pide que "todo esté en tiempo real". Para el plan, esto se interpreta como:
- **Dentro de una misma sesión/navegación:** comportamiento SPA de Inertia (sin recarga completa de página, los datos nuevos aparecen al navegar entre vistas). Esto se cubre solo con Inertia + React, sin infraestructura adicional.
- **Entre distintos usuarios conectados simultáneamente** (ej. tesorero ve en vivo cuando el presidente registra un socio): esto **requiere WebSockets** (Laravel Reverb/Echo) y es una decisión de alcance, no algo implícito. Ver decisión por defecto en 8.7.

### 7.6 Testing
- Pest para el backend: mínimo, tests de features por cada módulo de negocio (cobros, cálculo de consumo, permisos por rol, scoping por JASS) y tests de autorización (que un tesorero de una JASS no pueda tocar datos de otra).

---

## 8. Decisiones abiertas (con decisión por defecto)

Estas son ambigüedades reales del brief original. Para cada una doy la decisión por defecto que debes tomar en el plan si no se indica lo contrario — así no te detienes a preguntar y el plan avanza.

**8.1 — Nombre de la base de datos.** El brief original lo dejó vacío. **Default: `BDyakucore`.**

**8.2 — Primera lectura de un medidor nuevo.** No se especifica el valor inicial de "lectura anterior" cuando un medidor no tiene pagos previos. **Default: 0**, editable manualmente por el tesorero/presidente si el medidor ya venía con un acumulado real al momento de registrar el sistema.

**8.3 — Cambio o reemplazo de medidor.** El brief no contempla qué pasa si se cambia un medidor físico (la lectura actual sería menor a la anterior por un reinicio). **Default:** agregar un flujo explícito de "reemplazo de medidor" que resetea la lectura base a 0 y dejar traza en auditoría, en vez de solo bloquear el pago.

**8.4 — Acceso del vocal a auditoría.** No está explícito si el vocal puede ver el módulo de auditoría. **Default: sin acceso**, coherente con que su rol es solo de consulta de deudas de terceros, no de gestión.

**8.5 — Mecanismo de los "3 intentos" en el buscador público de deuda.** No se especifica si es por sesión, por IP, o con qué ventana de tiempo. **Default:** limitar por combinación de IP + JASS + DNI consultado, usando el `RateLimiter` de Laravel, con bloqueo temporal (ej. 15 minutos) tras el tercer intento fallido, sin captcha en el MVP (se puede añadir después si hay abuso).

**8.6 — Mantenimiento: ¿se cobra por socio o por vivienda?** El brief dice que el cobro de mantenimiento selecciona al "socio", pero el cobro de consumo sin medidor es explícitamente por vivienda ("1 persona con 2 viviendas hace 2 pagos"). Esto es inconsistente si se deja "por socio". **Default: el mantenimiento se cobra por vivienda**, igual que el consumo sin medidor — un socio con 2 viviendas paga mantenimiento 2 veces. Esta es la decisión con más impacto en el esquema de base de datos de todo el documento; si el autor del proyecto no confirma esto explícitamente, es la primera pregunta que debe hacérsele antes de programar el módulo de mantenimiento.

**8.7 — Alcance de "tiempo real" entre usuarios distintos.** Ver 7.5. **Default para el MVP de 2 meses: NO implementar WebSockets.** Se resuelve con recarga de datos de Inertia al navegar/enfocar la pestaña (`router.reload` con `only`), y se deja Laravel Reverb como mejora de fase 2 si el negocio lo pide explícitamente.

**8.8 — Cómo se determina "moroso".** No hay un campo de fecha de vencimiento definido en el brief. **Default:** derivar la fecha de vencimiento a partir de la periodicidad configurada en la JASS (mensual/trimestral) más un día de vencimiento configurable a nivel de JASS; un job programado (Laravel Scheduler) marca como moroso a quien no tenga pago registrado dentro del periodo vencido.

---

## 9. Fuera de alcance para este plan (fase 2, no para los 2 meses locales)

- Infraestructura de producción hostinger basico.
- WebSockets / tiempo real entre usuarios distintos (salvo confirmación explícita, ver 8.7).
- Firma digital de recibos (se mantiene firma física sobre el impreso).
- Captcha en el portal público (se evalúa si hay abuso real).
- Notificaciones push/email a socios.

---

## 10. Qué debe entregar tu plan (Fable 5)

Tu respuesta debe incluir, en este orden:

1. **Mapa de módulos y límites** (monolito modular): qué carpetas/dominios existen en el backend Laravel (ej. `app/Domain/JASS`, `app/Domain/Socios`, `app/Domain/Pagos`, `app/Domain/Reuniones`, etc.) y cómo se relacionan, sin acoplarse entre sí más de lo necesario.
2. **Esquema de base de datos completo**: tablas, columnas, tipos, claves foráneas, índices, y normalización explicada brevemente por qué evita redundancia. Debe reflejar las entidades de la sección 4 y las decisiones de la sección 8.
3. **Estrategia de autorización**: cómo se implementan las Policies/Gates para el scoping por rol y por JASS de la sección 3.
4. **Plan por fases**, cada fase con tareas atómicas y verificables (ej. "Fase 1: modelos + migraciones + seeders de JASS/Socios/Viviendas/Medidores", "Fase 2: módulo de mantenimiento", etc.), en un orden que evite que Claude Code rompa código ya funcionando al avanzar a la siguiente fase.
5. **Estrategia de testing con Pest** por fase (qué se prueba antes de avanzar a la siguiente).
6. **Checklist de seguridad** mapeado a features concretas de Laravel (Policies, Middleware, RateLimiter, validación de uploads, etc.), no solo la lista genérica de la sección 7.1.
7. **Lista de preguntas al autor del proyecto**, si detectas alguna ambigüedad adicional no cubierta en la sección 8 — no debes asumir nada que no esté aquí decidido.

**Formato de salida esperado:** fases numeradas, cada una con una lista de tareas concretas y accionables (no párrafos narrativos), pensadas para que Claude Code (Sonnet 5) las ejecute una por una sin ambigüedad y sin necesidad de volver a preguntar. Si una tarea depende de una decisión de la sección 8, cítala por su número (ej. "según 8.6, la tabla `mantenimiento_pagos` referencia `vivienda_id`, no `socio_id`").
