# Serangan 09 - Xerxes (High-Rate Connection + HTTP Flood) Dengan Wireshark + Snort

Skenario ini mensimulasikan Xerxes — tool DDoS yang menggabungkan HTTP flood volume tinggi dengan banyak koneksi persisten (keep-alive). Lebih agresif dari LOIC karena Xerxes mempertahankan koneksi lebih lama sambil terus mengirim request baru, menghabiskan file descriptor dan connection pool server secara bersamaan.

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

Tools yang dipasang: curl, ApacheBench (`ab`), slowhttptest, iPerf3, Nmap, **hping3**.

> Mode `tcp_flood` memerlukan hping3. Mode `http_flood` hanya butuh `ab` dan `curl`.

### 3. Setup Sudo Lab di VM Target (sekali saja)

```bash
cd "/home/farhan/Documents/VsCode Project/slowloris-attack"
chmod +x scripts/vm-lab/*.sh

ATTACKER_SSH="attacker@192.168.56.102" \
TARGET_SSH="target@192.168.56.103" \
scripts/vm-lab/setup-remote-lab-sudo.sh
```

---

## Perbedaan Xerxes vs LOIC vs HOIC

| Aspek | LOIC HTTP | HOIC | Xerxes HTTP |
| --- | --- | --- | --- |
| Wave | Satu burst besar | Dua gelombang multi-path | Burst besar + sustained keep-alive |
| Keep-alive | Tidak | Partial | Ya, eksplisit |
| Multi-path | Tidak | Ya | Tidak (fokus `/`) |
| Agresivitas | Tinggi | Tinggi | Paling tinggi (file descriptor exhaustion) |

---

## Output Wajib

```text
storage/app/vm-lab-captures/xerxes-wireshark.pcapng
storage/app/vm-lab-captures/xerxes-snort.log
storage/app/vm-lab-captures/xerxes-target-metrics.csv
```

---

## Jalankan — Mode HTTP Flood (default, dua gelombang)

Script Xerxes menjalankan dua gelombang berurutan:
- **Gelombang 1**: `ab` burst ribuan request ke port 80 dengan header Xerxes custom.
- **Gelombang 2**: Banyak worker curl paralel yang loop kirim request dengan `Connection: keep-alive` selama `DURATION` detik.

```bash
cd "/home/farhan/Documents/VsCode Project/slowloris-attack"

SCENARIO="xerxes" \
ATTACK_MODE="http_flood" \
ATTACKER_SSH="attacker@192.168.56.102" \
TARGET_SSH="target@192.168.56.103" \
ATTACKER_IP="192.168.56.102" \
TARGET_IP="192.168.56.103" \
TARGET_IFACE="enp0s8" \
CAPTURE_SECONDS=120 \
REQUESTS=8000 \
CONCURRENCY=80 \
DURATION=60 \
scripts/vm-lab/run-wireshark-snort-scenario-from-host.sh
```

## Jalankan — Mode TCP Flood

Mode ini menggunakan hping3 untuk mengirim paket TCP (SYN+ACK) dengan flood rate tinggi ke port 80.

```bash
cd "/home/farhan/Documents/VsCode Project/slowloris-attack"

SCENARIO="xerxes" \
ATTACK_MODE="tcp_flood" \
ATTACKER_SSH="attacker@192.168.56.102" \
TARGET_SSH="target@192.168.56.103" \
ATTACKER_IP="192.168.56.102" \
TARGET_IP="192.168.56.103" \
TARGET_IFACE="enp0s8" \
CAPTURE_SECONDS=80 \
DURATION=60 \
scripts/vm-lab/run-wireshark-snort-scenario-from-host.sh
```

---

## Parameter Penting

| Variabel | Default | Penjelasan |
| --- | --- | --- |
| `REQUESTS` | 8000 | Total request ab di gelombang 1 |
| `CONCURRENCY` | 80 | Jumlah koneksi paralel ab (gelombang 1) dan worker curl (gelombang 2) |
| `DURATION` | 60 | Durasi gelombang 2 (sustained curl loop) dalam detik |

> **Hitung `CAPTURE_SECONDS`**: gelombang 1 (ab) dan gelombang 2 (curl) berjalan berurutan. Gunakan: `CAPTURE_SECONDS = estimasi_durasi_ab + DURATION + 20`. Untuk default: sekitar 30 + 60 + 20 = 110, dibulatkan ke 120.

---

## Tools Yang Dipakai

- Akuisisi: Wireshark `dumpcap`
- Validasi: Snort IDS
- Traffic generator gelombang 1: ApacheBench (`ab`)
- Traffic generator gelombang 2: curl dengan keep-alive (banyak worker paralel)
- Traffic generator TCP flood: hping3

---

## Apa Yang Dihasilkan di PCAP

| Mode | Karakteristik traffic di PCAP |
| --- | --- |
| `http_flood` | Burst GET ke `/` disusul sustained keep-alive GET, banyak koneksi ESTABLISHED ke port 80 |
| `tcp_flood` | Banjir TCP SYN+ACK ke port 80, volume paket sangat tinggi |

Ciri yang membedakan Xerxes dari LOIC di PCAP: banyak koneksi TCP yang tetap ESTABLISHED lama (keep-alive) setelah gelombang burst pertama selesai.

---

## Upload Ke Website

1. Buat experiment baru, pilih tool profile **Xerxes**, isi IP dan parameter.
2. Upload `xerxes-wireshark.pcapng` sebagai data akuisisi.
3. Upload `xerxes-snort.log` sebagai data validasi.
4. Pilih pasangan akuisisi yang sama saat upload validasi.
5. Jalankan analisis dan AI validation.

---

## Catatan

- Capture filter otomatis: `host 192.168.56.102 and tcp port 80` (HTTP flood) atau `host 192.168.56.102 and tcp port 80` (TCP flood).
- Nilai `CONCURRENCY=80` cukup agresif untuk VM. Jika VM Attacker kehabisan file descriptor, kurangi ke 40–50.
- Mode HTTP flood gelombang dua menggunakan `$CONCURRENCY` worker curl paralel — pastikan VM Attacker punya cukup file descriptor (`ulimit -n 65535` jika perlu).
