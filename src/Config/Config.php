<?php
declare(strict_types=1);

namespace TwoIzi\Guard\Config;

use TwoIzi\Guard\Support\Encoding;
use TwoIzi\Guard\Core\ServerContext;

final class Config
{
    public function __construct(private array $data)
    {
        foreach (['database','keys','actions','origin'] as $required) {
            if (!array_key_exists($required,$data)) throw new \InvalidArgumentException("Missing Guard config key: {$required}");
        }
        $this->assertKeyRing('hmac');
        $this->assertKeyRing('privacy');
    }
    public function get(string $key,mixed $default=null):mixed
    {
        $parts=explode('.',$key); $value=$this->data;
        foreach($parts as $part){if(!is_array($value)||!array_key_exists($part,$value))return $default;$value=$value[$part];}
        return $value;
    }
    public function action(string $name):array
    {
        $action=$this->data['actions'][$name]??null;
        if(!is_array($action))throw new \InvalidArgumentException('Unknown Guard action.');
        return $action;
    }
    public function hasAction(string $name):bool{return isset($this->data['actions'][$name])&&is_array($this->data['actions'][$name]);}
    public function actions():array{return (array)$this->data['actions'];}
    public function serverContext(string $action):ServerContext
    {
        $provider=$this->data['server_context_provider']??null;
        if($provider===null)return new ServerContext(null,[],[]);
        if(!is_callable($provider))throw new \RuntimeException('server_context_provider must be callable.');
        $data=$provider($action); if(!is_array($data))throw new \RuntimeException('server_context_provider must return an array.');
        return ServerContext::fromArray($data);
    }
    /** Backwards compatible: returns the active key value. */
    public function key(string $name):string{return $this->activeKey($name)['key'];}
    /** @return array{kid:string,key:string,status:string} */
    public function activeKey(string $name):array
    {
        $ring=$this->keyRing($name); $kid=$ring['active'];
        foreach($ring['keys'] as $entry){if($entry['kid']===$kid)return $entry;}
        throw new \RuntimeException("Active Guard key {$name}/{$kid} not found.");
    }
    /** @return list<array{kid:string,key:string,status:string}> */
    public function verificationKeys(string $name):array
    {
        return array_values(array_filter($this->keyRing($name)['keys'],fn(array $e)=>in_array($e['status'],['active','verify_only'],true)));
    }
    public function keyById(string $name,string $kid):?string
    {
        foreach($this->verificationKeys($name) as $entry)if(hash_equals($entry['kid'],$kid))return $entry['key'];
        return null;
    }
    /** @return array{active:string,keys:list<array{kid:string,key:string,status:string}>} */
    private function keyRing(string $name):array
    {
        $raw=$this->data['keys'][$name]??null;
        if($raw===null && $name==='rate_limit') $raw=$this->data['keys']['privacy']??null;
        if(is_string($raw)){
            return ['active'=>'legacy','keys'=>[['kid'=>'legacy','key'=>$this->decodeKey($raw,$name),'status'=>'active']]];
        }
        if(!is_array($raw))throw new \RuntimeException("Guard key ring {$name} is missing.");
        $active=(string)($raw['active']??''); $entries=[];
        foreach((array)($raw['keys']??[]) as $kid=>$spec){
            if(!is_string($kid)||!preg_match('/^[A-Za-z0-9_.-]{1,32}$/',$kid))throw new \RuntimeException("Invalid key id in {$name} ring.");
            $value=is_array($spec)?($spec['value']??''):$spec;
            $status=is_array($spec)?(string)($spec['status']??($kid===$active?'active':'verify_only')):($kid===$active?'active':'verify_only');
            if(!in_array($status,['active','verify_only','retired'],true))throw new \RuntimeException("Invalid key status for {$name}/{$kid}.");
            $entries[]=['kid'=>$kid,'key'=>$this->decodeKey((string)$value,"{$name}/{$kid}"),'status'=>$status];
        }
        $activeEntries=array_values(array_filter($entries,fn($e)=>$e['status']==='active'));if($active===''||count($activeEntries)!==1||$activeEntries[0]['kid']!==$active)throw new \RuntimeException("Key ring {$name} must have exactly one active key and matching active id.");
        return ['active'=>$active,'keys'=>$entries];
    }
    private function decodeKey(string $value,string $name):string
    {
        if(!str_starts_with($value,'base64url:'))throw new \RuntimeException("Guard key {$name} must use base64url: prefix.");
        $key=Encoding::base64UrlDecode(substr($value,10));
        if(strlen($key)<32)throw new \RuntimeException("Guard key {$name} must be at least 32 bytes.");
        return $key;
    }
    private function assertKeyRing(string $name):void{$this->activeKey($name);}
}
