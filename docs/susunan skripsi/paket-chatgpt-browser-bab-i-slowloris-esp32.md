# Paket Penyusunan BAB I Skripsi Slowloris pada ESP32 IoT Web Server

## 1. Judul Penelitian

### Bahasa Inggris

**A Hybrid Network Forensics and Large Language Model Framework for Explainable Analysis of Slowloris Slow HTTP Request Attacks on ESP32 IoT Web Servers**

### Bahasa Indonesia

**Kerangka Hibrida Forensik Jaringan dan Large Language Model untuk Analisis yang Dapat Dijelaskan terhadap Serangan Slowloris Slow HTTP Request pada Web Server IoT ESP32**

## 2. Status Dokumen

Dokumen ini merupakan paket antara dari Codex untuk dilanjutkan di ChatGPT Browser bersama berkas `DRAFT SKRIPSI PLTP RIZKY FAIHAQI.docx`. Isinya terdiri atas fakta repository yang telah diverifikasi, prompt final untuk ChatGPT Browser, serta draft awal BAB I.

Draft BAB I sudah mengikuti urutan subbab pada DOCX acuan, tetapi belum boleh dianggap sebagai naskah final penelitian. Model ESP32, firmware, topologi jaringan, jumlah eksperimen, durasi, versi perangkat lunak, provider LLM, serta hasil evaluasi masih harus diisi menggunakan data eksperimen nyata.

## 3. Ringkasan Fakta Terverifikasi dari Repository

### 3.1 Komponen yang sudah diimplementasikan

1. Repository berisi aplikasi analisis defensif berbasis Laravel 11 untuk mengelola eksperimen, data akuisisi jaringan, data validasi Snort, fitur hasil ekstraksi, hasil AI, evaluasi, dan laporan.
2. `ScoringService` merupakan sumber keputusan rule-based. Layanan ini memilih data akuisisi dan validasi, membangun fitur, menghitung radar score, menghasilkan weighted composite score, menerapkan evidence gate, dan memetakan hasil menjadi kategori, status eksperimen, serta keputusan akhir.
3. Profil Slowloris menggunakan indikator durasi koneksi, anomali header, kombinasi bandwidth rendah dan banyak koneksi, volume koneksi TCP, deviasi baseline, bukti alert Snort, serta confidence AI dengan bobot terbatas.
4. Profil Slowloris memiliki false-positive guards untuk `http-burst`, `iperf-bandwidth`, `portscan`, dan `normal-baseline`.
5. `AnalysisService` mengorelasikan data akuisisi dan validasi, menyimpan `ExtractedFeature`, lalu membentuk payload AI yang berisi ringkasan paket, ringkasan alert, hasil logika program, dan `evidence_contract`.
6. `AiPromptBuilder` membatasi AI agar hanya menggunakan nilai pada payload, mengembalikan kelas yang diizinkan, tidak mengarang bukti, dan tidak memakai label terdeteksi ketika evidence gate tidak mengizinkannya.
7. `AiValidationService` menurunkan label menjadi `Inconclusive` atau kelas non-detected ketika kontrak bukti tidak terpenuhi. LLM berfungsi sebagai validator dan penyusun penjelasan, bukan pengganti keputusan rule-based.
8. `EvaluationMetricsService` menyediakan evaluasi berbasis confusion matrix serta accuracy, precision, recall, dan F1-score.
9. Model utama yang membentuk alur data adalah `Experiment`, `AcquisitionFile`, `ValidationFile`, `SnortAlert`, `ExtractedFeature`, `AiResult`, dan `FinalReport`.

### 3.2 Target ESP32 yang ditetapkan untuk penelitian

1. Objek akhir penelitian ditetapkan sebagai perangkat fisik ESP32 yang menjalankan layanan HTTP sebagai web server IoT.
2. ESP32 ditempatkan pada jaringan laboratorium milik peneliti yang terisolasi. Penelitian tidak diarahkan ke sistem publik.
3. Aplikasi Laravel, Wireshark/TShark, Snort, dan layanan LLM berjalan pada komputer atau VM analisis yang terpisah. Komponen-komponen tersebut tidak dinyatakan berjalan langsung pada ESP32.
4. Traffic yang dianalisis harus benar-benar menuju ESP32. Capture jaringan dan alert Snort harus diperoleh dari titik observasi yang dapat melihat komunikasi menuju ESP32.

### 3.3 Fakta yang belum tersedia

