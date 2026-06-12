#!/usr/bin/env bash
set -Eeuo pipefail

TARGET_IP="${TARGET_IP:-192.168.56.103}"
DURATION="${DURATION:-30}"
PARALLEL="${PARALLEL:-2}"
BANDWIDTH="${BANDWIDTH:-10M}"

log() {
  printf '[iperf-bandwidth] %s\n' "$*"
}

die() {
  printf '[iperf-bandwidth][ERROR] %s\n' "$*" >&2
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
  command -v iperf3 >/dev/null 2>&1 || die "Install dulu: sudo apt install -y iperf3"

  log "Bandwidth test lokal: duration=$DURATION parallel=$PARALLEL bandwidth=$BANDWIDTH"
  iperf3 -c "$TARGET_IP" -t "$DURATION" -P "$PARALLEL" -b "$BANDWIDTH" --json
}

main "$@"
