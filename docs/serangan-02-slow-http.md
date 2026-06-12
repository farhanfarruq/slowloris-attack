# Serangan 02 - Slow HTTP Dengan Wireshark + Snort

Skenario ini mensimulasikan koneksi HTTP lambat ke Nginx target. Ini skenario utama untuk deteksi Slowloris-like traffic.

## Output Wajib

```text
storage/app/vm-lab-captures/slow-http-wireshark.pcapng
storage/app/vm-lab-captures/slow-http-snort.log
```

## Jalankan

```bash
cd "/home/farhan/Documents/VsCode Project/slowloris-attack"

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

## Tools Yang Dipakai

- Akuisisi: Wireshark `dumpcap`
- Validasi: Snort IDS
- Traffic generator: slowhttptest

## Upload Ke Website

1. Buat experiment `slow-http`.
2. Upload `slow-http-wireshark.pcapng` sebagai data akuisisi.
3. Upload `slow-http-snort.log` sebagai data validasi.
4. Pilih pasangan akuisisi yang sama saat upload validasi.
5. Jalankan analisis dan AI validation jika API key sudah siap.
