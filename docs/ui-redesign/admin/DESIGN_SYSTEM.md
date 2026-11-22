# LabBI Design System

Centralized design system for the Admin interface. Implementation: `public/css/admin.css` (single file, no build step required), icons via `resources/views/components/icon.blade.php`, status badges via `resources/views/components/status-badge.blade.php`.

## Color tokens

| Token | Value | Usage |
|-------|-------|-------|
| `--brand-navy` | `#142B45` | Sidebar, headings, primary text accents |
| `--brand-navy-3` | `#0F2136` | Sidebar gradient end |
| `--teal` / `--teal-hover` | `#0D9488` / `#0F766E` | Primary actions, active nav, focus rings |
| `--teal-light` | `#CCFBF1` | Icon chip backgrounds |
| `--blue` `--purple` `--cyan` | `#2563EB` `#7C3AED` `#0EA5E9` | Analytics accents |
| `--bg` / `--surface` / `--surface-2` | `#F5F7FB` / `#FFF` / `#F8FAFC` | App background / cards / table headers |
| `--ink` / `--ink-2` / `--muted` / `--faint` | `#172B4D` `#475569` `#64748B` `#94A3B8` | Text hierarchy |
| `--line` / `--line-strong` | `#E2E8F0` / `#CBD5E1` | Borders |
| `--success` `--warning` `--danger` `--info` | `#16A34A` `#D97706` `#DC2626` `#0284C7` | Semantic statuses (each with `-bg` tint) |

Chart palette: `#0D9488 #2563EB #7C3AED #0EA5E9 #D97706 #DC2626 #16A34A #64748B` — assigned deterministically by `admin-charts.js`.

## Typography

- UI font: **IBM Plex Sans Arabic** (kept from existing app; covers Arabic + Latin)
- Display/headings: **Cairo** (700/800)
- Mono: system mono stack for codes/IDs
- Scale: page title 1.6rem/700, card title 1rem/600, KPI value 1.7rem/700, body .875–.9rem, labels .83rem/600, helper .75–.8rem

## Spacing / shape / elevation

- 8px-based spacing; content padding 1.5rem (24px); card padding 1.25rem (20px); KPI gap 1rem
- Radii: inputs/buttons 8px, cards 12px, modals-scale 16px, badges pill
- Shadows: restrained (`--shadow-sm`, `--shadow`, `--shadow-lg`); 1px neutral borders instead of heavy shadows
- Sidebar 264px expanded / 80px collapsed (desktop); sticky 68px topbar

## Components (CSS classes)

- **Layout**: `.app .sidebar .brand .nav .nav-group .topbar .content .page-head .page-actions`
- **Cards**: `.card` (+`.flush` for edge-to-edge tables), `.card-title`, `.card-sub`
- **KPI**: `.kpi-grid .kpi .kpi-icon .kpi-value .kpi-label` + `.tone-*` icon chips
- **Buttons**: `.btn` + `.btn-secondary .btn-ghost .btn-danger .btn-outline-danger .btn-sm .btn-lg .btn-block`
- **Badges**: `.badge` + `.badge-success/-warning/-danger/-info/-critical/-neutral/-teal/-purple` via `<x-status-badge>`
- **Tables**: `.card.flush > .table-wrap > table.data` (sticky-friendly, hover rows, `.num` tabular numerals, `.muted-cell`)
- **Forms**: `.form-grid` (2-col), `.field` (+`.has-error`), `.input`, `.check-line`, `.form-section`, `.form-actions`
- **Feedback**: `.alert` variants, `.empty` states, `.dl` label/value grids, `pre.json`
- **Pagination**: `admin/partials/pagination.blade.php` — self-contained styled view passed to `->links('admin.partials.pagination')`
- **Dropdowns**: `.dropdown .dropdown-menu` (topbar user menu)

## Icons

`<x-icon name="…" />` — inline SVG, Lucide-style 24×24 stroke icons, `currentColor`, consistent 2px stroke. ~55 icons covering all modules/actions (dashboard, patients, doctor, flask/test-tubes, clipboard, chart, alert, scroll, database, settings, bell, search, plus, pencil, eye, trash, download, upload, print, refresh, check, x, chevrons, calendar, clock, filter, mail, phone, logout, arrows, info, archive, ban, play/pause, paperclip, home, heart-pulse, timer, layers, menu, external, send, save, key, lock, shield, users, building, truck, activity, file-text, ruler, microscope).

Icon-only buttons always carry `aria-label`.

## Status badge mapping

`<x-status-badge :status="$model->status" :label="Model::statusLabel($status)" />` maps known codes to semantic tones (requested→neutral, in_analysis→info, pending_review/deferred→warning, ready→teal, completed/approved/received→success, rejected/failed→danger, open→critical, acknowledged→warning, closed→neutral, active→success, inactive→neutral). Unknown statuses fall back to neutral. Labels are always text — never color-only.

## Charts

`public/js/admin-charts.js` — dependency-free SVG renderer: `area` (line+area+grid+tooltip), `donut` (center total + legend with counts/%), `bars` (horizontal), `sparkline`. Data injected via `@json` from controllers — no chart CDN, no build step, RTL-safe.

## Responsive

Breakpoints: 1280px (grids→2col), 1024px (sidebar→off-canvas drawer + overlay, collapse prefs reset), 768px (single column, KPI 2col), 480px (KPI 1col). Tables scroll horizontally inside `.table-wrap`. `prefers-reduced-motion` respected.
