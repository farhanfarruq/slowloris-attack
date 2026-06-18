#!/usr/bin/env bash
set -Eeuo pipefail

ROLE="${ROLE:-}"

log() {
  printf '[install-vm-tools] %s\n' "$*"
}

die() {
  printf '[install-vm-tools][ERROR] %s\n' "$*" >&2
  exit 1
}

main() {
  case "$ROLE" in
    attacker|target) ;;
    *) die "Set ROLE=attacker atau ROLE=target." ;;
  esac

  command -v sudo >/dev/null 2>&1 || die "sudo belum tersedia."

  log "Update repository"
  sudo apt-get update

  if [[ "$ROLE" == "target" ]]; then
    log "Install tools Target: OpenSSH, Nginx, Wireshark/dumpcap, Snort, iPerf3"
    echo "wireshark-common wireshark-common/install-setuid boolean true" | sudo debconf-set-selections
    sudo DEBIAN_FRONTEND=noninteractive apt-get install -y \
      openssh-server nginx wireshark wireshark-common snort iperf3 curl net-tools iproute2

    log "Aktifkan service Target"
    sudo systemctl enable --now ssh
    sudo systemctl enable --now nginx

    log "Tambahkan user saat ini ke group wireshark jika group tersedia"
    if getent group wireshark >/dev/null 2>&1; then
      sudo usermod -aG wireshark "$USER"
    fi
  else
    log "Install tools Attacker: OpenSSH, curl, ab, slowhttptest, iPerf3, nmap, hping3"
    sudo DEBIAN_FRONTEND=noninteractive apt-get install -y \
      openssh-server curl apache2-utils slowhttptest iperf3 nmap hping3 net-tools iproute2

    sudo systemctl enable --now ssh
  fi

  log "Selesai. Jika group wireshark baru ditambahkan, logout/login VM Target agar berlaku untuk GUI Wireshark."
}

main "$@"
