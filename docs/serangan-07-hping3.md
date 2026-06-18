# Serangan 07 - Hping3 (TCP SYN Flood / UDP Flood / ICMP Flood) Dengan Wireshark + Snort

Skenario ini mensimulasikan serangan transport-layer flood menggunakan hping3 — tool packet crafting yang bisa mengirim SYN flood, UDP flood, dan ICMP flood dengan random source IP. Hping3 beroperasi di layer 3/4 sehingga tidak menghasilkan HTTP traffic, berbeda dari LOIC/HOIC.

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

Tools yang dipasang: curl, ApacheBench, slowhttptest, iPerf3, Nmap, **hping3**.

> **Hping3 wajib ada** untuk semua mode serangan ini. Verifikasi: `hping3 --version`

### 3. Setup Sudo Lab di VM Target (sekali saja)

```bash
cd "/home/farhan/Documents/VsCode Project/slowloris-attack"
chmod +x scripts/vm-lab/*.sh

ATTACKER_SSH="attacker@192.168.56.102" \
TARGET_SSH="target@192.168.56.103" \
scripts/vm-lab/setup-remote-lab-sudo.sh
```

> **Catatan sudo**: hping3 memerlukan `sudo` karena memanipulasi raw socket. Script `setup-remote-lab-sudo.sh` sudah menyiapkan izin yang diperlukan di VM Attacker.

---

## Output Wajib

```text
storage/app/vm-lab-captures/hping3-wireshark.pcapng
storage/app/vm-lab-captures/hping3-snort.log
storage/app/vm-lab-captures/hping3-target-metrics.csv
```

---

## Jalankan — Preset PCAPNG Kecil

Gunakan preset ini sebagai default untuk upload dashboard. Preset ini tetap memakai `dumpcap` dari paket Wireshark, tetapi capture dibatasi agar PCAPNG tidak membengkak:

- durasi capture default 15 detik
- durasi hping3 default 8 detik
- snaplen 96 byte
- batas file capture 50 MB
- mode hping3 rate-limited, bukan `--flood`
- jumlah packet default 12.000
- source IP default fixed attacker IP agar rule Snort lokal bisa match
- output hping3 quiet agar terminal tidak mencetak ribuan reply packet
- capture filter dipersempit sesuai mode

TCP SYN rate-limited:

```bash
cd "/home/farhan/Documents/VsCode Project/slowloris-attack"

ATTACK_MODE="tcp_syn_flood" \
ATTACKER_SSH="attacker@192.168.56.102" \
TARGET_SSH="target@192.168.56.103" \
ATTACKER_IP="192.168.56.102" \
TARGET_IP="192.168.56.103" \
TARGET_IFACE="enp0s8" \
bash scripts/vm-lab/run-hping3-small-capture-from-host.sh
```

UDP rate-limited:

```bash
cd "/home/farhan/Documents/VsCode Project/slowloris-attack"

ATTACK_MODE="udp_flood" \
ATTACKER_SSH="attacker@192.168.56.102" \
TARGET_SSH="target@192.168.56.103" \
ATTACKER_IP="192.168.56.102" \
TARGET_IP="192.168.56.103" \
TARGET_IFACE="enp0s8" \
bash scripts/vm-lab/run-hping3-small-capture-from-host.sh
```

ICMP rate-limited:

```bash
cd "/home/farhan/Documents/VsCode Project/slowloris-attack"

ATTACK_MODE="icmp_flood" \
ATTACKER_SSH="attacker@192.168.56.102" \
TARGET_SSH="target@192.168.56.103" \
ATTACKER_IP="192.168.56.102" \
TARGET_IP="192.168.56.103" \
TARGET_IFACE="enp0s8" \
bash scripts/vm-lab/run-hping3-small-capture-from-host.sh
```

Jika alert Snort kurang, naikkan bertahap tanpa kembali ke full flood:

```bash
HPING3_COUNT=20000 \
CAPTURE_SECONDS=25 \
CAPTURE_FILESIZE_KB=102400 \
bash scripts/vm-lab/run-hping3-small-capture-from-host.sh
```

Catatan: untuk TCP SYN/UDP/ICMP, output seperti `100% packet loss` bisa normal karena target tidak wajib membalas paket raw hping3. Selama ada baris `packets transmitted`, script menganggap traffic sudah terkirim.

Output tetap:

