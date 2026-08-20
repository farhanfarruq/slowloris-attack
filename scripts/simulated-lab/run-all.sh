#!/usr/bin/env bash
set -Eeuo pipefail

# Defensive only: creates offline PCAPNG/log simulations; never opens a socket or sends traffic.
SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd -- "$SCRIPT_DIR/../.." && pwd)"
OUTPUT_DIR="${1:-$PROJECT_ROOT/storage/app/simulated-lab}"

if ! command -v php >/dev/null; then
  echo 'PHP tidak tersedia.' >&2
  exit 1
fi

if ! command -v tshark >/dev/null; then
  echo 'TShark tidak tersedia; verifikasi PCAPNG tidak dapat dijalankan.' >&2
  exit 1
fi

php "$SCRIPT_DIR/generate-artifacts.php" "$OUTPUT_DIR"

shopt -s nullglob
pcaps=("$OUTPUT_DIR"/*-acquisition.pcapng)
if (( ${#pcaps[@]} != 10 )); then
  echo "Artefak tidak lengkap: ditemukan ${#pcaps[@]} PCAPNG, seharusnya 10." >&2
  exit 1
fi

for pcap in "${pcaps[@]}"; do
  packets="$(tshark -r "$pcap" -T fields -e frame.number 2>/dev/null | wc -l | tr -d ' ')"
  if (( packets < 1 )); then
    echo "PCAPNG tidak dapat dibaca atau kosong: $(basename -- "$pcap")" >&2
    exit 1
  fi
  echo "OK $(basename -- "$pcap"): ${packets} paket"
done

echo "Selesai: pasangan PCAPNG dan log simulasi tersimpan di $OUTPUT_DIR"
