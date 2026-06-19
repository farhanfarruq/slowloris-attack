# Scoring Literature Analysis

Tanggal riset: 2026-06-18

## Kesimpulan

`Final Attack Score` di aplikasi adalah skor komposit 0-100 berbasis tool profile. Nama outputnya umum, tetapi metric dan bobotnya berbeda untuk Slowloris, LOIC, HOIC, Hping3, Torshammer, dan Xerxes.

Indikator yang dipakai sudah selaras dengan literatur deteksi DoS/DDoS defensif: durasi koneksi, incomplete HTTP/header behavior, low-bandwidth high-connection behavior, packet/byte/connection volume, transport flood signal, HTTP volume, baseline deviation, dan alert IDS.

Namun, angka bobot persis di `config/tool_profiles.php` saat ini belum bisa diklaim berasal langsung dari satu jurnal tertentu. Status paling tepat: bobot heuristic berbasis literatur dan desain lab, lalu harus dikalibrasi atau divalidasi dengan dataset eksperimen penelitian.

## Sumber Utama

1. CICDDoS2019, Canadian Institute for Cybersecurity, University of New Brunswick
   - URL: https://www.unb.ca/cic/datasets/ddos-2019.html
   - Relevansi: dataset DDoS modern; paper menyatakan ada taxonomy, network flow features, dan feature sets dengan corresponding weights untuk tipe DDoS.
   - Cocok untuk: kalibrasi bobot packet volume, flow/connection volume, transport protocol, HTTP/application features, dan klasifikasi keluarga DDoS.

2. A Proposed DoS Detection Scheme for Mitigating DoS Attack Using Data Mining Techniques, Computers, 2019
   - URL: https://www.mdpi.com/2073-431X/8/4/85
   - Relevansi: traffic DoS tools dianalisis memakai Wireshark, detection scheme diuji dengan Snort IDS, dan dibandingkan dengan mekanisme lain.
   - Cocok untuk: pembenaran penggunaan Wireshark + Snort sebagai evidence source.

3. A Reliable Real-Time Slow DoS Detection Framework for Resource-Constrained IoT Networks
   - URL: https://oro.open.ac.uk/79542/2/GlobeCom%20Camera%20Ready.pdf
   - Relevansi: Slow DoS/Slowloris sulit dibedakan dari node legitimate yang lambat; framework memakai atribut ringan seperti packet length dan packet delta time.
   - Cocok untuk: pembenaran low-bandwidth, timing, dan false-positive guard terhadap koneksi lambat normal.

4. A Method for Preventing Slow HTTP DoS Attacks, SECURWARE 2017
   - URL: https://personales.upv.es/thinkmind/dl/conferences/securware/securware_2017_4_30_30032.pdf
   - Relevansi: Slow HTTP Headers/Slowloris memperpanjang TCP session dengan mengirim HTTP request header sedikit demi sedikit.
   - Cocok untuk: connection duration score, header anomaly score, incomplete request behavior.

5. SDToW: A Slowloris Detecting Tool for WMNs, Information, 2020
   - URL: https://www.mdpi.com/2078-2489/11/12/544
   - Relevansi: Slowloris mempertahankan request tidak selesai dan request periodik; paper juga menekankan risiko false positive jika hanya membatasi parallel connection.
   - Cocok untuk: evidence gating, bukan sekadar jumlah koneksi.

6. Traffic Characteristics of Common DoS Tools, Masaryk University technical report, 2014
   - URL: https://www.fi.muni.cz/reports/files/2014/FIMU-RS-2014-02.pdf
   - Relevansi: packet rate dan byte rate disebut sebagai feature umum deteksi DoS; report membandingkan traffic berbagai DoS tools termasuk LOIC, HOIC, Torshammer, Slowloris, dan Xerxes.
   - Cocok untuk: packet_volume_score, throughput_pressure_score, connection_volume_score.

7. Snort 3 Rule Writing Guide - priority and classtype
   - URL: https://docs.snort.org/rules/options/general/priority
   - URL: https://docs.snort.org/rules/options/general/classtype
   - Relevansi: Snort rule punya severity priority dan attack classification.
   - Cocok untuk: snort_alert_score berbasis severity/priority.

8. Wireshark User's Guide
   - URL: https://www.wireshark.org/docs/wsug_html_chunked/ChapterIntroduction.html
   - Relevansi: Wireshark adalah packet analyzer yang menampilkan packet data detail dan menyediakan statistik/filter.
   - Cocok untuk: legitimasi packet capture sebagai sumber fitur.

## Mapping Metric ke Literatur

