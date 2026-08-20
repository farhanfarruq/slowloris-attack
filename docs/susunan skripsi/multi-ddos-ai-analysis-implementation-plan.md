# Multi-Tool DDoS AI Analysis Implementation Plan

Dokumen ini menerjemahkan revisi dosen menjadi rencana implementasi Laravel 11 untuk dashboard analisis defensif DDoS berbasis profil tool: LOIC, HOIC, Slowloris, Hping3, Torshammer, dan Xerxes. Scope tetap analisis lab: data akuisisi Wireshark/Snort dari target VM Ubuntu Server saat testing, lalu narasi laporan akhir dapat memetakan objek penelitian ke ESP32/IoT drone. Jangan menambahkan otomasi serangan, bypass, evasion, public-target scanning, atau fitur yang meningkatkan penyalahgunaan.

## 1. Koreksi Konsep

Arahan dosen: jangan menjadikan Slow POST, SYN Flood, UDP Flood, dan sejenisnya sebagai identitas utama per orang. Itu adalah jenis/pola serangan. Identitas penelitian yang dipakai adalah tool/profil DDoS seperti LOIC, HOIC, Slowloris, Hping3, Torshammer, dan Xerxes.

Konsekuensi desain:

- UI utama memakai istilah `Tool Profile`, bukan hanya `Attack Category`.
- `tool_profile` menjadi pemisah utama eksperimen, skoring, prompt AI, grafik, laporan, dan ownership data.
- `attack_pattern` tetap disimpan sebagai metadata teknis turunan. Contoh: Hping3 dapat dipakai untuk pola SYN/ICMP/UDP flood sesuai dataset lab, tetapi halaman tetap berlabel Hping3.
- Prompt AI wajib menganalisis tool profile yang dipilih dan tidak mencampur indikator antar tool.
- Scoring logic tetap berbasis bukti forensik, bukan klaim nama tool saja.

## 2. Pembagian Profil Untuk 4 Orang

Karena anggota yang disebut ada 4 orang, gunakan 4 profil utama dari daftar dosen. Dua profil lain tetap dibuat sebagai profil tersedia agar aplikasi siap jika pembagian berubah.

| Orang | Profil tool | Pola bukti yang dianalisis | Fokus forensik | Provider AI yang disarankan |
| --- | --- | --- | --- | --- |
| Farhan | Slowloris | slow HTTP connection exhaustion | koneksi lama, header/request tidak lengkap, bandwidth rendah, durasi koneksi tinggi, alert slow HTTP | OpenAI-compatible/Groq |
| Gading | LOIC | HTTP/TCP/UDP flood sesuai dataset lab | request/packet rate tinggi, throughput naik, koneksi agresif dan singkat, pola flood lebih kasar | Gemini |
| Maudi | HOIC | HTTP flood dengan request burst/profil booster | HTTP request burst, variasi header/user-agent jika tersedia, dominasi traffic HTTP, spike request rate | DeepSeek/OpenAI-compatible |
| Adila | Hping3 | TCP SYN/ICMP/UDP flood sesuai skenario lab | flag/protocol dominan, packet rate tinggi, rasio SYN/ACK tidak normal, alert DoS transport-layer | Ollama/local Llama atau provider lain |

Profil tambahan yang tetap disediakan:

| Profil tool | Status | Pola bukti yang dianalisis |
| --- | --- | --- |
| Torshammer | tersedia/rotasi | slow HTTP POST/body exhaustion, koneksi HTTP lama, transfer rate rendah |
| Xerxes | tersedia/rotasi | HTTP/TCP flood, koneksi paralel, request/packet spike, resource exhaustion |

Catatan penting:

- GoldenEye tidak dipakai sebagai profil utama karena sudah digunakan sebagai contoh judul dari dosen.
- Jika dosen meminta semua nama tool muncul di aplikasi, tampilkan keenam profil. Untuk pendadaran per orang, filter akun masing-masing hanya menampilkan satu profil utama.
- Jangan membuat fitur menjalankan tool. Aplikasi hanya menganalisis dataset hasil akuisisi dan validasi lab.

## 3. Target Arsitektur

### 3.1 Data model baru

Tambahkan migration:

