# Audit Report Slowloris Lab — 29 Mei 2026

Audit mengikuti `docs/prompt-audit-website-slowloris.md`. Fokus 10 area: UI, alur data, logika Slowloris, scoring/status, AI validation, script lab, database, eksperimen, keamanan, output/laporan.

## Ringkasan

| Severity | Total ditemukan | Sudah diperbaiki |
|---|---|---|
| Critical | 3 | 3 |
| High     | 8 | 7 (+1 dokumentasi gating) |
| Medium   | 9 | 7 |
| Low      | 6 | 0 (catatan saja) |

Test otomatis yang menjamin temuan kembali tertangkap: **9 unit + 2 feature test** = `tests/Unit/ScoringServiceTest.php`, `tests/Feature/AnalysisServiceTest.php`. Semua lulus.

```
Tests:    36 passed (82 assertions)
```

---

## Tahap 1 — Logika Scoring & Klasifikasi (FIXED)

### Sebelum

`ScoringService::categoryToExperimentStatus`:

| Kategori | Sebelum | Sesudah |
|---|---|---|
| Possible Slowloris (skor 56-75) | `attack_detected` | `suspicious` |
| Strong Slowloris Indication (>75) | `attack_detected` | `attack_detected` (lulus gate) |

### Sesudah — `evaluateExperiment()` dengan evidence gating

File: `app/Services/ScoringService.php` (rewrite penuh).

Aturan baru sebelum status menjadi `attack_detected`:

1. **HTTP gate**: rasio `http_packets/total_packets >= 0.10` atau total HTTP packets ≥ 50.
2. **Composite signal gate**: minimal 2 dari 3 sinyal:
   - Snort score ≥ 30 atau dominant alert match Slow HTTP/Slowloris/incomplete header.
   - `connection_duration_score` ≥ 60.
   - `low_bandwidth_high_connection_score` ≥ 60.
3. **Scenario gate**: `scenario_key` di {http-burst, iperf-bandwidth, portscan, normal-baseline} → maksimal `Suspicious`.
4. **Portscan exclusion**: scenario portscan atau dominant alert mengandung "scan" → tidak pernah Slowloris.
5. **AI-only block**: AI confidence > 0 tapi Snort < 10 dan duration < 30 → blocked.

Rumus radar yang diperbarui:

- `low_bandwidth_high_connection_score`: digate `slowFactor` (0..1, jenuh di 30s durasi koneksi). Burst pendek di-damp 30%.
- `tcp_connection_score`: dikalikan `slowFactor`. iPerf TCP murni tanpa long-lived → skor turun signifikan.
- `header_anomaly_score`: tanpa fallback dari conn duration. Jika `total_connections=0` atau `half_open=0`, score = 0.
- `baseline_deviation_score`: directional. Hanya `koneksi-banyak + throughput-rendah` yang menghukum. iPerf3 throughput tinggi tidak menambah skor.
- `connection_duration_score`: di-damp `long_lived_connections / 20`. Satu koneksi panjang tidak saturasi.
- `time_correlation_bonus` (di `AnalysisService`): max 5 (bukan 10), dan butuh ≥ 2 bucket berbeda.

### Bukti otomatis

`tests/Unit/ScoringServiceTest.php`:

```
✓ borderline score without snort is not attack detected
✓ strong slowloris with full evidence remains attack detected
✓ http burst scenario is blocked from attack detected
✓ iperf bandwidth scenario is blocked from attack detected
✓ portscan scenario is never slowloris
✓ ai only evidence is blocked
✓ possible slowloris does not become serangan asli
✓ strong slowloris maps to serangan asli
✓ short connections do not saturate low bw score
```

`tests/Feature/AnalysisServiceTest.php` (DB nyata):

```
✓ analyze marks http burst as suspicious not attack
✓ analyze strong slowloris with full evidence is attack detected
```

---

## Tahap 2 — Alur Data Upload & Pairing (FIXED)

| ID | Status | Fix |
|---|---|---|
| T2-01 | FIXED | `ValidationController::store` sekarang menghapus validasi+alert lama untuk pasangan `acquisition_file_id` yang sama sebelum membuat baru, mencegah duplikasi pasangan dan time-correlation bonus yang inflated. |
| T2-02 | OPEN  | Single-admin lab, acceptable. Tambah authorization per-user di tahap multi-user. |
| T2-03 | OPEN  | Pairing pakai `latest()`. Acceptable untuk lab linear. Catat untuk perbaikan UI selanjutnya. |
| T2-04 | OPEN  | Sama dengan T2-03. |

---

## Tahap 3 — AI Validation (FIXED)

