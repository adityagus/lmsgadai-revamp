import test, { afterEach } from 'node:test';
import assert from 'node:assert/strict';
import { effectScope, ref, nextTick } from 'vue';
import { useQuery } from '@tanstack/vue-query';
import { queryClient, clearApiCache } from '../../resources/js/api/queryClient.ts';
import { defineQuery } from '../../resources/js/api/query.ts';
import { defineMutation } from '../../resources/js/api/mutation.ts';

// No five-minute cache eviction timers should keep the Node test worker alive.
queryClient.setDefaultOptions({
  ...queryClient.getDefaultOptions(),
  queries: { ...queryClient.getDefaultOptions().queries, gcTime: Infinity },
});
afterEach(clearApiCache);
const tick = () => new Promise(resolve => setImmediate(resolve));

test('concurrent reads deduplicate, pagination isolates keys, and edits cannot corrupt cached data', async () => {
  let calls = 0, resolve;
  const read = defineQuery('courses', 'test', ([page]) => {
    calls++;
    return new Promise(done => { resolve = () => done({ data: [{ id: page }] }); });
  });
  const first = read(1), duplicate = read(1);
  assert.equal(calls, 1);
  resolve();
  const [a, b] = await Promise.all([first, duplicate]);
  a.data[0].id = 99;
  assert.equal(b.data[0].id, 1);
  assert.equal(queryClient.getQueryData(['courses', 'test', 1]).data[0].id, 1);
  const second = read(2); resolve();
  assert.equal((await second).data[0].id, 2);
  assert.equal(calls, 2);
});

test('useQuery follows reactive arguments and mutation invalidation refreshes its active observer', async () => {
  const scope = effectScope(), page = ref(1);
  let revision = 0;
  const read = defineQuery('categories', 'test', async ([value]) => ({ page: value, revision }));
  const result = scope.run(() => useQuery(read.options(page), queryClient));
  try {
    await tick();
    assert.equal(result.data.value.page, 1);
    page.value = 2; await nextTick(); await tick();
    assert.equal(result.data.value.page, 2);
    const save = defineMutation('categories', 'save', async () => { revision++; });
    await save(); await tick();
    assert.equal(result.data.value.revision, 1);
  } finally { scope.stop(); }
});

test('failed writes propagate validation errors, do not retry or invalidate, and can be retried explicitly', async () => {
  let calls = 0;
  const failure = { response: { status: 422, data: { message: 'Tidak valid' } } };
  queryClient.setQueryData(['courses', 'test'], { id: 1 });
  const save = defineMutation('courses', 'test', async () => { if (++calls === 1) throw failure; return { success: true }; });
  await assert.rejects(save(), error => error === failure);
  assert.equal(calls, 1);
  assert.equal(queryClient.getQueryState(['courses', 'test']).isInvalidated, false);
  await save();
  assert.equal(calls, 2);
  assert.equal(queryClient.getQueryState(['courses', 'test']).isInvalidated, true);
});

test('session reset aborts old requests and discards private cached results', async () => {
  let signal, resolve;
  const read = defineQuery('auth', 'private', ([], requestSignal) => {
    signal = requestSignal;
    return new Promise(done => { resolve = done; });
  });
  const pending = read();
  const rejected = assert.rejects(pending);
  queryClient.setQueryData(['courses', 'private'], [{ id: 1 }]);
  clearApiCache();
  assert.equal(signal.aborted, true);
  resolve({ user: 'old-session' });
  await rejected;
  assert.equal(queryClient.getQueryCache().getAll().length, 0);
});

test('domain transports preserve filters, multipart, progress callbacks, blobs, quiz verbs, and fresh CSRF', async () => {
  let token = 'meta-token';
  globalThis.window = { location: { origin: 'http://localhost', href: 'http://localhost/' } };
  globalThis.document = { querySelector: selector => selector.includes('csrf-token') ? { content: token } : null };
  const { apiInstance, apiInstanceAuth } = await import('../../resources/js/utils/axios.ts');
  const { quizHttp } = await import('../../resources/js/api/quizHttp.ts');
  const requests = [];
  const adapter = async config => {
    requests.push(config);
    const data = config.url === '/csrf-token' ? { csrf_token: 'fresh-token' } : config.responseType === 'blob' ? new Blob(['excel']) : { ok: true };
    return { data, status: 200, statusText: 'OK', headers: {}, config };
  };
  apiInstance.defaults.adapter = adapter;
  apiInstanceAuth.defaults.adapter = adapter;
  quizHttp.defaults.adapter = adapter;
  const { searchContent } = await import('../../resources/js/services/queries/courseQuery.ts');
  const { updateContent } = await import('../../resources/js/services/mutations/contentMutation.ts');
  const { createAnnouncement } = await import('../../resources/js/services/mutations/announcementMutation.ts');
  const { exportDocumentReport } = await import('../../resources/js/services/mutations/reportMutation.ts');
  const { api } = await import('../../resources/js/pages/quiz/api.js');
  const { login, signOut } = await import('../../resources/js/services/mutations/authMutation.ts');
  await searchContent('a & b');
  assert.equal(requests.at(-1).params.q, 'a & b');
  const form = new FormData(); form.append('title', 'Materi');
  await updateContent(form, 9);
  assert.equal(requests.at(-1).url, '/contents/9');
  assert.equal(requests.at(-1).method, 'post');
  assert.equal(requests.at(-1).data.get('_method'), 'PUT');
  const progress = () => {};
  await createAnnouncement(form, progress);
  assert.equal(requests.at(-1).onUploadProgress, progress);
  const exported = await exportDocumentReport([{ id: 2 }]);
  assert.equal(await exported.data.text(), 'excel');
  assert.deepEqual(JSON.parse(requests.at(-1).data), { data: [{ id: 2 }] });
  await api('get', 'attempts/7');
  assert.equal(requests.at(-1).method, 'get');
  await api('post', '7/start');
  assert.equal(requests.at(-1).url, '7/start');
  assert.equal(requests.at(-1).method, 'post');
  token = 'rotated-token';
  await api('put', 'attempts/7/answers', { value: 'A' });
  assert.equal(requests.at(-1).headers['X-CSRF-TOKEN'], 'rotated-token');
  await login({ user: 'fixture', pass: 'fixture' });
  assert.equal(requests.at(-1).headers['X-CSRF-TOKEN'], 'fresh-token');
  assert.equal(queryClient.getQueryCache().getAll().length, 0);
  await signOut();
  assert.equal(requests.at(-1).url, '/logout');
});