- `experiments.tool_profile`: string/enum. Nilai awal: `slowloris`, `loic`, `hoic`, `hping3`, `torshammer`, `xerxes`.
- `experiments.attack_pattern`: nullable string. Contoh: `slow_http`, `http_flood`, `tcp_syn_flood`, `udp_flood`, `icmp_flood`.
- `experiments.analysis_profile_key`: string. Default sama dengan `tool_profile`.
- `experiments.target_platform`: string. Contoh: `vm_ubuntu_server`, `esp32_iot_web_server`, `esp32_drone_simulation`.
- `ai_provider_settings.tool_profile`: nullable string agar provider bisa dikunci per profil tool.
- `ai_results.tool_profile`: string.
- `ai_results.attack_pattern`: nullable string.
- `ai_results.analysis_profile_key`: string.
- `ai_results.logic_classification`: string.
- `ai_results.logic_score`: float.
- `ai_results.logic_gate_reasons`: json.
- `ai_results.ai_chart_data`: json.
- `ai_results.comparison_summary`: json.

Opsional:

- Buat tabel `tool_profiles` jika ingin profil bisa diedit dari UI admin.
- Jika belum perlu UI admin, simpan dulu di `config/tool_profiles.php`.

### 3.2 Service layer

Komponen baru/diubah:

- `ToolProfileService`: sumber daftar LOIC, HOIC, Slowloris, Hping3, Torshammer, Xerxes; memuat label, pola bukti, prompt rules, score weights, gate rules.
- `ScoringService`: tetap source of truth, tetapi menerima `tool_profile` dan memilih evaluator sesuai profil.
- `AnalysisService`: membangun fitur, hasil logic scoring, evidence contract, payload AI, dan comparison context berdasarkan `tool_profile`.
- `AiValidationService`: secara UI dan konsep diganti menjadi AI Analysis. Class lama boleh dipertahankan sementara untuk kompatibilitas route/test.
- `AiPromptBuilder`: menyusun prompt provider-neutral berdasarkan `tool_profile`.
- `AnalysisComparisonService`: membandingkan hasil logic program dengan hasil AI Analysis.

### 3.3 Strategy evaluator

Buat evaluator per profil:

- `SlowlorisScoringProfile`
- `LoicScoringProfile`
- `HoicScoringProfile`
- `Hping3ScoringProfile`
- `TorshammerScoringProfile`
- `XerxesScoringProfile`

Setiap profile wajib punya:

- required acquisition fields
- required validation/Snort fields
- positive evidence gates
- false-positive guards
- score weights
- detected label
- chart metrics
- recommended AI prompt rules

## 4. Evidence Contract Per Tool

### 4.1 Format umum payload AI

```json
{
  "tool_profile": "slowloris",
  "attack_pattern": "slow_http",
  "target_platform": "vm_ubuntu_server",
  "logic_analysis": {
    "classification": "Suspicious",
    "score": 72.5,
    "radar_scores": {},
    "gate_reasons": []
  },
  "evidence_contract": {
    "detected_allowed": false,
    "required_evidence": [],
    "present_evidence": [],
    "missing_evidence": [],
    "false_positive_guards": []
  },
  "features": {},
  "acquisition_summary": {},
  "validation_summary": {},
  "snort_alerts": []
}
```

Aturan:

- AI hanya analyst pembanding, bukan penentu tunggal.
- AI tidak boleh menaikkan label menjadi detected jika `evidence_contract.detected_allowed` false.
- Confidence adalah confidence terhadap label yang dipilih, bukan probabilitas serangan kecuali classification benar-benar detected.
- Semua indikator harus mengutip field payload.
- Nama tool saja tidak cukup untuk detected. Detected wajib didukung bukti acquisition + validation/gate.

### 4.2 Slowloris

Detected label: `Slowloris Detected`.

Indikator utama:

- koneksi HTTP banyak dan long-lived
- throughput rendah dibanding jumlah koneksi
- durasi koneksi tinggi
- header/request tidak selesai atau pola slow HTTP
- alert Snort slow HTTP/Slowloris bila tersedia

False positive:

- HTTP burst pendek
- traffic normal baseline
- iPerf/throughput tinggi
- portscan
- TCP dominan non-HTTP
- missing Snort/validation evidence

### 4.3 LOIC

Detected label: `LOIC Flood Detected`.

Indikator utama:

- packet/request rate tinggi dalam waktu pendek
- throughput naik tajam
- banyak koneksi singkat atau request agresif ke target
- protokol dominan sesuai skenario dataset: HTTP, TCP, atau UDP
- alert Snort DoS/flood yang relevan

False positive:

