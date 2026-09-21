<?php
declare(strict_types=1);
namespace TwoIzi\Guard\Diagnostics;
use TwoIzi\Guard\Storage\Database;
final class EventStatsService
{
    public function __construct(private Database $db){}
    public function summary(int $hours=24):array
    {
        $hours=max(1,min(2160,$hours));$pdo=$this->db->pdo();
        $tot=$pdo->query("SELECT COUNT(*) total,ROUND(AVG(latency_ms),1) avg_latency,MAX(latency_ms) max_latency FROM guard_events WHERE event_time>=DATE_SUB(NOW(6),INTERVAL {$hours} HOUR)")->fetch()?:[];
        $dec=$pdo->query("SELECT decision,COUNT(*) total FROM guard_events WHERE event_time>=DATE_SUB(NOW(6),INTERVAL {$hours} HOUR) GROUP BY decision ORDER BY total DESC")->fetchAll();
        return ['hours'=>$hours,'total'=>(int)($tot['total']??0),'avg_latency_ms'=>(float)($tot['avg_latency']??0),'max_latency_ms'=>(int)($tot['max_latency']??0),'decisions'=>$dec];
    }
    public function recent(int $limit=100):array
    {
        $limit=max(1,min(500,$limit));$sql="SELECT event_time,request_id,action,risk_score,decision,reason_codes,latency_ms,predicted_decision,mode FROM guard_events ORDER BY id DESC LIMIT {$limit}";
        $rows=$this->db->pdo()->query($sql)->fetchAll();foreach($rows as &$r){$r['reason_codes']=json_decode((string)($r['reason_codes']??'[]'),true)?:[];}return $rows;
    }
}
