# Lab Shell Wireshark + Snort

Dokumen ini menjalankan lab lokal dengan Wireshark capture engine (`dumpcap`) dan Snort. Semua command dibatasi untuk Host-only Network `192.168.56.x`.

Jangan gunakan script ini ke IP publik, domain publik, jaringan kantor/kampus, atau sistem orang lain.

## Jawaban Singkat: Wireshark Bisa?

Bisa, tetapi ada dua mode:

| Kebutuhan | Tool |
| --- | --- |
| Capture otomatis dari shell | `dumpcap` |
| Buka dan inspeksi file secara visual | Wireshark GUI |
| Deteksi alert IDS | Snort |

`dumpcap` adalah engine capture resmi dari paket Wireshark. Jadi untuk script otomatis, gunakan `dumpcap`. Setelah file `.pcapng` jadi, buka dengan Wireshark GUI.

## Topologi

| Node | IP | Fungsi |
| --- | --- | --- |
| Host Ubuntu | `192.168.56.1` | Menjalankan dashboard dan runner shell |
| VM Attacker | `192.168.56.102` | Menjalankan traffic skenario |
| VM Target | `192.168.56.103` | Nginx, Wireshark/dumpcap, Snort, iPerf3 server |

Interface Host-only di VM biasanya:

```text
enp0s8
```

Cek di masing-masing VM:

```bash
ip -br addr
```

## File Yang Dihasilkan

Runner Wireshark + Snort menghasilkan dua file utama:

```text
storage/app/vm-lab-captures/<scenario>-wireshark.pcapng
storage/app/vm-lab-captures/<scenario>-snort.log
```

Upload ke dashboard:

| File | Menu |
| --- | --- |
| `*-wireshark.pcapng` | Upload Data Akuisisi |
| `*-snort.log` | Upload Data Validasi Snort |

## 1. Install Tools Di VM

Jalankan dari host Ubuntu di folder project:

```bash
cd "/home/farhan/Documents/VsCode Project/slowloris-attack"
chmod +x scripts/vm-lab/*.sh
```

Copy installer ke Target:

```bash
scp scripts/vm-lab/install-vm-tools.sh target@192.168.56.103:/tmp/
ssh -tt target@192.168.56.103 "chmod +x /tmp/install-vm-tools.sh && ROLE=target /tmp/install-vm-tools.sh"
```

Copy installer ke Attacker:

```bash
scp scripts/vm-lab/install-vm-tools.sh attacker@192.168.56.102:/tmp/
ssh -tt attacker@192.168.56.102 "chmod +x /tmp/install-vm-tools.sh && ROLE=attacker /tmp/install-vm-tools.sh"
```

Target akan memasang:

```text
openssh-server
nginx
wireshark
wireshark-common
snort
iperf3
curl
net-tools
iproute2
```

Attacker akan memasang:

```text
openssh-server
curl
apache2-utils
slowhttptest
iperf3
nmap
net-tools
iproute2
```

Jika installer menambahkan user ke group `wireshark`, logout/login ulang VM Target agar Wireshark GUI bisa capture tanpa root.

## 2. Setup Sudo Lab Dari Host

Runner butuh sudo terbatas untuk `dumpcap`, `snort`, `systemctl`, dan operasi file capture.

```bash
ATTACKER_SSH="attacker@192.168.56.102" \
TARGET_SSH="target@192.168.56.103" \
scripts/vm-lab/setup-remote-lab-sudo.sh
```

Masukkan password VM saat diminta.

## 3. Jalankan Skenario Slow HTTP Dengan Wireshark + Snort

Ini skenario paling relevan untuk Slowloris-like traffic.

```bash
SCENARIO="slow-http" \
ATTACKER_SSH="attacker@192.168.56.102" \
TARGET_SSH="target@192.168.56.103" \
ATTACKER_IP="192.168.56.102" \
TARGET_IP="192.168.56.103" \
TARGET_IFACE="enp0s8" \
CAPTURE_SECONDS=100 \
CONNECTIONS=80 \
RATE=10 \
DURATION=75 \
scripts/vm-lab/run-wireshark-snort-scenario-from-host.sh
```