- load test internal yang diberi label normal
- traffic backup/download besar
- iPerf bandwidth test
- HTTP burst normal
- tidak ada spike rate atau validasi alert

### 4.4 HOIC

Detected label: `HOIC Flood Detected`.

Indikator utama:

- HTTP request burst sangat tinggi
- dominasi HTTP ke target
- variasi header/user-agent/referrer jika tersedia
- banyak request paralel dari sumber lab
- alert HTTP flood/DoS bila tersedia

False positive:

- web benchmark legal
- crawling internal
- normal high-traffic web access
- request burst pendek tanpa dampak koneksi/alert
- tidak ada bukti HTTP-level

### 4.5 Hping3

Detected label: `Hping3 Flood Detected`.

Indikator utama:

- pola flag/protocol spesifik sesuai skenario: SYN, ICMP, UDP, atau TCP custom
- packet rate tinggi
- SYN/ACK tidak seimbang jika skenario SYN
- ICMP echo rate tinggi jika skenario ICMP
- UDP datagram rate tinggi jika skenario UDP
- alert DoS/flood transport-layer bila tersedia

False positive:

- portscan ringan
- ping monitoring normal
- retransmission karena packet loss
- service discovery internal
- traffic transport-layer tinggi tanpa pola flood

### 4.6 Torshammer

Detected label: `Torshammer Detected`.

Indikator utama:

- koneksi HTTP lama
- transfer body/header lambat
- request cenderung tidak selesai
- bandwidth rendah tetapi koneksi aktif tinggi
- alert slow HTTP/slow POST bila tersedia

False positive:

- upload lambat normal
- jaringan klien lambat
- HTTP POST burst pendek
- throughput tinggi yang wajar
- tidak ada bukti body/header slow behavior

### 4.7 Xerxes

Detected label: `Xerxes Flood Detected`.

Indikator utama:

- koneksi paralel/request spike ke target
- HTTP/TCP flood sesuai dataset lab
- packet/request rate tinggi
- resource exhaustion signal dari validasi bila tersedia
- alert DoS/flood relevan

False positive:

- load test legal
- web crawler internal
- normal concurrency tinggi
- dataset tanpa validasi Snort
- spike singkat tanpa bukti lanjutan

## 5. Perubahan Halaman

### 5.1 Experiments

Tambahkan field:

- Tool Profile: LOIC, HOIC, Slowloris, Hping3, Torshammer, Xerxes
- Attack Pattern: HTTP flood, slow HTTP, SYN flood, UDP flood, ICMP flood, mixed
- Analysis Profile
- Target Platform: VM Ubuntu Server, ESP32 IoT Web Server, ESP32 Drone Simulation
- Dataset owner / research member

Default testing tetap VM Ubuntu Server. Laporan dapat menjelaskan VM sebagai substitusi lab sebelum deployment ESP32/drone.

### 5.2 Analysis

Halaman analisis menjadi tool-aware:

- Filter tool profile.
- Tampilkan skor logic program berdasarkan profile tool.
- Tampilkan gate reason dan missing evidence.
- Tampilkan status sumber data: acquisition, validation/Snort, AI analysis.
- Tombol proses ulang hanya menjalankan profile tool eksperimen.

### 5.3 AI Analysis

Rename UI dari `AI Validation` menjadi `AI Analysis`.

Isi halaman:

- Provider/model per tool profile.
- Prompt preview ringkas tanpa API key.
- Hasil AI analysis.
- Grafik AI analysis.
- Supporting indicators dan missing evidence.
- Perbandingan classification AI vs logic program.

Route lama `/ai` boleh tetap dipakai, tetapi label, title, sidebar, controller copy, export filename, dan test wording harus berubah ke AI Analysis.

### 5.4 Visualization

Tambahkan filter:

- tool profile
- attack pattern
- experiment
- source: acquisition, validation, AI analysis, comparison

Grafik minimal:

- Acquisition graph: packet timeline, protocol distribution, connection duration, throughput.
- Validation graph: Snort severity, alert timeline, alert type distribution.
- AI analysis graph: confidence by provider, AI indicator weight, missing evidence count, classification distribution.
- Comparison graph: logic score vs AI confidence, classification agreement, tool profile radar comparison.

Grafik AI harus berbeda dari grafik acquisition/validation. AI graph menampilkan interpretasi AI, bukan raw packet ulang.

### 5.5 Comparison Page

