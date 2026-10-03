<?php
function icon($name) {
    $paths=[
        'home'=>'<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-6v-7h-4v7H4a1 1 0 0 1-1-1z"/>',
        'calendar'=>'<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4m10-4v4M3 10h18"/>',
        'clock'=>'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l4 2"/>',
        'users'=>'<circle cx="9" cy="7" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 4a3 3 0 0 1 0 6m2 4a5 5 0 0 1 3 5v2"/>',
        'medical'=>'<rect x="3" y="7" width="18" height="14" rx="2"/><path d="M8 7V4h8v3m-4 4v6m-3-3h6"/>',
        'ticket'=>'<path d="M6 3h12v19l-6-4-6 4z"/>',
        'settings'=>'<path d="m9 3-1 3-3 1v4l-2 1 2 3v3l3 1 1 2h6l1-2 3-1v-3l2-3-2-1V7l-3-1-1-3z"/><circle cx="12" cy="12" r="3"/>',
        'logout'=>'<path d="M10 4H4v16h6m4-13 5 5-5 5m-6-5h11"/>',
        'search'=>'<circle cx="10" cy="10" r="6"/><path d="m15 15 6 6"/>',
        'grid'=>'<path d="M4 4h2v2H4zm7 0h2v2h-2zm7 0h2v2h-2zM4 11h2v2H4zm7 0h2v2h-2zm7 0h2v2h-2zM4 18h2v2H4zm7 0h2v2h-2zm7 0h2v2h-2z"/>',
    ];
    return '<svg class="icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($paths[$name]??$paths['medical']).'</svg>';
}
