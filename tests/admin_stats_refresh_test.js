const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
test('visible statistics refresh at 60 seconds and preserve active edits/background tabs',()=>{
    let callback,delay,reloads=0;
    const document={hidden:false,activeElement:null};
    const window={setInterval(fn,ms){callback=fn;delay=ms;},location:{reload(){reloads++;}}};
    vm.runInNewContext(fs.readFileSync('assets/js/admin-stats-refresh.js','utf8'),{window,document});
    assert.equal(delay,60000);callback();assert.equal(reloads,1);
    document.hidden=true;callback();assert.equal(reloads,1);
    document.hidden=false;document.activeElement={tagName:'INPUT'};callback();assert.equal(reloads,1);
    document.activeElement={tagName:'BODY'};callback();assert.equal(reloads,2);
});