1. Repository belum berisi firmware web server ESP32 yang dapat dijadikan sumber konfigurasi aktual.
2. Database aktif yang diperiksa pada 2 Agustus 2026 berisi tiga eksperimen, tetapi tidak memiliki kolom `target_platform` dan tidak menyediakan bukti bahwa eksperimen tersebut berasal dari ESP32.
3. Kode model dan migration mengenal `target_platform`, tetapi migration terkait belum tercermin pada database aktif. Hal ini harus dibereskan atau diverifikasi sebelum metadata eksperimen ESP32 dianggap tersimpan dengan benar.
4. Belum tersedia bukti aktual mengenai model ESP32, endpoint HTTP, versi firmware, topologi, jumlah pengulangan, durasi, versi Wireshark/TShark, versi dan rule Snort, provider LLM, serta hasil accuracy, precision, recall, dan F1-score untuk eksperimen ESP32.
5. Oleh karena itu, ESP32 sah disebut sebagai objek dan rancangan penelitian, tetapi hasil empiris ESP32 belum boleh diklaim.

## 4. Prompt Final untuk ChatGPT Browser

Salin prompt di bawah ini ke ChatGPT Browser. Lampirkan dua berkas berikut pada percakapan yang sama:

1. `DRAFT SKRIPSI PLTP RIZKY FAIHAQI.docx`
2. `paket-chatgpt-browser-bab-i-slowloris-esp32.md`