```text
storage/app/vm-lab-captures/hping3-wireshark.pcapng
storage/app/vm-lab-captures/hping3-snort.log
storage/app/vm-lab-captures/hping3-target-metrics.csv
```

---

## Jalankan — Mode TCP SYN Flood Penuh

Mode ini adalah command lama. Masih bisa digunakan, tetapi tidak disarankan untuk upload dashboard karena memakai `--flood`, durasi 60 detik, dan bisa menghasilkan jutaan paket serta PCAPNG ratusan MB.

```bash
cd "/home/farhan/Documents/VsCode Project/slowloris-attack"

SCENARIO="hping3" \
ATTACK_MODE="tcp_syn_flood" \
ATTACKER_SSH="attacker@192.168.56.102" \
TARGET_SSH="target@192.168.56.103" \
ATTACKER_IP="192.168.56.102" \
TARGET_IP="192.168.56.103" \
TARGET_IFACE="enp0s8" \
CAPTURE_SECONDS=80 \
DURATION=60 \
PORTS="80" \
scripts/vm-lab/run-wireshark-snort-scenario-from-host.sh
```

## Jalankan — Mode UDP Flood Penuh

Mode ini adalah full flood UDP. Gunakan hanya bila benar-benar perlu bukti volume tinggi; untuk dashboard gunakan preset PCAPNG kecil di atas.

```bash
cd "/home/farhan/Documents/VsCode Project/slowloris-attack"

SCENARIO="hping3" \
ATTACK_MODE="udp_flood" \
ATTACKER_SSH="attacker@192.168.56.102" \
TARGET_SSH="target@192.168.56.103" \
ATTACKER_IP="192.168.56.102" \
TARGET_IP="192.168.56.103" \
TARGET_IFACE="enp0s8" \
CAPTURE_SECONDS=80 \
DURATION=60 \
PORTS="80" \
scripts/vm-lab/run-wireshark-snort-scenario-from-host.sh
```

## Jalankan — Mode ICMP Flood Penuh

Mode ini adalah full flood ICMP. Gunakan hanya bila benar-benar perlu bukti volume tinggi; untuk dashboard gunakan preset PCAPNG kecil di atas.

Mode ini mengirimkan ICMP echo (ping) flood secara masif ke target. Menggunakan `--icmp --flood` sehingga volume sangat tinggi.

```bash
cd "/home/farhan/Documents/VsCode Project/slowloris-attack"

SCENARIO="hping3" \
ATTACK_MODE="icmp_flood" \
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
- Traffic generator: hping3 (semua mode)

---

## Apa Yang Dihasilkan di PCAP

| Mode | Karakteristik traffic di PCAP |
| --- | --- |
| `tcp_syn_flood` | Banjir SYN packet, banyak SYN-RECV di `ss`, source IP random |
| `udp_flood` | Banjir UDP + ICMP Port Unreachable balasan dari target |
| `icmp_flood` | Banjir ICMP echo request, volume paket sangat tinggi |

Perbedaan kunci vs HTTP flood: **tidak ada HTTP layer** di PCAP. Seluruh traffic ada di layer 3/4 saja.

---

## Snort Alert Yang Diharapkan

Untuk TCP SYN flood, Snort dengan ruleset standar biasanya menghasilkan alert seperti:

```text
[**] [1:1000001:1] Possible SYN Flood [**]
[**] [1:469:3] ICMP PING [**]
```

Jika Snort tidak menghasilkan alert (log kosong), itu tetap hasil valid — upload file kosong sebagai bukti tidak ada deteksi rule.

---

## Upload Ke Website

1. Buat experiment baru, pilih tool profile **Hping3**, isi IP dan parameter.
2. Upload `hping3-wireshark.pcapng` sebagai data akuisisi.
3. Upload `hping3-snort.log` sebagai data validasi.
4. Pilih pasangan akuisisi yang sama saat upload validasi.
5. Jalankan analisis dan AI validation.

---

## Catatan

- Capture filter otomatis untuk hping3: `host 192.168.56.102` (semua traffic dari attacker IP, baik TCP/UDP/ICMP).
- Mode `--flood` pada hping3 mengirim paket secepat mungkin tanpa menunggu reply — hasilkan volume sangat tinggi bahkan di VM lokal.
- Di VM VirtualBox dengan Host-only Adapter, pastikan network interface di VM Target adalah `enp0s8` (bukan `eth0`). Verifikasi: `ip link show` di VM Target.
