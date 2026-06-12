@props(['name' => 'home', 'class' => 'w-5 h-5'])

@php
    $icons = [
        'home'     => 'M3 12 12 3l9 9M5 10v10h14V10',
        'upload'   => 'M12 5v14M5 12l7-7 7 7',
        'shield'   => 'M12 2 4 6v6c0 5 3.5 9.5 8 10 4.5-.5 8-5 8-10V6l-8-4z',
        'flask'    => 'M9 3h6M10 3v6L4 19a2 2 0 0 0 1.7 3h12.6a2 2 0 0 0 1.7-3L14 9V3',
        'chart'    => 'M3 3v18h18M7 14l3-3 4 4 5-7',
        'cpu'      => 'M5 5h14v14H5zM9 9h6v6H9z M3 9h2 M3 15h2 M19 9h2 M19 15h2 M9 3v2 M15 3v2 M9 19v2 M15 19v2',
        'database' => 'M4 6c0-1.7 3.6-3 8-3s8 1.3 8 3-3.6 3-8 3-8-1.3-8-3zM4 6v12c0 1.7 3.6 3 8 3s8-1.3 8-3V6 M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3',
        'file'     => 'M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9l-6-6zM14 3v6h6 M9 13h6 M9 17h6',
        'lab'      => 'M5 4h14M9 4v6L4 19a2 2 0 0 0 1.7 3h12.6a2 2 0 0 0 1.7-3L15 10V4',
        'book'     => 'M4 4h12a4 4 0 0 1 4 4v12H8a4 4 0 0 1-4-4V4z M4 4v12a4 4 0 0 0 4 4h12',
        'key'      => 'M15 7a4 4 0 1 1-3.46 6L8 17l-2 2-3-3 7-7 1.46-1.46A4 4 0 0 1 15 7z',
        'plus'     => 'M12 5v14M5 12h14',
        'trash'    => 'M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M6 6v14a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V6',
        'eye'      => 'M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12zM12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6z',
        'download' => 'M12 3v12M5 12l7 7 7-7M5 21h14',
        'check'    => 'M5 12l4 4L19 6',
        'cross'    => 'M6 6l12 12M18 6 6 18',
        'play'     => 'M8 5v14l11-7-11-7z',
    ];
    $d = $icons[$name] ?? $icons['home'];
@endphp

<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24"
     fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
    <path d="{{ $d }}"/>
</svg>
