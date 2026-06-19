# Slowloris/DDoS Defensive Lab Dashboard - Project Guide

Dokumen ini dibuat sebagai bahan literasi pribadi, presentasi ke senior magang, dan fondasi laporan skripsi. Fokus sistem ini adalah analisis defensif pada data eksperimen lab terisolasi, bukan otomasi serangan ke target publik.

## Prompt Generate Presentasi Project Saat Ini

Gunakan prompt berikut jika ingin membuat slide presentasi dari dokumen ini. Prompt ini sengaja dibatasi hanya untuk presentasi project yang sudah dikerjakan saat ini, bukan untuk skripsi, penelitian, publikasi, atau klaim akademik.

```text
Buatkan presentasi profesional berbahasa Indonesia berdasarkan dokumentasi project "Slowloris/DDoS Defensive Lab Dashboard".

Target audiens:
- Senior/pembimbing magang teknis.
- Orang yang ingin memahami apa yang sudah dikerjakan pada aplikasi saat ini.
- Jangan arahkan presentasi ke skripsi, penelitian, proposal akademik, jurnal, atau metodologi penelitian yang belum final.

Sumber yang boleh digunakan dari dokumentasi:
- 1. Inti Project
- 2. Batasan Etis dan Scope
- 3. Stack Aplikasi
- 4. File dan Modul Penting
- 5. Arsitektur Data
- 6. Flow Mendapatkan Data
- 7. Flow Aplikasi dari UI
- 8. Scoring: Konsep Dasar
- 9. Bobot Scoring Per Profile
- 11. Metric Scoring dan Maknanya
- 12. Evidence Gating
- 13. AI Prompt dan Validasi
- 14. Kenapa AI Tidak Jadi Sumber Keputusan Utama
- 15. Cara Agar Data Valid
- 18. Logic Krusial yang Harus Dijaga
- 19. Testing yang Ada
- 20. Tools yang Diperlukan untuk Menjalankan Project
- 25. Kesimpulan

Bagian yang wajib diabaikan dan jangan dimasukkan ke slide:
- 10. Status Akademik Bobot
- 16. Cara Memvalidasi Skoring untuk Skripsi
- 17. Kalibrasi Bobot yang Lebih Kuat
- 21. Rujukan Literatur dan Sumber Teknis
- 22. Narasi Presentasi ke Senior
- 23. Rencana Pengembangan ke Depan
- Semua pembahasan tentang skripsi, penelitian, jurnal, paper, sumber literatur, kalibrasi akademik, dataset penelitian, validasi penelitian, atau klaim ilmiah yang belum final.

Batasan isi:
- Presentasikan hanya fitur, flow, logic, dan implementasi yang sudah ada pada project saat ini.
- Jangan membuat klaim bahwa sistem ini sudah final untuk skripsi atau penelitian.
- Jangan membahas rujukan jurnal/artikel.
- Jangan membahas serangan sebagai panduan eksekusi.
- Tekankan bahwa project bersifat defensif, lab-isolated, dan audit-oriented.
- Jangan menampilkan instruksi attack automation, bypass, scanning target publik, atau eksploitasi.

Buat presentasi 12-15 slide dengan struktur berikut:

1. Judul
   - Nama project.
   - Deskripsi singkat: dashboard analisis defensif traffic Slow HTTP/DDoS berbasis Wireshark/dumpcap, Snort, scoring logic, AI validation, dan reporting.

2. Latar Belakang Project
   - Masalah yang diselesaikan: data eksperimen lab sulit diaudit jika hanya berupa file capture dan log mentah.
   - Solusi: dashboard untuk mengelola metadata eksperimen, file akuisisi, validasi IDS, scoring, AI comparison, visualisasi, dan report.

3. Tujuan Project
   - Mengelola data eksperimen defensif.
   - Menggabungkan bukti packet capture dan alert IDS.
   - Menghasilkan scoring dan keputusan yang bisa diaudit.
   - Menampilkan visualisasi dan report.

4. Scope dan Batasan Etis
   - Project hanya untuk defensive analysis.
   - Semua data berasal dari lab terisolasi.
   - Tidak ada fitur serangan langsung ke target publik.
   - AI hanya validator, bukan pengambil keputusan utama.

5. Stack dan Tools
   - Laravel 11, PHP 8.2, MySQL, Blade, Tailwind, Alpine, Vite.
   - Wireshark/dumpcap/tshark untuk akuisisi.
   - Snort untuk validasi alert.
   - iPerf3 untuk baseline.
   - Provider AI: OpenAI-compatible, Groq, Gemini, Ollama.

6. Arsitektur Modul
   - Experiment.
   - AcquisitionFile.
   - ValidationFile.
   - SnortAlert.
   - ExtractedFeature.
   - AiResult.
   - FinalReport.
   - AuditLog.

7. Flow Data End-to-End
   - Buat eksperimen.
   - Upload capture Wireshark/dumpcap.
   - Upload log Snort.
   - Parser membuat summary.
   - Scoring menghitung feature dan final score.
   - Evidence gate menentukan apakah label kuat boleh dipakai.
   - AI validation membandingkan hasil.
   - Dashboard/report menampilkan hasil akhir.

8. Parser dan Feature Extraction
   - AcquisitionParser membaca pcap/pcapng/csv/json.
   - ValidationParser membaca log/json/csv/txt Snort.
   - Output parser menjadi fitur numerik seperti total packet, HTTP packet, koneksi, durasi, throughput, alert severity, dan dominant alert.

9. Scoring Per Tool Profile
   - Jelaskan bahwa Final Attack Score adalah output umum 0-100.
   - Formula bobot berbeda per profile: Slowloris, LOIC, HOIC, Hping3, Torshammer, Xerxes.
   - Jangan sebut bobot sebagai klaim akademik.
   - Tampilkan contoh ringkas metric utama per profile.

10. Evidence Gating
   - Score tinggi tidak otomatis menjadi attack_detected.
   - Sistem memeriksa bukti sesuai profile aktif.
   - False-positive guard melindungi traffic normal, iPerf, HTTP burst, portscan, dan evidence yang tidak lengkap.
   - Gate reasons dan missing evidence harus terlihat agar reviewer bisa audit keputusan.

11. AI Validation
   - Prompt AI dibuat di AiPromptBuilder.
   - AI menerima payload ringkasan, bukan file mentah.
   - AI harus output JSON.
   - AI tidak boleh mengarang data.
   - AI tidak boleh memberi label detected jika evidence contract melarang.
   - AiValidationService menormalisasi response provider.

12. Logic Krusial
   - ScoringService sebagai sumber keputusan scoring.
   - AnalysisService sebagai penghubung feature extraction dan persist result.
   - AiPromptBuilder sebagai kontrak prompt.
   - AiValidationService sebagai validator, bukan override.
   - ReportController menghasilkan report audit-friendly.

13. Validitas Data
   - Capture dan Snort log harus berasal dari eksperimen yang sama.
   - Waktu capture dan alert harus relevan.
   - Tool profile harus sesuai data.
   - Baseline dan ground truth perlu jelas.
   - Parser summary dan evidence gate harus dicek sebelum membuat kesimpulan.

14. Testing dan Verifikasi
   - ScoringServiceTest.
   - AiValidationServiceTest.
   - AcquisitionParserTest.
   - ValidationParserTest.
   - Build frontend dengan npm run build.
   - Test backend dengan php artisan test.

15. Penutup
   - Project sudah menjadi dashboard defensif yang menghubungkan data capture, IDS validation, scoring, AI comparison, visualisasi, audit log, dan report.
   - Fokus utama project: keputusan yang bisa ditelusuri dari bukti, bukan hanya label akhir.

Gaya slide:
- Profesional, ringkas, teknis, mudah dipresentasikan.
- Jangan terlalu penuh teks.
- Gunakan bullet pendek.
- Setiap slide punya judul jelas.
- Tambahkan speaker notes untuk menjelaskan isi slide.
- Gunakan bahasa Indonesia yang natural.
- Hindari istilah skripsi, penelitian, jurnal, paper, literatur, dan kalibrasi akademik.
- Hindari pembahasan attack execution.

Output yang saya minta:
- Daftar slide lengkap.
- Isi bullet tiap slide.
- Speaker notes singkat untuk tiap slide.
- Saran visual sederhana untuk tiap slide, misalnya flow diagram, table, module map, atau scoring pipeline.
```