```text
Anda berperan sebagai penulis dan penyunting akademik bidang keamanan jaringan, forensik jaringan, Internet of Things, sistem deteksi intrusi, dan Large Language Model.

Saya melampirkan dua dokumen:

1. “DRAFT SKRIPSI PLTP RIZKY FAIHAQI.docx”, yang hanya digunakan sebagai acuan struktur BAB I, pola pengembangan pembahasan, urutan subbab, dan gaya penulisan tugas akhir.
2. “paket-chatgpt-browser-bab-i-slowloris-esp32.md”, yang berisi fakta repository terverifikasi dan draft awal BAB I penelitian saya.

Judul penelitian:

“A Hybrid Network Forensics and Large Language Model Framework for Explainable Analysis of Slowloris Slow HTTP Request Attacks on ESP32 IoT Web Servers”

Terjemahan:

“Kerangka Hibrida Forensik Jaringan dan Large Language Model untuk Analisis yang Dapat Dijelaskan terhadap Serangan Slowloris Slow HTTP Request pada Web Server IoT ESP32”

TUGAS

Baca kedua lampiran secara menyeluruh. Periksa, koreksi, dan sempurnakan bagian “Draft Awal BAB I” pada paket Markdown menjadi BAB I skripsi yang utuh, akademik, konsisten, dan siap ditinjau pembimbing. Jangan menyalin isi mengenai PLTP, aliran dua-fasa, pipa, CFD, atau objek penelitian lain dari DOCX. Pertahankan hanya metode penyusunan dan struktur BAB I-nya.

STRUKTUR WAJIB

BAB I PENDAHULUAN

1.1 Latar Belakang
1.2 Rumusan Masalah
1.3 Batasan Masalah
1.4 Tujuan Tugas Akhir
1.5 Manfaat Tugas Akhir
1.6 Sistematika Penulisan

FAKTA SISTEM YANG WAJIB DIPERTAHANKAN

1. Target penelitian adalah perangkat fisik ESP32 yang menjalankan web server HTTP pada jaringan laboratorium terisolasi.
2. Laravel, Wireshark/TShark, Snort, dan layanan LLM berjalan pada komputer atau VM analisis yang terpisah dari ESP32.
3. Data utama terdiri atas metadata eksperimen, capture atau ringkasan Wireshark/TShark, log atau alert Snort, fitur hasil ekstraksi, hasil scoring, validasi LLM, evaluasi, dan laporan.
4. ScoringService adalah sumber keputusan utama. Sistem melakukan ekstraksi indikator, radar score, weighted composite score 0–100, kategorisasi, dan evidence gate.
5. Indikator Slowloris meliputi durasi koneksi, request atau header HTTP yang tidak lengkap/anomali, bandwidth rendah dengan banyak koneksi, koneksi menuju layanan HTTP, deviasi baseline, dan bukti Snort yang relevan sesuai data yang benar-benar tersedia.
6. Evidence gate mencegah label “Slowloris Detected” ketika bukti wajib tidak terpenuhi.
7. LLM hanya menjadi validator dan penyusun penjelasan berdasarkan payload serta evidence_contract. LLM tidak boleh mengalahkan evidence gate.
8. Keluaran AI dibatasi menjadi Normal, Suspicious, Slowloris Detected, dan Inconclusive.
9. Confidence AI adalah keyakinan terhadap kelas yang dipilih, bukan otomatis probabilitas serangan.
10. False-positive guards mencakup HTTP burst, iPerf/bandwidth test, portscan, normal baseline, traffic TCP dominan non-HTTP, dan kekurangan bukti Snort.
11. Explainability berasal dari fitur terukur, rincian skor, supporting indicators, missing evidence, gate reasons, evidence_contract, dan perbandingan keputusan rule-based dengan validasi LLM.
12. Evaluasi menggunakan ground truth, confusion matrix, accuracy, precision, recall, dan F1-score.
13. Penelitian bersifat defensif dan lab-local. Jangan mengubahnya menjadi alat serangan, panduan menyerang, sistem real-time produksi, atau klaim deteksi universal.

BATAS KEBENARAN ESP32

ESP32 wajib dipertahankan sebagai objek penelitian nyata, bukan dihapus atau diganti dengan VM. Namun, repository belum membuktikan model board, firmware, endpoint, topologi, jumlah eksperimen, atau hasil metrik ESP32. Nyatakan hal-hal tersebut sebagai rancangan atau placeholder sampai data nyata diberikan.

Jangan menyatakan bahwa Laravel, Wireshark, Snort, atau LLM berjalan langsung pada ESP32. Jangan mengarang model ESP32, kapasitas memori, jumlah koneksi maksimum, IP, nama SSID, endpoint, framework firmware, nilai availability, jumlah paket, hasil Snort, provider LLM, accuracy, precision, recall, F1-score, atau persentase keberhasilan.

Gunakan placeholder berikut bila datanya belum tersedia:

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
- [PERLU VALIDASI PEMBIMBING]

ATURAN LATAR BELAKANG

Susun 7–9 paragraf dari umum ke khusus dengan alur:

1. Perkembangan perangkat IoT dan penggunaan ESP32 sebagai web server ringan.
2. Pentingnya availability pada web server berbasis perangkat terbatas.
3. Karakter Slowloris/Slow HTTP Request dan perbedaannya dari high-rate HTTP flood.
4. Kesulitan membedakan serangan low-rate dari klien lambat atau traffic normal.
5. Peran Wireshark/TShark dan Snort sebagai bukti forensik dan validasi.
6. Kekuatan serta keterbatasan rule-based scoring.
7. Potensi dan risiko LLM; LLM harus ditempatkan sebagai validator/pemberi penjelasan, bukan mesin keputusan utama.
8. Gap penelitian dan usulan kerangka hibrida yang dapat diaudit.
9. Fokus penelitian pada web server IoT ESP32 dan kaitannya dengan judul.

ATURAN RUMUSAN MASALAH DAN TUJUAN

Buat empat rumusan masalah yang dapat diuji dan empat tujuan yang berpasangan satu per satu. Pasangan tersebut harus mencakup:

1. Pengolahan bukti Wireshark/TShark dan Snort menjadi fitur serta skor rule-based Slowloris pada ESP32.
2. Penerapan evidence gate dan false-positive guards.
3. Validasi dan penjelasan LLM berbasis evidence_contract tanpa menggantikan rule-based decision.
4. Evaluasi menggunakan ground truth, confusion matrix, accuracy, precision, recall, dan F1-score.

ATURAN SITASI

1. Gunakan gaya sitasi IEEE [1], [2], dan seterusnya.
2. Lakukan penelusuran web untuk memverifikasi sumber sebelum menggunakannya.
3. Prioritaskan artikel peer-reviewed, RFC, dokumentasi resmi Espressif, NIST, Wireshark, dan Snort.
4. Utamakan sumber lima tahun terakhir, kecuali sumber standar atau foundational yang masih relevan.
5. Pastikan judul, penulis, tahun, jurnal, volume, nomor, halaman/article number, DOI, dan URL benar.
6. Jangan membuat referensi atau DOI.
7. Gunakan Daftar Pustaka Sementara pada paket sebagai titik awal, tetapi buka dan verifikasi kembali setiap sumber.
8. Jika sebuah klaim belum memiliki sumber yang dapat diverifikasi, beri penanda [SITASI TERVERIFIKASI DIPERLUKAN].

ATURAN PENYUNTINGAN

1. Gunakan bahasa Indonesia formal, jelas, objektif, dan tidak terasa seperti teks promosi.
2. Perbaiki tata bahasa dan kohesi tanpa mengubah fakta repository.
3. Hindari klaim “sempurna”, “100% akurat”, “real-time”, “universal”, atau “siap produksi” tanpa hasil pengujian.
4. Bedakan fakta literatur, implementasi repository, rancangan eksperimen ESP32, dan hasil empiris.
5. Jangan menuliskan hasil penelitian pada BAB I sebelum datanya tersedia.
6. Jangan memberikan perintah, payload, atau langkah serangan. Penjelasan Slowloris dibatasi pada karakteristik yang diperlukan untuk analisis defensif.
7. Pastikan setiap rumusan masalah memiliki satu tujuan yang konsisten.
8. Pastikan batasan masalah benar-benar membatasi ruang lingkup.
9. Jangan menambah subbab di luar 1.1–1.6.

FORMAT KELUARAN

Keluarkan Markdown saja, tanpa pengantar percakapan, dengan urutan:

1. Judul bahasa Inggris dan bahasa Indonesia.
2. BAB I PENDAHULUAN lengkap, subbab 1.1–1.6.
3. Daftar Pustaka berformat IEEE.
4. Daftar Data yang Masih Harus Dilengkapi.
5. Tabel Pemeriksaan Konsistensi Rumusan Masalah dan Tujuan.

Sebelum memberikan hasil, lakukan pemeriksaan internal bahwa tidak ada isi PLTP/CFD, tidak ada fakta ESP32 buatan, seluruh sitasi nyata, LLM hanya validator, dan struktur sama dengan DOCX.
```

