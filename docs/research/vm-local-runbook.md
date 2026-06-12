# Runbook Eksperimen VM Lokal

## Scope

Runbook ini hanya untuk dua VM lokal pada subnet lab `192.168.56.x`: satu attacker VM dan satu target VM. Jangan gunakan target publik, domain publik, atau subnet di luar lab.

## Alur Eksperimen

1. Verifikasi target dan attacker berada di subnet lab.
2. Siapkan target: Nginx, Snort, dumpcap/Wireshark, iPerf3, dan direktori lab.
3. Rekam baseline normal dengan traffic rendah.
4. Jalankan skenario Slow HTTP terkontrol dengan koneksi dan durasi terbatas.
5. Jalankan skenario pembanding: HTTP burst, port scan terbatas pada port lab, dan iPerf bandwidth.
6. Ambil artefak dari target: pcapng, Snort log, dan CSV metrik target.
7. Upload artefak ke dashboard sebagai pasangan acquisition-validation.
8. Jalankan analisis, scoring, AI validation, visualisasi, dan laporan.

## Guard Keselamatan

Script berhenti jika `ATTACKER_IP` atau `TARGET_IP` bukan `192.168.56.x`. Hentikan eksperimen jika target tidak responsif, koneksi SSH putus berulang, load meningkat tidak wajar, atau service target gagal pulih setelah skenario selesai.

## Artefak Wajib

- `storage/app/vm-lab-captures/<scenario>-wireshark.pcapng`
- `storage/app/vm-lab-captures/<scenario>-snort.log`
- `storage/app/vm-lab-captures/<scenario>-target-metrics.csv`

## Indikator Deteksi

- Jumlah koneksi TCP port 80 yang bertahan lama.
- Kenaikan koneksi dari attacker VM ke target VM.
- Pola Snort rule lokal untuk Slow HTTP atau pembanding.
- Ketersediaan resource target: load 1 menit, MemAvailable, dan TCP socket in-use.
- Korelasi waktu antara capture, alert, dan metrik target.

## Batasan

Snort rule lokal adalah indikator validasi lab, bukan signature universal. Keputusan akhir tetap memakai evidence gate pada `ScoringService` dan harus membedakan Slow HTTP dari HTTP burst, iPerf, port scan, baseline normal, dan data tidak lengkap.