## 1. Inti Project

Project ini adalah dashboard Laravel 11 untuk mengelola eksperimen defensif Slow HTTP/DDoS berbasis data lab. Sistem menerima metadata eksperimen, file akuisisi traffic dari Wireshark/dumpcap/tshark, file validasi IDS dari Snort, lalu mengekstrak fitur numerik untuk scoring berbasis rule, validasi AI multi-model, visualisasi, audit log, dan laporan.

Tujuan utamanya:

1. Membuat alur eksperimen yang bisa diaudit dari awal sampai keputusan akhir.
2. Membandingkan bukti dari packet capture dan IDS, bukan hanya mengandalkan satu sumber.
3. Menghasilkan skor dan label deteksi yang punya alasan teknis, gate evidence, dan jejak data.
4. Menjaga false positive agar traffic normal, iPerf, HTTP burst, portscan, atau traffic non-HTTP tidak mudah salah diberi label attack.
5. Menyediakan bahan laporan penelitian: dataset lab, metodologi, scoring, validasi AI, dan report akhir.

## 2. Batasan Etis dan Scope

Scope project adalah defensive analysis, validation, reporting, dan dokumentasi lab. Semua pengujian harus dilakukan di lingkungan yang dimiliki sendiri atau memiliki izin tertulis.

Yang boleh:

- Upload dan analisis file `.pcap`, `.pcapng`, `.csv`, `.json`, `.log`, atau `.txt` dari lab.
- Validasi alert Snort terhadap capture Wireshark/dumpcap.
- Analisis traffic profile defensif: Slowloris, LOIC, HOIC, Hping3, Torshammer, Xerxes.
- Membuat report, audit trail, dan visualisasi.

Yang tidak boleh:

