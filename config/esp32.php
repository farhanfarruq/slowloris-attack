<?php

$host = (string) env('TARGET_HOST', '192.168.4.1');

return [
    'type' => env('TARGET_TYPE', 'esp32'),
    'host' => $host,
    'port' => (int) env('TARGET_PORT', 80),
    'scheme' => env('TARGET_SCHEME', 'http'),
    'health_path' => env('ESP32_HEALTH_PATH', '/health'),
    'metrics_path' => env('ESP32_METRICS_PATH', '/metrics'),
    'capture_interface' => env('CAPTURE_INTERFACE', 'wlp8s0'),
    'serial_port' => env('ESP32_SERIAL_PORT', '/dev/ttyUSB0'),
    'serial_baud' => (int) env('ESP32_SERIAL_BAUD', 115200),
    'ssid' => env('ESP32_SSID', 'ESP32-LAB'),
    'device' => [
        'board' => env('ESP32_BOARD', 'generic 30-pin'),
        'chip' => env('ESP32_CHIP', 'ESP32-D0WD-V3'),
        'revision' => env('ESP32_REVISION', 'v3.1'),
        'flash' => env('ESP32_FLASH', '4 MB'),
        'usb_bridge' => env('ESP32_USB_BRIDGE', 'Silicon Labs CP2102'),
        'arduino_board_profile' => env('ESP32_ARDUINO_BOARD_PROFILE', 'ESP32 Dev Module'),
        'arduino_core' => env('ESP32_ARDUINO_CORE', '3.3.11'),
        'mac' => env('ESP32_MAC', '20:50:0d:2b:39:f0'),
    ],
    'capture_filter' => env('PCAP_CAPTURE_FILTER', "host {$host} and tcp port 80"),
    'allowed_hosts' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('ESP32_ALLOWED_HOSTS', '192.168.4.1')),
    ))),
    'http_timeout_seconds' => (int) env('ESP32_HTTP_TIMEOUT_SECONDS', 3),
    'dumpcap_binary' => env('DUMPCAP_BINARY', '/usr/bin/dumpcap'),
    'tshark_binary' => env('TSHARK_BINARY', '/usr/bin/tshark'),
    'snort_binary' => env('SNORT_BINARY', '/usr/local/bin/snort'),
    'snort_config' => env('SNORT_CONFIG', '/usr/local/etc/snort/snort.lua'),
];
