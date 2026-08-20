# Panduan Singkat Menjalankan Project di Windows

Panduan ini hanya menjelaskan cara memasang Docker dan menjalankan dashboard sampai bisa dibuka di browser.

## 1. Yang dibutuhkan

- Windows 11 64-bit yang masih didukung.
- Koneksi internet.
- Docker Desktop.
- Folder project slowloris-attack.

PHP, Composer, MySQL, dan Node.js tidak perlu dipasang secara manual karena akan dijalankan melalui Docker.

## 2. Instal WSL2

Klik kanan **Windows PowerShell**, lalu pilih **Run as administrator**.

Jalankan:

~~~powershell
wsl --install
~~~

Restart komputer jika diminta.

Setelah komputer menyala kembali, buka PowerShell dan periksa:

~~~powershell
wsl --version
~~~

Jika informasi versi WSL muncul, lanjutkan ke instalasi Docker Desktop.

## 3. Instal Docker Desktop

1. Unduh Docker Desktop dari:
   https://docs.docker.com/desktop/setup/install/windows-install/
2. Jalankan installer.
3. Gunakan pilihan **WSL 2** jika ditanyakan.
4. Setelah selesai, buka Docker Desktop.
5. Tunggu sampai status Docker menunjukkan **Running**.

Periksa melalui PowerShell:

~~~powershell
docker --version
docker compose version
~~~

Jika kedua perintah menampilkan versi, Docker sudah siap.

## 4. Siapkan folder project

Letakkan folder project pada lokasi yang mudah, misalnya:

~~~text
C:\slowloris-attack
~~~

Hindari nama folder yang terlalu panjang.

Buka folder tersebut, klik kanan area kosong, lalu pilih **Open in Terminal**.

Pastikan PowerShell berada di folder project:

~~~powershell
cd C:\slowloris-attack
Test-Path artisan
Test-Path docker-compose.yml
~~~

Jika keduanya menghasilkan **True**, lokasi folder sudah benar.

## 5. Siapkan project

Jalankan semua perintah berikut secara berurutan.

### Buat file konfigurasi

~~~powershell
if (!(Test-Path .env)) { Copy-Item .env.example .env }
~~~

### Build container aplikasi

~~~powershell
docker compose build app
~~~

Tunggu sampai proses selesai.

### Instal dependency PHP

~~~powershell
docker compose run --rm --no-deps app composer install --no-interaction
~~~

### Buat application key

~~~powershell
docker compose run --rm --no-deps app php artisan key:generate
~~~

### Build tampilan website

~~~powershell
docker run --rm -v "$($PWD.Path):/app" -w /app node:24-bookworm-slim sh -lc "npm ci && npm run build"
~~~

Proses pertama kali mungkin agak lama karena Docker perlu mengunduh image.

## 6. Jalankan project

~~~powershell
docker compose up -d
~~~

Periksa statusnya:

~~~powershell
docker compose ps
~~~

Pastikan container aplikasi dan MySQL berstatus **Running** atau **Healthy**.

Buka dashboard:

~~~powershell
Start-Process http://localhost:8000
~~~

Atau buka alamat berikut secara manual:

http://localhost:8000

Untuk akun login, lihat bagian **Akun Demo** pada file README.md di folder project.

## 7. Menjalankan project pada hari berikutnya

1. Buka Docker Desktop.
2. Tunggu sampai Docker aktif.
3. Buka PowerShell.
4. Jalankan:

~~~powershell
cd C:\slowloris-attack
docker compose up -d
~~~

Kemudian buka:

http://localhost:8000

## 8. Menghentikan project

~~~powershell
cd C:\slowloris-attack
docker compose stop
~~~

Perintah tersebut menghentikan aplikasi tanpa menghapus database.

Jangan menjalankan:

~~~powershell
docker compose down -v
~~~

Opsi tersebut dapat menghapus database Docker.

## 9. Jika terjadi masalah

### Docker tidak terhubung

Pastikan Docker Desktop sudah dibuka dan berstatus Running.

### Error vendor/autoload.php

Jalankan kembali:

~~~powershell
docker compose run --rm --no-deps app composer install --no-interaction
~~~

### Tampilan website tidak memiliki CSS

Jalankan kembali:

~~~powershell
docker run --rm -v "$($PWD.Path):/app" -w /app node:24-bookworm-slim sh -lc "npm ci && npm run build"
~~~

### Website tidak dapat dibuka

Periksa container:

~~~powershell
docker compose ps
docker compose logs --tail=30 app
~~~

## Ringkasan command

Untuk instalasi pertama:

~~~powershell
cd C:\slowloris-attack
if (!(Test-Path .env)) { Copy-Item .env.example .env }
docker compose build app
docker compose run --rm --no-deps app composer install --no-interaction
docker compose run --rm --no-deps app php artisan key:generate
docker run --rm -v "$($PWD.Path):/app" -w /app node:24-bookworm-slim sh -lc "npm ci && npm run build"
docker compose up -d
Start-Process http://localhost:8000
~~~

Untuk pemakaian berikutnya:

~~~powershell
cd C:\slowloris-attack
docker compose up -d
Start-Process http://localhost:8000
~~~

Panduan ini hanya sampai dashboard berhasil dijalankan. Proses simulasi Wireshark/Snort pada dua VM tidak dibahas di sini.
