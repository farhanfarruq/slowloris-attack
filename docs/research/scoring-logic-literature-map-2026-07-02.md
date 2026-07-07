# Peta Rujukan Pembentukan Skor Logic

Tanggal revisi: 2026-07-02

Aturan umur rujukan: rujukan utama dipilih dari sumber 2021-07-02 ke atas. Sumber lama hanya boleh dipakai sebagai konteks historis atau standar teknis jika belum ada pengganti resmi yang lebih baru.

Scope: dasar akademik/resmi untuk menjelaskan `logic_score` pada beberapa `tool_profile`: `slowloris`, `torshammer`, `loic`, `hoic`, `hping3`, dan `xerxes`.

## Jawaban singkat

Skoring aplikasi **belum diubah di kode**. Yang direvisi di sini adalah dasar rujukan dan cara menjelaskan skoring.

Alasannya: jurnal/paper terbaru mendukung **pemilihan indikator** seperti durasi koneksi, request/header tidak lengkap, interval packet, packet volume, flow/traffic statistics, protocol/payload feature, dan Snort severity. Namun paper tersebut tidak memberi bobot final yang identik dengan profile aplikasi. Jadi bobot aplikasi tidak boleh diklaim "mengikuti persis jurnal"; klaim yang aman adalah:

```text
logic_score memakai weighted sum 0-100, dengan bobot profile yang disusun berdasarkan karakteristik serangan dan divalidasi memakai evidence gate Wireshark + Snort.
```

Perubahan bobot baru layak dilakukan jika sudah ada evaluasi eksperimen sendiri: confusion matrix, false positive/false negative, precision, recall, F1-score per profile.

## Model skor yang bisa dipertahankan

```text
logic_score = Σ(weight_i * normalized_metric_i)
```

Kondisi metodologis:

- tiap metrik dinormalisasi ke 0-100;
- bobot dalam satu profile berjumlah 1.00;
- kategori skor dipisahkan dari evidence gate;
- AI confidence hanya metrik tambahan kecil, bukan bukti utama;
- hasil akhir harus tetap menjelaskan alasan gate: bukti HTTP, packet/flow, Snort, dan false-positive guard.

## Rujukan terbaru yang paling relevan

| Tahun | Sumber | Dipakai untuk |
|---|---|---|
| 2025 | Chen et al., IEA-DMS: Interpretable feature-driven detection for Slow HTTP DoS, Computers & Security | Slow HTTP/Slowloris: koneksi lama, low-speed legitimate-looking traffic, packet interval, fitur interpretable. |
| 2024 | Rios et al., Detection of Slowloris Attacks using Machine Learning Algorithms, ACM SAC 2024 | Slowloris detection dan pembanding ML/Fuzzy Logic. |
| 2024 | Kapourchali et al., P4httpGuard, Cluster Computing | Slow-rate DDoS detection/prevention di SDN/P4. |
| 2024 | Wang et al., Detection and mitigation of DDoS attacks based on multi-dimensional characteristics in SDN, Scientific Reports | DDoS detection berbasis multi-dimensional traffic statistics. |
| 2025 | Springer Cybersecurity, Adaptive DDoS attack detection via packet payload feature selection | HTTP flood/payload feature, CIC-DDoS-2019 dan ISCX-SlowDoS-2016 validation. |
| 2025 | Real-time DDoS Attacks Detection using AI-based Algorithms, DiVA portal | Pembanding evaluasi model ML/DL pada CICIDS2017 dan CICDDoS2019. |
| 2025 | Weighted Sum Method + Multi-Criteria Decision-Making, MDPI Mathematics | Dasar weighted sum sebagai metode agregasi multi-kriteria. |
| 2023/2024 | FIRST CVSS v4.0 / NVD CVSS | Pembanding resmi untuk skor numerik, severity category, dan transparansi metric vector. |
| Current | Snort 3 Rule Writing Guide | Dasar `snort_alert_score` dari `classtype` dan `priority`. |
| 2023 | CIC-DDoS2019 Dataset, Mendeley Data | Dataset publik terbaru/tersedia untuk DDoS dan feature set CIC. |

## Mapping metrik ke rujukan baru

