#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_NAME="$(basename "$0")"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

ACTION="${ACTION:-run}"
PROFILE="${PROFILE:-slowloris}"
ATTACK_PATTERN="${ATTACK_PATTERN:-}"
EXPERIMENT_CODE="${EXPERIMENT_CODE:-manual-$(date +%Y%m%d-%H%M%S)}"

ATTACKER_SSH="${ATTACKER_SSH:-attacker@192.168.56.102}"
TARGET_SSH="${TARGET_SSH:-target@192.168.56.103}"
ATTACKER_IP="${ATTACKER_IP:-192.168.56.102}"
TARGET_IP="${TARGET_IP:-192.168.56.103}"
TARGET_IFACE="${TARGET_IFACE:-enp0s8}"
CAPTURE_SECONDS="${CAPTURE_SECONDS:-120}"
TARGET_LAB_DIR="${TARGET_LAB_DIR:-/opt/slowloris-lab}"
LOCAL_OUT_DIR="${LOCAL_OUT_DIR:-$REPO_ROOT/storage/app/vm-lab-captures}"
SSH_EXTRA_OPTS="${SSH_EXTRA_OPTS:-}"

SSH_OPTS=(-o BatchMode=no -o StrictHostKeyChecking=accept-new -o ConnectTimeout=8)
if [[ -n "$SSH_EXTRA_OPTS" ]]; then
  # shellcheck disable=SC2206
  SSH_OPTS+=($SSH_EXTRA_OPTS)
fi

log() { printf '[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*"; }
die() { printf '[%s][ERROR] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*" >&2; exit 1; }
need_cmd() { command -v "$1" >/dev/null 2>&1 || die "Command missing on laptop: $1"; }

usage() {
  cat <<EOF
Usage:
  ACTION=status|prepare|run|collect|target-shell|attacker-shell $SCRIPT_NAME

Environment:
  PROFILE=slowloris|loic|hoic|hping3|torshammer|xerxes
  ATTACK_PATTERN=slow_http|http_flood|tcp_syn_flood|udp_flood|icmp_flood|mixed
  EXPERIMENT_CODE=EXP-039
  TARGET_SSH=target@192.168.56.103
  ATTACKER_SSH=attacker@192.168.56.102
  TARGET_IP=192.168.56.103
  ATTACKER_IP=192.168.56.102
  TARGET_IFACE=enp0s8
  CAPTURE_SECONDS=120

Actions:
  status         Check SSH, target tools, and lab directories.
  prepare        Copy safe target helper scripts and prepare target monitoring.
  run            Prepare target, start capture/Snort monitor, open attacker SSH shell manually, collect output.
  collect        Copy PCAP, Snort alert log, metrics, and runtime logs from target to laptop.
  target-shell   Open target SSH shell with lab environment variables.
  attacker-shell Open attacker SSH shell with lab environment variables.

Safety boundary:
  This script does not run attack commands, scanners, flood tools, payload tools, or bypass logic.
  Use only an isolated VM lab that you own or have written authorization to test.
EOF
}

profile_defaults() {
  case "$PROFILE" in
    slowloris) ATTACK_PATTERN="${ATTACK_PATTERN:-slow_http}" ;;
    loic) ATTACK_PATTERN="${ATTACK_PATTERN:-http_flood}" ;;
    hoic) ATTACK_PATTERN="${ATTACK_PATTERN:-http_flood}" ;;
    hping3) ATTACK_PATTERN="${ATTACK_PATTERN:-tcp_syn_flood}" ;;
    torshammer) ATTACK_PATTERN="${ATTACK_PATTERN:-slow_http}" ;;
    xerxes) ATTACK_PATTERN="${ATTACK_PATTERN:-http_flood}" ;;
    *) die "Unknown PROFILE: $PROFILE" ;;
  esac
}

monitor_scenario() {
  case "$ATTACK_PATTERN" in
    slow_http) printf '%s\n' 'slow-http' ;;
    http_flood|tcp_flood|tcp_syn_flood|udp_flood|icmp_flood|mixed) printf '%s\n' "${PROFILE}-${ATTACK_PATTERN}" ;;
    *) printf '%s\n' "${PROFILE}-${ATTACK_PATTERN:-manual}" ;;
  esac
}

validate_lab_ip() {
  case "$1" in
    192.168.56.*) ;;
    *) die "IP '$1' is not allowed. Use the isolated VM lab subnet 192.168.56.x." ;;
  esac
}

remote_env() {
  printf "PROFILE=%q ATTACK_PATTERN=%q EXPERIMENT_CODE=%q TARGET_IP=%q ATTACKER_IP=%q TARGET_IFACE=%q TARGET_LAB_DIR=%q CAPTURE_SECONDS=%q" \
    "$PROFILE" "$ATTACK_PATTERN" "$EXPERIMENT_CODE" "$TARGET_IP" "$ATTACKER_IP" "$TARGET_IFACE" "$TARGET_LAB_DIR" "$CAPTURE_SECONDS"
}

safe_ssh() {
  ssh "${SSH_OPTS[@]}" "$@"
}

safe_scp() {
  scp "${SSH_OPTS[@]}" "$@"
}

preflight() {
  need_cmd ssh
  need_cmd scp
  validate_lab_ip "$TARGET_IP"
  validate_lab_ip "$ATTACKER_IP"
  [[ "$CAPTURE_SECONDS" =~ ^[0-9]+$ ]] || die "CAPTURE_SECONDS must be numeric."
  (( CAPTURE_SECONDS >= 30 && CAPTURE_SECONDS <= 3600 )) || die "CAPTURE_SECONDS must be between 30 and 3600."
  [[ -f "$SCRIPT_DIR/target-wireshark-snort-prepare.sh" ]] || die "Missing target prepare helper."
  [[ -f "$SCRIPT_DIR/target-wireshark-snort-monitor.sh" ]] || die "Missing target monitor helper."
}

