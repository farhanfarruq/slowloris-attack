# Artefak Simulasi Offline untuk Validasi Dashboard

## Batas penggunaan

Dokumen ini menjalankan **simulasi offline**, bukan serangan ke VM, ESP32, atau jaringan. Runner tidak membutuhkan mesin attacker, target, SSH, sudo, Snort live, maupun koneksi internet. Tujuannya hanya menghasilkan file yang bisa dipakai untuk menguji upload, parser, evidence gate, dan tampilan dashboard.

Jangan memakai hasilnya sebagai bukti eksperimen fisik atau mengunggahnya ke catatan yang diklaim sebagai capture ESP32/VM nyata.

## Persiapan

1. Buka terminal pada root project.
2. Pastikan PHP dan TShark tersedia:

   ```bash
   command -v php
   command -v tshark
   ```

3. Pastikan folder project dapat ditulis. Untuk menjalankan runner pertama kali:

   ```bash
   chmod +x scripts/simulated-lab/*.sh
   ```

Tidak ada IP target yang harus diisi dan tidak ada tool serangan yang harus dipasang.

## Menjalankan satu skenario

Jalankan satu runner per skenario dari root project. Setiap runner hanya membuat satu pasangan PCAPNG/log pada subfolder skenarionya.

```bash
scripts/simulated-lab/run-slowloris.sh
```

| Status yang diharapkan | Runner |
| --- | --- |
| Detected | `run-slowloris.sh`, `run-loic.sh`, `run-hoic.sh`, `run-hping3.sh`, `run-torshammer.sh`, `run-xerxes.sh` |
| Suspicious | `run-slowloris-suspicious.sh` |
| Normal/non-attack | `run-http-burst.sh`, `run-portscan.sh`, `run-iperf-bandwidth.sh` |

Setiap runner memverifikasi PCAPNG dengan TShark. Berikan direktori keluaran sebagai argumen pertama bila perlu.

Contoh lain:

```bash
scripts/simulated-lab/run-loic.sh
scripts/simulated-lab/run-slowloris-suspicious.sh
scripts/simulated-lab/run-http-burst.sh
```

Setelah selesai, terminal menampilkan lokasi file akuisisi dan validasi. Runner gagal bila PCAPNG kosong atau tidak dapat dibaca TShark.

Hasil berada di `storage/app/simulated-lab`. Setiap skenario memiliki tiga file:

| Skenario | Akuisisi | Validasi |
| --- | --- | --- |
| Slowloris | `slowloris-acquisition.pcapng` | `slowloris-validation.log` |
| Slowloris bukti tidak lengkap | `slowloris-suspicious-acquisition.pcapng` | `slowloris-suspicious-validation.log` |
| LOIC | `loic-acquisition.pcapng` | `loic-validation.log` |
| HOIC | `hoic-acquisition.pcapng` | `hoic-validation.log` |
| Hping3 | `hping3-acquisition.pcapng` | `hping3-validation.log` |
| Torshammer | `torshammer-acquisition.pcapng` | `torshammer-validation.log` |
| Xerxes | `xerxes-acquisition.pcapng` | `xerxes-validation.log` |
| HTTP burst pembanding | `http-burst-acquisition.pcapng` | `http-burst-validation.log` |
| Portscan pembanding | `portscan-acquisition.pcapng` | `portscan-validation.log` |
| iPerf pembanding | `iperf-bandwidth-acquisition.pcapng` | `iperf-bandwidth-validation.log` |

`MANIFEST.json` dan setiap `*-metadata.json` mencatat indikator, jumlah paket, jumlah alert, hash SHA-256, dan penanda `is_simulated: true`.

## Memeriksa hasil

Misalnya setelah menjalankan Slowloris:

```bash
tshark -r storage/app/simulated-lab/slowloris/slowloris-acquisition.pcapng -q
sha256sum storage/app/simulated-lab/slowloris/slowloris-acquisition.pcapng \
  storage/app/simulated-lab/slowloris/slowloris-validation.log
```

File yang dipakai sebagai bukti simulasi adalah:

- `*-acquisition.pcapng`: file akuisisi jaringan untuk parser Wireshark/TShark.
- `*-validation.log`: log validasi format Snort-fast simulasi.
- `*-metadata.json`: indikator yang sengaja dibentuk dan status keputusan yang diharapkan.

## Menguji dashboard

Gunakan database atau akun pengujian. Buat eksperimen dengan nama yang jelas, misalnya `SIMULATION - Slowloris`, lalu unggah pasangan PCAPNG dan log dari folder skenario yang sama. Pilih profil tool yang sesuai dengan nama runner. Simpan penanda `is_simulated: true` dari metadata pada catatan eksperimen agar tidak tercampur dengan bukti fisik.

Generator tidak membuka socket, tidak mengirim paket, dan tidak memanggil tool serangan. PCAPNG disintesis offline hanya untuk menguji parser, evidence gate, UI upload, dan alur analisis. Jangan menyebut file ini sebagai capture dari VM, ESP32, Snort live, atau eksperimen fisik.
