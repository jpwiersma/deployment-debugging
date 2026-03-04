@props(['title', 'results'])

<div class="rounded-lg border border-zinc-800 bg-zinc-900/50">
    <div class="border-b border-zinc-800 px-4 py-3">
        <h2 class="text-sm font-semibold tracking-wide text-zinc-300 uppercase">{{ $title }}</h2>
    </div>
    <div class="divide-y divide-zinc-800/50">
        @foreach ($results as $result)
            <x-diagnose.result-row :result="$result" />
        @endforeach
    </div>
</div>
