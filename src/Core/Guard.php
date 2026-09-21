<?php
declare(strict_types=1);

namespace TwoIzi\Guard\Core;

use TwoIzi\Guard\Challenge\ChallengeManager;
use TwoIzi\Guard\Challenge\ChallengeRepository;
use TwoIzi\Guard\Config\Config;
use TwoIzi\Guard\Diagnostics\DiagnosticsService;
use TwoIzi\Guard\Diagnostics\ShadowStatsService;
use TwoIzi\Guard\Locale\Translator;
use TwoIzi\Guard\Logging\SecurityLogger;
use TwoIzi\Guard\RateLimit\DatabaseRateLimiter;
use TwoIzi\Guard\RateLimit\RateLimiterInterface;
use TwoIzi\Guard\RateLimit\RateLimitResult;
use TwoIzi\Guard\RateLimit\RedisRateLimiter;
use TwoIzi\Guard\Risk\ReasonCode;
use TwoIzi\Guard\Risk\RiskEngine;
use TwoIzi\Guard\Security\Crypto;
use TwoIzi\Guard\Security\OriginValidator;
use TwoIzi\Guard\Security\ChallengeIntegrity;
use TwoIzi\Guard\Submission\SubmissionRepository;
use TwoIzi\Guard\Fallback\EmergencyLimiter;
use TwoIzi\Guard\Security\TrustedProxyResolver;
use TwoIzi\Guard\Session\GuardSession;
use TwoIzi\Guard\Storage\Database;
use TwoIzi\Guard\Token\TokenConsumeResult;
use TwoIzi\Guard\Token\TokenRepository;

final class Guard
{
    private static ?self $instance = null;
    private Config $config; private Database $db; private GuardSession $session;
    private OriginValidator $origin; private TrustedProxyResolver $proxy;
    private RateLimiterInterface $limiter; private ChallengeManager $challengeManager;
    private TokenRepository $tokens; private SecurityLogger $logger;
    private DiagnosticsService $diagnostics; private ShadowStatsService $shadowStats; private \TwoIzi\Guard\Diagnostics\EventStatsService $eventStats;
    private Translator $translator; private SubmissionRepository $submissions;

    private function __construct(array $data)
    {
        $this->config=new Config($data); $this->db=new Database($this->config);
        $this->session=new GuardSession($this->config,$this->db); $this->origin=new OriginValidator($this->config);
        $this->proxy=new TrustedProxyResolver($this->config); $this->tokens=new TokenRepository($this->db,$this->config,$this->origin);
        $this->submissions=new SubmissionRepository($this->db,$this->config->key('privacy'));
        $backend=(string)$this->config->get('rate_limit.backend','database');
        $this->limiter=$backend==='redis' ? new RedisRateLimiter($this->config,$this->config->key('rate_limit')) : new DatabaseRateLimiter($this->db,$this->config->key('rate_limit'));
        $this->logger=new SecurityLogger($this->db,$this->config->key('privacy')); $risk=new RiskEngine();
        $this->translator=new Translator($this->config); $challengeIntegrity=new ChallengeIntegrity($this->config);
        $this->challengeManager=new ChallengeManager($this->config,$this->db,$this->session,new ChallengeRepository($this->db,$challengeIntegrity),$this->tokens,$this->origin,$risk,$this->translator,$this->logger,$this->proxy,$challengeIntegrity);
        $this->diagnostics=new DiagnosticsService($this->config,$this->db); $this->shadowStats=new ShadowStatsService($this->db); $this->eventStats=new \TwoIzi\Guard\Diagnostics\EventStatsService($this->db);
    }
    public static function boot(array $config): self { return self::$instance ??= new self($config); }
    public static function instance(): self { if(!self::$instance)throw new \RuntimeException('2IZI Guard is not booted.'); return self::$instance; }

