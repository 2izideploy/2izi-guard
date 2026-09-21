<?php
require __DIR__.'/_common.php';
require __DIR__.'/_style.php';
$d=$guard->diagnostics();
$stats=$guard->eventStats(24);
$actions=$guard->config()->actions();
$denied=0;$challenged=0;
foreach(($stats['decisions']??[]) as $row){
    $decision=(string)($row['decision']??'');$total=(int)($row['total']??0);
    if(str_contains($decision,'deny')||str_contains($decision,'block'))$denied+=$total;
    if(str_contains($decision,'challenge'))$challenged+=$total;
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>2IZI Guard</title><?php guard_admin_style(); ?></head><body><div class="ga-wrap">
<header class="ga-head"><div class="ga-brand"><div class="ga-mark">G</div><div><h1 class="ga-title">2IZI Guard</h1><p class="ga-sub">Self-hosted adaptive protection · 0.4.10</p></div></div><div class="ga-status"><?=$d['ok']?'Protection ready':'Needs attention'?></div></header>
<nav class="ga-nav"><a href="?guard=overview">Overview</a><a href="?guard=preview">Visual preview</a><a href="?guard=events">Events</a><a href="?guard=rules">Rules</a><a href="?guard=tuning">Tuning</a></nav>
<section class="ga-grid">
<div class="ga-card"><div class="ga-kicker">Protected actions</div><div class="ga-value"><?=count($actions)?></div></div>
<div class="ga-card"><div class="ga-kicker">Events · 24h</div><div class="ga-value"><?=(int)($stats['total']??0)?></div></div>
<div class="ga-card"><div class="ga-kicker">Challenges · 24h</div><div class="ga-value"><?=$challenged?></div></div>
<div class="ga-card"><div class="ga-kicker">Average Guard latency</div><div class="ga-value small"><?=gh((string)($stats['avg_latency_ms']??0))?> ms</div></div>
</section>
<section class="ga-section"><h2>Protection state</h2><p>Technical checks remain separate from the visitor-facing Guard badge and adaptive challenge.</p><table><thead><tr><th>Check</th><th>Status</th><th>Details</th></tr></thead><tbody><?php foreach($d['checks'] as $c):?><tr><td><?=gh($c['name'])?></td><td class="<?=$c['ok']?'ga-ok':'ga-bad'?>"><?=$c['ok']?'OK':'FAIL'?></td><td><?=gh($c['message'])?></td></tr><?php endforeach?></tbody></table></section>
<section class="ga-section"><h2>Visitor UI</h2><p>0.4.10 keeps visitor UI minimal: one protected brand state, short checking/verified states, and one compact challenge only when risk requires it.</p><div class="ga-actions"><a class="ga-btn primary" href="?guard=preview">Open visual preview</a><a class="ga-btn" href="?guard=events">Open security events</a></div></section>
<section class="ga-section"><h2>24-hour summary</h2><div class="ga-grid"><div><span class="ga-pill">Denied <?=$denied?></span></div><div><span class="ga-pill">Mode <?=gh((string)$guard->config()->get('mode','shadow'))?></span></div><div><span class="ga-pill">Origin <?=gh((string)$guard->config()->get('origin',''))?></span></div><div><span class="ga-pill">External runtime calls 0</span></div></div></section>
</div></body></html>
