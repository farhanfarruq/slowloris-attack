@extends('layouts.app')

@section('title', 'Edit Eksperimen ' . $experiment->experiment_code)

@section('content')
@php
    $trafficLabels = [
        'unknown' => 'unknown',
        'normal' => 'normal',
        'slowloris_lab' => 'slowloris lab (legacy)',
        'mixed' => 'mixed',
    ];

    foreach ($toolProfiles as $profile) {
        $trafficLabels[$profile['key']] = $profile['label'];
    }
@endphp
<div class="card max-w-3xl">
    <div class="card-header"><p class="card-title">Edit Metadata</p></div>
    <form action="{{ route('experiments.update', $experiment) }}" method="POST" class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
        @csrf @method('PUT')
        <div class="md:col-span-2">
            <label class="label-field">Nama Eksperimen *</label>
            <input name="name" type="text" required class="input-field" value="{{ old('name', $experiment->name) }}">
        </div>
        <div>
            <label class="label-field">Tanggal *</label>
            <input name="experiment_date" type="date" required class="input-field"
                   value="{{ old('experiment_date', $experiment->experiment_date?->toDateString()) }}">
        </div>
        <div>
            <label class="label-field">Tipe Traffic *</label>
            <select name="traffic_type" class="input-field" required>
                @foreach ($trafficLabels as $key => $label)
                    <option value="{{ $key }}" @selected(old('traffic_type', $experiment->traffic_type) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label-field">Tool Profile *</label>
            <select name="tool_profile" class="input-field" required>
                @foreach ($toolProfiles as $profile)
                    <option value="{{ $profile['key'] }}" @selected(old('tool_profile', $experiment->tool_profile ?: 'slowloris') === $profile['key'])>
                        {{ $profile['label'] }} @if($profile['owner']) - {{ $profile['owner'] }} @endif
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label-field">Kode Skenario *</label>
            <input name="scenario_key" type="text" required class="input-field"
                   value="{{ old('scenario_key', $experiment->scenario_key ?: 'manual') }}"
                   placeholder="slow-http">
            <p class="text-[11px] text-slate-500 mt-1">Identitas skenario untuk memasangkan PCAP dan log validasi.</p>
        </div>
        <div>
            <label class="label-field">Attack Pattern</label>
            <input name="attack_pattern" type="text" class="input-field"
                   value="{{ old('attack_pattern', $experiment->attack_pattern) }}"
                   placeholder="slow_http / http_flood / tcp_syn_flood">
            <p class="text-[11px] text-slate-500 mt-1">Metadata teknis, bukan identitas utama penelitian.</p>
        </div>
        <div>
            <label class="label-field">Analysis Profile Key</label>
            <input name="analysis_profile_key" type="text" class="input-field"
                   value="{{ old('analysis_profile_key', $experiment->analysis_profile_key) }}"
                   placeholder="otomatis mengikuti tool_profile">
        </div>
        <div>
            <label class="label-field">Target Platform</label>
            <input name="target_platform" type="text" class="input-field"
                   value="{{ old('target_platform', $experiment->target_platform ?: 'vm_ubuntu_server') }}">
        </div>
        <div>
            <label class="label-field">Ground Truth Label</label>
            <select name="ground_truth_label" class="input-field">
                <option value="">—</option>
                @foreach ($trafficLabels as $key => $label)
                    <option value="{{ $key }}" @selected(old('ground_truth_label', $experiment->ground_truth_label) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label-field">Network Interface</label>
            <input name="network_interface" type="text" class="input-field" value="{{ $experiment->network_interface }}">
        </div>
        <div>
            <label class="label-field">IP Target Lab</label>
            <input name="target_ip" type="text" class="input-field" value="{{ $experiment->target_ip }}">
        </div>
        <div>
            <label class="label-field">IP Sumber Traffic</label>
            <input name="source_ip" type="text" class="input-field" value="{{ $experiment->source_ip }}">
        </div>
        <div>
            <label class="label-field">Durasi Capture (detik)</label>
            <input name="capture_duration" type="number" min="1" max="86400" class="input-field" value="{{ $experiment->capture_duration }}">
        </div>
        <div class="md:col-span-2">
            <label class="label-field">Catatan</label>
            <textarea name="notes" rows="4" class="input-field">{{ $experiment->notes }}</textarea>
        </div>
        <div class="md:col-span-2 flex justify-end gap-3 pt-2">
            <a href="{{ route('experiments.show', $experiment) }}" class="btn-ghost">Kembali</a>
            <button class="btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</div>
@endsection