Buat halaman khusus: `Analysis Comparison`.

Isi:

- Tool profile
- Attack pattern
- Logic classification
- Logic score
- AI classification
- AI confidence
- Agreement status: `match`, `partial`, `conflict`, `blocked_by_evidence_gate`
- Gate reason
- Recommendation
- Chart logic vs AI

## 6. Prompt AI Per Tool

### 6.1 System prompt template

```text
You are a defensive network forensic analyst for controlled lab DDoS research.
Return JSON only. Do not include markdown.

Analyze only the DDoS tool profile declared in payload.tool_profile.
Do not reuse indicators from another tool profile.
Use payload.attack_pattern only as technical context, not as the primary research identity.
Use only values present in the payload. Do not invent IPs, timestamps, rule names, packet counts, connection counts, ports, target hardware, or model results.

Allowed classification values:
- Normal
- Suspicious
- {TOOL_DETECTED_LABEL}
- Inconclusive

The detected label is allowed only when payload.evidence_contract.detected_allowed is true.
If detected_allowed is false, classification must be Normal, Suspicious, or Inconclusive.
If required evidence is missing or ambiguous, classify as Inconclusive unless the payload clearly supports Normal.

Confidence score means confidence in your selected classification.
It is not attack probability unless classification equals {TOOL_DETECTED_LABEL}.

Output schema:
{
  "tool_profile": "string",
  "attack_pattern": "string|null",
  "classification": "Normal|Suspicious|{TOOL_DETECTED_LABEL}|Inconclusive",
  "confidence_score": number,
  "reason": "string",
  "supporting_indicators": [
    {
      "field": "payload field path",
      "value": "observed value",
      "interpretation": "defensive forensic interpretation"
    }
  ],
  "missing_evidence": ["string"],
  "false_positive_considerations": ["string"],
  "logic_comparison": {
    "logic_classification": "string",
    "logic_score": number,
    "agreement": "match|partial|conflict|blocked_by_evidence_gate",
    "explanation": "string"
  },
  "chart_data": {
    "indicator_scores": [
      {"label": "string", "score": number}
    ],
    "evidence_counts": {
      "present": number,
      "missing": number,
      "blocking": number
    },
    "confidence": number
  },
  "recommendation": "string"
}
```

### 6.2 User prompt template

```text
Analyze this controlled lab dataset for the declared DDoS tool profile only.

Research context:
- This is defensive DDoS forensic analysis.
- Current lab target is VM Ubuntu Server.
- Final thesis narrative may map the object to ESP32 IoT/drone web server, but do not invent ESP32-specific measurements unless present in payload.
- Compare programmatic scoring with AI analysis.
- The tool profile is the research identity. The attack pattern is only technical evidence context.

Tool profile rules:
{TOOL_PROFILE_RULES}

False-positive guards:
{FALSE_POSITIVE_GUARDS}

Payload JSON:
{PAYLOAD_JSON}
```

### 6.3 Detected labels

- `slowloris`: `Slowloris Detected`
- `loic`: `LOIC Flood Detected`
- `hoic`: `HOIC Flood Detected`
- `hping3`: `Hping3 Flood Detected`
- `torshammer`: `Torshammer Detected`
- `xerxes`: `Xerxes Flood Detected`

## 7. Phase Plan

### Phase 0 - Baseline Audit

Tujuan: pastikan perubahan tidak merusak fitur Slowloris lama.

Tasks:

- Jalankan `php artisan test --filter=ScoringServiceTest`.
- Jalankan `php artisan test --filter=AiValidationServiceTest`.
- Catat field grafik saat ini.
- Catat semua label UI `AI Validation` untuk diganti menjadi `AI Analysis`.

### Phase 1 - Tool Profile Foundation

Tujuan: profil tool menjadi data resmi aplikasi.

Tasks:

- Buat `config/tool_profiles.php` berisi LOIC, HOIC, Slowloris, Hping3, Torshammer, Xerxes.
- Tambahkan migration field `tool_profile`, `attack_pattern`, `analysis_profile_key`, `target_platform`.
- Tambahkan `ai_provider_settings.tool_profile`.
- Update model fillable/casts.
- Update form create/edit experiment.
- Tambahkan filter tool profile di experiment index.

Tests:

- feature test create experiment dengan tool profile
- unit test `ToolProfileService`

### Phase 2 - Profile-Based Scoring

Tujuan: skoring logic tidak lagi Slowloris-only.

