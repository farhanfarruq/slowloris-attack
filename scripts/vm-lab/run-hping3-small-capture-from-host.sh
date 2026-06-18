#!/usr/bin/env bash
set -Eeuo pipefail

ATTACK_MODE="${ATTACK_MODE:-tcp_syn_flood}"   # tcp_syn_flood | udp_flood | icmp_flood
TARGET_IP="${TARGET_IP:-192.168.56.103}"
PORTS="${PORTS:-80}"

case "$ATTACK_MODE" in
  tcp_syn_flood)
    DEFAULT_FILTER="host $TARGET_IP and tcp port $PORTS"
    ;;
  udp_flood)
    DEFAULT_FILTER="dst host $TARGET_IP and udp dst port $PORTS"
    ;;
  icmp_flood)
    DEFAULT_FILTER="icmp and host $TARGET_IP"
    ;;
  *)
    printf '[hping3-small][ERROR] ATTACK_MODE tidak dikenal: %s\n' "$ATTACK_MODE" >&2
    exit 1
    ;;
esac

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"

SCENARIO="${SCENARIO:-hping3}" \
ATTACK_MODE="$ATTACK_MODE" \
TARGET_IP="$TARGET_IP" \
PORTS="$PORTS" \
CAPTURE_SECONDS="${CAPTURE_SECONDS:-15}" \
DURATION="${DURATION:-8}" \
HPING3_FLOOD="${HPING3_FLOOD:-0}" \
HPING3_COUNT="${HPING3_COUNT:-12000}" \
HPING3_INTERVAL="${HPING3_INTERVAL:-u1000}" \
HPING3_RAND_SOURCE="${HPING3_RAND_SOURCE:-0}" \
HPING3_QUIET="${HPING3_QUIET:-1}" \
CAPTURE_SNAPLEN="${CAPTURE_SNAPLEN:-96}" \
CAPTURE_FILESIZE_KB="${CAPTURE_FILESIZE_KB:-51200}" \
CAPTURE_FILTER="${CAPTURE_FILTER:-$DEFAULT_FILTER}" \
"$SCRIPT_DIR/run-wireshark-snort-scenario-from-host.sh"
