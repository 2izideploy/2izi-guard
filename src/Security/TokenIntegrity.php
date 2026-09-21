<?php
declare(strict_types=1);
namespace TwoIzi\Guard\Security;
use TwoIzi\Guard\Config\Config;
final class TokenIntegrity
{
    public function __construct(private Config $config){}
    /** @return array{kid:string,mac:string} */
    public function sign(array $r):array{$a=$this->config->activeKey('hmac');return ['kid'=>$a['kid'],'mac'=>Crypto::hmac($a['key'],$this->canonical($r))];}
    public function verify(array $r):bool{$kid=(string)($r['integrity_key_id']??'');$mac=$r['integrity_mac']??null;if($kid===''||!is_string($mac)||strlen($mac)!==32)return false;$key=$this->config->keyById('hmac',$kid);return $key!==null&&hash_equals(Crypto::hmac($key,$this->canonical($r)),$mac);}
    /** @return array{kid:string,mac:string} */
    public function signState(string $tokenHash,string $status):array{$a=$this->config->activeKey('hmac');return ['kid'=>$a['kid'],'mac'=>Crypto::hmac($a['key'],'token-state-v1|'.bin2hex($tokenHash).'|'.$status)];}
    public function verifyState(array $r):bool{$kid=(string)($r['state_key_id']??'');$mac=$r['state_mac']??null;if($kid===''||!is_string($mac)||strlen($mac)!==32)return false;$key=$this->config->keyById('hmac',$kid);if($key===null)return false;$msg='token-state-v1|'.bin2hex((string)$r['token_hash']).'|'.(string)$r['status'];return hash_equals(Crypto::hmac($key,$msg),$mac);}
    private function canonical(array $r):string{return implode('|',['token-v1',bin2hex((string)($r['token_hash']??'')),bin2hex((string)($r['session_hash']??'')),(string)(int)($r['challenge_id']??0),(string)($r['action']??''),bin2hex((string)($r['origin_hash']??'')),(string)($r['origin_key_id']??''),(string)(int)($r['risk_level']??0),(string)(int)($r['issued_at_ms']??0),(string)(int)($r['expires_at_ms']??0)]);}
}
