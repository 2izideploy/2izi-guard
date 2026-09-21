<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$js=(string)file_get_contents($root.'/public/assets/guard.js');
$css=(string)file_get_contents($root.'/public/assets/guard.css');
$preview=(string)file_get_contents($root.'/admin/preview.php');

$jsNeedles=[
    "fake.style.setProperty('width','100%','important')",
    "fake.style.setProperty('min-width','0','important')",
    "fake.style.setProperty('min-inline-size','0','important')",
    "fake.style.setProperty('flex','1 1 100%','important')",
    "fake.style.setProperty('justify-self','stretch','important')",
    "set('min-width','0')",
    "set('min-inline-size','0')",
    "set('flex','0 1 420px')",
];
foreach($jsNeedles as $needle){
    if(!str_contains($js,$needle)){
        fwrite(STDERR,"Responsive layout JS hardening missing: {$needle}\n");
        exit(1);
    }
}

if(!str_contains($preview,'.ga-preview-mount{width:100%;display:grid;place-items:center;min-width:0;min-inline-size:0}')){
    fwrite(STDERR,"Preview mount must use grid instead of shrink-to-fit flex\n");
    exit(1);
}
foreach([
    '.ga-preview-mount>form{display:block!important',
    'width:100%!important',
    'min-width:0!important',
    'min-inline-size:0!important',
    'justify-self:stretch!important',
] as $needle){
    if(!str_contains($preview,$needle)){
        fwrite(STDERR,"Preview form sizing contract missing: {$needle}\n");
        exit(1);
    }
}
if(!str_contains($css,':host{box-sizing:border-box;max-width:100%;min-width:0;min-inline-size:0;')){
    fwrite(STDERR,"Shadow host flex/grid min-size reset missing\n");
    exit(1);
}

echo "Responsive layout hotfix contract OK\n";
