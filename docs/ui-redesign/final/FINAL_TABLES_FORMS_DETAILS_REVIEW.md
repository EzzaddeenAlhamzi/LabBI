# Final Tables / Forms / Details Review

All listing pages use .table-wrap > table.data with .pager/.pagination, .empty states, status-badge tones, .row-actions. Forms use .form-grid/.field/.input/.form-actions with preserved names/actions/CSRF/method-override (verified in diffs — no form field, endpoint, or validation changes). Details pages use card + dl label/value + badges. Filter bars use responsive grids; no columns or actions removed (diff review of all view files vs baseline confirms additive classes/markup only).
