# Prompt Pengisian Placeholder BAB I dari Database Proyek

## Tujuan

Prompt ini dijalankan di Codex pada repository `slowloris-attack` untuk:

1. membaca database aktif secara **read-only**;
2. menemukan seluruh placeholder pada paket BAB I;
3. mengisi placeholder yang benar-benar didukung data database;
4. mempertahankan placeholder yang belum memiliki bukti;
5. membedakan data laboratorium Ubuntu/VM dari data ESP32;
6. menghasilkan Markdown baru yang siap dipakai untuk revisi lanjutan di ChatGPT Browser.

## Berkas masukan

- Paket yang akan direvisi: `docs/paket-chatgpt-browser-bab-i-slowloris-esp32.md`
- Prompt awal: `docs/prompt-bab-i-skripsi-slowloris.md`
- Database proyek: gunakan database aktif Laravel; pada checkout saat prompt ini dibuat, snapshot lokal berada di `database/database.sqlite`.
- Sumber logika: `app/Services/ScoringService.php`, `app/Services/AnalysisService.php`, `app/Services/AiValidationService.php`, `app/Services/EvaluationMetricsService.php`, dan `config/tool_profiles.php`.

## Berkas keluaran

Codex wajib membuat:

`docs/revisi-placeholder-database-bab-i-slowloris-esp32.md`

---

## Prompt siap dijalankan di Codex

