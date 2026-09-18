# YakuCore — Sistema de diseño

Documento de referencia obligatorio para toda UI del proyecto (CLAUDE.md, regla 7). No se improvisa: toda vista nueva sigue lo definido aquí. Se crea en la Fase 1.5 del plan y se aplica retroactivamente al login y a los CRUDs de la Fase 1.

---

## 1. Prohibiciones (spec §7.2)

No se usan, bajo ninguna circunstancia:

- Gradientes decorativos.
- Emojis en la UI (íconos son siempre Lucide).
- Colores tipo neón / "cyberpunk".
- Animaciones genéricas de plantilla (fade-ins decorativos, parallax, etc.).
- Copy genérico de SaaS ("¡Bienvenido de nuevo! 🎉", "¡Todo listo!", etc.). El tono es directo, institucional, en español neutro de Perú.
- Colores o tipografía por defecto de Tailwind/shadcn sin pasar por los tokens de este documento.

---

## 2. Paleta

Paleta obligatoria (spec §7.2), definida como tokens Tailwind v4 en `resources/css/app.css` (Fase 0):

| Token | Hex | Uso |
|---|---|---|
| `yaku-900` | `#021F4A` | Texto/fondo de máximo contraste, `card` en modo oscuro |
| `yaku-800` | `#06396C` | `primary` (modo claro) — color de marca principal |
| `yaku-600` | `#065B96` | `ring`, enlaces, acentos activos |
| `yaku-400` | `#6496B1` | `accent`, `primary` (modo oscuro) |
| `yaku-200` | `#B1CCDF` | `secondary` (modo claro), fondos suaves |

Neutros derivados (`yaku-neutral-{50,200,400,600,800,950}`) para texto secundario, bordes y fondos. Estados semánticos armonizados con la paleta (no colores saturados genéricos):

| Token | Hex | Uso |
|---|---|---|
| `yaku-exito` / `yaku-exito-suave` | `#0F6E5C` / `#DCEEE8` | Confirmaciones, estado "al día", "finalizada" |
| `yaku-error` | `#9B2C2C` | Errores, "moroso", "cancelada" |
| `yaku-advertencia` | `#8A5A15` | Avisos, "pendiente" |

### Mapeo a shadcn (`:root` / `.dark` en `app.css`)

Todos los componentes shadcn (Button, Input, Card, Select, ...) leen `--primary`, `--secondary`, `--accent`, `--destructive`, `--background`, `--foreground`, `--border`, etc. Estas variables están mapeadas a la paleta institucional — **nunca** se sobreescriben con valores sueltos en un componente. `--destructive` usa `yaku-error` (no el rojo por defecto de shadcn). El modo oscuro invierte la jerarquía tonal (fondo `yaku-neutral-950`, `card` en `yaku-900`) manteniendo el mismo mapeo semántico.

Para badges de estado (ver §5) se usan directamente `yaku-exito`, `yaku-error`, `yaku-advertencia` y sus variantes "suave" como fondo — no `--destructive`/`--primary`, que son para acciones, no para indicar estado de un registro.

---

## 3. Tipografía

- **Texto general:** IBM Plex Sans (variable), autohospedada vía `@fontsource-variable/ibm-plex-sans`. Token `--font-sans`.
- **Cifras y recibos:** IBM Plex Mono, autohospedada vía `@fontsource/ibm-plex-mono` (pesos 400/500/600). Token `--font-mono`, clase utilitaria `font-mono`. Se usa en montos, números de recibo, DNI, lecturas de medidor y cualquier tabla de cifras — la tabular alineada facilita lectura y comparación.
- **Sin CDN externo**: ambas familias se instalan por npm y se importan en `resources/js/app.tsx`. `resources/views/app.blade.php` no enlaza ninguna fuente externa (se retiró el enlace a `fonts.bunny.net` del starter kit).

Motivo de la elección (partiendo de la sugerencia de la spec): IBM Plex es una familia abierta, diseñada para lectura técnica/institucional, con buen soporte de caracteres latinos y una mono a juego — encaja con el tono "utilitario, no genérico" pedido en spec §7.2 y con el uso intensivo de cifras del sistema (pagos, lecturas, recibos).

---

## 4. Iconografía

