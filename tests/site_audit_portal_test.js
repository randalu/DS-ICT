'use strict';
const test=require('node:test'),assert=require('node:assert/strict');
const {publicRouteAllowed}=require('../cli/site_audit');
const {analyzeDOM,placeholderKind,statusCertainty,pageKey,navigationRedirects,resourceFinding}=require('../cli/site_audit_checks');
const {robotsRules,robotsAllowed,crawl}=require('../cli/site_audit_crawl');
test('public scope excludes credentials, mutations, private routes and subdirectory escapes',()=>{
 const base='http://127.0.0.1:8119/island';
 for(const path of ['/admin/site_audit.php','/account.php','/account_plan.php','/install.php','/cli/run.php','/.env','/destination_save.php','/privacy_choices_save.php','/contact?token=secret','/../../admin','/track-event.php'])assert.equal(publicRouteAllowed(base+path,base),false,path);
 assert.equal(publicRouteAllowed('http://127.0.0.1:8119/elsewhere',base),false);
 assert.equal(publicRouteAllowed(base+'/destinations/sigiriya',base),true);
 assert.equal(publicRouteAllowed(base+'/blog?page=2',base),true);
 assert.equal(publicRouteAllowed(base+'/assets/js/site-audit.js?v=123',base),true);
});
test('placeholder registry and uncertain HTTP classifications',()=>{
 assert.equal(placeholderKind('/island/assets/images/placeholders/placeholder-destination.svg'),'confirmed');
 assert.equal(placeholderKind('/uploads/no-image.jpg'),'suspected');assert.equal(placeholderKind('/uploads/real.jpg'),null);
 for(const code of [0,403,429,503])assert.equal(statusCertainty(code),'inconclusive');
 assert.equal(statusCertainty(404),'confirmed');assert.equal(statusCertainty(410),'confirmed');
});
test('robots specific groups, allow precedence and wildcards',()=>{
 const rules=robotsRules('User-agent: *\nDisallow: /admin\nDisallow: /blocked*\nAllow: /blocked/open\n');
 assert.equal(robotsAllowed('https://example.test/blocked/secret',rules),false);
 assert.equal(robotsAllowed('https://example.test/blocked/open',rules),true);
 const specific=robotsRules('User-agent: *\nDisallow: /\nUser-agent: ICT-SiteAudit\nAllow: /\n');
 assert.equal(robotsAllowed('https://example.test/a',specific),true);
});

test('page keys merge only the site root and empty queries, preserving meaningful variants',()=>{
 const base='http://localhost/island';
 assert.equal(pageKey(base,base),pageKey(base+'/',base));
 assert.equal(pageKey(base+'/itineraries?',base),pageKey(base+'/itineraries',base));
 assert.notEqual(pageKey(base+'/blog?page=2',base),pageKey(base+'/blog',base));
 assert.notEqual(pageKey(base+'/route/',base),pageKey(base+'/route',base));
 assert.notEqual(pageKey(base+'/route?a=1&b=2',base),pageKey(base+'/route?b=2&a=1',base));
});

test('navigation distinguishes one redirect from a chain',()=>{
 const request={redirectedFrom:()=>null};const hop={redirectedFrom:()=>request};
 assert.equal(navigationRedirects(request),0);assert.equal(navigationRedirects(hop),1);
 assert.equal(navigationRedirects({redirectedFrom:()=>hop}),2);
});

test('worker reports actual external limit before any browser checks',async()=>{
 const events=[];
 const api={emit:event=>events.push(event),fetchSitemapUrls:async()=>({urls:[],issues:[]}),normalizeUrl:x=>x,publicRouteAllowed:()=>true,isPageCandidate:()=>true,fetchWithRedirects:async()=>({status:200,body:''})};
 await assert.rejects(crawl(api,{base:'http://localhost/island',maxPages:5,maxInternal:5,maxExternal:0},{launch:async()=>{throw new Error('fixture stop');}}),/fixture stop/);
 assert.equal(events[0].coverage.max_external,0);assert.equal(events[0].coverage.resources_not_checked,0);
});

test('excluded and uncertain resources cannot become high severity broken targets',()=>{
 assert.deepEqual(resourceFinding({status:0,error:'Excluded'},true),{type:'resource_not_checked',severity:'low',certainty:'not_checked'});
 for(const status of [0,403,429,503])assert.equal(resourceFinding({status}).severity,'low');
 assert.deepEqual(resourceFinding({status:404}),{type:'resource_failure',severity:'high',certainty:'confirmed'});
});
test('loaded placeholders, missing database assignments and decorative alt are independent',()=>{
 const dom={title:'Private tours',description:'A useful complete description of private Sri Lanka tours and planning.',h1s:['Tours'],headings:[1,2],canonicals:['https://example.test/'],robots:'',text:'A'.repeat(120),ogTitle:'Tours',ogDescription:'Description',ogImage:'/real.jpg',twitterCard:'summary',overflow:false,overflowElements:[],unlabelledInputs:[],emptyButtons:[],ttfb:50,
 images:[{src:'https://example.test/assets/images/placeholders/placeholder-tour.svg',complete:true,width:500,height:300,alt:null,decorative:false,location:'#hero'},{src:'https://example.test/icon.svg',complete:true,width:10,alt:null,decorative:true,location:'#icon'}],backgrounds:[],videos:[],schema:['{"@type":"Organization"}','bad']};
 const result=analyzeDOM(dom,'https://example.test/',{media:{hero_image:''}});
 assert.equal(result.issues.filter(x=>x.type==='images_missing_alt').length,1);
 for(const type of ['placeholder_image','missing_media_assignment','schema_required_field','malformed_jsonld'])assert.ok(result.issues.some(x=>x.type===type),type);
 assert.ok(!result.issues.some(x=>x.type==='broken_image'));assert.ok(result.passed_checks.includes('mobile_overflow'));
 dom.schema=[JSON.stringify({'@type':'Article',headline:'Travel',mainEntityOfPage:{'@type':'WebPage','@id':'https://example.test/'}})];
 assert.ok(!analyzeDOM(dom,'https://example.test/').issues.some(x=>x.type==='schema_required_field'));
 dom.schema=[JSON.stringify({'@type':'WebPage','@id':'https://example.test/',url:'https://example.test/'})];
 assert.ok(analyzeDOM(dom,'https://example.test/').issues.some(x=>x.type==='schema_required_field'));
});
