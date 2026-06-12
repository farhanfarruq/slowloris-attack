# Sample Data untuk Pengujian Dashboard

File-file di folder ini bisa diunggah langsung ke dashboard untuk uji coba alur
analisis tanpa harus melakukan capture sungguhan terlebih dahulu.

| File | Tipe | Cara Pakai |
|------|------|-----------|
| `sample_capture.csv` | CSV TShark | Upload pada halaman **Upload Akuisisi** |
| `sample_capture_summary.json` | JSON ringkasan | Upload pada halaman **Upload Akuisisi** (parser memanfaatkan field summary) |
| `sample_snort_alerts.json` | JSON Lines Snort | Upload pada halaman **Upload Validasi** |

Setelah kedua file diunggah ke eksperimen yang sama, jalankan:

1. **Proses Analisis** → menghitung skor radar & final attack score.
2. **Validasi AI** → sistem mengirim ringkasan ke provider terpilih.
3. **Generate Laporan** → menghasilkan PDF laporan lengkap.

## Format JSON Capture Summary (sesuai parser)

Parser akan mengenali kunci `packet_summary`, `connection_summary`,
`top_source_ips`, `top_destination_ips`, `protocol_distribution`. Ini membuat
upload `.json` ringkasan dari skrip TShark/preprocessing menjadi cara paling
praktis untuk eksperimen yang besar.
