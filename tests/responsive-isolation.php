<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$js=(string)file_get_contents($root.'/public/assets/guard.js');
$css=(string)file_get_contents($root.'/public/assets/guard.css');
$preview=(string)file_get_contents($root.'/admin/preview.php');
foreach([
    "dataset.guardIsolation || 'shadow'",
    "attachShadow({mode:'open'})",
    "link.href=cssUrl",
    "set('max-width','420px')",
    "createSurface(uiSlot(form),'challenge',form)",
    "customElements.define('izi-guard-surface'"
] as $needle){
    if(!str_contains($js,$needle)){fwrite(STDERR,"Missing responsive isolation JS contract: {$needle}\n");exit(1);}
}
foreach([
    '.izi-guard-surface-root{all:initial',
    'container-type:inline-size',
    'max-width:420px',
    '@container (max-width:340px)',
    '@container (max-width:270px)'
] as $needle){
    if(!str_contains($css,$needle)){fwrite(STDERR,"Missing responsive isolation CSS contract: {$needle}\n");exit(1);}
}
foreach(["'desktop'=>960","'tablet'=>720","'phone'=>390","'small'=>320","['pointer','touch','keyboard']","['protected','checking','verified','challenge']"] as $needle){
    if(!str_contains($preview,$needle)){fwrite(STDERR,"Missing preview width/input/state contract: {$needle}\n");exit(1);}
}
if(str_contains($preview,'ga-preview-grid')){fwrite(STDERR,"Old four-state preview grid returned\n");exit(1);}
if(str_contains($preview,'device=touch')){fwrite(STDERR,"Old coupled device/touch preview contract returned\n");exit(1);}
echo "Responsive isolation contract OK\n";