Tasks:

- Refactor `ScoringService` agar profile-driven.
- Pertahankan Slowloris behavior existing.
- Implement evaluator untuk LOIC, HOIC, Hping3, Torshammer, Xerxes.
- Simpan logic score dan gate reasons ke hasil extracted feature/raw_features.
- Tambahkan false-positive tests per tool.

Tests:

- Slowloris backward compatibility
- LOIC blocks detected jika hanya traffic bandwidth normal/iPerf
- HOIC blocks detected jika HTTP burst pendek tanpa bukti flood
- Hping3 blocks detected jika hanya portscan/ping normal
- Torshammer blocks detected jika tidak ada slow POST/body evidence
- Xerxes blocks detected jika hanya concurrency normal tanpa validasi

### Phase 3 - AI Analysis Refactor

Tujuan: AI analysis memakai prompt dan provider sesuai tool.

Tasks:

- Rename UI text dari `AI Validation` ke `AI Analysis`.
- Tambahkan provider mapping per `tool_profile`.
- Buat `AiPromptBuilder`.
- Update payload dengan `tool_profile`, `attack_pattern`, `logic_analysis`, dan `evidence_contract`.
- Normalize response dengan detected label per tool.
- Simpan `ai_chart_data` dan `comparison_summary`.
- Pastikan API key tetap server-side.

Tests:

- prompt memuat tool profile yang benar
- AI tidak boleh detected saat gate false
- detected label tool lain diturunkan menjadi Inconclusive
- provider tool profile dipilih benar

### Phase 4 - Visualization and Comparison

Tujuan: grafik lengkap dari acquisition, validation, AI analysis, dan comparison.

Tasks:

- Tambahkan filter tool profile/source di visualization.
- Tambahkan AI confidence chart.
- Tambahkan indicator score chart dari `ai_chart_data`.
- Tambahkan evidence present/missing chart.
- Buat halaman comparison logic vs AI.
- Tambahkan export JSON comparison.

Tests:

- visualization tidak error saat AI result kosong
- comparison menampilkan status conflict/match
- chart data valid JSON

### Phase 5 - Research Polish

Tujuan: aplikasi siap dipakai demo dan laporan.

Tasks:

- Update methodology page: VM Ubuntu Server sebagai target testing, ESP32/drone sebagai objek penelitian akhir.
- Tambahkan label `controlled lab only`.
- Tambahkan report section per tool profile.
- Tambahkan audit log untuk perubahan provider/profile.
- Tambahkan dokumentasi runbook upload dataset per profile tool.

Tests:

- `php artisan test`
- `npm run build`

## 8. Acceptance Criteria

Fitur selesai jika:

- Eksperimen punya `tool_profile`.
- Tool profile tersedia: LOIC, HOIC, Slowloris, Hping3, Torshammer, Xerxes.
- Setiap tool punya scoring profile berbeda.
- Setiap tool punya prompt AI berbeda.
- Provider AI bisa dipisah per tool.
- AI Analysis menggantikan AI Validation di UI.
- Grafik menampilkan acquisition, validation, AI analysis, dan comparison.
- Halaman comparison logic vs AI tersedia.
- AI tidak bisa melabeli detected saat evidence gate melarang.
- Slowloris lama tetap lulus test.
- Tidak ada API key di Blade, response JSON publik, log, report, atau export.
- Tidak ada fitur ofensif baru.

## 9. Master Prompt Implementasi Untuk Codex