copy_target_helpers() {
  log "Copy target helper scripts to $TARGET_SSH"
  safe_scp "$SCRIPT_DIR/target-wireshark-snort-prepare.sh" "$TARGET_SSH:/tmp/target-wireshark-snort-prepare.sh"
  safe_scp "$SCRIPT_DIR/target-wireshark-snort-monitor.sh" "$TARGET_SSH:/tmp/target-wireshark-snort-monitor.sh"
}

prepare_target() {
  copy_target_helpers
  log "Prepare target monitoring services and lab folders"
  safe_ssh "$TARGET_SSH" "chmod +x /tmp/target-wireshark-snort-prepare.sh && $(remote_env) /tmp/target-wireshark-snort-prepare.sh"
}

status() {
  log "Laptop -> target SSH"
  safe_ssh "$TARGET_SSH" "hostname; ip -brief addr; command -v dumpcap || true; command -v snort || true; test -d '$TARGET_LAB_DIR' && find '$TARGET_LAB_DIR' -maxdepth 2 -type d | sort || true"
  log "Laptop -> attacker SSH"
  safe_ssh "$ATTACKER_SSH" "hostname; ip -brief addr; printf 'Manual attacker shell only. No attack command is executed by this script.\\n'"
}

target_shell() {
  log "Opening target shell"
  safe_ssh "$TARGET_SSH" "$(remote_env) bash -l"
}

attacker_shell() {
  log "Opening attacker shell. Run only authorized lab actions manually."
  safe_ssh "$ATTACKER_SSH" "$(remote_env) bash -lc 'cat <<BANNER
Authorized isolated VM lab only.
PROFILE=$PROFILE
ATTACK_PATTERN=$ATTACK_PATTERN
EXPERIMENT_CODE=$EXPERIMENT_CODE
TARGET_IP=$TARGET_IP
ATTACKER_IP=$ATTACKER_IP

This script did not start any attack command.
Start and stop your authorized lab traffic manually, then exit this shell.
BANNER
exec bash -l'"
}

TARGET_MONITOR_PID=""

start_target_monitor() {
  local scenario
  scenario="$(monitor_scenario)"
  log "Start target capture/Snort monitor: scenario=$scenario seconds=$CAPTURE_SECONDS"
  safe_ssh "$TARGET_SSH" "chmod +x /tmp/target-wireshark-snort-monitor.sh && SCENARIO='$scenario' $(remote_env) /tmp/target-wireshark-snort-monitor.sh" &
  TARGET_MONITOR_PID="$!"
}

collect_outputs() {
  local scenario dest
  scenario="$(monitor_scenario)"
  dest="$LOCAL_OUT_DIR/$EXPERIMENT_CODE/$PROFILE"
  mkdir -p "$dest"
  log "Collect target artifacts to $dest"
  safe_scp "$TARGET_SSH:$TARGET_LAB_DIR/captures/${scenario}-wireshark.pcapng" "$dest/" || log "PCAP not found yet."
  safe_scp "$TARGET_SSH:$TARGET_LAB_DIR/validation/${scenario}-snort-alerts.log" "$dest/" || log "Snort alert log not found yet."
  safe_scp "$TARGET_SSH:$TARGET_LAB_DIR/logs/${scenario}-metrics.csv" "$dest/" || log "Metrics CSV not found yet."
  safe_scp "$TARGET_SSH:$TARGET_LAB_DIR/logs/${scenario}-dumpcap.log" "$dest/" || true
  safe_scp "$TARGET_SSH:$TARGET_LAB_DIR/logs/${scenario}-snort-run.log" "$dest/" || true
  cat > "$dest/README.txt" <<EOF
Experiment: $EXPERIMENT_CODE
Profile: $PROFILE
Attack pattern: $ATTACK_PATTERN
Target platform: vm_ubuntu_server
Target SSH/IP: $TARGET_SSH / $TARGET_IP
Attacker SSH/IP: $ATTACKER_SSH / $ATTACKER_IP
Capture seconds: $CAPTURE_SECONDS

Upload the PCAP as acquisition data and the Snort alert log as validation data.
No attack command was executed by vm-profile-ssh-control.sh.
EOF
  log "Done. Local artifact folder: $dest"
}

run_manual_lab() {
  prepare_target
  local monitor_pid
  start_target_monitor
  monitor_pid="$TARGET_MONITOR_PID"
  log "Wait 5 seconds for capture to initialize"
  sleep 5
  if ! kill -0 "$monitor_pid" 2>/dev/null; then
    wait "$monitor_pid" || true
    die "Target monitor exited before manual attacker shell opened."
  fi

  attacker_shell

  log "Waiting for target monitor to finish"
  if ! wait "$monitor_pid"; then
    die "Target monitor failed. Check remote logs under $TARGET_LAB_DIR/logs."
  fi
  collect_outputs
}

main() {
  if [[ "${1:-}" == "-h" || "${1:-}" == "--help" ]]; then
    usage
    exit 0
  fi

  profile_defaults
  preflight

  case "$ACTION" in
    status) status ;;
    prepare) prepare_target ;;
    run) run_manual_lab ;;
    collect) collect_outputs ;;
    target-shell) target_shell ;;
    attacker-shell) attacker_shell ;;
    *) usage; die "Unknown ACTION: $ACTION" ;;
  esac
}

main "$@"