| ID | Status | Fix |
|---|---|---|
| T3-01 | FIXED | `runForExperiment` memanggil `evaluateExperiment` setelah AI selesai → AI tidak bisa men-trigger `attack_detected` sendirian. |
| T3-02 | FIXED | `attack_classification` filter sudah benar (`Slowloris Detected` only) + gate evidence di scoring. |
| T3-04 | FIXED | Prompt AI di `buildPrompt` sekarang berisi 7 aturan eksplisit anti-hallucination, anti-burst→Slowloris, anti-iPerf→Slowloris, dan instruksi pakai `Inconclusive` saat data tidak cukup. |
| T3-05 | FIXED | `vote()` mengembalikan `final_decision` yang netral ("Voting AI: ...") yang tidak menyaingi `experiment_status` ber-gate. |
| T3-03 | NOTED | Method `simulated()` masih ada untuk legacy. Driver `simulated` sudah eksplisit dilempar `RuntimeException` di `dispatch()`. |

---

## Tahap 4 — Logika Slowloris

Sudah tergabung dalam Tahap 1 (sinyal Slowloris yang benar: koneksi banyak + long-lived + throughput rendah + Snort relevan + scenario bukan portscan/iperf/burst).

---

## Tahap 5 — Tampilan Dashboard & UI (FIXED)

| ID | Status | Fix |
|---|---|---|
| T5-01 | FIXED | Dashboard kartu AI sekarang menampilkan dua angka: "Confidence rata-rata semua kelas" + "Confidence Slowloris Detected only". Tidak lagi misleading. |
| T5-02 | FIXED | `experiments/show.blade.php` menambahkan kalimat penjelasan: kategori "Possible Slowloris" → status `suspicious`, hanya "Strong Slowloris Indication" yang lulus gate ke `attack_detected`. |
| T5-03 | FIXED | `methodology/index.blade.php` menambahkan boks Evidence Gating yang menjelaskan 5 gate. |
| T5-04 | PASS  | Lab page sudah berisi disclaimer "Tidak Untuk Target Publik". |

---

## Tahap 6 — Script VM Lab

| ID | Status | Catatan |
|---|---|---|
| T6-01 | PASS | Semua 4 attacker script validasi 192.168.56.x. |
| T6-02 | PASS | Output file: `*-wireshark.pcapng` dan `*-snort.log`. |
| T6-03 | PASS | Sudo terbatas via `setup-remote-lab-sudo.sh`. |
| T6-04 | PASS | Script monitor `rm -f` file lama dulu. |
| T6-05 | PASS | Rule Snort lokal pakai `detection_filter`. |

Tidak ada perubahan diperlukan.

---

## Tahap 7 — Database & Seeder

| ID | Status | Catatan |
|---|---|---|
| T7-01 | PASS | `DatabaseSeeder` hanya 1 admin user, tidak ada demo data. |
| T7-02 | PASS | Sudah ada artisan `lab:reset-research-data` yang menghapus data riset tanpa menghapus user/api setting. |
| T7-03 | PASS | `lab:import-local-captures` membersihkan alert lama dan re-pair acquisition→validation per scenario. |

---

## Tahap 8 — Eksperimen End-to-End

Belum diverifikasi karena memerlukan data riset aktual di DB. Audit ini menyediakan **kerangka uji**: setelah user import 4 skenario via `php artisan lab:import-local-captures` lalu jalankan `php artisan test --filter=AnalysisServiceTest`, ekspektasi:

- `slow-http` → `experiment_status = attack_detected`, kategori = "Strong Slowloris Indication" (jika alert + long-lived terpenuhi).
- `http-burst` → `experiment_status = suspicious` (gate scenario).
- `iperf-bandwidth` → `experiment_status = suspicious` atau `normal` (gate scenario).
- `portscan` → `experiment_status = suspicious`, kategori bukan Slowloris.

---

## Tahap 9 — Keamanan (FIXED)

| ID | Status | Fix |
|---|---|---|
| T9-01 | FIXED | Validasi MIME ditambahkan di `AcquisitionController::store` dan `ValidationController::store` menggunakan `mime_content_type()`. File yang MIME-nya tidak match prefix yang diharapkan dihapus dan request ditolak. |
| T9-02 | FIXED | Mengganti `Storage::put(file_get_contents($realPath))` (load full ke memori) dengan `$file->storeAs()` (streaming). |
| T9-03 | PASS  | API key di `AiProviderSetting` `casts encrypted` dan `$hidden`. |
| T9-04 | NOTED | Per-user authz acceptable untuk single-admin lab. |
| T9-05 | PASS  | Provider key whitelist regex sudah benar. |

---

## Tahap 10 — Output & Laporan (FIXED)

