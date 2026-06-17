#!/usr/bin/env bash
set -Eeuo pipefail

ATTACKER_IP="${ATTACKER_IP:-192.168.56.102}"
TARGET_IP="${TARGET_IP:-192.168.56.103}"
TARGET_IFACE="${TARGET_IFACE:-enp0s8}"
TARGET_LAB_DIR="${TARGET_LAB_DIR:-$HOME/slowloris-lab}"
SNORT_CONF="${SNORT_CONF:-/etc/snort/snort.conf}"
SNORT_LOCAL_RULES="${SNORT_LOCAL_RULES:-/etc/snort/rules/local.rules}"

log() {
  printf '[target-prepare-wireshark-snort] %s\n' "$*"
}

die() {
  printf '[target-prepare-wireshark-snort][ERROR] %s\n' "$*" >&2
  exit 1
}

validate_lab_ip() {
  case "$1" in
    192.168.56.*) ;;
    *) die "IP '$1' bukan subnet lab 192.168.56.x. Script dihentikan." ;;
  esac
}

need_cmd() {
  command -v "$1" >/dev/null 2>&1 || die "Command '$1' belum ada. Jalankan install-vm-tools.sh di Target."
}

need_sudo() {
  sudo -n "$@" >/dev/null 2>&1 || die "Sudo non-interactive belum siap untuk '$*'. Jalankan ulang scripts/vm-lab/setup-remote-lab-sudo.sh dari host."
}

main() {
  validate_lab_ip "$ATTACKER_IP"
  validate_lab_ip "$TARGET_IP"
  need_cmd ip
  need_cmd dumpcap
  need_cmd snort
  need_sudo ip link show

  ip link show "$TARGET_IFACE" >/dev/null 2>&1 || die "Interface $TARGET_IFACE tidak ditemukan. Cek: ip -br addr"

  log "Siapkan folder lab"
  mkdir -p "$TARGET_LAB_DIR"/{captures,validation,logs,notes}

  log "Pastikan IP Target ada di $TARGET_IFACE"
  if ! ip -o -4 addr show dev "$TARGET_IFACE" | grep -q "$TARGET_IP/"; then
    sudo ip addr add "$TARGET_IP/24" dev "$TARGET_IFACE" 2>/dev/null || true
  fi
  sudo ip link set "$TARGET_IFACE" up

  log "Nyalakan OpenSSH dan Nginx"
  sudo systemctl enable --now ssh
  sudo systemctl enable --now nginx

  log "Nyalakan iPerf3 server untuk baseline"
  sudo pkill -f 'iperf3 -s' 2>/dev/null || true
  nohup iperf3 -s >"$TARGET_LAB_DIR/logs/iperf3-server.log" 2>&1 &

  log "Tulis rule Snort lokal khusus lab"
  sudo mkdir -p "$(dirname "$SNORT_LOCAL_RULES")"
  local tmp_rules
  tmp_rules="$(mktemp)"
  cat >"$tmp_rules" <<EOF
alert tcp $ATTACKER_IP any -> $TARGET_IP 80 (msg:"LOCAL LAB possible Slow HTTP or Slowloris traffic"; flow:to_server,established; detection_filter:track by_src, count 20, seconds 60; sid:1000001; rev:2;)
alert tcp $ATTACKER_IP any -> $TARGET_IP 80 (msg:"LOCAL LAB high HTTP request burst"; flow:to_server,established; detection_filter:track by_src, count 200, seconds 10; sid:1000002; rev:1;)
alert tcp $ATTACKER_IP any -> $TARGET_IP any (msg:"LOCAL LAB repeated TCP connection attempts"; detection_filter:track by_src, count 40, seconds 20; sid:1000003; rev:1;)
EOF
  sudo cp "$tmp_rules" "$SNORT_LOCAL_RULES"
  rm -f "$tmp_rules"

  if [[ -f "$SNORT_CONF" ]]; then
    local tmp_conf
    tmp_conf="$(mktemp)"
    sudo cp "$SNORT_CONF" "$tmp_conf"
    sudo chmod 644 "$tmp_conf"

    if ! grep -q 'local.rules' "$tmp_conf"; then
      log "Tambahkan include local.rules ke snort.conf"
      printf '%s\n' 'include $RULE_PATH/local.rules' >>"$tmp_conf"
      sudo cp "$tmp_conf" "$SNORT_CONF"
    fi

    rm -f "$tmp_conf"
  fi

  log "Cek konfigurasi Snort"
  sudo snort -T -q -c "$SNORT_CONF" -i "$TARGET_IFACE" >/tmp/vm-lab-snort-test.log 2>&1 || {
    cat /tmp/vm-lab-snort-test.log >&2
    die "Konfigurasi Snort gagal."
  }

  log "Target siap: IP=$TARGET_IP IFACE=$TARGET_IFACE LAB=$TARGET_LAB_DIR"
}

main "$@"
