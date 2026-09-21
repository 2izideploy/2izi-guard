<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$js=(string)file_get_contents($root.'/public/assets/guard.js');
$css=(string)file_get_contents($root.'/public/assets/guard.css');
$ru=json_decode((string)file_get_contents($root.'/locales/ru.json'),true,512,JSON_THROW_ON_ERROR);
$en=json_decode((string)file_get_contents($root.'/locales/en.json'),true,512,JSON_THROW_ON_ERROR);

$expectedRu=['protected'=>'Защищено','checking'=>'Проверяем…','verified'=>'Проверено','badge_protected'=>'Защищено','badge_checking'=>'Проверяем…','badge_verified'=>'Проверено'];
foreach($expectedRu as $k=>$v){if(($ru[$k]??null)!==$v){fwrite(STDERR,"RU {$k} is not minimal\n");exit(1);}}
$expectedEn=['protected'=>'Protected','checking'=>'Checking…','verified'=>'Verified'];
foreach($expectedEn as $k=>$v){if(($en[$k]??null)!==$v){fwrite(STDERR,"EN {$k} is not minimal\n");exit(1);}}
foreach(['2IZI Guard проверяет','Проверено 2IZI Guard','Protected by 2IZI Guard','Verified by 2IZI Guard'] as $bad){
    if(str_contains($js,$bad)){fwrite(STDERR,"Duplicate brand copy regression: {$bad}\n");exit(1);}
}
foreach(['izi-guard-badge-brand','checkSvg()','brand.hidden = state !== \'protected\'','btn.hidden=true','box.dataset.phase=\'checking\''] as $needle){
    if(!str_contains($js,$needle)){fwrite(STDERR,"Missing minimal UI behavior: {$needle}\n");exit(1);}
}
if(!str_contains($css,'.izi-guard-badge[data-state="verified"] .izi-guard-badge-mark{color:var(--izi-guard-success);background:transparent}')){
    fwrite(STDERR,"Verified mark must be a plain green glyph\n");exit(1);
}
if(!str_contains($css,'min-height:48px')){fwrite(STDERR,"Touch target regression\n");exit(1);}
echo "Minimal visitor UI contract OK\n";
