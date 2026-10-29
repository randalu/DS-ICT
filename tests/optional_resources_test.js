const test=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const source=fs.readFileSync('assets/js/optional-resources.js','utf8');
function harness(allow,items=[['external_reviews','reviews'],['translation','translation'],['chat','chat']]) {
    let activations=[];
    const templates=items.map(([category,id])=>({getAttribute(name){return name==='data-optional-category'?category:id;},content:{cloneNode(){return {querySelectorAll(){return [];}};}},after(){activations.push(id);}}));
    const window={ICTPrivacy:{canLoad:allow}};
    const document={querySelectorAll(selector){return selector.startsWith('template')?templates:[];}};
    const context=vm.createContext({window,document});
    return {window,run(){vm.runInContext(source,context);},get activations(){return activations;}};
}
test('unchosen/refused categories activate nothing',()=>{
    const h=harness(()=>false); h.run(); assert.deepEqual(h.activations,[]);
});
test('one category grant never activates another',()=>{
    const h=harness(category=>category==='external_reviews'); h.run(); assert.deepEqual(h.activations,['reviews']);
});
test('duplicate module or template insertion activates each id once',()=>{
    const h=harness(()=>true,[['external_reviews','reviews'],['external_reviews','reviews'],['translation','translation']]);
    h.run(); h.run(); assert.deepEqual(h.activations,['reviews','translation']);
});
test('missing browser gate fails closed',()=>{
    const window={}; const document={querySelectorAll(){throw new Error('Should not inspect templates without the gate');}};
    vm.runInNewContext(source,{window,document}); assert.equal(window.ICTOptionalLoaded,undefined);
});
