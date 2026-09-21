<?php
declare(strict_types=1);
spl_autoload_register(static function(string $class):void{$p='TwoIzi\\Guard\\';if(!str_starts_with($class,$p))return;$f=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($p))).'.php';if(is_file($f))require_once $f;});
use TwoIzi\Guard\Config\Config;use TwoIzi\Guard\Fallback\FallbackGuard;use TwoIzi\Guard\Support\Encoding;
function keyv(string $s):string{return 'base64url:'.Encoding::base64UrlEncode(hash('sha256',$s,true));}
function ok(bool $v,string $m):void{if(!$v){fwrite(STDERR,"FAIL: $m\n");exit(1);}echo "OK: $m\n";}
$dir=sys_get_temp_dir().'/izi-guard-fallback-'.bin2hex(random_bytes(4));mkdir($dir,0700,true);
$config=new Config(['origin'=>'https://example.test','require_origin_header'=>true,'database'=>[],'keys'=>['hmac'=>keyv('h'),'privacy'=>keyv('p'),'rate_limit'=>keyv('r')],'emergency'=>['enabled'=>true,'storage_path'=>$dir],'actions'=>['contact'=>['fail_mode'=>'open_with_limit','emergency_limit'=>['capacity'=>2,'period'=>60]],'login'=>['fail_mode'=>'closed']]]);
$g=new FallbackGuard($config,new PDOException('offline'));$_SERVER['REMOTE_ADDR']='203.0.113.4';unset($_SERVER['HTTP_ORIGIN']);
ok(!$g->verifyAndConsume('','contact')->allowed(),'degraded mode still rejects missing Origin');
$_SERVER['HTTP_ORIGIN']='https://example.test';
ok(!$g->verifyAndConsume('','login')->allowed(),'critical fail-closed action stays closed');
$r=$g->verifyAndConsume('','contact','sub_abcdefghijklmnop');ok($r->allowed()&&$r->code()==='emergency_allow','explicit open_with_limit action can use emergency limiter');
ok($g->verifyAndConsume('','contact','sub_abcdefghijklmnop')->replayed(),'same degraded submission is not executed twice');
foreach(glob($dir.'/*')?:[] as $f)@unlink($f);@rmdir($dir);echo "Fallback security tests passed.\n";
