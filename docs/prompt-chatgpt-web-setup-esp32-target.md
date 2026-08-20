# Prompt ChatGPT Web: Setup ESP32 sebagai Target Web Server Lab

Salin seluruh prompt berikut ke ChatGPT Web. Ganti bagian dalam tanda `<...>` jika informasinya sudah diketahui.

```text
Saya sedang menyiapkan ESP32 sebagai TARGET WEB SERVER untuk penelitian defensif Slow HTTP/Slowloris di laboratorium lokal yang terisolasi. Tolong cari dan susun panduan setup lengkap, terbaru, dan dapat langsung dipraktikkan.

PERANGKAT SAYA
- Board yang dijual dengan deskripsi: ESP32 BLE IoT Development Board, 38 pin/30 pin, USB Type-C, USB-to-UART CP2102 atau CH340.
- Tulisan pada modul/chip: <ISI JIKA SUDAH DIKETAHUI; JIKA BELUM, AJARI CARA MEMERIKSANYA>.
- Sistem operasi laptop: <WINDOWS 10/11 ATAU UBUNTU/LINUX>.
- Metode pengembangan yang diutamakan: Arduino IDE.
- ESP32 akan menjadi web server HTTP lokal, bukan server publik.

BATAS KEAMANAN DAN RUANG LINGKUP
- Semua pengujian hanya pada ESP32 milik saya dan jaringan lab terisolasi.
- Jangan memberikan tool, script, atau langkah untuk menyerang target publik, scanning internet, evasion, bypass, persistence, atau meningkatkan kemampuan serangan.
- Fokus hanya pada setup target, observabilitas, capture defensif, validasi kesiapan, dan dokumentasi penelitian.
- Jangan mengklaim Wireshark, TShark, atau Snort 3 dapat diinstal pada ESP32. Jelaskan bahwa alat tersebut berjalan di laptop/VM/router sensor.

GUNAKAN SUMBER TERBARU
- Telusuri web dan prioritaskan dokumentasi resmi Espressif, Arduino-ESP32, Silicon Labs untuk CP210x, WCH untuk CH340, Wireshark, dan Snort 3.
- Cantumkan tautan sumber resmi pada langkah yang relevan.
- Jangan mengarang nama board, pin, URL Board Manager, versi paket, driver, atau perintah.
- Jika varian board saya belum pasti, mulai dengan cara mengidentifikasi chip ESP32, jumlah pin, chip USB-to-UART, port serial, dan board profile yang benar. Jangan langsung berasumsi bahwa board adalah ESP32 klasik, S2, S3, C3, atau varian lain.

HASIL YANG SAYA MINTA

1. Pemeriksaan hardware
- Cara membaca tulisan pada modul ESP32 dan chip CP2102/CH340.
- Cara membedakan board 30 pin dan 38 pin.
- Cara memastikan kabel USB Type-C mendukung data, bukan hanya charging.
- Peringatan pin boot/strapping dan pin yang sebaiknya tidak digunakan, berdasarkan varian yang teridentifikasi.

2. Instalasi komputer
- Langkah memasang Arduino IDE versi terbaru yang stabil.
- URL Board Manager ESP32 resmi dan cara memasang paket board Espressif.
- Cara menentukan apakah driver CP210x atau CH340 diperlukan dan mengambilnya hanya dari vendor resmi.
- Cara menemukan port COM di Windows atau `/dev/ttyUSB*`/`/dev/ttyACM*` di Linux.
- Solusi ringkas untuk port tidak muncul, permission denied, gagal upload, dan pesan “Connecting...”.

3. Uji board bertahap
- Upload program minimal untuk Serial Monitor pada 115200 baud.
- Upload Blink tanpa mengasumsikan nomor LED; jelaskan cara menemukan `LED_BUILTIN` atau melewati Blink bila board tidak memiliki LED pengguna.
- Uji Wi-Fi dasar sebelum membuat web server.
- Berikan tanda keberhasilan dan kegagalan untuk setiap tahap.

4. Firmware target web server
- Gunakan library bawaan yang tersedia pada Arduino-ESP32; jangan menambah library jika tidak diperlukan.
- Buat firmware lengkap dan dapat dikompilasi yang menjalankan ESP32 dalam mode SoftAP terlebih dahulu.
- SSID lab: `ESP32-LAB`.
- Password contoh minimal 8 karakter dan beri tahu saya agar menggantinya.
- Pertahankan IP default SoftAP bila sesuai, lalu tampilkan IP aktual melalui Serial Monitor.
- Jalankan HTTP pada port 80.
- Sediakan endpoint:
  - `/` untuk halaman sederhana yang menyatakan ESP32 aktif;
  - `/health` untuk status `200 OK` yang ringan;
  - `/metrics` untuk JSON sederhana berisi uptime, free heap, minimum free heap jika didukung, jumlah request, dan alasan reset bila API resmi tersedia.
- Hindari `delay()` panjang dan operasi berat dalam handler.
- Jangan membuat endpoint serangan atau kode penyerang.
- Tambahkan log Serial yang ringkas: waktu boot, mode Wi-Fi, SSID, IP, request masuk, free heap, dan error penting. Jangan mencetak password.
- Jelaskan keterbatasan web server dan resource ESP32 tanpa mengubahnya menjadi panduan serangan.

5. Cara menghubungkan dan menguji
- Laptop harus tersambung ke SSID `ESP32-LAB` melalui Wi-Fi; USB tetap boleh terhubung untuk daya, upload, dan Serial Monitor.
- Jelaskan bahwa HTTP berjalan melalui Wi-Fi, bukan melalui CP2102/CH340.
- Berikan langkah browser dan perintah pendek `curl` untuk `/`, `/health`, dan `/metrics`.
- Berikan cara mengetahui IP ESP32 jika berbeda dari contoh.
- Berikan checklist bahwa ESP32 tetap responsif dan tidak reset pada traffic normal.

6. Topologi alternatif untuk eksperimen final
- Setelah SoftAP berhasil, jelaskan opsi Station mode: ESP32 dan laptop/VM berada pada router/AP lab yang sama.
- Bandingkan SoftAP dan Station mode secara singkat.
- Rekomendasikan topologi paling mudah untuk percobaan awal dan topologi paling baik untuk capture independen.
- Jangan menyarankan jaringan kampus, kantor, Wi-Fi publik, port forwarding, atau exposure internet.

7. Persiapan akuisisi dan validasi
- Jelaskan lokasi Wireshark/dumpcap/TShark dan Snort 3: laptop, VM sensor, atau router/AP; bukan ESP32.
- Tunjukkan cara memilih interface Wi-Fi yang benar dan capture filter hanya untuk IP ESP32 dan TCP port 80.
- Hasil akuisisi harus `.pcap` atau `.pcapng`.
- Tunjukkan validasi offline Snort 3 yang membaca PCAP yang sama dan menghasilkan `alert_fast.txt` atau file `.log`.
- Gunakan placeholder seperti `<INTERFACE>`, `<IP_ESP32>`, `<SNORT_LUA>`, dan `<RULES_FILE>`; jelaskan cara menemukan nilainya.
- Jangan membuat rule deteksi secara asal. Jika rule Slow HTTP khusus diperlukan, tandai sebagai pekerjaan terpisah yang harus diuji terhadap traffic normal dan false positive.
- Jelaskan bahwa nol alert adalah hasil valid dan tidak boleh dimanipulasi.

8. Bukti penelitian dan reproducibility
- Buat format tabel pencatatan: model board, chip, jumlah pin, USB bridge, versi Arduino IDE, versi Arduino-ESP32 core, hash/versi firmware, mode jaringan, SSID tanpa password, IP/MAC ESP32, IP sensor, interface capture, endpoint, waktu mulai/selesai, firmware log, nama PCAP, nama Snort log, ruleset, dan SHA-256 setiap artefak.
- Tegaskan bahwa PCAP yang ditangkap laptop/router tetap merupakan traffic eksperimen dengan ESP32 sebagai target, tetapi jangan disebut “PCAP dibuat oleh ESP32”.

9. Troubleshooting
- Board/port tidak muncul.
- Driver CP2102/CH340 salah.
- Upload gagal atau perlu tombol BOOT.
- Serial Monitor berisi karakter acak.
- Laptop tersambung ke SoftAP tetapi tidak bisa membuka web.
- IP salah.
- Wireshark tidak melihat packet.
- PCAP kosong.
- Snort tidak menghasilkan alert atau format log tidak sesuai.
- ESP32 reset, watchdog, heap turun, atau brownout.

10. Format jawaban
- Gunakan Bahasa Indonesia sederhana.
- Susun langkah bernomor dari nol sampai siap dipakai.
- Untuk setiap langkah tampilkan: tujuan, tindakan, hasil yang diharapkan, dan troubleshooting singkat.
- Pisahkan perintah Windows dan Ubuntu/Linux jika berbeda.
- Berikan satu blok firmware final utuh, bukan potongan yang saling bertentangan.
- Jangan melompati verifikasi setelah upload.
- Akhiri dengan checklist “ESP32 siap menjadi target lab” dan daftar file/bukti yang harus saya simpan.
- Jika informasi board atau OS belum cukup untuk memberikan langkah yang aman dan tepat, ajukan maksimal lima pertanyaan identifikasi terlebih dahulu, lalu tunggu jawaban saya sebelum memberikan firmware final.
```

## Informasi yang sebaiknya disiapkan

Sebelum mengirim prompt, foto atau catat:

1. Tulisan pada kaleng modul ESP32.
2. Tulisan pada chip kecil dekat konektor USB Type-C.
3. Jumlah pin aktual: 30 atau 38.
4. Sistem operasi dan versinya.
5. Pesan error Arduino IDE, jika sudah pernah mencoba upload.

