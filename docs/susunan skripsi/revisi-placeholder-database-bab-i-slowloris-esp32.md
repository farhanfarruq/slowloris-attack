# Revisi Placeholder BAB I Berdasarkan Database

## 1. Identitas Snapshot Database

| Item | Hasil pemeriksaan |
|---|---|
| Waktu pemeriksaan | 2 Agustus 2026, 01.55 WIB |
| Driver aktif Laravel | MySQL |
| Database aktif | `slowloris_lab` |
| Cara akses | Laravel di container aplikasi; query `SELECT`/read-only |
| Jumlah migration tercatat | 19 |
| Migration metadata profil/target | `2026_06_17_000000_add_tool_profile_analysis_fields`, batch 5, sudah diterapkan |
| Tabel eksperimen | 13 baris |
| Tabel akuisisi | 17 baris |
| Tabel validasi | 17 baris |
| Tabel alert Snort | 10.056 baris |
| Tabel extracted features | 13 baris |
| Tabel AI results | 152 baris |
| Tabel final reports | 2 baris |
| Perubahan database oleh pemeriksaan | Tidak ada; seluruh operasi berupa pembacaan |

### Catatan sumber database

Konfigurasi Laravel pada host menunjuk ke MySQL `slowloris_lab`. Koneksi langsung dari host gagal karena hostname `mysql` hanya dapat diselesaikan di jaringan Docker, sehingga pembacaan dilakukan melalui container aplikasi yang terhubung ke database aktif. Berkas `database/database.sqlite` bukan sumber utama untuk revisi ini karena hanya merupakan snapshot lokal yang berbeda dari keadaan runtime.

## 2. Kesimpulan Kelayakan Data

Database aktif menyediakan data nyata untuk mengisi sebagian konteks metodologi dan evaluasi awal, tetapi belum menyediakan data ESP32. Seluruh 13 eksperimen memiliki `target_platform = vm_ubuntu_server`; tidak ada baris dengan `target_platform = esp32_iot_web_server`. Delapan eksperimen memakai profil Slowloris, sedangkan lima eksperimen lain memakai profil LOIC, HOIC, Hping3, Torshammer, dan Xerxes.

Data VM yang dapat dipakai secara sah meliputi kode eksperimen, skenario, alamat jaringan laboratorium, interface, durasi sebagian eksperimen, pasangan acquisition–validation yang dipilih aplikasi, fitur jaringan, hasil scoring, gate reasons, konfigurasi Snort yang tersimpan, hasil AI, dan metrik evaluasi. Data tersebut hanya dapat disebut sebagai **snapshot dataset laboratorium VM** atau **data awal untuk validasi kerangka**, bukan sebagai hasil pengujian web server IoT ESP32.

Database belum menyediakan model ESP32, firmware, endpoint HTTP ESP32, topologi ESP32, spesifikasi CPU/RAM komputer analisis, versi Wireshark/TShark, versi Snort, maupun pengulangan eksperimen ESP32. Informasi tersebut tetap harus dikumpulkan dari perangkat dan protokol eksperimen aktual.

Berbeda dari snapshot SQLite lama, database MySQL aktif memuat 152 `ai_results` dan seluruhnya bertanda `is_simulated = 0`. Empat konfigurasi provider sedang memiliki `use_live_api = 1`, sedangkan NVIDIA Nemotron Nano memiliki `use_live_api = 0`. Walaupun demikian, hasil AI tersebut tetap berasal dari eksperimen VM, bukan ESP32.

## 3. Ledger Eksperimen

### 3.1 Komposisi target dan profil

| Target platform | Profil | Jumlah |
|---|---|---:|
| `vm_ubuntu_server` | Slowloris | 8 |
| `vm_ubuntu_server` | LOIC | 1 |
| `vm_ubuntu_server` | HOIC | 1 |
| `vm_ubuntu_server` | Hping3 | 1 |
| `vm_ubuntu_server` | Torshammer | 1 |
| `vm_ubuntu_server` | Xerxes | 1 |
| ESP32 IoT web server | Semua profil | **0** |

### 3.2 Eksperimen dan data yang dipilih aplikasi

Pemilihan acquisition menggunakan `ScoringService::selectAcquisition()` dan pemilihan validation menggunakan `ScoringService::selectValidation()`. Nilai pada tabel berikut tidak mencampur seluruh file secara sembarang.

