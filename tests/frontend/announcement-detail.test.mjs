import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { parse } from '@vue/compiler-sfc';
import { ref, computed, watch } from 'vue';

function setup(readDocuments, readAreas) {
  const { descriptor } = parse(readFileSync('resources/js/pages/pengumuman/detail.vue', 'utf8'));
  const script = descriptor.scriptSetup.content.replace(/^import .*;?\r?$/gm, '');
  let mounted;
  const run = new Function('ref', 'computed', 'watch', 'onMounted', 'useRoute', 'useRouter', 'usePage',
    'getDetailAnnouncement', 'getWilayah', 'getAnnouncementRegions', script + '\nreturn { cards, isLoading, loadPage, pageError, loadError, loadDocuments, loadAreas, areasError, areasLoading, memoTabs, auth, loadRemainingDocuments, remainingError, nextDocumentPage };');
  const state = run(ref, computed, watch, fn => { mounted = fn; }, () => ({ params: { id: '1' } }),
    () => ({}), () => ({ props: { auth: { role: 'sop' } } }), readDocuments, readAreas, async () => ({}));
  return { state, mounted };
}

test('documents stay behind the skeleton until region tabs are ready', async () => {
  let resolveAreas;
  const { state, mounted } = setup(async id => {
    assert.equal(id, '1');
    return { detail: { title: 'Memo' }, items: [{ id: 9, title: 'Document' }] };
  }, () => new Promise(resolve => { resolveAreas = resolve; }));
  const pending = mounted();
  await new Promise(resolve => setImmediate(resolve));
  assert.equal(state.isLoading.value, true);
  assert.equal(state.cards.value[0].id, 9);
  assert.equal(state.areasLoading.value, true);
  assert.equal(state.auth.value.role, 'sop');
  resolveAreas([{ kd_wilayah: '1', nm_wilayah: 'Jaya' }]);
  await pending;
  assert.equal(state.isLoading.value, false);
  assert.equal(state.pageError.value, '');
  assert.equal(state.cards.value.length, 1);
  assert.equal(state.memoTabs.value[1].kd_wilayah, '1');
});

test('tabs stay behind the skeleton until the last document page is ready', async () => {
  let resolveLast;
  const { state, mounted } = setup(async (id, page) => page === 1
    ? { detail: {}, items: [{ id: 1 }], next_page: 2 }
    : new Promise(resolve => { resolveLast = resolve; }), async () => [{ kd_wilayah: '1' }]);
  const pending = mounted();
  await new Promise(resolve => setImmediate(resolve));
  assert.equal(state.memoTabs.value.length, 2);
  assert.equal(state.isLoading.value, true);
  resolveLast({ items: [{ id: 2 }], next_page: null });
  await pending;
  assert.equal(state.isLoading.value, false);
  assert.equal(state.cards.value.length, 2);
});

test('region failure shows a page error and retry reveals documents and tabs together', async () => {
  let fail = true;
  const { state } = setup(async () => ({ detail: {}, items: [{ id: 1 }] }), async () => {
    if (fail) throw { code: 'ECONNABORTED' };
    return [{ kd_wilayah: '1' }];
  });
  await state.loadPage();
  assert.equal(state.isLoading.value, false);
  assert.match(state.pageError.value, /Tab wilayah/);
  fail = false;
  await state.loadPage();
  assert.equal(state.pageError.value, '');
  assert.equal(state.memoTabs.value.length, 2);
});

test('later page failure keeps earlier documents and retries from the failed page', async () => {
  let fail = true;
  const pages = [];
  const { state } = setup(async (id, page) => {
    pages.push(page);
    if (page === 2 && fail) throw { code: 'ECONNABORTED' };
    return { detail: {}, items: [{ id: page }], next_page: page === 1 ? 2 : null };
  }, async () => []);
  await state.loadDocuments();
  await new Promise(resolve => setImmediate(resolve));
  assert.deepEqual(state.cards.value.map(item => item.id), [1]);
  assert.equal(state.remainingError.value, true);
  assert.equal(state.nextDocumentPage.value, 2);
  fail = false;
  await state.loadRemainingDocuments();
  assert.deepEqual(pages, [1, 2, 2]);
  assert.deepEqual(state.cards.value.map(item => item.id), [1, 2]);
  assert.equal(state.nextDocumentPage.value, null);
});

test('document timeout exposes retry and a successful retry restores the list', async () => {
  let calls = 0;
  const { state } = setup(async () => {
    if (++calls === 1) throw { code: 'ECONNABORTED' };
    return { detail: { title: 'Memo' }, items: [{ id: 1 }] };
  }, async () => []);
  await state.loadPage();
  assert.match(state.loadError.value, /terlalu lama/);
  assert.equal(state.isLoading.value, false);
  await state.loadPage();
  assert.equal(state.loadError.value, '');
  assert.equal(state.cards.value.length, 1);
});
