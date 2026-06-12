# Serangan 04 - iPerf Bandwidth Dengan Wireshark + Snort

Skenario ini menjalankan traffic bandwidth iPerf3 sebagai baseline throughput tinggi. Ini bukan Slowloris, tetapi tetap memakai akuisisi Wireshark dan validasi Snort.

## Output Wajib

```text
storage/app/vm-lab-captures/iperf-bandwidth-wireshark.pcapng
storage/app/vm-lab-captures/iperf-bandwidth-snort.log
```

## Jalankan

```bash
cd "/home/farhan/Documents/VsCode Project/slowloris-attack"

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

## Tools Yang Dipakai

- Akuisisi: Wireshark `dumpcap`
- Validasi: Snort IDS
- Traffic generator: iPerf3

## Upload Ke Website

1. Buat experiment `iperf-bandwidth`.
2. Upload `iperf-bandwidth-wireshark.pcapng` sebagai data akuisisi.
3. Upload `iperf-bandwidth-snort.log` sebagai data validasi.
4. Pilih pasangan akuisisi yang sama saat upload validasi.
5. Jalankan analisis.