| Kode | Profil/skenario | Target | Durasi | Ground truth | Acquisition/validation terpilih | Skor dan kategori | AI nyata/simulasi |
|---|---|---|---:|---|---|---|---:|
| EXP-001 | Slowloris / `slow-http` | VM Ubuntu | 100 dtk | `slowloris_lab` | 45 / 42 | 4,50 — Normal | 36 / 0 |
| EXP-002 | Slowloris / `iperf-bandwidth` | VM Ubuntu | 45 dtk | `normal` | 42 / 33 | 21,93 — Normal | 2 / 0 |
| EXP-003 | Slowloris / `http-burst` | VM Ubuntu | 45 dtk | `normal` | 43 / 34 | 34,50 — Suspicious | 6 / 0 |
| EXP-004 | Slowloris / `portscan` | VM Ubuntu | 45 dtk | `mixed` | 44 / 35 | 1,33 — Normal | 4 / 0 |
| EXP-VM-LONG-183401 | Slowloris / belum diisi | VM Ubuntu | 150 dtk | `slowloris_lab` | 49 / 43 | 43,72 — Suspicious | 2 / 0 |
| EXP-VM-DETECTED-190152 | Slowloris / `slow-http` | VM Ubuntu | 60 dtk | `slowloris` | 51 / 44 | 85,51 — Strong Slowloris Indication | 6 / 0 |
| EXP-038 | Slowloris / `slow-http` | VM Ubuntu | 85 dtk | `slowloris_lab` | 53 / 49 | 83,71 — Strong Slowloris Indication | 16 / 0 |
| EXP-039 | Slowloris / `vm-slowloris-pending` | VM Ubuntu | Belum diisi | `slowloris` | 69 / 63 | 67,88 — Possible Slowloris | 24 / 0 |
| EXP-040 | LOIC / `vm-loic-pending` | VM Ubuntu | Belum diisi | `loic` | 55 / 50 | 95,00 — Strong LOIC Indication | 21 / 0 |
| EXP-041 | HOIC / `vm-hoic-pending` | VM Ubuntu | Belum diisi | `hoic` | 56 / 51 | 95,00 — Strong HOIC Indication | 9 / 0 |
| EXP-042 | Hping3 / `vm-hping3-pending` | VM Ubuntu | Belum diisi | `hping3` | 64 / 55 | 87,50 — Strong Hping3 Indication | 14 / 0 |
| EXP-043 | Torshammer / `vm-torshammer-pending` | VM Ubuntu | Belum diisi | `torshammer` | 66 / 57 | 45,37 — Suspicious | 10 / 0 |
| EXP-044 | Xerxes / `vm-xerxes-pending` | VM Ubuntu | Belum diisi | `xerxes` | 67 / 58 | 95,00 — Strong Xerxes Indication | 2 / 0 |

### 3.3 Detail dataset profil Slowloris

Tujuh dari delapan eksperimen Slowloris memiliki durasi tersimpan: 100, 45, 45, 45, 150, 60, dan 85 detik. EXP-039 belum memiliki durasi pada metadata eksperimen. Nilai tersebut tidak dapat disebut sebagai tujuh pengulangan karena skenario, ground truth, dan kondisi eksperimennya berbeda.

Pada tujuh eksperimen Slowloris yang memiliki metadata jaringan, interface yang tersimpan adalah `enp0s8`. Enam eksperimen menggunakan sumber `192.168.56.102` menuju target `192.168.56.103`, sedangkan EXP-038 menggunakan sumber `192.168.56.1` menuju target yang sama. EXP-039 belum memiliki source IP, target IP, dan interface pada tabel `experiments`. Pola ini mendokumentasikan jaringan VM host-only, tetapi tidak membuktikan titik capture fisik atau target ESP32.

### 3.4 Konfigurasi Snort yang tersimpan

Seluruh 17 validation files menggunakan mode `ids`. Tiga baris menyimpan rule set `local lab rules` dengan interface `enp0s8`; satu baris menyimpan `local-vm-lab`, satu `testing`, satu `t`, dan sebelas tidak menyimpan nama rule set maupun interface. Database tidak menyimpan nomor versi Snort, sehingga versi tidak boleh ditebak dari mode atau rule set.

### 3.5 Provider dan model AI

| Provider key | Model | Status API saat snapshot |
|---|---|---|
| `groq` | `llama-3.3-70b-versatile` | Live aktif |
| `openai` | `openai/gpt-4o-mini` | Live aktif |
| `groq_gpt_oss_120b` | `openai/gpt-oss-120b` | Live aktif |
| `mistral_large` | `mistral-large-latest` | Live aktif |
| `nvidia_nemotron_nano` | `openai/gpt-oss-20b` | Live tidak aktif |

Distribusi 152 hasil AI non-simulasi berdasarkan `model_version` adalah 64 Llama 3.3 70B Versatile, 57 GPT-4o Mini, 14 GPT-OSS 120B, 14 Mistral Large, dan 3 GPT-OSS 20B. API key tidak dibaca atau ditampilkan.

### 3.6 Evaluasi resmi aplikasi

`EvaluationMetricsService` menghasilkan metrik berikut dari 13 eksperimen seluruh profil:

| Lingkup | TP | TN | FP | FN | PM | Total | Accuracy | Precision | Recall | F1-score |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| Seluruh profil | 6 | 1 | 1 | 5 | 0 | 13 | 53,85% | 85,71% | 54,55% | 66,67% |
| Profil Slowloris | 2 | 1 | 1 | 4 | 0 | 8 | 37,50% | 66,67% | 33,33% | 44,44% |

Metrik tersebut berstatus **NOT_ESP32** karena seluruh sampelnya bertarget VM Ubuntu. Nilainya adalah evaluasi awal database aktif, bukan hasil akhir penelitian ESP32 dan tidak dimasukkan sebagai klaim hasil pada BAB I.

## 4. Matriks Pengisian Placeholder

