<!DOCTYPE html>
<html lang="en" class="bg-zinc-950 text-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Deployment Diagnostics' }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-zinc-950 text-zinc-100 antialiased">
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        {{ $slot }}
    </div>
</body>
</html>
