<?php
declare(strict_types=1);
use TwoIzi\Guard\Config\Config;
use TwoIzi\Guard\Core\Guard;
use TwoIzi\Guard\Fallback\FallbackGuard;

spl_autoload_register(static function(string $class):void{
    $prefix='TwoIzi\\Guard\\';if(!str_starts_with($class,$prefix))return;
    $path=__DIR__.'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';if(is_file($path))require_once $path;
});
$configFile=getenv('IZI_GUARD_CONFIG')?:__DIR__.'/config/guard.php';
if(!is_file($configFile))throw new RuntimeException('2IZI Guard config not found.');
$raw=require $configFile;if(!is_array($raw))throw new RuntimeException('2IZI Guard config must return an array.');
$config=new Config($raw);
try{return Guard::boot($raw);}catch(\Throwable $e){
    $enabled=(bool)$config->get('emergency.enabled',false);
    $infra=$e instanceof \PDOException || (class_exists('RedisException') && $e instanceof \RedisException)
        || ($e instanceof \RuntimeException && (str_contains($e->getMessage(),'Redis rate limiter selected')||str_contains($e->getMessage(),'Unable to connect to Redis')||str_contains($e->getMessage(),'Redis authentication failed')));
    if(!$enabled || !$infra)throw $e;
    return new FallbackGuard($config,$e);
}
