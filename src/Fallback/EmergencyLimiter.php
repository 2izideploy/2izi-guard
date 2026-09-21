<?php
declare(strict_types=1);
namespace TwoIzi\Guard\Fallback;
final class EmergencyLimiter
{
    public function __construct(private string $dir){}
    public function allow(string $key,int $capacity,int $period):bool
    {
        if($capacity<1||$period<1)return false;
        if(!is_dir($this->dir)&&!@mkdir($this->dir,0700,true)&&!is_dir($this->dir))return false;
        $path=rtrim($this->dir,'/').'/'.hash('sha256',$key).'.json'; $fh=@fopen($path,'c+'); if(!$fh)return false;
        try{if(!flock($fh,LOCK_EX))return false;$raw=stream_get_contents($fh);$data=json_decode($raw?:'{}',true);$now=time();$start=(int)($data['start']??0);$count=(int)($data['count']??0);if($start<=0||$now-$start>=$period){$start=$now;$count=0;}if($count>=$capacity)return false;$count++;ftruncate($fh,0);rewind($fh);fwrite($fh,json_encode(['start'=>$start,'count'=>$count],JSON_UNESCAPED_SLASHES));fflush($fh);return true;}finally{flock($fh,LOCK_UN);fclose($fh);}
    }
}
