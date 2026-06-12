@extends('layouts.app')

@section('title', 'Eksperimen Baru')
@section('subtitle', 'Daftarkan eksperimen lab terlebih dahulu sebelum mengunggah file akuisisi.')

@section('content')

<div class="card max-w-3xl">
    <div class="card-header"><p class="card-title">Metadata Eksperimen</p></div>
    <form action="{{ route('experiments.store') }}" method="POST" class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
        @csrf
        <div class="md:col-span-2">
            <label class="label-field">Nama Eksperimen *</label>
            <input name="name" type="text" required class="input-field" placeholder="Slowloris Lab Test Ubuntu Local">
        </div>
        <div>
            <label class="label-field">Tanggal *</label>
            <input name="experiment_date" type="date" required value="{{ now()->toDateString() }}" class="input-field">
        </div>
        <div>
            <label class="label-field">Tipe Traffic *</label>
            <select name="traffic_type" class="input-field" required>
                <option value="unknown">Unknown</option>
                <option value="normal">Normal</option>
                <option value="slowloris_lab">Slowloris Lab</option>
                <option value="mixed">Mixed</option>
            </select>
        </div>
        <div>
            <label class="label-field">Kode Skenario *</label>
            <input name="scenario_key" type="text" required class="input-field" list="scenario-options"
                   placeholder="slow-http">
            <datalist id="scenario-options">
                <option value="normal-baseline">
                <option value="http-burst">
                <option value="slow-http">
                <option value="portscan">
                <option value="iperf-bandwidth">
                <option value="manual">
            </datalist>
            <p class="text-[11px] text-slate-500 mt-1">Gunakan huruf kecil, angka, dash, atau underscore. Contoh: slow-http.</p>
        </div>
        <div>
            <label class="label-field">Ground Truth Label</label>
            <select name="ground_truth_label" class="input-field">
                <option value="">— belum diketahui —</option>
                <option value="normal">Normal</option>
                <option value="slowloris_lab">Slowloris Lab</option>
                <option value="mixed">Mixed</option>
                <option value="unknown">Unknown</option>
            </select>
        </div>
        <div>
            <label class="label-field">Network Interface</label>
            <input name="network_interface" type="text" class="input-field" placeholder="enp0s3">
        </div>
        <div>
            <label class="label-field">IP Target Lab</label>
            <input name="target_ip" type="text" class="input-field" placeholder="192.168.56.10">
        </div>
        <div>
            <label class="label-field">IP Sumber Traffic</label>
            <input name="source_ip" type="text" class="input-field" placeholder="192.168.56.5">
        </div>
        <div>
            <label class="label-field">Durasi Capture (detik)</label>
            <input name="capture_duration" type="number" min="1" max="86400" class="input-field" placeholder="600">
        </div>
        <div class="md:col-span-2">
            <label class="label-field">Catatan Eksperimen</label>
            <textarea name="notes" rows="4" class="input-field" placeholder="Tujuan, kondisi lab, mode Snort, dll."></textarea>
        </div>
        <div class="md:col-span-2 flex justify-end gap-3 pt-2">
            <a href="{{ route('experiments.index') }}" class="btn-ghost">Batal</a>
            <button type="submit" class="btn-primary">Simpan Eksperimen</button>
        </div>
    </form>
</div>

@endsection
