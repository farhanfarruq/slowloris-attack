# Serangan 08 - Torshammer (Slow HTTP POST Body) Dengan Wireshark + Snort

Skenario ini mensimulasikan Torshammer — tool serangan Slow HTTP yang berbeda dari Slowloris. Torshammer mengirim POST request dengan `Content-Length` besar tetapi body dikirim sangat lambat, menahan koneksi lama dan menghabiskan connection pool server. Selain itu, script juga menjalankan mode Slow Read yang memanfaatkan kecil TCP receive window.

## Prasyarat

Sebelum menjalankan skenario ini, pastikan setup VM sudah selesai. Jika belum, ikuti langkah berikut **sekali saja**.

### 1. Install Tools di VM Target

```bash
scp scripts/vm-lab/install-vm-tools.sh target@192.168.56.103:/tmp/
ssh -tt target@192.168.56.103 \
  "chmod +x /tmp/install-vm-tools.sh && ROLE=target /tmp/install-vm-tools.sh"
```

Tools yang dipasang: OpenSSH, Nginx, Wireshark/dumpcap, Snort, iPerf3.

### 2. Install Tools di VM Attacker

```bash
scp scripts/vm-lab/install-vm-tools.sh attacker@192.168.56.102:/tmp/
ssh -tt attacker@192.168.56.102 \
  "chmod +x /tmp/install-vm-tools.sh && ROLE=attacker /tmp/install-vm-tools.sh"
```

Tools yang dipasang: curl, ApacheBench, **slowhttptest**, iPerf3, Nmap, hping3.

> **slowhttptest wajib ada** untuk skenario ini. Verifikasi: `slowhttptest -v`

### 3. Setup Sudo Lab di VM Target (sekali saja)

```bash
cd "/home/farhan/Documents/VsCode Project/slowloris-attack"
chmod +x scripts/vm-lab/*.sh

ATTACKER_SSH="attacker@192.168.56.102" \
TARGET_SSH="target@192.168.56.103" \
scripts/vm-lab/setup-remote-lab-sudo.sh
```

---

## Perbedaan Torshammer vs Slowloris

| Aspek | Slowloris | Torshammer |
| --- | --- | --- |
| Vektor | Kirim header HTTP sangat lambat | Kirim POST body sangat lambat |
| Method | GET (sebagian besar) | POST dengan body besar |
| `Content-Length` | Tidak relevan | Dipasang besar, body dikirim sedikit-sedikit |
| Tambahan | — | Juga mode Slow Read (TCP window kecil) |

---

## Output Wajib

```text
storage/app/vm-lab-captures/torshammer-wireshark.pcapng
storage/app/vm-lab-captures/torshammer-snort.log
storage/app/vm-lab-captures/torshammer-target-metrics.csv
```

---

## Jalankan

Script Torshammer menjalankan dua fase berurutan:
1. **Slow Body POST** (`slowhttptest -B`): koneksi POST dengan body lambat selama `DURATION` detik.
2. **Slow Read** (`slowhttptest -R`): advertise TCP window kecil sehingga server tidak bisa mengirim respons cepat.

```bash
cd "/home/farhan/Documents/VsCode Project/slowloris-attack"

SCENARIO="torshammer" \
ATTACKER_SSH="attacker@192.168.56.102" \
TARGET_SSH="target@192.168.56.103" \
ATTACKER_IP="192.168.56.102" \
TARGET_IP="192.168.56.103" \
TARGET_IFACE="enp0s8" \
CAPTURE_SECONDS=175 \
CONNECTIONS=40 \
RATE=5 \
DURATION=75 \
scripts/vm-lab/run-wireshark-snort-scenario-from-host.sh
```

> **Hitung `CAPTURE_SECONDS`**: dua fase berjalan berurutan, masing-masing `DURATION` detik. Gunakan rumus: `CAPTURE_SECONDS = (DURATION × 2) + 20`. Untuk `DURATION=75`: `75 × 2 + 20 = 170`, dibulatkan ke 175.

---

## Parameter Penting

| Variabel | Default | Penjelasan |
| --- | --- | --- |
| `CONNECTIONS` | 40 | Jumlah koneksi slow HTTP bersamaan |
| `RATE` | 5 | Koneksi baru per detik |
| `DURATION` | 75 | Durasi setiap fase dalam detik |
| `BODY_SIZE` | 8192 | Ukuran body POST yang diklaim (bytes) |

---

## Tools Yang Dipakai

- Akuisisi: Wireshark `dumpcap`
- Validasi: Snort IDS
- Traffic generator fase 1: slowhttptest mode `-B` (Slow Body POST)
- Traffic generator fase 2: slowhttptest mode `-R` (Slow Read)

---

## Apa Yang Dihasilkan di PCAP

| Fase | Karakteristik traffic di PCAP |
| --- | --- |
| Slow Body POST | Banyak koneksi TCP port 80 tetap terbuka lama, data POST masuk sangat pelan |
| Slow Read | Koneksi TCP terbuka, TCP Window kecil, server menunggu client baca respons |

Ciri khas yang berbeda dari Slowloris: ada paket POST (bukan hanya GET/HEAD), dan `Content-Length` header ada di request.

---

## Upload Ke Website

1. Buat experiment baru, pilih tool profile **Torshammer**, isi IP dan parameter.
2. Upload `torshammer-wireshark.pcapng` sebagai data akuisisi.
3. Upload `torshammer-snort.log` sebagai data validasi.
4. Pilih pasangan akuisisi yang sama saat upload validasi.
5. Jalankan analisis dan AI validation.

---

## Catatan

- Capture filter otomatis: `tcp port 80` — sama dengan slow-http karena keduanya berbasis HTTP port 80.
- Mode Slow Body dan Slow Read keduanya menargetkan connection exhaustion, tetapi lewat mekanisme berbeda yang terlihat jelas di PCAP.
- Jika Nginx dikonfigurasi dengan `client_body_timeout` pendek, Nginx akan drop koneksi lebih cepat — eksperimen tetap valid untuk dianalisis.
