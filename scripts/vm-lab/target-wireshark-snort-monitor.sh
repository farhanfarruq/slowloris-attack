#!/usr/bin/env bash
set -Eeuo pipefail

SCENARIO="${SCENARIO:-slow-http}"
ATTACKER_IP="${ATTACKER_IP:-192.168.56.102}"
TARGET_IP="${TARGET_IP:-192.168.56.103}"
TARGET_IFACE="${TARGET_IFACE:-enp0s8}"
CAPTURE_SECONDS="${CAPTURE_SECONDS:-90}"
TARGET_LAB_DIR="${TARGET_LAB_DIR:-$HOME/slowloris-lab}"
SNORT_CONF="${SNORT_CONF:-/etc/snort/snort.conf}"
CAPTURE_FILTER="${CAPTURE_FILTER:-}"

log() {
  printf '[target-monitor] %s\n' "$*"
}

die() {
  printf '[target-monitor][ERROR] %s\n' "$*" >&2
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

tcp_state_count() {
  local state="$1"
  ss -Htan 2>/dev/null | awk -v state="$state" '
    $1 == state && ($4 ~ /:80$/ || $5 ~ /:80$/) { count++ }
    END { print count + 0 }
  '
}

tcp_attacker_count() {
  ss -Htan 2>/dev/null | awk -v attacker="$ATTACKER_IP" '
    $4 ~ attacker || $5 ~ attacker { count++ }
    END { print count + 0 }
  '
}

load_one_minute() {
  awk '{ print $1 }' /proc/loadavg 2>/dev/null || printf '0
'
}

mem_available_kb() {
  awk '/^MemAvailable:/ { print $2 }' /proc/meminfo 2>/dev/null || printf '0
'
}

sockstat_tcp_inuse() {
  awk '/^TCP:/ {
    for (i = 1; i <= NF; i++) {
      if ($i == "inuse") {
        print $(i + 1)
        exit
      }
    }
  }' /proc/net/sockstat 2>/dev/null || printf '0
'
}

metrics_loop() {
  local metrics_file="$1"

  printf '%s
' 'timestamp_epoch,scenario,tcp_established_port80,tcp_syn_recv_port80,tcp_close_wait_port80,tcp_attacker_total,load_1m,mem_available_kb,sockstat_tcp_inuse' >"$metrics_file"

  while true; do
    printf '%s,%s,%s,%s,%s,%s,%s,%s,%s
'       "$(date +%s)"       "$SCENARIO"       "$(tcp_state_count ESTAB)"       "$(tcp_state_count SYN-RECV)"       "$(tcp_state_count CLOSE-WAIT)"       "$(tcp_attacker_count)"       "$(load_one_minute)"       "$(mem_available_kb)"       "$(sockstat_tcp_inuse)" >>"$metrics_file"
    sleep 2
  done
}

capture_filter_for() {
  if [[ -n "$CAPTURE_FILTER" ]]; then
    printf '%s\n' "$CAPTURE_FILTER"
    return
  fi

  case "$SCENARIO" in
    baseline-normal|http-burst|slow-http) printf '%s\n' 'tcp port 80' ;;
    iperf-bandwidth) printf '%s\n' 'tcp port 5201' ;;
    portscan) printf '%s\n' 'tcp' ;;
    *) printf '%s\n' "host $ATTACKER_IP and host $TARGET_IP" ;;
  esac
}

main() {
  validate_lab_ip "$ATTACKER_IP"
  validate_lab_ip "$TARGET_IP"
  command -v dumpcap >/dev/null 2>&1 || die "dumpcap belum ada. Install Wireshark di Target."
  command -v snort >/dev/null 2>&1 || die "snort belum ada. Install Snort di Target."

  local filter
  filter="$(capture_filter_for)"

  local capture_dir="$TARGET_LAB_DIR/captures"
  local validation_dir="$TARGET_LAB_DIR/validation"
  local log_dir="$TARGET_LAB_DIR/logs"
  local tmp_capture="/tmp/vm-lab-${SCENARIO}-wireshark.pcapng"
  local tmp_snort_dir="/tmp/vm-lab-${SCENARIO}-snort"
  local final_capture="$capture_dir/${SCENARIO}-wireshark.pcapng"
  local final_alert="$validation_dir/${SCENARIO}-snort.log"
  local final_metrics="$validation_dir/${SCENARIO}-target-metrics.csv"
  local dumpcap_log="$log_dir/${SCENARIO}-dumpcap.log"
  local snort_log="$log_dir/${SCENARIO}-snort-run.log"

  mkdir -p "$capture_dir" "$validation_dir" "$log_dir"
  rm -f "$final_capture" "$final_alert" "$final_metrics" "$dumpcap_log" "$snort_log"
  sudo rm -rf "$tmp_capture" "$tmp_snort_dir"
  sudo mkdir -p "$tmp_snort_dir"

  log "Mulai dumpcap Wireshark selama ${CAPTURE_SECONDS}s: $final_capture"
  log "Filter capture: $filter"
  sudo timeout "${CAPTURE_SECONDS}s" dumpcap -i "$TARGET_IFACE" -f "$filter" -w "$tmp_capture" >"$dumpcap_log" 2>&1 &
  local dumpcap_pid=$!

  log "Mulai Snort selama ${CAPTURE_SECONDS}s: $final_alert"
  sudo timeout "${CAPTURE_SECONDS}s" snort -A fast -q -c "$SNORT_CONF" -i "$TARGET_IFACE" -l "$tmp_snort_dir" >"$snort_log" 2>&1 &
  local snort_pid=$!

  log "Mulai sampling metrik target: $final_metrics"
  metrics_loop "$final_metrics" &
  local metrics_pid=$!

  set +e
  wait "$dumpcap_pid"
  local dumpcap_status=$?
  wait "$snort_pid"
  local snort_status=$?
  kill "$metrics_pid" >/dev/null 2>&1 || true
  wait "$metrics_pid" >/dev/null 2>&1 || true
  set -e

  if [[ "$dumpcap_status" -ne 0 && "$dumpcap_status" -ne 124 ]]; then
    cat "$dumpcap_log" >&2 || true
    die "dumpcap gagal dengan exit code $dumpcap_status"
  fi
  if [[ "$snort_status" -ne 0 && "$snort_status" -ne 124 ]]; then
    cat "$snort_log" >&2 || true
    die "snort gagal dengan exit code $snort_status"
  fi

  sudo cp "$tmp_capture" "$final_capture"
  sudo chmod 644 "$final_capture"
  sudo rm -f "$tmp_capture"

  if [[ -f "$tmp_snort_dir/alert" ]]; then
    sudo cp "$tmp_snort_dir/alert" "$final_alert"
  else
    : >"$final_alert"
  fi
  sudo chmod 644 "$final_alert"
  chmod 644 "$final_metrics"
  sudo rm -rf "$tmp_snort_dir"

  log "Selesai capture: $(du -h "$final_capture" | awk '{print $1}')"
  log "Selesai alert: $(du -h "$final_alert" | awk '{print $1}')"
  log "Selesai metrik: $(du -h "$final_metrics" | awk '{print $1}')"
}

main "$@"
