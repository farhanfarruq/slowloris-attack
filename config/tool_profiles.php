<?php

return [
    'default' => 'slowloris',

    'profiles' => [
        'slowloris' => [
            'owner' => 'Farhan',
            'label' => 'Slowloris',
            'detected_label' => 'Slowloris Detected',
            'attack_patterns' => ['slow_http'],
            'default_attack_pattern' => 'slow_http',
            'score_weights' => [
                'connection_duration_score' => 0.20,
                'header_anomaly_score' => 0.20,
                'low_bandwidth_high_connection_score' => 0.15,
                'snort_alert_score' => 0.20,
                'tcp_connection_score' => 0.10,
                'baseline_deviation_score' => 0.10,
                'ai_confidence_score' => 0.05,
            ],
            'prompt_rules' => [
                'Fokus pada request HTTP yang tidak lengkap atau koneksi HTTP yang berlangsung lama.',
                'Wajib ada koneksi HTTP long-lived, bandwidth rendah dengan banyak koneksi, atau bukti Snort Slow HTTP yang relevan.',
            ],
            'false_positive_guards' => ['http-burst', 'iperf-bandwidth', 'portscan', 'normal-baseline'],
            'chart_metrics' => ['duration', 'header_anomaly', 'low_bandwidth', 'snort'],
        ],

        'loic' => [
            'owner' => 'Gading',
            'label' => 'LOIC',
            'detected_label' => 'LOIC Detected',
            'attack_patterns' => ['http_flood', 'tcp_flood', 'udp_flood'],
            'default_attack_pattern' => 'http_flood',
            'score_weights' => [
                'packet_volume_score' => 0.20,
                'connection_volume_score' => 0.20,
                'throughput_pressure_score' => 0.15,
                'http_volume_score' => 0.15,
                'transport_flood_score' => 0.10,
                'snort_alert_score' => 0.15,
                'ai_confidence_score' => 0.05,
            ],
            'prompt_rules' => [
                'Analisis perilaku flood berupa volume paket atau request yang tinggi.',
                'Jangan menganggap indikator slow HTTP sebagai bukti LOIC.',
            ],
            'false_positive_guards' => ['normal-baseline', 'iperf-bandwidth', 'portscan'],
            'chart_metrics' => ['packet_volume', 'connection_volume', 'throughput', 'snort'],
        ],

        'hoic' => [
            'owner' => 'Maudi',
            'label' => 'HOIC',
            'detected_label' => 'HOIC Detected',
            'attack_patterns' => ['http_flood', 'mixed'],
            'default_attack_pattern' => 'http_flood',
            'score_weights' => [
                'http_volume_score' => 0.25,
                'connection_volume_score' => 0.20,
                'packet_volume_score' => 0.15,
                'throughput_pressure_score' => 0.15,
                'snort_alert_score' => 0.20,
                'ai_confidence_score' => 0.05,
            ],
            'prompt_rules' => [
                'Analisis perilaku HTTP flood dan request web berulang dengan laju tinggi.',
                'Jangan mengklasifikasikan flood hanya pada lapisan transport sebagai HOIC tanpa bukti HTTP.',
            ],
            'false_positive_guards' => ['normal-baseline', 'iperf-bandwidth', 'portscan'],
            'chart_metrics' => ['http_volume', 'connection_volume', 'snort', 'missing_evidence'],
        ],

        'hping3' => [
            'owner' => 'Adila',
            'label' => 'Hping3',
            'detected_label' => 'Hping3 Detected',
            'attack_patterns' => ['tcp_syn_flood', 'udp_flood', 'icmp_flood'],
            'default_attack_pattern' => 'tcp_syn_flood',
            'score_weights' => [
                'transport_flood_score' => 0.25,
                'packet_volume_score' => 0.20,
                'connection_volume_score' => 0.15,
                'snort_alert_score' => 0.25,
                'baseline_deviation_score' => 0.10,
                'ai_confidence_score' => 0.05,
            ],
            'prompt_rules' => [
                'Analisis indikator flood lapisan transport untuk pola lab TCP SYN, UDP, atau ICMP.',
                'Jangan memakai ulang bukti Slowloris atau HTTP flood sebagai bukti utama Hping3.',
            ],
            'false_positive_guards' => ['normal-baseline', 'http-burst', 'iperf-bandwidth', 'portscan'],
            'chart_metrics' => ['transport_flood', 'packet_volume', 'snort', 'gate'],
        ],

        'torshammer' => [
            'owner' => 'Additional',
            'label' => 'Torshammer',
            'detected_label' => 'Torshammer Detected',
            'attack_patterns' => ['slow_http'],
            'default_attack_pattern' => 'slow_http',
            'score_weights' => [
                'connection_duration_score' => 0.25,
                'low_bandwidth_high_connection_score' => 0.20,
                'header_anomaly_score' => 0.15,
                'snort_alert_score' => 0.20,
                'http_volume_score' => 0.10,
                'ai_confidence_score' => 0.10,
            ],
            'prompt_rules' => [
                'Analisis perilaku kehabisan koneksi akibat slow HTTP.',
                'Wajib ada bukti perilaku request lambat atau tidak lengkap sebelum memakai klasifikasi detected.',
            ],
            'false_positive_guards' => ['normal-baseline', 'http-burst', 'iperf-bandwidth', 'portscan'],
            'chart_metrics' => ['duration', 'low_bandwidth', 'header_anomaly', 'snort'],
        ],

        'xerxes' => [
            'owner' => 'Additional',
            'label' => 'Xerxes',
            'detected_label' => 'Xerxes Detected',
            'attack_patterns' => ['http_flood', 'tcp_flood'],
            'default_attack_pattern' => 'http_flood',
            'score_weights' => [
                'connection_volume_score' => 0.25,
                'packet_volume_score' => 0.20,
                'http_volume_score' => 0.15,
                'transport_flood_score' => 0.15,
                'snort_alert_score' => 0.20,
                'ai_confidence_score' => 0.05,
            ],
            'prompt_rules' => [
                'Analisis tekanan koneksi atau request HTTP dengan laju tinggi.',
                'Jangan mengklasifikasikan perilaku slow-header saja sebagai Xerxes tanpa indikator flood.',
            ],
            'false_positive_guards' => ['normal-baseline', 'iperf-bandwidth', 'portscan'],
            'chart_metrics' => ['connection_volume', 'packet_volume', 'http_volume', 'snort'],
        ],
    ],
];
