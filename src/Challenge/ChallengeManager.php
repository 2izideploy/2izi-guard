<?php
declare(strict_types=1);

namespace TwoIzi\Guard\Challenge;

use PDO;
use TwoIzi\Guard\Config\Config;
use TwoIzi\Guard\Core\GuardHttpException;
use TwoIzi\Guard\Core\ServerContext;
use TwoIzi\Guard\Locale\Translator;
use TwoIzi\Guard\Logging\SecurityLogger;
use TwoIzi\Guard\Risk\BrowserSignals;
use TwoIzi\Guard\Risk\ReasonCode;
use TwoIzi\Guard\Risk\RiskEngine;
use TwoIzi\Guard\Security\Crypto;
use TwoIzi\Guard\Security\OriginValidator;
use TwoIzi\Guard\Security\TrustedProxyResolver;
use TwoIzi\Guard\Security\ChallengeIntegrity;
use TwoIzi\Guard\Session\GuardSession;
use TwoIzi\Guard\Storage\Database;
use TwoIzi\Guard\Support\Encoding;
use TwoIzi\Guard\Token\TokenRepository;

final class ChallengeManager
{
    public function __construct(
        private Config $config, private Database $db, private GuardSession $session,
        private ChallengeRepository $challenges, private TokenRepository $tokens,
        private OriginValidator $originValidator, private RiskEngine $riskEngine,
        private Translator $translator, private SecurityLogger $logger,
        private TrustedProxyResolver $proxy,
        private ChallengeIntegrity $integrity,
    ) {}

