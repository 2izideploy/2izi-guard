<?php
declare(strict_types=1);
$root = dirname(__DIR__);
$css = (string) file_get_contents($root . '/public/assets/guard.css');
$js = (string) file_get_contents($root . '/public/assets/guard.js');
$ru = json_decode((string) file_get_contents($root . '/locales/ru.json'), true, 512, JSON_THROW_ON_ERROR);
$checks = [
    'container query layout' => '@container (max-width:340px)',
    'micro density layout' => '@container (max-width:270px)',
    'logical progress origin' => 'inset-inline-start:0',
    'logical percent edge' => 'inset-inline-end:12px',
    'compact label class' => '.izi-guard-hold-label-compact',
    'compact card padding' => 'padding:10px',
    'mobile-safe hold height' => 'min-height:48px',
];
foreach ($checks as $name => $needle) {
    if (!str_contains($css, $needle)) {
        fwrite(STDERR, "Missing responsive UI contract: {$name}\n"); exit(1);
    }
}
foreach ([
    '0.4.10 client version' => "version:'0.4.10'",
    'custom locale registration' => 'registerLocale',
    'challenge badge suppression' => "if(state!=='challenge')",
    'compact copy support' => 'buttonCompact',
] as $name=>$needle) {
    if (!str_contains($js, $needle)) {
        fwrite(STDERR, "Missing 0.4.10 UI JS contract: {$name}\n"); exit(1);
    }
}
if (($ru['interactive_title'] ?? '') !== 'Проверка безопасности') {
    fwrite(STDERR, "Russian challenge title is not neutral/compact\n"); exit(1);
}
if (($ru['interactive_button'] ?? '') !== 'Удерживайте') {
    fwrite(STDERR, "Russian desktop label is not compact\n"); exit(1);
}
if (($ru['interactive_button_touch_compact'] ?? '') !== 'Удерживайте') {
    fwrite(STDERR, "Russian micro touch label missing\n"); exit(1);
}
if (str_contains($css, '.izi-guard-hold-label{') && str_contains(preg_replace('/.*?(\.izi-guard-hold-label\{[^}]*\}).*/s','$1',$css), 'text-overflow:ellipsis')) {
    fwrite(STDERR, "Hold action must not rely on ellipsis\n"); exit(1);
}
echo "Responsive multilingual UI contract OK\n";
