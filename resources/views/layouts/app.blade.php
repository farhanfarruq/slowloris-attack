<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name') }} | @yield('title', 'Dashboard')</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&family=jetbrains-mono:400,500&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-chart-matrix@2.0.1/dist/chartjs-chart-matrix.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/luxon@3"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@1.3"></script>
</head>
<body class="font-sans bg-gray-50 text-gray-900 antialiased min-h-screen">

<div class="flex min-h-screen">
    @include('partials.sidebar')

    <div class="flex-1 flex flex-col">
        @include('partials.topbar')

        <main class="flex-1 p-6 lg:p-8 overflow-x-hidden">
            @if (session('success'))
                <div class="mb-4 p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 p-4 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">
                    {{ session('error') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-4 p-4 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm space-y-1">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            @yield('content')

            <footer class="mt-12 pt-6 border-t border-gray-200 text-xs text-gray-500 text-center">
                Slowloris Lab - dashboard pribadi
            </footer>
        </main>
    </div>
</div>

</body>
</html>
