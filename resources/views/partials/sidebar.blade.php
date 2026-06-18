@php
    $items = [
        ['route' => 'dashboard',            'label' => 'Dashboard',          'icon' => 'home'],
        ['route' => 'acquisition.index',    'label' => 'Upload Data',        'icon' => 'upload'],
        ['route' => 'validation.index',     'label' => 'Validasi Data',      'icon' => 'shield'],
        ['route' => 'analysis.index',       'label' => 'Analisis',           'icon' => 'flask'],
        ['route' => 'visualization.index',  'label' => 'Grafik',             'icon' => 'chart'],
        ['route' => 'ai.index',             'label' => 'AI Analysis',        'icon' => 'cpu'],
        ['route' => 'comparison.index',     'label' => 'Comparison',         'icon' => 'chart'],
        ['route' => 'experiments.index',    'label' => 'Dataset',            'icon' => 'database'],
        ['route' => 'evaluation.index',     'label' => 'Evaluasi',           'icon' => 'check'],
        ['route' => 'reports.index',        'label' => 'Laporan',            'icon' => 'file'],
        ['route' => 'lab.index',            'label' => 'Lab VM',             'icon' => 'lab'],
        ['route' => 'methodology.index',    'label' => 'Alur Sistem',        'icon' => 'book'],
    ];
@endphp

<aside class="w-64 hidden lg:flex flex-col bg-white border-r border-gray-200 sticky top-0 h-screen">
    <div class="px-6 pt-6 pb-4 border-b border-gray-200">
        <div class="flex items-center gap-3">
            <div class="brand-logo-frame brand-logo-frame--sidebar">
        <img src="{{ asset('cyber-criminal.png') }}" alt="Network Attack Analysis Lab" class="brand-logo-image">
            </div>
            <div>
            <p class="text-sm font-semibold text-gray-900 leading-tight">Attack Analysis Lab</p>
                <p class="text-[11px] text-gray-500 uppercase tracking-wider">Personal Dashboard</p>
            </div>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
        @foreach ($items as $item)
            @php $active = request()->routeIs($item['route']); @endphp
            <a href="{{ route($item['route']) }}"
               class="group flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition border
                      {{ $active
                          ? 'bg-blue-50 text-blue-700 border-blue-200'
                          : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 border-transparent' }}">
                <x-icon :name="$item['icon']" class="w-4 h-4" />
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach

        @auth
            @if (auth()->user()->isAdmin())
                <div class="mt-4 px-3 text-[10px] uppercase tracking-widest text-gray-400">Pengaturan</div>
                <a href="{{ route('settings.api') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition border
                          {{ request()->routeIs('settings.api')
                              ? 'bg-blue-50 text-blue-700 border-blue-200'
                              : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 border-transparent' }}">
                    <x-icon name="key" class="w-4 h-4" />
                    Pengaturan API
                </a>
                <a href="{{ route('audit.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition border
                          {{ request()->routeIs('audit.index')
                              ? 'bg-blue-50 text-blue-700 border-blue-200'
                              : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 border-transparent' }}">
                    <x-icon name="eye" class="w-4 h-4" />
                    Audit Log
                </a>
            @endif
        @endauth
    </nav>

    <div class="px-4 py-4 border-t border-gray-200 text-xs text-gray-500">
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            Local lab - v1.0
        </div>
    </div>
</aside>
