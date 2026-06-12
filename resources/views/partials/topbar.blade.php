<header class="sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-gray-200">
    <div class="px-6 lg:px-8 py-4 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3 min-w-0">
            <div class="brand-logo-frame brand-logo-frame--topbar lg:hidden">
                <img src="{{ asset('cyber-criminal.png') }}" alt="Slowloris Lab" class="brand-logo-image">
            </div>
            <div class="min-w-0">
                <h1 class="text-lg lg:text-xl font-semibold text-gray-900 truncate">@yield('title', 'Dashboard Slowloris')</h1>
                <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">@yield('subtitle', 'Monitoring traffic lab lokal dan validasi pola Slow HTTP.')</p>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <div class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-md bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                Lab Lokal
            </div>

            @auth
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" type="button"
                            class="flex items-center gap-2 px-3 py-1.5 rounded-md bg-white hover:bg-gray-50 border border-gray-200 text-sm">
                        <div class="w-7 h-7 rounded-full bg-blue-600 flex items-center justify-center font-semibold text-white text-xs">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div class="hidden md:block text-left">
                            <p class="text-gray-900 leading-tight">{{ auth()->user()->name }}</p>
                            <p class="text-[10px] uppercase tracking-wider text-gray-500">{{ auth()->user()->isAdmin() ? 'Admin' : 'Viewer' }}</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 14l-5-5h10l-5 5z"/></svg>
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition x-cloak
                         class="absolute right-0 mt-2 w-48 rounded-lg border border-gray-200 bg-white shadow-lg py-1 text-sm z-40">
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-50">Profil</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-red-700 hover:bg-red-50">Keluar</button>
                        </form>
                    </div>
                </div>
            @endauth
        </div>
    </div>
</header>
