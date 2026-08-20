# Artefak Simulasi Offline untuk Validasi Dashboard

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

Generator tidak membuka socket, tidak mengirim paket, dan tidak memanggil tool serangan. PCAPNG disintesis offline hanya untuk menguji parser, evidence gate, UI upload, dan alur analisis. Jangan menyebut file ini sebagai capture dari VM, ESP32, Snort live, atau eksperimen fisik.
