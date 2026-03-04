<x-diagnose.layout title="Deployment Diagnostics">
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-white">Deployment Diagnostics</h1>
                <p class="mt-1 text-sm text-zinc-500">
                    Stack health check &mdash; generated {{ now()->format('Y-m-d H:i:s T') }}
                </p>
            </div>
            <div>
                @php
                    $badgeClasses = match ($summary->value) {
                        'ok' => 'bg-emerald-500/10 text-emerald-400 ring-emerald-500/20',
                        'warning' => 'bg-amber-500/10 text-amber-400 ring-amber-500/20',
                        'error' => 'bg-red-500/10 text-red-400 ring-red-500/20',
                    };
                @endphp
                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold ring-1 ring-inset {{ $badgeClasses }}">
                    {{ $summary->icon() }} {{ $summary->label() }}
                </span>
            </div>
        </div>
    </div>

    <div class="grid gap-6">
        @foreach ($results as $name => $checks)
            <x-diagnose.section :title="$name" :results="$checks" />
        @endforeach
    </div>

    <div class="mt-8 text-center text-xs text-zinc-600">
        <p>Laravel {{ app()->version() }} &middot; PHP {{ PHP_VERSION }} &middot; {{ php_uname('s') }}</p>
    </div>
</x-diagnose.layout>
