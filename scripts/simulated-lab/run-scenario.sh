#!/usr/bin/env bash
set -Eeuo pipefail

# Defensive only: produces one offline PCAPNG/log pair and never sends network traffic.
SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd -- "$SCRIPT_DIR/../.." && pwd)"
SCENARIO="${1:?Usage: run-scenario.sh <scenario> [output-directory]}"
OUTPUT_DIR="${2:-$PROJECT_ROOT/storage/app/simulated-lab/$SCENARIO}"

command -v php >/dev/null || { echo 'PHP tidak tersedia.' >&2; exit 1; }
command -v tshark >/dev/null || { echo 'TShark tidak tersedia.' >&2; exit 1; }

php "$SCRIPT_DIR/generate-artifacts.php" "$OUTPUT_DIR" "$SCENARIO"

PCAP="$OUTPUT_DIR/$SCENARIO-acquisition.pcapng"
LOG="$OUTPUT_DIR/$SCENARIO-validation.log"
PACKETS="$(tshark -r "$PCAP" -T fields -e frame.number 2>/dev/null | wc -l | tr -d ' ')"

if (( PACKETS < 1 )) || [[ ! -s "$LOG" ]]; then
  echo "Artefak tidak valid untuk $SCENARIO." >&2
  exit 1
fi

echo "OK $SCENARIO: $PACKETS paket"
echo "Akuisisi: $PCAP"
echo "Validasi: $LOG"
