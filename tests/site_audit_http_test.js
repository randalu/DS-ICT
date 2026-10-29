'use strict';
const {chromium}=require('playwright'),assert=require('node:assert/strict'),fs=require('node:fs');
const base='http://127.0.0.1:8146',url=base+'/admin/site_audit_api.php';
(async()=>{
 const browser=await chromium.launch();let context=await browser.newContext();let page=await context.newPage();const checks=[];
 const check=(ok,label)=>{assert.ok(ok,label);checks.push(label);};
 try{
  await page.goto(base+'/__audit_fixture');await page.goto(base+'/admin/site_audit.php');const csrf=await page.locator('#site-audit').getAttribute('data-csrf');
  const post=(action,payload={},token=csrf)=>context.request.post(url,{multipart:{action,payload:JSON.stringify(payload),_token:token}});
  check((await context.request.fetch(url,{method:'PUT'})).status()===405,'Unsafe method rejected');
  check((await post('triage',{},'invalid')).status()===419,'CSRF rejected before writes');
  check((await post('triage',{reason:'x'.repeat(60000)})).status()===413,'Oversized request rejected');
  check((await post('triage',{fingerprint:'invalid',state:'ignored',reason:''})).status()===422,'Invalid triage rejected');
  check((await post('context',{run_id:1,ids:[99999999]})).status()===422,'Foreign selected finding rejected');
  const report=await (await context.request.get(url+'?action=report&run_id=1')).json();const id=Number(report.issues[0].id);
  const preview=await (await post('context',{run_id:1,ids:[id]})).json();
  check((await post('review',{run_id:1,ids:[id],context_hash:'0'.repeat(64),request_token:'synthetic-http-request-0001'})).status()===409,'Changed context rejected before provider');
  check((await post('review',{run_id:1,ids:[id],context_hash:preview.hash,request_token:'synthetic-http-request-0002'})).status()===422,'Disconnected key prevents paid request');
  const csv=await context.request.get(url+'?action=export&run_id=1&format=csv');check(csv.status()===200&&(await csv.text()).includes('triage_state'),'CSV download works');
  check((await context.request.get(base+'/admin/site_audit_run.php')).status()===405,'Crawler launch requires POST');
  check((await context.request.post(base+'/admin/site_audit_run.php',{multipart:{_token:'bad'}})).status()===419,'Crawler launch requires CSRF');
  const started=await context.request.post(base+'/admin/site_audit_run.php',{multipart:{_token:csrf}});const run=await started.json();check(started.status()===200&&run.started,'Second technical audit started');
  const duplicate=await context.request.post(base+'/admin/site_audit_run.php',{multipart:{_token:csrf}});check(duplicate.status()===409,'Duplicate run blocked');
  await context.close();context=await browser.newContext();page=await context.newPage();await page.goto(base+'/__audit_fixture');await page.goto(base+'/admin/site_audit.php');const token=await page.locator('#site-audit').getAttribute('data-csrf');
  let done=false;for(let attempt=0;attempt<50;attempt++){
   const response=await context.request.post(base+'/admin/site_audit_results.php',{multipart:{_token:token,run_id:String(run.run_id)}});const data=await response.json();if(data.status==='done'){done=true;break;}await new Promise(resolve=>setTimeout(resolve,500));
  }
  check(done,'Crawler survives browser closure and results import in a new session');
  check((await context.request.post(base+'/admin/site_audit_results.php',{multipart:{_token:token,run_id:'9999999'}})).status()===404,'Unknown progress run returns 404');
  fs.writeFileSync('output/playwright/site-audit/http-checks.json',JSON.stringify({passed:checks.length,checks},null,2));console.log(checks.length+' HTTP/lifecycle checks passed');
 }finally{await browser.close();}
})().catch(error=>{console.error(error);process.exitCode=1;});
