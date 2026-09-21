<?php
declare(strict_types=1);
namespace TwoIzi\Guard\Token;
use TwoIzi\Guard\Config\Config;
use TwoIzi\Guard\Risk\ReasonCode;
use TwoIzi\Guard\Security\Crypto;
use TwoIzi\Guard\Security\OriginValidator;
use TwoIzi\Guard\Security\TokenIntegrity;
use TwoIzi\Guard\Storage\Database;
final class TokenRepository
{
    private TokenIntegrity $integrity;
    public function __construct(private Database $db,private Config $config,private OriginValidator $origin){$this->integrity=new TokenIntegrity($config);}
    public function create(string $token,string $sessionHash,int $challengeId,string $action,string $origin,int $ttl,int $riskLevel=0):void
    {
        $key=$this->config->activeKey('privacy');$tokenHash=Crypto::sha256($token);$originHash=$this->origin->hash($origin,$key['key']);$issuedMs=(int)floor(microtime(true)*1000);$expiresMs=$issuedMs+$ttl*1000;
        $row=['token_hash'=>$tokenHash,'session_hash'=>$sessionHash,'challenge_id'=>$challengeId,'action'=>$action,'origin_hash'=>$originHash,'origin_key_id'=>$key['kid'],'risk_level'=>$riskLevel,'issued_at_ms'=>$issuedMs,'expires_at_ms'=>$expiresMs];
        $signed=$this->integrity->sign($row);$state=$this->integrity->signState($tokenHash,'issued');
        $stmt=$this->db->pdo()->prepare("INSERT INTO guard_tokens (token_hash,session_hash,challenge_id,action,origin_hash,origin_key_id,risk_level,issued_at,expires_at,issued_at_ms,expires_at_ms,status,integrity_key_id,integrity_mac,state_key_id,state_mac) VALUES (?,?,?,?,?,?,?,NOW(6),DATE_ADD(NOW(6),INTERVAL ? SECOND),?,?,'issued',?,?,?,?)");
        $stmt->execute([$tokenHash,$sessionHash,$challengeId,$action,$originHash,$key['kid'],$riskLevel,$ttl,$issuedMs,$expiresMs,$signed['kid'],$signed['mac'],$state['kid'],$state['mac']]);
    }
    public function consume(string $token,string $sessionHash,string $action,string $origin):bool{return $this->consumeDetailed($token,$sessionHash,$action,$origin)->valid();}
    public function consumeDetailed(string $token,string $sessionHash,string $action,string $origin):TokenConsumeResult
    {
        $hash=Crypto::sha256($token);$inspect=$this->db->pdo()->prepare('SELECT *, (expires_at>NOW(6)) is_fresh FROM guard_tokens WHERE token_hash=? LIMIT 1');$inspect->execute([$hash]);$row=$inspect->fetch();
        if(!$row)return new TokenConsumeResult(false,ReasonCode::TOKEN_INVALID);
        if(!$this->integrity->verify($row)||!$this->integrity->verifyState($row))return new TokenConsumeResult(false,ReasonCode::TOKEN_INTEGRITY_FAILED);
        if((int)($row['expires_at_ms']??0)<=(int)floor(microtime(true)*1000)||(int)$row['is_fresh']!==1)return new TokenConsumeResult(false,ReasonCode::TOKEN_EXPIRED);
        if(!hash_equals((string)$row['session_hash'],$sessionHash))return new TokenConsumeResult(false,ReasonCode::SESSION_MISMATCH);
        if(!hash_equals((string)$row['action'],$action))return new TokenConsumeResult(false,ReasonCode::ACTION_MISMATCH);
        $kid=(string)($row['origin_key_id']??'');$key=$kid!==''?$this->config->keyById('privacy',$kid):null;if($key===null)return new TokenConsumeResult(false,ReasonCode::ORIGIN_MISMATCH);
        if(!hash_equals((string)$row['origin_hash'],$this->origin->hash($origin,$key)))return new TokenConsumeResult(false,ReasonCode::ORIGIN_MISMATCH);
        if($row['status']==='consumed')return new TokenConsumeResult(false,ReasonCode::TOKEN_REPLAY);
        if($row['status']==='revoked'||$row['revoked_at']!==null)return new TokenConsumeResult(false,ReasonCode::TOKEN_REVOKED);
        if($row['status']!=='issued')return new TokenConsumeResult(false,ReasonCode::TOKEN_INVALID);
        $next=$this->integrity->signState($hash,'consumed');
        $stmt=$this->db->pdo()->prepare("UPDATE guard_tokens SET status='consumed',consumed_at=NOW(6),state_key_id=?,state_mac=? WHERE id=? AND status='issued' AND state_mac=?");
        $stmt->execute([$next['kid'],$next['mac'],$row['id'],$row['state_mac']]);
        if($stmt->rowCount()===1)return new TokenConsumeResult(true,ReasonCode::TOKEN_VALID);
        $again=$this->db->pdo()->prepare('SELECT *, (expires_at>NOW(6)) is_fresh FROM guard_tokens WHERE id=? LIMIT 1');$again->execute([$row['id']]);$latest=$again->fetch();
        if($latest&&$this->integrity->verify($latest)&&$this->integrity->verifyState($latest)&&$latest['status']==='consumed')return new TokenConsumeResult(false,ReasonCode::TOKEN_REPLAY);
        return new TokenConsumeResult(false,ReasonCode::TOKEN_INTEGRITY_FAILED);
    }
}
