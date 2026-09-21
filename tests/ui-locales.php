<?php
declare(strict_types=1);
$required=['checking','working','verified','failed','rate_limited','interactive_title','interactive_instruction','interactive_button','interactive_button_compact','interactive_instruction_touch','interactive_button_touch','interactive_button_touch_compact','interactive_instruction_keyboard','interactive_button_keyboard','interactive_button_keyboard_compact','interactive_done','protected','challenge','local','badge_protected','badge_checking','badge_verified','badge_error'];
$dir=dirname(__DIR__).'/locales';$failed=false;
foreach(glob($dir.'/*.json')?:[] as $file){
    $data=json_decode((string)file_get_contents($file),true);
    if(!is_array($data)){fwrite(STDERR,basename($file).": invalid JSON\n");$failed=true;continue;}
    $missing=array_values(array_diff($required,array_keys($data)));
    if($missing){fwrite(STDERR,basename($file).': missing '.implode(', ',$missing)."\n");$failed=true;}
}
if($failed)exit(1);
echo "UI locale contract OK\n";