- Menambahkan fitur attack automation ke dashboard.
- Menambahkan bypass/evasion logic.
- Menambahkan scanning target publik.
- Menyediakan tombol eksekusi serangan dari UI.

## 3. Stack Aplikasi

Backend:

- PHP 8.2.
- Laravel 11.
- MySQL 8.4 untuk database utama.
- Laravel Breeze untuk auth.
- DomPDF untuk export PDF report.
- League CSV untuk parsing CSV.

Frontend:

- Blade.
- Tailwind CSS.
- Alpine.js.
- Vite.
- Chart.js digunakan pada view visualisasi melalui asset frontend.

Lab tools:

- Wireshark/dumpcap/tshark untuk packet capture dan ekstraksi field.
- Snort 3 untuk IDS/IPS validation.
- iPerf3 untuk baseline bandwidth/normal traffic.
- Nginx/Apache sebagai target web server lab.
- SSH/SCP untuk mengambil hasil capture dari VM lab.
- Docker/Docker Compose untuk environment aplikasi.

AI providers:

- OpenAI-compatible provider.
- Groq/Llama.
- Gemini.
- Ollama.
- Provider disimpan server-side melalui dashboard pengaturan API.

## 4. File dan Modul Penting

Core scoring dan analisis:

- `app/Services/ScoringService.php`
  - Source of truth untuk feature building, radar score, final score, evidence gate, kategori, status eksperimen, dan final decision.
- `app/Services/AnalysisService.php`
  - Menjalankan korelasi data akuisisi + validasi, menghitung scoring, menyimpan `ExtractedFeature`, dan menyiapkan payload untuk AI.
- `config/tool_profiles.php`
  - Menyimpan profile serangan, bobot scoring, prompt rules, false-positive guards, dan chart metrics.
- `app/Services/ToolProfileService.php`
  - Normalisasi dan lookup tool profile aktif.

Parser data:

- `app/Services/AcquisitionParser.php`
  - Parser file akuisisi dari Wireshark/dumpcap/tshark.
  - Mendukung CSV, JSON summary, pcap/pcapng via tshark streaming bila tersedia, dan fallback metadata.
- `app/Services/ValidationParser.php`
  - Parser file validasi Snort.
  - Mengambil alert, severity, protocol, IP, port, classification, dan summary alert.

AI validation:

- `app/Services/AiPromptBuilder.php`
  - Lokasi utama prompt AI.
  - Membuat system prompt + user payload dengan schema JSON, evidence contract, prompt rules, dan false-positive guards.
- `app/Services/AiValidationService.php`
  - Mengirim payload ringkasan ke provider AI.
  - Menormalisasi response provider.
  - Memastikan AI tidak boleh override evidence gate.
  - Melakukan voting/summary multi-model.

Controller penting:

- `app/Http/Controllers/ExperimentController.php`
  - CRUD metadata eksperimen.
- `app/Http/Controllers/AcquisitionController.php`
  - Upload dan parse file akuisisi.
- `app/Http/Controllers/ValidationController.php`
  - Upload dan parse file validasi Snort.
- `app/Http/Controllers/AnalysisController.php`
  - Trigger analisis/scoring.
- `app/Http/Controllers/AiValidationController.php`
  - Trigger AI validation.
- `app/Http/Controllers/VisualizationController.php`
  - Dashboard grafik dan data visualisasi.
- `app/Http/Controllers/ComparisonController.php`
  - Perbandingan logic program vs AI analysis.
- `app/Http/Controllers/ReportController.php`
  - Generate report, PDF, dan export feature CSV.
- `app/Http/Controllers/EvaluationController.php`
  - Evaluasi klasifikasi terhadap ground truth.

Views penting:

- `resources/views/dashboard.blade.php`
- `resources/views/experiments/`
- `resources/views/acquisition/index.blade.php`
- `resources/views/validation/index.blade.php`
- `resources/views/analysis/index.blade.php`
- `resources/views/ai/index.blade.php`
- `resources/views/ai/show.blade.php`
- `resources/views/comparison/`
- `resources/views/visualization/index.blade.php`
- `resources/views/reports/`
- `resources/views/methodology/index.blade.php`
- `resources/views/lab/index.blade.php`

Dokumentasi penting:

- `docs/scoring-literature-analysis.md`
  - Analisis literatur skoring dan status bobot.
- `docs/lab-wireshark-snort-shell.md`
  - Alur lab Wireshark + Snort.
- `docs/manual-experiment-upload-validation.md`
  - Alur upload manual eksperimen, akuisisi, dan validasi.
- `docs/serangan-lab-index.md`
  - Index profil eksperimen defensif.
- `docs/audit-report-2026-05-29.md`
  - Audit teknis dan riwayat perbaikan.

## 5. Arsitektur Data

Entity utama:

| Model | Fungsi |
|---|---|
| `Experiment` | Metadata eksperimen: kode, nama, tanggal, scenario/profile, ground truth, status akhir |
| `AcquisitionFile` | File capture/summary Wireshark, dumpcap, tshark, CSV, JSON |
| `ValidationFile` | File validasi IDS/Snort |
| `SnortAlert` | Alert Snort hasil parsing |
| `ExtractedFeature` | Fitur numerik dan hasil scoring logic program |
| `AiResult` | Hasil validasi AI per provider/model |
| `FinalReport` | Laporan akhir eksperimen |
| `AuditLog` | Jejak aksi user dan perubahan penting |
| `AiProviderSetting` | Konfigurasi provider AI server-side |

