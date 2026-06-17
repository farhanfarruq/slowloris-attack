#!/usr/bin/env bash
set -Eeuo pipefail

TARGET_IP="${TARGET_IP:-192.168.56.20}"
REQUESTS="${REQUESTS:-20}"
DELAY_SECONDS="${DELAY_SECONDS:-1}"

log() {
  printf '[baseline-normal] %s
' "$*"
}

die() {
  printf '[baseline-normal][ERROR] %s
' "$*" >&2
  exit 1
}

validate_lab_ip() {
  case "$1" in
    192.168.56.*) ;;
    10.*|172.*|127.*|169.254.*|0.*|255.*) die "IP '$1' bukan subnet lab 192.168.56.x. Script dihentikan." ;;
    *.*.*.*) die "IP '$1' tidak diizinkan. Gunakan subnet VM lokal 192.168.56.x saja." ;;
    *) die "IP '$1' tidak valid." ;;
  esac
}

main() {
  validate_lab_ip "$TARGET_IP"
  command -v curl >/dev/null 2>&1 || die "Install dulu: sudo apt install -y curl"

  log "Baseline HTTP lokal: requests=$REQUESTS delay=${DELAY_SECONDS}s"
  for i in $(seq 1 "$REQUESTS"); do
    curl -fsS --max-time 5 "http://$TARGET_IP/" >/dev/null
    sleep "$DELAY_SECONDS"
  done
}

main "$@"
