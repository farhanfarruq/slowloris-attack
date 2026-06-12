#!/usr/bin/env bash
set -Eeuo pipefail

TARGET_IP="${TARGET_IP:-192.168.56.103}"
PORTS="${PORTS:-22,80,443,5201,8000}"

log() {
  printf '[portscan] %s\n' "$*"
}

die() {
  printf '[portscan][ERROR] %s\n' "$*" >&2
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
  command -v nmap >/dev/null 2>&1 || die "Install dulu: sudo apt install -y nmap"

  log "TCP connect scan lokal, rate rendah."
  nmap -sT -Pn -n --max-rate 20 -p "$PORTS" "$TARGET_IP"
}

main "$@"
