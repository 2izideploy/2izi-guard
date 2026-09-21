<?php
declare(strict_types=1);
namespace TwoIzi\Guard\Security;
use TwoIzi\Guard\Config\Config;
final class ChallengeIntegrity
{
    public function __construct(private Config $config){}
    /** @return array{kid:string,mac:string} */
    public function sign(array $r):array
    {
        $active=$this->config->activeKey('hmac');
        return ['kid'=>$active['kid'],'mac'=>Crypto::hmac($active['key'],$this->canonical($r))];
    }
    public function verify(array $r):bool
    {
        $kid=(string)($r['integrity_key_id']??'');$mac=$r['integrity_mac']??null;
        if($kid===''||!is_string($mac)||strlen($mac)!==32)return false;
        $key=$this->config->keyById('hmac',$kid);return $key!==null&&hash_equals(Crypto::hmac($key,$this->canonical($r)),$mac);
    }
    /** @return array{kid:string,mac:string} */
    public function signState(string $publicId,string $status,int $attemptCount):array
    {
        $active=$this->config->activeKey('hmac');$msg='challenge-state-v1|'.$publicId.'|'.$status.'|'.$attemptCount;
        return ['kid'=>$active['kid'],'mac'=>Crypto::hmac($active['key'],$msg)];
    }
    public function verifyState(array $r):bool
    {
        $kid=(string)($r['state_key_id']??'');$mac=$r['state_mac']??null;if($kid===''||!is_string($mac)||strlen($mac)!==32)return false;
        $key=$this->config->keyById('hmac',$kid);if($key===null)return false;
        $msg='challenge-state-v1|'.(string)$r['public_id'].'|'.(string)$r['status'].'|'.(int)$r['attempt_count'];
        return hash_equals(Crypto::hmac($key,$msg),$mac);
    }
    private function canonical(array $r):string
    {
        $parts=['challenge-v1',(string)($r['public_id']??''),bin2hex((string)($r['session_hash']??'')),(string)($r['action']??''),(string)($r['challenge_type']??''),(string)($r['salt']??''),(string)(int)($r['difficulty']??0),(string)($r['honeypot_name']??''),(string)(int)($r['honeypot_required']??0),(string)(int)($r['policy_version']??0),(string)($r['predicted_decision']??''),(string)(int)($r['interactive_required']??0),(string)(int)($r['interactive_min_ms']??0),(string)(int)($r['risk_score']??0),(string)(int)($r['issued_at_ms']??0),(string)(int)($r['expires_at_ms']??0)];
        return implode('|',$parts);
    }
}
