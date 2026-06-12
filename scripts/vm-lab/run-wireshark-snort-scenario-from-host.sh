#!/usr/bin/env bash
set -Eeuo pipefail

SCENARIO="${SCENARIO:-slow-http}"
ATTACKER_SSH="${ATTACKER_SSH:-attacker@192.168.56.102}"
TARGET_SSH="${TARGET_SSH:-target@192.168.56.103}"
ATTACKER_IP="${ATTACKER_IP:-192.168.56.102}"
TARGET_IP="${TARGET_IP:-192.168.56.103}"
TARGET_IFACE="${TARGET_IFACE:-enp0s8}"
CAPTURE_SECONDS="${CAPTURE_SECONDS:-90}"
REQUESTS="${REQUESTS:-3000}"
CONCURRENCY="${CONCURRENCY:-30}"
CONNECTIONS="${CONNECTIONS:-80}"
RATE="${RATE:-10}"
DURATION="${DURATION:-75}"
PORTS="${PORTS:-22,80,443,5201,8000}"
PARALLEL="${PARALLEL:-2}"
BANDWIDTH="${BANDWIDTH:-10M}"
MONITOR_WAIT_TIMEOUT="${MONITOR_WAIT_TIMEOUT:-$((CAPTURE_SECONDS + 30))}"
TARGET_USER="${TARGET_SSH%@*}"
TARGET_LAB_DIR="${TARGET_LAB_DIR:-/home/$TARGET_USER/slowloris-lab}"
LOCAL_CAPTURE_DIR="${LOCAL_CAPTURE_DIR:-$PWD/storage/app/vm-lab-captures}"
SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"

log() {
  printf '[wireshark-snort-runner] %s\n' "$*"
}

die() {
  printf '[wireshark-snort-runner][ERROR] %s\n' "$*" >&2
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

attacker_script_for() {
  case "$SCENARIO" in
    http-burst) printf '%s\n' "$SCRIPT_DIR/attacker-http-burst.sh" ;;
    slow-http) printf '%s\n' "$SCRIPT_DIR/attacker-slow-http.sh" ;;
    portscan) printf '%s\n' "$SCRIPT_DIR/attacker-portscan.sh" ;;
    iperf-bandwidth) printf '%s\n' "$SCRIPT_DIR/attacker-iperf-bandwidth.sh" ;;
    *) die "SCENARIO tidak dikenal: $SCENARIO" ;;
  esac
}

main() {
  validate_lab_ip "$ATTACKER_IP"
  validate_lab_ip "$TARGET_IP"
  command -v ssh >/dev/null 2>&1 || die "ssh belum ada di host."
  command -v scp >/dev/null 2>&1 || die "scp belum ada di host."

  local attacker_script
  attacker_script="$(attacker_script_for)"
  [[ -f "$attacker_script" ]] || die "Script attacker tidak ditemukan: $attacker_script"

  log "Scenario: $SCENARIO"
  log "Target: $TARGET_SSH ($TARGET_IP) iface=$TARGET_IFACE"
  log "Attacker: $ATTACKER_SSH ($ATTACKER_IP)"

  log "Copy script Target dan Attacker"
  scp "$SCRIPT_DIR/target-wireshark-snort-prepare.sh" "$TARGET_SSH:/tmp/target-wireshark-snort-prepare.sh"
  scp "$SCRIPT_DIR/target-wireshark-snort-monitor.sh" "$TARGET_SSH:/tmp/target-wireshark-snort-monitor.sh"
  scp "$attacker_script" "$ATTACKER_SSH:/tmp/$(basename "$attacker_script")"

  log "Prepare Target: Nginx, iPerf3 server, Snort rules, folder lab"
  ssh "$TARGET_SSH" "chmod +x /tmp/target-wireshark-snort-prepare.sh && ATTACKER_IP='$ATTACKER_IP' TARGET_IP='$TARGET_IP' TARGET_IFACE='$TARGET_IFACE' TARGET_LAB_DIR='$TARGET_LAB_DIR' /tmp/target-wireshark-snort-prepare.sh"

  log "Start Wireshark dumpcap + Snort monitor selama ${CAPTURE_SECONDS}s"
  ssh "$TARGET_SSH" "chmod +x /tmp/target-wireshark-snort-monitor.sh && SCENARIO='$SCENARIO' ATTACKER_IP='$ATTACKER_IP' TARGET_IP='$TARGET_IP' TARGET_IFACE='$TARGET_IFACE' TARGET_LAB_DIR='$TARGET_LAB_DIR' CAPTURE_SECONDS='$CAPTURE_SECONDS' /tmp/target-wireshark-snort-monitor.sh" &
  local monitor_ssh_pid=$!

  log "Tunggu 5 detik agar monitor siap"
  sleep 5
  if ! kill -0 "$monitor_ssh_pid" 2>/dev/null; then
    wait "$monitor_ssh_pid" || true
    die "Monitor Target berhenti sebelum attacker berjalan."
  fi

  log "Run attacker scenario"
  ssh "$ATTACKER_SSH" "chmod +x '/tmp/$(basename "$attacker_script")' && TARGET_IP='$TARGET_IP' REQUESTS='$REQUESTS' CONCURRENCY='$CONCURRENCY' CONNECTIONS='$CONNECTIONS' RATE='$RATE' DURATION='$DURATION' PORTS='$PORTS' PARALLEL='$PARALLEL' BANDWIDTH='$BANDWIDTH' '/tmp/$(basename "$attacker_script")'"

  log "Menunggu monitor menutup file capture"
  if ! wait "$monitor_ssh_pid"; then
    die "Monitor Target gagal. Lihat log di Target: $TARGET_LAB_DIR/logs/${SCENARIO}-dumpcap.log dan ${SCENARIO}-snort-run.log"
  fi

  log "Log dumpcap"
  ssh "$TARGET_SSH" "tail -80 '$TARGET_LAB_DIR/logs/${SCENARIO}-dumpcap.log' 2>/dev/null || true"
  log "Log Snort"
  ssh "$TARGET_SSH" "tail -80 '$TARGET_LAB_DIR/logs/${SCENARIO}-snort-run.log' 2>/dev/null || true"

  local remote_pcap="$TARGET_LAB_DIR/captures/${SCENARIO}-wireshark.pcapng"
  local remote_alert="$TARGET_LAB_DIR/validation/${SCENARIO}-snort.log"
  local remote_metrics="$TARGET_LAB_DIR/validation/${SCENARIO}-target-metrics.csv"
  mkdir -p "$LOCAL_CAPTURE_DIR"

  log "Copy file akuisisi dan validasi ke host"
  ssh "$TARGET_SSH" "test -s '$remote_pcap' && test -e '$remote_alert'"
  scp "$TARGET_SSH:$remote_pcap" "$LOCAL_CAPTURE_DIR/"
  scp "$TARGET_SSH:$remote_alert" "$LOCAL_CAPTURE_DIR/"

  log "Selesai:"
  printf '%s\n' "$LOCAL_CAPTURE_DIR/${SCENARIO}-wireshark.pcapng"
  printf '%s\n' "$LOCAL_CAPTURE_DIR/${SCENARIO}-snort.log"
}

main "$@"
