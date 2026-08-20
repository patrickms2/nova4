# Portal UI System — Nova v2.0

**Proyecto:** Nova – Portal Taxista  
**Stack:** Filament 5 + Livewire 4 + Vite + Tailwind 4 + Alpine.js  
**Estilo oficial:** Glass Dark UI · Minimal · Mobile First · SaaS 2026

---

## Estructura de archivos del sistema

```
resources/
  css/
    portal.css                          ← CSS único del portal (glass + fondo + overrides Filament)
  views/
    portal/
      hooks/
        background.blade.php            ← Hook de fondo inyectado via RenderHook BODY_START
    components/
      portal/
        card.blade.php                  ← <x-portal.card>
        row.blade.php                   ← <x-portal.row>
        badge.blade.php                 ← <x-portal.badge>
        button.blade.php                ← <x-portal.button>
    pages/
      auth/
        login.blade.php                 ← Lanzadera de accesos (carga portal.css)
app/
  Providers/
    Filament/
      PortalPanelProvider.php           ← ->viteTheme('resources/css/portal.css') + RenderHook
```

---

## 1. Principio fundamental

El Portal **NO es un admin panel clásico**. Debe sentirse como:

- SaaS moderno y producto premium
- Interfaz fintech / dark glass 2026
- Glassmorphism oscuro elegante

**Nunca debe parecer:** Bootstrap admin · ERP tradicional · Dashboard gris con cajas rígidas

---

## 2. Paleta oficial (obligatoria)

| Token | Valor |
|---|---|
| Fondo base | `#05070A` |
| Glass fill | `bg-white/5` · `bg-white/7` |
| Glass border | `border-white/10` · `border-white/15` |
| Texto primario | `text-white/90` |
| Texto secundario | `text-white/60` |
| Texto muted | `text-white/40` |
| Acento rojo (primario) | `red-500` |
| Acento azul (info) | `blue-500` |
| Acento emerald (éxito) | `emerald-500` |
| Acento amber (advertencia) | `amber-500` |
| Acento violet (comunicación) | `violet-500` |

> ⚠️ El rojo NO se usa para bordes de secciones. Solo para CTAs y acentos de acción.

---

## 3. Fondo global (obligatorio en todas las páginas)

Todas las páginas (Dashboard, Listados, Detalle, Login) deben tener el fondo con radial gradients + grid sutil.

**Implementación:** el hook `portal.hooks.background` se inyecta automáticamente via `PortalPanelProvider::boot()`:

```php
FilamentView::registerRenderHook(
    PanelsRenderHook::BODY_START,
    fn (): string => view('portal.hooks.background')->render(),
);
```

Para páginas fuera de Filament (login lanzadera), incluir manualmente:

```html
<div class="pointer-events-none fixed inset-0 -z-10 portal-bg"></div>
<div class="pointer-events-none fixed inset-0 -z-10 portal-grid"></div>
```

---

## 4. Componentes obligatorios

### `<x-portal.card>` — Contenedor glass

```blade
<x-portal.card>              {{-- p-4 por defecto --}}
<x-portal.card padding="p-6">
```

**Regla:** TODO contenedor visible debe usar `.glass` o `<x-portal.card>`.  
**Prohibido:** `bg-gray-900`, `shadow-xl`, `border-red-500` como layout, cajas opacas.

---

### `<x-portal.badge>` — Pill de estado/tipo

```blade
<x-portal.badge color="blue">NÓMINA</x-portal.badge>
<x-portal.badge color="emerald">PAGADO</x-portal.badge>
<x-portal.badge color="amber">PENDIENTE</x-portal.badge>
<x-portal.badge color="red">URGENTE</x-portal.badge>
<x-portal.badge color="violet">CHAT</x-portal.badge>
<x-portal.badge color="zinc">ARCHIVADO</x-portal.badge>
```

---

### `<x-portal.button>` — CTA primario y ghost

```blade
<x-portal.button variant="primary">+ Nuevo</x-portal.button>
<x-portal.button variant="ghost" as="a" href="/portal/docs">Ver todo</x-portal.button>
```

---

### `<x-portal.row>` — Fila de lista estilo Portal Pro

