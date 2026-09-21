<?php
require __DIR__.'/_common.php';
require __DIR__.'/_style.php';

$locale=is_string($_GET['locale']??null)?substr((string)$_GET['locale'],0,32):'ru';
$viewport=(string)($_GET['viewport']??'phone');
$viewports=['desktop'=>960,'tablet'=>720,'phone'=>390,'small'=>320];
if(!isset($viewports[$viewport]))$viewport='phone';
$width=$viewports[$viewport];
$input=(string)($_GET['input']??'touch');
if(!in_array($input,['pointer','touch','keyboard'],true))$input='touch';
$state=(string)($_GET['state']??'challenge');
if(!in_array($state,['protected','checking','verified','challenge'],true))$state='challenge';

function gpq(array $overrides=[]): string {
    $base=[
        'guard'=>'preview',
        'locale'=>(string)($_GET['locale']??'ru'),
        'viewport'=>(string)($_GET['viewport']??'phone'),
        'input'=>(string)($_GET['input']??'touch'),
        'state'=>(string)($_GET['state']??'challenge'),
    ];
    return '?'.http_build_query(array_merge($base,$overrides));
}

$stateLabels=['protected'=>'Защищено','checking'=>'Проверяем','verified'=>'Проверено','challenge'=>'Проверка безопасности'];
?><!doctype html>
<html lang="<?=gh($locale)?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>2IZI Guard · Предпросмотр</title>
<?php guard_admin_style(); ?>
<style>
.ga-preview-v2{display:grid;gap:16px}
.ga-preview-toolbar{display:grid;gap:12px}
.ga-preview-toolbar .ga-control-row{margin-top:0}
.ga-preview-canvas-wrap{overflow:auto;padding:18px 4px 6px}
.ga-preview-canvas{width:min(100%,var(--ga-preview-width));min-height:260px;margin:0 auto;border:1px solid var(--ga-border);border-radius:22px;background:#f8fbfd;box-shadow:0 10px 34px rgba(35,65,92,.06);transition:width .18s ease}
.ga-preview-canvas-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 16px;border-bottom:1px solid #e6edf4;color:var(--ga-muted);font-size:12px;font-weight:750}
.ga-preview-canvas-body{min-height:220px;display:grid;place-items:center;padding:28px 24px}
.ga-preview-mount{width:100%;display:grid;place-items:center;min-width:0;min-inline-size:0}
.ga-preview-mount>form{display:block!important;box-sizing:border-box!important;width:100%!important;max-width:100%!important;min-width:0!important;min-inline-size:0!important;margin:0!important;padding:0!important;border:0!important;justify-self:stretch!important;align-self:stretch!important}
.ga-preview-caption{display:flex;justify-content:center;gap:8px;flex-wrap:wrap;margin-top:10px;color:var(--ga-muted);font-size:12px;font-weight:700}
.ga-preview-chip{display:inline-flex;align-items:center;gap:6px;padding:5px 8px;border-radius:999px;background:#f0f5f9;color:#5d7287}
.ga-isolation-card{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;gap:18px;padding:16px;border:1px solid var(--ga-border);border-radius:14px;background:#fbfdff}
.ga-isolation-title{font-weight:800;margin-bottom:3px}.ga-isolation-copy{color:var(--ga-muted)}
.ga-isolation-result{margin-top:12px;padding:12px 14px;border-radius:11px;background:#f5fbf7;color:var(--ga-green);font-weight:700}.ga-isolation-result[hidden]{display:none}
.ga-isolation-result.bad{background:#fff7f6;color:var(--ga-red)}
.ga-hostile-fixture{position:fixed;left:-10000px;top:0;width:390px;padding:40px!important;font-size:32px!important;line-height:2!important;font-family:serif!important;letter-spacing:3px!important}
.ga-hostile-fixture button{font-size:32px!important;line-height:2!important;padding:28px!important;border-radius:0!important;background:#ffeb3b!important;color:#d50000!important}
.ga-hostile-fixture svg{width:96px!important;height:96px!important}
.ga-hostile-fixture izi-guard-surface{width:999px!important;max-width:none!important;padding:40px!important;border:12px solid #f0f!important;background:#ff0!important}
@media(max-width:620px){.ga-preview-canvas-wrap{padding-inline:0}.ga-preview-canvas-body{padding:22px 12px}.ga-isolation-card{grid-template-columns:1fr}.ga-isolation-card .ga-btn{width:100%}}
</style>
</head>
<body>
<div class="ga-wrap">
<header class="ga-head">
    <div class="ga-brand"><div class="ga-mark">G</div><div><h1 class="ga-title">Предпросмотр защиты</h1><p class="ga-sub">2IZI Guard 0.4.10 · один компонент, одно состояние</p></div></div>
    <a class="ga-btn" href="?guard=overview">Назад</a>
</header>

<div class="ga-note">Предпросмотр локальный и не влияет на реальную защиту, security events или rate limits.</div>

<section class="ga-section ga-preview-v2">
    <div class="ga-preview-toolbar">
        <div class="ga-control-row"><strong>Размер</strong><div class="ga-segment">
            <?php foreach(['desktop'=>'Desktop · 960','tablet'=>'Tablet · 720','phone'=>'Phone · 390','small'=>'Phone · 320'] as $key=>$label): ?>
            <a class="<?=$viewport===$key?'active':''?>" href="<?=gh(gpq(['viewport'=>$key]))?>"><?=gh($label)?></a>
            <?php endforeach; ?>
        </div></div>
        <div class="ga-control-row"><strong>Управление</strong><div class="ga-segment">
            <?php foreach(['pointer'=>'Мышь','touch'=>'Touch','keyboard'=>'Клавиатура'] as $key=>$label): ?>
            <a class="<?=$input===$key?'active':''?>" href="<?=gh(gpq(['input'=>$key]))?>"><?=gh($label)?></a>
            <?php endforeach; ?>
        </div></div>
        <div class="ga-control-row"><strong>Состояние</strong><div class="ga-segment">
            <?php foreach($stateLabels as $key=>$label): ?>
            <a class="<?=$state===$key?'active':''?>" href="<?=gh(gpq(['state'=>$key]))?>"><?=gh($label)?></a>
            <?php endforeach; ?>
        </div></div>
    </div>

    <div class="ga-preview-canvas-wrap">
        <div class="ga-preview-canvas" style="--ga-preview-width:<?=$width?>px">
            <div class="ga-preview-canvas-head"><span><?=gh((string)$width)?> px</span><span><?=gh($stateLabels[$state])?></span></div>
            <div class="ga-preview-canvas-body"><div id="guard-preview-mount" class="ga-preview-mount"></div></div>
        </div>
        <div class="ga-preview-caption"><span class="ga-preview-chip">Shadow DOM</span><span class="ga-preview-chip"><?=gh($input)?></span><span class="ga-preview-chip"><?=gh((string)$width)?> px</span></div>
    </div>
</section>

<section class="ga-section">
    <h2>Изоляция интерфейса</h2>
    <div class="ga-isolation-card">
        <div><div class="ga-isolation-title">Shadow DOM активен</div><div class="ga-isolation-copy">Конфликтные CSS-правила хост-проекта проверяются отдельно и не искажают основной предпросмотр.</div></div>
        <button type="button" class="ga-btn" id="guard-isolation-test">Проверить изоляцию</button>
    </div>
    <div class="ga-isolation-result" id="guard-isolation-result" hidden></div>
</section>
</div>

<script src="/guard/public/assets/guard.js?v=0.4.10" data-guard-base="/guard/public" data-guard-css="/guard/public/assets/guard.css?v=0.4.10" data-guard-worker="/guard/public/assets/guard-worker.js?v=0.4.10" data-guard-isolation="shadow"></script>
<script>
(function(){
    if(!window.IZIGuard)return;
    var mount=document.getElementById('guard-preview-mount');
    var state=<?=json_encode($state,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>;
    var input=<?=json_encode($input,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>;
    IZIGuard.preview(mount,{
        state:state,
        locale:<?=json_encode($locale,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>,
        modality:input,
        isolation:'shadow',
        holdMs:1200,
        resetAfterMs:2200
    });

    var button=document.getElementById('guard-isolation-test');
    var result=document.getElementById('guard-isolation-result');

    function waitForShadowCss(shadowRoot, timeoutMs){
        return new Promise(function(resolve){
            var link=shadowRoot&&shadowRoot.querySelector('link[data-izi-guard-shadow-css]');
            if(!link){ resolve({ok:false,reason:'css_link_missing'}); return; }
            if(link.sheet){ resolve({ok:true}); return; }
            var settled=false;
            function finish(ok,reason){
                if(settled) return;
                settled=true;
                resolve({ok:ok,reason:reason||''});
            }
            link.addEventListener('load',function(){finish(true);},{once:true});
            link.addEventListener('error',function(){finish(false,'css_load_error');},{once:true});
            setTimeout(function(){finish(!!link.sheet,link.sheet?'':'css_load_timeout');},timeoutMs||2000);
        });
    }

    function nextPaint(){
        return new Promise(function(resolve){
            requestAnimationFrame(function(){requestAnimationFrame(resolve);});
        });
    }

    button.addEventListener('click',async function(){
        button.disabled=true; button.textContent='Проверяем…';
        result.hidden=true;
        var fixture=document.createElement('div'); fixture.className='ga-hostile-fixture';
        document.body.appendChild(fixture);
        try{
            IZIGuard.preview(fixture,{state:'challenge',locale:'ru',modality:'touch',isolation:'shadow',holdMs:1200,resetAfterMs:0});
            var host=fixture.querySelector('izi-guard-surface');
            if(!host){ throw new Error('host_missing'); }
            var shadow=host.shadowRoot;
            if(!shadow){ throw new Error('shadow_root_missing'); }

            var cssState=await waitForShadowCss(shadow,2500);
            if(!cssState.ok){ throw new Error(cssState.reason); }
            await nextPaint();

            var card=shadow.querySelector('.izi-guard-interactive');
            var hold=shadow.querySelector('.izi-guard-hold');
            var title=shadow.querySelector('.izi-guard-interactive-title');
            if(!(card&&hold&&title)){ throw new Error('ui_missing'); }

            var hostWidth=host.getBoundingClientRect().width;
            var cardWidth=card.getBoundingClientRect().width;
            var holdHeight=hold.getBoundingClientRect().height;
            var titleStyle=getComputedStyle(title);
            var firstFont=(titleStyle.fontFamily||'').split(',')[0].trim().replace(/^['"]|['"]$/g,'').toLowerCase();
            var fontOk=firstFont!=='' && firstFont!=='serif';
            var widthOk=hostWidth<=421&&cardWidth<=421;
            var touchOk=holdHeight>=47&&holdHeight<90;
            var ok=widthOk&&touchOk&&fontOk;

            result.classList.toggle('bad',!ok);
            if(ok){
                result.textContent='✓ CSS-изоляция работает: Shadow DOM и guard.css защищают интерфейс Guard.';
            }else{
                var reasons=[];
                if(!widthOk) reasons.push('ширина компонента изменена внешним CSS');
                if(!touchOk) reasons.push('высота action-кнопки изменена внешним CSS');
                if(!fontOk) reasons.push('шрифт Guard унаследован от страницы');
                result.textContent='✕ CSS-изоляция не прошла: '+reasons.join('; ')+'.';
            }
        }catch(e){
            result.classList.add('bad');
            var map={
                host_missing:'не создан host izi-guard-surface',
                shadow_root_missing:'Shadow DOM не создан',
                css_link_missing:'ссылка на guard.css отсутствует внутри Shadow DOM',
                css_load_error:'guard.css не загрузился внутри Shadow DOM',
                css_load_timeout:'guard.css не успел загрузиться внутри Shadow DOM',
                ui_missing:'в Shadow DOM отсутствуют элементы Guard'
            };
            result.textContent='✕ CSS-изоляция не прошла: '+(map[e.message]||'ошибка диагностического теста')+'.';
        }finally{
            result.hidden=false;
            fixture.remove();
            button.disabled=false;
            button.textContent='Проверить изоляцию';
        }
    });
})();
</script>
</body>
</html>
