#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd -- "$SCRIPT_DIR/../.." && pwd)"

usage() {
  cat <<'USAGE'
Usage:
  scripts/esp32-lab/capture-and-validate.sh EXP-001 [duration-seconds] [options]

Defensive ESP32 lab capture from the laptop only. This script records acquisition
and validation artifacts, but does not generate attack traffic.

Options:
  --target-host=IP        ESP32 IPv4 address, default: TARGET_HOST or 192.168.4.1
  --target-port=PORT     ESP32 HTTP port, default: TARGET_PORT or 80
  --interface=IFACE      Laptop capture interface, default: CAPTURE_INTERFACE or wlp8s0
  --import-mode=MODE     auto, host, docker, or skip. default: auto
  -h, --help             Show this help

Examples:
  scripts/esp32-lab/capture-and-validate.sh EXP-001 60
  scripts/esp32-lab/capture-and-validate.sh EXP-002 120 --target-host=192.168.4.1 --interface=wlp8s0
  scripts/esp32-lab/capture-and-validate.sh EXP-003 90 --import-mode=skip

Artifacts:
  captures/experiment/<EXP>.pcapng
  logs/snort/experiment/<EXP>.log
  logs/snort/experiment/<EXP>/alert_fast.txt
  metadata/experiment/<EXP>.json
USAGE
}

die() {
  printf 'ERROR: %s\n' "$*" >&2
  exit 1
}

require_command() {
  command -v "$1" >/dev/null 2>&1 || die "$1 tidak tersedia di PATH."
}

if [[ "${1:-}" == "-h" || "${1:-}" == "--help" ]]; then
  usage
  exit 0
fi

EXPERIMENT_CODE="${1:-}"
[[ -n "$EXPERIMENT_CODE" ]] || { usage >&2; exit 2; }
shift || true

DURATION="60"
if [[ "${1:-}" =~ ^[0-9]+$ ]]; then
  DURATION="$1"
  shift || true
fi

TARGET_HOST_VALUE="${TARGET_HOST:-192.168.4.1}"
TARGET_PORT_VALUE="${TARGET_PORT:-80}"
CAPTURE_INTERFACE_VALUE="${CAPTURE_INTERFACE:-wlp8s0}"
IMPORT_MODE="${IMPORT_MODE:-auto}"

while (($#)); do
  case "$1" in
    --target-host=*) TARGET_HOST_VALUE="${1#*=}" ;;
    --target-port=*) TARGET_PORT_VALUE="${1#*=}" ;;
    --interface=*) CAPTURE_INTERFACE_VALUE="${1#*=}" ;;
    --import-mode=*) IMPORT_MODE="${1#*=}" ;;
    -h|--help) usage; exit 0 ;;
    *) die "Argumen tidak dikenal: $1" ;;
  esac
  shift
done

[[ "$EXPERIMENT_CODE" =~ ^EXP-[0-9]{3,}$ ]] || die 'Kode eksperimen harus memakai format EXP-001.'
[[ "$DURATION" =~ ^[0-9]+$ ]] || die 'Durasi harus angka dalam satuan detik.'
(( DURATION >= 1 && DURATION <= 86400 )) || die 'Durasi harus 1-86400 detik.'
[[ "$TARGET_HOST_VALUE" =~ ^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$ ]] || die 'Target host harus IPv4 ESP32 lab.'
[[ "$TARGET_PORT_VALUE" =~ ^[0-9]+$ ]] || die 'Target port harus angka.'
(( TARGET_PORT_VALUE >= 1 && TARGET_PORT_VALUE <= 65535 )) || die 'Target port harus 1-65535.'
[[ "$CAPTURE_INTERFACE_VALUE" =~ ^[A-Za-z0-9_.:-]+$ ]] || die 'Nama interface capture tidak valid.'
[[ "$IMPORT_MODE" =~ ^(auto|host|docker|skip)$ ]] || die 'Import mode harus auto, host, docker, atau skip.'

cd "$PROJECT_ROOT"

require_command php

if [[ -f bootstrap/cache/config.php ]]; then
  die 'Laravel config cache aktif. Jalankan `php artisan config:clear` atau set nilai ESP32 di .env sebelum memakai wrapper ini.'
fi

printf 'Mode: defensive capture/validation only. Script ini TIDAK menjalankan serangan.\n'
printf 'Target ESP32       : %s:%s\n' "$TARGET_HOST_VALUE" "$TARGET_PORT_VALUE"
printf 'Capture interface : %s\n' "$CAPTURE_INTERFACE_VALUE"
printf 'Durasi capture    : %s detik\n' "$DURATION"
printf 'Kode eksperimen   : %s\n' "$EXPERIMENT_CODE"
printf '\nPastikan laptop tersambung ke jaringan lab ESP32 dan target adalah perangkat milik sendiri.\n'
printf 'Jika ada workflow uji terotorisasi, jalankan manual di terminal lain selama window capture ini.\n\n'

export TARGET_TYPE='esp32'
export TARGET_SCHEME='http'
export TARGET_HOST="$TARGET_HOST_VALUE"
export TARGET_PORT="$TARGET_PORT_VALUE"
export ESP32_ALLOWED_HOSTS="$TARGET_HOST_VALUE"
export CAPTURE_INTERFACE="$CAPTURE_INTERFACE_VALUE"
export PCAP_CAPTURE_FILTER="host ${TARGET_HOST_VALUE} and tcp port ${TARGET_PORT_VALUE}"

php artisan esp32:readiness
php artisan esp32:capture "$EXPERIMENT_CODE" "--duration=$DURATION"

METADATA_PATH="metadata/experiment/${EXPERIMENT_CODE}.json"

run_host_import() {
  php artisan esp32:import "$METADATA_PATH"
}

run_docker_import() {
  require_command docker
  docker compose exec -T app php artisan esp32:import "$METADATA_PATH"
}

case "$IMPORT_MODE" in
  skip)
    printf '\nImport dilewati sesuai --import-mode=skip.\n'
    ;;
  host)
    run_host_import
    ;;
  docker)
    run_docker_import
    ;;
  auto)
    if run_host_import; then
      :
    else
      printf '\nImport host gagal; mencoba import via Docker Compose service `app`.\n' >&2
      run_docker_import
    fi
    ;;
esac

printf '\nSelesai. Artefak laptop:\n'
printf '  Akuisisi PCAPNG : captures/experiment/%s.pcapng\n' "$EXPERIMENT_CODE"
printf '  Log Snort       : logs/snort/experiment/%s.log\n' "$EXPERIMENT_CODE"
printf '  Alert Snort     : logs/snort/experiment/%s/alert_fast.txt\n' "$EXPERIMENT_CODE"
printf '  Metadata        : metadata/experiment/%s.json\n' "$EXPERIMENT_CODE"
