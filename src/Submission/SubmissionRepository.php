<?php
declare(strict_types=1);
namespace TwoIzi\Guard\Submission;
use TwoIzi\Guard\Security\Crypto;
use TwoIzi\Guard\Storage\Database;
final class SubmissionRepository
{
    public function __construct(private Database $db,private string $privacyKey){}
    public function hash(string $submissionId):string{return Crypto::hmac($this->privacyKey,'submission|'.$submissionId);}
    public function find(string $submissionId):?array
    {
        $s=$this->db->pdo()->prepare('SELECT *,(expires_at>NOW(6)) is_fresh FROM guard_submissions WHERE submission_hash=? LIMIT 1');$s->execute([$this->hash($submissionId)]);$r=$s->fetch();return $r?:null;
    }
    public function record(string $submissionId,string $sessionHash,string $action,string $tokenHash,string $result,int $ttl=300):void
    {
        $s=$this->db->pdo()->prepare('INSERT INTO guard_submissions (submission_hash,session_hash,action,token_hash,result,created_at,expires_at) VALUES (?,?,?,?,?,NOW(6),DATE_ADD(NOW(6),INTERVAL ? SECOND))');
        $s->execute([$this->hash($submissionId),$sessionHash,$action,$tokenHash,$result,$ttl]);
    }
}