Relasi konseptual:

1. Satu `Experiment` punya banyak `AcquisitionFile`.
2. Satu `Experiment` punya banyak `ValidationFile`.
3. `ValidationFile` menghasilkan banyak `SnortAlert`.
4. Satu `Experiment` punya satu `ExtractedFeature` aktif.
5. Satu `Experiment` bisa punya banyak `AiResult`.
6. Satu `Experiment` bisa punya report akhir.

## 6. Flow Mendapatkan Data

Flow umum data lab:

1. Siapkan lab terisolasi.
   - Target VM menjalankan web server lokal.
   - IDS Snort berjalan untuk memonitor traffic.
   - Packet capture berjalan memakai Wireshark/dumpcap/tshark.
   - Baseline normal dikumpulkan sebelum scenario attack profile.

2. Jalankan eksperimen defensif.
   - Pilih `tool_profile`: Slowloris, LOIC, HOIC, Hping3, Torshammer, atau Xerxes.
   - Tentukan metadata: tanggal, target IP, sumber, label skenario, ground truth.
   - Capture traffic dan alert Snort pada rentang waktu yang sama.

3. Hasilkan file bukti.
   - Akuisisi: `.pcap`, `.pcapng`, `.csv`, atau `.json`.
   - Validasi: Snort `.log`, `.txt`, `.csv`, atau `.json`.
   - Baseline: capture traffic normal dan/atau summary baseline.

4. Upload ke dashboard.
   - Buat `Experiment`.
   - Upload file akuisisi ke menu Acquisition.
   - Upload file Snort ke menu Validation.
   - Pastikan pair file sesuai eksperimen, waktu, target, dan profile.

5. Parsing.
   - `AcquisitionParser` mengekstrak ringkasan packet/flow.
   - `ValidationParser` mengekstrak alert Snort dan severity.
   - Hasil parser disimpan sebagai summary terstruktur.

6. Feature extraction.
   - `ScoringService::buildFeatures()` mengambil data akuisisi dan validasi yang relevan.
   - Fitur numerik dibangun untuk scoring.

7. Scoring.
   - `computeRadarScores()` menghitung skor per indikator.
   - `computeFinalScore()` menghitung weighted score berdasarkan `tool_profile`.
   - `evaluateExperiment()` menerapkan evidence gate.

8. AI validation.
   - `AnalysisService` membuat payload audit.
   - `AiPromptBuilder` membangun prompt provider-neutral.
   - `AiValidationService` meminta klasifikasi AI dan menormalisasi hasil.
   - AI hanya validator/pembanding, bukan sumber keputusan utama.

9. Visualisasi dan report.
   - Dashboard menampilkan skor, status, chart, heatmap, AI comparison.
   - Report PDF/CSV dibuat untuk arsip dan presentasi.

## 7. Flow Aplikasi dari UI

Urutan kerja user:

1. Login.
2. Buat eksperimen di `Experiments`.
3. Upload packet capture di `Acquisition`.
4. Upload Snort log di `Validation`.
5. Jalankan `Analysis`.
6. Cek hasil score dan evidence gate.
7. Jalankan `AI Analysis` bila provider tersedia.
8. Cek `Comparison` logic program vs AI.
9. Lihat `Visualization`.
10. Generate `Report`.
11. Export PDF/CSV untuk bukti.

Admin-only action:

- Upload/hapus data tertentu.
- Pengaturan API provider.
- Generate report.
- Aksi yang mengubah data penting memakai middleware `role:admin`.

## 8. Scoring: Konsep Dasar

`Final Attack Score` adalah output umum 0-100. Namun formula bobotnya tidak sama untuk semua profile. Bobot diambil dari `config/tool_profiles.php`.

Alur scoring:

1. Raw data dari acquisition + validation dikonversi menjadi feature.
2. Feature dihitung menjadi radar score per indikator.
3. Radar score dikalikan bobot profile.
4. Score dikategorikan.
5. Evidence gate mengecek apakah kategori kuat boleh dipakai.
6. Status akhir eksperimen ditentukan dari hasil setelah gate, bukan raw score saja.

Kategori umum:

| Score | Kategori umum |
|---|---|
| 0-30 | Normal |
| 31-55 | Suspicious |
| 56-75 | Possible profile |
| 76-100 | Strong profile indication / detected candidate |

Catatan penting:

- Score tinggi tidak otomatis berarti `attack_detected`.
- Evidence gate bisa menurunkan label ke `Suspicious` atau `Possible`.
- AI confidence bukan attack probability.
- Untuk AI, confidence berarti keyakinan model terhadap label yang dipilih.

## 9. Bobot Scoring Per Profile

Bobot saat ini:

### Slowloris

