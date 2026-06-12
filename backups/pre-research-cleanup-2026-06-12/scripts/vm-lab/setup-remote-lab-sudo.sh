#!/usr/bin/env bash
set -Eeuo pipefail

ATTACKER_SSH="${ATTACKER_SSH:-attacker@192.168.56.102}"
TARGET_SSH="${TARGET_SSH:-target@192.168.56.103}"
ATTACKER_USER="${ATTACKER_SSH%@*}"
TARGET_USER="${TARGET_SSH%@*}"
SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"

log() {
  printf '[setup] %s\n' "$*"
}

die() {
  printf '[setup][ERROR] %s\n' "$*" >&2
  exit 1
}

need_cmd() {
  command -v "$1" >/dev/null 2>&1 || die "Command '$1' belum ada di host."
}

main() {
  need_cmd ssh

  log "Setup sudo terbatas di Target. Masukkan password user Target jika diminta."
  ssh -tt "$TARGET_SSH" "sudo sh -c 'cat > /etc/sudoers.d/vm-lab-target <<EOF
$TARGET_USER ALL=(ALL) NOPASSWD: /usr/bin/dumpcap, /usr/sbin/snort, /usr/bin/snort, /usr/bin/timeout, /usr/sbin/ip, /usr/bin/systemctl, /usr/bin/pkill, /usr/bin/rm, /usr/bin/mkdir, /usr/bin/chmod, /usr/bin/cp, /usr/bin/tee
EOF
chmod 440 /etc/sudoers.d/vm-lab-target
visudo -cf /etc/sudoers.d/vm-lab-target'"

  log "Setup sudo terbatas di Attacker. Masukkan password user Attacker jika diminta."
  ssh -tt "$ATTACKER_SSH" "sudo sh -c 'cat > /etc/sudoers.d/vm-lab-attacker <<EOF
$ATTACKER_USER ALL=(ALL) NOPASSWD: /usr/sbin/ip
EOF
chmod 440 /etc/sudoers.d/vm-lab-attacker
visudo -cf /etc/sudoers.d/vm-lab-attacker'"

  log "Cek sudo non-interactive Target"
  ssh "$TARGET_SSH" "sudo -n /usr/sbin/ip link show >/dev/null"

  log "Cek sudo non-interactive Attacker"
  ssh "$ATTACKER_SSH" "sudo -n /usr/sbin/ip link show >/dev/null"

  log "Setup selesai."
}

main "$@"
