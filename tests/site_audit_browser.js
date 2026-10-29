'use strict';
// Real Chromium against the isolated PHP/MySQL fixture. Provider requests are intercepted.
const {chromium}=require('playwright'),fs=require('node:fs'),assert=require('node:assert/strict');
const base='http://127.0.0.1:8146',out='output/playwright/site-audit';fs.mkdirSync(out,{recursive:true});
(async()=>{
 const browser=await chromium.launch({headless:true});const context=await browser.newContext({viewport:{width:1440,height:1000},acceptDownloads:true});const page=await context.newPage();const checks=[],errors=[],failures=[];
 const check=(ok,label)=>{assert.ok(ok,label);checks.push(label);};
 page.on('pageerror',error=>errors.push(error.message));page.on('response',response=>{if(response.status()>=400)failures.push(response.status()+' '+response.url());});
 try{
  const guest=await context.request.get(base+'/admin/site_audit_api.php?action=history');check(guest.status()===401,'Guest API denied');failures.length=0;
  await page.goto(base+'/__audit_fixture');await page.goto(base+'/admin/site_audit.php');
  await page.getByText('No audit runs yet.',{exact:false}).waitFor();check(await page.locator('h1').textContent()==='Site Audit','Authenticated portal rendered');
  await page.getByRole('button',{name:'Check host readiness',exact:true}).click();await page.getByText('Host preflight passed:',{exact:false}).waitFor({timeout:20000});check(true,'Chromium readiness checked from web worker');
  await page.getByRole('button',{name:'Run public site audit',exact:true}).click();
  await page.waitForFunction(()=>document.querySelector('#audit-progress').textContent.includes('done'),{},{timeout:90000});check(true,'Real worker completed and imported through polling');
  check((await page.locator('#audit-progress').textContent()).includes('findings'),'Progress and findings displayed');
  await page.screenshot({path:out+'/overview-desktop.png',fullPage:true});
  await page.getByRole('button',{name:'Issues',exact:true}).click();
  check(await page.locator('.audit-issue').count()>0,'Evidence cards rendered');
  check(await page.getByRole('link',{name:'Choose replacement image',exact:true}).count()>0,'Image findings link to Image Manager');
  await page.locator('[name="certainty"]').selectOption('inconclusive');await page.getByRole('button',{name:'Filter',exact:true}).click();
  await page.waitForFunction(()=>[...document.querySelectorAll('.audit-issue')].every(x=>x.textContent.includes('inconclusive')));check(await page.locator('.audit-issue').count()>0,'Blocked/rate-limited checks remain inconclusive');
  await page.locator('[name="certainty"]').selectOption('');await page.locator('[name="q"]').fill('missing_media_assignment');await page.getByRole('button',{name:'Filter',exact:true}).click();
  await page.waitForFunction(()=>document.querySelector('.audit-issue h3')?.textContent==='missing media assignment');const card=page.locator('.audit-issue').first();
  await card.locator('select').selectOption('ignored');await card.locator('textarea').fill('Synthetic reviewed exception');await card.getByRole('button',{name:'Save review state',exact:true}).click();await page.getByText('Review state saved.',{exact:false}).waitFor();check(true,'Ignored reason saved through protected API');
  await card.locator('input[type=checkbox]').check();await page.getByRole('button',{name:'Preview AI context for selected findings',exact:true}).click();
  await page.locator('#audit-context-preview pre').waitFor();const preview=await page.locator('#audit-context-preview pre').textContent();check(preview.includes('missing_media_assignment'),'Exact public evidence previewed before AI call');check(!preview.includes('password_hash'),'Context excludes credentials');
  let reviews=0;await page.route('**/admin/site_audit_api.php',async route=>{
   const request=route.request();if(request.method()!=='POST'||!request.postData().includes('name="action"\r\n\r\nreview'))return route.continue();reviews++;
   const issueId=JSON.parse(preview).findings[0].issue_id;
   await route.fulfill({json:{result:{recommendations:[{issue_id:issueId,priority:'medium',explanation:'Synthetic evidence review',action:'Choose an appropriate image',dependencies:[],verification:['Recheck the public route']}]},usage:{connected:true,today:[]}}});
  });
  await page.getByRole('button',{name:'Send this context for paid AI review',exact:true}).click();await page.getByText('AI review saved.',{exact:false}).waitFor();check(reviews===1,'Explicit AI submission runs once');check(await page.locator('#audit-review').isDisabled(),'Completed request cannot be replayed from preview');
  await page.unroute('**/admin/site_audit_api.php');
  await page.getByRole('button',{name:'Issues',exact:true}).click();
  await page.locator('[name="q"]').fill('missing_title');await page.getByRole('button',{name:'Filter',exact:true}).click();await page.waitForTimeout(150);
  // The fixture title is present. Use a supported title finding from the duplicate group.
  await page.locator('[name="q"]').fill('duplicate_title');await page.getByRole('button',{name:'Filter',exact:true}).click();await page.getByRole('button',{name:'Preview SEO fix',exact:true}).first().waitFor();
  page.on('dialog',dialog=>dialog.accept());
  await page.route('**/admin/seo_manager_api.php',async route=>{
   if(route.request().method()!=='POST'||!route.request().postData().includes('name="action"\r\n\r\ngenerate'))return route.continue();
   await route.fulfill({json:{result:{replacement:'Reviewed travel title',explanation:'Synthetic title preview',intent:'Travel planning',keywords:[],warnings:['Review before saving'],model:'mock',policy_version:'test',suggestion_id:501,sources:[]}}});
  });
  await page.getByRole('button',{name:'Preview SEO fix',exact:true}).first().click();await page.getByRole('dialog').waitFor();check((await page.getByRole('dialog').textContent()).includes('Current text'),'SEO before/after comparison shown');
  await page.getByRole('button',{name:'Accept suggestion',exact:true}).click();check(await page.locator('.audit-pending').count()===1,'Accept creates pending preview without saving');
  check((await page.locator('.audit-pending').textContent()).includes('saving updates this public page'),'Published-save effect shown');
  await page.getByRole('button',{name:'Save this selected field',exact:true}).click();await page.getByText('Selected field saved with revision history.',{exact:false}).waitFor();check(await page.locator('.audit-pending').count()===0,'Explicit selected-field save completed');
  await page.unroute('**/admin/seo_manager_api.php');
  await page.getByRole('button',{name:'Pages & Media',exact:true}).click();check(await page.locator('.audit-observation').count()>0,'Page/resource observations available');
  await page.locator('.audit-observation').first().locator('summary').click();check((await page.locator('.audit-observation').first().textContent()).includes('status'),'Resource evidence expandable');
  await page.getByRole('button',{name:'History',exact:true}).click();check(await page.locator('#audit-history button').count()>0,'Historical runs selectable');
  await page.getByRole('button',{name:'Overview',exact:true}).click();
  const downloadPromise=page.waitForEvent('download');await page.getByRole('link',{name:'Download JSON',exact:true}).click();const download=await downloadPromise;await download.saveAs(out+'/report.json');check(JSON.parse(fs.readFileSync(out+'/report.json')).issues.length>0,'Authenticated JSON export contains evidence');
  await page.setViewportSize({width:390,height:844});await page.screenshot({path:out+'/overview-mobile.png',fullPage:true});check(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'Mobile overview has no overflow');
  await page.getByRole('button',{name:'Issues',exact:true}).click();await page.screenshot({path:out+'/issues-mobile.png',fullPage:true});check(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'Mobile issue cards have no overflow');
  await page.getByRole('button',{name:'Pages & Media',exact:true}).click();check(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'Mobile observations fit viewport');
  await page.getByRole('button',{name:'AI Review',exact:true}).click();check(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'Mobile AI review fits viewport');
  check(errors.length===0,'No uncaught browser errors');check(failures.length===0,'No unexpected portal HTTP failures');
  fs.writeFileSync(out+'/browser-checks.json',JSON.stringify({passed:checks.length,checks,errors,failures},null,2));console.log(checks.length+' browser checks passed');
 }finally{await browser.close();}
})().catch(error=>{console.error(error);process.exitCode=1;});
