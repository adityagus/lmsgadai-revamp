import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { parse } from '@vue/compiler-sfc';
import { ref, computed, watch } from 'vue';
import { courseContentType } from '../../resources/js/utils/courseContentType.mjs';

const script = parse(readFileSync('resources/js/pages/course-preview/index.vue', 'utf8')).descriptor.scriptSetup.content.replace(/^import .*;?\r?$/gm, '');
const tick = () => new Promise(resolve => setImmediate(resolve));
const empty = { items: [{ content_id: 1, completed_at: null }, { content_id: 2, completed_at: null }], completed_count: 0, total: 2, resume_content_id: 1, is_complete: false };
function setup({ progress = empty, complete = async () => empty, query = {}, routeName = 'content-preview', confirm = true, materials = [{ id: 1, type: 'text' }, { id: 2, type: 'pdf' }] } = {}) {
  const alerts = [];
  const visits = [];
  const run = new Function('ref', 'computed', 'watch', 'onMounted', 'onUnmounted', 'onBeforeRouteLeave', 'useRoute', 'useRouter', 'getCourseById', 'getCourseProgress', 'completeCourseContent', 'Swal', 'courseContentType', script + '\nreturn { fetchCourseData, markAsCompleted, goToContent, goBack, currentContentIndex, progress, savingProgress };');
  const state = run(ref, computed, watch, () => {}, () => {}, () => {}, () => ({ name: routeName, params: { id: 1 }, query }), () => ({ push: to => visits.push(to) }),
    async () => ({ course: { id: 1 }, contents: materials }),
    async () => progress, complete, { fire: options => { alerts.push(options); return Promise.resolve({ isConfirmed: confirm }); } }, courseContentType);
  return { ...state, alerts, visits };
}

test('course back targets student classes or manager detail and preserves unfinished-form confirmation', async () => {
  const student = setup();
  await student.fetchCourseData(); student.goBack();
  assert.deepEqual(student.visits, [{ name: 'studentCourses' }]);
  const manager = setup({ routeName: 'content-admin-preview' });
  await manager.fetchCourseData(); manager.goBack();
  assert.deepEqual(manager.visits, [{ name: 'courseDetail', params: { id: 1 } }]);
  for (const confirm of [false, true]) {
    const form = setup({ confirm, materials: [{ id: 1, type: 'google_form', content: 'https://docs.google.com/forms/d/example/viewform' }] });
    await form.fetchCourseData(); form.goBack(); await tick();
    assert.equal(form.alerts.length, 1);
    assert.deepEqual(form.visits, confirm ? [{ name: 'studentCourses' }] : []);
  }
});

test('student resumes active lesson and can reopen completed lessons without a disabled state', async () => {
  let writes = 0;
  const resumed = { ...empty, resume_content_id: 2, completed_count: 1, items: [{ content_id: 1, completed_at: '2026-09-28T00:00:00Z' }, empty.items[1]] };
  const state = setup({ progress: resumed, complete: async () => { writes++; } });
  await state.fetchCourseData();
  assert.equal(state.currentContentIndex.value, 1);
  state.goToContent(0);
  assert.equal(state.currentContentIndex.value, 0);
  assert.equal(writes, 0);
  state.goToContent(1);
  assert.equal(state.currentContentIndex.value, 1);
  const linked = setup({ progress: resumed, query: { content: '1' } });
  await linked.fetchCourseData();
  assert.equal(linked.currentContentIndex.value, 1);
});

test('reopening a completed lesson does not unlock unfinished lessons beyond the next step', async () => {
  const state = setup({
    materials: [1, 2, 3, 4].map(id => ({ id, type: 'text' })),
    progress: { items: [1, 2, 3, 4].map(id => ({ content_id: id, completed_at: [1, 4].includes(id) ? '2026-09-28T00:00:00Z' : null })) },
  });
  await state.fetchCourseData();
  state.goToContent(3);
  assert.equal(state.currentContentIndex.value, 3);
  state.goToContent(2);
  assert.equal(state.currentContentIndex.value, 3);
  state.goToContent(0);
  assert.equal(state.currentContentIndex.value, 0);
  state.goToContent(1);
  assert.equal(state.currentContentIndex.value, 1);
});

test('complete waits for persistence, blocks duplicate clicks, then checks and advances', async () => {
  let resolve, writes = 0;
  const state = setup({ complete: () => { writes++; return new Promise(done => { resolve = done; }); } });
  await state.fetchCourseData();
  state.markAsCompleted();
  state.markAsCompleted();
  state.goToContent(1);
  assert.equal(writes, 1);
  assert.equal(state.currentContentIndex.value, 0);
  assert.equal(state.progress.value.completed_count, 0);
  resolve({ ...empty, completed_count: 1, items: [{ content_id: 1, completed_at: '2026-09-28T00:00:00Z' }, empty.items[1]] });
  await tick();
  assert.equal(state.currentContentIndex.value, 1);
  assert.equal(state.progress.value.completed_count, 1);
  assert.equal(state.savingProgress.value, false);
});

test('failed save stays on the lesson and keeps the next lesson locked until retry succeeds', async () => {
  let fail = true;
  const state = setup({ complete: async () => {
    if (fail) throw new Error('Network failed');
    return { ...empty, completed_count: 1, items: [{ content_id: 1, completed_at: '2026-09-28T00:00:00Z' }, empty.items[1]] };
  } });
  await state.fetchCourseData();
  state.goToContent(1);
  state.markAsCompleted();
  await tick();
  assert.equal(state.currentContentIndex.value, 0);
  assert.equal(state.progress.value.completed_count, 0);
  assert.equal(state.alerts[0].icon, 'error');
  fail = false;
  state.markAsCompleted();
  await tick();
  assert.equal(state.currentContentIndex.value, 1);
  assert.equal(state.alerts.length, 1);
});

test('student cannot skip through sidebar, content query or an inconsistent resume hint', async () => {
    const state = setup({ query: { content: '2' }, progress: { ...empty, resume_content_id: 2 } });
    await state.fetchCourseData();
    assert.equal(state.currentContentIndex.value, 0);
    state.goToContent(1);
    assert.equal(state.currentContentIndex.value, 0);
});

test('completed student course can reopen any completed step', async () => {
  const state = setup({ query: { content: '1' }, progress: {
    ...empty, completed_count: 2, is_complete: true,
    items: empty.items.map(item => ({ ...item, completed_at: '2026-09-28T00:00:00Z' })),
  } });
  await state.fetchCourseData();
  assert.equal(state.currentContentIndex.value, 1);
  state.goToContent(0);
  assert.equal(state.currentContentIndex.value, 0);
});

test('manager preview remains separate from strict student lesson navigation', async () => {
  const state = setup({ routeName: 'content-admin-preview', query: { content: '2' } });
  await state.fetchCourseData();
  assert.equal(state.currentContentIndex.value, 1);
  state.goToContent(0);
  assert.equal(state.currentContentIndex.value, 0);
});
