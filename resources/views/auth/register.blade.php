<x-guest-layout>
    <h2 class="text-base font-semibold text-white mb-1">Daftar Akun</h2>
    <p class="text-xs text-slate-400 mb-5">Pilih peran akun.</p>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <div>
            <label class="label-field">Nama</label>
            <input name="name" type="text" required value="{{ old('name') }}" class="input-field">
            @error('name')<p class="text-xs text-rose-300 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label-field">Email</label>
            <input name="email" type="email" required value="{{ old('email') }}" class="input-field">
            @error('email')<p class="text-xs text-rose-300 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label-field">Peran</label>
            <select name="role" class="input-field">
                <option value="viewer" @selected(old('role')==='viewer')>Viewer</option>
                <option value="admin" @selected(old('role')==='admin')>Peneliti / Admin</option>
            </select>
        </div>
        <div>
            <label class="label-field">Password</label>
            <input name="password" type="password" required class="input-field">
            @error('password')<p class="text-xs text-rose-300 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label-field">Konfirmasi Password</label>
            <input name="password_confirmation" type="password" required class="input-field">
        </div>
        <button class="btn-primary w-full justify-center">Daftar</button>
        <p class="text-xs text-center text-slate-500">Sudah punya akun?
            <a href="{{ route('login') }}" class="text-cyan-300">Masuk</a>
        </p>
    </form>
</x-guest-layout>
