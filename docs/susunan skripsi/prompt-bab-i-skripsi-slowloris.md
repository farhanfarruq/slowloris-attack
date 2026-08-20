# Prompt Penyusunan BAB I Skripsi Slowloris

## Judul kerja

**A Hybrid Network Forensics and Large Language Model Framework for Explainable Analysis of Slowloris Slow HTTP Request Attacks on ESP32 IoT Web Servers**

Terjemahan judul:

**Kerangka Hibrida Forensik Jaringan dan Large Language Model untuk Analisis yang Dapat Dijelaskan terhadap Serangan Slowloris Slow HTTP Request pada Web Server IoT ESP32**

> Target penelitian wajib berupa perangkat fisik **ESP32 yang menjalankan web server IoT** di jaringan laboratorium terisolasi. Repository saat ini menyediakan kerangka analisisnya; spesifikasi ESP32, firmware, topologi, dan hasil eksperimen hanya boleh dinyatakan sebagai hasil nyata setelah diverifikasi dari perangkat dan dataset penelitian.

---

## Prompt siap pakai

```text
Anda berperan sebagai penulis akademik bidang keamanan jaringan, forensik jaringan, sistem deteksi intrusi, dan Large Language Model (LLM). Susun BAB I skripsi berbahasa Indonesia dengan judul:

“A Hybrid Network Forensics and Large Language Model Framework for Explainable Analysis of Slowloris Slow HTTP Request Attacks on ESP32 IoT Web Servers”

Terjemahan judul:

“Kerangka Hibrida Forensik Jaringan dan Large Language Model untuk Analisis yang Dapat Dijelaskan terhadap Serangan Slowloris Slow HTTP Request pada Web Server IoT ESP32”

TUGAS UTAMA

Kerjakan tugas ini dalam dua tahap. Tahap pertama dijalankan di Codex untuk memeriksa repository dan menyusun satu paket Markdown. Tahap kedua menggunakan bagian “Prompt Final untuk ChatGPT Browser” dari paket tersebut bersama dokumen “DRAFT SKRIPSI PLTP RIZKY FAIHAQI.docx” yang dilampirkan di ChatGPT Browser.

Pada tahap Codex, susun draf BAB I PENDAHULUAN secara utuh, jelas, akademik, dan konsisten dengan struktur serta pola penulisan pada dokumen acuan. Gunakan dokumen tersebut hanya sebagai acuan urutan subbab, pola pengembangan argumentasi, dan gaya penyajian. Jangan menyalin topik, isi, data, kalimat, atau referensi mengenai PLTP, aliran dua-fasa, maupun CFD.

SUMBER KEBENARAN PROYEK

Sebelum menulis, periksa repository slowloris-attack. Gunakan prioritas sumber berikut:

1. app/Services/ScoringService.php sebagai sumber kebenaran utama untuk ekstraksi indikator, radar score, weighted composite score, kategori, keputusan akhir, dan evidence gate.
2. app/Services/AnalysisService.php untuk alur korelasi data akuisisi dan validasi, penyimpanan ExtractedFeature, serta penyusunan payload AI dan evidence_contract.
3. app/Services/AiPromptBuilder.php untuk kontrak prompt LLM yang provider-neutral dan aturan keluaran terstruktur.
4. app/Services/AiValidationService.php untuk peran LLM sebagai validator, bukan pengambil keputusan utama.
5. app/Services/EvaluationMetricsService.php untuk confusion matrix serta metrik accuracy, precision, recall, dan F1-score.
6. config/tool_profiles.php untuk profil Slowloris, label “Slowloris Detected”, indikator, bobot, metrik visualisasi, dan false-positive guards.
7. Model Experiment, AcquisitionFile, ValidationFile, SnortAlert, ExtractedFeature, AiResult, dan FinalReport di app/Models.
8. README.md, docs/project-presentation-and-thesis-guide.md, docs/scoring-literature-analysis.md, docs/lab-wireshark-snort-shell.md, dan docs/manual-experiment-upload-validation.md untuk konteks sistem dan batas lingkungan penelitian.
9. routes/web.php hanya untuk memastikan fungsi aplikasi yang benar-benar tersedia.
10. database/database.sqlite hanya untuk memverifikasi keadaan data aktual. Jangan menyebut jumlah eksperimen, hasil pengujian, atau nilai metrik tanpa pemeriksaan langsung terhadap data terkini.

FAKTA PROYEK YANG BOLEH DIGUNAKAN

- Sistem merupakan dashboard analisis defensif berbasis Laravel 11 untuk mengelola data lalu lintas jaringan dari laboratorium lokal.
- Objek penelitian yang wajib digunakan adalah perangkat fisik ESP32 yang menjalankan layanan HTTP sebagai web server IoT pada jaringan laboratorium terisolasi.
- Aplikasi Laravel, Wireshark/TShark, Snort, dan layanan LLM berjalan pada komputer/VM analisis yang terpisah dari ESP32. Jangan menyatakan bahwa Laravel, Wireshark, Snort, atau LLM berjalan langsung pada mikrokontroler ESP32.
- Traffic yang dianalisis harus benar-benar menuju web server ESP32. PCAP/ringkasan Wireshark/TShark dan alert Snort harus berasal dari titik observasi jaringan yang dapat melihat komunikasi menuju ESP32, misalnya komputer pemantau, gateway, atau interface jaringan laboratorium yang telah diverifikasi.
- Data forensik jaringan berasal dari hasil akuisisi/capture Wireshark atau TShark dan data validasi/alert Snort yang diunggah ke aplikasi.
- AnalysisService menghubungkan data eksperimen, data akuisisi, dan data validasi; menghasilkan fitur terstruktur; kemudian membentuk payload untuk validasi AI.
- ScoringService adalah mesin keputusan rule-based dan sumber kebenaran klasifikasi. Sistem menggunakan indikator terukur, weighted composite score 0–100, kategori, serta evidence gate.
- Indikator Slowloris mencakup pola request/header HTTP yang tidak lengkap atau anomali, koneksi HTTP yang berlangsung lama, banyak koneksi dengan bandwidth rendah, dan bukti Snort Slow HTTP yang relevan sesuai data yang tersedia.
- Evidence gate mencegah label “Slowloris Detected” jika bukti wajib tidak terpenuhi.
- LLM hanya bertindak sebagai validator dan pemberi penjelasan berdasarkan payload. LLM tidak boleh mengalahkan keputusan evidence gate atau mengarang IP, waktu, jumlah paket, jumlah koneksi, nama rule, maupun bukti lain.
- Kelas keluaran AI dibatasi menjadi Normal, Suspicious, Slowloris Detected, dan Inconclusive.
- Confidence AI adalah tingkat keyakinan terhadap kelas yang dipilih, bukan probabilitas serangan, kecuali kelas yang dipilih memang “Slowloris Detected”.
- Perlindungan false positive mencakup HTTP burst, iPerf/bandwidth test, portscan, normal baseline, traffic TCP dominan yang bukan HTTP, dan kondisi kekurangan bukti Snort.
- Explainability berasal dari fitur yang terukur, rincian skor, supporting indicators, evidence_contract, gate reasons, dan missing evidence yang dapat diaudit.
- Evaluasi sistem dapat menggunakan ground truth dan confusion matrix TP, TN, FP, serta FN untuk menghitung accuracy, precision, recall, dan F1-score.
- Lingkup penelitian adalah analisis defensif terhadap web server IoT ESP32 fisik pada jaringan laboratorium lokal yang terisolasi. Komputer/VM digunakan sebagai mesin akuisisi dan analisis. Jangan menggambarkan sistem sebagai alat deteksi universal, real-time production IDS, atau alat untuk menyerang target publik.

FAKTA YANG WAJIB DIVERIFIKASI ATAU DIBERI PLACEHOLDER

Jika fakta berikut belum tersedia atau belum tervalidasi, gunakan placeholder yang terlihat jelas dan jangan mengarang:

- [TOPOLOGI LAB TERVERIFIKASI]
- [SPESIFIKASI VM TARGET DAN VM SUMBER]
- [MODEL/VARIAN ESP32 YANG DIGUNAKAN]
- [VERSI FRAMEWORK DAN FIRMWARE WEB SERVER ESP32]
- [KONFIGURASI ENDPOINT HTTP ESP32]
- [SUMBER DAYA ESP32 YANG DIUKUR, JIKA ADA]
- [JUMLAH DATASET/EKSPERIMEN TERVERIFIKASI]
- [PERIODE PENGAMBILAN DATA]
- [DURASI DAN JUMLAH PENGULANGAN EKSPERIMEN]
- [VERSI WIRESHARK/TSHARK]
- [VERSI DAN RULE SNORT]
- [PROVIDER DAN MODEL LLM YANG BENAR-BENAR DIGUNAKAN]
- [HASIL ACCURACY, PRECISION, RECALL, DAN F1-SCORE]
- [HASIL PERBANDINGAN RULE-BASED DAN VALIDASI LLM]
- [PERLU VALIDASI PEMBIMBING]

ESP32 adalah target penelitian yang wajib, bukan opsi. Namun, jangan mengarang model ESP32, framework firmware, endpoint HTTP, kapasitas perangkat, alamat IP, topologi, nilai performa, jumlah sampel, persentase keberhasilan, atau model LLM. Sebelum eksperimen selesai, tulis sebagai rancangan penelitian atau gunakan placeholder. Setelah eksperimen selesai, ganti placeholder hanya dengan bukti aktual dari perangkat, konfigurasi, repository, dan dataset.

STRUKTUR WAJIB BAB I

Ikuti urutan subbab pada draft acuan berikut tanpa menambah subbab baru:

BAB I PENDAHULUAN

1.1 Latar Belakang
1.2 Rumusan Masalah
1.3 Batasan Masalah
1.4 Tujuan Tugas Akhir
1.5 Manfaat Tugas Akhir
1.6 Sistematika Penulisan

PETUNJUK ISI SETIAP SUBBAB

1.1 Latar Belakang

Kembangkan latar belakang dari umum ke khusus dalam 7–9 paragraf yang saling terhubung:

1. Jelaskan meningkatnya penggunaan perangkat IoT berbasis ESP32 yang menyediakan layanan HTTP dan pentingnya availability pada perangkat dengan sumber daya komputasi, memori, dan koneksi yang terbatas. Setiap angka atau perbandingan kapasitas harus berasal dari sumber resmi atau konfigurasi perangkat aktual.
2. Jelaskan ancaman denial-of-service pada lapisan aplikasi dan perbedaan Slowloris/Slow HTTP Request dengan HTTP flood berkecepatan tinggi. Slowloris mempertahankan banyak koneksi dengan mengirim request HTTP secara lambat atau tidak lengkap sehingga sumber daya koneksi server tertahan.
3. Jelaskan kesulitan analisis Slowloris: laju trafik dapat rendah, koneksi menyerupai klien lambat, satu indikator tidak cukup, dan kesalahan klasifikasi dapat terjadi terhadap traffic normal, HTTP burst, bandwidth test, atau portscan.
4. Jelaskan peran forensik jaringan menggunakan bukti hasil capture Wireshark/TShark dan alert Snort untuk merekonstruksi serta memvalidasi karakteristik trafik.
5. Bahas keterbatasan pendekatan tunggal. Rule-based bersifat konsisten dan dapat diaudit tetapi bergantung pada pemilihan fitur, bobot, dan evidence gate; LLM dapat membantu menyusun penjelasan kontekstual tetapi berisiko berhalusinasi dan tidak boleh menjadi sumber keputusan utama.
6. Jelaskan gap penelitian yang spesifik: masih diperlukan kerangka hibrida yang menggabungkan bukti forensik jaringan, penilaian rule-based, evidence gate, dan validasi LLM yang dibatasi kontrak bukti untuk menghasilkan keputusan yang dapat dijelaskan.
7. Perkenalkan implementasi proyek secara faktual: web server berjalan pada ESP32 fisik sebagai objek penelitian; komputer/VM melakukan akuisisi Wireshark/TShark dan validasi Snort; aplikasi mengorelasikan data eksperimen, mengekstrak fitur, menghitung skor dan evidence gate, meminta validasi LLM, menampilkan visualisasi, melakukan evaluasi, mencatat audit, dan menghasilkan laporan.
8. Tegaskan kontribusi penelitian pada explainability dan perlindungan false positive. Hindari klaim “mendeteksi secara sempurna”, “akurat 100%”, atau “berlaku universal”.
9. Tutup dengan alasan dan fokus penelitian, lalu hubungkan langsung dengan judul tugas akhir.

Ikuti pola draft acuan: konteks umum → fenomena teknis → dampak → penelitian terdahulu → keterbatasan/gap → pendekatan yang dipilih → fokus penelitian. Gunakan paragraf akademik, bukan daftar poin, pada bagian latar belakang.

1.2 Rumusan Masalah

Awali dengan satu kalimat pengantar seperti pola draft, kemudian buat 3–4 pertanyaan penelitian yang paralel dan dapat diuji. Rumusan minimal membahas:

1. Bagaimana bukti forensik jaringan dari Wireshark/TShark dan Snort diolah menjadi fitur dan skor rule-based untuk menganalisis Slowloris pada web server IoT ESP32?
2. Bagaimana evidence gate dan false-positive guards membatasi keputusan agar label Slowloris tidak diberikan ketika bukti tidak mencukupi?
3. Bagaimana LLM digunakan sebagai validator berbasis evidence_contract untuk menghasilkan penjelasan tanpa menggantikan keputusan rule-based?
4. Bagaimana kinerja dan kesesuaian hasil kerangka hibrida dievaluasi menggunakan ground truth, confusion matrix, accuracy, precision, recall, dan F1-score?

Pastikan setiap pertanyaan memiliki pasangan langsung pada tujuan penelitian. ESP32 harus menjadi objek eksperimen aktual, tetapi jangan memperluas rumusan masalah ke pencegahan serangan atau mitigasi real-time karena fitur tersebut tidak menjadi fokus repository.

1.3 Batasan Masalah

Awali dengan paragraf pengantar seperti draft, lalu tulis batasan yang tegas dan operasional. Cantumkan bahwa:

1. Penelitian hanya dilakukan terhadap perangkat ESP32 milik peneliti pada jaringan laboratorium lokal yang terisolasi dan tidak mencakup target publik.
2. Objek fisik penelitian adalah ESP32 yang menjalankan web server HTTP; objek trafik utamanya adalah Slowloris/Slow HTTP Request pada profil Slowloris di dalam sistem.
3. Data utama dibatasi pada metadata eksperimen, hasil capture Wireshark/TShark atau ringkasannya, log/alert Snort, fitur hasil ekstraksi, hasil scoring, dan validasi LLM yang tersimpan pada aplikasi.
4. ScoringService menjadi sumber keputusan utama; LLM hanya menjadi validator dan penyusun penjelasan.
5. Label Slowloris Detected hanya diperbolehkan ketika evidence gate mengizinkannya.
6. Skenario pembanding/false positive dibatasi pada data yang benar-benar tersedia, seperti normal baseline, HTTP burst, iPerf/bandwidth test, dan portscan.
7. Evaluasi dibatasi pada dataset eksperimen yang memiliki ground truth tervalidasi.
8. Penelitian berfokus pada analisis setelah data diperoleh, bukan pembangunan alat serangan, evasion, pemindaian publik, mitigasi jaringan real-time, atau klaim sebagai IDS produksi universal.
9. Model ESP32, firmware, endpoint HTTP, provider/model LLM, jumlah data, konfigurasi komputer/VM, versi alat, dan rule Snort hanya boleh disebut setelah diverifikasi; jika belum, gunakan placeholder.

1.4 Tujuan Tugas Akhir

Awali dengan satu kalimat pengantar seperti draft. Buat tujuan yang menggunakan kata kerja operasional dan berpasangan satu per satu dengan rumusan masalah. Tujuan minimal:

1. Menganalisis dan mengolah bukti Wireshark/TShark serta Snort menjadi fitur dan skor rule-based Slowloris pada web server IoT ESP32.
2. Menerapkan atau menilai evidence gate dan false-positive guards untuk mencegah klasifikasi Slowloris tanpa bukti yang memadai.
3. Memanfaatkan LLM sebagai validator berbasis evidence_contract untuk menghasilkan penjelasan yang dapat diaudit tanpa menggantikan keputusan rule-based.
4. Mengevaluasi hasil kerangka hibrida menggunakan ground truth, confusion matrix, accuracy, precision, recall, dan F1-score pada dataset penelitian yang tervalidasi.

Gunakan kata “menganalisis”, “menilai”, “memvalidasi”, atau “mengevaluasi”; hindari tujuan absolut seperti “menghilangkan seluruh serangan” atau “menjamin akurasi sempurna”.

1.5 Manfaat Tugas Akhir

Awali dengan paragraf pengantar seperti draft. Jelaskan manfaat dalam uraian bernomor atau paragraf terpisah yang mencakup:

1. Manfaat akademis: menambah kajian mengenai integrasi forensik jaringan, rule-based scoring, evidence gate, dan LLM untuk analisis Slow HTTP DoS yang dapat dijelaskan.
2. Manfaat metodologis: menyediakan alur analisis yang dapat ditelusuri dari bukti mentah/ringkasan, fitur, skor, gate, validasi AI, hingga laporan.
3. Manfaat praktis: membantu analis atau pengelola laboratorium meninjau indikasi Slowloris pada web server IoT ESP32 dan alasan keputusan secara lebih sistematis.
4. Manfaat evaluatif: menyediakan dasar pengukuran berbasis ground truth dan metrik klasifikasi serta perlindungan terhadap false positive.

Jangan mengklaim manfaat produksi, komersial, real-time, atau universal yang belum dibuktikan.

1.6 Sistematika Penulisan

Ikuti pola draft dan jelaskan isi setiap bab secara ringkas:

- BAB I PENDAHULUAN: latar belakang, rumusan masalah, batasan masalah, tujuan, manfaat, dan sistematika penulisan.
- BAB II TINJAUAN PUSTAKA DAN LANDASAN TEORI: penelitian terdahulu serta teori IoT dan ESP32 web server, Slowloris/Slow HTTP, forensik jaringan, Wireshark/TShark, Snort, ekstraksi fitur, rule-based scoring, evidence gate, explainable analysis, LLM, dan evaluasi klasifikasi.
- BAB III METODOLOGI PENELITIAN: perangkat ESP32 dan firmware web server, topologi lab terisolasi, rancangan eksperimen defensif, pengumpulan data, ground truth, alur sistem, ekstraksi fitur, scoring, validasi LLM, dan metode evaluasi.
- BAB IV HASIL DAN PEMBAHASAN: hasil eksperimen, hasil scoring dan evidence gate, hasil validasi LLM, confusion matrix dan metrik, analisis false positive, serta pembahasan explainability.
- BAB V PENUTUP: kesimpulan dan saran berdasarkan hasil penelitian.

ATURAN PENULISAN AKADEMIK

1. Gunakan bahasa Indonesia formal, objektif, runtut, dan mudah dipahami.
2. Gunakan istilah asing secara konsisten; berikan padanan atau penjelasan pada penyebutan pertama.
3. Jangan memakai gaya promosi, hiperbola, atau klaim tanpa bukti.
4. Jangan menyatakan hasil penelitian pada BAB I jika eksperimen dan evaluasi belum selesai.
5. Gunakan sitasi IEEE berbentuk [1], [2], dan seterusnya.
6. Setiap angka, tren, definisi teknis, pernyataan efektivitas, dan klaim penelitian terdahulu harus memiliki sumber yang nyata dan dapat diverifikasi.
7. Prioritaskan jurnal/prosiding peer-reviewed, dokumentasi resmi, standar, atau laporan lembaga tepercaya. Utamakan sumber 5 tahun terakhir dari tahun penulisan, tetapi izinkan sumber seminal yang lebih lama jika benar-benar diperlukan.
8. Jangan membuat referensi, nama penulis, judul artikel, tahun, DOI, URL, hasil eksperimen, atau nomor sitasi palsu.
9. Jika akses sumber tidak tersedia, tulis [SITASI TERVERIFIKASI DIPERLUKAN] pada klaim terkait dan masukkan ke daftar kebutuhan data, bukan mengarang referensi.
10. Parafrase sumber dengan benar. Jangan menyalin kalimat panjang dari referensi atau draft acuan.
11. Bedakan dengan jelas antara fakta literatur, rancangan penelitian, implementasi yang terlihat pada repository, dan hasil yang baru boleh disebut setelah eksperimen.
12. Gunakan istilah “kerangka hibrida” untuk kombinasi forensik jaringan + keputusan rule-based/evidence gate + validasi LLM. Jangan menggambarkan LLM sebagai mesin deteksi utama.

FORMAT KELUARAN CODEX

Codex wajib menyimpan hasil sebagai satu file Markdown bernama:

docs/paket-chatgpt-browser-bab-i-slowloris-esp32.md

File tersebut harus mandiri dan dapat dipindahkan ke ChatGPT Browser bersama DOCX acuan. Susun isinya dalam urutan berikut:

1. Judul penelitian dalam bahasa Inggris dan terjemahan bahasa Indonesia.
2. Ringkasan Fakta Terverifikasi dari Repository, termasuk pemisahan yang jelas antara komponen yang sudah diimplementasikan dan data eksperimen ESP32 yang masih harus dikumpulkan.
3. Prompt Final untuk ChatGPT Browser dalam satu code block teks yang lengkap dan mandiri. Prompt ini harus meminta ChatGPT membaca DOCX yang dilampirkan, mempertahankan struktur BAB I, memeriksa dan menyempurnakan draft dari Codex, mencari sumber nyata jika akses web tersedia, serta mengembalikan naskah dalam Markdown.
4. Draft Awal BAB I PENDAHULUAN lengkap dengan subbab 1.1 sampai 1.6. Draft harus sudah layak ditinjau, bukan hanya kerangka, tetapi tetap menggunakan placeholder untuk fakta eksperimen yang belum ada.
5. Daftar Pustaka Sementara berformat IEEE yang hanya berisi sumber yang benar-benar digunakan dan terverifikasi. Jika Codex tidak melakukan penelusuran sumber, jangan membuat daftar pustaka palsu; masukkan kebutuhan sitasi ke prompt browser.
6. Daftar Data Eksperimen ESP32 yang Masih Harus Dilengkapi, berisi seluruh placeholder atau fakta yang belum dapat diverifikasi.
7. Tabel Pemeriksaan Konsistensi dengan kolom: Rumusan Masalah, Tujuan Terkait, Data yang Dibutuhkan, Metode Analisis, dan Luaran yang Diharapkan.
8. Peta Ketertelusuran Repository dengan kolom: Klaim, Status Terverifikasi, dan File Sumber.

Prompt Final untuk ChatGPT Browser wajib memuat seluruh fakta, batasan, placeholder, struktur, dan aturan anti-halusinasi dari prompt ini sehingga dapat digunakan tanpa akses langsung ke repository. Instruksikan ChatGPT Browser untuk mengeluarkan hasil akhir sebagai Markdown, bukan DOCX, HTML, atau uraian percakapan.

PEMERIKSAAN SEBELUM MENYERAHKAN HASIL

Pastikan seluruh kondisi berikut terpenuhi:

- Struktur subbab sama dengan BAB I pada draft acuan.
- Tidak ada sisa pembahasan PLTP, pipa, aliran dua-fasa, atau CFD.
- Seluruh uraian sistem sesuai repository slowloris-attack.
- ScoringService ditempatkan sebagai sumber keputusan utama.
- LLM ditempatkan hanya sebagai validator yang tunduk pada evidence_contract dan evidence gate.
- ESP32 ditetapkan secara konsisten sebagai web server IoT fisik dan objek penelitian.
- Aplikasi analisis tidak dinyatakan berjalan langsung pada ESP32.
- Detail dan hasil ESP32 yang belum diverifikasi tetap berupa placeholder, bukan fakta buatan.
- Rumusan masalah dan tujuan berpasangan satu per satu.
- Batasan penelitian menegaskan lingkungan lab terkontrol dan analisis defensif.
- Tidak ada angka, hasil, sitasi, atau referensi yang dibuat-buat.
- Kekurangan fakta ditandai dengan placeholder yang jelas.
- Istilah Slowloris, Slow HTTP Request, rule-based scoring, evidence gate, false positive, explainability, dan LLM digunakan secara konsisten.
```

## Catatan penggunaan

1. Jalankan file prompt ini di Codex dengan akses baca ke repository `slowloris-attack` dan DOCX acuan.
2. Ambil bagian **Prompt Final untuk ChatGPT Browser** dari file keluaran `docs/paket-chatgpt-browser-bab-i-slowloris-esp32.md`, lalu kirim ke ChatGPT Browser bersama DOCX acuan. Sertakan pula bagian draft BAB I jika ingin ChatGPT menyempurnakannya, bukan menulis ulang dari nol.
3. Isi placeholder hanya dari perangkat ESP32 fisik, firmware, catatan eksperimen, konfigurasi alat, database, dan hasil evaluasi yang telah diverifikasi.
4. Minta pembimbing memvalidasi judul, objek penelitian, batasan, dan istilah program studi sebelum naskah dijadikan versi final.
