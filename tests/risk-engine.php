<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/RateLimit/RateLimitResult.php';
require dirname(__DIR__) . '/src/Risk/ReasonCode.php';
require dirname(__DIR__) . '/src/Risk/RiskAssessment.php';
require dirname(__DIR__) . '/src/Core/ServerContext.php';
require dirname(__DIR__) . '/src/Risk/RiskEngine.php';
use TwoIzi\Guard\Core\ServerContext; use TwoIzi\Guard\RateLimit\RateLimitResult; use TwoIzi\Guard\Risk\RiskEngine;
function ok(bool $v,string $m):void{if(!$v)throw new RuntimeException('FAIL: '.$m);echo "OK: $m\n";}
$engine=new RiskEngine();
$policy=['thresholds'=>['pow'=>30,'interactive'=>70,'deny'=>92],'application_signals'=>['recent_failed_logins'=>22,'known_good'=>-10]];
$a=$engine->assess(['js'=>true,'page_age_ms'=>3000,'pointer'=>true,'keyboard'=>false,'touch'=>false],['age_seconds'=>30,'successful_actions'=>0,'failed_actions'=>0,'failed_challenges'=>0],['network'=>new RateLimitResult(true,.05)],new ServerContext(null,[]),$policy);
ok($a->score() < 20,'normal browser remains low risk'); ok($engine->decision($a->score(),$policy)==='pass','low risk predicts pass');
$b=$engine->assess(['js'=>false,'page_age_ms'=>0,'pointer'=>false,'keyboard'=>false,'touch'=>false],['age_seconds'=>1,'successful_actions'=>0,'failed_actions'=>3,'failed_challenges'=>3],['network'=>new RateLimitResult(true,.02),'account'=>new RateLimitResult(true,.01)],new ServerContext('42',['recent_failed_logins'=>true]),$policy);
ok($b->score() >= 70,'combined server/client risk reaches high band'); ok(in_array($engine->decision($b->score(),$policy),['interactive','deny'],true),'high risk predicts step-up or deny');
$c=$engine->assess(['js'=>true,'page_age_ms'=>3000,'pointer'=>true,'keyboard'=>false,'touch'=>false],['age_seconds'=>60,'successful_actions'=>5,'failed_actions'=>0,'failed_challenges'=>0],[],new ServerContext('42',['known_good'=>true]),$policy);
ok($c->score()===0,'positive signals clamp score at zero'); echo "Risk engine tests passed.\n";
