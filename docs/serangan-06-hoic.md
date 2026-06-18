# Serangan 06 - HOIC (High-Rate HTTP Flood Multi-Path) Dengan Wireshark + Snort

Skenario ini mensimulasikan HOIC (High Orbit Ion Cannon) — DDoS tool yang mengirim HTTP flood volume sangat tinggi ke berbagai path target secara bersamaan, dengan user-agent dan header yang bervariasi. Lebih agresif dari LOIC HTTP mode karena menyebar ke banyak endpoint.

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

Tools yang dipasang: curl, ApacheBench (`ab`), slowhttptest, iPerf3, Nmap, hping3.

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
storage/app/vm-lab-captures/hoic-wireshark.pcapng
storage/app/vm-lab-captures/hoic-snort.log
storage/app/vm-lab-captures/hoic-target-metrics.csv
```

---

## Jalankan — Mode HTTP Flood (default, dua gelombang)

Script HOIC menjalankan dua gelombang:
- **Gelombang 1**: ApacheBench (`ab`) kirim ribuan request sekaligus dengan custom User-Agent HOIC.
- **Gelombang 2**: Banyak worker curl paralel yang mempertahankan koneksi keep-alive ke berbagai path (`/`, `/login`, `/search?q=...`, dll.) selama `DURATION` detik.

```bash
cd "/home/farhan/Documents/VsCode Project/slowloris-attack"

SCENARIO="hoic" \
ATTACK_MODE="http_flood" \
ATTACKER_SSH="attacker@192.168.56.102" \
TARGET_SSH="target@192.168.56.103" \
ATTACKER_IP="192.168.56.102" \
TARGET_IP="192.168.56.103" \
TARGET_IFACE="enp0s8" \
CAPTURE_SECONDS=110 \
REQUESTS=5000 \
CONCURRENCY=50 \
DURATION=60 \
scripts/vm-lab/run-wireshark-snort-scenario-from-host.sh
```

## Jalankan — Mode Mixed (HTTP flood + curl sustained)

Mode `mixed` fokus pada gelombang kedua yang lebih panjang — banyak worker curl paralel terus-menerus hit berbagai path selama `DURATION` detik, ditambah ab burst di atasnya.

```bash
cd "/home/farhan/Documents/VsCode Project/slowloris-attack"

SCENARIO="hoic" \
ATTACK_MODE="mixed" \
ATTACKER_SSH="attacker@192.168.56.102" \
TARGET_SSH="target@192.168.56.103" \
ATTACKER_IP="192.168.56.102" \
TARGET_IP="192.168.56.103" \
TARGET_IFACE="enp0s8" \
CAPTURE_SECONDS=130 \
REQUESTS=5000 \
CONCURRENCY=50 \
DURATION=90 \
scripts/vm-lab/run-wireshark-snort-scenario-from-host.sh
```

---

## Tools Yang Dipakai

- Akuisisi: Wireshark `dumpcap`
- Validasi: Snort IDS
- Traffic generator gelombang 1: ApacheBench (`ab`)
- Traffic generator gelombang 2: curl (banyak worker paralel)

---

## Apa Yang Dihasilkan di PCAP

| Mode | Karakteristik traffic |
| --- | --- |
| `http_flood` | Burst besar GET ke port 80, lalu sustained multi-path ke banyak endpoint berbeda |
| `mixed` | Sustained flood ke banyak path, volume koneksi tinggi, UA bervariasi per worker |

Perbedaan dengan LOIC: HOIC menyebar ke banyak path/URL sehingga request count per path lebih merata (lebih sulit diblok dengan single-path rate limiting).

---

## Upload Ke Website

1. Buat experiment baru, pilih tool profile **HOIC**, isi IP dan parameter.
2. Upload `hoic-wireshark.pcapng` sebagai data akuisisi.
3. Upload `hoic-snort.log` sebagai data validasi.
4. Pilih pasangan akuisisi yang sama saat upload validasi.
5. Jalankan analisis dan AI validation.

---

## Catatan

- `CONCURRENCY` menentukan jumlah worker curl paralel di gelombang 2 — nilai 50 sudah cukup agresif untuk lab VM.
- `CAPTURE_SECONDS` harus lebih besar dari `DURATION` karena ada dua gelombang berurutan. Aturan aman: `CAPTURE_SECONDS = DURATION + 40`.
- Capture filter otomatis: `host 192.168.56.102 and tcp port 80`.