| Metric | Bobot |
|---|---:|
| `connection_duration_score` | 0.20 |
| `header_anomaly_score` | 0.20 |
| `low_bandwidth_high_connection_score` | 0.15 |
| `snort_alert_score` | 0.20 |
| `tcp_connection_score` | 0.10 |
| `baseline_deviation_score` | 0.10 |
| `ai_confidence_score` | 0.05 |

Interpretasi: profile ini menekankan koneksi lama, HTTP header/incomplete request, koneksi banyak dengan bandwidth rendah, dan validasi Snort.

### LOIC

| Metric | Bobot |
|---|---:|
| `packet_volume_score` | 0.20 |
| `connection_volume_score` | 0.20 |
| `throughput_pressure_score` | 0.15 |
| `http_volume_score` | 0.15 |
| `transport_flood_score` | 0.10 |
| `snort_alert_score` | 0.15 |
| `ai_confidence_score` | 0.05 |

Interpretasi: profile ini menekankan volume packet/koneksi/throughput dan indikasi HTTP/transport flood.

### HOIC

| Metric | Bobot |
|---|---:|
| `http_volume_score` | 0.25 |
| `connection_volume_score` | 0.20 |
| `packet_volume_score` | 0.15 |
| `throughput_pressure_score` | 0.15 |
| `snort_alert_score` | 0.20 |
| `ai_confidence_score` | 0.05 |

Interpretasi: profile ini paling menekankan HTTP volume dan validasi IDS.

### Hping3

| Metric | Bobot |
|---|---:|
| `transport_flood_score` | 0.25 |
| `packet_volume_score` | 0.20 |
| `connection_volume_score` | 0.15 |
| `snort_alert_score` | 0.25 |
| `baseline_deviation_score` | 0.10 |
| `ai_confidence_score` | 0.05 |

Interpretasi: profile ini menekankan flood layer transport/network seperti SYN/UDP/ICMP dan alert Snort.

### Torshammer

| Metric | Bobot |
|---|---:|
| `connection_duration_score` | 0.25 |
| `low_bandwidth_high_connection_score` | 0.20 |
| `header_anomaly_score` | 0.15 |
| `snort_alert_score` | 0.20 |
| `http_volume_score` | 0.10 |
| `ai_confidence_score` | 0.10 |

Interpretasi: mirip slow HTTP profile, tetapi bobot lebih besar pada durasi dan low-bandwidth behavior.

### Xerxes

| Metric | Bobot |
|---|---:|
| `connection_volume_score` | 0.25 |
| `packet_volume_score` | 0.20 |
| `http_volume_score` | 0.15 |
| `transport_flood_score` | 0.15 |
| `snort_alert_score` | 0.20 |
| `ai_confidence_score` | 0.05 |

Interpretasi: profile ini menekankan connection pressure, packet volume, HTTP volume, dan transport flood signal.

## 10. Status Akademik Bobot

Metric yang dipakai punya dasar literatur, tetapi angka bobot persis belum boleh diklaim sebagai angka yang diambil langsung dari jurnal tertentu.

Posisi metodologi yang aman:

- Feature selection: literature-supported.
- Bobot awal: heuristic berbasis literatur dan desain lab.
- Bobot final untuk skripsi: sebaiknya dikalibrasi dari dataset eksperimen.

Kalimat aman untuk laporan:

> Sistem menggunakan weighted composite score 0-100. Pemilihan fitur didasarkan pada literatur deteksi Slow HTTP DoS dan DDoS flow-based detection, sedangkan bobot awal ditetapkan sebagai heuristic berbasis profile serangan dan divalidasi terhadap dataset eksperimen lab.

Kalimat yang harus dihindari:

> Bobot 0.20, 0.15, dan seterusnya berasal dari jurnal X.

Kecuali jurnal tersebut memang memuat bobot yang sama dan cocok dengan metodologi project.

## 11. Metric Scoring dan Maknanya

| Metric | Makna | Sumber data |
|---|---|---|
| `connection_duration_score` | Durasi koneksi tinggi dibanding perilaku normal | Acquisition |
| `header_anomaly_score` | Indikasi request/header tidak selesai atau half-open behavior | Acquisition |
| `low_bandwidth_high_connection_score` | Banyak koneksi tetapi bandwidth rendah | Acquisition |
| `packet_volume_score` | Volume packet tinggi | Acquisition |
| `connection_volume_score` | Jumlah koneksi tinggi | Acquisition |
| `throughput_pressure_score` | Tekanan throughput/byte rate | Acquisition |
| `http_volume_score` | Volume HTTP tinggi | Acquisition |
| `transport_flood_score` | Indikasi flood TCP/UDP/ICMP | Acquisition |
| `snort_alert_score` | Bobot alert IDS berdasarkan severity/priority | Validation/Snort |
| `baseline_deviation_score` | Deviasi terhadap baseline normal | Acquisition + baseline |
| `ai_confidence_score` | Confidence AI terhadap labelnya | AI result |

## 12. Evidence Gating

Evidence gate adalah bagian penting karena score saja bisa menyesatkan. Gate memastikan bahwa label kuat hanya muncul jika bukti sesuai profile aktif.

Untuk Slowloris/Torshammer:

