# Inventaris Project - 2026-06-12

## Kondisi Awal

Project adalah aplikasi Laravel 11 untuk analisis defensif Slow HTTP/Slowloris berbasis eksperimen lab. Modul utama mencakup upload akuisisi, upload validasi Snort, ekstraksi fitur, scoring rule-based, validasi AI, visualisasi, audit log, dan laporan akhir.

## Struktur Penting Dipertahankan

- `app/Services/ScoringService.php` sebagai sumber keputusan scoring dan evidence gate.
- `app/Services/AnalysisService.php` sebagai pembangun extracted features dan payload AI.
- `app/Services/AiValidationService.php` sebagai validator tambahan, bukan pengambil keputusan utama.
- `app/Http/Controllers/ApiSettingController.php`, `app/Models/AiProviderSetting.php`, `config/ai.php`, `config/services.php`, dan `resources/views/settings/api.blade.php` sebagai area pengaturan API yang dipertahankan.
- `scripts/vm-lab/` sebagai orkestrasi eksperimen VM lokal.
- `tests/Unit/ScoringServiceTest.php`, `tests/Unit/AiValidationServiceTest.php`, dan test feature analisis sebagai validasi regresi.

## Area Dirapikan

- Dokumentasi riset baru dipusatkan di `docs/research/` agar tidak bercampur dengan catatan audit lama.
- Teks placeholder pada halaman lab diganti dengan deskripsi telemetry VM target.
- Script VM lab diperkuat dengan validasi subnet `192.168.56.x` dan penolakan eksplisit terhadap IP non-lab.
- Monitor target sekarang menghasilkan `*-target-metrics.csv` berisi koneksi TCP dan resource usage selama capture.

## Area Yang Tidak Dihapus

File lama tidak dihapus pada sesi ini. Backup dibuat di `backups/pre-research-cleanup-2026-06-12/`. File contoh lama tetap bisa diaudit, tetapi dokumen riset final sebaiknya memakai `docs/research/`.

## Catatan API

Pengaturan API dipertahankan. Tidak ada API key, token, atau credential yang ditulis ke dokumentasi baru.
