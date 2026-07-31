# Roadmap Fitur Evaluasi dan Auditability

Dokumen ini berisi rencana fitur lanjutan untuk memperkuat project Slowloris/DDoS Defensive Lab Dashboard. Fokusnya bukan menambah kemampuan serangan, tetapi memperkuat pembuktian riset: data coverage, evidence trail, evaluasi metrik, false positive guard, dan laporan yang bisa diaudit.

Scope aman:

- Analisis defensif dari data lab lokal.
- Validasi berbasis Wireshark/dumpcap/tshark dan Snort.
- Scoring, AI validation, report, dan evaluasi.
- Tidak menambahkan attack automation, bypass, evasion, scanning target publik, atau tombol eksekusi serangan.

## Ringkasan Prioritas

| Prioritas | Fitur | Tujuan Utama | Nilai Untuk Project |
|---|---|---|---|
| 1 | Dataset Coverage Checklist | Melihat kelengkapan sample per tool profile | Menghindari klaim akurasi dari data yang kurang |
| 2 | Evidence Drilldown Per Eksperimen | Menjelaskan dasar keputusan sistem | Memudahkan audit pembimbing/penguji |
| 3 | Evaluation Export | Export metrik evaluasi untuk laporan | Memperkuat BAB IV / hasil pengujian |
| 4 | False Positive Guard Dashboard | Melihat proteksi terhadap alarm palsu | Membuktikan sistem tidak asal deteksi attack |
| 5 | AI Disagreement Queue | Melihat kasus AI dan logic berbeda | Menjaga AI sebagai validator, bukan hakim utama |
| 6 | Experiment Quality Score | Menilai kelayakan data sebelum evaluasi | Mencegah data tidak lengkap masuk klaim |
| 7 | Profile Calibration Review | Simulasi threshold dan dampak metrik | Membantu tuning secara terukur |
| 8 | Report Evidence Bundle | Mengumpulkan bukti per eksperimen | Memudahkan lampiran dan audit teknis |

## Status Implementasi Saat Ini

Status per fitur:

| Fitur | Status | Implementasi |
|---|---|---|
| Dataset Coverage Checklist | Implemented | Panel `Dataset Coverage Per Profile` di halaman Evaluation |
| Experiment Quality Score | Implemented | Badge `Quality` per eksperimen di halaman Evaluation |
| Evidence Drilldown Per Eksperimen | Implemented | Panel `Evidence Drilldown` di detail eksperimen |
| Evaluation Export | Implemented | Command dan route download JSON/CSV/Markdown |
| False Positive Guard Dashboard | Implemented | Panel `False Positive Guard` di halaman Evaluation |
| AI Disagreement Queue | Implemented | Panel `AI Disagreement Queue` di halaman Evaluation |
| Report Evidence Bundle | Implemented | Command dan route download Markdown per eksperimen |
| Profile Calibration Review | Implemented minimal | UI, command, export snapshot, dan simpan snapshot audit tanpa mengubah data asli |

Command yang tersedia:

```bash
php artisan lab:evaluate-profiles --format=json
php artisan lab:evaluate-profiles --format=csv
php artisan lab:evaluate-profiles --format=md
php artisan lab:evidence-bundle EXP-001
php artisan lab:calibrate-profile slowloris --detected=95 --suspicious=56
php artisan lab:calibrate-profile slowloris --detected=95 --suspicious=56 --format=md --output=storage/app/reports/calibration-slowloris.md
```

Route UI yang tersedia:

```text
/evaluation
/evaluation/export/json
/evaluation/export/csv
/evaluation/export/md
/evaluation?calibration_profile=slowloris&detected=95&suspicious=56
/evaluation/calibration/export/md?calibration_profile=slowloris&detected=95&suspicious=56
/experiments/{experiment}/evidence-bundle
```

Yang sengaja belum dibuat:

- Mengaktifkan threshold calibration ke scoring runtime.
- UI editor threshold.
- Perubahan otomatis pada `experiment_status` dari hasil simulasi calibration.