| Placeholder | Status | Nilai database | Sumber | Teks pengganti aman | Catatan |
|---|---|---|---|---|---|
| `[MODEL/VARIAN ESP32]` | UNAVAILABLE | Tidak ada eksperimen ESP32 | `experiments.target_platform` | `[BELUM TERSEDIA DI DATABASE: model/varian ESP32]` | Seluruh 13 baris adalah VM Ubuntu |
| `[VERSI FRAMEWORK DAN FIRMWARE ESP32]` | UNAVAILABLE | Tidak ada kolom/metadata firmware | Seluruh tabel relevan | `[BELUM TERSEDIA DI DATABASE: framework dan versi firmware ESP32]` | Harus diambil dari source firmware/build |
| `[DAFTAR ENDPOINT HTTP ESP32]` | UNAVAILABLE | Tidak tersedia | Seluruh tabel relevan | `[BELUM TERSEDIA DI DATABASE: endpoint HTTP ESP32]` | Harus diambil dari handler firmware |
| `[TOPOLOGI LAB TERVERIFIKASI]` | NOT_ESP32 | Topologi VM: sumber `.102`/`.1`, target `.103`, interface `enp0s8` | `experiments` | `[DATA TERSEDIA TETAPI BUKAN ESP32: topologi VM host-only menuju 192.168.56.103 melalui enp0s8]` | IP tidak membuktikan jenis target |
| `[SPESIFIKASI KOMPUTER/VM AKUISISI DAN ANALISIS]` | PARTIAL | `target_platform=vm_ubuntu_server`; interface `enp0s8` | `experiments` | `[TERISI SEBAGIAN: VM Ubuntu dan interface enp0s8; CPU, RAM, dan versi OS belum tersedia]` | Database tidak menyimpan spesifikasi hardware |
| `[TITIK CAPTURE JARINGAN]` | PARTIAL | `enp0s8` pada tujuh eksperimen Slowloris; sebagian validation tidak mengisi interface | `experiments.network_interface`, `validation_files.monitoring_interface` | `[TERISI SEBAGIAN: interface enp0s8; posisi fisik titik capture belum terdokumentasi]` | Tidak membuktikan traffic ESP32 |
| `[JUMLAH DAN KOMPOSISI DATASET]` | NOT_ESP32 | 13 eksperimen: 8 Slowloris dan 5 profil lain; 0 ESP32 | `experiments` | `[DATA TERSEDIA TETAPI BUKAN ESP32: 13 eksperimen VM, terdiri atas 8 Slowloris dan 5 profil lain; dataset ESP32=0]` | Komposisi Slowloris mencakup attack, baseline, dan mixed |
| `[DURASI DAN JUMLAH PENGULANGAN SKENARIO]` | PARTIAL | Durasi Slowloris: 100, 45, 45, 45, 150, 60, 85 detik; satu null | `experiments.capture_duration` | `[TERISI SEBAGIAN DAN BUKAN ESP32: tujuh durasi VM tersedia; jumlah pengulangan sebanding belum terdokumentasi]` | Baris berbeda bukan otomatis repetition |
| `[VERSI WIRESHARK/TSHARK]` | UNAVAILABLE | Semua acquisition berformat `pcapng`, tetapi versi alat tidak disimpan | `acquisition_files.extension` | `[BELUM TERSEDIA DI DATABASE: versi Wireshark/TShark]` | Format file tidak membuktikan versi |
| `[VERSI DAN RULE SNORT]` | PARTIAL | Mode IDS; beberapa rule set tersimpan | `validation_files` | `[TERISI SEBAGIAN: Snort mode IDS; rule set local lab rules/local-vm-lab/testing; versi Snort belum tersedia]` | Rule set `t` perlu dibersihkan/ditinjau |
| `[PROVIDER DAN MODEL LLM]` | FILLED | Empat konfigurasi live aktif dan satu nonaktif; 152 hasil non-simulasi | `ai_provider_settings`, `ai_results` | `Groq Llama 3.3 70B Versatile, OpenAI-compatible GPT-4o Mini, Groq GPT-OSS 120B, dan Mistral Large; seluruh hasil tersimpan berstatus non-simulasi` | Semua hasil masih terkait eksperimen VM |
| `[HASIL CONFUSION MATRIX]` | NOT_ESP32 | Global 6/1/1/5; Slowloris 2/1/1/4 | `EvaluationMetricsService` | `[DATA TERSEDIA TETAPI BUKAN ESP32: confusion matrix Slowloris VM TP=2, TN=1, FP=1, FN=4]` | Jangan dipakai sebagai hasil final ESP32 |
| `[HASIL ACCURACY, PRECISION, RECALL, DAN F1-SCORE]` | NOT_ESP32 | Slowloris: 37,50%; 66,67%; 33,33%; 44,44% | `EvaluationMetricsService` | `[DATA TERSEDIA TETAPI BUKAN ESP32: metrik awal Slowloris VM 37,50/66,67/33,33/44,44 persen]` | Dataset hanya delapan sampel VM |
| `[PERLU VALIDASI PEMBIMBING]` | REQUIRES_SUPERVISOR | Tidak dapat berasal dari database | N/A | `[PERLU VALIDASI PEMBIMBING]` | Pertahankan sampai ada keputusan pembimbing |
| `[SITASI TERVERIFIKASI DIPERLUKAN]` | UNAVAILABLE | Database bukan sumber literatur | N/A | `[SITASI TERVERIFIKASI DIPERLUKAN]` | Verifikasi melalui browser/reference manager |
| `[DAFTAR SKENARIO NORMAL DAN PEMBANDING TERVERIFIKASI]` | NOT_ESP32 | iPerf baseline dan HTTP burst ber-ground-truth normal; portscan ber-label mixed | `experiments.scenario_key`, `ground_truth_label` | `[DATA TERSEDIA TETAPI BUKAN ESP32: iPerf bandwidth dan HTTP burst sebagai normal; portscan sebagai mixed]` | Perlu dikumpulkan ulang pada ESP32 |

