@extends('layouts.app')

@section('title', 'Pengaturan API')
@section('subtitle', 'Tambah provider AI sendiri. Provider tanpa key tidak muncul di Validasi AI.')

@section('content')

<div class="card mb-4">
    <div class="card-header"><p class="card-title">Tambah Provider AI</p></div>
    <form method="POST" action="{{ route('settings.api.store') }}" class="p-5 grid grid-cols-1 lg:grid-cols-2 gap-4">
        @csrf
        <div>
            <label class="label-field">Nama Provider *</label>
            <input class="input-field" name="provider_label" value="{{ old('provider_label') }}" placeholder="Groq Llama / OpenRouter / Together AI" required>
            @error('provider_label')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="label-field">Provider Key</label>
            <input class="input-field" name="provider_key" value="{{ old('provider_key') }}" placeholder="otomatis dari nama jika kosong">
            <p class="mt-1 text-[11px] text-gray-500">Huruf kecil, angka, strip, atau underscore. Contoh: `groq_llama`.</p>
            @error('provider_key')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="label-field">Driver *</label>
            <select name="driver" class="input-field" required>
                <option value="openai_compatible" @selected(old('driver') === 'openai_compatible')>OpenAI-compatible</option>
                <option value="gemini" @selected(old('driver') === 'gemini')>Google Gemini</option>
                <option value="ollama" @selected(old('driver') === 'ollama')>Ollama lokal</option>
            </select>
            @error('driver')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="label-field">Model *</label>
            <input class="input-field" name="model" value="{{ old('model') }}" placeholder="llama-3.3-70b-versatile / gpt-4o-mini / gemini-1.5-flash">
            @error('model')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
        </div>

        <div class="lg:col-span-2">
            <label class="label-field">Endpoint API *</label>
            <input class="input-field" name="api_url" value="{{ old('api_url') }}" placeholder="https://api.groq.com/openai/v1">
            <p class="mt-1 text-[11px] text-gray-500">
                OpenAI-compatible boleh base URL atau full `/chat/completions`. Gemini boleh base `/v1beta/models` atau full `:generateContent`.
            </p>
            @error('api_url')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
        </div>

        <div class="lg:col-span-2">
            <label class="label-field">API Key</label>
            <input class="input-field" type="password" name="api_key" autocomplete="new-password" placeholder="Isi manual. Kosong = tidak muncul di Validasi AI.">
            @error('api_key')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
        </div>

        <label class="lg:col-span-2 flex items-center justify-between gap-4 rounded-lg border border-gray-200 bg-gray-50 px-3 py-3">
            <span>
                <span class="block text-sm font-medium text-gray-900">Gunakan live API</span>
                <span class="block text-xs text-gray-500">Provider hanya muncul di Validasi AI jika live API aktif dan API key tersedia.</span>
            </span>
            <input type="checkbox" name="use_live_api" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" @checked(old('use_live_api', true))>
        </label>

        <div class="lg:col-span-2 flex justify-end">
            <button class="btn-primary" type="submit">Simpan Provider</button>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header"><p class="card-title">Provider Tersimpan</p></div>
    <div class="p-5 grid grid-cols-1 xl:grid-cols-2 gap-4">
        @forelse ($providers as $p)
            <div class="rounded-lg border border-gray-200 bg-white">
                <form method="POST" action="{{ route('settings.api.store') }}" class="p-4 space-y-4">
                    @csrf
                    <input type="hidden" name="provider_key" value="{{ $p['key'] }}">

                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-gray-950">{{ $p['label'] }}</p>
                            <p class="text-xs text-gray-500">{{ $p['key'] }} · {{ $p['driver'] }}</p>
                        </div>
                        @if ($p['can_run'])
                            <span class="badge-emerald">Muncul di Validasi AI</span>
                        @elseif ($p['has_key'])
                            <span class="badge-amber">Live API off</span>
                        @else
                            <span class="badge-slate">Key kosong</span>
                        @endif
                    </div>

                    <div>
                        <label class="label-field">Nama Provider</label>
                        <input class="input-field" name="provider_label" value="{{ old('provider_label', $p['label']) }}" required>
                    </div>

                    <div>
                        <label class="label-field">Driver</label>
                        <select name="driver" class="input-field" required>
                            <option value="openai_compatible" @selected($p['driver'] === 'openai_compatible')>OpenAI-compatible</option>
                            <option value="gemini" @selected($p['driver'] === 'gemini')>Google Gemini</option>
                            <option value="ollama" @selected($p['driver'] === 'ollama')>Ollama lokal</option>
                        </select>
                    </div>

                    <div>
                        <label class="label-field">Endpoint API</label>
                        <input class="input-field" name="api_url" value="{{ old('api_url', $p['api_url']) }}">
                    </div>

                    <div>
                        <label class="label-field">Model</label>
                        <input class="input-field" name="model" value="{{ old('model', $p['model']) }}">
                    </div>

                    <div>
                        <label class="label-field">API Key</label>
                        <input class="input-field" type="password" name="api_key" autocomplete="new-password" placeholder="Isi hanya saat ingin menambah atau mengganti key">
                        @if ($p['has_key'])
                            <label class="mt-2 flex items-center gap-2 text-xs text-gray-500">
                                <input type="checkbox" name="clear_api_key" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                Hapus key tersimpan
                            </label>
                        @endif
                    </div>

                    <label class="flex items-center justify-between gap-4 rounded-lg border border-gray-200 bg-gray-50 px-3 py-3">
                        <span>
                            <span class="block text-sm font-medium text-gray-900">Gunakan live API</span>
                            <span class="block text-xs text-gray-500">Matikan jika provider belum mau dipakai.</span>
                        </span>
                        <input type="checkbox" name="use_live_api" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" @checked($p['use_live_api'])>
                    </label>

                    <div class="flex justify-end">
                        <button class="btn-primary" type="submit">Simpan</button>
                    </div>
                </form>

                <form method="POST" action="{{ route('settings.api.destroy', $p['key']) }}" class="px-4 pb-4" onsubmit="return confirm('Hapus provider ini?')">
                    @csrf
                    @method('DELETE')
                    <button class="text-xs font-medium text-red-700" type="submit">Hapus provider</button>
                </form>
            </div>
        @empty
            <div class="xl:col-span-2 rounded-lg border border-gray-200 bg-gray-50 p-5 text-sm text-gray-700">
                Belum ada provider tersimpan. Tambahkan provider dan isi API key agar muncul di halaman Validasi AI.
            </div>
        @endforelse
    </div>
</div>

@endsection
