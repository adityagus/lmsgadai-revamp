import test from 'node:test';
import assert from 'node:assert/strict';
import { primaryQuizAction, quizStepError, passingGradeError } from '../../resources/js/pages/quiz/flow.mjs';

test('active attempt takes priority over starting another quiz', () => {
    assert.deepEqual(primaryQuizAction({active_attempt:4,can_start:true}), {kind:'resume',label:'Lanjutkan Quiz',attempt:4});
});
test('available quiz has a single start action and retakes have a clear label', () => {
    assert.equal(primaryQuizAction({can_start:true,attempts:[]}).label,'Mulai Quiz');
    assert.equal(primaryQuizAction({can_start:true,attempts:[{id:1,status:'SUBMITTED'}]}).label,'Kerjakan Lagi');
});
test('completed quiz points to the latest result regardless of API ordering', () => {
    assert.equal(primaryQuizAction({attempts:[{id:2,attempt_number:1,status:'SUBMITTED'},{id:7,attempt_number:3,status:'AUTO_SUBMITTED'},{id:4,attempt_number:2,status:'SUBMITTED'}]}).attempt,7);
});
test('missing or scheduled quiz cannot be started', () => {
    assert.equal(primaryQuizAction(null).kind,'unavailable');
    assert.equal(primaryQuizAction({status:'Belum Dimulai',attempts:[]}).label,'Belum dimulai');
});
const valid = {title:'Quiz Produk Gadai',duration_minutes:45,passing_grade:75,available_from:'2026-09-23T10:00',available_until:'2026-09-24T10:00',question_count:3,question_ids:[1,2,3],class_ids:[],overrides:[]};
test('locked course quizzes cannot start or resume through the quiz list', () => {
    assert.deepEqual(primaryQuizAction({ sequence_locked: true, can_start: true, active_attempt: 7 }), { kind: 'unavailable', label: 'Selesaikan materi sebelumnya' });
});
test('passing grade uses the normalized 0 to 100 scale independently of weights', () => {
    for (const grade of [0, 0.31, 75.01, 80, 100]) assert.equal(passingGradeError(grade), '');
    for (const grade of [-1, 100.01, NaN, Infinity, '', null, undefined, 'invalid']) {
        assert.match(passingGradeError(grade), /antara 0 dan 100/);
    }
});
test('publish requires exactly one course even with class or individual assignments', () => {
    assert.equal(quizStepError(valid,0),'');
    assert.equal(quizStepError(valid,1),'');
    assert.match(quizStepError(valid,2),/satu course/);
    assert.match(quizStepError({...valid,overrides:[{student_id:1}],class_ids:[1]},2),/satu course/);
    assert.match(quizStepError({...valid,course_id:[1,2]},2),/satu course/);
    assert.equal(quizStepError({...valid,course_id:1},2),'');
});
test('publish explains a question deficit and invalid schedule', () => {
    assert.match(quizStepError({...valid,question_count:10,question_ids:[]},1),/10 soal.*0 soal/);
    assert.match(quizStepError({...valid,available_until:valid.available_from},0),/jadwal/);
});

test('quiz without dates still validates duration and can return to a scheduled quiz', () => {
    const form = {...valid, no_schedule:true, available_from:'', available_until:''};
    assert.equal(quizStepError(form, 0), '');
    assert.match(quizStepError({...form, duration_minutes:0}, 0), /Durasi/);
    assert.match(quizStepError({...form, no_schedule:false}, 0), /jadwal/);
});
