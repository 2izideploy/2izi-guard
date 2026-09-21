<?php
declare(strict_types=1);
namespace TwoIzi\Guard\Diagnostics;
use TwoIzi\Guard\Config\Config;
final class TuningAdvisor
{
    public function __construct(private Config $config){}
    public function advise(array $shadow):array
    {
        $out=[];foreach((array)($shadow['actions']??[]) as $row){$n=max(1,(int)$row['total']);$deny=(int)$row['predicted_deny']/$n;$interactive=(int)$row['predicted_interactive']/$n;$pow=(int)$row['predicted_pow']/$n;$action=(string)$row['action'];$notes=[];
            if($n<100)$notes[]='Collect more traffic before changing thresholds.';
            if($deny>0.05)$notes[]='High predicted deny rate (>5%): inspect events before enforcement.';
            if($interactive>0.15)$notes[]='High interactive rate (>15%): verify false positives and mobile UX.';
            if($pow+$interactive+$deny<0.01&&$n>=500)$notes[]='Very low friction rate: consider whether thresholds are too permissive for this action.';
            if(!$notes)$notes[]='No obvious threshold warning from current shadow sample.';
            $out[]=['action'=>$action,'samples'=>$n,'notes'=>$notes,'configured_thresholds'=>$this->config->hasAction($action)?($this->config->action($action)['thresholds']??[]):[]];
        }return $out;
    }
}
