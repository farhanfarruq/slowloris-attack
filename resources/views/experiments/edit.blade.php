@extends('layouts.app')

@section('title', 'Edit Eksperimen ' . $experiment->experiment_code)

@section('content')
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
                @foreach (['unknown','normal','slowloris_lab','mixed'] as $t)
                    <option value="{{ $t }}" @selected($experiment->traffic_type===$t)>{{ str_replace('_',' ',$t) }}</option>
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
            <label class="label-field">Ground Truth Label</label>
            <select name="ground_truth_label" class="input-field">
                <option value="">—</option>
                @foreach (['normal','slowloris_lab','mixed','unknown'] as $t)
                    <option value="{{ $t }}" @selected($experiment->ground_truth_label===$t)>{{ str_replace('_',' ',$t) }}</option>
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
