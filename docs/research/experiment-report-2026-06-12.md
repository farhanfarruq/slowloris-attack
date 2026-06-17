# Laporan Eksperimen - 2026-06-12

## Status Sesi

Pada sesi ini sudah dilakukan audit project, backup artefak penting, perapihan dokumentasi, dan penguatan script VM lab. Eksekusi traffic Slow HTTP belum dicatat sebagai hasil final sampai runner VM berhasil dijalankan dan artefak baru tersedia.

## Backup

Backup awal tersedia di `backups/pre-research-cleanup-2026-06-12/`.

## Perubahan Teknis

- Guard subnet pada script attacker, runner host, dan monitor target diperketat ke `192.168.56.x`.
- Monitor target menghasilkan CSV metrik aktual: koneksi port 80, koneksi dari attacker, load 1 menit, MemAvailable, dan TCP socket in-use.
- Runner host mengambil pcap, Snort log, dan CSV metrik dari target.
- Dokumentasi riset profesional ditambahkan di `docs/research/`.
- Placeholder UI lab diganti dengan deskripsi telemetry VM target.

## Hasil Data Aktual

Setelah SSH key non-interaktif berhasil dipasang, baseline normal dan Slow HTTP terkontrol sudah dijalankan pada VM lokal. Target dan attacker berada pada subnet lab `192.168.56.x`; tidak ada target publik yang digunakan.

Ringkasan mesin:

| Skenario | Exit code | Pcap | Snort log | Metrics CSV | Kesimpulan |
| --- | ---: | ---: | ---: | ---: | --- |
| baseline-normal | 0 | 16,000 byte | 1,216 byte | 4,753 byte | Baseline valid; tidak ada koneksi port 80 aktif pada sampel metrik |
| slow-http | timeout runner, artefak dipulihkan | 50,596 byte | 24,320 byte | 5,250 byte | Traffic Slow HTTP terkontrol terekam; target tetap available |
| http-burst | Tidak dijalankan | 0 byte | 0 byte | 0 byte | Belum ada klaim |
| portscan | Tidak dijalankan | 0 byte | 0 byte | 0 byte | Belum ada klaim |
| iperf-bandwidth | Tidak dijalankan | 0 byte | 0 byte | 0 byte | Belum ada klaim |

Indikator ringkas:

| Indikator | Baseline | Slow HTTP |
| --- | ---: | ---: |
| PCAP frames | 80 | 340 |
| Unique TCP streams | 8 | 30 |
| HTTP requests terdeteksi PCAP | 8 | 10 |
| Snort alert lines | 8 | 160 |
| Max established TCP port 80 | 0 | 20 |
| Max koneksi attacker terpantau | 0 | 20 |
| Max TCP socket in-use | 5 | 25 |

Catatan: Snort log pada dua skenario memuat rule `LOCAL LAB repeated TCP connection attempts`. Pada run Slow HTTP ini belum ada alert eksplisit `possible Slow HTTP or Slowloris traffic`; karena itu kesimpulan harus memakai gabungan PCAP, metrics CSV, dan evidence gate dashboard, bukan hanya nama alert Snort.

Artefak pendukung:

- `storage/app/vm-lab-captures/vm-run-summary-2026-06-12.json`
- `storage/app/vm-lab-captures/vm-artifact-analysis-2026-06-12.json`
- `storage/app/vm-lab-captures/vm-pcap-analysis-2026-06-12.json`
- `storage/app/vm-lab-captures/baseline-normal-run-redacted.log`
- `storage/app/vm-lab-captures/slow-http-run-redacted.log`

## Kesimpulan Sementara

Project sudah menghasilkan artefak VM lokal aktual untuk baseline dan Slow HTTP terkontrol. Perbedaan utama terlihat pada jumlah koneksi port 80 yang bertahan, total koneksi dari attacker, jumlah stream TCP, dan volume alert Snort. Kesimpulan deteksi final tetap harus divalidasi melalui dashboard agar evidence gate membedakan Slow HTTP dari false positive seperti HTTP burst, port scan, dan iPerf.

## Tindak Lanjut Teknis

1. Upload baseline dan Slow HTTP artefak ke dashboard.
2. Jalankan analisis dashboard dan catat hasil scoring, AI validation, gate reasons, dan final report.
3. Jalankan skenario pembanding `http-burst`, `portscan`, dan `iperf-bandwidth`.
4. Perbaiki rule Snort lokal jika riset membutuhkan alert eksplisit Slow HTTP, tetapi jangan jadikan Snort satu-satunya sumber keputusan.
