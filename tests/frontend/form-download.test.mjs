import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { parse } from '@vue/compiler-sfc';
import { ref, computed } from 'vue';
import { formDownloadUrl, downloadForm } from '../../resources/js/utils/formDownload.js';

test('form downloads use same-origin storage paths with spaces and preserve file extensions', () => {
  for (const extension of ['pdf', 'docx', 'xlsx']) {
    for (const url of [`FORM PP/form 01.${extension}`, `/storage/aktif/FORM%20PP/form%2001.${extension}`,
      `https://old.example/storage/aktif/FORM%20PP/form%2001.${extension}`]) {
      assert.equal(formDownloadUrl({ menu: { id_menu: '2' }, url }),
        `/storage/aktif/FORM%20PP/form%2001.${extension}?download=1`);
    }
  }
  assert.equal(formDownloadUrl({ menu: { id_menu: 1 }, url: 'Memo/test.pdf' }), null);
  assert.equal(formDownloadUrl({ menu: { id_menu: 2 }, url: null }), '');
});

test('opening a form downloads it without a viewer; announcements still open the viewer', () => {
  const { descriptor } = parse(readFileSync('resources/js/components/openModalPDF.vue', 'utf8'));
  const script = descriptor.scriptSetup.content.replace(/^import .*;?\r?$/gm, '');
  const events = [], downloads = [], errors = [];
  const run = new Function('ref', 'computed', 'onMounted', 'defineExpose', 'defineProps', 'defineEmits',
    'formDownloadUrl', 'downloadForm', 'Swal', script + '\nreturn { openModal };');
  const modal = run(ref, computed, () => {}, () => {}, () => ({}), () => (...args) => events.push(args),
    formDownloadUrl, url => downloads.push(url), { fire: error => errors.push(error) });
  modal.openModal({ menu: { id_menu: 2 }, url: 'FORM PP/form.pdf' });
  assert.deepEqual(downloads, ['/storage/aktif/FORM%20PP/form.pdf?download=1']);
  assert.deepEqual(events, [['update:showModal', false], ['update:selectedCard', null]]);
  events.length = 0;
  const memo = { menu: { id_menu: 1 }, url: 'Memo/memo.pdf' };
  modal.openModal(memo);
  assert.deepEqual(events, [['update:showModal', true], ['update:selectedCard', memo]]);
  modal.openModal({ menu: { id_menu: 2 } });
  assert.equal(errors.length, 1);
  assert.equal(downloads.length, 1);
});

test('download triggers a native browser attachment request and removes the temporary link', () => {
  const previous = globalThis.document;
  const calls = [];
  const link = { click: () => calls.push('click'), remove: () => calls.push('remove') };
  globalThis.document = { createElement: tag => { assert.equal(tag, 'a'); return link; },
    body: { appendChild: element => { assert.equal(element, link); calls.push('append'); } } };
  try {
    downloadForm('/storage/aktif/FORM/form.pdf?download=1');
    assert.equal(link.href, '/storage/aktif/FORM/form.pdf?download=1');
    assert.equal(link.download, '');
    assert.deepEqual(calls, ['append', 'click', 'remove']);
  } finally { globalThis.document = previous; }
});
