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

Belum ada hasil baru yang diklaim pada dokumen ini. Setelah skenario dijalankan, isi bagian ini dengan ukuran file, rentang waktu capture, jumlah baris metrik, ringkasan koneksi, dan jumlah alert Snort berdasarkan artefak baru.

## Format Ringkasan Hasil

| Skenario | Pcap | Snort log | Metrics CSV | Kesimpulan |
| --- | --- | --- | --- | --- |
| baseline-normal | Belum dijalankan | Belum dijalankan | Belum dijalankan | Belum ada klaim |
| slow-http | Belum dijalankan | Belum dijalankan | Belum dijalankan | Belum ada klaim |
| http-burst | Belum dijalankan | Belum dijalankan | Belum dijalankan | Belum ada klaim |
| portscan | Belum dijalankan | Belum dijalankan | Belum dijalankan | Belum ada klaim |
| iperf-bandwidth | Belum dijalankan | Belum dijalankan | Belum dijalankan | Belum ada klaim |

## Kesimpulan Sementara

Project sudah lebih siap untuk eksperimen defensif berbasis bukti. Kesimpulan teknis final harus menunggu artefak aktual dari VM lokal dan hasil analisis dashboard.
