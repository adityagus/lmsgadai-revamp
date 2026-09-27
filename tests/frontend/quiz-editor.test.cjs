const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { JSDOM } = require('jsdom');
const { parse, compileScript } = require('@vue/compiler-sfc');
const babel = require('@babel/core');
const dom = new JSDOM('<html><body></body></html>', { url:'http://localhost/quiz/manage/new' });
for(const key of ['window','document','Element','HTMLElement','SVGElement','Node','sessionStorage']) global[key]=dom.window[key];
dom.window.HTMLDialogElement.prototype.showModal=function(){this.setAttribute('open','');};
dom.window.HTMLDialogElement.prototype.close=function(){this.removeAttribute('open');};
const vue=require('vue');
const delay=ms=>new Promise(resolve=>setTimeout(resolve,ms));
const flush=async()=>{await delay(0);await vue.nextTick();};
const fixture={id:12,title:'K3',description:'',course_id:1,duration_minutes:45,passing_grade:75,maximum_attempt:1,grading_method:'highest',question_count:1,available_from:'2026-09-23T09:00:00+07:00',available_until:'2026-10-23T09:00:00+07:00',status:'DRAFT',has_attempts:false,randomize_question:true,randomize_answer:false,show_score:true,show_correct_answer:false,show_wrong_answer:false,show_explanation:false,show_total_correct:true,show_passing_status:true,release_after_close:false,class_ids:[],question_ids:[1],overrides:[]};
const originalQuestion={id:1,bank_id:1,question:'Jaminan?',type:'multiple_choice',weight:1,category:'Gadai',difficulty:'easy',explanation:'Penjelasan',options:[{text:'Emas',is_correct:true},{text:'Buku',is_correct:false}]};

async function mountEditor({quiz=null,saveGate=null,props={}}={}) {
  sessionStorage.clear();document.body.innerHTML='<div id="app"></div>';
  const calls=[], routes=[];
  const metadata={user:{id:1},classes:[{id:1,name:'Gadai Batch 01',student_ids:[2]}],students:[{id:2,name:'Budi',username:'budi'}],courses:[{id:1,name:"Produk Gadai"},{id:2,name:"SOP"}],banks:[{id:1,name:'Bank utama'}]};
  const api=async(method,url,payload)=>{
    calls.push({method,url,payload:payload ? JSON.parse(JSON.stringify(payload)):undefined});
    if(url==='metadata')return structuredClone(metadata);
    if(url==='questions'&&method==='get')return [structuredClone(originalQuestion)];
    if(url==='questions'&&method==='post')return {id:2};
    if(method==='get'&&url.startsWith('manage/'))return structuredClone(quiz || fixture);
    if(method==='post'&&url==='manage'&&saveGate)await saveGate;
    return {id:12};
  };
  const flow=await import('../../resources/js/pages/quiz/flow.mjs');
  const authoring=await import('../../resources/js/pages/quiz/authoring.mjs');
  const router={useRoute:()=>({params:{id:quiz?'12':'new'},query:{}}),useRouter:()=>({replace:async route=>routes.push(route),push:async route=>routes.push(route)}),onBeforeRouteLeave:()=>{}};
  const apiModule={api,errorMessage:e=>e.message,localInput:s=>s?s.slice(0,16):'',isoDate:s=>s?new Date(s).toISOString():null,dateLabel:s=>s};
  const load=file=>{
    const filename=path.resolve('resources/js/pages/quiz',file);
    const {descriptor}=parse(fs.readFileSync(filename,'utf8'),{filename});
    const compiled=compileScript(descriptor,{id:file,inlineTemplate:true});
    const output=babel.transformSync(compiled.content,{configFile:false,babelrc:false,plugins:['@babel/plugin-transform-modules-commonjs']}).code;
    const module={exports:{}};
    const localRequire=name=>name==='vue'?vue:name==='vue-router'?router:name==='./api'?apiModule:name==='./flow.mjs'?flow:name==='./authoring.mjs'?authoring:name.endsWith('.css')?{}:load(name);
    new Function('require','module','exports',output)(localRequire,module,module.exports);
    return module.exports;
  };
  const app=vue.createApp(load('QuizEditor.vue').default,props);app.mount('#app');await flush();
  const findButton=text=>[...document.querySelectorAll('button')].find(b=>b.textContent.trim()===text);
  const inputFor=text=>[...document.querySelectorAll('label.field')].find(l=>l.textContent.trim().startsWith(text))?.querySelector('input,select,textarea');
  const set=async(input,value)=>{assert.ok(input,'Input exists');input.value=value;input.dispatchEvent(new dom.window.Event(input.tagName==='SELECT'?'change':'input',{bubbles:true}));await flush();};
  const click=async text=>{const button=findButton(text);assert.ok(button,`Button ${text}`);assert.equal(button.disabled,false);button.click();await flush();};
  return {app,calls,routes,set,click,inputFor,findButton};
}