### Ringkasan status

| Status | Jumlah |
|---|---:|
| FILLED | 1 |
| PARTIAL | 4 |
| UNAVAILABLE | 5 |
| NOT_ESP32 | 5 |
| REQUIRES_SUPERVISOR | 1 |
| SIMULATED_ONLY | 0 |
| CONFLICT | 0 |
| REQUIRES_CALCULATION | 0 |
| **Total** | **16** |

## 5. Prompt Revisi untuk ChatGPT Browser

Lampirkan berkas berikut saat menggunakan prompt ini:

1. `DRAFT SKRIPSI PLTP RIZKY FAIHAQI.docx`
2. `paket-chatgpt-browser-bab-i-slowloris-esp32.md`
3. `revisi-placeholder-database-bab-i-slowloris-esp32.md`

```text
Anda bertindak sebagai penyunting akademik untuk BAB I skripsi keamanan jaringan dan IoT.

Saya melampirkan:

1. DOCX acuan struktur tugas akhir;
2. paket awal BAB I Slowloris–ESP32;
3. hasil audit database aktif dan BAB I revisi berbasis database.

Judul penelitian harus tetap:

“A Hybrid Network Forensics and Large Language Model Framework for Explainable Analysis of Slowloris Slow HTTP Request Attacks on ESP32 IoT Web Servers”.

Tugas Anda adalah menyempurnakan bagian “BAB I Hasil Revisi Berbasis Database” menjadi Markdown akademik yang jelas dan siap ditinjau pengguna.

ATURAN SUMBER DATA

1. Gunakan hanya `Teks pengganti aman` pada Matriks Pengisian Placeholder.
2. Nilai berstatus FILLED boleh dipakai sebagai fakta database.
3. Nilai PARTIAL hanya boleh dipakai bersama keterbatasannya.
4. Nilai NOT_ESP32 harus disebut sebagai data awal VM dan tidak boleh diubah menjadi hasil ESP32.
5. Nilai UNAVAILABLE harus tetap ditandai belum tersedia.
6. Nilai REQUIRES_SUPERVISOR tetap membutuhkan validasi pembimbing.
7. Jangan mengarang model ESP32, firmware, endpoint, topologi ESP32, versi alat, pengulangan, atau hasil ESP32.
8. Jangan memakai data SQLite lama; sumber audit ini adalah MySQL aktif `slowloris_lab`.
9. Jangan menyebut metrik VM sebagai hasil akhir penelitian ESP32.
10. Jangan menyebut beberapa AI result dari satu eksperimen sebagai sampel eksperimen independen.

FAKTA DATABASE YANG BOLEH DIGUNAKAN

- Database aktif berisi 13 eksperimen dan seluruhnya bertarget `vm_ubuntu_server`.
- Delapan eksperimen menggunakan profil Slowloris; tidak ada eksperimen ESP32.
- Dataset Slowloris VM memuat skenario slow-http, iPerf bandwidth, HTTP burst, portscan, long-lived evidence, dan draft VM.
- Tujuh durasi Slowloris yang tersedia adalah 100, 45, 45, 45, 150, 60, dan 85 detik; satu eksperimen belum memiliki durasi.
- Interface eksperimen yang dominan adalah `enp0s8`; data ini tidak membuktikan titik capture fisik ESP32.
- Seluruh 17 validation files memakai Snort mode IDS, tetapi versi Snort tidak disimpan.
- Database memuat 152 hasil AI dengan `is_simulated=0`.
- Konfigurasi live aktif mencakup Groq Llama 3.3 70B Versatile, GPT-4o Mini melalui driver OpenAI-compatible, Groq GPT-OSS 120B, dan Mistral Large.
- Metrik resmi aplikasi untuk profil Slowloris VM adalah TP=2, TN=1, FP=1, FN=4, accuracy=37,50%, precision=66,67%, recall=33,33%, dan F1-score=44,44%. Data ini hanya bahan evaluasi awal VM.

ATURAN BAB I

1. Pertahankan struktur: Latar Belakang, Rumusan Masalah, Batasan Masalah, Tujuan Tugas Akhir, Manfaat Tugas Akhir, dan Sistematika Penulisan.
2. ESP32 tetap menjadi objek akhir penelitian.
3. Jelaskan bahwa database sekarang berisi dataset VM pendahuluan untuk menguji alur analisis, sedangkan dataset ESP32 masih harus dikumpulkan.
4. Jangan menaruh angka metrik VM seolah-olah sebagai hasil ESP32 atau kesimpulan penelitian.
5. BAB I boleh menyebut keberadaan data awal VM untuk menjelaskan status penelitian, tetapi pembahasan hasil rinci diarahkan ke BAB IV.
6. Pertahankan sitasi IEEE dan verifikasi kembali daftar pustaka melalui browser.
7. Jangan menyalin isi PLTP/CFD dari DOCX.
8. Jangan menambahkan perintah, payload, atau langkah serangan.
9. Gunakan bahasa Indonesia formal, objektif, dan tidak berlebihan.

OUTPUT

Kembalikan Markdown saja dengan urutan:

1. Judul bahasa Inggris dan bahasa Indonesia.
2. BAB I lengkap, subbab 1.1–1.6.
3. Daftar pustaka IEEE yang telah diverifikasi.
4. Daftar data ESP32 yang masih harus dikumpulkan.
5. Ringkasan perubahan dari draft database.

Sebelum selesai, pastikan tidak ada data VM yang disebut ESP32 dan tidak ada placeholder kosong yang disamarkan sebagai fakta.
```