## 5. Draft Awal BAB I

# BAB I

# PENDAHULUAN

## 1.1 Latar Belakang

Perkembangan Internet of Things (IoT) mendorong semakin banyak perangkat tertanam menyediakan layanan berbasis jaringan untuk pemantauan, kendali, dan pertukaran data. ESP32 merupakan salah satu keluarga mikrokontroler yang mengintegrasikan konektivitas nirkabel dan dapat digunakan untuk menjalankan web server ringan. Dokumentasi resmi ESP-IDF menjelaskan bahwa komponen ESP HTTP Server menyediakan layanan HTTP pada ESP32, membuat listening socket untuk menerima traffic, mengalokasikan sumber daya sesuai konfigurasi, dan memanggil handler berdasarkan URI yang diminta [1]. Kemampuan tersebut membuat ESP32 relevan sebagai objek penelitian keamanan web server IoT, terutama karena layanan harus tetap tersedia ketika diakses oleh klien jaringan, sementara kapasitas perangkatnya tidak setara dengan server konvensional.

Layanan HTTP berjalan di atas koneksi transport dan dapat memanfaatkan koneksi persisten. RFC 9110 menjelaskan semantik HTTP modern, termasuk penggunaan koneksi persisten sebagai perilaku bawaan pada implementasi HTTP tertentu [2]. Karakter ini bermanfaat untuk mengurangi pembentukan koneksi berulang, tetapi juga membentuk permukaan yang perlu diperhatikan ketika klien mempertahankan koneksi dalam waktu lama. Pada serangan Slowloris atau Slow HTTP Request, koneksi ke layanan HTTP dipertahankan melalui pengiriman request atau header yang lambat dan belum lengkap. Berbeda dengan HTTP flood yang mengandalkan volume request tinggi dalam waktu singkat, serangan ini dapat menggunakan bandwidth relatif rendah sambil mempertahankan banyak koneksi sehingga ketersediaan slot atau sumber daya layanan berpotensi menurun.

Karakter low-rate menyebabkan Slowloris lebih sulit dianalisis hanya melalui volume traffic. Reed, Dooley, dan Mostefaoui menjelaskan bahwa Slow DoS dapat menyerupai aktivitas node sah yang mengalami koneksi lambat atau tidak stabil, sehingga mekanisme deteksi perlu membedakan traffic berbahaya dari kondisi latensi yang wajar pada jaringan IoT [3]. Penelitian lanjutan mereka juga menekankan pentingnya model yang mempertimbangkan keterbatasan sumber daya serta kemampuan membedakan serangan lambat dari genuine slow nodes [4]. Dengan demikian, satu indikator seperti durasi koneksi, jumlah paket, atau rendahnya throughput tidak cukup untuk menjadi dasar keputusan. Analisis membutuhkan kombinasi indikator dan pembanding terhadap traffic normal agar risiko false positive dapat dikurangi.

Analisis tersebut memerlukan bukti jaringan yang dapat ditelusuri. NIST SP 800-86 menempatkan pengumpulan dan pemeriksaan data sebagai bagian penting dalam penerapan teknik forensik pada penanganan insiden [5]. Dalam penelitian ini, Wireshark atau TShark digunakan pada komputer/VM pengamat untuk memperoleh capture komunikasi menuju web server ESP32. Wireshark mendukung live capture, penyimpanan capture file, filter, pemeriksaan protokol, dan statistik traffic [6]. Snort digunakan sebagai sumber validasi berbasis rule; dokumentasi resminya menjelaskan bahwa rule menentukan traffic yang diperiksa melalui header serta kriteria payload maupun non-payload pada rule body [7]. Korelasi kedua sumber tersebut memungkinkan analisis tidak hanya bergantung pada satu alert atau satu ringkasan traffic.