```text
Kamu bekerja di repo Laravel 11:
/home/farhan/Documents/VsCode Project/slowloris-attack

Ikuti AGENTS.md. Scope defensif saja. Jangan menambahkan attack automation, evasion, public-target scanning, bypass logic, atau fitur yang meningkatkan penyalahgunaan. Existing lab scripts hanya untuk lingkungan lokal/VM.

Tujuan:
Ubah aplikasi dari Slowloris-only menjadi dashboard analisis defensif multi-tool DDoS dengan pemisahan tool profile, attack pattern, scoring profile, AI prompt, provider AI, grafik AI, dan halaman comparison logic vs AI.

Koreksi konsep dosen:
- Jangan memakai Slow POST, SYN Flood, UDP Flood, dan sejenisnya sebagai identitas utama per orang.
- Identitas utama adalah tool/profil: LOIC, HOIC, Slowloris, Hping3, Torshammer, Xerxes.
- Jenis serangan seperti HTTP flood, SYN flood, UDP flood, ICMP flood hanya menjadi attack_pattern/metadata teknis.

Konteks riset:
- Testing saat ini tetap memakai VM Ubuntu Server sebagai target lab.
- Narasi laporan akhir dapat menyebut objek ESP32/IoT drone, tetapi aplikasi tidak boleh mengarang data ESP32 jika payload tidak memuatnya.
- GoldenEye tidak dipakai sebagai profil utama karena sudah digunakan sebagai contoh judul.
- Empat profil utama untuk 4 orang:
  1. Farhan: slowloris
  2. Gading: loic
  3. Maudi: hoic
  4. Adila: hping3
- Profil tambahan tetap tersedia:
  5. torshammer
  6. xerxes

Kondisi repo sekarang:
- Core scoring: app/Services/ScoringService.php
- Core analysis payload: app/Services/AnalysisService.php
- AI service: app/Services/AiValidationService.php
- Existing AI page routes: /ai
- Visualization page: resources/views/visualization/index.blade.php
- Tests penting:
  php artisan test --filter=ScoringServiceTest
  php artisan test --filter=AiValidationServiceTest
  php artisan test --filter=AnalysisServiceTest

Implementasi bertahap:

Phase 0:
- Audit label AI Validation, route, view, controller, tests.
- Jalankan baseline tests jika dependency tersedia.

Phase 1:
- Tambahkan tool profile foundation.
- Buat config/tool_profiles.php dengan LOIC, HOIC, Slowloris, Hping3, Torshammer, Xerxes.
- Tambahkan migration untuk experiments.tool_profile, experiments.attack_pattern, experiments.analysis_profile_key, experiments.target_platform.
- Tambahkan provider mapping per tool_profile melalui ai_provider_settings.tool_profile atau config fallback.
- Update model casts/fillable.
- Update form create/edit experiment dan filter index.

Phase 2:
- Refactor ScoringService agar profile-driven.
- Pertahankan Slowloris behavior existing.
- Tambahkan evaluator logic untuk loic, hoic, hping3, torshammer, xerxes.
- Setiap evaluator wajib punya positive evidence gates dan false-positive guards.
- Logic scoring adalah source of truth.

Phase 3:
- Rename konsep UI AI Validation menjadi AI Analysis.
- Buat prompt builder provider-neutral.
- Prompt harus tool-specific dan tidak boleh menganalisis tool lain.
- Payload AI harus memuat tool_profile, attack_pattern, logic_analysis, evidence_contract, features, acquisition_summary, validation_summary, snort_alerts.
- AI tidak boleh memberi detected label jika evidence_contract.detected_allowed false.
- Simpan logic_classification, logic_score, logic_gate_reasons, ai_chart_data, comparison_summary di ai_results.
- API key tetap server-side.

Phase 4:
- Tambahkan grafik AI di visualization.
- Grafik AI harus berbeda dari grafik acquisition/validation.
- Tambahkan halaman comparison logic vs AI.
- Comparison minimal berisi tool profile, attack pattern, logic classification, logic score, AI classification, AI confidence, agreement status, gate reasons, recommendation, chart data.

Phase 5:
- Update methodology/report agar menjelaskan VM target untuk testing dan ESP32/drone sebagai objek penelitian akhir.
- Tambahkan tests untuk profile selection, prompt isolation, evidence gate, comparison, dan chart data.
- Jalankan:
  php artisan test
  npm run build

Acceptance:
- Skoring dan prompt tidak tercampur antar tool profile.
- Slowloris existing tetap lulus.
- Tersedia profil loic, hoic, slowloris, hping3, torshammer, xerxes.
- AI Analysis menggantikan AI Validation di UI.
- Ada grafik acquisition, validation, AI analysis, comparison.
- Ada halaman comparison logic vs AI.
- Tidak ada API key bocor.
- Tidak ada fitur ofensif baru.
```

## 10. Urutan Kerja Paling Aman

1. Tambah `tool_profile` dan `attack_pattern` tanpa mengubah behavior Slowloris.
2. Pindahkan prompt Slowloris existing ke prompt builder.
3. Tambah profil LOIC, HOIC, Hping3, Torshammer, Xerxes satu per satu.
4. Tambah kolom hasil AI/comparison.
5. Ubah UI label dan grafik.
6. Tambah comparison page.
7. Polish report/methodology.

Jangan mulai dari UI besar sebelum data contract stabil.