## 6. BAB I Hasil Revisi Berbasis Database

# BAB I

# PENDAHULUAN

## 1.1 Latar Belakang

Perkembangan Internet of Things (IoT) mendorong semakin banyak perangkat tertanam menyediakan layanan berbasis jaringan untuk pemantauan, kendali, dan pertukaran data. ESP32 merupakan salah satu keluarga mikrokontroler yang mengintegrasikan konektivitas nirkabel dan dapat digunakan untuk menjalankan web server ringan. Dokumentasi resmi ESP-IDF menjelaskan bahwa komponen ESP HTTP Server menyediakan layanan HTTP pada ESP32, membuat listening socket untuk menerima traffic, mengalokasikan sumber daya sesuai konfigurasi, dan memanggil handler berdasarkan URI yang diminta [1]. Kemampuan tersebut membuat ESP32 relevan sebagai objek penelitian keamanan web server IoT, terutama karena layanan harus tetap tersedia ketika diakses oleh klien jaringan, sementara kapasitas perangkatnya tidak setara dengan server konvensional.

Layanan HTTP berjalan di atas koneksi transport dan dapat memanfaatkan koneksi persisten. RFC 9110 menjelaskan semantik HTTP modern, termasuk penggunaan koneksi persisten sebagai perilaku bawaan pada implementasi HTTP tertentu [2]. Karakter ini bermanfaat untuk mengurangi pembentukan koneksi berulang, tetapi juga membentuk permukaan yang perlu diperhatikan ketika klien mempertahankan koneksi dalam waktu lama. Pada serangan Slowloris atau Slow HTTP Request, koneksi ke layanan HTTP dipertahankan melalui pengiriman request atau header yang lambat dan belum lengkap. Berbeda dengan HTTP flood yang mengandalkan volume request tinggi dalam waktu singkat, serangan ini dapat menggunakan bandwidth relatif rendah sambil mempertahankan banyak koneksi sehingga ketersediaan slot atau sumber daya layanan berpotensi menurun.

Karakter low-rate menyebabkan Slowloris lebih sulit dianalisis hanya melalui volume traffic. Reed, Dooley, dan Mostefaoui menjelaskan bahwa Slow DoS dapat menyerupai aktivitas node sah yang mengalami koneksi lambat atau tidak stabil, sehingga mekanisme deteksi perlu membedakan traffic berbahaya dari kondisi latensi yang wajar pada jaringan IoT [3]. Penelitian lanjutan mereka juga menekankan pentingnya model yang mempertimbangkan keterbatasan sumber daya serta kemampuan membedakan serangan lambat dari genuine slow nodes [4]. Dengan demikian, satu indikator seperti durasi koneksi, jumlah paket, atau rendahnya throughput tidak cukup untuk menjadi dasar keputusan. Analisis membutuhkan kombinasi indikator dan pembanding terhadap traffic normal agar risiko false positive dapat dikurangi.

Analisis tersebut memerlukan bukti jaringan yang dapat ditelusuri. NIST SP 800-86 menempatkan pengumpulan dan pemeriksaan data sebagai bagian penting dalam penerapan teknik forensik pada penanganan insiden [5]. Dalam penelitian ini, Wireshark atau TShark direncanakan berjalan pada komputer/VM pengamat untuk memperoleh capture komunikasi menuju web server ESP32. Wireshark mendukung live capture, penyimpanan capture file, filter, pemeriksaan protokol, dan statistik traffic [6]. Snort digunakan sebagai sumber validasi berbasis rule; dokumentasi resminya menjelaskan bahwa rule menentukan traffic yang diperiksa melalui header serta kriteria payload maupun non-payload pada rule body [7]. Korelasi kedua sumber tersebut memungkinkan analisis tidak hanya bergantung pada satu alert atau satu ringkasan traffic.

Berbagai metode telah dikaji untuk mendeteksi low-rate denial-of-service. Trejo-Rodríguez dkk. mengusulkan model untuk mendeteksi serangan low-rate pada lapisan transport dan aplikasi dengan penggunaan fitur yang terbatas guna mengurangi overhead pemrosesan [8]. Penelitian Guardian Node pada lingkungan IoT juga menunjukkan bahwa atribut jaringan dan pembandingan dengan node sah yang lambat merupakan aspek penting pada analisis Slow DoS [3]. Meskipun demikian, penggunaan metode statistik atau pembelajaran mesin tidak secara otomatis menyelesaikan persoalan keterlacakan alasan keputusan. Dalam konteks penelitian tugas akhir, hasil analisis perlu dapat menunjukkan fitur apa yang ditemukan, bukti apa yang belum tersedia, dan alasan suatu label diperbolehkan atau diblokir.

