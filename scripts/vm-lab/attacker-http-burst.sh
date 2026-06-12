#!/usr/bin/env bash
set -Eeuo pipefail

TARGET_IP="${TARGET_IP:-192.168.56.103}"
REQUESTS="${REQUESTS:-500}"
CONCURRENCY="${CONCURRENCY:-20}"

log() {
  printf '[http-burst] %s\n' "$*"
}

die() {
  printf '[http-burst][ERROR] %s\n' "$*" >&2
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
  command -v ab >/dev/null 2>&1 || die "Install dulu: sudo apt install -y apache2-utils"
  command -v curl >/dev/null 2>&1 || die "Install dulu: sudo apt install -y curl"

  log "Cek target Nginx"
  curl -fsSI --max-time 5 "http://$TARGET_IP/"

  log "HTTP burst lokal: requests=$REQUESTS concurrency=$CONCURRENCY"
  ab -n "$REQUESTS" -c "$CONCURRENCY" "http://$TARGET_IP/"
}

main "$@"