Alasan: calibration masih harus menjadi simulasi sampai dataset coverage cukup. Snapshot simulasi sudah bisa disimpan untuk audit, tetapi belum dipakai sebagai threshold aktif.

## Phase 1 - Fondasi Evaluasi Lokal

Tujuan phase ini adalah membuat data evaluasi bisa dipercaya sebelum mempercantik UI.

### Fitur 1: Dataset Coverage Checklist

Masalah yang diselesaikan:

- Metrik precision/recall/F1 tidak kuat kalau sample per profile belum lengkap.
- Beberapa tool profile bisa terlihat bagus karena belum punya sample pembanding false positive.

Output fitur:

- Halaman atau panel coverage per tool profile.
- Status sample attack, normal, dan comparison.
- Status evidence: acquisition ada, validation ada, extracted feature ada, ground truth ada.

Data yang digunakan:

- `experiments.tool_profile`
- `experiments.ground_truth_label`
- `experiments.experiment_status`
- relasi `acquisitionFiles`
- relasi `validationFiles`
- relasi `extractedFeature`

Contoh indikator:

| Field | Arti |
|---|---|
| attack_samples | Jumlah eksperimen attack untuk profile terkait |
| normal_samples | Jumlah baseline normal yang tersedia |
| false_positive_guards | Jumlah sample pembanding seperti HTTP burst, iPerf, portscan |
| ready_for_evaluation | Ground truth dan status final tersedia |
| missing_evidence | File/bukti yang belum lengkap |

Implementasi minimal:

1. Buat service kecil, misalnya `DatasetCoverageService`.
2. Query semua profile dari `config/tool_profiles.php`.
3. Hitung coverage per profile dari tabel `experiments`.
4. Tampilkan di halaman Evaluation atau halaman baru `Coverage`.

Acceptance criteria:

- Semua profile tetap muncul walau sample masih 0.
- Profile dengan data belum lengkap diberi status `Incomplete`.
- Tidak ada klaim akurasi tanpa menampilkan jumlah sample.

Test minimal:

- Feature test: profile tanpa sample tetap muncul.
- Feature test: sample attack + normal + validation lengkap menghasilkan status ready.

### Fitur 2: Experiment Quality Score

Masalah yang diselesaikan:

- Eksperimen yang metadata atau bukti teknisnya kurang bisa ikut terbaca seolah valid.
- Reviewer sulit tahu data mana yang layak masuk evaluasi.

Output fitur:

- Status kualitas per eksperimen: `Ready`, `Needs Review`, atau `Incomplete`.
- Daftar alasan kualitas data.

Rule awal:

| Kondisi | Dampak |
|---|---|
| ground_truth_label kosong/unknown | Incomplete |
| experiment_status pending/inconclusive | Needs Review |
| tidak ada acquisition file | Incomplete |
| tidak ada extracted feature | Needs Review |
| attack sample tanpa validation/Snort | Needs Review |
| tool_profile kosong/tidak dikenal | Incomplete |

Implementasi minimal:

1. Buat method evaluator kualitas, bisa di service terpisah atau di service coverage.
2. Return array sederhana: `status`, `score`, `reasons`.
3. Tampilkan badge di detail eksperimen dan evaluation rows.

Acceptance criteria:

- Eksperimen tidak lengkap tidak disembunyikan; alasan ditampilkan.
- Status kualitas tidak mengubah hasil scoring, hanya memberi konteks evaluasi.

Test minimal:

- Ground truth kosong menghasilkan `Incomplete`.
- Eksperimen lengkap menghasilkan `Ready`.

## Phase 2 - Auditability Keputusan Sistem

Tujuan phase ini adalah membuat setiap keputusan bisa dijelaskan dari bukti, bukan hanya label akhir.

### Fitur 3: Evidence Drilldown Per Eksperimen

Masalah yang diselesaikan:

- Reviewer bisa bertanya: "Kenapa sistem bilang ini attack?"
- Saat ini bukti tersebar di acquisition, validation, extracted feature, AI result, dan report.

Output fitur:

- Bagian detail eksperimen yang menampilkan:
  - final decision
  - final attack score
  - attack category
  - active tool profile
  - passed gates
  - failed gates
  - missing evidence
  - key Wireshark/acquisition signals
  - key Snort/validation signals
  - AI classification dan apakah sesuai evidence contract

Data yang digunakan:

- `ExtractedFeature.raw_features`
- `ExtractedFeature.final_attack_score`
- `ExtractedFeature.attack_category`
- `Experiment.experiment_status`
- latest/current `AiResult`
- `SnortAlert`
- `AcquisitionFile.parsed_summary`

Implementasi minimal:

1. Buat service presenter, misalnya `ExperimentEvidenceService`.
2. Service hanya membaca data yang sudah tersimpan.
3. Tampilkan panel baru di `experiments/show.blade.php`.
4. Jangan menjalankan ulang serangan atau mengambil data eksternal.

Acceptance criteria:

- Gate reason terlihat jelas.
- Missing evidence terlihat jelas.
- AI disagreement terlihat, tapi tidak override logic scoring.

Test minimal:

- Eksperimen dengan `raw_features.evidence_gates` menampilkan gate passed/failed.
- Eksperimen tanpa Snort menampilkan missing validation evidence.

### Fitur 4: Report Evidence Bundle

Masalah yang diselesaikan:

- Saat membuat laporan, bukti per eksperimen harus dikumpulkan manual.
- Reviewer membutuhkan jejak data end-to-end.

Output fitur:

- Tombol atau command untuk membuat bundle bukti per eksperimen.
- Format awal cukup JSON atau Markdown.
- PDF bisa ditambahkan belakangan jika sudah stabil.

Isi bundle:

- metadata eksperimen
- ground truth
- acquisition summary
- validation/Snort summary
- extracted features
- scoring result
- evidence gate reasons
- AI validation result
- final decision

Implementasi minimal:

1. Reuse data dari `ExperimentEvidenceService`.
2. Tambah command: `php artisan lab:evidence-bundle {experiment_id}`.
3. Output ke `storage/app/reports/evidence-bundles/`.

Acceptance criteria:

- Tidak menyimpan API key, token, password, atau credential.
- Bundle bisa dibuat ulang dari data lokal.
- Jika data kurang, tulis `missing`, bukan mengarang.

Test minimal:

- Command membuat file bundle.
- Bundle berisi experiment_code, ground_truth_label, experiment_status, dan gate reasons.

## Phase 3 - Evaluasi dan Laporan Metrik

Tujuan phase ini adalah membuat hasil evaluasi siap dipakai untuk laporan dan presentasi.

### Fitur 5: Evaluation Export

Masalah yang diselesaikan:

- Metrik sudah bisa dihitung, tetapi laporan butuh output yang mudah disalin/dilampirkan.

Output fitur:

- Export CSV untuk tabel per eksperimen.
- Export JSON untuk data mentah.
- Export Markdown untuk ringkasan laporan.

Basis yang sudah ada:

- `EvaluationMetricsService`
- command `php artisan lab:evaluate-profiles --json`
- halaman `/evaluation`

Implementasi minimal:

1. Tambahkan option command:
   - `--format=json`
   - `--format=csv`
   - `--format=md`
   - `--output=path`
2. Untuk UI, tambah tombol export di halaman Evaluation.
3. Gunakan service yang sama agar UI dan command tidak beda rumus.

Acceptance criteria:

- Angka di UI sama dengan angka di export.
- CSV memuat row eksperimen: code, profile, ground truth, predicted, TP/TN/FP/FN/PM.
- Markdown memuat ringkasan singkat plus tabel per profile.

Test minimal:

- Command CSV menghasilkan header yang benar.
- Command Markdown berisi accuracy, precision, recall, F1.

### Fitur 6: False Positive Guard Dashboard

Masalah yang diselesaikan:

- Project perlu membuktikan sistem tidak asal memberi label attack.
- Sample normal dan pembanding perlu terlihat terpisah dari sample attack.

Output fitur:

- Halaman/panel false positive guard.
- Kategori:
  - baseline normal
  - HTTP burst
  - iPerf bandwidth
  - portscan
  - TCP-dominant non-HTTP
  - missing Snort evidence