Repository `slowloris-attack` menerapkan pendekatan rule-based melalui `ScoringService`. Data eksperimen, hasil akuisisi Wireshark/TShark, dan validasi Snort diolah menjadi fitur terstruktur. Fitur tersebut kemudian dipetakan menjadi radar score dan weighted composite score 0–100. Keputusan tidak hanya bergantung pada nilai akhir karena sistem menerapkan evidence gate. Pada profil Slowloris, indikator yang diperiksa meliputi durasi koneksi, anomali header, bandwidth rendah dengan banyak koneksi, koneksi TCP, deviasi dari baseline, dan bukti Snort yang relevan. Sistem juga menjaga kemungkinan false positive melalui skenario HTTP burst, iPerf/bandwidth test, portscan, normal baseline, traffic TCP dominan non-HTTP, dan kekurangan bukti Snort. Pendekatan ini menjadikan keputusan lebih konsisten dan dapat diaudit, tetapi pemilihan fitur serta bobot awal tetap harus divalidasi dengan dataset eksperimen penelitian.

Large Language Model dapat membantu mengubah fitur dan hasil logika menjadi penjelasan yang lebih mudah ditinjau. Namun, LLM tidak tepat ditempatkan sebagai satu-satunya mesin deteksi. Houssel dkk. menemukan bahwa LLM memiliki potensi sebagai agen pelengkap untuk menjelaskan hasil NIDS, tetapi masih memiliki keterbatasan pada ketepatan deteksi dan dapat menghasilkan informasi yang tidak sesuai dengan data [9]. Atas dasar itu, repository menempatkan `AiValidationService` hanya sebagai validator. Database aktif telah menyimpan 152 hasil AI non-simulasi dari beberapa model, termasuk Llama 3.3 70B Versatile, GPT-4o Mini melalui driver OpenAI-compatible, GPT-OSS 120B, Mistral Large, dan GPT-OSS 20B. Seluruh hasil tersebut masih berkaitan dengan eksperimen VM dan tidak dapat dianggap sebagai hasil validasi ESP32.

Kerangka hibrida dalam penelitian ini menggabungkan tiga lapisan yang saling melengkapi. Lapisan pertama adalah bukti forensik jaringan dari Wireshark/TShark dan Snort. Lapisan kedua adalah penilaian rule-based beserta evidence gate sebagai sumber keputusan utama. Lapisan ketiga adalah validasi LLM untuk membandingkan hasil, menyusun penjelasan, dan menunjukkan bukti pendukung maupun bukti yang masih hilang. Objek fisik akhir penelitian tetap berupa `[BELUM TERSEDIA DI DATABASE: model/varian ESP32]` yang menjalankan `[BELUM TERSEDIA DI DATABASE: framework dan versi firmware ESP32]` dengan `[BELUM TERSEDIA DI DATABASE: endpoint HTTP ESP32]`. Snapshot database saat ini hanya menyediakan `[DATA TERSEDIA TETAPI BUKAN ESP32: topologi VM host-only menuju 192.168.56.103 melalui enp0s8]` sebagai data awal pengujian alur analisis.

Database aktif memuat 13 eksperimen VM, yang terdiri atas delapan eksperimen profil Slowloris dan lima eksperimen profil serangan lain, tetapi belum memuat dataset ESP32. Tujuh eksperimen Slowloris memiliki durasi 100, 45, 45, 45, 150, 60, dan 85 detik, sedangkan satu eksperimen belum memiliki durasi. Data tersebut belum membentuk pengulangan terkontrol yang sebanding dan tidak boleh dipakai sebagai hasil akhir objek ESP32. Oleh karena itu, evaluasi ESP32 baru dapat dilakukan setelah model perangkat, firmware, topologi, baseline, skenario, durasi, pengulangan, ground truth, dan pasangan capture–Snort dikumpulkan secara nyata.

Berdasarkan uraian tersebut, penelitian ini diarahkan untuk membangun dan mengevaluasi kerangka analisis yang tidak hanya memberikan label, tetapi juga menunjukkan hubungan antara bukti jaringan, skor, evidence gate, dan penjelasan LLM. Metrik awal aplikasi pada delapan eksperimen Slowloris VM telah tersedia, tetapi hanya menjadi bahan audit kesiapan metode dan tidak menjadi hasil penelitian ESP32 pada BAB I. Pendekatan ini diharapkan menghasilkan analisis Slowloris yang transparan dan dapat ditinjau ulang tanpa mengklaim sebagai sistem deteksi universal. Oleh karena itu, penelitian ini mengambil judul “A Hybrid Network Forensics and Large Language Model Framework for Explainable Analysis of Slowloris Slow HTTP Request Attacks on ESP32 IoT Web Servers”.

## 1.2 Rumusan Masalah

Berdasarkan latar belakang yang telah dijelaskan, rumusan masalah dalam penelitian ini adalah sebagai berikut:

1. Bagaimana bukti forensik jaringan dari Wireshark/TShark dan Snort diolah menjadi fitur serta skor rule-based untuk menganalisis Slowloris Slow HTTP Request pada web server IoT ESP32?
2. Bagaimana evidence gate dan false-positive guards diterapkan agar label `Slowloris Detected` tidak diberikan ketika bukti yang tersedia belum memadai atau menunjukkan skenario pembanding?
3. Bagaimana Large Language Model digunakan sebagai validator berbasis `evidence_contract` untuk menghasilkan penjelasan yang dapat diaudit tanpa menggantikan keputusan rule-based?
4. Bagaimana kinerja kerangka hibrida dievaluasi menggunakan ground truth, confusion matrix, accuracy, precision, recall, dan F1-score pada dataset eksperimen ESP32?

