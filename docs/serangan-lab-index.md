# Index Dokumentasi Serangan VM Lab

Dokumen ini untuk lab lokal milik sendiri. Semua skenario memakai Host-only Network `192.168.56.x`. Jangan jalankan script ke IP publik, domain publik, jaringan kantor, kampus, atau sistem orang lain.

## Topologi Standar

| Node | Contoh IP | Fungsi |
| --- | --- | --- |
| Host Ubuntu | `192.168.56.1` | Menjalankan dashboard dan script orchestrator |
| VM Attacker | `192.168.56.102` | Menjalankan traffic simulasi |
| VM Target | `192.168.56.103` | Nginx, OpenSSH, Wireshark/dumpcap, Snort, iPerf3 server |

## Dokumen Skenario

Semua skenario wajib menghasilkan dua file: `*-wireshark.pcapng` untuk akuisisi dan `*-snort.log` untuk validasi.

| Dokumen | Skenario | Output wajib |
| --- | --- | --- |
| `docs/serangan-01-http-burst.md` | HTTP burst ringan dengan ApacheBench | `http-burst-wireshark.pcapng` + `http-burst-snort.log` |
| `docs/serangan-02-slow-http.md` | Slow HTTP headers dengan slowhttptest | `slow-http-wireshark.pcapng` + `slow-http-snort.log` |
| `docs/serangan-03-portscan.md` | TCP connect port scan dengan Nmap | `portscan-wireshark.pcapng` + `portscan-snort.log` |
| `docs/serangan-04-iperf-bandwidth.md` | Bandwidth pressure dengan iPerf3 | `iperf-bandwidth-wireshark.pcapng` + `iperf-bandwidth-snort.log` |
| `docs/lab-wireshark-snort-shell.md` | Runner shell Wireshark/dumpcap + Snort | `storage/app/vm-lab-captures/*-wireshark.pcapng` dan `*-snort.log` |
| `docs/manual-experiment-upload-validation.md` | Flow manual website: experiment, upload PCAP, upload Snort, pairing, analisis | Web dashboard |

## Setup Umum Sekali Saja

Di VM Target:

```bash
scp scripts/vm-lab/install-vm-tools.sh target@192.168.56.103:/tmp/
ssh -tt target@192.168.56.103 "chmod +x /tmp/install-vm-tools.sh && ROLE=target /tmp/install-vm-tools.sh"
```

Di VM Attacker:

```bash
scp scripts/vm-lab/install-vm-tools.sh attacker@192.168.56.102:/tmp/
ssh -tt attacker@192.168.56.102 "chmod +x /tmp/install-vm-tools.sh && ROLE=attacker /tmp/install-vm-tools.sh"
```

Di host Ubuntu, dari folder project:

```bash
cd "/home/farhan/Documents/VsCode Project/slowloris-attack"
chmod +x scripts/vm-lab/*.sh
```

Setup sudo lab satu kali:

```bash
ATTACKER_SSH="attacker@192.168.56.102" \
TARGET_SSH="target@192.168.56.103" \
scripts/vm-lab/setup-remote-lab-sudo.sh
```

## Menjalankan Skenario

Format umum:

```bash
SCENARIO="<nama-skenario>" \
ATTACKER_SSH="attacker@192.168.56.102" \
TARGET_SSH="target@192.168.56.103" \
ATTACKER_IP="192.168.56.102" \
TARGET_IP="192.168.56.103" \
CAPTURE_SECONDS=90 \
scripts/vm-lab/run-wireshark-snort-scenario-from-host.sh
```

Nama skenario:

```text
http-burst
slow-http
portscan
iperf-bandwidth
```

## Import ke Dashboard

Setelah script selesai, upload pasangan file dari:

```text
storage/app/vm-lab-captures/<scenario>-wireshark.pcapng
storage/app/vm-lab-captures/<scenario>-snort.log
```

Gunakan menu upload akuisisi di dashboard, lalu upload file Snort di menu validasi dan pilih pasangan file akuisisinya. Detail flow manual ada di `docs/manual-experiment-upload-validation.md`.
