<?php
declare(strict_types=1);
namespace TwoIzi\Guard\Diagnostics;
use TwoIzi\Guard\Config\Config;
use TwoIzi\Guard\Storage\Database;
final class DiagnosticsService
{
    public function __construct(private Config $config,private Database $db){}
    public function run():array
    {
        $versionFile=dirname(__DIR__,2).'/VERSION';$version=is_file($versionFile)?trim((string)file_get_contents($versionFile)):'unknown';
        $checks=[];$add=static function(string $name,bool $ok,string $message)use(&$checks){$checks[]=['name'=>$name,'ok'=>$ok,'message'=>$message];};
        $add('PHP',PHP_VERSION_ID>=80100,PHP_VERSION);$add('PDO',extension_loaded('pdo'),extension_loaded('pdo')?'loaded':'missing');$add('pdo_mysql',extension_loaded('pdo_mysql'),extension_loaded('pdo_mysql')?'loaded':'missing');
        $origin=(string)$this->config->get('origin','');$add('HTTPS origin',str_starts_with(strtolower($origin),'https://'),$origin?:'not configured');$add('Secure cookie',(bool)$this->config->get('cookie.secure',true),(bool)$this->config->get('cookie.secure',true)?'enabled':'disabled');
        foreach(['hmac','privacy','rate_limit'] as $ring){try{$a=$this->config->activeKey($ring);$add('Key '.$ring,true,'active kid='.$a['kid'].'; >=256 bit');}catch(\Throwable $e){$add('Key '.$ring,false,$e->getMessage());}}
        try{$this->db->pdo()->query('SELECT 1')->fetchColumn();$add('Database',true,'connected');}catch(\Throwable $e){$add('Database',false,$e->getMessage());return ['version'=>$version,'ok'=>false,'checks'=>$checks];}
        $required=['guard_sessions'=>['pow_ema_ms'],'guard_challenges'=>['issued_at_ms','expires_at_ms','integrity_key_id','integrity_mac','state_key_id','state_mac'],'guard_tokens'=>['origin_key_id','issued_at_ms','expires_at_ms','integrity_key_id','integrity_mac','state_key_id','state_mac'],'guard_rate_limits'=>[],'guard_events'=>['predicted_decision','mode'],'guard_submissions'=>[],'guard_schema_migrations'=>[]];
        foreach($required as $table=>$columns){try{$found=[];foreach($this->db->pdo()->query('SHOW COLUMNS FROM `'.$table.'`')->fetchAll() as $r)$found[]=(string)$r['Field'];$missing=array_values(array_diff($columns,$found));$add('Schema '.$table,$missing===[],$missing===[]?'ok':'missing: '.implode(', ',$missing));}catch(\Throwable){$add('Schema '.$table,false,'table missing or inaccessible');}}
        $backend=(string)$this->config->get('rate_limit.backend','database');$add('Rate limiter',$backend!=='redis'||extension_loaded('redis'),$backend);
        $dir=(string)$this->config->get('emergency.storage_path',dirname(__DIR__,2).'/storage/emergency');$parent=is_dir($dir)?$dir:dirname($dir);$add('Emergency limiter path',is_dir($parent)&&is_writable($parent),$dir);
        $add('Actions',count($this->config->actions())>0,count($this->config->actions()).' registered');$ok=!in_array(false,array_column($checks,'ok'),true);return ['version'=>$version,'ok'=>$ok,'checks'=>$checks];
    }
}
