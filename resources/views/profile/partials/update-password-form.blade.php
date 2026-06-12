<form method="post" action="{{ route('password.update') }}" class="space-y-4">
    @csrf
    @method('put')

    <div>
        <label class="label-field" for="update_password_current_password">Password Saat Ini</label>
        <input id="update_password_current_password" name="current_password" type="password"
               class="input-field" autocomplete="current-password">
        @error('current_password', 'updatePassword')<p class="text-xs text-rose-300 mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="label-field" for="update_password_password">Password Baru</label>
        <input id="update_password_password" name="password" type="password"
               class="input-field" autocomplete="new-password">
        @error('password', 'updatePassword')<p class="text-xs text-rose-300 mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="label-field" for="update_password_password_confirmation">Konfirmasi Password</label>
        <input id="update_password_password_confirmation" name="password_confirmation" type="password"
               class="input-field" autocomplete="new-password">
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="btn-primary">Simpan Password</button>
        @if (session('status') === 'password-updated')
            <span class="text-xs text-emerald-300">Password diperbarui.</span>
        @endif
    </div>
</form>
