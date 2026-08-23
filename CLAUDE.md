# CLAUDE.md — YakuCore

Sistema de gestión multi-tenant para JASS (juntas de agua) de Huaraz. Cada JASS es un tenant; el scoping por `jass_id` es la regla de seguridad más importante del proyecto.

## Documentos de referencia (leer antes de programar)
- `PLAN-YAKUCORE.md` — plan por fases. Se ejecuta **una fase a la vez, en orden**.
- `YakuCore-Spec-para-Fable5.md` — spec funcional. Las decisiones de negocio están en su sección 8; cítalas por número.
- `docs/design-system.md` — sistema de diseño (existe desde la Fase 1.5). Toda UI lo sigue.

## Stack (fijo — no proponer alternativas)
Laravel 12 · PHP 8.2 · MySQL en puerto **3307**, BD `BDyakucore` · Pest · Inertia v2 · React 19 + TypeScript · Tailwind v4 · shadcn/ui · Vite. Sin Redux/Zustand.

## Comandos
- `composer test` → suite Pest (obligatorio en verde antes de cerrar cualquier fase o tarea)
- `composer lint` → Pint
- `npm run dev` / `npm run build`

## Reglas duras
1. **Tenancy:** todo modelo de negocio usa el trait `PerteneceAJass` (scope global + asignación de `jass_id`). Jamás aceptar `jass_id` desde el request. Toda ruta nueva necesita test de scoping cruzado entre dos JASS.
2. **Dinero y lecturas:** montos, correlativos de recibo y validación de lecturas se calculan SOLO en servidor (`Services/Pagos`), dentro de transacción. El frontend nunca es fuente de verdad.
3. **Migraciones:** nunca editar una migración ya ejecutada; crear una nueva.
4. **Inmutables:** `asistencias`, `auditoria` y `pagos` no tienen rutas de update/delete. No crearlas.
5. **Idioma:** toda cadena visible por el usuario (UI, validaciones, errores) en español. Nada en inglés llega al usuario.
6. **Seguridad:** prohibido `dangerouslySetInnerHTML`, `{!! !!}` y `DB::raw` con variables. Uploads solo con `File::types()->max()`. Archivos sensibles en `storage/app/private`, servidos por controller con `authorize`.
7. **UI:** prohibidos gradientes decorativos, emojis, neón y copy genérico de SaaS. Paleta y tipografías: las de `docs/design-system.md`. Iconos: Lucide. Tablas densas: componente `TablaResponsiva` (tabla en desktop, tarjetas en móvil).
8. **Autorización:** cada modelo tiene Policy; los controllers siempre llaman `authorize`. Superadmin es solo-lectura en módulos operativos (supervisión).
9. **Auditoría:** toda mutación de negocio pasa por `RegistradorAuditoria` (nunca escribir el modelo `Auditoria` directo).
10. **Ambigüedad:** si algo no está resuelto en la spec §8 ni en la sección 7 del plan, detente y pregunta. No inventes reglas de negocio.

## Convenciones
- Lógica de negocio en `app/Services/{Modulo}`; controllers delgados.
- FormRequest para toda validación (nunca `$request->validate()` inline en controllers con lógica).
- Enums PHP en `app/Enums` para roles, estados y tipos.
- Tests Pest de feature por módulo; helpers `actingComoPresidente(Jass)` etc. en `tests/Pest.php`.
- Commits con prefijo de fase: `F2: CRUD de viviendas`.