Output:

```text
storage/app/vm-lab-captures/slow-http-wireshark.pcapng
storage/app/vm-lab-captures/slow-http-snort.log
```

## 4. Jalankan Skenario Pembanding

HTTP burst cepat:

```bash
SCENARIO="http-burst" \
ATTACKER_SSH="attacker@192.168.56.102" \
TARGET_SSH="target@192.168.56.103" \
ATTACKER_IP="192.168.56.102" \
TARGET_IP="192.168.56.103" \
TARGET_IFACE="enp0s8" \
CAPTURE_SECONDS=45 \
REQUESTS=3000 \
CONCURRENCY=30 \
scripts/vm-lab/run-wireshark-snort-scenario-from-host.sh
```

Port scan lokal:

```bash
SCENARIO="portscan" \
ATTACKER_SSH="attacker@192.168.56.102" \
TARGET_SSH="target@192.168.56.103" \
ATTACKER_IP="192.168.56.102" \
TARGET_IP="192.168.56.103" \
TARGET_IFACE="enp0s8" \
CAPTURE_SECONDS=45 \
PORTS="22,80,443,5201,8000" \
scripts/vm-lab/run-wireshark-snort-scenario-from-host.sh
```

iPerf3 bandwidth baseline:

```bash
SCENARIO="iperf-bandwidth" \
ATTACKER_SSH="attacker@192.168.56.102" \
TARGET_SSH="target@192.168.56.103" \
ATTACKER_IP="192.168.56.102" \
TARGET_IP="192.168.56.103" \
TARGET_IFACE="enp0s8" \
CAPTURE_SECONDS=45 \
BANDWIDTH="10M" \
DURATION=30 \
scripts/vm-lab/run-wireshark-snort-scenario-from-host.sh
```

## 5. Buka File Dengan Wireshark GUI

Dari host Ubuntu:

```bash
wireshark storage/app/vm-lab-captures/slow-http-wireshark.pcapng
```

Display filter yang berguna:

```text
ip.addr == 192.168.56.102 && ip.addr == 192.168.56.103
tcp.port == 80
http
tcp.analysis.retransmission
tcp.flags.syn == 1
```

Untuk Slowloris-like traffic, perhatikan:

```text
- banyak koneksi dari attacker ke port 80
- koneksi bertahan lebih lama
- request/header tidak selesai cepat
- throughput relatif rendah
- Snort alert muncul di waktu yang sama
```

## 6. Upload Ke Dashboard

Akuisisi:

```text
storage/app/vm-lab-captures/slow-http-wireshark.pcapng
```

Validasi:

```text
storage/app/vm-lab-captures/slow-http-snort.log
```

Setelah upload, jalankan analisis eksperimen.

## 7. Troubleshooting

Jika `dumpcap tidak berjalan`:

```bash
ssh target@192.168.56.103 "command -v dumpcap && ip -br addr && tail -80 ~/slowloris-lab/logs/slow-http-dumpcap.log"
```

Jika Snort gagal:

```bash
ssh target@192.168.56.103 "sudo snort -T -q -c /etc/snort/snort.conf -i enp0s8"
```

Jika tidak ada alert Snort:

```text
- Pastikan attacker IP benar: 192.168.56.102
- Pastikan target IP benar: 192.168.56.103
- Pastikan traffic melewati enp0s8
- Naikkan CONNECTIONS atau DURATION untuk slow-http
```

Jika ingin capture manual pakai GUI:

```text
1. Buka Wireshark di Target.
2. Pilih interface enp0s8.
3. Capture filter: tcp port 80.
4. Start capture.
5. Jalankan skenario dari Attacker.
6. Stop capture.
7. Save As .pcapng.
```

Mode GUI ini bagus untuk belajar visual, tetapi untuk eksperimen berulang lebih rapi memakai runner shell `run-wireshark-snort-scenario-from-host.sh`.


UNTUK TARGET
USERNAME : target
password : 221221

UNTUK ATTACKER 
password : 221221