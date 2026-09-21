<?php
declare(strict_types=1);

// This file is intentionally NOT a public standalone admin endpoint.
// Include/route it only after your project's authenticated admin layer defines:
// define('IZI_GUARD_ADMIN_AUTHORIZED', true);
if (!defined('IZI_GUARD_ADMIN_AUTHORIZED') || IZI_GUARD_ADMIN_AUTHORIZED !== true) {
    http_response_code(403);
    exit('Forbidden');
}

/** @var TwoIzi\Guard\Core\Guard $guard */
$guard = require dirname(__DIR__) . '/bootstrap.php';
$result = $guard->diagnostics();
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>2IZI Guard diagnostics</title>
<style>
body{font:14px/1.5 system-ui,sans-serif;max-width:920px;margin:40px auto;padding:0 20px;color:#1d2433;background:#f6f8fb}main{background:#fff;border:1px solid #e3e7ef;border-radius:14px;padding:24px}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:10px;border-bottom:1px solid #edf0f5}.ok{color:#087a44}.fail{color:#b42318}code{background:#f1f3f7;padding:2px 5px;border-radius:5px}
</style>
</head><body><main>
<h1>2IZI Guard <code><?=htmlspecialchars($result['version'])?></code></h1>
<p>Status: <strong class="<?=$result['ok']?'ok':'fail'?>"><?=$result['ok']?'OK':'Needs attention'?></strong></p>
<table><thead><tr><th>Check</th><th>Status</th><th>Details</th></tr></thead><tbody>
<?php foreach ($result['checks'] as $check): ?>
<tr><td><?=htmlspecialchars($check['name'])?></td><td class="<?=$check['ok']?'ok':'fail'?>"><?=$check['ok']?'OK':'FAIL'?></td><td><?=htmlspecialchars($check['message'])?></td></tr>
<?php endforeach ?>
</tbody></table>
</main></body></html>
