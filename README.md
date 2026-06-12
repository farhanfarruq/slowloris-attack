# Slowloris Lab

Dashboard pribadi berbasis Laravel 11 untuk mengelola dataset traffic lab lokal, analisis pola Slow HTTP, visualisasi, validasi AI, dan laporan.

## Perubahan Utama

- Database default memakai MySQL.
- Pengaturan API AI bisa diisi dari halaman web dan disimpan terenkripsi di database.
- Tampilan memakai tema putih, sederhana, dan bernuansa personal dashboard.
- Dokumentasi simulasi VM Ubuntu multi-IP tersedia di `docs/simulasi-vm-ubuntu.md`.

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

## Simulasi VM Ubuntu

Panduan lab multi-IP ada di `docs/simulasi-vm-ubuntu.md`. Gunakan hanya pada Host-only/Internal network dan target lokal milik sendiri.

## Catatan Keamanan

Aplikasi ini tidak menyediakan tombol eksekusi serangan. Fungsinya untuk upload data, ekstraksi fitur, analisis, validasi, dan laporan dari lab lokal.
# slowloris-attack
# slowloris-attack
