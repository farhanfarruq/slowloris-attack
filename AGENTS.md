# AGENTS.md

## Project Scope

Laravel 11 dashboard for defensive Slow HTTP / Slowloris lab analysis. The app manages experiment metadata, paired Wireshark/acquisition summaries, Snort validation summaries, extracted features, rule-based scoring, AI validation, visualization, audit logs, and reports.

Work only on defensive analysis, validation, reporting, and lab documentation. Do not add attack automation, evasion, public-target scanning, bypass logic, or tooling that improves real-world abuse. Existing lab scripts are for isolated local/VM environments only.

## Stack

- PHP 8.2, Laravel 11, Blade, Tailwind, Alpine, Vite.
- Data model centers on `Experiment`, `AcquisitionFile`, `ValidationFile`, `SnortAlert`, `ExtractedFeature`, `AiResult`, and `FinalReport`.
- Core analysis lives in `app/Services/ScoringService.php`, `app/Services/AnalysisService.php`, and `app/Services/AiValidationService.php`.
- Routes live in `routes/web.php`; admin-only actions use `role:admin` middleware.

## Analysis Rules

- `ScoringService` is source of truth for Slowloris scoring and evidence gating.
- `AnalysisService` builds persisted extracted features and AI payloads. AI payloads must include enough context for auditability, especially `evidence_contract`.
- `AiValidationService` is a validator only. It must not override evidence gates or classify `Slowloris Detected` when `evidence_contract.slowloris_detected_allowed` is false.
- AI confidence is confidence in the chosen label. It is not attack probability unless classification is exactly `Slowloris Detected`.
- False positives to protect against: HTTP burst, iPerf/bandwidth test, portscan, normal-baseline, TCP-dominant non-HTTP traffic, and missing Snort evidence.

## AI Prompt Contract

All AI providers, including OpenAI-compatible, Groq/Llama, Gemini, and Ollama, must receive the same decision boundary:

- Return JSON only.
- Allowed classification values: `Normal`, `Suspicious`, `Slowloris Detected`, `Inconclusive`.
- Use only values present in payload. Do not invent IPs, timestamps, rule names, packet counts, or connection counts.
- `Slowloris Detected` requires the evidence contract to allow it and supporting indicators must cite payload fields.
- Ambiguous or incomplete payloads become `Inconclusive`, not a confident attack label.

## Test Commands

- Unit/feature tests: `php artisan test`
- Focus scoring: `php artisan test --filter=ScoringServiceTest`
- Focus AI validation contract: `php artisan test --filter=AiValidationServiceTest`
- Frontend build: `npm run build`

## Change Discipline

- Keep scoring changes covered by tests for both true positive and false positive scenarios.
- Keep prompt changes provider-neutral; do not tune only for one model family.
- Keep API keys server-side. Never expose decrypted provider keys in Blade, JSON responses, logs, reports, or exports.
- Prefer explicit evidence fields over prose-only conclusions.
- Reports and UI should show gate reasons and missing evidence so professional reviewers can audit the decision.