Berbagai metode telah dikaji untuk mendeteksi low-rate denial-of-service. Trejo-Rodríguez dkk. mengusulkan model untuk mendeteksi serangan low-rate pada lapisan transport dan aplikasi dengan penggunaan fitur yang terbatas guna mengurangi overhead pemrosesan [8]. Penelitian Guardian Node pada lingkungan IoT juga menunjukkan bahwa atribut jaringan dan pembandingan dengan node sah yang lambat merupakan aspek penting pada analisis Slow DoS [3]. Meskipun demikian, penggunaan metode statistik atau pembelajaran mesin tidak secara otomatis menyelesaikan persoalan keterlacakan alasan keputusan. Dalam konteks penelitian tugas akhir, hasil analisis perlu dapat menunjukkan fitur apa yang ditemukan, bukti apa yang belum tersedia, dan alasan suatu label diperbolehkan atau diblokir.

Repository `slowloris-attack` menerapkan pendekatan rule-based melalui `ScoringService`. Data eksperimen, hasil akuisisi Wireshark/TShark, dan validasi Snort diolah menjadi fitur terstruktur. Fitur tersebut kemudian dipetakan menjadi radar score dan weighted composite score 0–100. Keputusan tidak hanya bergantung pada nilai akhir karena sistem menerapkan evidence gate. Pada profil Slowloris, indikator yang diperiksa meliputi durasi koneksi, anomali header, bandwidth rendah dengan banyak koneksi, koneksi TCP, deviasi dari baseline, dan bukti Snort yang relevan. Sistem juga menjaga kemungkinan false positive melalui skenario HTTP burst, iPerf/bandwidth test, portscan, normal baseline, traffic TCP dominan non-HTTP, dan kekurangan bukti Snort. Pendekatan ini menjadikan keputusan lebih konsisten dan dapat diaudit, tetapi pemilihan fitur serta bobot awal tetap harus divalidasi dengan dataset eksperimen penelitian.

Large Language Model dapat membantu mengubah fitur dan hasil logika menjadi penjelasan yang lebih mudah ditinjau. Namun, LLM tidak tepat ditempatkan sebagai satu-satunya mesin deteksi. Houssel dkk. menemukan bahwa LLM memiliki potensi sebagai agen pelengkap untuk menjelaskan hasil NIDS, tetapi masih memiliki keterbatasan pada ketepatan deteksi dan dapat menghasilkan informasi yang tidak sesuai dengan data [9]. Atas dasar itu, repository menempatkan `AiValidationService` hanya sebagai validator. Payload AI memuat `evidence_contract`, hasil scoring, supporting indicators, gate reasons, dan missing evidence. LLM dibatasi agar hanya menggunakan nilai pada payload serta tidak boleh memberikan label `Slowloris Detected` ketika evidence gate tidak mengizinkannya.

Kerangka hibrida dalam penelitian ini menggabungkan tiga lapisan yang saling melengkapi. Lapisan pertama adalah bukti forensik jaringan dari Wireshark/TShark dan Snort. Lapisan kedua adalah penilaian rule-based beserta evidence gate sebagai sumber keputusan utama. Lapisan ketiga adalah validasi LLM untuk membandingkan hasil, menyusun penjelasan, dan menunjukkan bukti pendukung maupun bukti yang masih hilang. Objek fisik penelitian ditetapkan berupa [MODEL/VARIAN ESP32] yang menjalankan [VERSI FRAMEWORK DAN FIRMWARE ESP32] dengan endpoint [DAFTAR ENDPOINT HTTP ESP32] pada [TOPOLOGI LAB TERVERIFIKASI]. Seluruh data eksperimen harus diperoleh dari traffic yang benar-benar menuju perangkat tersebut; VM hanya digunakan sebagai mesin akuisisi dan analisis, bukan pengganti objek akhir penelitian.

Berdasarkan uraian tersebut, penelitian ini diarahkan untuk membangun dan mengevaluasi kerangka analisis yang tidak hanya memberikan label, tetapi juga menunjukkan hubungan antara bukti jaringan, skor, evidence gate, dan penjelasan LLM. Evaluasi dilakukan terhadap dataset ESP32 yang memiliki ground truth dengan menggunakan confusion matrix, accuracy, precision, recall, dan F1-score setelah [JUMLAH DAN KOMPOSISI DATASET] serta [DURASI DAN JUMLAH PENGULANGAN SKENARIO] tersedia. Pendekatan ini diharapkan menghasilkan analisis Slowloris yang transparan dan dapat ditinjau ulang tanpa mengklaim sebagai sistem deteksi universal. Oleh karena itu, penelitian ini mengambil judul “A Hybrid Network Forensics and Large Language Model Framework for Explainable Analysis of Slowloris Slow HTTP Request Attacks on ESP32 IoT Web Servers”.

