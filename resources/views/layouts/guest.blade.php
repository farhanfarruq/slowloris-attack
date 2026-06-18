<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Attack Analysis Lab') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans bg-gray-50 text-gray-900 antialiased">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="w-full max-w-md">
            <div class="text-center mb-6">
                <div class="inline-flex brand-logo-frame brand-logo-frame--login">
                    <img src="{{ asset('cyber-criminal.png') }}" alt="Network Attack Analysis Lab" class="brand-logo-image">
                </div>
                <h1 class="mt-3 text-xl font-semibold text-gray-900 tracking-tight">Attack Analysis Lab</h1>
                <p class="text-xs text-gray-500 uppercase tracking-widest mt-1">Personal Dashboard</p>
            </div>

            <div class="card p-6">
                {{ $slot }}
            </div>

            <p class="mt-4 text-center text-xs text-gray-500">Gunakan hanya pada lab lokal milik sendiri</p>
        </div>
    </div>
</body>
</html>
