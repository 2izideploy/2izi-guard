<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$css=(string)file_get_contents($root.'/public/assets/guard.css');
$js=(string)file_get_contents($root.'/public/assets/guard.js');
foreach(['en','ru','zh-CN','ja','it'] as $lang){
    $d=json_decode((string)file_get_contents($root.'/locales/'.$lang.'.json'),true,512,JSON_THROW_ON_ERROR);
    foreach(['interactive_title','interactive_button','interactive_button_compact','interactive_button_touch','interactive_button_touch_compact','interactive_button_keyboard_compact'] as $key){
        if(!isset($d[$key])||trim((string)$d[$key])===''){fwrite(STDERR,"{$lang}: missing {$key}\n");exit(1);}
    }
    if(count(preg_split('//u',(string)$d['interactive_button_compact'],-1,PREG_SPLIT_NO_EMPTY))>14){fwrite(STDERR,"{$lang}: compact label too long\n");exit(1);}
}
foreach(['inset-inline-start','inset-inline-end','padding-inline','container-type:inline-size'] as $needle){
    if(!str_contains($css,$needle)){fwrite(STDERR,"Missing RTL/container CSS: {$needle}\n");exit(1);}
}
if(!str_contains($js,"data.guardLocale") && !str_contains($js,'dataset.guardLocale')){fwrite(STDERR,"Missing explicit locale configuration\n");exit(1);}
if(!str_contains($js,'registerLocale')){fwrite(STDERR,"Missing runtime locale extension API\n");exit(1);}
echo "Responsive multilingual contract OK\n";