- Harus ada indikasi slow/incomplete request.
- Koneksi long-lived relevan.
- Low-bandwidth high-connection relevan.
- Alert Snort relevan memperkuat evidence.
- HTTP burst pendek tidak boleh langsung menjadi attack detected.

Untuk LOIC/HOIC/Xerxes:

- Fokus pada volume packet, connection, HTTP, throughput, atau transport sesuai profile.
- Tidak boleh memaksa bukti Slowloris seperti long-lived low-bandwidth jika profile aktif adalah flood.

Untuk Hping3:

- Fokus pada transport/network flood signal.
- Alert Snort dan deviasi baseline penting.

False-positive guards:

- Normal baseline.
- iPerf bandwidth test.
- HTTP burst.
- Portscan.
- TCP-dominant non-HTTP traffic.
- Missing Snort evidence pada kasus yang butuh validasi IDS.

## 13. AI Prompt dan Validasi

Prompt AI berada di:

- `app/Services/AiPromptBuilder.php`

Prompt ini menginstruksikan AI untuk:

1. Mengembalikan JSON only.
2. Memakai classification yang diizinkan.
3. Tidak mengarang IP, timestamp, packet count, connection count, rule name, atau field lain.
4. Mengikuti `evidence_contract`.
5. Tidak memberi label detected bila `detected_allowed` false.
6. Menjelaskan `supporting_indicators` dari field payload.
7. Mengisi `missing_evidence`.
8. Memahami bahwa confidence adalah confidence terhadap label, bukan attack probability.

Konfigurasi profile yang mempengaruhi prompt:

- `config/tool_profiles.php`
  - `prompt_rules`
  - `false_positive_guards`
  - `score_weights`
  - `attack_patterns`

Validator AI berada di:

- `app/Services/AiValidationService.php`

Logic penting di AI validator:

- AI menerima ringkasan fitur, bukan file mentah.
- API key tidak boleh bocor ke frontend.
- Response provider dinormalisasi ke schema internal.
- Jika AI mencoba memberi label detected saat evidence gate melarang, hasil diturunkan menjadi `Inconclusive` atau label non-detected.
- Multi-model voting hanya pembanding, bukan override terhadap scoring/evidence gate.

## 14. Kenapa AI Tidak Jadi Sumber Keputusan Utama

AI bisa membantu interpretasi, tetapi raw traffic dan IDS evidence tetap lebih audit-friendly. AI bisa hallucinate bila payload kurang lengkap. Karena itu:

- Logic scoring program tetap source of truth.
- Evidence gate tetap dipakai sebelum `attack_detected`.
- AI hanya validator/pembanding.
- Confidence AI tidak boleh dipakai sebagai bukti tunggal.
- Report harus menampilkan missing evidence dan gate reasons.

## 15. Cara Agar Data Valid

Checklist validitas data:

1. Pairing file benar.
   - Capture dan Snort log berasal dari eksperimen yang sama.
   - Waktu capture dan waktu alert saling overlap.
   - Target IP dan port sesuai metadata eksperimen.

2. Ground truth jelas.
   - Setiap eksperimen punya label ground truth.
   - Baseline normal dipisahkan dari scenario profile.
   - Profile aktif tidak ambigu.

3. Tool profile konsisten.
   - `experiment.tool_profile` sesuai jenis eksperimen.
   - Jangan upload data Hping3 ke profile Slowloris.
   - `analysis_profile_key` mengikuti profile aktif.

4. Capture cukup representatif.
   - Durasi capture cukup untuk menangkap baseline, skenario, dan cooldown.
   - Tidak terlalu pendek sehingga hanya menangkap spike.
   - Tidak bercampur eksperimen lain.

5. Snort rule dan log terdokumentasi.
   - Rule set dicatat.
   - Mode Snort dicatat.
   - Alert raw disimpan.
   - Severity/priority tidak diedit manual.

6. Baseline tersedia.
   - Minimal baseline normal browsing dan/atau iPerf dicatat.
   - Baseline harus diambil pada network/lab yang sama.
   - Baseline dipakai untuk membedakan anomaly dari kondisi normal.

7. Parser menghasilkan summary lengkap.
   - Total packets.
   - TCP packets.
   - HTTP packets.
   - Total connections.
   - Duration.
   - Throughput.
   - Snort alert count dan severity.
   - Dominant alert type.

8. Evidence gate dicek.
   - Report harus menunjukkan gate yang pass/fail.
   - Missing evidence tidak disembunyikan.
   - Keputusan akhir harus bisa dijelaskan dari field payload.

9. Data tidak dimanipulasi setelah upload.
   - Gunakan audit log.
   - Simpan original filename, stored filename, size, extension, parsed summary.
   - Hindari edit manual pada hasil parser kecuali dicatat sebagai reviewer note.

10. Evaluasi memakai metrik klasifikasi.
   - Accuracy.
   - Precision.
   - Recall.
   - F1-score.
   - Confusion matrix.
   - False-positive dan false-negative analysis.

## 16. Cara Memvalidasi Skoring untuk Skripsi

Langkah yang disarankan:

