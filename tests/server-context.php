<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/Core/ServerContext.php';
use TwoIzi\Guard\Core\ServerContext;
function ok(bool $v,string $m):void{if(!$v){fwrite(STDERR,"FAIL: $m\n");exit(1);}echo "OK: $m\n";}
$c=ServerContext::fromArray(['account_id'=>123,'signals'=>['risk'=>true,'fake'=>'true'],'assurances'=>['mfa'=>true,'webauthn'=>'true','bad name'=>true]]);
ok($c->accountId()==='123','account id normalized server-side');
ok($c->signals()===['risk'=>true],'only boolean application signals accepted');
ok($c->hasAssurance('mfa'),'boolean server assurance accepted');
ok(!$c->hasAssurance('webauthn'),'string cannot forge assurance');
ok(!$c->hasAssurance('bad name'),'invalid assurance name rejected');
echo "Server context tests passed.\n";