Únicamente [Lucide](https://lucide.dev) (`lucide-react`, ya integrado con shadcn). Nunca emoji. Tamaño por defecto `size-4` (16px) en línea con texto, `size-5` en botones de acción destacados.

---

## 5. Componentes base

### Botón, Input, Card, Select, Checkbox
Los de shadcn/ui tal cual (`resources/js/components/ui/`), sin modificar — heredan la paleta automáticamente vía las variables de §2.

### Badge de estado — `components/yaku/BadgeEstado.tsx`
Componente nuevo para estados de negocio (reunión programada/cancelada/finalizada, anuncio visible/inactivo, asistencia presente/ausente, vivienda al día/morosa). Variantes `exito | error | advertencia | neutro`, fondo "suave" + texto del tono sólido correspondiente. Reemplaza cualquier uso de `Badge` de shadcn con colores por defecto para indicar estado de negocio.

### Badge de rol — `components/yaku/BadgeRol.tsx`
Identidad visual por rol de usuario (no confundir con `BadgeEstado`, que indica estado de un registro): presidente celeste, tesorero rojo, vocal amarillo, superadmin azul. Tokens `yaku-rol-{presidente,tesorero,vocal,superadmin}` (+ variante "suave" para el fondo), mismo patrón que los badges de estado. Se muestra en la cabecera del panel (junto a las migas de pan) y en los listados de usuarios.

### Tabla responsiva — `components/yaku/TablaResponsiva.tsx`
Patrón obligatorio para toda tabla de datos dense (spec §7.3): **tabla real en viewport ≥ `md` (768px)**, **tarjetas apiladas en `< md`**. Encapsula el patrón una sola vez — ninguna página implementa su propia versión mobile de una tabla. API: columnas declarativas (`{ etiqueta, render(fila) }`) + `filas`; internamente renderiza `<table>` en desktop (`hidden md:table`) y una lista de tarjetas en mobile (`md:hidden`), reutilizando el mismo `render` por columna para no duplicar la lógica de presentación de cada celda.

### Recibo imprimible (referencia para la Fase 3)
No hay componente todavía (no existe el módulo de Pagos hasta la Fase 3), pero el patrón queda decidido aquí para no improvisarlo entonces: página dedicada con estilos `@media print` (oculta nav/sidebar, fuerza fondo blanco y texto negro, tamaño carta/A4), cifras en `font-mono`, y un bloque final con línea horizontal + etiqueta "Firma" para la firma física del tesorero o presidente (regla 5.9 — la firma nunca es digital).

---

## 6. Espaciado y layout

Se usa la escala por defecto de Tailwind (múltiplos de 4px) sin token adicional. Convenciones:
- Contenedor de página: `p-4` en mobile, `p-6` en `md+`.
- Separación entre secciones/bloques de un formulario: `gap-6`.
- Separación entre campo y su label/error: `gap-2`.
- Radio de esquina: `--radius: 0.5rem` (heredado por `rounded-lg/md/sm` vía los tokens `--radius-*` ya definidos).

---

## 7. Layouts

Tres layouts (`resources/js/layouts/`), todos construidos sobre el mismo shell de sidebar (`AppShell`/`AppContent`) pero con navegación propia — nunca comparten el mismo componente de sidebar porque cada rol ve destinos distintos:

- **`PanelLayout`** (`panel-layout.tsx`): presidente/tesorero/vocal, y superadmin en modo supervisión. Nav: Panel (dashboard) y, solo si el usuario es presidente, Usuarios.
- **`SuperadminLayout`** (`superadmin-layout.tsx`): superadmin en su propio dominio administrativo (JASS). Nav: JASS.
- **`PublicoLayout`** (`publico-layout.tsx`): portal público (Fase 8) — sin sidebar, header simple con logo + "Acceder al sistema", footer institucional. Se crea el layout ahora (vacío de contenido real) para no tener que decidir su estructura de nuevo en la Fase 8.

---

## 8. Responsivo

Punto de quiebre único: `md` (768px), consistente con `TablaResponsiva`. Todo formulario usa `grid gap-*` de una sola columna en mobile; el sidebar colapsa a ícono/hoja (`Sheet`) por debajo de `lg`, comportamiento ya provisto por el componente `Sidebar` de shadcn.
