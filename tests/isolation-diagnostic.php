<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$preview=(string)file_get_contents($root.'/admin/preview.php');
$checks=[
    'waits for shadow CSS' => 'waitForShadowCss',
    'uses link load event' => "addEventListener('load'",
    'reports css timeout' => 'css_load_timeout',
    'first-family parsing' => "split(',')[0]",
    'rejects exact serif only' => "firstFont!=='serif'",
    'detailed width diagnostic' => 'ширина компонента изменена внешним CSS',
];
foreach($checks as $name=>$needle){
    if(!str_contains($preview,$needle)){fwrite(STDERR,"Isolation diagnostic contract missing: {$name}\n");exit(1);}
}
if(str_contains($preview,"!/serif$/i.test")){
    fwrite(STDERR,"Regression: sans-serif would be misclassified as serif\n");exit(1);
}
echo "Isolation diagnostic regression OK\n";
