# YakuCore

Sistema de gestión multi-tenant para JASS (Juntas Administradoras de Servicios de Saneamiento) de la zona de Huaraz. Cada JASS es un tenant aislado por `jass_id`; un superadmin supervisa todas las JASS en modo solo lectura, y existe un portal público de transparencia y consulta de deuda.

## Stack

- Backend: Laravel 12 (PHP 8.2), MySQL en el puerto **3307**, base de datos `BDyakucore`, testing con Pest.
- Frontend: Inertia v2 + React 19 + TypeScript, Tailwind v4, shadcn/ui, gráficos con Recharts, gestionado con **pnpm** (no usar `npm install`, este proyecto usa `pnpm-lock.yaml`).

## Entorno local

Requisitos: PHP 8.2, Composer, MySQL corriendo en el puerto 3307 con una base de datos `BDyakucore` creada, Node 18+ y pnpm.

```bash
composer install
pnpm install

cp .env.example .env   # ajustar credenciales de MySQL si difieren de las locales
php artisan key:generate

php artisan migrate            # esquema vacío
php artisan migrate:fresh --seed   # esquema + datos demo (recomendado para desarrollo)

pnpm run build      # build de producción de los assets
# o, para desarrollo:
composer dev         # servidor Artisan + queue:listen + Vite, en paralelo
```

La app queda disponible en `http://localhost:8000` (o el host/puerto que use `php artisan serve` / `composer dev`).

### Scheduler (morosidad)

El comando `php artisan yaku:marcar-morosos` recalcula `viviendas.morosa_desde` de todas las JASS y está registrado en `routes/console.php` para correr diariamente vía el Scheduler de Laravel (`php artisan schedule:work` en desarrollo, cron real en producción apuntando a `php artisan schedule:run` cada minuto). No es necesario para probar la morosidad manualmente: cada cobro recalcula en línea el estado de la vivienda que paga (`Services/Pagos/RegistradorPago`).

## Credenciales demo (seeder)

`php artisan migrate:fresh --seed` crea 1 superadmin, 2 JASS demo (una con medidor, otra sin) con presidente/tesorero/vocal cada una, y datos realistas de varios meses (socios, viviendas, medidores, pagos, gastos, reuniones con asistencia tomada, anuncios). Todas las cuentas usan la contraseña `password`.

| Rol | JASS | Email |
|---|---|---|
| Superadmin | — | `superadmin@yakucore.pe` |
| Presidente | Villa Sol (con medidor) | `presidente.villasol@yakucore.pe` |
| Tesorero | Villa Sol (con medidor) | `tesorero.villasol@yakucore.pe` |
| Vocal | Villa Sol (con medidor) | `vocal.villasol@yakucore.pe` |
| Presidente | Los Andes (sin medidor) | `presidente.losandes@yakucore.pe` |
| Tesorero | Los Andes (sin medidor) | `tesorero.losandes@yakucore.pe` |
| Vocal | Los Andes (sin medidor) | `vocal.losandes@yakucore.pe` |

El portal público no requiere sesión: buscar "Villa Sol" o "Los Andes" desde la página de inicio.

## Comandos

- `composer test` — suite Pest completa (obligatorio en verde antes de cerrar cualquier fase o tarea)
- `composer lint` — Laravel Pint
- `composer dev` — servidor Artisan + queue:listen + Vite en paralelo
- `pnpm run build` — build de producción de los assets
- `pnpm run lint` — ESLint sobre `resources/js`

## Estructura del proyecto

- `app/Services/{Modulo}` — lógica de negocio (Pagos, Morosidad, Reuniones, Gastos, Dashboard, Publico, Tenancy, Auditoria). Los controllers son delgados y siempre llaman a un servicio o a `$this->authorize()`.
- `app/Models/Concerns/PerteneceAJass` — trait de scoping multi-tenant: asigna `jass_id` automáticamente al crear (nunca desde el request) y aplica `TenantScope`, que **falla cerrado** (devuelve cero filas) si no hay una JASS activa en `ContextoJass`.
- `app/Services/Tenancy/ContextoJass` — singleton por request con la JASS activa; la fija el middleware `ResolverJassActual` a partir del usuario autenticado (`/panel/*`) o del segmento de ruta (`/supervision/{jass}/*`).
- `app/Policies` — una Policy por modelo de negocio; los controllers nunca mutan sin `$this->authorize()`.
- `resources/js/pages/{panel,superadmin,publico}` — una carpeta por dominio de rutas; `docs/design-system.md` documenta la paleta, tipografías y componentes (`TablaResponsiva`, `BadgeEstado`) obligatorios para toda UI nueva.

## Documentación

- `docs/PLAN-YAKUCORE.md` — plan por fases ejecutado, con el estado de cierre de cada una al final del archivo.
- `docs/YakuCore-Spec-para-Fable5.md` — spec funcional original; las decisiones de negocio con ambigüedad están resueltas en su sección 8 y referenciadas como P1–P10 en los commits de cada fase.
- `docs/design-system.md` — sistema de diseño (paleta, tipografías, componentes).