## 1.2 Rumusan Masalah

Berdasarkan latar belakang yang telah dijelaskan, rumusan masalah dalam penelitian ini adalah sebagai berikut:

1. Bagaimana bukti forensik jaringan dari Wireshark/TShark dan Snort diolah menjadi fitur serta skor rule-based untuk menganalisis Slowloris Slow HTTP Request pada web server IoT ESP32?
2. Bagaimana evidence gate dan false-positive guards diterapkan agar label `Slowloris Detected` tidak diberikan ketika bukti yang tersedia belum memadai atau menunjukkan skenario pembanding?
3. Bagaimana Large Language Model digunakan sebagai validator berbasis `evidence_contract` untuk menghasilkan penjelasan yang dapat diaudit tanpa menggantikan keputusan rule-based?
4. Bagaimana kinerja kerangka hibrida dievaluasi menggunakan ground truth, confusion matrix, accuracy, precision, recall, dan F1-score pada dataset eksperimen ESP32?

## 1.3 Batasan Masalah

Pembatasan masalah digunakan agar penelitian tetap terarah dan pembahasannya sesuai dengan tujuan yang hendak dicapai. Batasan masalah pada penelitian ini adalah sebagai berikut:

1. Penelitian hanya dilakukan pada perangkat ESP32 milik peneliti di jaringan laboratorium lokal yang terisolasi dan tidak melibatkan target publik.
2. Objek fisik penelitian dibatasi pada [MODEL/VARIAN ESP32] yang menjalankan layanan web berbasis HTTP menggunakan [VERSI FRAMEWORK DAN FIRMWARE ESP32].
3. Objek traffic utama dibatasi pada Slowloris/Slow HTTP Request dengan profil Slowloris yang tersedia pada aplikasi.
4. Penelitian menganalisis metadata eksperimen, capture atau ringkasan Wireshark/TShark, log atau alert Snort, fitur hasil ekstraksi, skor rule-based, evidence gate, hasil validasi LLM, dan laporan yang tersimpan pada aplikasi.
5. Wireshark/TShark dan Snort dijalankan pada [SPESIFIKASI KOMPUTER/VM AKUISISI DAN ANALISIS] melalui [TITIK CAPTURE JARINGAN], bukan langsung pada ESP32.
6. `ScoringService` menjadi sumber keputusan utama, sedangkan LLM hanya menjadi validator dan penyusun penjelasan berdasarkan payload yang diberikan.
7. Label `Slowloris Detected` hanya dapat digunakan ketika evidence gate mengizinkannya dan bukti pendukung tersedia.
8. Skenario pembanding untuk menilai false positive dibatasi pada data yang benar-benar dikumpulkan, yaitu [DAFTAR SKENARIO NORMAL DAN PEMBANDING TERVERIFIKASI], dengan kandidat normal baseline, HTTP burst, iPerf/bandwidth test, dan portscan.
9. Evaluasi hanya menggunakan eksperimen yang memiliki ground truth tervalidasi dan pasangan data akuisisi serta validasi yang memenuhi kriteria kualitas penelitian.
10. Metrik evaluasi dibatasi pada confusion matrix, accuracy, precision, recall, dan F1-score. Nilainya belum disebutkan sebelum seluruh data eksperimen diproses.
11. Penelitian berfokus pada analisis defensif setelah data diperoleh dan tidak membahas pembuatan alat serangan, evasion, pemindaian publik, atau mitigasi real-time pada jaringan produksi.
12. Kesimpulan penelitian hanya berlaku pada konfigurasi ESP32, firmware, topologi, dataset, dan kondisi laboratorium yang didokumentasikan; hasilnya tidak digeneralisasi sebagai deteksi universal.

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

Bab ini menyajikan hasil eksperimen pada web server IoT ESP32, hasil ekstraksi fitur, scoring dan evidence gate, validasi LLM, confusion matrix dan metrik evaluasi, analisis false positive, serta pembahasan keterjelasan alasan keputusan.

**BAB V PENUTUP**

Bab ini berisi kesimpulan yang menjawab rumusan masalah berdasarkan hasil penelitian serta saran untuk pengembangan dan penelitian selanjutnya.

## 6. Daftar Pustaka Sementara

[1] Espressif Systems, “HTTP Server,” *ESP-IDF Programming Guide*, ver. 5.0.4. [Online]. Available: https://docs.espressif.com/projects/esp-idf/en/v5.0.4/esp32/api-reference/protocols/esp_http_server.html. [Accessed: Aug. 2, 2026].

