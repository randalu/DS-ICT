const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('assets/js/privacy-controls.js', 'utf8');
function rehearsal(overrides = {}, response = null) {
    const listeners = {}, documentListeners = {};
    let reloads = 0;
    const config = {state: {chosen:true,analytics:true,expires_at:2000,choice_token:'current'},revision:1,pageGroup:'contact',optionalContext:true,firstPartyEnabled:true,gtmEnabled:false,cookiePath:'/',choiceUrl:'/privacy_choices.php',...overrides};
    const window = {ICTPrivacy:config,dataLayer:[{event:'pending'}],location:{href:'http://localhost/contact',origin:'http://localhost',reload(){reloads++;}},addEventListener(name,fn){listeners[name]=fn;}};
    const document = {hidden:false,getElementById(){return null;},querySelectorAll(){return [];},addEventListener(name,fn){documentListeners[name]=fn;}};
    let interval;
    const context = {window,document,URL,Date:{now:()=>1000000},setInterval(fn){interval=fn;},fetch:async()=>response ?? {ok:true,json:async()=>({...config,state:{...config.state}})}};
    vm.runInNewContext(source,context);
    return {config,window,listeners,documentListeners,expire(){config.state.expires_at=999; interval();},get reloads(){return reloads;}};
}
test('valid public grant allows first party independently of GTM',()=>{
    const r=rehearsal(); assert.equal(r.config.canTrack('first_party'),true); assert.equal(r.config.canTrack('gtm'),false);
    r.config.gtmEnabled=true; assert.equal(r.config.canTrack('gtm'),true);
});
test('first party counts through refusal and expiry while GTM remains denied',()=>{
    for (const state of [{chosen:true,analytics:false,expires_at:2000},{chosen:true,analytics:true,expires_at:999},{chosen:false,analytics:true,expires_at:2000}]) {
        const r=rehearsal({state,gtmEnabled:true});
        assert.equal(r.config.canTrack('first_party'),true); assert.equal(r.config.canTrack('gtm'),false);
    }
    for(const overrides of [{pageGroup:''},{firstPartyEnabled:false}]) assert.equal(rehearsal(overrides).config.canTrack('first_party'),false);
});

test('cross-tab signal pauses Google events while first party continues',async()=>{
    const r=rehearsal({}, {ok:true,json:async()=>({state:{choice_token:'new'},revision:1})});
    r.listeners.storage({key:'ict_analytics_choice_changed:/'});
    assert.equal(r.config.canTrack('first_party'),true); assert.equal(r.window.dataLayer.length,0);
    await new Promise(resolve=>setImmediate(resolve)); assert.equal(r.reloads,1);
});
test('failed optional refresh keeps first party active',async()=>{
    const r=rehearsal({}, {ok:false}); await r.listeners.focus();
    assert.equal(r.config.canTrack('first_party'),true); assert.equal(r.reloads,0);
});
test('expiry refreshes optional choices without stopping first party',()=>{
    const r=rehearsal(); r.expire(); assert.equal(r.config.canTrack('first_party'),true); assert.equal(r.reloads,1);
});
test('optional categories require their own available public grant',()=>{
    const state={chosen:true,analytics:false,external_reviews:true,translation:false,chat:false,expires_at:2000,choice_token:'current'};
    const r=rehearsal({state,availableCategories:{external_reviews:true,translation:true,chat:false}});
    assert.equal(r.config.canLoad('external_reviews'),true); assert.equal(r.config.canLoad('translation'),false);
    assert.equal(r.config.canLoad('chat'),false); assert.equal(r.config.canTrack('first_party'),true);
    assert.equal(r.config.canLoad('unknown'),false);
    r.config.optionalContext=false; assert.equal(r.config.canLoad('external_reviews'),false);
});
test('failed refresh and a cross-tab change stop optional activation',async()=>{
    const r=rehearsal({state:{chosen:true,external_reviews:true,expires_at:2000},availableCategories:{external_reviews:true}}, {ok:false});
    assert.equal(r.config.canLoad('external_reviews'),true);
    await r.listeners.focus(); assert.equal(r.config.canLoad('external_reviews'),false);
});
