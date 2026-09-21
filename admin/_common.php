<?php
/** SECURITY: host application must authenticate the administrator first, then define IZI_GUARD_ADMIN_AUTHORIZED=true before include. */
if(!defined('IZI_GUARD_ADMIN_AUTHORIZED') || IZI_GUARD_ADMIN_AUTHORIZED !== true){http_response_code(403);exit('Forbidden');}
/** @var TwoIzi\Guard\Core\Guard $guard */
if(!isset($guard)){$guard=require dirname(__DIR__).'/bootstrap.php';}
function gh(mixed $v):string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
?>