[2] R. Fielding, M. Nottingham, and J. Reschke, “HTTP Semantics,” RFC 9110, Jun. 2022. [Online]. Available: https://www.rfc-editor.org/rfc/rfc9110.html.

[3] A. Reed, L. Dooley, and S. Kouadri Mostefaoui, “The Guardian Node Slow DoS Detection Model for Real-Time Application in IoT Networks,” *Sensors*, vol. 24, no. 17, Art. no. 5581, 2024, doi: 10.3390/s24175581.

[4] A. Reed, L. S. Dooley, and S. Kouadri Mostefaoui, “Minimal Overhead Modelling of Slow DoS Attack Detection for Resource-Constrained IoT Networks,” *Future Internet*, vol. 17, no. 10, Art. no. 432, 2025, doi: 10.3390/fi17100432.

[5] K. Kent, S. Chevalier, T. Grance, and H. Dang, *Guide to Integrating Forensic Techniques into Incident Response*, NIST Special Publication 800-86. Gaithersburg, MD, USA: National Institute of Standards and Technology, 2006, doi: 10.6028/NIST.SP.800-86.

[6] Wireshark Foundation, “Wireshark User’s Guide,” ver. 4.7.3. [Online]. Available: https://www.wireshark.org/docs/wsug_html/. [Accessed: Aug. 2, 2026].

[7] Cisco Talos Detection Response Team, “Snort 3 Rule Writing Guide.” [Online]. Available: https://docs.snort.org/. [Accessed: Aug. 2, 2026].

[8] L. A. Trejo-Rodríguez *et al*., “On the Detection of Low-Rate Denial of Service Attacks at Transport and Application Layers,” *Electronics*, vol. 10, no. 17, Art. no. 2105, 2021, doi: 10.3390/electronics10172105.

[9] P. R. B. Houssel, P. Singh, S. Layeghy, and M. Portmann, “Towards Explainable Network Intrusion Detection using Large Language Models,” *CoRR*, vol. abs/2408.04342, 2024, doi: 10.48550/arXiv.2408.04342.

> Seluruh entri harus diverifikasi kembali di ChatGPT Browser dan melalui reference manager sebelum dimasukkan ke naskah final.

## 7. Data Eksperimen ESP32 yang Masih Harus Dilengkapi

| Data | Status saat ini | Bukti yang dibutuhkan |
|---|---|---|
| Model/varian ESP32 | Belum tersedia | Foto/perangkat, label board, dan datasheet resmi |
| Framework dan versi firmware | Belum tersedia | Repository firmware, commit, dan konfigurasi build |
| Endpoint HTTP | Belum tersedia | Source code handler dan daftar URI aktual |
| Topologi laboratorium | Belum tersedia | Diagram, peran setiap host, dan jalur traffic |
| Titik capture | Belum tersedia | Interface dan bukti bahwa traffic menuju ESP32 terlihat |
| Konfigurasi komputer/VM analisis | Belum tersedia | CPU, RAM, OS, dan peran setiap mesin |
| Versi Wireshark/TShark | Belum tersedia | Output versi dan catatan eksperimen |
| Versi serta rule Snort | Belum tersedia | Konfigurasi, rule aktif, SID, dan versi |
| Skenario normal | Belum tersedia untuk ESP32 | Capture normal baseline dengan ground truth |
| Skenario Slowloris | Belum tersedia untuk ESP32 | Capture lab terkontrol dan ground truth |
| Skenario pembanding | Belum tersedia untuk ESP32 | Dataset HTTP burst, iPerf, portscan, atau skenario sah yang dipilih |
| Jumlah pengulangan dan durasi | Belum tersedia | Protokol eksperimen dan log waktu |
| Provider/model LLM | Belum ditetapkan sebagai hasil penelitian | Konfigurasi yang digunakan tanpa membuka API key |
| Confusion matrix | Belum dapat dihitung | Prediksi dan ground truth dataset final |
| Accuracy, precision, recall, F1-score | Belum dapat dihitung | Confusion matrix dataset final |
| Validasi pembimbing | Belum tersedia | Persetujuan judul, objek, batasan, dan metode |

## 8. Pemeriksaan Konsistensi Rumusan Masalah dan Tujuan

