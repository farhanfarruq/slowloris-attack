#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd -- "$SCRIPT_DIR/../.." && pwd)"
SCENARIO="${1:?Skenario simulasi wajib diisi.}"
OUTPUT_DIR="${2:-$PROJECT_ROOT/storage/app/esp32-simulated-lab/$SCENARIO}"

export LAB_TOPOLOGY=esp32
exec "$PROJECT_ROOT/scripts/simulated-lab/run-scenario.sh" "$SCENARIO" "$OUTPUT_DIR"
