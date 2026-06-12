<div x-data="{ open: false }">
    <p class="text-sm text-slate-300 mb-3">Setelah akun dihapus, semua data akan hilang permanen. Pastikan unduh laporan penting terlebih dahulu.</p>
    <button @click="open = true" type="button" class="btn-danger">Hapus Akun</button>

    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70">
        <div class="card max-w-md w-full p-5">
            <h3 class="text-base font-semibold text-rose-300 mb-2">Konfirmasi Hapus Akun</h3>
            <p class="text-sm text-slate-300 mb-4">Masukkan password untuk konfirmasi.</p>
            <form method="post" action="{{ route('profile.destroy') }}" class="space-y-3">
                @csrf
                @method('delete')
                <input type="password" name="password" placeholder="Password" class="input-field"
                       @if($errors->has('password', 'userDeletion')) autofocus @endif>
                @error('password', 'userDeletion')<p class="text-xs text-rose-300">{{ $message }}</p>@enderror
                <div class="flex justify-end gap-2">
                    <button type="button" @click="open = false" class="btn-ghost">Batal</button>
                    <button type="submit" class="btn-danger">Hapus Akun</button>
                </div>
            </form>
        </div>
    </div>
</div>
