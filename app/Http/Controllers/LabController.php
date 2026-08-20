<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;

class LabController extends Controller
{
    public function index()
    {
        $files = File::glob(base_path('metadata/experiment/*.json'));
        usort($files, fn (string $a, string $b) => filemtime($b) <=> filemtime($a));
        $latestMetadata = isset($files[0])
            ? json_decode(File::get($files[0]), true)
            : null;

        return view('lab.index', [
            'target' => [
                'host' => config('esp32.host'),
                'port' => config('esp32.port'),
                'capture_interface' => config('esp32.capture_interface'),
                'serial_port' => config('esp32.serial_port'),
                'ssid' => config('esp32.ssid'),
                'device' => config('esp32.device'),
            ],
            'latestMetadata' => is_array($latestMetadata) ? $latestMetadata : null,
        ]);
    }
}
