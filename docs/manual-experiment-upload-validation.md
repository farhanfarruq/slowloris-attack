# Manual Experiment, Akuisisi, dan Validasi

Dokumen ini menjelaskan flow manual di website agar data cyber tidak tercampur antara file akuisisi Wireshark dan file validasi Snort.

## Prinsip Data

Satu eksperimen harus merepresentasikan satu sesi skenario yang jelas.

Contoh yang benar:

- `EXP-001` = `slow-http` dari Attacker `192.168.56.102` ke Target `192.168.56.103`
- Akuisisi = `slow-http-wireshark.pcapng`
- Validasi = `slow-http-snort.log`
- Keduanya memakai label capture yang sama, misalnya `slow-http-20260529-01`

Contoh yang salah:

- `EXP-001` dibuat untuk `slow-http`, tetapi file PCAP dari `iperf-bandwidth`
- File validasi Snort dari `portscan` dipasangkan ke PCAP `http-burst`
- Satu experiment dipakai untuk banyak skenario berbeda

## File Akuisisi

File akuisisi adalah bukti packet capture dari Wireshark/dumpcap.

Format yang diterima:

- `.pcap`
- `.pcapng`
- `.csv`
- `.json`

Metadata penting saat upload:

- `Label Capture`: ID sesi capture, contoh `slow-http-20260529-01`
- `Kode Skenario`: contoh `slow-http`, `http-burst`, `portscan`, `iperf-bandwidth`
- `IP Sumber`: IP attacker VM
- `IP Target`: IP target VM

## File Validasi

File validasi adalah output Snort, bukan PCAP.

Format yang diterima:

- `.log`
- `.txt`
- `.csv`
- `.json`

Saat upload validasi, wajib pilih `Pasangkan File Akuisisi`. Ini mencegah sistem memakai log Snort dari skenario lain.

## Urutan Manual di Website

1. Buka menu `Eksperimen`.
2. Buat experiment baru.
3. Isi `Kode Skenario`, contoh `slow-http`.
4. Buka menu `Akuisisi`.
5. Upload file `.pcapng` hasil Wireshark/dumpcap.
6. Isi `Label Capture`, contoh `slow-http-20260529-01`.
7. Buka menu `Validasi`.
8. Upload file Snort `.log`.
9. Pilih file akuisisi yang benar di field `Pasangkan File Akuisisi`.
10. Buka menu `Analisis`.
11. Pastikan kolom `Pair` bernilai `Siap`.
12. Jalankan `Proses Analisis`.
13. Jalankan `Validasi AI` hanya setelah pasangan akuisisi-validasi lengkap.

## Reset Data Riset Lama

Command ini menghapus data experiment, akuisisi, validasi, alert, fitur, AI result, laporan, dan file upload. Akun login dan API key tetap disimpan.

```bash
docker compose exec app php artisan lab:reset-research-data --force
```

Setelah reset, buat experiment dari website. Jangan pakai data demo lama untuk pengujian cyber.

## Seeder

Seeder sekarang hanya membuat akun admin default:

- Email: `peneliti@lab.test`
- Password: `password`

Seeder tidak membuat experiment, file akuisisi, file validasi, Snort alert, extracted feature, atau hasil AI demo.