    public function issue(string $action,array $rawSignals,array $limitStates,string $requestedLocale,ServerContext $serverContext,string $mode): array
    {
        $started=hrtime(true); $policy=$this->config->action($action); $signals=BrowserSignals::sanitize($rawSignals);
        $assessment=$this->riskEngine->assess($signals,$this->session->stats(),$limitStates,$serverContext,$policy);
        $predicted=$this->riskEngine->decision($assessment->score(),$policy); $locale=$this->translator->resolve($requestedLocale); $ui=$this->translator->bundle($locale);
        $ttl=max(15,min(300,(int)($policy['challenge_ttl']??$this->config->get('defaults.challenge_ttl',90))));
        $publicId=Crypto::randomId('ch_',18); $salt=Encoding::base64UrlEncode(random_bytes(16));
        $honeypotRequired=array_key_exists('honeypot',$policy)?(bool)$policy['honeypot']:(($signals['form_bound']??false)===true);
        $honeypotName=$honeypotRequired?'g_hp_'.strtolower(substr(bin2hex(random_bytes(8)),0,12)):null;

        $min=(int)($policy['pow']['min_difficulty']??$this->config->get('defaults.pow_min_difficulty',13));
        $max=(int)($policy['pow']['max_difficulty']??$this->config->get('defaults.pow_max_difficulty',19));
        $target=(int)($policy['pow']['target_ms']??$this->config->get('defaults.pow_target_ms',450));
        $max=max($min,min(24,$max)); $min=max(8,min($max,$min)); $floor=$this->riskEngine->difficultyFloor($assessment->score(),$min,$max);
        $difficulty=$this->session->calibratedDifficulty($floor,$max,max(100,$target));
        $interactive=$mode!=='invisible' && $predicted==='interactive'; $interactiveMin=max(500,min(5000,(int)($policy['interactive']['min_hold_ms']??1200)));

        if($mode==='shadow'){
            $this->challenges->create(['public_id'=>$publicId,'session_hash'=>$this->session->hash(),'action'=>$action,'challenge_type'=>'shadow','salt'=>$salt,'difficulty'=>$difficulty,'ttl'=>$ttl,'risk_score'=>$assessment->score(),'reason_codes'=>$assessment->reasons(),'honeypot_name'=>$honeypotName,'honeypot_required'=>$honeypotRequired,'client_locale'=>$locale,'policy_version'=>4,'predicted_decision'=>$predicted,'interactive_required'=>0,'interactive_min_ms'=>0]);
            $token=Crypto::randomToken();
            $tokenTtl=max(30,min(300,(int)($policy['token_ttl']??$this->config->get('defaults.token_ttl',120))));
            $pdo=$this->db->pdo(); $stmt=$pdo->prepare("SELECT id,public_id,state_mac,attempt_count FROM guard_challenges WHERE public_id=? LIMIT 1");$stmt->execute([$publicId]);$created=$stmt->fetch(); if(!$created)throw new \RuntimeException('Challenge creation failed.');
            $id=(int)$created['id']; $this->tokens->create($token,$this->session->hash(),$id,$action,$this->originValidator->expectedOrigin(),$tokenTtl,$assessment->score());
            $next=$this->integrity->signState((string)$created['public_id'],'token_issued',(int)$created['attempt_count']);
            $upd=$pdo->prepare("UPDATE guard_challenges SET status='token_issued',verified_at=NOW(6),token_issued_at=NOW(6),state_key_id=?,state_mac=? WHERE id=? AND status='issued' AND state_mac=?");$upd->execute([$next['kid'],$next['mac'],$id,$created['state_mac']]);if($upd->rowCount()!==1)throw new \RuntimeException('Challenge state race detected.');
            $lat=(int)round((hrtime(true)-$started)/1_000_000); $this->logger->log($action,$this->session->hash(),$this->proxy->clientIp(),$assessment->score(),'shadow_pass',$assessment->reasons(),$lat,$predicted,$mode);
            return ['status'=>'shadow_pass','challenge_id'=>$publicId,'type'=>'shadow','token'=>$token,'expires_in'=>$tokenTtl,'locale'=>$ui['locale'],'ui'=>$ui['messages']];
        }
        if($mode!=='invisible' && $predicted==='deny'){
            $this->logger->log($action,$this->session->hash(),$this->proxy->clientIp(),$assessment->score(),'deny_prechallenge',$assessment->reasons(),0,$predicted,$mode);
            throw new GuardHttpException(403,'request_not_permitted');
        }
        $type=$interactive?'pow_interactive':'pow';
        $this->challenges->create(['public_id'=>$publicId,'session_hash'=>$this->session->hash(),'action'=>$action,'challenge_type'=>$type,'salt'=>$salt,'difficulty'=>$difficulty,'ttl'=>$ttl,'risk_score'=>$assessment->score(),'reason_codes'=>$assessment->reasons(),'honeypot_name'=>$honeypotName,'honeypot_required'=>$honeypotRequired,'client_locale'=>$locale,'policy_version'=>4,'predicted_decision'=>$predicted,'interactive_required'=>$interactive?1:0,'interactive_min_ms'=>$interactive?$interactiveMin:0]);
        $lat=(int)round((hrtime(true)-$started)/1_000_000); $this->logger->log($action,$this->session->hash(),$this->proxy->clientIp(),$assessment->score(),'challenge_'.$type,$assessment->reasons(),$lat,$predicted,$mode);
        return ['status'=>'challenge','challenge_id'=>$publicId,'type'=>$type,'expires_in'=>$ttl,'locale'=>$ui['locale'],'ui'=>$ui['messages'],'parameters'=>['salt'=>$salt,'difficulty'=>$difficulty,'algorithm'=>'SHA-256','format'=>'v1|challenge_id|salt|nonce','honeypot_name'=>$honeypotName,'interactive_min_ms'=>$interactive?$interactiveMin:0]];
    }

