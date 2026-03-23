@props(['variant'])

<div class="mb-6 rounded-lg border border-zinc-700 bg-zinc-800/50 px-4 py-3">
    <div class="flex items-center gap-3">
        <span class="rounded bg-indigo-500/10 px-2 py-0.5 font-mono text-sm font-semibold text-indigo-400 ring-1 ring-inset ring-indigo-500/20">
            {{ $variant['variant'] ?? 'unknown' }}
        </span>
        <span class="text-sm text-zinc-400">
            {{ $variant['description'] ?? '' }}
        </span>
    </div>
</div>
