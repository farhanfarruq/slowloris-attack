# Slowloris Lab

Dashboard pribadi berbasis Laravel 11 untuk mengelola dataset traffic lab lokal, analisis pola Slow HTTP, visualisasi, validasi AI, dan laporan.

## Perubahan Utama

- Database default memakai MySQL.
- Pengaturan API AI bisa diisi dari halaman web dan disimpan terenkripsi di database.
- Tampilan memakai tema putih, sederhana, dan bernuansa personal dashboard.
- Target runtime baru adalah ESP32 fisik; dataset VM lama tetap dipertahankan sebagai bukti historis.

## Stack

- Laravel 11
- MySQL 8
- Tailwind CSS
- Chart.js
- TShark/Snort log import untuk data lab

## Setup Lokal

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Pastikan MySQL aktif dan nilai berikut sesuai. Untuk Docker gunakan `DB_HOST=mysql`; untuk menjalankan Laravel langsung di host tanpa Docker, ubah ke `DB_HOST=127.0.0.1` dan pastikan MySQL host bisa diakses.

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=slowloris_lab
DB_USERNAME=slowloris
DB_PASSWORD=secret
```

## Setup Docker

```bash
docker compose up --build
```

Service Docker menjalankan app Laravel dan MySQL. Akses dashboard di `http://localhost:8000`. MySQL tidak dipublish ke port host agar tidak bentrok dengan MySQL lokal; app mengaksesnya lewat host internal `mysql`. Image app dibangun dari `Dockerfile` dengan extension `pdo_mysql` sudah terpasang.

## Akun Demo

- Admin: `peneliti@lab.test` / `password`
- Viewer: `viewer@lab.test` / `password`

## Pengaturan API

Buka `Pengaturan API` sebagai Admin. Isi endpoint, model, API key, dan aktifkan `Gunakan live API` pada provider yang dipakai. API key disimpan terenkripsi dan tidak ditampilkan ulang di UI.

Jika live API tidak aktif atau key kosong, sistem memakai heuristik lokal sebagai fallback.

## ESP32 Physical Target Setup

ESP32 harus menjalankan firmware HTTP dengan endpoint `/`, `/health`, dan `/metrics`. Sambungkan laptop ke SoftAP `ESP32-LAB`; password jaringan tidak disimpan di repository.

Perangkat terverifikasi adalah board generik 30-pin dengan chip `ESP32-D0WD-V3` revision `v3.1`, flash 4 MB, bridge CP2102, Arduino profile `ESP32 Dev Module`, dan Arduino-ESP32 Core 3.3.11.

Konfigurasi default:

```env
TARGET_TYPE=esp32
TARGET_HOST=192.168.4.1
TARGET_PORT=80
CAPTURE_INTERFACE=wlp8s0
ESP32_SERIAL_PORT=/dev/ttyUSB0
```

Periksa target dari host Ubuntu tanpa scanning:

```bash
php artisan esp32:readiness
```

Buat draft ESP32 melalui dashboard, lalu jalankan capture defensif menggunakan kode eksperimen tersebut:

```bash
scripts/esp32-lab/run.sh EXP-001 60
```

Runner host melakukan readiness, metrics before/after, dumpcap, TShark, Snort offline, hashing, dan import ke Laravel. Runner tidak menghasilkan traffic serangan. Jangan jalankan perintah ini sebelum ESP32 aktif dan laptop tersambung ke `ESP32-LAB`.

Panduan VM lama tetap tersedia hanya untuk reproduksi dataset historis; VM bukan target default baru.

## Catatan Keamanan

Aplikasi ini tidak menyediakan tombol eksekusi serangan. Target otomatis dibatasi oleh allowlist ESP32 lab; domain publik dan pencarian target tidak didukung.
# slowloris-attack
# slowloris-attack
