@extends('layouts.app')

@section('title', 'Generate Laporan · ' . $experiment->experiment_code)

@section('content')

<div class="card max-w-4xl">
    <div class="card-header">
        <p class="card-title">Form Laporan</p>
        <span class="badge-cyan">{{ $experiment->experiment_code }}</span>
    </div>
    <form action="{{ route('reports.store', $experiment) }}" method="POST" class="p-5 space-y-4">
        @csrf
        <div class="rounded-lg border border-amber-500/30 bg-amber-500/10 p-3 text-xs text-amber-200 leading-relaxed">
            Catatan: <strong>Final Decision</strong> di laporan ini akan diambil dari evidence-gated kategori
            ({{ $experiment->extractedFeature->attack_category ?? '—' }}), <em>bukan</em> dari voting AI.
            Voting AI tetap dicatat sebagai data pendukung. Disclaimer lab lokal otomatis ditambahkan ke "Catatan Batasan".
        </div>
        <div>
            <label class="label-field">Judul Eksperimen</label>
            <input name="title" required class="input-field" value="{{ old('title', 'Analisis Slowloris pada Lab ' . $experiment->name) }}">
        </div>
        <div>
            <label class="label-field">Tujuan Eksperimen</label>
            <textarea name="purpose" rows="3" class="input-field">Mendeteksi dan memvalidasi pola serangan Slowloris/Slow HTTP DoS pada lingkungan lab terisolasi menggunakan kombinasi Wireshark, Snort, baseline iPerf3, dan validasi multi-model AI.</textarea>
        </div>
        <div>
            <label class="label-field">Topologi Pengujian</label>
            <textarea name="topology" rows="3" class="input-field">Ubuntu controller → VM Target Nginx → Wireshark/dumpcap capture → Snort IDS → Feature extraction → AI validation → Dashboard.</textarea>
        </div>
        <div>
            <label class="label-field">Tools yang Digunakan</label>
            <textarea name="tools_used" rows="2" class="input-field">Wireshark/dumpcap, Snort, iPerf3, SlowHTTPTest, Docker, Laravel Dashboard, Multi-LLM live.</textarea>
        </div>
        <div>
            <label class="label-field">Kesimpulan</label>
            <textarea name="conclusion" rows="4" class="input-field">Berdasarkan skor radar gabungan dan ringkasan alert Snort, sistem mengindikasikan: {{ $experiment->extractedFeature->attack_category ?? '—' }} (Final Score: {{ $experiment->extractedFeature->final_attack_score ?? '—' }}). Status eksperimen setelah evidence gating: {{ str_replace('_',' ',$experiment->experiment_status ?? 'pending') }}. Voting AI sebagai pendukung: {{ $vote['final_decision'] ?? '—' }} dengan confidence rata-rata {{ $vote['voting_average_confidence'] ?? 0 }}% (bukan confidence serangan).</textarea>
        </div>
        <div>
            <label class="label-field">Catatan Batasan</label>
            <textarea name="limitations" rows="3" class="input-field">Eksperimen dilakukan pada subset trafik lab terisolasi. Header completion timing belum diukur secara mikro-detik. Variasi rule Snort terbatas pada community + custom basic.</textarea>
        </div>
        <div>
            <label class="label-field">Rekomendasi Pengembangan</label>
            <textarea name="recommendations" rows="3" class="input-field">1) Tambah ekstraksi fitur waktu antar-header. 2) Variasikan baseline iPerf3 dengan profil bandwidth berbeda. 3) Integrasi ESP32 sebagai edge node metadata di tahap selanjutnya. 4) Perluas dataset uji untuk pelatihan model AI khusus.</textarea>
        </div>
        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('experiments.show', $experiment) }}" class="btn-ghost">Batal</a>
            <button class="btn-primary"><x-icon name="file" class="w-4 h-4"/> Simpan Laporan</button>
        </div>
    </form>
</div>

@endsection
