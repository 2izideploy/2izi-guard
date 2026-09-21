<?php
declare(strict_types=1);
namespace TwoIzi\Guard\Fallback;
use TwoIzi\Guard\Config\Config;
use TwoIzi\Guard\Core\GuardResult;
use TwoIzi\Guard\Security\OriginValidator;
final class FallbackGuard
{
    private EmergencyLimiter $limiter; private OriginValidator $origin;
    public function __construct(private Config $config,private \Throwable $cause)
    {
        $dir=(string)$config->get('emergency.storage_path',dirname(__DIR__,2).'/storage/emergency');$this->limiter=new EmergencyLimiter($dir);$this->origin=new OriginValidator($config);
    }
    public function verifyAndConsume(string $token,string $action,?string $submissionId=null):GuardResult
    {
        if(!$this->config->hasAction($action))return new GuardResult(false,'invalid_action');
        if(!$this->origin->validateRequest())return new GuardResult(false,'origin_mismatch','DENY');
        $policy=$this->config->action($action);$mode=(string)($policy['fail_mode']??'closed');
        if($mode!=='open_with_limit')return new GuardResult(false,'guard_unavailable');
        $cap=max(1,(int)($policy['emergency_limit']['capacity']??3));$period=max(1,(int)($policy['emergency_limit']['period']??60));
        $ip=(string)($_SERVER['REMOTE_ADDR']??'unknown');$ok=$this->limiter->allow('network|'.$action.'|'.$ip,$cap,$period);
        if($ok && $submissionId!==null){$ok=$this->limiter->allow('submission|'.$action.'|'.$ip.'|'.$submissionId,1,max(300,$period));if(!$ok)return new GuardResult(false,'idempotent_replay','ALLOW');}
        return new GuardResult($ok,$ok?'emergency_allow':'emergency_rate_limited',$ok?'ALLOW':'DENY');
    }
    public function tokenFromRequest():string{return is_string($_SERVER['HTTP_X_IZI_GUARD']??null)?substr((string)$_SERVER['HTTP_X_IZI_GUARD'],0,256):(is_string($_POST['guard_token']??null)?substr((string)$_POST['guard_token'],0,256):'');}
    public function submissionIdFromRequest():?string{$v=$_SERVER['HTTP_X_IZI_GUARD_SUBMISSION']??($_POST['guard_submission_id']??null);return is_string($v)&&preg_match('/^sub_[A-Za-z0-9_-]{16,80}$/',$v)?$v:null;}
    public function unavailableReason():string{return $this->cause::class;}
}