    public function challenge(string $action,array $signals=[],string $locale=''): array
    {
        $this->assertAction($action); if(!$this->origin->validateRequest())throw new GuardHttpException(403,'request_not_permitted');
        $ctx=$this->config->serverContext($action); $mode=$this->effectiveMode($action); $limits=$this->applyLimits($action,$ctx); if($mode!=='shadow'&&$mode!=='disabled'&&!$this->assurancesSatisfied($action,$ctx))throw new GuardHttpException(403,'step_up_required');
        return $this->challengeManager->issue($action,$signals,$limits,$locale,$ctx,$mode);
    }
    public function verifyChallenge(string $challengeId,string $nonce,array $honeypot=[],array $interaction=[]): array
    {
        if(!$this->origin->validateRequest())throw new GuardHttpException(403,'request_not_permitted'); $this->applyVerifyLimits();
        return $this->challengeManager->verifyAndIssueToken($challengeId,$nonce,$honeypot,$interaction);
    }
    public function verifyAndConsume(string $token,string $action,?string $submissionId=null): GuardResult
    {
        $this->assertAction($action);
        try { return $this->verifyAndConsumeInternal($token,$action,$submissionId); }
        catch (\Throwable $e) { if($this->isInfrastructureFailure($e))return $this->degradedResult($action,$submissionId??$this->submissionIdFromRequest()); throw $e; }
    }
    private function verifyAndConsumeInternal(string $token,string $action,?string $submissionId=null): GuardResult
    {
        $mode=$this->effectiveMode($action); if($mode==='disabled')return new GuardResult(true,'disabled','ALLOW');
        if(!$this->origin->validateRequest())return new GuardResult(false,'origin_mismatch','DENY');
        if(!$this->consumeLimitAllowed($action))return new GuardResult(false,'rate_limited','DENY');
        $freshContext=$this->config->serverContext($action); if($mode!=='shadow'&&!$this->assurancesSatisfied($action,$freshContext))return new GuardResult(false,'step_up_required','DENY');
        $submissionId=$submissionId??$this->submissionIdFromRequest();
        if($submissionId!==null){
            $existing=$this->submissions->find($submissionId);
            if($existing && (int)$existing['is_fresh']===1 && hash_equals((string)$existing['session_hash'],$this->session->hash()) && hash_equals((string)$existing['action'],$action) && hash_equals((string)$existing['token_hash'],Crypto::sha256($token)) && $existing['result']==='allow'){
                return new GuardResult(false,'idempotent_replay','ALLOW');
            }
        }
        $consume=$token===''?new TokenConsumeResult(false,ReasonCode::TOKEN_INVALID):$this->tokens->consumeDetailed($token,$this->session->hash(),$action,$this->origin->expectedOrigin());
        if($consume->valid()){
            if($submissionId!==null){try{$this->submissions->record($submissionId,$this->session->hash(),$action,Crypto::sha256($token),'allow');}catch(\Throwable){/* a concurrent duplicate will be handled as replay; application must keep its own transaction idempotent */}}
            $this->session->recordActionResult(true); $this->logger->log($action,$this->session->hash(),$this->proxy->clientIp(),0,$mode==='shadow'?'allow_shadow':'allow',[ReasonCode::TOKEN_VALID],0,null,$mode); return new GuardResult(true,$mode==='shadow'?'shadow':'ok','ALLOW');
        }
        if($mode==='shadow'){ $this->logger->log($action,$this->session->hash(),$this->proxy->clientIp(),100,'allow_shadow',[$consume->reason()],0,null,$mode); return new GuardResult(true,'shadow','DENY'); }
        $this->session->recordActionResult(false); $this->logger->log($action,$this->session->hash(),$this->proxy->clientIp(),100,'deny',[$consume->reason()],0,'deny',$mode); return new GuardResult(false,strtolower($consume->reason()));
    }
    private function consumeLimitAllowed(string $action): bool
    {
        $limits=(array)$this->config->get('defaults.consume_limits',[]);
        foreach(['global','network'] as $kind){if(!isset($limits[$kind]))continue;$r=$limits[$kind];$key=$kind==='global'?'consume|global|'.$action:'consume|network|'.$action.'|'.$this->proxy->networkKey();if(!$this->limiter->consume($key,(int)$r['capacity'],(int)$r['period'])->allowed())return false;}
        return true;
    }
    private function assurancesSatisfied(string $action,ServerContext $ctx): bool
    {
        $required=(array)($this->config->action($action)['required_assurances']??[]);
        foreach($required as $name){if(!is_string($name)||!preg_match('/^[a-z][a-z0-9_.:-]{0,63}$/',$name)||!$ctx->hasAssurance($name))return false;}
        return true;
    }
    private function isInfrastructureFailure(\Throwable $e): bool
    {
        if($e instanceof \PDOException)return true;
        if(class_exists('RedisException')&&$e instanceof \RedisException)return true;
        return false;
    }
    private function degradedResult(string $action,?string $submissionId): GuardResult
    {
        if(!$this->origin->validateRequest())return new GuardResult(false,'origin_mismatch','DENY');
        $policy=$this->config->action($action);$failMode=(string)($policy['fail_mode']??'closed');
        if($failMode!=='open_with_limit'||!(bool)$this->config->get('emergency.enabled',false))return new GuardResult(false,'guard_unavailable','DENY');
        $dir=(string)$this->config->get('emergency.storage_path',dirname(__DIR__,2).'/storage/emergency');$limiter=new EmergencyLimiter($dir);
        $cap=max(1,(int)($policy['emergency_limit']['capacity']??3));$period=max(1,(int)($policy['emergency_limit']['period']??60));$ip=(string)($_SERVER['REMOTE_ADDR']??'unknown');
        if(!$limiter->allow('network|'.$action.'|'.$ip,$cap,$period))return new GuardResult(false,'emergency_rate_limited','DENY');
        if($submissionId!==null&&!$limiter->allow('submission|'.$action.'|'.$ip.'|'.$submissionId,1,max(300,$period)))return new GuardResult(false,'idempotent_replay','ALLOW');
        return new GuardResult(true,'emergency_allow','ALLOW');
    }
    public function tokenFromRequest(): string { $h=$_SERVER['HTTP_X_IZI_GUARD']??''; if(is_string($h)&&$h!=='')return substr($h,0,256); return is_string($_POST['guard_token']??null)?substr($_POST['guard_token'],0,256):''; }
    public function submissionIdFromRequest(): ?string { $v=$_SERVER['HTTP_X_IZI_GUARD_SUBMISSION']??($_POST['guard_submission_id']??null); return is_string($v)&&preg_match('/^sub_[A-Za-z0-9_-]{16,80}$/',$v)?$v:null; }
    public function config(): Config{return $this->config;} public function diagnostics():array{return $this->diagnostics->run();} public function shadowStats(int $hours=168):array{return $this->shadowStats->summary($hours);} public function recentEvents(int $limit=100):array{return $this->eventStats->recent($limit);} public function eventStats(int $hours=24):array{return $this->eventStats->summary($hours);} public function ui(string $locale=''):array{return $this->translator->bundle($locale)['messages'];}
    private function assertAction(string $action):void{if(!preg_match('/^[a-z][a-z0-9_.:-]{0,63}$/',$action)||!$this->config->hasAction($action))throw new GuardHttpException(400,'invalid_action');}
    private function applyLimits(string $action,ServerContext $ctx):array
    {
        $policy=$this->config->action($action); $limits=(array)($policy['limits']??[]); $states=[];
        foreach(['global','network'] as $kind){if(!isset($limits[$kind]))continue;$rule=$limits[$kind];$key=$kind==='global'?"global|$action":"network|$action|".$this->proxy->networkKey();$states[$kind]=$this->limiter->consume($key,(int)$rule['capacity'],(int)$rule['period']);if(!$states[$kind]->allowed())throw new GuardHttpException(429,'rate_limited');}
        if(isset($limits['account'])&&$ctx->accountId()!==null){$r=$limits['account'];$acc=bin2hex(Crypto::hmac($this->config->key('rate_limit'),'account|'.$ctx->accountId()));$states['account']=$this->limiter->consume("account|$action|$acc",(int)$r['capacity'],(int)$r['period']);if(!$states['account']->allowed())throw new GuardHttpException(429,'rate_limited');}
        if(isset($limits['session'])){$r=$limits['session'];$states['session']=$this->limiter->consume("session|$action|".bin2hex($this->session->hash()),(int)$r['capacity'],(int)$r['period']);if(!$states['session']->allowed())throw new GuardHttpException(429,'rate_limited');}
        return $states;
    }
    private function applyVerifyLimits():void
    {
        $limits=(array)$this->config->get('defaults.verify_limits',[]); foreach(['global','network'] as $kind){if(!isset($limits[$kind]))continue;$r=$limits[$kind];$key=$kind==='global'?'verify|global':'verify|network|'.$this->proxy->networkKey();if(!$this->limiter->consume($key,(int)$r['capacity'],(int)$r['period'])->allowed())throw new GuardHttpException(429,'rate_limited');}
    }
    private function effectiveMode(string $action):string{$g=(string)$this->config->get('mode','shadow');if($g==='shadow'||$g==='disabled')return $g;return(string)($this->config->action($action)['mode']??$g);}
}
