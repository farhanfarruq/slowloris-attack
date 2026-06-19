<?php

namespace App\Services;

class AiPromptBuilder
{
    public function __construct(private ?ToolProfileService $profiles = null)
    {
        $this->profiles ??= new ToolProfileService();
    }

    public function build(array $payload): array
    {
        $toolProfile = $this->profiles->normalize($payload['tool_profile'] ?? null);
        $profile = $this->profiles->get($toolProfile);
        $detectedLabel = $this->profiles->detectedLabel($toolProfile);

        $schema = [
            'tool_profile' => 'string; harus sama dengan payload.tool_profile',
            'attack_pattern' => 'string|null; salin dari payload.attack_pattern jika tersedia',
            'classification' => 'salah satu dari: Normal, Suspicious, ' . $detectedLabel . ', Inconclusive',
            'confidence_score' => 'angka 0-100; keyakinan pada klasifikasi yang dipilih',
            'reason' => 'kesimpulan singkat berbasis bukti, berbahasa Indonesia, hanya memakai nilai dari payload',
            'supporting_indicators' => [
                ['field' => 'path field payload', 'value' => 'nilai yang diamati', 'interpretation' => 'interpretasi forensik defensif berbahasa Indonesia'],
            ],
            'missing_evidence' => ['teks bahasa Indonesia yang menyebut bukti hilang atau lemah'],
            'false_positive_considerations' => ['teks bahasa Indonesia yang menyebut penjaga positif palsu yang relevan'],
            'logic_comparison' => [
                'logic_classification' => 'string',
                'logic_score' => 'angka',
                'agreement' => 'match|partial|conflict|blocked_by_evidence_gate',
                'explanation' => 'teks bahasa Indonesia',
            ],
            'chart_data' => [
                'indicator_scores' => [['label' => 'teks bahasa Indonesia', 'score' => 'angka']],
                'evidence_counts' => ['present' => 'angka', 'missing' => 'angka', 'blocking' => 'angka'],
                'confidence' => 'angka',
            ],
            'recommendation' => 'langkah validasi defensif atau monitoring berikutnya dalam bahasa Indonesia',
        ];

        $system = "Anda adalah analis forensik jaringan defensif untuk riset DDoS di lab terkontrol.\n"
            . "Kembalikan JSON saja. Jangan sertakan markdown.\n"
            . "Semua teks naratif pada keluaran wajib berbahasa Indonesia: reason, interpretation, missing_evidence, false_positive_considerations, logic_comparison.explanation, chart_data.indicator_scores.label, dan recommendation.\n"
            . "Pengecualian hanya untuk nama field JSON, path field payload, nilai enum kontrak, label klasifikasi, nama tool, nama protokol, nama rule, IP, timestamp, dan angka yang memang berasal dari payload.\n"
            . "Analisis hanya tool profile DDoS yang dinyatakan di payload.tool_profile: {$toolProfile}.\n"
            . "Tool profile adalah identitas penelitian. Attack pattern hanya konteks bukti teknis.\n"
            . "Jangan memakai ulang indikator dari tool profile lain.\n"
            . "Gunakan hanya nilai yang ada di payload. Jangan mengarang IP, timestamp, nama rule, jumlah paket, jumlah koneksi, port, perangkat target, atau hasil model.\n"
            . "Nilai classification yang diizinkan: Normal, Suspicious, {$detectedLabel}, Inconclusive.\n"
            . "Label detected hanya boleh dipakai ketika payload.evidence_contract.detected_allowed bernilai true.\n"
            . "Jika detected_allowed bernilai false, classification harus Normal, Suspicious, atau Inconclusive.\n"
            . "confidence_score adalah keyakinan terhadap classification yang dipilih. Nilai itu bukan probabilitas serangan kecuali classification sama dengan {$detectedLabel}.\n"
            . "Gunakan payload.evidence_contract.required_for_detected untuk tool profile aktif ini. Jangan mewajibkan bukti long-lived/low-bandwidth khusus Slowloris untuk LOIC, HOIC, atau Xerxes HTTP flood kecuali payload secara eksplisit menyatakan slow_http.\n"
            . "Aturan khusus profile: " . implode(' ', $profile['prompt_rules'] ?? []) . "\n"
            . "Penjaga positif palsu: " . implode(', ', $profile['false_positive_guards'] ?? []) . "\n"
            . "Skema keluaran: " . json_encode($schema, JSON_UNESCAPED_UNICODE);

        $user = "Analisis payload lab defensif berikut dan bandingkan analisis AI dengan skoring logic program. Jawab dengan JSON valid saja, dan seluruh teks naratif di dalam JSON harus berbahasa Indonesia.\n\n"
            . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return ['system' => $system, 'user' => $user];
    }
}
