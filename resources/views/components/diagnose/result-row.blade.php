@props(['result'])

<div class="flex items-center justify-between gap-4 px-4 py-2.5 text-sm">
    <div class="flex items-center gap-3 min-w-0">
        <x-diagnose.status-badge :status="$result->status" />
        <span class="font-medium text-zinc-300 shrink-0">{{ $result->label }}</span>
    </div>
    <div class="flex items-center gap-3 min-w-0">
        <span class="truncate font-mono text-xs text-zinc-500">{{ $result->detail }}</span>
        @if ($result->latencyMs !== null)
            <span class="shrink-0 rounded bg-zinc-800 px-1.5 py-0.5 font-mono text-xs text-zinc-500">
                {{ number_format($result->latencyMs, 1) }}ms
            </span>
        @endif
    </div>
</div>
