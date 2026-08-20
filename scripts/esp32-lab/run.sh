#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
EXPERIMENT_CODE="${1:-}"
DURATION="${2:-60}"

if [[ ! "$EXPERIMENT_CODE" =~ ^EXP-[0-9]{3,}$ ]]; then
  printf 'Usage: %s EXP-001 [duration-seconds]\n' "$0" >&2
  exit 2
fi

if [[ ! "$DURATION" =~ ^[0-9]+$ ]] || (( DURATION < 1 || DURATION > 86400 )); then
  printf 'Duration must be an integer from 1 to 86400 seconds.\n' >&2
  exit 2
fi

cd "$REPO_ROOT"

printf 'Defensive capture only; this script never generates attack traffic.\n'
printf 'During the %s-second window, use only the existing authorized lab workflow in another terminal.\n' "$DURATION"

capture_command=(php artisan esp32:capture "$EXPERIMENT_CODE" "--duration=$DURATION")
"${capture_command[@]}"

metadata_path="metadata/experiment/$EXPERIMENT_CODE.json"
import_command=(docker compose exec -T app php artisan esp32:import "$metadata_path")
"${import_command[@]}"
