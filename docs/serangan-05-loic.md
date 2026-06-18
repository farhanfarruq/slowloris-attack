# Serangan 05 - LOIC (HTTP Flood / TCP Flood / UDP Flood) Dengan Wireshark + Snort

Skenario ini mensimulasikan LOIC (Low Orbit Ion Cannon) — tool DDoS klasik yang mengirimkan flood HTTP, TCP, atau UDP secara masif ke target. Traffic ini menghasilkan volume koneksi dan paket sangat tinggi dalam waktu singkat.

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

> **Hping3 wajib ada untuk mode `tcp_flood` dan `udp_flood`.** Mode `http_flood` hanya butuh `ab`.

### 3. Setup Sudo Lab di VM Target (sekali saja)

```bash
cd "/home/farhan/Documents/VsCode Project/slowloris-attack"
chmod +x scripts/vm-lab/*.sh

ATTACKER_SSH="attacker@192.168.56.102" \
TARGET_SSH="target@192.168.56.103" \
scripts/vm-lab/setup-remote-lab-sudo.sh
```

---

## Output Wajib

```text
storage/app/vm-lab-captures/loic-wireshark.pcapng
storage/app/vm-lab-captures/loic-snort.log
storage/app/vm-lab-captures/loic-target-metrics.csv
```

---

## Jalankan — Mode HTTP Flood (default)

Mode ini mengirim ribuan GET request HTTP ke port 80 memakai ApacheBench. Ini mode paling sering mewakili LOIC "HTTP mode".

```bash
cd "/home/farhan/Documents/VsCode Project/slowloris-attack"

SCENARIO="loic" \
ATTACK_MODE="http_flood" \
ATTACKER_SSH="attacker@192.168.56.102" \
TARGET_SSH="target@192.168.56.103" \
ATTACKER_IP="192.168.56.102" \
TARGET_IP="192.168.56.103" \
TARGET_IFACE="enp0s8" \
CAPTURE_SECONDS=60 \
REQUESTS=3000 \
CONCURRENCY=30 \
scripts/vm-lab/run-wireshark-snort-scenario-from-host.sh
```

## Jalankan — Mode TCP Flood

Mode ini mengirim SYN packet dengan random source IP ke port 80 menggunakan hping3. Mewakili LOIC "TCP mode".

```bash
cd "/home/farhan/Documents/VsCode Project/slowloris-attack"

SCENARIO="loic" \
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

## Jalankan — Mode UDP Flood

Mode ini mengirim paket UDP ke port 80 secara masif menggunakan hping3. Mewakili LOIC "UDP mode".

```bash
cd "/home/farhan/Documents/VsCode Project/slowloris-attack"

SCENARIO="loic" \
ATTACK_MODE="udp_flood" \
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

## Tools Yang Dipakai

- Akuisisi: Wireshark `dumpcap`
- Validasi: Snort IDS
- Traffic generator HTTP flood: ApacheBench (`ab`)
- Traffic generator TCP/UDP flood: hping3

---

## Apa Yang Dihasilkan di PCAP

| Mode | Karakteristik traffic |
| --- | --- |
| `http_flood` | Banyak GET ke port 80, koneksi cepat selesai, throughput tinggi |
| `tcp_flood` | Banjir SYN packet, banyak half-open connection di target |
| `udp_flood` | Banjir UDP ke port 80, volume paket sangat tinggi |

---

## Upload Ke Website

1. Buat experiment baru, pilih tool profile **LOIC**, isi IP dan parameter.
2. Upload `loic-wireshark.pcapng` sebagai data akuisisi.
3. Upload `loic-snort.log` sebagai data validasi.
4. Pilih pasangan akuisisi yang sama saat upload validasi.
5. Jalankan analisis dan AI validation.

---

## Catatan

- Jika Snort tidak membuat alert, file `loic-snort.log` akan kosong — itu hasil valid yang menunjukkan tidak ada rule Snort yang cocok.
- Untuk mode `tcp_flood` dan `udp_flood`, hping3 memerlukan `sudo`. Script sudah menangani ini via `setup-remote-lab-sudo.sh`.
- Capture filter otomatis: `host 192.168.56.102 and tcp port 80` (HTTP flood) atau `host 192.168.56.102` (TCP/UDP/ICMP flood via hping3 mode).
