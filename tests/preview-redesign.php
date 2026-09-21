<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$preview=(string)file_get_contents($root.'/admin/preview.php');
$js=(string)file_get_contents($root.'/public/assets/guard.js');
$css=(string)file_get_contents($root.'/public/assets/guard.css');
foreach(['Размер','Управление','Состояние','guard-preview-mount','ga-preview-canvas','Изоляция интерфейса'] as $needle){
    if(!str_contains($preview,$needle)){fwrite(STDERR,"Preview redesign contract missing: {$needle}\n");exit(1);}
}
if(substr_count($preview,'IZIGuard.preview(')!==2){
    fwrite(STDERR,"Preview should render one visible state plus one isolated diagnostic fixture only\n");exit(1);
}
if(!str_contains($js,"brandline.className='izi-guard-interactive-brandline'")){fwrite(STDERR,"Challenge brand footer missing\n");exit(1);}
if(!str_contains($css,'.izi-guard-interactive-brandline{display:flex')){fwrite(STDERR,"Challenge brand footer styling missing\n");exit(1);}
if(str_contains($css,'.izi-guard-interactive-head{display:grid;grid-template-columns:24px minmax(0,1fr) auto')){fwrite(STDERR,"Brand still competes with title in header\n");exit(1);}
echo "Preview redesign contract OK\n";
