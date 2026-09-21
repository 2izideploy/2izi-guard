const http=require('http'),fs=require('fs'),path=require('path');
const root=path.resolve(__dirname,'../..');
const page=`<!doctype html><html lang="en"><head><meta charset="utf-8"><title>2IZI Guard E2E</title></head><body><form id="f" data-guard-action="contact"><input name="x" value="test"><button type="submit">Send</button></form><script src="/guard/public/assets/guard.js" data-guard-base="/guard/public" data-guard-isolation="shadow"></script><script>window.addEventListener('DOMContentLoaded',()=>{const f=document.getElementById('f');f.addEventListener('submit',e=>{if(f.dataset.guardSubmitting==='1'){e.preventDefault();document.body.dataset.e2e='passed';document.body.dataset.token=f.querySelector('[name=guard_token]')?.value||'';}});setTimeout(()=>f.requestSubmit(),50);});</script></body></html>`;
function json(res,v){res.statusCode=200;res.setHeader('content-type','application/json; charset=utf-8');res.end(JSON.stringify(v));}
http.createServer((req,res)=>{
 if(req.url==='/'){res.setHeader('content-type','text/html; charset=utf-8');return res.end(page)}
 if(req.url.startsWith('/guard/public/assets/')){const rel=req.url.replace('/guard/public/','');const f=path.join(root,'public',rel);if(!f.startsWith(path.join(root,'public'))||!fs.existsSync(f)){res.statusCode=404;return res.end('not found')}res.setHeader('content-type',req.url.endsWith('.js')?'application/javascript; charset=utf-8':'text/css; charset=utf-8');return fs.createReadStream(f).pipe(res)}
 if(req.url==='/guard/public/challenge.php')return json(res,{status:'challenge',challenge_id:'ch_e2e',type:'pow',expires_in:60,ui:{working:'Checking',verified:'Verified'},parameters:{salt:'salt',difficulty:1,honeypot_name:'g_hp_e2e'}});
 if(req.url==='/guard/public/verify.php')return json(res,{success:true,token:'gt_e2e_token',expires_in:120,ui:{verified:'Verified'}});
 res.statusCode=404;res.end('not found');
}).listen(18765,'127.0.0.1',()=>console.log('ready'));
