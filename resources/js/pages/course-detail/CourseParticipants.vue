<script setup>
import { ref, watch, onUnmounted } from 'vue';
import { getCourseParticipants, getCourseParticipantOptions } from '@/services/queries/courseQuery.ts';
import { addCourseParticipant, removeCourseParticipant } from '@/services/mutations/courseMutation.ts';

const props = defineProps({ courseId: { type: [Number, String], required: true } });
const emit = defineEmits(['total-students']);
const participants = ref([]), employees = ref([]), query = ref(''), selected = ref('');
const loading = ref(true), searching = ref(false), busy = ref(false), error = ref(''), searchError = ref(''), message = ref('');
let timer, searchVersion = 0, loadVersion = 0;
const failure = e => e.response?.data?.message || 'Permintaan gagal. Silakan coba lagi.';
async function load() {
  const version = ++loadVersion;
  loading.value = true; error.value = '';
  emit('total-students', null);
  try {
    const data = await getCourseParticipants(props.courseId);
    if (version === loadVersion) {
      participants.value = data.participants;
      emit('total-students', data.total_students);
    }
  } catch (e) { if (version === loadVersion) error.value = failure(e); }
  finally { if (version === loadVersion) loading.value = false; }
}
watch(() => props.courseId, () => { participants.value = []; message.value = ''; load(); }, { immediate: true });
watch(query, value => {
  clearTimeout(timer);
  const version = ++searchVersion;
  employees.value = []; selected.value = ''; searchError.value = ''; searching.value = false;
  if (value.trim().length < 2) return;
  searching.value = true;
  timer = setTimeout(async () => {
    try {
      const data = await getCourseParticipantOptions(value.trim());
      if (version === searchVersion) employees.value = data.employees;
    } catch (e) { if (version === searchVersion) searchError.value = failure(e); }
    finally { if (version === searchVersion) searching.value = false; }
  }, 300);
});
async function change(username, remove = false) {
  if (busy.value || !username) return;
  if (remove && !window.confirm(`Hapus akses khusus ${username} dari kelas ini?`)) return;
  busy.value = true; error.value = ''; message.value = '';
  try {
    const data = remove
      ? await removeCourseParticipant(props.courseId, username)
      : await addCourseParticipant(props.courseId, username);
    message.value = data.message;
    query.value = ''; selected.value = '';
    await load();
  } catch (e) { error.value = failure(e); }
  finally { busy.value = false; }
}
onUnmounted(() => { clearTimeout(timer); searchVersion++; loadVersion++; });
</script>

<template>
  <section class="rounded-[20px] border border-[#CFDBEF] p-5 sm:p-6 mb-6 space-y-4" aria-labelledby="course-participants-title">
    <div>
      <h2 id="course-participants-title" class="text-lg font-bold">Peserta Khusus Kelas</h2>
      <p class="text-sm text-gray-600 mt-1">Tambahkan karyawan agar dapat mengakses seluruh materi dan kuis, termasuk di luar jabatan yang dipilih untuk kelas. Jika kelas terbuka untuk semua jabatan, semua karyawan tetap dapat mengaksesnya.</p>
    </div>
    <p v-if="message" role="status" class="text-sm text-green-700">{{ message }}</p>
    <div v-if="error" role="alert" class="text-sm text-red-700">{{ error }} <button type="button" class="underline" :disabled="busy" @click="load">Muat ulang daftar</button></div>
    <fieldset :disabled="busy || loading" class="space-y-3">
      <label class="block text-sm font-semibold">Cari karyawan
        <input v-model="query" maxlength="100" placeholder="Ketik minimal 2 karakter nama atau username" class="block mt-2 w-full border border-gray-300 rounded-xl p-3 font-normal" />
      </label>
      <p v-if="searching" role="status" class="text-sm text-gray-500">Mencari karyawan...</p>
      <p v-else-if="searchError" role="alert" class="text-sm text-red-700">{{ searchError }}</p>
      <template v-else-if="employees.length">
        <label class="block text-sm font-semibold">Pilih karyawan
          <select v-model="selected" class="block mt-2 w-full border border-gray-300 rounded-xl p-3 font-normal">
            <option value="">Pilih karyawan</option>
            <option v-for="employee in employees" :key="employee.username" :value="employee.username" :disabled="participants.some(p => p.username === employee.username)">{{ employee.name }} ({{ employee.username }})</option>
          </select>
        </label>
        <button type="button" :disabled="!selected || !!error" class="rounded-full px-5 py-3 bg-[#662FFF] text-white font-semibold disabled:opacity-50" @click="change(selected)">Tambah Peserta Khusus</button>
      </template>
      <p v-else-if="query.trim().length >= 2" class="text-sm text-gray-500">Karyawan tidak ditemukan.</p>
    </fieldset>
    <p v-if="loading" role="status" class="text-sm text-gray-500">Memuat peserta...</p>
    <ul v-else-if="participants.length" class="divide-y divide-gray-100">
      <li v-for="participant in participants" :key="participant.username" class="flex items-center justify-between gap-3 py-3">
        <div class="min-w-0 break-words"><p class="font-semibold text-sm">{{ participant.name }}</p><p class="text-xs text-gray-500">{{ participant.username }}</p></div>
        <button type="button" :disabled="busy" class="text-sm text-red-600 shrink-0 disabled:opacity-50" :aria-label="`Hapus akses khusus ${participant.name}`" @click="change(participant.username, true)">Hapus akses</button>
      </li>
    </ul>
    <p v-else-if="!error" class="text-sm text-gray-500">Belum ada peserta khusus di kelas ini.</p>
    <p class="text-xs text-gray-500">Menghapus akses khusus tidak menghapus progres atau hasil kuis. Akses berdasarkan jabatan tetap berlaku.</p>
  </section>
</template>
