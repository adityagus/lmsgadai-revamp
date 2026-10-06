import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { parse } from '@vue/compiler-sfc';
import { ref, computed } from 'vue';

const script = parse(readFileSync('resources/js/pages/course-detail/CourseContentOrder.vue', 'utf8')).descriptor.scriptSetup.content.replace(/^import .*;?\r?$/gm, '');
function setup({ patch = async () => ({ data: { message: 'Tersimpan' } }), confirm = false } = {}) {
  const emitted = [], writes = [];
  let guard;
  const api = {
    get: async () => ({ data: { contents: [{ id: 1, title: 'Materi', type: 'text' }, { id: 2, title: 'Kuis', type: 'quiz' }, { id: 3, title: 'Penutup', type: 'pdf' }] } }),
    patch: async (url, payload) => { writes.push({ url, payload }); return patch(); },
  };
  const run = new Function('ref', 'computed', 'onMounted', 'onUnmounted', 'onBeforeRouteLeave', 'defineProps', 'defineEmits', 'getCourseContentOrder', 'saveCourseContentOrder', 'window', script + '\nreturn { load, move, save, close, contents, original, dirty, error, saving, opened };');
  const state = run(ref, computed, () => {}, () => {}, fn => { guard = fn; }, () => ({ courseId: 51 }), () => (...args) => emitted.push(args), async () => (await api.get()).data, async (id, payload) => (await api.patch(`/api/courses/${id}/content-order`, payload)).data, { confirm: () => confirm });
  return { ...state, emitted, writes, leave: () => guard() };
}

test('materials and quizzes share one saved order and boundary moves do nothing', async () => {
  const state = setup();
  await state.load();
  state.move(0, -1); state.move(2, 1);
  assert.equal(state.dirty.value, false);
  state.move(1, -1);
  assert.deepEqual(state.contents.value.map(item => item.id), [2, 1, 3]);
  assert.equal(state.dirty.value, true);
  await state.save();
  assert.deepEqual(state.writes, [{ url: '/api/courses/51/content-order', payload: { content_ids: [2, 1, 3], original_ids: [1, 2, 3] } }]);
  assert.deepEqual(state.emitted, [['saved']]);
  assert.equal(state.dirty.value, false);
});

test('failed saves retain edits and guards prevent losing unsaved order', async () => {
  const state = setup({ patch: async () => { throw { response: { data: { message: 'Daftar berubah' } } }; } });
  await state.load(); state.move(1, -1);
  assert.equal(state.leave(), false);
  state.close(); assert.equal(state.opened.value, true);
  await state.save();
  assert.equal(state.error.value, 'Daftar berubah');
  assert.equal(state.dirty.value, true);
  assert.equal(state.saving.value, false);
  assert.deepEqual(state.emitted, []);
  assert.deepEqual(state.original.value, [1, 2, 3]);
});

test('saving blocks duplicate writes and navigation until persistence completes', async () => {
  let resolve;
  const state = setup({ patch: () => new Promise(done => { resolve = done; }) });
  await state.load(); state.move(1, -1);
  const saved = state.save(); await state.save(); state.move(1, 1);
  assert.equal(state.writes.length, 1);
  assert.equal(state.leave(), false);
  assert.deepEqual(state.contents.value.map(item => item.id), [2, 1, 3]);
  resolve({ data: { message: 'Tersimpan' } }); await saved;
  assert.equal(state.leave(), true);
});
