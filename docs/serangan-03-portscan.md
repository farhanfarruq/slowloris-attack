# Serangan 03 - Port Scan Dengan Wireshark + Snort

Skenario ini menjalankan TCP connect scan terbatas dari VM Attacker ke VM Target. Ini pembanding non-Slowloris yang tetap diawasi oleh Wireshark dan Snort.

## Output Wajib

```text
storage/app/vm-lab-captures/portscan-wireshark.pcapng
storage/app/vm-lab-captures/portscan-snort.log
```

## Jalankan

```bash
cd "/home/farhan/Documents/VsCode Project/slowloris-attack"

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

## Tools Yang Dipakai

- Akuisisi: Wireshark `dumpcap`
- Validasi: Snort IDS
- Traffic generator: Nmap

## Upload Ke Website

1. Buat experiment `portscan`.
2. Upload `portscan-wireshark.pcapng` sebagai data akuisisi.
3. Upload `portscan-snort.log` sebagai data validasi.
4. Pilih pasangan akuisisi yang sama saat upload validasi.
5. Jalankan analisis.
