<?php

return [
    'max_size_mb' => (int) env('UPLOAD_MAX_SIZE_MB', 1024),
    'pcap_auto_parse_max_mb' => (int) env('UPLOAD_PCAP_AUTO_PARSE_MAX_MB', 100),
    'allowed_acquisition' => array_filter(array_map('trim',
        explode(',', env('UPLOAD_ALLOWED_ACQUISITION', 'pcap,pcapng,csv,json'))
    )),
    'allowed_validation' => array_filter(array_map('trim',
        explode(',', env('UPLOAD_ALLOWED_VALIDATION', 'json,log,csv,txt'))
    )),
];
