<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/Support/Encoding.php';
require dirname(__DIR__).'/src/Config/Config.php';
require dirname(__DIR__).'/src/Security/Crypto.php';
require dirname(__DIR__).'/src/Security/ChallengeIntegrity.php';
require dirname(__DIR__).'/src/Security/TokenIntegrity.php';

use TwoIzi\Guard\Config\Config;
use TwoIzi\Guard\Security\ChallengeIntegrity;
use TwoIzi\Guard\Security\TokenIntegrity;
use TwoIzi\Guard\Security\Crypto;
use TwoIzi\Guard\Support\Encoding;

function k(string $seed): string { return 'base64url:'.Encoding::base64UrlEncode(hash('sha256',$seed,true)); }
function cfg(string $active='h2', string $oldStatus='verify_only'): Config {
    return new Config([
        'origin'=>'https://example.test','database'=>[],'actions'=>['contact'=>[]],
        'keys'=>[
            'hmac'=>['active'=>$active,'keys'=>[
                'h1'=>['value'=>k('old-hmac'),'status'=>$active==='h1'?'active':$oldStatus],
                'h2'=>['value'=>k('new-hmac'),'status'=>$active==='h2'?'active':'verify_only'],
            ]],
            'privacy'=>['active'=>'p1','keys'=>['p1'=>['value'=>k('privacy'),'status'=>'active']]],
            'rate_limit'=>['active'=>'r1','keys'=>['r1'=>['value'=>k('rate-limit'),'status'=>'active']]],
        ],
    ]);
}
function ok(bool $v,string $m):void{if(!$v){fwrite(STDERR,"FAIL: {$m}\n");exit(1);}echo "OK: {$m}\n";}

$row=[
    'public_id'=>'ch_public','session_hash'=>hash('sha256','session',true),'action'=>'contact','challenge_type'=>'pow_interactive',
    'salt'=>'salt','difficulty'=>17,'honeypot_name'=>'g_hp_x','honeypot_required'=>1,'policy_version'=>4,
    'predicted_decision'=>'interactive','interactive_required'=>1,'interactive_min_ms'=>1200,'risk_score'=>73,
    'issued_at_ms'=>1700000000000,'expires_at_ms'=>1700000090000,
];
$oldIntegrity=new ChallengeIntegrity(cfg('h1'));
$old=$oldIntegrity->sign($row); $signed=$row+['integrity_key_id'=>$old['kid'],'integrity_mac'=>$old['mac']];
ok($oldIntegrity->verify($signed),'valid challenge integrity verifies');
$newIntegrity=new ChallengeIntegrity(cfg('h2','verify_only'));
ok($newIntegrity->verify($signed),'previous HMAC key verifies during rotation grace');
$retiredIntegrity=new ChallengeIntegrity(cfg('h2','retired'));
ok(!$retiredIntegrity->verify($signed),'retired HMAC key no longer verifies');
foreach(['action'=>'login','challenge_type'=>'pow','salt'=>'attacker','difficulty'=>1,'honeypot_required'=>0,'policy_version'=>1,'predicted_decision'=>'pass','interactive_required'=>0,'interactive_min_ms'=>0] as $field=>$value){$tampered=$signed;$tampered[$field]=$value;ok(!$newIntegrity->verify($tampered),"tampering {$field} is rejected");}
$forged=$row+['integrity_key_id'=>'h2','integrity_mac'=>random_bytes(32)];ok(!$newIntegrity->verify($forged),'random forged MAC is rejected');
$state=$newIntegrity->signState('ch_public','issued',0);$stateRow=$signed+['state_key_id'=>$state['kid'],'state_mac'=>$state['mac'],'status'=>'issued','attempt_count'=>0];
ok($newIntegrity->verifyState($stateRow),'challenge state MAC verifies');$stateRow['attempt_count']=1;ok(!$newIntegrity->verifyState($stateRow),'challenge attempt rollback/tamper is detected');
$ti=new TokenIntegrity(cfg('h2','verify_only'));$tokenHash=Crypto::sha256('secret-token');$tokenRow=['token_hash'=>$tokenHash,'session_hash'=>hash('sha256','session',true),'challenge_id'=>44,'action'=>'contact','origin_hash'=>hash('sha256','origin',true),'origin_key_id'=>'p1','risk_level'=>70,'issued_at_ms'=>1700000000000,'expires_at_ms'=>1700000120000];$ts=$ti->sign($tokenRow);$tstate=$ti->signState($tokenHash,'issued');$tokenRow+=['integrity_key_id'=>$ts['kid'],'integrity_mac'=>$ts['mac'],'state_key_id'=>$tstate['kid'],'state_mac'=>$tstate['mac'],'status'=>'issued'];ok($ti->verify($tokenRow)&&$ti->verifyState($tokenRow),'token static and state MAC verify');$tokenRow['action']='login';ok(!$ti->verify($tokenRow),'token action tampering is rejected');$tokenRow['action']='contact';$tokenRow['status']='consumed';ok(!$ti->verifyState($tokenRow),'token status tampering is rejected without server state MAC');
$tokens=[];for($i=0;$i<1000;$i++){$t=Crypto::randomToken();if(!str_starts_with($t,'gt_')){fwrite(STDERR,'FAIL token prefix\n');exit(1);}$tokens[$t]=1;}
ok(count($tokens)===1000,'1000 opaque 256-bit tokens are unique in smoke test');
echo "Adversarial known-source smoke test passed.\n";
