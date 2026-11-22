@props(['status' => null, 'tone' => null, 'label' => null])
@php
$map = [
    // order / workflow statuses
    'requested' => ['neutral', 'info'],
    'sample_received' => ['info', 'check-circle'],
    'in_analysis' => ['info', 'activity'],
    'pending_review' => ['warning', 'clock'],
    'ready' => ['teal', 'check-circle'],
    'completed' => ['success', 'check-circle'],
    'archived' => ['neutral', 'archive'],
    'deferred' => ['warning', 'clock'],
    'rejected' => ['danger', 'x'],
    // sample statuses
    'awaiting' => ['warning', 'clock'],
    'received' => ['success', 'check-circle'],
    // result statuses
    'draft' => ['neutral', null],
    'submitted' => ['info', null],
    'approved' => ['success', 'check'],
    'returned' => ['warning', 'refresh'],
    // critical alert statuses
    'open' => ['critical', 'alert-octagon'],
    'acknowledged' => ['warning', 'check'],
    'closed' => ['neutral', 'check-circle'],
    // generic
    'active' => ['success', null],
    'inactive' => ['neutral', null],
    'enabled' => ['success', null],
    'disabled' => ['neutral', null],
    'success' => ['success', 'check-circle'],
    'failed' => ['danger', 'alert'],
    'pending' => ['warning', 'clock'],
    'sent' => ['success', 'check'],
    'scheduled' => ['info', 'calendar'],
];
$entry = $status ? ($map[$status] ?? null) : null;
$tone = $tone ?: ($entry[0] ?? 'neutral');
$iconName = $entry[1] ?? null;
$badgeClass = 'badge badge-' . $tone;
if ($tone === 'critical') { $badgeClass = 'badge badge-critical'; }
if ($tone === 'neutral') { $badgeClass = 'badge badge-neutral'; }
if ($tone === 'teal') { $badgeClass = 'badge badge-teal'; }
if ($tone === 'info') { $badgeClass = 'badge badge-info'; }
if ($tone === 'warning') { $badgeClass = 'badge badge-warning'; }
if ($tone === 'danger') { $badgeClass = 'badge badge-danger'; }
if ($tone === 'success') { $badgeClass = 'badge badge-success'; }
if ($tone === 'purple') { $badgeClass = 'badge badge-purple'; }
@endphp
<span {{ $attributes->merge(['class' => $badgeClass]) }}>
    @if($iconName)<x-icon :name="$iconName" />@endif
    {{ $label ?? ($status ? \Illuminate\Support\Str::headline($status) : '') }}
</span>