    public function verifyAndIssueToken(string $publicId,string $nonce,array $honeypot=[],array $interaction=[]): array
    {
        $started=hrtime(true); if($publicId===''||!preg_match('/^[0-9]{1,10}$/',$nonce))return ['success'=>false,'code'=>'verification_failed'];
        $pdo=$this->db->pdo(); $pdo->beginTransaction();
        try{
            $stmt=$pdo->prepare('SELECT *, (expires_at > NOW(6)) is_fresh FROM guard_challenges WHERE public_id=? FOR UPDATE');$stmt->execute([$publicId]);$row=$stmt->fetch();
            if(!$row||!hash_equals((string)$row['session_hash'],$this->session->hash())){$pdo->rollBack();return ['success'=>false,'code'=>'verification_failed'];}
            if(!$this->integrity->verify($row)||!$this->integrity->verifyState($row)){ $pdo->rollBack(); $this->logger->log((string)$row['action'],$this->session->hash(),$this->proxy->clientIp(),100,'deny_integrity',[ReasonCode::CHALLENGE_INTEGRITY_FAILED],0,'deny','adaptive'); return ['success'=>false,'code'=>'verification_failed']; }
            $nowMs=(int)floor(microtime(true)*1000);$solveMs=max(0,$nowMs-(int)($row['issued_at_ms']??0));if($row['status']!=='issued'||(int)$row['is_fresh']!==1||(int)($row['expires_at_ms']??0)<=$nowMs){$pdo->rollBack();return ['success'=>false,'code'=>'verification_failed'];}
            $maxAttempts=max(1,min(20,(int)$this->config->get('defaults.max_verify_attempts',5))); if((int)$row['attempt_count']>=$maxAttempts){$pdo->rollBack();return ['success'=>false,'code'=>'verification_failed'];}
            $hp=$this->validateHoneypot($row,$honeypot); if($hp!==null){$this->failChallenge($pdo,$row,$maxAttempts,$hp,true);$this->session->recordPow($solveMs,(int)$row['difficulty'],false);$pdo->commit();return ['success'=>false,'code'=>'verification_failed'];}
            if((int)($row['interactive_required']??0)===1){$confirmed=($interaction['confirmed']??false)===true; if(!$confirmed||$solveMs<(int)$row['interactive_min_ms']){$reason=$confirmed?ReasonCode::INTERACTION_TOO_FAST:ReasonCode::INTERACTION_REQUIRED;$this->failChallenge($pdo,$row,$maxAttempts,$reason,false);$this->session->recordPow($solveMs,(int)$row['difficulty'],false);$pdo->commit();return ['success'=>false,'code'=>'verification_failed'];}}
            $hash=hash('sha256','v1|'.$row['public_id'].'|'.$row['salt'].'|'.$nonce,true); if(Crypto::leadingZeroBits($hash)<(int)$row['difficulty']){$this->failChallenge($pdo,$row,$maxAttempts,ReasonCode::POW_FAILED,false);$this->session->recordPow($solveMs,(int)$row['difficulty'],false);$pdo->commit();return ['success'=>false,'code'=>'verification_failed'];}
            $token=Crypto::randomToken();$policy=$this->config->action((string)$row['action']);$ttl=max(30,min(300,(int)($policy['token_ttl']??$this->config->get('defaults.token_ttl',120))));
            $this->tokens->create($token,$this->session->hash(),(int)$row['id'],(string)$row['action'],$this->originValidator->expectedOrigin(),$ttl,(int)$row['risk_score']);
            $attempts=(int)$row['attempt_count']+1;$next=$this->integrity->signState((string)$row['public_id'],'token_issued',$attempts);$upd=$pdo->prepare("UPDATE guard_challenges SET status='token_issued',verified_at=NOW(6),token_issued_at=NOW(6),attempt_count=?,state_key_id=?,state_mac=? WHERE id=? AND status='issued' AND state_mac=?");$upd->execute([$attempts,$next['kid'],$next['mac'],$row['id'],$row['state_mac']]);if($upd->rowCount()!==1)throw new \RuntimeException('Challenge state race detected.');
            $this->session->recordPow($solveMs,(int)$row['difficulty'],true);$pdo->commit();$ui=$this->translator->bundle((string)($row['client_locale']??''));return ['success'=>true,'token'=>$token,'expires_in'=>$ttl,'ui'=>$ui['messages']];
        }catch(\Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }
    private function validateHoneypot(array $row,array $honeypot):?string{$expected=is_string($row['honeypot_name']??null)?(string)$row['honeypot_name']:'';$required=(int)($row['honeypot_required']??0)===1;if($expected==='')return null;$name=is_string($honeypot['name']??null)?substr((string)$honeypot['name'],0,64):'';$value=$honeypot['value']??null;if($required&&!hash_equals($expected,$name))return ReasonCode::HONEYPOT_MISSING;if($value!==null&&!is_string($value))return ReasonCode::HONEYPOT_TRIGGERED;if($name!==''&&hash_equals($expected,$name)&&trim((string)$value)!=='')return ReasonCode::HONEYPOT_TRIGGERED;return null;}
    private function failChallenge(PDO $pdo,array $row,int $maxAttempts,string $reason,bool $terminal):void{$n=(int)$row['attempt_count']+1;$status=($terminal||$n>=$maxAttempts)?'failed':'issued';$next=$this->integrity->signState((string)$row['public_id'],$status,$n);$stmt=$pdo->prepare("UPDATE guard_challenges SET attempt_count=?,status=?,state_key_id=?,state_mac=? WHERE id=? AND status='issued' AND state_mac=?");$stmt->execute([$n,$status,$next['kid'],$next['mac'],$row['id'],$row['state_mac']]);if($stmt->rowCount()!==1)throw new \RuntimeException('Challenge state race detected.');}
}