```blade
<x-portal.row title="Nómina Enero 2024" subtitle="Hace 2 días" href="/portal/docs/1">
    <x-slot:icon>📄</x-slot:icon>
    <x-slot:right>
        <x-portal.badge color="blue">NÓMINA</x-portal.badge>
    </x-slot:right>
</x-portal.row>
```

Cada fila debe tener: glass · hover sutil · icono pill izquierda · badge pill derecha · sin divisores duros.

---

## 5. Clases de utilidad disponibles

```css
/* Fondo */
.portal-bg      /* radial gradients atmosféricos */
.portal-grid    /* grid sutil 42px */

/* Contenedores */
.glass          /* rounded-2xl border-white/10 bg-white/5 backdrop-blur-xl */
.glass-hover    /* transition hover:bg-white/7 hover:border-white/15 active:scale-[0.99] */

/* Pills */
.pill           /* base */
.pill-red / .pill-blue / .pill-emerald / .pill-amber / .pill-violet / .pill-zinc

/* Botones */
.btn-primary    /* rounded-full rojo con glow */
.btn-ghost      /* rounded-full glass sutil */

/* Inputs */
.input-glass    /* rounded-2xl border-white/10 bg-white/5 backdrop-blur */
```

---

## 6. Overrides Filament 5 (ya aplicados en portal.css)

| Selector | Efecto |
|---|---|
| `.fi-body`, `.fi-main`, `.fi-page`, `.fi-layout` | `background: transparent` |
| `.fi-topbar` | `bg-black/30 backdrop-blur-xl border-b border-white/10` |
| `.fi-sidebar` | `bg-black/30 backdrop-blur-xl border-r border-white/10` |
| `.fi-section`, `.fi-card`, `.fi-wi` | `.glass shadow-none` |
| `.fi-simple-layout .fi-simple-main` | `.glass` (login Filament) |
| `.fi-input`, `.fi-fo-text-input input` | `border-white/10 bg-white/5 backdrop-blur` |

---

## 7. Jerarquía de layout

```
page
└── space-y-6 / space-y-8
    ├── <x-portal.card>   (stats, resúmenes)
    │   └── grid grid-cols-2 md:grid-cols-4 gap-4
    └── div.space-y-3     (listas)
        └── <x-portal.row> × N
```

Separar por `space-y-6 / gap-4`. **NO** por cajas dentro de cajas, bordes gruesos ni líneas duras.

---

## 8. Mobile first

- Columna única en móvil → grid expande en desktop
- Cards grandes, botones pill táctiles
- Nada comprimido — espaciado generoso siempre

---

## 9. Reglas de hierro (el agente NO puede saltárselas)

| Elemento | Componente obligatorio |
|---|---|
| Cualquier bloque visual | `<x-portal.card>` o `.glass` |
| Cualquier lista | `<x-portal.row>` |
| Cualquier badge/estado | `<x-portal.badge>` |
| Cualquier CTA | `<x-portal.button>` |

**Prohibido usar:**
- `bg-gray-800` / `bg-gray-900`
- `shadow-xl` clásica
- `border-red-500` como layout
- `rounded-md` pequeños en contenedores
- Estilos inline ad-hoc sin usar clases del sistema
- Clases `fi-*` directamente en Blade (solo en CSS)

> Si una pantalla viola estas reglas → se considera incorrecta y debe rehacerse.

---

## 10. Login / Lanzadera

El login (`/login`) debe:
- Cargar `portal.css` vía Vite
- Incluir los 2 divs de fondo (`portal-bg` + `portal-grid`)
- Usar `.glass` para las cards
- Usar `.input-glass` para inputs
- Usar `.btn-primary` para el submit

---

## 11. Checklist antes de entregar cualquier pantalla

- [ ] Fondo `portal-bg` + `portal-grid` activo
- [ ] Todas las cards usan `.glass`
- [ ] No hay bordes duros ni sombras clásicas
- [ ] Espaciado generoso (`space-y-6`, `gap-4`)
- [ ] Mobile-first correcto (columna única por defecto)
- [ ] Login consistente con el dashboard
- [ ] No hay clases prohibidas

---

## 12. Referencia rápida de uso MCP

Instrucción estándar para el agente en cada sesión:

> Antes de generar cualquier UI del portal, consulta `docs/portal-ui-system.md` y cumple estrictamente sus reglas. Si el código viola alguna regla, rehacerlo antes de entregar.