## 1.3 Batasan Masalah

Pembatasan masalah digunakan agar penelitian tetap terarah dan pembahasannya sesuai dengan tujuan yang hendak dicapai. Batasan masalah pada penelitian ini adalah sebagai berikut:

1. Penelitian akhir hanya dilakukan pada perangkat ESP32 milik peneliti di jaringan laboratorium lokal yang terisolasi dan tidak melibatkan target publik.
2. Objek fisik penelitian dibatasi pada `[BELUM TERSEDIA DI DATABASE: model/varian ESP32]` yang menjalankan layanan web berbasis HTTP menggunakan `[BELUM TERSEDIA DI DATABASE: framework dan versi firmware ESP32]`.
3. Objek traffic utama dibatasi pada Slowloris/Slow HTTP Request dengan profil Slowloris yang tersedia pada aplikasi.
4. Penelitian menganalisis metadata eksperimen, capture atau ringkasan Wireshark/TShark, log atau alert Snort, fitur hasil ekstraksi, skor rule-based, evidence gate, hasil validasi LLM, dan laporan yang tersimpan pada aplikasi.
5. Database saat ini hanya mengisi sebagian informasi komputer/VM berupa `target_platform=vm_ubuntu_server` dan interface `enp0s8`; CPU, RAM, versi OS, serta posisi fisik titik capture belum tersedia. Seluruh konfigurasi tersebut harus didokumentasikan ulang pada eksperimen ESP32.
6. `ScoringService` menjadi sumber keputusan utama, sedangkan LLM hanya menjadi validator dan penyusun penjelasan berdasarkan payload yang diberikan.
7. Label `Slowloris Detected` hanya dapat digunakan ketika evidence gate mengizinkannya dan bukti pendukung tersedia.
8. Database VM menyediakan iPerf bandwidth dan HTTP burst dengan ground truth normal serta portscan dengan ground truth mixed. Skenario tersebut belum dianggap skenario pembanding ESP32 sebelum dikumpulkan ulang pada objek akhir.
9. Evaluasi ESP32 hanya menggunakan eksperimen yang memiliki ground truth tervalidasi dan pasangan data akuisisi serta validasi yang memenuhi kriteria kualitas penelitian.
10. Metrik evaluasi dibatasi pada confusion matrix, accuracy, precision, recall, dan F1-score. Metrik VM yang sudah tersedia diperlakukan sebagai evaluasi awal dan tidak menjadi hasil akhir ESP32.
11. Penelitian berfokus pada analisis defensif setelah data diperoleh dan tidak membahas pembuatan alat serangan, evasion, pemindaian publik, atau mitigasi real-time pada jaringan produksi.
12. Kesimpulan penelitian hanya berlaku pada konfigurasi ESP32, firmware, topologi, dataset, dan kondisi laboratorium yang nantinya didokumentasikan; hasilnya tidak digeneralisasi sebagai deteksi universal.

## 1.4 Tujuan Tugas Akhir

Berdasarkan latar belakang dan rumusan masalah tersebut, tujuan penelitian ini adalah sebagai berikut:

1. Menganalisis dan mengolah bukti Wireshark/TShark serta Snort menjadi fitur dan skor rule-based untuk menilai indikasi Slowloris Slow HTTP Request pada web server IoT ESP32.
2. Menerapkan dan menilai evidence gate serta false-positive guards untuk mencegah klasifikasi Slowloris ketika bukti belum memadai atau lebih sesuai dengan skenario pembanding.
3. Memanfaatkan Large Language Model sebagai validator berbasis `evidence_contract` untuk menghasilkan penjelasan yang dapat ditelusuri tanpa menggantikan keputusan rule-based.
4. Mengevaluasi kinerja kerangka hibrida menggunakan ground truth, confusion matrix, accuracy, precision, recall, dan F1-score pada dataset eksperimen ESP32 yang tervalidasi.

## 1.5 Manfaat Tugas Akhir

Penelitian ini diharapkan dapat memberikan manfaat dari sisi akademis, metodologis, dan penerapan laboratorium sebagai berikut:

1. Secara akademis, penelitian ini diharapkan menambah kajian mengenai integrasi forensik jaringan, rule-based scoring, evidence gate, dan Large Language Model untuk analisis Slow HTTP DoS pada perangkat IoT dengan keluaran yang dapat dijelaskan.
2. Secara metodologis, penelitian ini menyediakan alur yang dapat ditelusuri dari metadata eksperimen dan bukti jaringan menuju ekstraksi fitur, perhitungan skor, pemeriksaan evidence gate, validasi LLM, evaluasi, dan laporan.
3. Secara praktis, penelitian ini diharapkan membantu analis atau pengelola laboratorium meninjau indikasi Slowloris pada web server IoT ESP32 beserta bukti yang mendukung, bukti yang belum tersedia, dan alasan keputusan sistem.
4. Secara evaluatif, penelitian ini menyediakan dasar untuk membandingkan keputusan sistem dengan ground truth melalui confusion matrix, accuracy, precision, recall, dan F1-score serta menilai perlindungan terhadap false positive.
5. Bagi penelitian selanjutnya, dokumentasi fitur, evidence gate, dan batasan eksperimen dapat menjadi dasar pengembangan setelah efektivitasnya divalidasi pada konfigurasi dan dataset yang lebih beragam.