1. Buat dataset eksperimen.
   - Normal baseline.
   - Slowloris.
   - Torshammer.
   - LOIC.
   - HOIC.
   - Hping3.
   - Xerxes.
   - False-positive scenarios: iPerf, HTTP burst, portscan.

2. Untuk setiap eksperimen, simpan:
   - Metadata eksperimen.
   - Capture file.
   - Snort log.
   - Ground truth.
   - Tool profile.
   - Baseline reference.

3. Jalankan pipeline dashboard.

4. Export feature CSV.

5. Bandingkan hasil:
   - Ground truth vs logic classification.
   - Ground truth vs AI classification.
   - Logic vs AI.

6. Hitung confusion matrix.

7. Uji bobot.
   - Bobot heuristic saat ini.
   - Bobot hasil feature importance dari dataset.
   - Bobot hasil tuning sederhana.

8. Pilih bobot terbaik berdasarkan:
   - False positive rendah.
   - Recall attack profile tetap cukup.
   - Penjelasan evidence gate tetap masuk akal.

## 17. Kalibrasi Bobot yang Lebih Kuat

Bobot saat ini bagus sebagai starting point, tetapi untuk skripsi sebaiknya ada proses kalibrasi.

Metode yang bisa dipakai:

- Random Forest feature importance.
- Permutation importance.
- Information Gain.
- Logistic regression coefficient.
- Ablation study: matikan satu metric lalu lihat pengaruhnya.
- Expert weighting + experimental validation.

Output kalibrasi:

- Bobot per profile.
- Alasan tiap bobot.
- Perbandingan performa sebelum/sesudah kalibrasi.
- Dataset version.
- Tanggal kalibrasi.

Rekomendasi config ke depan:

```php
'weight_source' => [
    'type' => 'heuristic_literature_lab_validated',
    'dataset_version' => 'lab-dataset-v1',
    'calibrated_at' => 'YYYY-MM-DD',
    'references' => ['CICDDoS2019', 'Snort docs', 'Slow HTTP DoS papers'],
],
```

## 18. Logic Krusial yang Harus Dijaga

1. `ScoringService` tidak boleh dilewati.
   - Semua keputusan scoring harus lewat service ini.

2. AI tidak boleh override gate.
   - AI validator harus tetap menghormati `evidence_contract`.

3. File upload harus aman.
   - Extension dan MIME dicek.
   - Ukuran file dibatasi.
   - API key tidak pernah muncul di Blade, response JSON, log, report, atau export.

4. Parser harus robust.
   - File besar tidak boleh dimuat sembarangan ke memory.
   - PCAP besar lebih aman diproses streaming via tshark.
   - Fallback metadata tidak boleh dianggap bukti penuh.

5. Report harus audit-friendly.
   - Tampilkan final score.
   - Tampilkan raw category dan category setelah gate.
   - Tampilkan gate reasons.
   - Tampilkan missing evidence.
   - Tampilkan sumber file.

6. False positive harus dilindungi.
   - iPerf bukan attack.
   - HTTP burst bukan otomatis Slowloris.
   - Portscan bukan LOIC/HOIC/Xerxes.
   - Traffic TCP besar tanpa HTTP evidence tidak otomatis Slow HTTP.

## 19. Testing yang Ada

Test penting:

- `tests/Unit/ScoringServiceTest.php`
  - Menguji true positive dan false positive scoring.
  - Menguji evidence gate.
- `tests/Unit/AiValidationServiceTest.php`
  - Menguji response AI yang melanggar evidence contract harus diturunkan.
  - Menguji detected response hanya valid jika evidence contract mengizinkan.
- `tests/Unit/AiValidationVotingTest.php`
  - Menguji voting/aggregation AI.
- `tests/Unit/AcquisitionParserTest.php`
  - Menguji parser akuisisi.
- `tests/Unit/ValidationParserTest.php`
  - Menguji parser Snort.
- Feature/auth tests bawaan Laravel.

Command verifikasi:

```bash
php artisan test
php artisan test --filter=ScoringServiceTest
php artisan test --filter=AiValidationServiceTest
npm run build
```

## 20. Tools yang Diperlukan untuk Menjalankan Project

Development:

- PHP 8.2.
- Composer.
- Node.js dan npm.
- MySQL 8.4 atau Docker Compose.
- Git.

Laravel:

- `composer install`
- `npm install`
- `.env` dari `.env.example`
- `php artisan key:generate`
- `php artisan migrate --seed`
- `php artisan serve`
- `npm run dev`

Docker:

- Docker.
- Docker Compose.
- Service app + MySQL.

Lab:

- Wireshark.
- tshark.
- dumpcap.
- Snort 3.
- iPerf3.
- Nginx/Apache target lab.
- VM Ubuntu Server.
- SSH/SCP.

AI:

- Provider API key untuk provider live.
- Ollama lokal bila ingin model lokal.

## 21. Rujukan Literatur dan Sumber Teknis

Sumber yang sudah dicatat di `docs/scoring-literature-analysis.md`:

- CICDDoS2019, Canadian Institute for Cybersecurity: https://www.unb.ca/cic/datasets/ddos-2019.html
- A Proposed DoS Detection Scheme, Computers 2019: https://www.mdpi.com/2073-431X/8/4/85
- SDToW: A Slowloris Detecting Tool, Information 2020: https://www.mdpi.com/2078-2489/11/12/544
- Slow DoS Detection Framework PDF: https://oro.open.ac.uk/79542/2/GlobeCom%20Camera%20Ready.pdf
- Traffic Characteristics of Common DoS Tools PDF: https://www.fi.muni.cz/reports/files/2014/FIMU-RS-2014-02.pdf
- Snort priority docs: https://docs.snort.org/rules/options/general/priority
- Snort classtype docs: https://docs.snort.org/rules/options/general/classtype
- Wireshark User Guide: https://www.wireshark.org/docs/wsug_html_chunked/ChapterIntroduction.html

Cara memakai sumber ini dalam skripsi:

- Gunakan paper Slow HTTP/Slowloris untuk membenarkan metric duration, incomplete request/header anomaly, dan low-bandwidth high-connection.
- Gunakan DDoS dataset/traffic characterization untuk membenarkan packet volume, connection volume, throughput, transport flood, dan HTTP volume.
- Gunakan Snort docs untuk membenarkan severity/priority alert.
- Gunakan Wireshark docs untuk membenarkan packet capture sebagai sumber data.
- Jelaskan bahwa bobot numeric adalah heuristic/lab-calibrated, bukan standar universal.

## 22. Narasi Presentasi ke Senior

Narasi singkat:

> Project ini adalah dashboard analisis defensif untuk eksperimen Slow HTTP/DDoS di lab terisolasi. Sistem menggabungkan bukti dari packet capture Wireshark/dumpcap dan alert Snort, lalu mengekstrak fitur numerik untuk scoring per tool profile. Scoring tidak hanya berbasis angka, tetapi juga evidence gate agar false positive seperti iPerf, HTTP burst, portscan, dan baseline normal tidak salah menjadi attack detected. AI multi-model digunakan sebagai validator pembanding, bukan pengambil keputusan utama. Output akhirnya berupa visualisasi, comparison, audit log, dan report yang bisa dipakai untuk evaluasi penelitian.

Poin yang perlu ditekankan:

1. Sistem defensive, bukan attack automation.
2. Data harus paired: Wireshark + Snort + metadata eksperimen.
3. Scoring berbeda per tool profile.
4. Evidence gate mencegah keputusan gegabah.
5. AI tidak boleh override logic program.
6. Report dibuat audit-friendly.
7. Untuk skripsi, bobot akan diperkuat dengan kalibrasi dataset.

## 23. Rencana Pengembangan ke Depan

Prioritas tinggi:

1. Tambahkan `weight_source` pada `config/tool_profiles.php`.
2. Buat export dataset untuk kalibrasi bobot.
3. Tambahkan halaman confusion matrix dan F1-score.
4. Tambahkan validasi pairing waktu antara capture dan Snort log.
5. Tambahkan indikator kualitas data: capture duration, Snort coverage, baseline available, missing evidence.

Prioritas menengah:

1. Tambahkan calibration notebook/script terpisah.
2. Tambahkan provenance dataset dan versioning.
3. Tambahkan reviewer note per eksperimen.
4. Tambahkan report section khusus metodologi profile.
5. Tambahkan warning bila parser fallback metadata dipakai untuk scoring.

Prioritas penelitian:

1. Susun dataset eksperimen minimal 5-10 sampel per profile.
2. Susun baseline normal beberapa kondisi.
3. Hitung performa per profile.
4. Bandingkan scoring heuristic vs calibrated.
5. Tulis pembahasan false positive/false negative.
6. Jelaskan threat to validity.

## 24. Risiko dan Gap Saat Ini

1. Bobot belum sepenuhnya dataset-calibrated.
2. Kualitas scoring sangat bergantung pada kualitas capture dan Snort log.
3. Jika tshark tidak tersedia, parser pcap bisa fallback ke metadata sehingga evidence lebih lemah.
4. AI provider dapat menghasilkan output tidak konsisten, walaupun sudah dinormalisasi.
5. Dataset lab perlu cukup besar agar hasil evaluasi skripsi kuat.
6. Baseline harus konsisten; baseline yang buruk akan membuat `baseline_deviation_score` kurang valid.

## 25. Kesimpulan

Project ini sudah memiliki fondasi yang kuat sebagai dashboard penelitian defensif: ada data model, parser, scoring per profile, evidence gate, AI validation, visualisasi, audit trail, dan report. Bagian paling penting untuk dipertahankan adalah `ScoringService` sebagai sumber keputusan, `AiPromptBuilder` sebagai kontrak AI, dan validitas pairing Wireshark-Snort sebagai bukti utama.

Untuk presentasi magang, tekankan manfaat praktis: dashboard ini membantu reviewer melihat bukti, skor, gate, AI comparison, dan report tanpa membaca file mentah.

Untuk skripsi, fokus penguatan berikutnya adalah validasi dataset, kalibrasi bobot, evaluasi metrik klasifikasi, dan pembahasan false positive/false negative.