| Metric aplikasi | Didukung literatur | Catatan |
|---|---|---|
| `connection_duration_score` | Slow HTTP DoS memperpanjang TCP session; Slowloris mempertahankan koneksi/request tidak selesai | Kuat untuk Slowloris/Torshammer, kurang relevan untuk flood volume murni |
| `header_anomaly_score` | Slow HTTP Headers/Slowloris mengirim header sedikit demi sedikit dan tidak menyelesaikan request | Kuat untuk Slowloris/Torshammer |
| `low_bandwidth_high_connection_score` | Slow DoS berbahaya karena memakai bandwidth rendah dan mirip traffic legitimate lambat | Kuat, tetapi harus digate agar tidak salah label koneksi lambat normal |
| `packet_volume_score` | Packet rate adalah feature umum deteksi DoS | Kuat untuk LOIC/HOIC/Hping3/Xerxes |
| `connection_volume_score` | Banyak koneksi/open or semi-open TCP connections digunakan dalam banyak DoS | Kuat untuk flood dan beberapa HTTP DoS |
| `throughput_pressure_score` | Byte rate/traffic volume umum dipakai untuk deteksi DoS | Kuat untuk volume-based profiles |
| `http_volume_score` | Application-layer HTTP flood/HTTP DoS butuh ukuran volume HTTP | Kuat untuk HOIC/LOIC HTTP/Xerxes |
| `transport_flood_score` | SYN/UDP/ICMP flood berbasis transport/network layer | Kuat untuk Hping3 dan flood transport |
| `snort_alert_score` | Snort menyediakan alert classification dan priority severity | Kuat sebagai evidence validator, bukan satu-satunya bukti |
| `baseline_deviation_score` | Anomaly detection membandingkan traffic terhadap baseline normal | Kuat jika baseline lab tersedia dan konsisten |
| `ai_confidence_score` | Tidak boleh dijadikan bukti utama serangan | Tepat sebagai bobot kecil/pembanding; confidence AI bukan attack probability |

## Bobot Saat Ini

Bobot saat ini berasal dari `config/tool_profiles.php`.

| Profile | Metric utama | Status akademik |
|---|---|---|
| Slowloris | duration, header anomaly, low bandwidth high connection, Snort, TCP, baseline, AI | Indikator kuat; bobot numerik masih heuristic |
| LOIC | packet volume, connection volume, throughput, HTTP volume, transport flood, Snort, AI | Indikator sesuai literatur volume/flood; bobot numerik masih heuristic |
| HOIC | HTTP volume, connection volume, packet volume, throughput, Snort, AI | Indikator sesuai HTTP flood; bobot numerik masih heuristic |
| Hping3 | transport flood, packet volume, connection volume, Snort, baseline, AI | Indikator sesuai SYN/UDP/ICMP flood; bobot numerik masih heuristic |
| Torshammer | duration, low bandwidth high connection, header anomaly, Snort, HTTP volume, AI | Indikator sesuai slow HTTP; bobot numerik masih heuristic |
| Xerxes | connection volume, packet volume, HTTP volume, transport flood, Snort, AI | Indikator sesuai flood/connection pressure; bobot numerik masih heuristic |

## Gap Metodologi

1. Jangan tulis bahwa bobot 0.20/0.15/dst "diambil dari jurnal" kecuali sudah ada tabel feature weight yang cocok langsung.
2. Literatur mendukung pemilihan feature, bukan otomatis mendukung angka bobot final aplikasi.
3. Untuk penelitian, bobot harus punya salah satu justifikasi:
   - expert judgment berbasis literatur dan divalidasi eksperimen lab;
   - feature importance dari dataset publik seperti CICDDoS2019;
   - feature importance dari dataset penelitian sendiri;
   - kombinasi dataset publik + dataset lab.
4. Threshold kategori 0-30, 31-55, 56-75, 76-100 juga perlu disebut sebagai operational threshold aplikasi, bukan standar universal.

## Rekomendasi Perbaikan Penelitian

1. Pisahkan istilah:
   - "literature-supported features" untuk metric;
   - "lab-calibrated weights" untuk angka bobot.
2. Tambahkan tabel referensi pada metodologi:
   - metric;
   - definisi;
   - sumber data eksperimen;
   - dasar literatur;
   - profile yang memakai metric.
3. Kalibrasi bobot dari data:
   - kumpulkan hasil eksperimen tiap profile dan baseline;
   - labeli ground truth: normal, suspicious, attack profile;
   - hitung feature importance dengan metode defensif, misalnya Random Forest importance, permutation importance, Information Gain, atau logistic regression coefficients;
   - normalisasi importance menjadi bobot per profile;
   - bandingkan dengan bobot heuristic saat ini.
4. Simpan provenance bobot di config:
   - `source_type`: `heuristic_literature`, `lab_calibrated`, atau `dataset_calibrated`;
   - `source_refs`: daftar referensi;
   - `calibrated_at`: tanggal;
   - `dataset_version`: nama dataset/capture set.
5. UI/report sebaiknya menampilkan "score profile source" agar auditor tahu skor berasal dari bobot heuristic atau bobot hasil kalibrasi.

## Formulasi Akademik yang Aman

Kalimat yang aman untuk laporan:

> Sistem menggunakan weighted composite score 0-100. Pemilihan fitur didasarkan pada literatur deteksi Slow HTTP DoS dan DDoS flow-based detection, sedangkan bobot awal ditetapkan sebagai heuristic berbasis profile serangan dan divalidasi terhadap dataset eksperimen lab. Bobot tidak diklaim sebagai standar universal dan dapat dikalibrasi ulang menggunakan feature importance dari dataset penelitian.

Kalimat yang harus dihindari:

> Bobot 0.20, 0.15, dan seterusnya berasal dari jurnal X.

Kecuali jurnal X memang memuat bobot yang sama dan metodologinya cocok.
