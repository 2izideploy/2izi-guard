(() => {
'use strict';

const script = document.currentScript;
const base = script?.dataset.guardBase || '/guard/public';
const workerUrl = script?.dataset.guardWorker || `${base}/assets/guard-worker.js`;
const cssUrl = script?.dataset.guardCss || `${base}/assets/guard.css`;
const shadowFailOpen = script?.dataset.guardFailOpen === 'true';
const defaultBadgeMode = normalizeBadgeMode(script?.dataset.guardBadge || 'always');
const defaultVerifiedMs = clampInt(script?.dataset.guardVerifiedMs, 0, 10000, 900);
const configuredLocale = (script?.dataset.guardLocale || '').trim();
const defaultIsolation = normalizeIsolation(script?.dataset.guardIsolation || 'shadow');
const pageStarted = performance.now();

const activity = {pointer:false, keyboard:false, touch:false, visibilityChanges:0};
if (window.customElements && !customElements.get('izi-guard-surface')) {
    customElements.define('izi-guard-surface', class extends HTMLElement {});
}
addEventListener('pointerdown', e => {
    activity.pointer = true;
    if (e.pointerType === 'touch') activity.touch = true;
}, {passive:true, capture:true});
addEventListener('keydown', () => { activity.keyboard = true; }, {passive:true, capture:true});
document.addEventListener('visibilitychange', () => {
    activity.visibilityChanges = Math.min(100, activity.visibilityChanges + 1);
}, {passive:true});

if (!document.querySelector('link[data-izi-guard-css]')) {
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = cssUrl;
    link.dataset.iziGuardCss = '1';
    document.head.appendChild(link);
}

const builtin = {
    en: {
        protected:'Protected', checking:'Checking…', verified:'Verified',
        challenge:'Security check', failed:'Protection check failed', local:'2IZI Guard',
        interactive_title:'Security check', interactive_instruction:'Hold until the security check is complete.',
        interactive_button:'Hold to verify', interactive_button_compact:'Hold',
        interactive_instruction_touch:'Touch and hold until the security check is complete.',
        interactive_button_touch:'Touch and hold', interactive_button_touch_compact:'Hold',
        interactive_instruction_keyboard:'Hold Space or Enter until the security check is complete.',
        interactive_button_keyboard:'Hold to verify', interactive_button_keyboard_compact:'Hold', interactive_done:'Checking…'
    },
    ru: {
        protected:'Защищено', checking:'Проверяем…', verified:'Проверено',
        challenge:'Проверка безопасности', failed:'Проверка защиты не пройдена', local:'2IZI Guard',
        interactive_title:'Проверка безопасности', interactive_instruction:'Удерживайте до завершения проверки безопасности.',
        interactive_button:'Удерживайте', interactive_button_compact:'Удерживайте',
        interactive_instruction_touch:'Коснитесь и удерживайте до завершения проверки безопасности.',
        interactive_button_touch:'Коснитесь и удерживайте', interactive_button_touch_compact:'Удерживайте',
        interactive_instruction_keyboard:'Удерживайте пробел или Enter до завершения проверки безопасности.',
        interactive_button_keyboard:'Удерживайте', interactive_button_keyboard_compact:'Удерживайте', interactive_done:'Проверяем…'
    },
    'zh-CN': {
        protected:'已保护', checking:'验证中…', verified:'已验证',
        challenge:'安全验证', failed:'安全验证未通过', local:'2IZI Guard',
        interactive_title:'安全验证', interactive_instruction:'请按住直到安全验证完成。',
        interactive_button:'按住验证', interactive_button_compact:'按住',
        interactive_instruction_touch:'请触摸并按住直到安全验证完成。',
        interactive_button_touch:'触摸并按住', interactive_button_touch_compact:'按住',
        interactive_instruction_keyboard:'请按住空格键或 Enter 键直到安全验证完成。',
        interactive_button_keyboard:'按住验证', interactive_button_keyboard_compact:'按住', interactive_done:'验证中…'
    },
    ja: {
        protected:'保護中', checking:'確認中…', verified:'確認済み',
        challenge:'セキュリティ確認', failed:'セキュリティ確認に失敗しました', local:'2IZI Guard',
        interactive_title:'セキュリティ確認', interactive_instruction:'セキュリティ確認が完了するまで押し続けてください。',
        interactive_button:'長押し', interactive_button_compact:'長押し',
        interactive_instruction_touch:'セキュリティ確認が完了するまでタッチして長押ししてください。',
        interactive_button_touch:'タッチして長押し', interactive_button_touch_compact:'長押し',
        interactive_instruction_keyboard:'確認が完了するまで Space または Enter を押し続けてください。',
        interactive_button_keyboard:'長押し', interactive_button_keyboard_compact:'長押し', interactive_done:'確認中…'
    },
    it: {
        protected:'Protetto', checking:'Verifica…', verified:'Verificato',
        challenge:'Verifica sicurezza', failed:'Controllo di sicurezza non riuscito', local:'2IZI Guard',
        interactive_title:'Verifica sicurezza', interactive_instruction:'Tieni premuto fino al termine della verifica di sicurezza.',
        interactive_button:'Tieni premuto', interactive_button_compact:'Tieni',
        interactive_instruction_touch:'Tocca e tieni premuto fino al termine della verifica di sicurezza.',
        interactive_button_touch:'Tocca e tieni premuto', interactive_button_touch_compact:'Tieni',
        interactive_instruction_keyboard:'Tieni premuto Spazio o Invio fino al termine della verifica.',
        interactive_button_keyboard:'Tieni premuto', interactive_button_keyboard_compact:'Tieni', interactive_done:'Verifica…'
    }
};

function clampInt(value, min, max, fallback) {
    const n = Number(value);
    return Number.isFinite(n) ? Math.max(min, Math.min(max, Math.round(n))) : fallback;
}
function normalizeBadgeMode(value) {
    return ['always','during','hidden'].includes(value) ? value : 'always';
}
function normalizeIsolation(value) {
    return ['shadow','scoped'].includes(value) ? value : 'shadow';
}
function localeTag() {
    const raw = (configuredLocale || document.documentElement.lang || navigator.language || 'en').replace('_','-').slice(0,32);
    const exact = Object.keys(builtin).find(key => key.toLowerCase() === raw.toLowerCase());
    if (exact) return exact;
    if (/^zh(?:-|$)/i.test(raw) && builtin['zh-CN']) return 'zh-CN';
    const baseLang = raw.split('-')[0].toLowerCase();
    const base = Object.keys(builtin).find(key => key.toLowerCase() === baseLang);
    return base || 'en';
}
function registerLocale(tag, messages) {
    const normalized=String(tag||'').replace('_','-').trim().slice(0,32);
    if(!/^[A-Za-z]{2,8}(?:-[A-Za-z0-9]{1,8})*$/.test(normalized)||!messages||typeof messages!=='object'||Array.isArray(messages)) throw new Error('invalid_locale');
    builtin[normalized]={...builtin.en,...messages};
    document.querySelectorAll('form[data-guard-action]').forEach(form=>{
        const node=form.__iziGuardBadgeNode || form.querySelector('.izi-guard-badge[data-izi-guard-badge]');
        if(node?.dataset.state==='protected') visualState(form,'protected');
    });
    return normalized;
}
function clientMessages(form=null) {
    const lang = localeTag();
    const msg = {...builtin.en, ...(builtin[lang] || {})};
    if (form && form.__iziGuardUi) Object.assign(msg, form.__iziGuardUi);
    const overrides = {
        protected:'guardLabelProtected', checking:'guardLabelChecking', verified:'guardLabelVerified',
        challenge:'guardLabelChallenge', failed:'guardLabelFailed', local:'guardLabelLocal'
    };
    if (form) for (const [key, dataKey] of Object.entries(overrides)) {
        if (form.dataset[dataKey]) msg[key] = form.dataset[dataKey];
    }
    return msg;
}
function preferredInteractionModality() {
    try {
        if ((navigator.maxTouchPoints || 0) > 0 && matchMedia('(pointer: coarse)').matches) return 'touch';
    } catch (_) {}
    return 'pointer';
}
function interactionCopy(ui, msg, modality) {
    const source = {...msg, ...(ui || {})};
    const suffix = modality === 'touch' ? '_touch' : (modality === 'keyboard' ? '_keyboard' : '');
    return {
        instruction: source[`interactive_instruction${suffix}`] || source.interactive_instruction,
        button: source[`interactive_button${suffix}`] || source.interactive_button,
        buttonCompact: source[`interactive_button${suffix}_compact`] || source.interactive_button_compact || source[`interactive_button${suffix}`] || source.interactive_button,
        done: source.interactive_done
    };
}
function shieldSvg() {
    return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 2.7 19 5.6v5.2c0 4.6-2.8 8.7-7 10.5-4.2-1.8-7-5.9-7-10.5V5.6L12 2.7Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path class="izi-guard-shield-check" d="m8.7 12.1 2.1 2.1 4.6-5" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>';
}
function checkSvg() {
    return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m4.8 12.6 4.3 4.3 10.1-10.1" fill="none" stroke="currentColor" stroke-width="2.35" stroke-linecap="round" stroke-linejoin="round"/></svg>';
}
function contextDirection(context) {
    const nearest=context?.closest?.('[dir]')?.getAttribute?.('dir');
    const doc=document.documentElement.getAttribute('dir');
    const dir=(nearest||doc||'auto').toLowerCase();
    return ['ltr','rtl','auto'].includes(dir)?dir:'auto';
}
function isolationMode(context) {
    return normalizeIsolation(context?.dataset?.guardIsolation || defaultIsolation);
}
function surfaceHostStyle(host, kind) {
    const set=(name,value)=>host.style.setProperty(name,value,'important');
    set('box-sizing','border-box');
    set('max-width','100%');
    set('margin','0');
    set('padding','0');
    set('border','0');
    set('background','transparent');
    set('isolation','isolate');
    set('contain','layout style paint');
    set('vertical-align','middle');
    set('min-width','0');
    set('min-inline-size','0');
    if(kind==='badge'){
        set('display','inline-block');
        set('width','auto');
        set('flex','0 0 auto');
    }else{
        set('display','block');
        set('width','100%');
        set('max-width','420px');
        set('flex','0 1 420px');
        set('align-self','stretch');
    }
}
function createSurface(slot, kind, context=null) {
    const host=document.createElement('izi-guard-surface');
    host.dataset.iziGuardSurface='1';
    host.dataset.kind=kind;
    host.setAttribute('dir',contextDirection(context));
    surfaceHostStyle(host,kind);
    let root=host;
    let shadow=false;
    if(isolationMode(context)==='shadow' && typeof host.attachShadow==='function'){
        const sr=host.attachShadow({mode:'open'});
        const link=document.createElement('link');
        link.rel='stylesheet'; link.href=cssUrl; link.dataset.iziGuardShadowCss='1';
        root=document.createElement('div');
        root.className='izi-guard-surface-root'; root.dataset.kind=kind;
        sr.append(link,root); shadow=true;
    }else{
        root=document.createElement('div');
        root.className='izi-guard-surface-root'; root.dataset.kind=kind;
        host.appendChild(root);
    }
    slot.appendChild(host);
    return {host,root,shadow};
}
function badgeMode(form) {
    return normalizeBadgeMode(form?.dataset.guardBadge || defaultBadgeMode);
}
function uiSlot(form) {
    return form?.querySelector('[data-guard-ui-slot]') || form;
}
function badgeSlot(form) {
    return form?.querySelector('[data-guard-badge-slot]') || form;
}
function ensureBadge(form) {
    if (!form || badgeMode(form) === 'hidden') return null;
    let node = form.__iziGuardBadgeNode || null;
    if (node?.isConnected) return node;
    const existing=form.querySelector('.izi-guard-badge[data-izi-guard-badge]');
    if(existing){ form.__iziGuardBadgeNode=existing; return existing; }
    const surface=createSurface(badgeSlot(form),'badge',form);
    node = document.createElement('div');
    node.className = 'izi-guard-badge';
    node.dataset.iziGuardBadge = '1';
    node.dataset.state = 'protected';
    node.setAttribute('role','status');
    node.setAttribute('aria-live','polite');
    node.innerHTML = `<span class="izi-guard-badge-mark">${shieldSvg()}</span><span class="izi-guard-badge-label"></span><span class="izi-guard-badge-brand">2IZI Guard</span><span class="izi-guard-badge-dot" aria-hidden="true"></span>`;
    surface.root.appendChild(node);
    form.__iziGuardBadgeNode=node;
    form.__iziGuardBadgeHost=surface.host;
    return node;
}
function visualState(form, state, customText='') {
    if (!form) return;
    const mode = badgeMode(form);
    if (mode === 'hidden') return;
    const node = ensureBadge(form);
    if (!node) return;
    const msg = clientMessages(form);
    const key = state === 'protected' ? 'protected' : state === 'checking' ? 'checking' : state === 'verified' ? 'verified' : state === 'challenge' ? 'challenge' : 'failed';
    const label = node.querySelector('.izi-guard-badge-label');
    const mark = node.querySelector('.izi-guard-badge-mark');
    const brand = node.querySelector('.izi-guard-badge-brand');
    const dot = node.querySelector('.izi-guard-badge-dot');
    if (label) label.textContent = customText || msg[key] || msg.protected;
    if (mark) mark.innerHTML = state === 'verified' ? checkSvg() : shieldSvg();
    if (brand) brand.hidden = state !== 'protected';
    if (dot) dot.hidden = state !== 'checking';
    node.dataset.state = state;
    const host=form.__iziGuardBadgeHost;
    const hidden=mode === 'during' && state === 'protected';
    node.hidden=hidden; if(host) host.hidden=hidden;
    if (state === 'verified' && mode === 'during') {
        clearTimeout(form.__iziGuardBadgeTimer);
        form.__iziGuardBadgeTimer = setTimeout(() => {
            if (node.isConnected && node.dataset.state === 'verified') { node.hidden = true; if(host) host.hidden=true; }
        }, clampInt(form.dataset.guardVerifiedMs, 0, 10000, defaultVerifiedMs));
    }
}
function statusNode(form) {
    let node=form.__iziGuardStatusNode || form.querySelector('.izi-guard-status[data-izi-guard-status]');
    if (!node) {
        const surface=createSurface(uiSlot(form),'status',form);
        node = document.createElement('div');
        node.className = 'izi-guard-status';
        node.dataset.iziGuardStatus = '1';
        node.setAttribute('role','alert');
        node.hidden = true;
        surface.root.appendChild(node);
        form.__iziGuardStatusNode=node; form.__iziGuardStatusHost=surface.host;
    }
    return node;
}
function setStatus(form,text) {
    if (!form) return;
    const node = statusNode(form);
    node.textContent = text || '';
    node.hidden = !text;
    if(form.__iziGuardStatusHost) form.__iziGuardStatusHost.hidden=!text;
}
function clearStatus(form) {
    if (!form) return;
    const node = form.__iziGuardStatusNode || form.querySelector('.izi-guard-status[data-izi-guard-status]');
    if (node) { node.textContent=''; node.hidden=true; }
    if(form.__iziGuardStatusHost) form.__iziGuardStatusHost.hidden=true;
}

async function post(path, body) {
    const response = await fetch(`${base}${path}`, {
        method:'POST', credentials:'same-origin',
        headers:{'Content-Type':'application/json','Accept':'application/json'},
        body:JSON.stringify(body), cache:'no-store'
    });
    const data = await response.json().catch(() => ({success:false,code:'invalid_response'}));
    if (!response.ok) {
        const error = new Error(data.code || `guard_http_${response.status}`);
        error.userMessage = data.message || '';
        throw error;
    }
    return data;
}
function signals(formBound=false) {
    return {
        js:true,
        page_age_ms:Math.max(0,Math.min(86400000,Math.round(performance.now()-pageStarted))),
        pointer:activity.pointer, keyboard:activity.keyboard, touch:activity.touch,
        visibility_changes:activity.visibilityChanges, form_bound:formBound
    };
}
function submissionId() {
    const bytes = new Uint8Array(18); crypto.getRandomValues(bytes);
    let raw=''; bytes.forEach(x => raw += String.fromCharCode(x));
    return 'sub_'+btoa(raw).replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,'');
}
function solvePow(challenge) {
    return new Promise((resolve,reject) => {
        const worker = new Worker(workerUrl);
        const end = () => worker.terminate();
        worker.onmessage = event => {
            end();
            event.data?.ok ? resolve(event.data) : reject(new Error(event.data?.error || 'pow_failed'));
        };
        worker.onerror = () => { end(); reject(new Error('pow_worker_failed')); };
        worker.postMessage({
            challengeId:challenge.challenge_id,
            salt:challenge.parameters.salt,
            difficulty:Number(challenge.parameters.difficulty)
        });
    });
}
function mountHp(form,name) {
    if (!form || !name) return null;
    const wrap=document.createElement('div'); wrap.className='izi-guard-hp-wrap'; wrap.setAttribute('aria-hidden','true');
    const input=document.createElement('input'); input.type='text'; input.name=name; input.tabIndex=-1; input.autocomplete='off'; input.setAttribute('aria-hidden','true');
    wrap.appendChild(input); form.appendChild(wrap); return {wrap,input,name};
}
function removeInteractive(form) {
    if (form?.__iziGuardInteractionCleanup) {
        try { form.__iziGuardInteractionCleanup(); } catch (_) {}
        delete form.__iziGuardInteractionCleanup;
    }
    if(form?.__iziGuardInteractiveHost){ form.__iziGuardInteractiveHost.remove(); delete form.__iziGuardInteractiveHost; }
    form?.querySelectorAll('.izi-guard-interactive[data-izi-guard-interactive]').forEach(node => node.remove());
}
function confirmInteraction(form, ui, minMs) {
    return new Promise(resolve => {
        if (!form) { setTimeout(() => resolve({confirmed:true}), minMs); return; }
        removeInteractive(form);
        form.__iziGuardUi = {...clientMessages(form), ...ui};
        const msg = clientMessages(form);
        const badge=ensureBadge(form); if(badge) badge.hidden=true; if(form.__iziGuardBadgeHost) form.__iziGuardBadgeHost.hidden=true;

        const surface=createSurface(uiSlot(form),'challenge',form);
        form.__iziGuardInteractiveHost=surface.host;
        const box=document.createElement('section');
        const uid=`izi-guard-${Math.random().toString(36).slice(2,10)}`;
        box.className='izi-guard-interactive'; box.dataset.iziGuardInteractive='1'; box.dataset.phase='hold';
        box.setAttribute('aria-labelledby',`${uid}-title`);
        const head=document.createElement('div'); head.className='izi-guard-interactive-head';
        const mark=document.createElement('span'); mark.className='izi-guard-interactive-mark'; mark.innerHTML=shieldSvg();
        const title=document.createElement('strong'); title.className='izi-guard-interactive-title'; title.id=`${uid}-title`; title.textContent=ui.interactive_title||msg.interactive_title;
        head.append(mark,title);
        const brandline=document.createElement('div'); brandline.className='izi-guard-interactive-brandline';
        const brand=document.createElement('span'); brand.className='izi-guard-interactive-brand'; brand.textContent='2IZI Guard'; brandline.appendChild(brand);
        const instruction=document.createElement('span'); instruction.className='izi-guard-sr-only'; instruction.id=`${uid}-instruction`;
        const btn=document.createElement('button'); btn.type='button'; btn.className='izi-guard-hold';
        btn.setAttribute('aria-describedby', instruction.id);
        const progress=document.createElement('span'); progress.className='izi-guard-hold-progress'; progress.setAttribute('aria-hidden','true');
        const labels=document.createElement('span'); labels.className='izi-guard-hold-labels';
        const btnText=document.createElement('span'); btnText.className='izi-guard-hold-label';
        const btnCompact=document.createElement('span'); btnCompact.className='izi-guard-hold-label izi-guard-hold-label-compact';
        labels.append(btnText,btnCompact);
        const btnValue=document.createElement('span'); btnValue.className='izi-guard-hold-value'; btnValue.setAttribute('aria-hidden','true'); btnValue.textContent='0%';
        btn.append(progress,labels,btnValue);
        const meter=document.createElement('progress'); meter.className='izi-guard-progress'; meter.max=minMs; meter.value=0;
        box.append(head,instruction,btn,brandline,meter); surface.root.appendChild(box);

        let modality=preferredInteractionModality();
        let start=0,raf=0,done=false,activePointerId=null,activeKey='';
        const aborter=new AbortController();
        const applyCopy=next=>{
            modality=next||modality;
            const copy=interactionCopy(ui,msg,modality);
            instruction.textContent=copy.instruction;
            btnText.textContent=copy.button;
            btnCompact.textContent=copy.buttonCompact;
            btn.setAttribute('aria-label',copy.instruction);
            meter.setAttribute('aria-label',copy.instruction);
            box.dataset.modality=modality;
        };
        const releasePointer=()=>{
            if(activePointerId!==null){
                try{if(btn.hasPointerCapture(activePointerId))btn.releasePointerCapture(activePointerId);}catch(_){}
            }
            activePointerId=null;
        };
        const reset=()=>{
            if(done)return;
            cancelAnimationFrame(raf); raf=0; start=0; activeKey='';
            releasePointer(); meter.value=0; btn.dataset.holding='0'; btn.style.setProperty('--izi-guard-hold-progress','0%'); btnValue.textContent='0%';
        };
        const cleanup=()=>{
            cancelAnimationFrame(raf); raf=0; releasePointer(); aborter.abort();
        };
        form.__iziGuardInteractionCleanup=cleanup;
        const complete=()=>{
            if(done)return;
            done=true; cancelAnimationFrame(raf); raf=0; releasePointer();
            meter.value=minMs; btn.style.setProperty('--izi-guard-hold-progress','100%'); btnValue.textContent='100%';
            btn.dataset.holding='0'; btn.disabled=true; btn.dataset.complete='1'; btn.hidden=true;
            box.dataset.phase='checking';
            title.textContent=(ui.interactive_done||msg.interactive_done||msg.checking);
            brand.hidden=true; brandline.hidden=true;
            mark.classList.add('izi-guard-interactive-mark-busy');
            cleanup(); delete form.__iziGuardInteractionCleanup;
            resolve({confirmed:true});
        };
        const tick=()=>{
            if(done||!start)return;
            const elapsed=performance.now()-start;
            const ratio=Math.min(1,elapsed/minMs);
            meter.value=Math.min(minMs,elapsed);
            const percent=Math.round(ratio*100);
            btn.style.setProperty('--izi-guard-hold-progress',`${percent}%`); btnValue.textContent=`${percent}%`;
            if(elapsed>=minMs){complete();return;}
            raf=requestAnimationFrame(tick);
        };
        const begin=(event,nextModality)=>{
            if(done||start)return;
            if(event?.type==='pointerdown'){
                if(event.isPrimary===false)return;
                if(event.pointerType==='mouse'&&event.button!==0)return;
                activePointerId=event.pointerId;
                try{btn.setPointerCapture(event.pointerId);}catch(_){}
            }
            event?.preventDefault?.();
            applyCopy(nextModality||modality); start=performance.now(); btn.dataset.holding='1'; raf=requestAnimationFrame(tick);
        };
        const outsideButton=e=>{
            const rect=btn.getBoundingClientRect(), slop=24;
            return e.clientX<rect.left-slop||e.clientX>rect.right+slop||e.clientY<rect.top-slop||e.clientY>rect.bottom+slop;
        };

        applyCopy(modality);
        btn.addEventListener('pointerdown',e=>begin(e,(e.pointerType==='touch'||e.pointerType==='pen')?'touch':'pointer'),{signal:aborter.signal});
        btn.addEventListener('pointermove',e=>{if(!done&&start&&e.pointerId===activePointerId&&outsideButton(e))reset();},{signal:aborter.signal});
        btn.addEventListener('pointerup',e=>{if(e.pointerId===activePointerId)reset();},{signal:aborter.signal});
        btn.addEventListener('pointercancel',e=>{if(e.pointerId===activePointerId)reset();},{signal:aborter.signal});
        btn.addEventListener('lostpointercapture',()=>{if(!done&&start)reset();},{signal:aborter.signal});
        btn.addEventListener('contextmenu',e=>e.preventDefault(),{signal:aborter.signal});
        btn.addEventListener('selectstart',e=>e.preventDefault(),{signal:aborter.signal});
        btn.addEventListener('dragstart',e=>e.preventDefault(),{signal:aborter.signal});
        btn.addEventListener('click',e=>e.preventDefault(),{signal:aborter.signal});
        btn.addEventListener('keydown',e=>{
            if((e.key===' '||e.key==='Enter')&&!e.repeat){activeKey=e.key;begin(e,'keyboard');}
        },{signal:aborter.signal});
        btn.addEventListener('keyup',e=>{if(e.key===activeKey)reset();},{signal:aborter.signal});
        btn.addEventListener('blur',reset,{signal:aborter.signal});
        addEventListener('blur',reset,{signal:aborter.signal});
        document.addEventListener('visibilitychange',()=>{if(document.hidden)reset();},{signal:aborter.signal});
    });
}

async function getToken(action, options={}) {
    if (!/^[a-z][a-z0-9_.:-]{0,63}$/.test(action)) throw new Error('invalid_action');
    const form = options.form instanceof HTMLFormElement ? options.form : null;
    clearStatus(form); visualState(form,'checking');
    const challenge = await post('/challenge.php',{action,version:'4',locale:localeTag(),signals:signals(Boolean(form))});
    const ui = challenge.ui || {};
    if (form) form.__iziGuardUi = {...clientMessages(form), ...ui};

    if (challenge.status === 'shadow_pass' && challenge.token) {
        visualState(form,'verified',clientMessages(form).verified);
        return challenge.token;
    }
    if (!['pow','pow_interactive'].includes(challenge.type)) throw new Error('unsupported_challenge');

    visualState(form,'checking',ui.working||ui.checking||clientMessages(form).checking);
    const hp=mountHp(form,challenge.parameters?.honeypot_name||'');
    try {
        const powPromise=solvePow(challenge);
        let interaction={};
        if (challenge.type==='pow_interactive') interaction=await confirmInteraction(form,ui,Number(challenge.parameters?.interactive_min_ms||1200));
        const solved=await powPromise;
        const result=await post('/verify.php',{
            challenge_id:challenge.challenge_id, locale:localeTag(), solution:{nonce:solved.nonce},
            honeypot:hp?{name:hp.name,value:hp.input.value||''}:{}, interaction
        });
        if(!result.success||!result.token) throw new Error(result.code||'verification_failed');
        if (form && result.ui) form.__iziGuardUi = {...clientMessages(form), ...result.ui};
        removeInteractive(form); clearStatus(form); visualState(form,'verified',result.ui?.verified||ui.verified||clientMessages(form).verified);
        return result.token;
    } finally {
        if(hp?.wrap?.isConnected) hp.wrap.remove();
    }
}

async function protectForm(form) {
    if(form.dataset.guardBound==='1') return;
    form.dataset.guardBound='1';
    if (badgeMode(form)==='always') visualState(form,'protected');
    form.addEventListener('submit',async event=>{
        if(form.dataset.guardSubmitting==='1') return;
        event.preventDefault();
        const action=form.dataset.guardAction, submitter=event.submitter||null;
        const buttons=form.querySelectorAll('button[type="submit"],input[type="submit"]');
        buttons.forEach(x=>x.disabled=true);
        try {
            const token=await getToken(action,{form});
            let input=form.querySelector('input[name="guard_token"]');
            if(!input){input=document.createElement('input');input.type='hidden';input.name='guard_token';form.appendChild(input);} input.value=token;
            let sub=form.querySelector('input[name="guard_submission_id"]');
            if(!sub){sub=document.createElement('input');sub.type='hidden';sub.name='guard_submission_id';form.appendChild(sub);} if(!sub.value)sub.value=submissionId();
            form.dataset.guardSubmitting='1'; buttons.forEach(x=>x.disabled=false);
            form.requestSubmit?form.requestSubmit(submitter||undefined):HTMLFormElement.prototype.submit.call(form);
        } catch(e) {
            buttons.forEach(x=>x.disabled=false);
            removeInteractive(form);
            visualState(form,'error',e?.userMessage||clientMessages(form).failed);
            form.dispatchEvent(new CustomEvent('izi-guard-error',{detail:{code:e?.message||'verification_failed'},bubbles:true}));
            if(form.dataset.guardFailOpen==='true'||shadowFailOpen){
                form.dataset.guardSubmitting='1';
                form.requestSubmit?form.requestSubmit(submitter||undefined):HTMLFormElement.prototype.submit.call(form);
                return;
            }
            const msg=e?.userMessage||(e?.message==='rate_limited'?(form.__iziGuardUi?.rate_limited||'Too many attempts. Please try again later.'):(form.__iziGuardUi?.failed||clientMessages(form).failed));
            setStatus(form,msg);
        }
    });
}
function bind(root=document) {
    root.querySelectorAll('form[data-guard-action]').forEach(protectForm);
}

function preview(container, options={}) {
    if (!(container instanceof Element)) throw new Error('preview_container_required');
    if (typeof container.__iziGuardPreviewCleanup === 'function') {
        try { container.__iziGuardPreviewCleanup(); } catch (_) {}
        delete container.__iziGuardPreviewCleanup;
    }
    const state=['protected','checking','verified','challenge','error'].includes(options.state)?options.state:'protected';
    const fake=document.createElement('form');
    fake.dataset.guardBadge=options.badge||'always';
    fake.dataset.guardIsolation=normalizeIsolation(options.isolation||defaultIsolation);
    // Preview forms must never be allowed to shrink-to-fit inside flex/grid hosts.
    // The production Guard surface also carries min-width/flex safeguards, but the
    // synthetic preview form is itself the flex/grid item and therefore needs an
    // explicit inline-size contract.
    fake.style.setProperty('display','block','important');
    fake.style.setProperty('box-sizing','border-box','important');
    fake.style.setProperty('width','100%','important');
    fake.style.setProperty('max-width','100%','important');
    fake.style.setProperty('min-width','0','important');
    fake.style.setProperty('min-inline-size','0','important');
    fake.style.setProperty('margin','0','important');
    fake.style.setProperty('padding','0','important');
    fake.style.setProperty('border','0','important');
    fake.style.setProperty('flex','1 1 100%','important');
    fake.style.setProperty('justify-self','stretch','important');
    fake.style.setProperty('align-self','stretch','important');
    if(options.locale&&builtin[options.locale]) fake.__iziGuardUi={...builtin[options.locale]};
    container.replaceChildren(fake);
    if(state!=='challenge'){
        const badge=ensureBadge(fake); if(badge) visualState(fake,state,options.label||'');
    }
    if(state==='challenge'){
        const msg=options.locale&&builtin[options.locale]?builtin[options.locale]:clientMessages(fake);
        const requestedModality=['touch','keyboard','pointer'].includes(options.modality)?options.modality:null;
        let modality=requestedModality||preferredInteractionModality();
        const holdMs=clampInt(options.holdMs,500,5000,1200);
        const resetAfterMs=clampInt(options.resetAfterMs,0,10000,2500);
        const surface=createSurface(fake,'challenge',fake);
        fake.__iziGuardInteractiveHost=surface.host;
        const box=document.createElement('div'); box.className='izi-guard-interactive'; box.dataset.modality=modality; box.dataset.phase='hold';
        const head=document.createElement('div'); head.className='izi-guard-interactive-head';
        const mark=document.createElement('span'); mark.className='izi-guard-interactive-mark'; mark.innerHTML=shieldSvg();
        const title=document.createElement('strong'); title.className='izi-guard-interactive-title'; title.textContent=msg.interactive_title;
        head.append(mark,title);
        const brandline=document.createElement('div'); brandline.className='izi-guard-interactive-brandline';
        const brand=document.createElement('span'); brand.className='izi-guard-interactive-brand'; brand.textContent='2IZI Guard'; brandline.appendChild(brand);
        const instruction=document.createElement('span'); instruction.className='izi-guard-sr-only';
        const hold=document.createElement('button'); hold.type='button'; hold.className='izi-guard-hold';
        const progress=document.createElement('span'); progress.className='izi-guard-hold-progress'; progress.setAttribute('aria-hidden','true');
        const labels=document.createElement('span'); labels.className='izi-guard-hold-labels';
        const label=document.createElement('span'); label.className='izi-guard-hold-label';
        const labelCompact=document.createElement('span'); labelCompact.className='izi-guard-hold-label izi-guard-hold-label-compact';
        labels.append(label,labelCompact);
        const value=document.createElement('span'); value.className='izi-guard-hold-value'; value.setAttribute('aria-hidden','true'); value.textContent='0%';
        hold.append(progress,labels,value); box.append(head,instruction,hold,brandline); surface.root.appendChild(box);

        let start=0, raf=0, done=false, activePointerId=null, activeKey='', resetTimer=0, phaseTimer=0;
        const aborter=new AbortController();
        const applyCopy=next=>{
            modality=next||modality;
            const copy=interactionCopy({},msg,modality);
            instruction.textContent=copy.instruction;
            label.textContent=done?(msg.interactive_done||'Verified'):copy.button;
            labelCompact.textContent=done?(msg.interactive_done||'Verified'):copy.buttonCompact;
            box.dataset.modality=modality;
            hold.setAttribute('aria-label',copy.instruction);
        };
        const releasePointer=()=>{
            if(activePointerId!==null){
                try{if(hold.hasPointerCapture(activePointerId))hold.releasePointerCapture(activePointerId);}catch(_){}
            }
            activePointerId=null;
        };
        const renderProgress=percent=>{
            const p=Math.max(0,Math.min(100,Math.round(percent)));
            hold.style.setProperty('--izi-guard-hold-progress',`${p}%`);
            value.textContent=`${p}%`;
        };
        const reset=()=>{
            if(done)return;
            cancelAnimationFrame(raf); raf=0; start=0; activeKey=''; releasePointer();
            hold.dataset.holding='0'; renderProgress(0);
        };
        const resetCompleted=()=>{
            clearTimeout(resetTimer); resetTimer=0; clearTimeout(phaseTimer); phaseTimer=0; done=false; start=0; activeKey='';
            hold.hidden=false; hold.disabled=false; hold.dataset.holding='0'; delete hold.dataset.complete; renderProgress(0);
            box.dataset.phase='hold'; brand.hidden=false; brandline.hidden=false; mark.innerHTML=shieldSvg(); mark.classList.remove('izi-guard-interactive-mark-busy','izi-guard-interactive-mark-verified');
            title.textContent=msg.interactive_title;
            applyCopy(requestedModality||preferredInteractionModality());
        };
        const cleanup=()=>{
            cancelAnimationFrame(raf); raf=0; clearTimeout(resetTimer); resetTimer=0; clearTimeout(phaseTimer); phaseTimer=0; releasePointer(); aborter.abort();
        };
        container.__iziGuardPreviewCleanup=cleanup;
        const complete=()=>{
            if(done)return;
            done=true; cancelAnimationFrame(raf); raf=0; releasePointer();
            hold.dataset.holding='0'; hold.dataset.complete='1'; hold.disabled=true; renderProgress(100); hold.hidden=true;
            box.dataset.phase='checking'; title.textContent=msg.checking||msg.interactive_done||'Checking…'; brand.hidden=true; brandline.hidden=true;
            mark.innerHTML=shieldSvg(); mark.classList.add('izi-guard-interactive-mark-busy');
            phaseTimer=setTimeout(()=>{
                phaseTimer=0; box.dataset.phase='verified'; mark.classList.remove('izi-guard-interactive-mark-busy'); mark.classList.add('izi-guard-interactive-mark-verified'); mark.innerHTML=checkSvg();
                title.textContent=msg.verified||'Verified';
                container.dispatchEvent(new CustomEvent('izi-guard-preview-complete',{detail:{modality,holdMs},bubbles:true}));
                if(resetAfterMs>0) resetTimer=setTimeout(resetCompleted,resetAfterMs);
            },350);
        };
        const tick=()=>{
            if(done||!start)return;
            const elapsed=performance.now()-start;
            renderProgress((elapsed/holdMs)*100);
            if(elapsed>=holdMs){complete();return;}
            raf=requestAnimationFrame(tick);
        };
        const begin=(event,nextModality)=>{
            if(done||start)return;
            if(event?.type==='pointerdown'){
                if(event.isPrimary===false)return;
                if(event.pointerType==='mouse'&&event.button!==0)return;
                activePointerId=event.pointerId;
                try{hold.setPointerCapture(event.pointerId);}catch(_){}
            }
            event?.preventDefault?.();
            applyCopy(nextModality||modality); start=performance.now(); hold.dataset.holding='1'; raf=requestAnimationFrame(tick);
        };
        const outside=e=>{
            const rect=hold.getBoundingClientRect(),slop=24;
            return e.clientX<rect.left-slop||e.clientX>rect.right+slop||e.clientY<rect.top-slop||e.clientY>rect.bottom+slop;
        };

        applyCopy(modality); renderProgress(0);
        hold.addEventListener('pointerdown',e=>begin(e,requestedModality||((e.pointerType==='touch'||e.pointerType==='pen')?'touch':'pointer')),{signal:aborter.signal});
        hold.addEventListener('pointermove',e=>{if(!done&&start&&e.pointerId===activePointerId&&outside(e))reset();},{signal:aborter.signal});
        hold.addEventListener('pointerup',e=>{if(e.pointerId===activePointerId)reset();},{signal:aborter.signal});
        hold.addEventListener('pointercancel',e=>{if(e.pointerId===activePointerId)reset();},{signal:aborter.signal});
        hold.addEventListener('lostpointercapture',()=>{if(!done&&start)reset();},{signal:aborter.signal});
        hold.addEventListener('contextmenu',e=>e.preventDefault(),{signal:aborter.signal});
        hold.addEventListener('selectstart',e=>e.preventDefault(),{signal:aborter.signal});
        hold.addEventListener('dragstart',e=>e.preventDefault(),{signal:aborter.signal});
        hold.addEventListener('click',e=>e.preventDefault(),{signal:aborter.signal});
        hold.addEventListener('keydown',e=>{
            if((e.key===' '||e.key==='Enter')&&!e.repeat){activeKey=e.key;begin(e,'keyboard');}
        },{signal:aborter.signal});
        hold.addEventListener('keyup',e=>{if(e.key===activeKey)reset();},{signal:aborter.signal});
        hold.addEventListener('blur',reset,{signal:aborter.signal});
        addEventListener('blur',reset,{signal:aborter.signal});
        document.addEventListener('visibilitychange',()=>{if(document.hidden)reset();},{signal:aborter.signal});
    }
    return fake;
}
function escapeHtml(value){return String(value??'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));}

window.IZIGuard=Object.freeze({getToken,bind,submissionId,preview,registerLocale,version:'0.4.10'});
document.readyState==='loading'?document.addEventListener('DOMContentLoaded',()=>bind()):bind();
})();
