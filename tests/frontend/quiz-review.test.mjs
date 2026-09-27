import test from 'node:test';
import assert from 'node:assert/strict';
import { reviewOptionState } from '../../resources/js/pages/quiz/review.mjs';

test('review marks the correct key green and a selected wrong answer red', () => {
  const q={selected_options:[2]};
  assert.equal(reviewOptionState(q,{id:1,is_correct:true}),'correct');
  assert.equal(reviewOptionState(q,{id:2,is_correct:false}),'incorrect');
  assert.equal(reviewOptionState(q,{id:3,is_correct:false}),'');
});
test('hidden keys remain neutral and multiple answers use individual option feedback', () => {
  const q={selected_options:[1,2]};
  assert.equal(reviewOptionState(q,{id:1}),'');
  assert.equal(reviewOptionState(q,{id:1,answer_status:'correct'}),'correct');
  assert.equal(reviewOptionState(q,{id:2,answer_status:'incorrect'}),'incorrect');
  assert.equal(reviewOptionState(q,{id:3}),'');
});
