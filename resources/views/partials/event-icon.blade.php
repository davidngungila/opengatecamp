@php
$paths = [
    'camp'         => '<path d="M12 3l7 9H5l7-9z"/><path d="M5 12h14v7H5z"/><path d="M8 19v-3h8v3"/>',
    'conference'   => '<path d="M4 15a8 8 0 0 1 16 0"/><path d="M12 7a4 4 0 0 1 4 4"/><path d="M8 19l-3 2v-4"/><path d="M16 19l3 2v-4"/><circle cx="12" cy="6" r="2"/>',
    'mission_trip' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><circle cx="12" cy="11" r="2.5"/>',
    'training'     => '<path d="M12 3l10 5-10 5L2 8l10-5z"/><path d="M6 10.5V15c0 1.5 2.7 3 6 3s6-1.5 6-3v-4.5"/><path d="M22 8v5"/>',
    'worship'      => '<path d="M12 3l9 9h-3v6h-4v-4h-4v4H6v-6H3l9-9z"/>',
    'other'        => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/>',
];
$d = $paths[$type ?? 'other'] ?? $paths['other'];
$w = $size ?? 22;
@endphp
<svg width="{{ $w }}" height="{{ $w }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">{!! $d !!}</svg>