test('editor uses plain labels and serializes autosave before publication', async()=>{
  let release;const gate=new Promise(resolve=>{release=resolve;});
  const ui=await mountEditor({saveGate:gate});
  try {
    assert.doesNotMatch(document.body.textContent,/sat-set|Assign To Class|Quick Create|Correct Answer|Questions/);
    await ui.set(ui.inputFor('Judul Quiz'),' K3 ');
    await delay(1000);
    await ui.set(ui.inputFor('Judul Quiz'),'SOP');
    await ui.set(ui.inputFor('Course'),'1');
    await ui.click('Pilih dari Bank Soal');document.querySelector('.quiz-bank-list input').click();await flush();await ui.click('Tambahkan 1 Soal');
    await ui.click('Terbitkan Quiz');await ui.click('Terbitkan');
    assert.equal(ui.calls.filter(c=>c.method==='post'&&c.url==='manage').length,1);
    release();await flush();await flush();
    const saves=ui.calls.filter(c=>['post','put'].includes(c.method)&&c.url.startsWith('manage'));
    assert.equal(saves.length,2);assert.equal(saves[1].url,'manage/12');assert.equal(saves[1].payload.title,'SOP');assert.equal(saves[1].payload.status,'PUBLISHED');
    assert.ok(ui.findButton('Simpan Perubahan'));assert.ok(!ui.findButton('Simpan Draft'));
  } finally {release();ui.app.unmount();}
});

test('used quiz keeps attempts editable and sends settings without changing questions', async()=>{
  const ui=await mountEditor({quiz:{...fixture,status:'PUBLISHED',has_attempts:true}});
  try {
    assert.equal(ui.inputFor('Judul Quiz').matches(':disabled'),true);
    assert.equal(ui.inputFor('Jumlah kesempatan mengerjakan').matches(':disabled'),false);
    await ui.set(ui.inputFor('Jumlah kesempatan mengerjakan'),'2');await ui.click('Simpan Perubahan');
    const save=ui.calls.find(c=>c.method==='patch');assert.equal(save.url,'manage/12/visibility');assert.equal(save.payload.maximum_attempt,2);assert.equal(save.payload.status,'PUBLISHED');assert.equal(save.payload.question_ids,undefined);
    assert.ok(!ui.findButton('Tambah Soal'));assert.ok(!ui.findButton('Terbitkan Quiz'));
  } finally {ui.app.unmount();}
});

test('draft question edits create a new version and preserve individual assignments', async()=>{
  const override={student_id:2,additional_minutes:15,maximum_attempt:3,available_from:null,available_until:null};
  const ui=await mountEditor({quiz:{...fixture,class_ids:[],overrides:[override]}});
  try {
    await ui.click('Edit Soal');await ui.set(ui.inputFor('Pertanyaan'),'Jaminan emas?');await ui.click('Simpan Soal');
    await ui.click('Simpan Draft');
    const question=ui.calls.find(c=>c.method==='post'&&c.url==='questions');assert.equal(question.payload.explanation,'Penjelasan');
    const save=ui.calls.findLast(c=>c.method==='put');assert.deepEqual(save.payload.question_ids,[2]);assert.equal(save.payload.overrides[0].maximum_attempt,3);
    assert.ok(!ui.calls.some(c=>c.method==='put'&&c.url==='questions/1'));
  } finally {ui.app.unmount();}
});

