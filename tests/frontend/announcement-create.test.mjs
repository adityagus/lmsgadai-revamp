import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { parse } from '@vue/compiler-sfc';
import { ref, computed, watch } from 'vue';
import { AnnouncementInfoSchema } from '../../resources/js/utils/zodSchema.js';

test('announcement setup initializes without accessing fields before declaration', () => {
  const { descriptor } = parse(readFileSync('resources/js/pages/pengumuman/create.vue', 'utf8'));
  const script = descriptor.scriptSetup.content.replace(/^import .*;?\r?$/gm, '');
  const setup = new Function('ref', 'computed', 'watch', 'onMounted', 'useRoute', 'useRouter', 'CKEditor', 'ClassicEditor', 'useForm', 'toTypedSchema', 'AnnouncementInfoSchema', script + '\nreturn { name, stepLabels };');
  const state = setup(ref, computed, watch, () => {}, () => ({ params: {}, query: {} }), () => ({}), { component: {} }, {},
    () => ({ handleSubmit: () => () => {}, errors: ref({}), defineField: () => [ref(''), {}] }), x => x, AnnouncementInfoSchema);
  assert.equal(state.name.value, 'Pengumuman');
  assert.deepEqual(state.stepLabels.value, ['Pengumuman', 'Wilayah', 'Jabatan']);
});

test('announcement validation accepts short letter numbers and rejects empty text or audience', () => {
  const valid = { title: 'Informasi', no_surat: '01', submenu_id: '1', type: 'text', content: '<p>Isi pengumuman</p>', tgl_berlaku: '2026-09-28', regionals_id: ['0001'], kd_jabatan: ['01'] };
  assert.equal(AnnouncementInfoSchema.safeParse(valid).success, true);
  for (const patch of [{ content: '<p>&nbsp;</p>' }, { regionals_id: [] }, { kd_jabatan: [] }]) {
    assert.equal(AnnouncementInfoSchema.safeParse({ ...valid, ...patch }).success, false);
  }
});