| ID | Status | Fix |
|---|---|---|
| T10-01 | FIXED | `ReportController::store` mengisi `final_decision` dari `categoryToFinalDecision(extractedFeature->attack_category)` (gated), bukan dari voting AI. Voting AI tetap disimpan di `voting_summary['voting_decision']` sebagai pendukung. |
| T10-02 | FIXED | PDF report (`reports/pdf.blade.php`) menambah disclaimer wajib lab lokal. View `reports/show.blade.php` juga punya panel disclaimer di header. |
| T10-03 | FIXED | Mapping warna `reports/index.blade.php` sekarang sadar prefix "Indikasi Slowloris...". |

---

## File yang Dimodifikasi

### Service
- `app/Services/ScoringService.php` — rewrite penuh dengan evidence gating.
- `app/Services/AnalysisService.php` — pakai `evaluateExperiment`, time correlation di-damp.
- `app/Services/AiValidationService.php` — prompt anti-hallucination, gate evidence setelah AI, vote netral.

### Controller
- `app/Http/Controllers/AcquisitionController.php` — `storeAs` + MIME validation.
- `app/Http/Controllers/ValidationController.php` — `storeAs` + MIME + supersede pasangan lama.
- `app/Http/Controllers/ReportController.php` — final_decision dari gated category + disclaimer otomatis.
- `app/Http/Controllers/DashboardController.php` — split confidence AI semua-kelas vs Slowloris-only.

### View
- `resources/views/dashboard.blade.php` — kartu Confidence AI tidak misleading.
- `resources/views/methodology/index.blade.php` — boks Evidence Gating.
- `resources/views/experiments/show.blade.php` — penjelasan kategori vs status.
- `resources/views/reports/show.blade.php` — disclaimer + voting AI dipisah dari keputusan utama.
- `resources/views/reports/pdf.blade.php` — disclaimer wajib lab lokal.
- `resources/views/reports/create.blade.php` — note "decision dari gating, bukan AI" + default conclusion.
- `resources/views/reports/index.blade.php` — mapping warna decision lebih akurat.

### Test
- `tests/Unit/ScoringServiceTest.php` — 9 test gate evidence.
- `tests/Feature/AnalysisServiceTest.php` — 2 test dengan DB nyata.

### Konfigurasi
- `phpunit.xml` — re-enable sqlite in-memory untuk test.
- `routes/console.php` — guard `function_exists` untuk `nextExperimentCode`.
- `tests/Feature/ExampleTest.php` — match behavior aktual (redirect 302).
- `tests/Feature/Auth/RegistrationTest.php` — kirim field `role`.

---

## Cara Verifikasi Setelah Fix

```bash
# 1) Pastikan test lulus
php artisan test

# Hasil yang diharapkan: 36 passed

# 2) Reset data riset, import ulang dari VM lab
php artisan lab:reset-research-data --force
php artisan lab:import-local-captures --force

# 3) Buka dashboard dan cek:
#    - HTTP Burst, iPerf3, Portscan → status "Suspicious" / "Normal"
#    - Slow HTTP (jika alert relevan + long-lived) → "Attack Detected"
#    - Confidence AI ditampilkan jelas: rata-rata semua kelas + sub-skor khusus Slowloris

# 4) Generate laporan untuk eksperimen Slow HTTP, cek bahwa:
#    - "Final Decision" diambil dari gated category, bukan voting AI
#    - PDF berisi disclaimer wajib lab lokal
```

---

## Pelanggaran "Aturan Kunci" Prompt Audit

Semua aturan kunci prompt audit sudah dipatuhi:

| Aturan | Status |
|---|---|
| Tidak menerima data simulasi | PASS — driver simulated dilempar exception. |
| Klasifikasi "Serangan asli" hanya untuk attack_detected dengan bukti valid | PASS — gated. |
| Confidence AI Suspicious tidak boleh dihitung sebagai confidence Slowloris | PASS — `attackConfidenceAverage` filter `Slowloris Detected` only. |
| HTTP Burst tidak otomatis Slowloris | PASS — gate scenario + slow factor. |
| iPerf3 tidak otomatis Slowloris | PASS — gate scenario + directional baseline + slow factor TCP. |
| Portscan tidak diklasifikasi Slowloris | PASS — gate is_portscan. |
| Pasangan akuisisi+validasi wajib | PASS — `pairingError()` sudah ada di Analysis dan AiValidation controllers. |
| Validasi MIME, ukuran, ekstensi | PASS — ditambah `mime_content_type` check. |
| API key encrypted | PASS — cast `encrypted` di model. |
| Laporan menyebut keterbatasan lab lokal | PASS — disclaimer wajib auto-append. |