| No. | Rumusan Masalah | Tujuan Terkait | Data yang Dibutuhkan | Metode Analisis | Luaran yang Diharapkan |
|---|---|---|---|---|---|
| 1 | Pengolahan bukti jaringan menjadi fitur dan skor | Tujuan 1 | PCAP/ringkasan TShark, log Snort, metadata eksperimen | Ekstraksi fitur dan weighted rule-based scoring | Fitur, radar score, final score, kategori |
| 2 | Penerapan evidence gate dan false-positive guards | Tujuan 2 | Hasil scoring, bukti Snort, skenario pembanding | Pemeriksaan gate, missing evidence, dan false-positive guards | Keputusan yang diblokir atau diizinkan beserta alasannya |
| 3 | Pemanfaatan LLM sebagai validator | Tujuan 3 | Payload AI, evidence_contract, hasil rule-based, keluaran LLM | Validasi kontrak, supporting indicators, dan perbandingan hasil | Penjelasan berbasis bukti tanpa override gate |
| 4 | Evaluasi kinerja kerangka hibrida | Tujuan 4 | Ground truth dan keputusan sistem pada dataset final | TP, TN, FP, FN, accuracy, precision, recall, F1-score | Nilai evaluasi dan pembahasan keterbatasan |

## 9. Peta Ketertelusuran Repository

| Klaim | Status | Sumber repository |
|---|---|---|
| Laravel 11 digunakan sebagai aplikasi analisis | Terverifikasi | `composer.json`, `README.md` |
| ScoringService membangun fitur dan menghitung skor | Terverifikasi | `app/Services/ScoringService.php:86`, `app/Services/ScoringService.php:147`, `app/Services/ScoringService.php:240` |
| Evidence gate diterapkan setelah perhitungan skor | Terverifikasi | `app/Services/ScoringService.php:273`, `app/Services/ScoringService.php:552` |
| Profil Slowloris dan false-positive guards tersedia | Terverifikasi | `config/tool_profiles.php:7` |
| AnalysisService menyimpan fitur dan membentuk payload AI | Terverifikasi | `app/Services/AnalysisService.php:23`, `app/Services/AnalysisService.php:138`, `app/Services/AnalysisService.php:240` |
| Prompt AI melarang bukti buatan dan detected tanpa gate | Terverifikasi | `app/Services/AiPromptBuilder.php:43` |
| AiValidationService memblokir detected ketika kontrak tidak mengizinkan | Terverifikasi | `app/Services/AiValidationService.php:487`, `app/Services/AiValidationService.php:503`, `app/Services/AiValidationService.php:676` |
| Evaluasi accuracy, precision, recall, dan F1 tersedia | Terverifikasi | `app/Services/EvaluationMetricsService.php:14`, `app/Services/EvaluationMetricsService.php:115` |
| Model alur Experiment sampai FinalReport tersedia | Terverifikasi | `app/Models/Experiment.php`, `app/Models/AcquisitionFile.php`, `app/Models/ValidationFile.php`, `app/Models/SnortAlert.php`, `app/Models/ExtractedFeature.php`, `app/Models/AiResult.php`, `app/Models/FinalReport.php` |
| Kode mengenal metadata target platform | Terverifikasi pada kode | `app/Models/Experiment.php:32`, `app/Services/AnalysisService.php:212`, `database/migrations/2026_06_17_000000_add_tool_profile_analysis_fields.php` |
| ESP32 harus didukung data akuisisi nyata | Terverifikasi sebagai aturan proyek | `app/Services/VmLabExperimentTemplateService.php:83`, `docs/multi-ddos-ai-analysis-implementation-plan.md:403` |
| Database aktif berisi target_platform | Tidak terverifikasi; kolom belum ada saat pemeriksaan | `database/database.sqlite`, diperiksa 2 Agustus 2026 |
| Firmware dan endpoint ESP32 tersedia di repository | Belum ditemukan | Memerlukan repository atau folder firmware ESP32 |
| Dataset ESP32 aktual tersedia | Belum terbukti | Memerlukan PCAP, log Snort, metadata target, dan catatan eksperimen aktual |
| Struktur BAB I mengikuti DOCX acuan | Terverifikasi | `DRAFT SKRIPSI PLTP RIZKY FAIHAQI.docx`, paragraf BAB I pada subbab Latar Belakang sampai Sistematika Penulisan |

## 10. Catatan untuk Peneliti

1. Jangan menghapus ESP32 dari objek penelitian, tetapi jangan pula menyamakan dataset VM lama dengan dataset ESP32.
2. Jalankan migration dan pastikan `target_platform` benar-benar tersedia sebelum merekam eksperimen ESP32 baru.
3. Simpan firmware ESP32 atau minimal commit hash dan konfigurasi build agar objek penelitian dapat direplikasi.
4. Rekam setiap skenario dengan ground truth, durasi, waktu, titik capture, versi alat, dan pasangan PCAP–Snort yang jelas.
5. Setelah dataset ESP32 tersedia, ganti placeholder dan hitung metrik melalui alur evaluasi aplikasi sebelum menuliskan hasil akhir.
6. Validasi judul serta batasan penelitian dengan pembimbing sebelum melanjutkan BAB II dan BAB III.
