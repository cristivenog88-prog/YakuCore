# YakuCore

Sistema de gestión multi-tenant para JASS (Juntas Administradoras de Servicios de Saneamiento) de la zona de Huaraz.

> Este README se completa en la Fase 9 del plan (`docs/PLAN-YAKUCORE.md`). Por ahora documenta lo mínimo necesario para levantar el entorno y las credenciales demo de la Fase 1.

## Entorno local

- Laravel 12 (PHP 8.2), MySQL en el puerto **3307**, base de datos `BDyakucore`.
- Frontend: Inertia v2 + React 19 + TypeScript, Tailwind v4, shadcn/ui, gestionado con **pnpm**.

```bash
composer install
pnpm install
php artisan migrate
pnpm run build   # o: composer run dev
```

## Credenciales demo (seeder)

`php artisan migrate:fresh --seed` crea 1 superadmin y 2 JASS demo (una con medidor, otra sin) con presidente/tesorero/vocal cada una. Todas las cuentas usan la contraseña `password`.

| Rol | JASS | Email |
|---|---|---|
| Superadmin | — | `superadmin@yakucore.pe` |
| Presidente | Villa Sol (con medidor) | `presidente.villasol@yakucore.pe` |
| Tesorero | Villa Sol (con medidor) | `tesorero.villasol@yakucore.pe` |
| Vocal | Villa Sol (con medidor) | `vocal.villasol@yakucore.pe` |
| Presidente | Los Andes (sin medidor) | `presidente.losandes@yakucore.pe` |
| Tesorero | Los Andes (sin medidor) | `tesorero.losandes@yakucore.pe` |
| Vocal | Los Andes (sin medidor) | `vocal.losandes@yakucore.pe` |

## Comandos

- `composer test` — suite Pest
- `composer lint` — Laravel Pint
- `composer dev` — servidor + queue + Vite en paralelo
