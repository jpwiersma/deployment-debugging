@props(['status'])

@php
    $classes = match ($status->value) {
        'ok' => 'bg-emerald-500/10 text-emerald-400 ring-emerald-500/20',
        'warning' => 'bg-amber-500/10 text-amber-400 ring-amber-500/20',
        'error' => 'bg-red-500/10 text-red-400 ring-red-500/20',
    };
@endphp

<span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $classes }}">
    {{ $status->icon() }} {{ $status->label() }}
</span>