test('editing an unused published quiz does not autosave it as a draft', async()=>{
  const ui=await mountEditor({quiz:{...fixture,status:'PUBLISHED'}});
  try {
    await ui.set(ui.inputFor('Judul Quiz'),'UAS');await delay(1000);
    assert.equal(ui.calls.filter(c=>c.method!=='get').length,0);
    await ui.click('Simpan Perubahan');
    assert.equal(ui.calls.find(c=>c.method==='put').payload.status,'PUBLISHED');
  } finally {ui.app.unmount();}
});

test('course editor fixes the quiz to its parent course without a class selector', async()=>{
  const ui=await mountEditor({props:{courseId:1}});
  try {
    assert.equal(ui.inputFor('Course').value,'1');
    assert.equal(ui.inputFor('Course').matches(':disabled'),true);
    assert.equal(document.querySelector('.class-multiselect-box'),null);
    await ui.set(ui.inputFor('Judul Quiz'),'K3');await ui.click('Simpan & Gunakan Quiz');
    const save=ui.calls.find(c=>c.method==='post'&&c.url==='manage');
    assert.equal(save.payload.course_id,1);assert.deepEqual(save.payload.class_ids,[]);
  } finally {ui.app.unmount();}
});

test('enabling explanations reveals editable fields and saves their text with the quiz', async()=>{
  const ui=await mountEditor({quiz:fixture});
  try {
    const toggle=[...document.querySelectorAll('label.check')].find(l=>l.textContent.trim()==='Tampilkan penjelasan').querySelector('input');
    toggle.click();await flush();
    await ui.set(ui.inputFor('Pembahasan soal'),'Emas dapat dijadikan jaminan.');
    await ui.click('Simpan Draft');
    assert.equal(ui.calls.find(c=>c.method==='post'&&c.url==='questions').payload.explanation,'Emas dapat dijadikan jaminan.');
    const saved=ui.calls.findLast(c=>c.method==='put');
    assert.equal(saved.payload.show_explanation,true);assert.deepEqual(saved.payload.question_ids,[2]);
    assert.ok(document.querySelector('.quick-choice-item.correct'));
    assert.ok(document.querySelector('.quick-choice-item.incorrect'));
    await ui.click('Edit Soal');
    const field=document.querySelector('.quiz-author-dialog textarea[placeholder="Jelaskan alasan jawaban yang benar."]');
    assert.ok(field);assert.equal(field.closest('details'),null);
  } finally {ui.app.unmount();}
});

test('used quiz allows editing its explanation even when student explanations are hidden', async()=>{
  const ui=await mountEditor({quiz:{...fixture,status:'PUBLISHED',has_attempts:true,show_explanation:false,question_explanations:{1:'Pembahasan khusus quiz'}}});
  try {
    const input=ui.inputFor('Pembahasan soal');
    assert.equal(input.readOnly,false);assert.equal(input.value,'Pembahasan khusus quiz');
    await ui.set(input,'Pembahasan yang direvisi');await ui.click('Simpan Pembahasan');
    const save=ui.calls.find(c=>c.url==='manage/12/questions/1/explanation');
    assert.equal(save.method,'patch');assert.deepEqual(save.payload,{explanation:'Pembahasan yang direvisi'});
    assert.ok(!ui.calls.some(c=>c.method==='post'&&c.url==='questions'));
    assert.ok(!ui.findButton('Edit Soal'));
  } finally {ui.app.unmount();}
});
