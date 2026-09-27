import test from 'node:test';
import assert from 'node:assert/strict';
import { createSaveQueue, parseBulkQuestions, questionPayload } from '../../resources/js/pages/quiz/authoring.mjs';
import { quizStepError } from '../../resources/js/pages/quiz/flow.mjs';

test('short quiz titles are accepted but whitespace and long titles are not', () => {
  const form={duration_minutes:45,passing_grade:75,available_from:'2026-09-23T09:00',available_until:'2026-09-24T09:00'};
  for(const title of [' K3 ','SOP','UAS','Quiz 1']) assert.equal(quizStepError({...form,title},0),'');
  assert.match(quizStepError({...form,title:'   '},0),/wajib/);
  assert.match(quizStepError({...form,title:'x'.repeat(151)},0),/150/);
});
test('publication waits for a pending draft and uses its new id', async () => {
  const queue=createSaveQueue(); let release, id=null; const events=[];
  const pending=new Promise(resolve=>{release=resolve;});
  const draft=queue(async()=>{events.push('create draft');await pending;id=42;});
  const published=queue(async()=>{assert.equal(id,42);events.push('publish');});
  await Promise.resolve();assert.deepEqual(events,['create draft']);
  release();await Promise.all([draft,published]);assert.deepEqual(events,['create draft','publish']);
});
test('failed save does not prevent retry', async () => {
  const queue=createSaveQueue();
  await assert.rejects(queue(()=>{throw new Error('offline');}),/offline/);
  assert.equal(await queue(()=>42),42);
});
test('bulk input accepts numbered questions without empty lines and previews exact keys', () => {
  const result=parseBulkQuestions('1. Jaminan?\nA. Emas\nB. Buku\nANSWER: A\n2. SOP?\nA. Satu\nB. Dua\nKUNCI: B');
  assert.deepEqual(result.errors,[]);assert.equal(result.questions.length,2);
  assert.equal(result.questions[1].options[1].is_correct,true);
});
test('bulk import never silently invents a key or skips invalid questions', () => {
  for(const key of ['','KUNCI: Z','KUNCI: A\nKUNCI: B']) assert.ok(parseBulkQuestions('1. Soal\nA. Satu\nB. Dua\n'+key).errors.length);
  assert.ok(parseBulkQuestions('1. Soal\nA. Satu\nA. Dua\nKUNCI: A').errors.length);
  assert.ok(parseBulkQuestions('1. Soal\nA. Satu\nB. Dua\nbaris salah\nKUNCI: A').errors.length);
});
test('question edits preserve type, explanations and bank without reusing answer option ids', () => {
  const result=questionPayload({bank_id:9,question:' Soal ',type:'multiple_answer',weight:2,explanation:'Pembahasan',options:[{id:1,text:' A ',is_correct:true},{id:2,text:'B',is_correct:true}]},3);
  assert.equal(result.bank_id,9);assert.equal(result.course_id,3);assert.equal(result.question,'Soal');assert.equal(result.explanation,'Pembahasan');
  assert.deepEqual(result.options,[{text:'A',is_correct:true},{text:'B',is_correct:true}]);
});
