<?php
declare(strict_types=1);

namespace TwoIzi\Guard\Risk;

use TwoIzi\Guard\Core\ServerContext;
use TwoIzi\Guard\RateLimit\RateLimitResult;

final class RiskEngine
{
    public function assess(array $signals, array $sessionStats, array $limits, ServerContext $serverContext, array $policy): RiskAssessment
    {
        $score = 0; $reasons = [];
        $add = static function (int $weight, string $reason) use (&$score, &$reasons): void {
            $score += $weight; if (!in_array($reason, $reasons, true)) $reasons[] = $reason;
        };
        if ((int)($sessionStats['age_seconds'] ?? 0) < 10) $add(5, ReasonCode::NEW_SESSION);
        $fa=(int)($sessionStats['failed_actions']??0); if($fa>=2)$add(min(25,8+$fa*3),ReasonCode::SESSION_FAILURE_HISTORY);
        $fc=(int)($sessionStats['failed_challenges']??0); if($fc>=2)$add(min(20,5+$fc*2),ReasonCode::CHALLENGE_FAILURE_HISTORY);
        if((int)($sessionStats['successful_actions']??0)>=3 && $fa===0 && $fc===0)$add(-10,ReasonCode::SESSION_SUCCESS_HISTORY);
        if (($signals['js'] ?? false)!==true) $add(20,ReasonCode::NO_JS_STATE); elseif((int)($signals['page_age_ms']??0)<250)$add(12,ReasonCode::ABNORMAL_TIMING);
        if (($signals['pointer']??false)!==true && ($signals['keyboard']??false)!==true && ($signals['touch']??false)!==true)$add(3,ReasonCode::MISSING_INTERACTION);
        $this->addPressure($limits['network']??null,12,ReasonCode::NETWORK_VELOCITY,$add);
        $this->addPressure($limits['session']??null,15,ReasonCode::SESSION_VELOCITY,$add);
        $this->addPressure($limits['global']??null,8,ReasonCode::GLOBAL_VELOCITY,$add);
        $this->addPressure($limits['account']??null,18,ReasonCode::ACCOUNT_VELOCITY,$add);
        $defs=(array)($policy['application_signals']??[]);
        foreach($serverContext->signals() as $name=>$active){
            if(!$active || !array_key_exists($name,$defs)) continue;
            $weight=max(-30,min(30,(int)$defs[$name]));
            if($weight===0) continue;
            $add($weight, ($weight>0?ReasonCode::APPLICATION_RISK_SIGNAL:ReasonCode::APPLICATION_TRUST_SIGNAL).':'.$name);
        }
        return new RiskAssessment(max(0,min(100,$score)),$reasons);
    }
    public function decision(int $score,array $policy): string
    {
        $t=(array)($policy['thresholds']??[]);
        $pow=(int)($t['pow']??30); $interactive=(int)($t['interactive']??70); $deny=(int)($t['deny']??92);
        return match(true){$score >= $deny=>'deny',$score >= $interactive=>'interactive',$score >= $pow=>'pow',default=>'pass'};
    }
    public function difficultyFloor(int $score,int $minDifficulty,int $maxDifficulty): int
    {
        $boost=match(true){$score>=70=>4,$score>=50=>3,$score>=30=>2,$score>=15=>1,default=>0};
        return max($minDifficulty,min($maxDifficulty,$minDifficulty+$boost));
    }
    private function addPressure(mixed $result,int $weight,string $reason,callable $add): void
    {
        if(!$result instanceof RateLimitResult)return; $p=$result->pressure();
        if($p>=.90)$add($weight,$reason); elseif($p>=.75)$add(max(1,(int)round($weight*.6)),$reason);
    }
}
