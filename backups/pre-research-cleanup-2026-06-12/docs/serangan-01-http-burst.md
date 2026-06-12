# Serangan 01 - HTTP Burst Dengan Wireshark + Snort

Skenario ini menghasilkan traffic HTTP cepat memakai ApacheBench. Ini bukan Slowloris, tetapi baseline HTTP yang tetap wajib punya dua bukti: akuisisi Wireshark dan validasi Snort.

## Output Wajib

```text
storage/app/vm-lab-captures/http-burst-wireshark.pcapng
storage/app/vm-lab-captures/http-burst-snort.log
```

## Jalankan

```bash
cd "/home/farhan/Documents/VsCode Project/slowloris-attack"

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

## Tools Yang Dipakai

- Akuisisi: Wireshark `dumpcap`
- Validasi: Snort IDS
- Traffic generator: ApacheBench

## Upload Ke Website

1. Buat experiment `http-burst`.
2. Upload `http-burst-wireshark.pcapng` sebagai data akuisisi.
3. Upload `http-burst-snort.log` sebagai data validasi.
4. Pilih pasangan akuisisi yang sama saat upload validasi.
5. Jalankan analisis.
