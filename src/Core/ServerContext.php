<?php
declare(strict_types=1);
namespace TwoIzi\Guard\Core;
/** Server-only context resolved by the host application. Never populate this from browser JSON. */
final class ServerContext
{
    public function __construct(private ?string $accountId,private array $signals,private array $assurances=[]){ }
    public function accountId():?string{return $this->accountId;}
    public function signals():array{return $this->signals;}
    public function assurances():array{return $this->assurances;}
    public function hasAssurance(string $name):bool{return ($this->assurances[$name]??false)===true;}
    public static function fromArray(array $data):self
    {
        $account=$data['account_id']??null;if(is_int($account)||is_float($account))$account=(string)$account;if(!is_string($account)||$account==='')$account=null;else$account=substr($account,0,256);
        $signals=[];foreach((array)($data['signals']??[]) as $name=>$value)if(is_string($name)&&preg_match('/^[a-z][a-z0-9_.:-]{0,63}$/',$name)&&is_bool($value))$signals[$name]=$value;
        $assurances=[];foreach((array)($data['assurances']??[]) as $name=>$value)if(is_string($name)&&preg_match('/^[a-z][a-z0-9_.:-]{0,63}$/',$name)&&$value===true)$assurances[$name]=true;
        return new self($account,$signals,$assurances);
    }
}
