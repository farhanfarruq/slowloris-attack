<x-guest-layout>
    <h2 class="text-base font-semibold text-white mb-1">Masuk ke Dashboard</h2>
    <p class="text-xs text-slate-400 mb-5">Gunakan akun admin atau viewer.</p>

    @if (session('status'))
        <div class="mb-3 text-xs text-emerald-300">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <label class="label-field" for="email">Email</label>
            <input id="email" name="email" type="email" required autofocus
                   value="{{ old('email') }}" class="input-field" placeholder="peneliti@lab.test">
            @error('email')<p class="text-xs text-rose-300 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label-field" for="password">Password</label>
            <input id="password" name="password" type="password" required class="input-field" placeholder="••••••••">
            @error('password')<p class="text-xs text-rose-300 mt-1">{{ $message }}</p>@enderror
        </div>
        <label class="flex items-center gap-2 text-xs text-slate-400">
            <input type="checkbox" name="remember" class="rounded border-slate-700 bg-slate-900 text-cyan-500"> Ingat saya
        </label>
        <button type="submit" class="btn-primary w-full justify-center">Masuk</button>

        <div class="text-xs text-slate-500 text-center pt-2 border-t border-slate-800">
            <p class="mb-1">Akun demo:</p>
            <p>Peneliti: <span class="text-cyan-300">peneliti@lab.test</span> / <span class="text-cyan-300">password</span></p>
            <p>Viewer: <span class="text-cyan-300">viewer@lab.test</span> / <span class="text-cyan-300">password</span></p>
        </div>
    </form>
</x-guest-layout>
