#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd -- "$SCRIPT_DIR/../.." && pwd)"
OUTPUT_DIR="${1:-$PROJECT_ROOT/storage/app/esp32-simulated-lab}"

export LAB_TOPOLOGY=esp32
exec "$PROJECT_ROOT/scripts/simulated-lab/run-all.sh" "$OUTPUT_DIR"
