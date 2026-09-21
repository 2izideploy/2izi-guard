<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$js = (string) file_get_contents($root . '/public/assets/guard.js');
$css = (string) file_get_contents($root . '/public/assets/guard.css');

$checks = [
    'pointer capture' => 'setPointerCapture',
    'pointer cancel' => "'pointercancel'",
    'pointer move boundary cancel' => "'pointermove'",
    'lost pointer capture' => "'lostpointercapture'",
    'context menu suppression' => "'contextmenu'",
    'visibility cancellation' => "'visibilitychange'",
    'touch copy' => 'interactive_instruction_touch',
    'keyboard copy' => 'interactive_instruction_keyboard',
    'requestAnimationFrame progress' => 'requestAnimationFrame',
    '0.4.10 client version' => "version:'0.4.10'",
];
foreach ($checks as $name => $needle) {
    if (!str_contains($js, $needle)) {
        fwrite(STDERR, "Missing mobile interaction contract: {$name}\n");
        exit(1);
    }
}
foreach (['-webkit-touch-callout:none','touch-action:none','-webkit-tap-highlight-color:transparent','izi-guard-hold-value'] as $needle) {
    if (!str_contains($css, $needle)) {
        fwrite(STDERR, "Missing mobile CSS contract: {$needle}\n");
        exit(1);
    }
}

echo "Mobile interaction contract OK\n";
