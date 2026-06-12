<form method="post" action="{{ route('profile.update') }}" class="space-y-4">
    @csrf
    @method('patch')

    <div>
        <label class="label-field" for="name">Nama</label>
        <input id="name" name="name" type="text" required autofocus
               value="{{ old('name', $user->name) }}" class="input-field">
        @error('name')<p class="text-xs text-rose-300 mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="label-field" for="email">Email</label>
        <input id="email" name="email" type="email" required
               value="{{ old('email', $user->email) }}" class="input-field">
        @error('email')<p class="text-xs text-rose-300 mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="btn-primary">Simpan</button>
        @if (session('status') === 'profile-updated')
            <span class="text-xs text-emerald-300">Tersimpan.</span>
        @endif
    </div>
</form>