```text
Anda bekerja pada repository Laravel `slowloris-attack` dan bertugas mengisi placeholder dokumen skripsi menggunakan database aktif proyek secara read-only.

BERKAS TARGET

Baca seluruh berkas berikut:

1. docs/paket-chatgpt-browser-bab-i-slowloris-esp32.md
2. docs/prompt-bab-i-skripsi-slowloris.md
3. app/Services/ScoringService.php
4. app/Services/AnalysisService.php
5. app/Services/AiValidationService.php
6. app/Services/EvaluationMetricsService.php
7. config/tool_profiles.php
8. Model dan migration yang berkaitan dengan Experiment, AcquisitionFile, ValidationFile, SnortAlert, ExtractedFeature, AiResult, dan FinalReport.

TUJUAN UTAMA

Buat satu file Markdown:

docs/revisi-placeholder-database-bab-i-slowloris-esp32.md

File tersebut harus berisi versi revisi paket BAB I yang placeholder-nya telah diisi sejauh dapat dibuktikan oleh database aktif. Jangan memaksa semua placeholder terisi. Data yang tidak ada, tidak lengkap, berbeda lingkup, atau tidak membuktikan ESP32 harus tetap ditandai secara jelas.

BATAS KEAMANAN DAN INTEGRITAS DATA

1. Seluruh pemeriksaan database wajib read-only.
2. Jangan menjalankan migration, seeder, factory, update, insert, delete, truncate, atau perintah lain yang mengubah database.
3. Jangan mengubah source code aplikasi dalam tugas ini.
4. Jangan membuka atau menampilkan API key, password, cookie, token, session, atau isi rahasia `.env`.
5. Bila perlu memastikan koneksi aktif, tampilkan hanya driver, nama/path database yang aman, dan status koneksi. Jangan tampilkan credential.
6. Jangan menyalin `raw_request`, `raw_response`, `raw`, atau data panjang lain ke dokumen tanpa kebutuhan akademik yang jelas.
7. Jangan mengubah data VM/Ubuntu menjadi data ESP32.
8. Jangan menyatakan hasil simulasi sebagai hasil provider LLM nyata.
9. Jangan menggunakan nama file atau catatan sebagai bukti tunggal jika bertentangan dengan kolom, relasi, atau logika aplikasi.

LANGKAH 1 — PASTIKAN DATABASE AKTIF

1. Tentukan koneksi database yang benar-benar dipakai Laravel pada lingkungan saat ini.
2. Verifikasi bahwa tabel utama dapat dibaca.
3. Catat tanggal dan waktu pemeriksaan.
4. Catat schema migration yang telah diterapkan tanpa menjalankan migration baru.
5. Bandingkan schema aktif dengan Model dan migration. Laporkan kolom yang ada di kode tetapi belum ada di database.
6. Jika database aktif bukan `database/database.sqlite`, gunakan database aktif Laravel dan jelaskan sumbernya tanpa membuka credential.

LANGKAH 2 — INVENTARISASI PLACEHOLDER

Temukan semua placeholder di `docs/paket-chatgpt-browser-bab-i-slowloris-esp32.md`, termasuk pola seperti:

- [MODEL/VARIAN ESP32]
- [VERSI FRAMEWORK DAN FIRMWARE ESP32]
- [DAFTAR ENDPOINT HTTP ESP32]
- [TOPOLOGI LAB TERVERIFIKASI]
- [SPESIFIKASI KOMPUTER/VM AKUISISI DAN ANALISIS]
- [TITIK CAPTURE JARINGAN]
- [JUMLAH DAN KOMPOSISI DATASET]
- [DURASI DAN JUMLAH PENGULANGAN SKENARIO]
- [VERSI WIRESHARK/TSHARK]
- [VERSI DAN RULE SNORT]
- [PROVIDER DAN MODEL LLM]
- [HASIL CONFUSION MATRIX]
- [HASIL ACCURACY, PRECISION, RECALL, DAN F1-SCORE]
- [DAFTAR SKENARIO NORMAL DAN PEMBANDING TERVERIFIKASI]
- [PERLU VALIDASI PEMBIMBING]

Jangan hanya mencari daftar di atas. Deteksi seluruh teks di antara tanda kurung siku yang berfungsi sebagai placeholder, lalu bedakan dari nomor sitasi IEEE seperti [1], [2], dan seterusnya.

LANGKAH 3 — BANGUN LEDGER BUKTI DATABASE

Gunakan relasi antar-tabel berdasarkan `experiment_id`. Untuk setiap eksperimen, ambil hanya kolom yang relevan dan aman dari:

- experiments;
- acquisition_files;
- validation_files;
- snort_alerts;
- extracted_features;
- ai_results;
- final_reports.

Ledger minimal mencatat:

1. kode, nama, tanggal, status, traffic type, dan ground truth eksperimen;
2. source/target lab, interface, serta durasi jika tersedia;
3. data akuisisi yang dipilih oleh logika `ScoringService::selectAcquisition`, bukan gabungan sembarang seluruh file;
4. data validasi yang dipilih oleh `ScoringService::selectValidation`;
5. total packet, TCP packet, HTTP packet, koneksi, long-lived connection, durasi rata-rata, throughput, dan koneksi menuju port HTTP;
6. total alert, dominant alert, severity, kecocokan Slow HTTP, mode Snort, rule set, interface pemantauan, dan threshold;
7. final attack score, raw/final category, gate reasons, missing evidence, dan keputusan akhir yang tersedia;
8. provider/model AI, classification, confidence, dan status `is_simulated`;
9. laporan akhir dan keterbatasannya jika tersedia.

Jangan menghitung dengan mencampur acquisition atau validation yang tidak dipilih sistem. Jika `ExtractedFeature` lebih baru dan konsisten dengan hasil analisis terakhir, gunakan sebagai sumber hasil persistensi. Jika terdapat konflik antara data file, fitur persistensi, dan hasil laporan, tandai `CONFLICT` dan jelaskan tanpa memilih nilai secara sembarang.

LANGKAH 4 — KLASIFIKASI SETIAP PLACEHOLDER

Gunakan salah satu status berikut:

- `FILLED`: tersedia langsung, lingkupnya sesuai, dan memiliki sumber tabel/kolom yang jelas.
- `PARTIAL`: hanya sebagian informasi tersedia.
- `UNAVAILABLE`: tidak tersedia di database.
- `NOT_ESP32`: data tersedia, tetapi berasal dari VM/Ubuntu atau tidak membuktikan target ESP32.
- `SIMULATED_ONLY`: hanya tersedia pada data simulasi dan tidak boleh dianggap hasil provider/model nyata.
- `CONFLICT`: terdapat nilai yang bertentangan atau sumber yang tidak konsisten.
- `REQUIRES_CALCULATION`: dapat dihitung, tetapi harus melalui metode aplikasi yang benar.
- `REQUIRES_SUPERVISOR`: bukan fakta teknis database dan membutuhkan keputusan pembimbing.

Setiap status wajib memiliki:

1. nilai yang ditemukan;
2. tabel dan kolom sumber;
3. experiment ID/kode terkait;
4. alasan boleh atau tidak boleh dipakai;
5. bentuk teks pengganti yang aman untuk dokumen skripsi.

LANGKAH 5 — ATURAN PEMETAAN PLACEHOLDER

### A. Placeholder ESP32

Placeholder berikut hanya boleh diisi bila eksperimen memiliki bukti eksplisit bahwa target adalah ESP32, misalnya `target_platform = esp32_iot_web_server`, metadata firmware, endpoint, dan pasangan capture yang berasal dari objek tersebut:

- model/varian ESP32;
- firmware ESP32;
- endpoint HTTP ESP32;
- performa atau sumber daya ESP32;
- jumlah dataset ESP32;
- hasil klasifikasi ESP32.

Nama judul penelitian atau dokumen rencana bukan bukti eksperimen ESP32. Jika database tidak memiliki `target_platform`, jangan menebak dari IP, nama file, atau kata “IoT” pada laporan. Gunakan status `NOT_ESP32` atau `UNAVAILABLE`.

### B. Topologi dan capture

`experiments.source_ip`, `experiments.target_ip`, `experiments.network_interface`, `validation_files.monitoring_interface`, dan metadata file dapat dipakai untuk menjelaskan topologi data yang tersimpan. Namun:

- alamat IP tidak membuktikan jenis perangkat;
- interface `enp0s3` cenderung menunjukkan interface Linux/VM, bukan ESP32;
- judul eksperimen “Ubuntu Local” harus dipertahankan sebagai konteks Ubuntu/VM;
- gunakan istilah “topologi dataset laboratorium yang tersimpan” bila target ESP32 belum terbukti.

### C. Jumlah dan komposisi dataset

Hitung eksperimen berdasarkan tabel `experiments`, lalu kelompokkan menurut `traffic_type`, `ground_truth_label`, dan target platform bila kolom tersedia. Bedakan:

- jumlah eksperimen database secara keseluruhan;
- jumlah eksperimen berkualitas yang memiliki acquisition, validation, extracted feature, dan ground truth;
- jumlah eksperimen ESP32 yang benar-benar terverifikasi.

Jangan menyebut tiga eksperimen umum sebagai tiga eksperimen ESP32 jika target platform tidak terbukti.

### D. Durasi dan pengulangan

Durasi boleh diambil dari `experiments.capture_duration` atau `extracted_features.duration_seconds` jika konsisten. Jumlah baris eksperimen bukan otomatis jumlah pengulangan skenario. Pengulangan hanya boleh disebut jika metadata skenario, target, konfigurasi, dan protokol menunjukkan bahwa eksperimen merupakan repetition yang sebanding.

### E. Wireshark/TShark dan Snort

- Nama file, extension, parsed summary, dan metadata akuisisi dapat membuktikan data yang diimpor, tetapi tidak selalu membuktikan versi alat.
- `validation_files.snort_mode`, `rule_set`, `monitoring_interface`, `threshold`, `total_alerts`, `dominant_alert_type`, `highest_severity`, dan `matches_slow_http_pattern` dapat digunakan sesuai nilai aktual.
- Jangan mengarang versi Snort dari string `rule_set`.
- Jangan mengarang versi Wireshark/TShark jika database tidak menyimpannya.

### F. Provider dan model LLM

Periksa `ai_results.model_name`, `ai_results.model_version`, dan `ai_results.is_simulated`.

- Baris dengan `is_simulated = 1` wajib diberi label simulasi.
- Model bernama OpenAI, Gemini, Groq, atau Ollama tidak boleh disebut sebagai hasil provider nyata jika `is_simulated = 1`.
- Hanya baris `is_simulated = 0` yang dapat dipertimbangkan sebagai hasil panggilan provider nyata, setelah konsistensi payload, timestamp, dan batch/run diverifikasi.
- Jangan menyebut API key atau konfigurasi rahasia.

### G. Confusion matrix dan metrik

Jangan menghitung accuracy, precision, recall, atau F1 hanya dengan membandingkan string secara naif.

1. Gunakan `EvaluationMetricsService` sebagai definisi metrik proyek.
2. Pastikan setiap eksperimen memiliki ground truth yang dapat dipetakan ke kelas evaluasi.
3. Gunakan hasil keputusan rule-based/final decision yang sesuai, bukan setiap baris AI sebagai sampel terpisah.
4. Jangan menghitung beberapa `ai_results` dari eksperimen yang sama sebagai eksperimen independen.
5. Pisahkan evaluasi rule-based, evaluasi AI, dan evaluasi keputusan akhir bila tersedia.
6. Jika dataset terlalu kecil, label target tidak seimbang, atau kelas `mixed` tidak dapat dipetakan secara sah, tampilkan hasil sebagai evaluasi awal dengan keterbatasan yang tegas atau beri status `REQUIRES_CALCULATION`.
7. Jangan memasukkan metrik awal ke BAB I sebagai hasil final. Letakkan pada bagian audit data atau bahan BAB IV.

LANGKAH 6 — KONTEKS SNAPSHOT YANG HARUS DIVERIFIKASI ULANG

Pada saat prompt ini dibuat, database lokal menunjukkan:

- 3 eksperimen: satu `slowloris_lab`, satu `normal`, dan satu `mixed`;
- nama eksperimen utama menyebut Ubuntu Local, bukan ESP32;
- durasi tersimpan 600, 600, dan 720 detik;
- interface eksperimen dan monitoring yang tersimpan adalah `enp0s3`;
- 3 validation files dan 131 Snort alerts;
- hasil fitur akhir masing-masing berkategori `Strong Slowloris Indication`, `Normal`, dan `Suspicious`;
- 17 `ai_results`, seluruhnya bertanda `is_simulated = 1`;
- schema database aktif belum memiliki kolom `experiments.target_platform`, walaupun Model dan migration mengenal kolom tersebut.

Snapshot ini hanya petunjuk awal. Query ulang database saat tugas dijalankan. Jika nilainya berubah, gunakan keadaan terbaru dan catat perbedaannya.

LANGKAH 7 — REVISI DOKUMEN

Buat `docs/revisi-placeholder-database-bab-i-slowloris-esp32.md` dengan struktur berikut:

# Revisi Placeholder BAB I Berdasarkan Database

## 1. Identitas Snapshot Database

Cantumkan waktu pemeriksaan, driver, database aman, jumlah tabel relevan, dan status migration. Jangan cantumkan credential.

## 2. Kesimpulan Kelayakan Data

Jelaskan secara langsung:

- data apa yang dapat dipakai;
- data apa yang hanya mewakili VM/Ubuntu;
- data apa yang simulated-only;
- data apa yang masih harus dikumpulkan dari ESP32 fisik.

## 3. Ledger Eksperimen

Buat tabel ringkas per eksperimen. Jangan menyalin raw payload.

## 4. Matriks Pengisian Placeholder

Gunakan kolom:

| Placeholder | Status | Nilai Database | Sumber | Teks Pengganti Aman | Catatan |

## 5. Prompt Revisi untuk ChatGPT Browser

Buat satu code block berisi prompt mandiri untuk ChatGPT Browser. Prompt tersebut harus:

1. meminta ChatGPT membaca DOCX acuan, paket BAB I awal, dan file revisi database ini;
2. mengganti placeholder hanya menggunakan kolom `Teks Pengganti Aman` berstatus `FILLED` atau bagian sah dari `PARTIAL`;
3. mempertahankan label belum tersedia untuk `UNAVAILABLE`, `NOT_ESP32`, `SIMULATED_ONLY`, `CONFLICT`, `REQUIRES_CALCULATION`, dan `REQUIRES_SUPERVISOR`;
4. tidak mengubah data Ubuntu/VM menjadi ESP32;
5. tidak memasukkan hasil awal database sebagai hasil final BAB I;
6. mengembalikan Markdown lengkap yang siap ditinjau pengguna.

## 6. BAB I Hasil Revisi Berbasis Database

Salin BAB I dari paket awal dan lakukan penggantian secara konservatif:

- isi data yang sah;
- gunakan frasa “berdasarkan snapshot database laboratorium saat ini” untuk data non-ESP32 yang relevan;
- gunakan `[BELUM TERSEDIA DI DATABASE: alasan]` untuk data yang belum ada;
- gunakan `[DATA TERSEDIA TETAPI BUKAN ESP32: ringkasan]` untuk data VM/Ubuntu;
- gunakan `[HANYA DATA SIMULASI: ringkasan]` untuk AI simulated-only;
- jangan mengubah nomor sitasi atau daftar pustaka kecuali diperlukan karena kalimat berubah.

## 7. Data yang Tetap Harus Dikumpulkan dari ESP32

Buat checklist operasional tanpa langkah serangan. Minimal mencakup model board, firmware, endpoint, topologi, titik capture, baseline normal, dataset Slowloris terkontrol, skenario pembanding, pengulangan, versi alat, ground truth, dan bukti evaluasi.

## 8. Catatan Perubahan

Daftar setiap placeholder yang diubah beserta sumbernya. Jangan hanya menulis “sudah direvisi”.

ATURAN GAYA PENULISAN

1. Gunakan bahasa Indonesia formal dan jelas.
2. Pisahkan fakta database, interpretasi, dan kebutuhan data.
3. Jangan menghapus judul ESP32; ESP32 tetap objek penelitian akhir.
4. Jangan membuat pembaca mengira database Ubuntu/VM adalah dataset ESP32.
5. Gunakan istilah “data awal”, “snapshot database”, atau “dataset laboratorium tersimpan” untuk data yang belum mewakili objek akhir.
6. Jangan mengklaim hasil sempurna, akurasi tinggi, siap produksi, atau berlaku universal.
7. Jangan menambahkan otomasi serangan, payload, perintah serangan, evasion, atau target publik.

VALIDASI WAJIB SEBELUM SELESAI

Pastikan:

- database tidak berubah;
- seluruh placeholder telah masuk matriks;
- setiap nilai terisi memiliki sumber tabel dan kolom;
- tidak ada data simulated yang disebut sebagai hasil LLM nyata;
- tidak ada data Ubuntu/VM yang disebut sebagai hasil ESP32;
- metrik tidak dihitung dari duplikasi `ai_results`;
- output berupa satu file Markdown;
- prompt ChatGPT Browser dapat dipakai tanpa akses langsung ke database;
- daftar data ESP32 yang masih kosong tetap terlihat jelas.

Pada jawaban akhir Codex, tampilkan hanya path file keluaran, ringkasan jumlah placeholder per status, dan peringatan utama tentang bukti ESP32.
```

## Catatan penting

Prompt ini sengaja tidak memerintahkan semua placeholder harus terisi. Database yang berisi data tidak otomatis membuktikan bahwa data tersebut sesuai dengan objek penelitian ESP32. Hasil yang kuat adalah hasil yang dapat ditelusuri, bukan hasil yang terlihat lengkap tetapi salah konteks.