## 1.6 Sistematika Penulisan

Penyusunan tugas akhir ini dibagi menjadi beberapa bab dengan sistematika sebagai berikut:

**BAB I PENDAHULUAN**

Bab ini membahas latar belakang, rumusan masalah, batasan masalah, tujuan tugas akhir, manfaat tugas akhir, dan sistematika penulisan.

**BAB II TINJAUAN PUSTAKA DAN LANDASAN TEORI**

Bab ini membahas penelitian terdahulu dan landasan teori yang meliputi Internet of Things, ESP32 sebagai web server, HTTP dan TCP, Slowloris/Slow HTTP Request, forensik jaringan, Wireshark/TShark, Snort, ekstraksi fitur, rule-based scoring, evidence gate, explainable analysis, Large Language Model, confusion matrix, dan metrik evaluasi klasifikasi.

**BAB III METODOLOGI PENELITIAN**

Bab ini menjelaskan perangkat ESP32 dan firmware web server, topologi laboratorium terisolasi, rancangan eksperimen defensif, skenario traffic, proses pengumpulan dan validasi data, penetapan ground truth, alur aplikasi, ekstraksi fitur, scoring, evidence gate, validasi LLM, serta metode evaluasi.

**BAB IV HASIL DAN PEMBAHASAN**

Bab ini menyajikan hasil eksperimen pada web server IoT ESP32, hasil ekstraksi fitur, scoring dan evidence gate, validasi LLM, confusion matrix dan metrik evaluasi, analisis false positive, serta pembahasan keterjelasan alasan keputusan. Data VM awal digunakan hanya sebagai pembanding kesiapan metode dan dibedakan secara eksplisit dari hasil ESP32.

**BAB V PENUTUP**

Bab ini berisi kesimpulan yang menjawab rumusan masalah berdasarkan hasil penelitian serta saran untuk pengembangan dan penelitian selanjutnya.

## 7. Data yang Tetap Harus Dikumpulkan dari ESP32

- [ ] Model dan varian board ESP32 beserta datasheet resmi.
- [ ] Repository firmware, commit hash, framework, versi toolchain, dan konfigurasi build.
- [ ] Daftar handler/endpoint HTTP yang benar-benar aktif.
- [ ] Topologi ESP32, access point, komputer akuisisi, komputer analisis, dan jalur traffic.
- [ ] Titik capture yang terbukti dapat melihat komunikasi menuju ESP32.
- [ ] Spesifikasi komputer/VM akuisisi dan analisis.
- [ ] Versi Wireshark/TShark dan perintah capture defensif yang digunakan dalam protokol penelitian.
- [ ] Versi Snort, rule aktif, SID, threshold, dan interface pemantauan.
- [ ] Baseline normal ESP32 yang memiliki ground truth.
- [ ] Dataset Slowloris laboratorium terkontrol pada ESP32 dengan ground truth.
- [ ] Dataset pembanding ESP32 untuk HTTP burst, bandwidth test, portscan, atau skenario sah yang disetujui.
- [ ] Durasi, jumlah pengulangan, dan kondisi tetap untuk setiap skenario.
- [ ] Catatan availability atau respons web server yang diukur dengan metode yang ditetapkan.
- [ ] Pasangan acquisition–validation dan hasil `ExtractedFeature` untuk setiap eksperimen.
- [ ] Confusion matrix dan metrik evaluasi khusus dataset ESP32.
- [ ] Validasi pembimbing atas judul, batasan, skenario, serta penggunaan data VM sebagai data pendahuluan.

## 8. Catatan Perubahan

1. Sumber database diperbaiki dari snapshot SQLite menjadi MySQL aktif `slowloris_lab`.
2. Klaim “database hanya memiliki tiga eksperimen” diganti dengan snapshot aktif 13 eksperimen.
3. Kolom `target_platform` dipastikan tersedia pada MySQL dan seluruh nilainya adalah `vm_ubuntu_server`.
4. Jumlah eksperimen profil Slowloris diisi menjadi delapan, tetapi diberi status NOT_ESP32.
5. Durasi tujuh eksperimen Slowloris diisi dari `experiments.capture_duration`; satu nilai tetap belum tersedia.
6. Topologi VM awal diisi dari source IP, target IP, dan interface, tetapi tidak disebut sebagai topologi ESP32.
7. Provider/model LLM diisi dari `ai_provider_settings` dan `ai_results`; 152 hasil aktif dipastikan non-simulasi.
8. Confusion matrix dan metrik diambil langsung dari `EvaluationMetricsService`, lalu ditandai sebagai hasil VM awal.
9. Versi Wireshark/TShark dan Snort tetap belum tersedia karena database tidak menyimpannya.
10. Model, firmware, endpoint, dan dataset ESP32 tetap belum tersedia serta dipertahankan secara eksplisit.
11. BAB I direvisi agar menjelaskan pemisahan antara dataset VM pendahuluan dan objek akhir ESP32.
