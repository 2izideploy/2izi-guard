<?php
declare(strict_types=1);
namespace TwoIzi\Guard\Challenge;
use TwoIzi\Guard\Storage\Database;
use TwoIzi\Guard\Security\ChallengeIntegrity;
final class ChallengeRepository
{
    public function __construct(private Database $db,private ChallengeIntegrity $integrity){}
    public function create(array $r):void
    {
        $issuedMs=(int)floor(microtime(true)*1000);$expiresMs=$issuedMs+((int)$r['ttl']*1000);$r['issued_at_ms']=$issuedMs;$r['expires_at_ms']=$expiresMs;$r['policy_version']=$r['policy_version']??4;
        $signed=$this->integrity->sign($r);$state=$this->integrity->signState((string)$r['public_id'],'issued',0);
        $sql="INSERT INTO guard_challenges (public_id,session_hash,action,challenge_type,salt,difficulty,status,issued_at,expires_at,issued_at_ms,expires_at_ms,risk_score,reason_codes,honeypot_name,honeypot_required,client_locale,policy_version,attempt_count,predicted_decision,interactive_required,interactive_min_ms,integrity_key_id,integrity_mac,state_key_id,state_mac) VALUES (?,?,?,?,?,?,'issued',NOW(6),DATE_ADD(NOW(6),INTERVAL ? SECOND),?,?,?,?,?,?,?,?,0,?,?,?,?,?,?,?)";
        $this->db->pdo()->prepare($sql)->execute([$r['public_id'],$r['session_hash'],$r['action'],$r['challenge_type'],$r['salt'],$r['difficulty'],$r['ttl'],$issuedMs,$expiresMs,$r['risk_score']??0,json_encode(array_values($r['reason_codes']??[]),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),$r['honeypot_name']??null,!empty($r['honeypot_required'])?1:0,$r['client_locale']??null,$r['policy_version'],$r['predicted_decision']??null,!empty($r['interactive_required'])?1:0,(int)($r['interactive_min_ms']??0),$signed['kid'],$signed['mac'],$state['kid'],$state['mac']]);
    }
}
