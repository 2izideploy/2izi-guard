<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$js=(string)file_get_contents($root.'/public/assets/guard.js');
$css=(string)file_get_contents($root.'/public/assets/guard.css');
$preview=(string)file_get_contents($root.'/admin/preview.php');
foreach(['Shadow DOM','Проверить изоляцию','ga-hostile-fixture'] as $needle){
    if(!str_contains($preview,$needle)){fwrite(STDERR,"Missing isolation test marker: {$needle}\n");exit(1);}
}
foreach(['all:initial',"set('max-width','420px')","set('contain','layout style paint')"] as $needle){
    $haystack=$css.$js;
    if(!str_contains($haystack,$needle)){fwrite(STDERR,"Missing hostile CSS defense: {$needle}\n");exit(1);}
}
foreach(['.ga-hostile-fixture button{font-size:32px!important','.ga-hostile-fixture izi-guard-surface{width:999px!important'] as $needle){
    if(!str_contains($preview,$needle)){fwrite(STDERR,"Hostile CSS fixture missing: {$needle}\n");exit(1);}
}
if(!str_contains($js,"normalizeIsolation(options.isolation||defaultIsolation)")){fwrite(STDERR,"Preview isolation option missing\n");exit(1);}
echo "Hostile CSS isolation contract OK\n";
