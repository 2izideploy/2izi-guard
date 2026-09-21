<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$js = (string) file_get_contents($root . '/public/assets/guard.js');
$preview = (string) file_get_contents($root . '/admin/preview.php');

$checks = [
    'interactive preview completion event' => 'izi-guard-preview-complete',
    'preview pointer down' => "hold.addEventListener('pointerdown'",
    'preview pointer cancel' => "hold.addEventListener('pointercancel'",
    'preview keyboard support' => "hold.addEventListener('keydown'",
    'preview requestAnimationFrame' => 'requestAnimationFrame(tick)',
    'preview auto reset' => 'resetAfterMs',
    'preview 0 percent initial state' => 'renderProgress(0)',
    '0.4.10 client version' => "version:'0.4.10'",
];
foreach ($checks as $name => $needle) {
    if (!str_contains($js, $needle)) {
        fwrite(STDERR, "Missing preview interaction contract: {$name}\n");
        exit(1);
    }
}
if (str_contains($preview, '48%') || str_contains($js, '>48%<')) {
    fwrite(STDERR, "Static 48% preview regression detected\n");
    exit(1);
}
if (!str_contains($preview, 'IZIGuard.preview')) {
    fwrite(STDERR, "Admin preview is not wired to IZIGuard.preview()\n");
    exit(1);
}

echo "Interactive preview contract OK\n";
