@extends('layouts.app')

@section('title', 'Eksperimen Lab Lokal')
@section('subtitle', 'Topologi lab lokal, tools, dan alur simulasi VM Ubuntu.')

@section('content')

<div class="card mb-4">
    <div class="card-header">
        <p class="card-title">Topologi Lab Terisolasi</p>
        <span class="badge-emerald">Lab Aman · Tidak Untuk Target Publik</span>
    </div>
    <div class="p-5 text-sm text-slate-300 space-y-3">
        <p>Eksperimen dilakukan pada jaringan virtual lokal antara Ubuntu sebagai controller dan target web server di dalam container/Docker. Semua traffic dibatasi pada subnet lab.</p>
        <p class="text-xs text-gray-500">Panduan lengkap: <code>docs/lab-wireshark-snort-shell.md</code></p>
        <ul class="list-disc list-inside space-y-1 text-slate-400">
            <li><span class="text-slate-200">Ubuntu Laptop / VM</span> — controller, juga sebagai sniffer.</li>
            <li><span class="text-slate-200">Target Web Server</span> — Nginx/Apache pada Docker container.</li>
            <li><span class="text-slate-200">Wireshark/dumpcap</span> — akuisisi packet (.pcap, .pcapng).</li>
            <li><span class="text-slate-200">Snort 3</span> — IDS/IPS untuk validasi alert.</li>
            <li><span class="text-slate-200">iPerf3</span> — baseline traffic normal & pengukuran bandwidth.</li>
            <li><span class="text-slate-200">Traffic generator lab rate-limited</span> — generator traffic uji lab.</li>
            <li><span class="text-slate-200">Target VM telemetry</span> - metrik koneksi aktif, resource usage, capture Wireshark, dan alert Snort dari VM target lokal.</li>
        </ul>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><p class="card-title">Diagram Alur Eksperimen</p></div>
    <div class="p-5">
        <div class="flex flex-wrap items-center gap-2 text-xs justify-center">
            @foreach ([
                ['Traffic Generator', 'cyan'],
                ['Target Web Server Lab', 'emerald'],
                ['Packet Capture (Wireshark/dumpcap)', 'sky'],
                ['Snort Validation (IDS/IPS)', 'amber'],
                ['Feature Extraction', 'fuchsia'],
                ['AI Analysis (Multi-Model)', 'rose'],
                ['Dashboard Result', 'violet'],
            ] as $i => $step)
                <div class="px-3 py-2 rounded-lg bg-{{ $step[1] }}-500/15 border border-{{ $step[1] }}-500/30 text-{{ $step[1] }}-200">
                    {{ $step[0] }}
                </div>
                @if (!$loop->last)
                    <span class="text-slate-500">→</span>
                @endif
            @endforeach
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="card">
        <div class="card-header"><p class="card-title">Stack Teknologi Eksperimen</p></div>
        <div class="p-5 grid grid-cols-2 gap-3 text-sm">
            @foreach ([
                'Akuisisi'   => 'Wireshark/dumpcap',
                'IDS/IPS'    => 'Snort 3 (community + custom rules)',
                'Baseline'   => 'iPerf3, browsing lokal HTTP',
                'Generator'  => 'Traffic generator lab rate-limited',
                'Container'  => 'Docker / Docker Compose',
                'Web Target' => 'Nginx / Apache (default config)',
                'Edge Plan'  => 'ESP32 untuk metadata/sensor',
                'Dashboard'  => 'Laravel 11 + Tailwind + Chart.js',
            ] as $key => $val)
                <div class="p-3 rounded-lg bg-slate-950/60 border border-slate-800">
                    <p class="text-[11px] uppercase tracking-wider text-slate-500">{{ $key }}</p>
                    <p class="text-slate-200">{{ $val }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card">
        <div class="card-header"><p class="card-title">Roadmap ESP32 (Tahap Berikutnya)</p></div>
        <div class="p-5 text-sm space-y-2">
            <p class="text-slate-300">ESP32 akan berperan sebagai <strong>edge node ringan</strong>, bukan sebagai penyerang. Ide implementasi:</p>
            <ol class="list-decimal list-inside space-y-1 text-slate-400">
                <li>Mengirim metadata trigger eksperimen (start/stop) ke backend.</li>
                <li>Memantau status sensor jaringan (mis. status link, beacon scanning Wi-Fi).</li>
                <li>Mengirim ringkasan event ringan via MQTT/HTTP REST ke dashboard ini.</li>
                <li>Tahap lanjutan: integrasi dengan firmware kustom untuk pengukuran trafik lokal di skala IoT.</li>
            </ol>
            <div class="mt-3 p-3 rounded-lg bg-cyan-500/10 border border-cyan-500/30 text-cyan-200 text-xs">
                Halaman ini merangkum alur lab lokal. Data eksperimen tetap berasal dari file akuisisi, validasi Snort, dan metrik target yang diunggah ke dashboard.
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header"><p class="card-title">Catatan Etis & Keamanan</p></div>
    <div class="p-5 text-sm text-slate-300 space-y-2">
        <p>Website ini <strong>tidak menyediakan tombol serangan langsung</strong> ke target publik. Semua aksi terbatas pada upload data eksperimen yang sudah dilakukan di lab terisolasi.</p>
        <p>Pengujian traffic attack profile harus dilakukan pada infrastruktur yang Anda miliki atau dengan izin tertulis. Penggunaan untuk menyerang sistem milik orang lain melanggar hukum dan etika penggunaan sistem.</p>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <p class="card-title">Workflow Draft Eksperimen VM</p>
    </div>
    <div class="p-5 text-sm text-slate-300 space-y-2">
        <p>Draft VM membuat satu eksperimen kosong untuk setiap tool profile: Slowloris, LOIC, HOIC, Hping3, Torshammer, dan Xerxes.</p>
        <p>Draft tidak berisi command serangan, payload, automation, hasil sintetis AI, PCAP, atau log Snort. Isi IP target, IP sumber, interface, dan durasi capture setelah lab VM siap, lalu upload data akuisisi dan validasi dari eksperimen nyata.</p>
        <p>Target default disimpan sebagai <code>vm_ubuntu_server</code> agar laporan jelas bahwa data saat ini berasal dari VM, bukan dataset generate AI.</p>
    </div>
</div>

@endsection