| Metrik aplikasi | Rujukan baru | Catatan |
|---|---|---|
| `connection_duration_score` | IEA-DMS 2025; ACM SAC Slowloris 2024; P4httpGuard 2024 | Slow HTTP mempertahankan koneksi lama/low-speed sehingga durasi tetap indikator inti. |
| `header_anomaly_score` | IEA-DMS 2025; ACM SAC Slowloris 2024 | Slowloris/slow header memakai request/header tidak selesai. |
| `low_bandwidth_high_connection_score` | IEA-DMS 2025; P4httpGuard 2024 | Traffic lambat dan low volume tidak boleh dibaca seperti flood biasa. |
| `tcp_connection_score` | IEA-DMS 2025; P4httpGuard 2024 | Slow HTTP berada di HTTP/HTTPS di atas TCP, tetapi TCP saja tidak cukup tanpa evidence gate. |
| `packet_volume_score` | Scientific Reports 2024; Springer Cybersecurity 2025; CIC-DDoS2019 dataset | Flood profile perlu packet/traffic statistics. |
| `connection_volume_score` | Scientific Reports 2024; DiVA 2025 | Volume koneksi/flow relevan untuk DDoS/flood profile. |
| `http_volume_score` | Springer Cybersecurity 2025; IEA-DMS 2025 | HTTP flood dan slow HTTP sama-sama butuh bukti HTTP, tetapi interpretasinya berbeda. |
| `throughput_pressure_score` | Scientific Reports 2024; DiVA 2025 | Throughput/bandwidth pressure cocok untuk volumetric DDoS. |
| `transport_flood_score` | Scientific Reports 2024; CIC-DDoS2019 dataset | TCP/UDP/ICMP/SYN-like flood dinilai lewat dominasi layer transport/network. |
| `snort_alert_score` | Snort 3 `classtype` dan `priority` | Severity alert boleh jadi bukti validasi, bukan pengganti traffic feature. |
| `baseline_deviation_score` | Scientific Reports 2024; DiVA 2025 | Deteksi modern tetap membandingkan pola traffic normal vs attack. |
| `ai_confidence_score` | DiVA 2025; CVSS/NVD transparency principle | AI/ML membantu validasi, tetapi skor harus transparan dan tidak override evidence gate. |

## Mapping tool profile

### `slowloris`

Bobot saat ini:

```text
connection_duration 0.20
header_anomaly 0.20
low_bandwidth_high_connection 0.15
snort_alert 0.20
tcp_connection 0.10
baseline_deviation 0.10
ai_confidence 0.05
```

Status: **masih sesuai secara konsep**.

Rujukan 2024-2025 mendukung indikator durasi koneksi, slow/low-speed traffic, incomplete HTTP/header behavior, packet interval, dan fitur interpretable. Tidak ada kebutuhan langsung mengubah bobot tanpa hasil evaluasi lokal.

### `torshammer`

Bobot saat ini:

```text
connection_duration 0.25
low_bandwidth_high_connection 0.20
header_anomaly 0.15
snort_alert 0.20
http_volume 0.10
ai_confidence 0.10
```

Status: **masih sesuai secara konsep**, karena Torshammer masuk keluarga slow HTTP.

Catatan: `ai_confidence` 0.10 agak besar dibanding profile lain. Kalau nanti evaluasi menunjukkan AI sering false positive, turunkan ke 0.05 dan pindahkan 0.05 ke `snort_alert` atau `connection_duration`.

### `loic`

Bobot saat ini:

```text
packet_volume 0.20
connection_volume 0.20
throughput_pressure 0.15
http_volume 0.15
transport_flood 0.10
snort_alert 0.15
ai_confidence 0.05
```

Status: **masih sesuai secara konsep** untuk multi-vector flood.

Paper terbaru mendukung traffic statistics, packet/flow feature, dan ML validation pada CIC-DDoS2019. Karena LOIC bisa HTTP/TCP/UDP, bobot seimbang packet + connection masih masuk akal.

### `hoic`

Bobot saat ini:

```text
http_volume 0.25
connection_volume 0.20
packet_volume 0.15
throughput_pressure 0.15
snort_alert 0.20
ai_confidence 0.05
```

Status: **paling konsisten** dengan HTTP flood profile.

`http_volume` sebagai bobot tertinggi cocok karena HOIC dominan HTTP request flood. Tetap butuh `snort_alert` dan volume/connection untuk menahan false positive dari traffic HTTP normal.

