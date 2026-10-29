'use strict';
const test = require('node:test'), assert = require('node:assert/strict');
const {analyzeDOM} = require('../cli/site_audit_checks');
test('database package field findings retain exact source and coverage image scope', () => {
    const dom = {title:'Private tours',description:'A useful complete description of private Sri Lanka tours and planning.',h1s:['Tours'],headings:[1,2],canonicals:['https://example.test/'],robots:'',text:'A'.repeat(120),ogTitle:'Tours',ogDescription:'Description',ogImage:'/real.jpg',twitterCard:'summary',overflow:false,overflowElements:[],unlabelledInputs:[],emptyButtons:[],ttfb:50,images:[],backgrounds:[],videos:[],schema:[]};
    const result = analyzeDOM(dom, 'https://example.test/', {media:{cover_image:''}, child_media:{'coverage:1':''},field_issues:[{type:'images_missing_alt',field:'cover_alt',detail:'Assigned cover description is empty.'}]});
    assert.ok(result.issues.some(x => x.type === 'images_missing_alt' && x.source_location === 'database:cover_alt'));
    assert.ok(result.issues.some(x => x.type === 'missing_media_assignment' && x.source_location === 'database:coverage:1'));
});
