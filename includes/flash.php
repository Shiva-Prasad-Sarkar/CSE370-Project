<?php
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function render_flash(): void {
    if (!isset($_SESSION['flash'])) return;
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $colors = [
        'success' => 'bg-green-50 border-green-500 text-green-800',
        'error'   => 'bg-red-50 border-red-500 text-red-800',
        'info'    => 'bg-blue-50 border-blue-500 text-blue-800',
    ];
    $c = $colors[$flash['type']] ?? $colors['info'];
    echo "<div class='border-l-4 p-4 mb-6 rounded-r-lg {$c} text-sm font-medium'>"
       . htmlspecialchars($flash['message'])
       . "</div>";
}