### `hping3`

Bobot saat ini:

```text
transport_flood 0.25
packet_volume 0.20
connection_volume 0.15
snort_alert 0.25
baseline_deviation 0.10
ai_confidence 0.05
```

Status: **masih sesuai secara konsep** untuk layer 3/4 flood.

Paper DDoS terbaru mendukung multi-dimensional traffic statistics. `transport_flood` dan `snort_alert` sebagai bobot tertinggi masuk akal karena hping3 sering dipakai untuk SYN/UDP/ICMP-like testing di lab.

### `xerxes`

Bobot saat ini:

```text
connection_volume 0.25
packet_volume 0.20
http_volume 0.15
transport_flood 0.15
snort_alert 0.20
ai_confidence 0.05
```

Status: **cukup sesuai**, tetapi paling perlu validasi lokal.

Jika data eksperimen Xerxes di lab ternyata dominan HTTP, `http_volume` bisa dinaikkan. Jika dominan TCP/connection flood, bobot sekarang sudah tepat. Jangan ubah sebelum melihat hasil eksperimen.

## Apakah skoring perlu diubah?

Belum. Revisi rujukan **tidak otomatis mengubah skoring**.

Yang berubah:

- daftar rujukan utama sekarang dominan 2024-2025;
- narasi pembenaran bobot diperketat;
- sumber lama dipindah menjadi historis;
- klaim "sesuai jurnal" diganti menjadi "indikator dan model agregasi didukung jurnal".

Yang tidak berubah:

- rumus weighted sum;
- bobot tiap `tool_profile`;
- threshold kategori;
- evidence gate.

Perubahan bobot disarankan hanya setelah evaluasi lokal, misalnya:

```text
TP, TN, FP, FN per profile
precision, recall, F1-score
kasus false positive: normal, HTTP burst, iPerf, portscan
kasus false negative: attack profile yang lolos rendah
```

## Link cepat sumber

| Sumber | Link |
|---|---|
| IEA-DMS, Computers & Security 2025 | https://www.sciencedirect.com/science/article/abs/pii/S0167404824005972 |
| Detection of Slowloris Attacks using Machine Learning Algorithms, ACM SAC 2024 | https://dl.acm.org/doi/10.1145/3605098.3635919 |
| Open manuscript ACM SAC Slowloris 2024 | https://hal.science/hal-04621123/document |
| P4httpGuard, Cluster Computing 2024 | https://dl.acm.org/doi/abs/10.1007/s10586-024-04407-5 |
| Detection and mitigation of DDoS attacks in SDN, Scientific Reports 2024 | https://www.nature.com/articles/s41598-024-66907-z |
| Adaptive DDoS attack detection via packet payload feature selection, Springer Cybersecurity 2025 | https://link.springer.com/article/10.1186/s42400-025-00495-x |
| Real-time DDoS Attacks Detection using AI-based Algorithms, DiVA 2025 | https://www.diva-portal.org/smash/get/diva2%3A2017528/FULLTEXT01.pdf |
| Weighted Sum Method + MCDM, MDPI Mathematics 2025 | https://www.mdpi.com/2227-7390/13/11/1704 |
| FIRST CVSS v4.0 Specification | https://www.first.org/cvss/specification-document |
| NVD CVSS overview | https://nvd.nist.gov/vuln-metrics/cvss |
| Snort 3 `classtype` | https://docs.snort.org/rules/options/general/classtype |
| Snort 3 `priority` | https://docs.snort.org/rules/options/general/priority |
| CIC-DDoS2019 Dataset, Mendeley Data | https://data.mendeley.com/datasets/ssnc74xm6r/1 |

## Sumber lama: jangan jadi rujukan utama

Sumber berikut tetap berguna, tetapi umurnya melewati batas 5 tahun untuk skripsi:

- CICIDS2017/CICIDS2018 original paper dan halaman dataset lama;
- CICDDoS2019 original IEEE paper 2019;
- RFC 4987;
- Mirkovic and Reiher DDoS taxonomy;
- paper Slow HTTP DoS sebelum 2021;
- Masaryk technical report 2014.

Pakai hanya jika dosen mengizinkan sumber historis/standar teknis. Untuk teori utama, pakai daftar 2024-2025 di atas.
