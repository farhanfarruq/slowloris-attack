# Metadata Eksperimen Baseline HTTP ESP32

## 1. Identitas Eksperimen

Nama eksperimen: Baseline HTTP Normal
Jenis traffic: HTTP normal
Target: ESP32 Web Server
PCAP: baseline_http_02.pcapng
Kondisi: Baseline / kontrol normal

Eksperimen ini digunakan sebagai baseline traffic HTTP normal sebelum dilakukan analisis terhadap traffic eksperimen lainnya.

## 2. Perangkat Target

Chip: ESP32-D0WD-V3
Revision: v3.1
Arsitektur: Classic ESP32
CPU: Dual Core, hingga 240 MHz
Flash: 4 MB
USB-to-UART bridge: Silicon Labs CP2102
Serial port Ubuntu: /dev/ttyUSB0
MAC ESP32: 20:50:0d:2b:39:f0

Profil board Arduino:
ESP32 Dev Module

Arduino-ESP32 Core:
3.3.11

Catatan:
Board merupakan board ESP32 generik 30-pin. Identifikasi chip dilakukan menggunakan esptool sehingga tidak bergantung pada asumsi berdasarkan bentuk fisik board.

## 3. Konfigurasi Jaringan

Mode jaringan ESP32: SoftAP
SSID: ESP32-LAB
Password: TIDAK DICATAT DALAM ARTEFAK PENELITIAN

IP ESP32:
192.168.4.1

IP sensor/laptop saat PCAP direkam:
192.168.4.2

Interface capture Ubuntu:
wlp8s0

HTTP port:
TCP/80

Endpoint baseline:
- /
- /health
- /metrics

Topologi:

ESP32 SoftAP / Web Server
        |
        | Wi-Fi / HTTP TCP 80
        |
Laptop Ubuntu
        |
        +-- dumpcap / Wireshark / TShark
        |
        +-- Snort 3

Wireshark, TShark, dumpcap, dan Snort 3 berjalan pada laptop/sensor, BUKAN pada ESP32.

## 4. Akuisisi PCAP

Nama file:
captures/baseline_http_02.pcapng

Capture interface:
wlp8s0

Capture filter:
host 192.168.4.1 and tcp port 80

Jumlah paket:
34

Dropped packets saat capture:
0

Timestamp paket pertama:
2026-08-14T01:04:49.242944264+0700

Timestamp paket terakhir:
2026-08-14T01:04:53.438725874+0700

Catatan:
Timestamp di atas merupakan waktu paket pertama dan terakhir yang terdapat di dalam PCAP, bukan waktu absolut proses dumpcap mulai dan berhenti.

SHA-256 PCAP:
44de39fca63ed132357bfc338909316929af566e73713d8d61a8f93722edbed7

## 5. Isi Traffic Baseline

Traffic normal terdiri dari tiga transaksi HTTP:

1. GET /
2. GET /health
3. GET /metrics

Semua endpoint menghasilkan HTTP response 200 pada pengujian baseline.

Hasil inspeksi Snort menunjukkan:

HTTP requests: 3
HTTP responses: 3
HTTP GET requests: 3
TCP sessions: 3
Packets received: 34
Packets analyzed: 34

## 6. Snort

Snort:
3.12.2.0

LibDAQ:
3.0.27

libpcap:
1.10.4

Konfigurasi:
 /usr/local/etc/snort/snort.lua

Mode analisis:
Offline PCAP / read-file

DAQ PCAP:
pcap(v4)

Hasil:
34 paket diterima
34 paket dianalisis
3 HTTP requests
3 HTTP responses
3 TCP sessions

## 7. Baseline Alert

Logger:
alert_fast

File:
logs/snort/baseline_alert/alert_fast.txt

Jumlah alert:
0

Jumlah baris:
0

Interpretasi:
Tidak terdapat rule aktif pada konfigurasi baseline yang menghasilkan alert terhadap traffic HTTP normal yang direkam.

Hasil 0 alert dipertahankan apa adanya dan tidak dimodifikasi untuk memaksakan terbentuknya alert.

SHA-256 alert_fast.txt:
e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855

## 8. Log Analisis Snort

File:
logs/snort/snort_baseline_http_02.log

SHA-256:
16563e6fec478446eda6ad1888c5d64e713557bf6f73d1c49ec724a8d36a639b

## 9. Integritas Artefak

baseline_http_01.pcapng:
a2c438685eccb5ef2d101fd5dd6e855df2cdd6e78502f7a3af1c35f6ee13ac87

baseline_http_02.pcapng:
44de39fca63ed132357bfc338909316929af566e73713d8d61a8f93722edbed7

snort_baseline_http_02.log:
16563e6fec478446eda6ad1888c5d64e713557bf6f73d1c49ec724a8d36a639b

alert_fast.txt:
e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855

## 10. Status Baseline

Status:
BASELINE BERHASIL

Kriteria yang telah terpenuhi:
- ESP32 dapat menjalankan HTTP web server.
- Laptop dapat berkomunikasi dengan ESP32 melalui jaringan lab lokal.
- HTTP endpoint memberikan response normal.
- dumpcap dapat merekam traffic ESP32.
- PCAP dapat dibaca oleh Wireshark/TShark.
- Snort 3 dapat membaca PCAP secara offline.
- Seluruh 34 paket berhasil dianalisis Snort.
- HTTP Inspector mendeteksi 3 request dan 3 response.
- Baseline menghasilkan 0 alert.
- Artefak utama memiliki SHA-256 untuk pemeriksaan integritas.

## 11. Catatan Reproducibility

Firmware hash:
BELUM DICATAT

Firmware source snapshot:
BELUM DICATAT

Snort ruleset/custom rules:
Belum ada custom rule untuk eksperimen ini.

Baseline ini tidak menggunakan rule khusus Slow HTTP/Slowloris.

Password jaringan tidak disimpan dalam metadata.