Metric yang ditampilkan:

- jumlah sample per kategori
- jumlah yang tetap `normal`
- jumlah yang menjadi `suspicious`
- jumlah yang salah menjadi `attack_detected`
- false positive rate

Implementasi minimal:

1. Ambil sample dari `ground_truth_label = normal` atau scenario comparison.
2. Kelompokkan dari `scenario_key`, `traffic_type`, dan `attack_pattern`.
3. Tampilkan ringkasan FP per kategori.

Acceptance criteria:

- HTTP burst, iPerf, dan portscan dapat terlihat sebagai sample pembanding.
- False positive rate dihitung per kategori.
- Data kosong tetap ditampilkan sebagai gap, bukan disembunyikan.

Test minimal:

- Normal sample yang diprediksi `attack_detected` dihitung FP.
- Normal sample yang diprediksi `normal` dihitung TN.

## Phase 4 - Analisis Perbedaan Logic dan AI

Tujuan phase ini adalah menjadikan AI sebagai bahan validasi yang bisa diaudit, bukan sumber keputusan utama.

### Fitur 7: AI Disagreement Queue

Masalah yang diselesaikan:

- AI dan logic scoring bisa berbeda pendapat.
- Perbedaan ini perlu dianalisis, bukan langsung dianggap bug.

Output fitur:

- Halaman daftar disagreement.
- Kolom:
  - experiment code
  - tool profile
  - logic classification
  - AI classification
  - evidence contract allowed/blocked
  - confidence
  - reviewer status
  - reviewer note

Kategori disagreement:

| Kategori | Arti |
|---|---|
| AI over-detect | AI bilang detected, evidence gate melarang |
| AI under-detect | Logic kuat, AI tidak setuju |
| Profile mismatch | AI atau logic memakai profile yang tidak sama |
| Inconclusive | AI tidak bisa memberi JSON/label valid |

Implementasi minimal:

1. Query `AiResult` yang classification berbeda dari logic result.
2. Gunakan `evidence_contract` dari payload/raw features bila tersedia.
3. Reuse `ReviewerNote` untuk catatan.

Acceptance criteria:

- Disagreement tidak mengubah final verdict.
- Reviewer bisa menandai status review.
- AI yang melanggar evidence contract terlihat jelas.

Test minimal:

- AI detected saat gate blocked masuk kategori `AI over-detect`.
- Logic detected tapi AI normal masuk kategori `AI under-detect`.

## Phase 5 - Kalibrasi dan Penguatan Klaim

Tujuan phase ini adalah membantu tuning scoring dengan bukti metrik, bukan feeling.

### Fitur 8: Profile Calibration Review

Masalah yang diselesaikan:

- Threshold dan bobot scoring perlu dievaluasi terhadap dataset lokal.
- Perubahan scoring harus terlihat dampaknya ke precision/recall/F1.

Output fitur:

- Mode review untuk simulasi threshold.
- Tidak langsung mengubah config produksi.
- Tampilkan eksperimen yang berubah status.

Input awal:

- selected profile
- threshold detected
- threshold suspicious
- optional: weight override sederhana

Output:

- metrik sebelum
- metrik simulasi
- daftar eksperimen berubah:
  - old status
  - simulated status
  - alasan perubahan

Implementasi minimal:

1. Mulai dari threshold simulation, bukan weight editor penuh.
2. Gunakan data `ExtractedFeature.final_attack_score`.
3. Jangan persist perubahan threshold dulu.
4. Jika diperlukan nanti, baru tambah save config dengan audit log.

Acceptance criteria:

- Simulasi tidak mengubah `experiment_status`.
- Dampak ke precision, recall, dan F1 terlihat.
- Eksperimen yang berubah status bisa dilihat.

Test minimal:

- Threshold naik membuat beberapa detected turun menjadi suspicious/normal di simulasi.
- Data asli tidak berubah setelah simulasi.

## Urutan Implementasi Yang Disarankan

Urutan paling aman:

1. Dataset Coverage Checklist.
2. Experiment Quality Score.
3. Evidence Drilldown Per Eksperimen.
4. Evaluation Export.
5. False Positive Guard Dashboard.
6. AI Disagreement Queue.
7. Report Evidence Bundle.
8. Profile Calibration Review.

Alasan urutan:

- Coverage dan quality score dulu agar tahu data mana yang layak dievaluasi.
- Evidence drilldown sebelum export agar hasil evaluasi punya bukti.
- Export setelah rumus evaluasi stabil.
- False positive dashboard memperkuat klaim bahwa sistem punya guard.
- AI disagreement setelah evidence contract tampil jelas.
- Calibration paling akhir karena menyentuh klaim scoring dan berisiko bias bila dataset belum lengkap.

## Struktur File Yang Kemungkinan Ditambah

Minimal dan bertahap:

```text
app/Services/DatasetCoverageService.php
app/Services/ExperimentQualityService.php
app/Services/ExperimentEvidenceService.php
app/Services/EvaluationExportService.php
app/Services/FalsePositiveGuardService.php
app/Services/AiDisagreementService.php
app/Services/ProfileCalibrationService.php
```

Controller/view yang mungkin dipakai:

```text
app/Http/Controllers/EvaluationController.php
resources/views/evaluation/index.blade.php
resources/views/evaluation/coverage.blade.php
resources/views/evaluation/false-positive.blade.php
resources/views/evaluation/ai-disagreement.blade.php
resources/views/experiments/show.blade.php
```

Command yang mungkin ditambah:

```bash
php artisan lab:evaluate-profiles --format=json
php artisan lab:evaluate-profiles --format=csv
php artisan lab:evaluate-profiles --format=md
php artisan lab:evidence-bundle {experiment_id}
```

Catatan Ponytail:

- Jangan buat semua service sekaligus kalau belum dipakai.
- Mulai dari service yang langsung memberi output.
- Reuse `EvaluationMetricsService` untuk semua metrik evaluasi.
- Jangan membuat tabel baru sebelum data yang ada terbukti tidak cukup.

## Definisi Done Per Phase

### Phase 1 Done

- Semua profile punya status coverage.
- Eksperimen punya quality status.
- Data kosong/missing terlihat jelas.
- Ada test untuk coverage dan quality status.

### Phase 2 Done

- Detail eksperimen bisa menjelaskan keputusan dari bukti.
- Gate passed/failed/missing tampil.
- Evidence bundle bisa dibuat dari command.
- Tidak ada data sensitif ikut export.

### Phase 3 Done

- Evaluation export tersedia minimal JSON dan CSV.
- Angka UI sama dengan angka export.
- False positive guard dashboard menampilkan baseline/comparison sample.

### Phase 4 Done

- AI disagreement tampil dalam satu queue.
- Evidence contract violation terlihat.
- Reviewer bisa memberi catatan atau status review.

### Phase 5 Done

- Threshold simulation bisa dijalankan tanpa mengubah data asli.
- Dampak metrik sebelum/sesudah terlihat.
- Eksperimen yang berubah status terdaftar.

## Risiko Yang Harus Dijaga

1. Jangan membuat fitur yang terlihat seperti attack automation.
2. Jangan membuat AI override scoring gate.
3. Jangan menampilkan API key, token, password, atau credential di export.
4. Jangan menyebut metrik akurat tanpa jumlah sample dan coverage.
5. Jangan menyamakan Wireshark/Snort evidence dengan bukti absolut tool tertentu jika pattern overlap.
6. Jangan mengubah threshold produksi dari fitur calibration tanpa audit log dan persetujuan eksplisit.

## Rekomendasi Scope Sprint Pertama

Sprint pertama cukup ambil tiga hal:

1. Dataset Coverage Checklist.
2. Experiment Quality Score.
3. Evaluation Export CSV/JSON.

Kenapa:

- Langsung menutup kelemahan klaim metrik.
- Tidak menyentuh scoring core.
- Tidak berisiko menambah attack capability.
- Bisa dites dengan data lokal yang sudah ada.
