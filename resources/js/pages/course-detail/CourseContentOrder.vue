<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { onBeforeRouteLeave } from '@/navigation';
import { getCourseContentOrder } from '@/services/queries/courseQuery.ts';
import { saveCourseContentOrder } from '@/services/mutations/courseMutation.ts';

const props = defineProps({ courseId: { type: [Number, String], required: true } });
const emit = defineEmits(['saved']);
const opened = ref(false), loading = ref(false), saving = ref(false), error = ref(''), message = ref('');
const contents = ref([]), original = ref([]);
const dirty = computed(() => contents.value.some((item, index) => item.id !== original.value[index]));
const canDiscard = () => !dirty.value || window.confirm('Urutan belum disimpan. Abaikan perubahan?');
let version = 0;
async function load() {
  if (saving.value || !canDiscard()) return;
  const request = ++version;
  opened.value = true; loading.value = true; error.value = ''; message.value = '';
  try {
    const data = await getCourseContentOrder(props.courseId);
    if (request !== version) return;
    contents.value = data.contents;
    original.value = data.contents.map(item => item.id);
  } catch (e) {
    if (request === version) error.value = e.response?.data?.message || 'Daftar materi belum dapat dimuat. Silakan coba lagi.';
  } finally { if (request === version) loading.value = false; }
}
function move(index, offset) {
  const target = index + offset;
  if (loading.value || saving.value || target < 0 || target >= contents.value.length) return;
  const items = [...contents.value];
  [items[index], items[target]] = [items[target], items[index]];
  contents.value = items; message.value = '';
}
async function save() {
  if (!dirty.value || loading.value || saving.value) return;
  saving.value = true; error.value = ''; message.value = '';
  try {
    const data = await saveCourseContentOrder(props.courseId, { content_ids: contents.value.map(item => item.id), original_ids: original.value });
    original.value = contents.value.map(item => item.id);
    message.value = data.message;
    emit('saved');
  } catch (e) { error.value = e.response?.data?.message || 'Urutan belum tersimpan. Silakan coba lagi.'; }
  finally { saving.value = false; }
}
function close() {
  if (saving.value || !canDiscard()) return;
  version++; opened.value = false; contents.value = []; original.value = []; loading.value = false;
}
function beforeUnload(event) {
  if (dirty.value || saving.value) { event.preventDefault(); event.returnValue = ''; }
}
onBeforeRouteLeave(() => !saving.value && canDiscard());
onMounted(() => window.addEventListener('beforeunload', beforeUnload));
onUnmounted(() => { version++; window.removeEventListener('beforeunload', beforeUnload); });
</script>

<template>
  <div>
    <button v-if="!opened" type="button" class="rounded-full border border-[#662FFF] text-[#662FFF] px-5 py-3 font-semibold text-sm" @click="load">Atur Urutan Materi &amp; Kuis</button>
    <section v-else class="rounded-2xl border border-purple-200 bg-white p-4 sm:p-5 space-y-4" aria-label="Urutan materi dan kuis">
      <div class="flex items-start justify-between gap-3">
        <div><h3 class="font-bold">Urutan Materi &amp; Kuis</h3><p class="text-sm text-gray-600 mt-1">Gunakan tombol naik/turun, lalu simpan. Daftar ini mencakup semua halaman materi.</p></div>
        <button type="button" :disabled="saving" class="text-sm text-gray-600 underline" @click="close">Tutup</button>
      </div>
      <p v-if="loading" role="status" class="text-sm text-gray-500">Memuat urutan...</p>
      <p v-if="error" role="alert" class="text-sm text-red-700">{{ error }} <button type="button" :disabled="saving || loading" class="underline" @click="load">Muat ulang</button></p>
      <p v-if="message" role="status" class="text-sm text-green-700">{{ message }}</p>
      <ol v-if="!loading" class="divide-y divide-gray-100">
        <li v-for="(item, index) in contents" :key="item.id" class="flex items-center gap-3 py-3">
          <span class="font-semibold text-purple-700 w-6 shrink-0">{{ index + 1 }}</span>
          <div class="min-w-0 flex-1"><p class="font-semibold text-sm break-words">{{ item.title }}</p><p class="text-xs text-gray-500">{{ item.quiz_id || item.type === 'quiz' ? 'Kuis' : 'Materi' }}</p></div>
          <div class="flex gap-2 shrink-0">
            <button type="button" :disabled="saving || index === 0" :aria-label="`Naikkan ${item.title}`" class="border rounded-lg px-2 py-2 text-sm disabled:opacity-30" @click="move(index, -1)"><i class="pi pi-arrow-up"></i> <span class="hidden sm:inline"></span></button>
            <button type="button" :disabled="saving || index === contents.length - 1" :aria-label="`Turunkan ${item.title}`" class="border rounded-lg px-2 py-2 text-sm disabled:opacity-30" @click="move(index, 1)"><i class="pi pi-arrow-down"></i> <span class="hidden sm:inline"></span></button>
          </div>
        </li>
      </ol>
      <p v-if="!loading && !error && !contents.length" class="text-sm text-gray-500">Belum ada materi atau kuis untuk diurutkan.</p>
      <button type="button" :disabled="!dirty || loading || saving" class="rounded-full bg-[#662FFF] text-white px-5 py-3 text-sm font-semibold disabled:opacity-40" @click="save">{{ saving ? 'Menyimpan...' : 'Simpan Urutan' }}</button>
    </section>
  </div>
</template>
