<?php

return [
    'max_size_mb' => (int) env('UPLOAD_MAX_SIZE_MB', 256),
    'allowed_acquisition' => array_filter(array_map('trim',
        explode(',', env('UPLOAD_ALLOWED_ACQUISITION', 'pcap,pcapng,csv,json'))
    )),
    'allowed_validation' => array_filter(array_map('trim',
        explode(',', env('UPLOAD_ALLOWED_VALIDATION', 'json,log,csv,txt'))
    )),
];
