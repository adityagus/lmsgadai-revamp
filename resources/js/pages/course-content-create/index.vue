<template>
  <div id="Breadcrumb" class="flex flex-wrap items-center gap-2 sm:gap-5 text-xs sm:text-sm *:after:content-['/'] *:after:ml-2 sm:*:after:ml-5 mb-2">
    <span class="last-of-type:after:content-[''] last-of-type:font-semibold">
      Kelola Kelas
    </span>
    <span class="last-of-type:after:content-[''] last-of-type:font-semibold">
      <router-link to='/kelas' class="hover:underline">
        Kelas
      </router-link>
    </span>
    <span v-if="courseId" class="last-of-type:after:content-[''] last-of-type:font-semibold">
      <router-link :to="`/kelas/${courseId}`" class="hover:underline">
        Detail Kelas
      </router-link>
    </span>
    <span class="last-of-type:after:content-[''] last-of-type:font-semibold">
      {{ isEditMode ? 'Ubah Materi' : 'Tambah Materi' }}
    </span>
  </div>
  <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 sm:gap-[30px] mb-4 sm:mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-[30px]">
      <div class="flex shrink-0 w-full sm:w-[150px] h-[120px] sm:h-[100px] rounded-[20px] overflow-hidden bg-[#D9D9D9]">
        <img src="/assets/images/thumbnails/th-1.png" class="w-full h-full object-cover" alt="thumbnail" />
      </div>
      <div>
        <h1 class="font-extrabold text-2xl sm:text-[28px] leading-tight sm:leading-[42px]">
          {{ isEditMode ? 'Ubah Materi' : 'Tambah Materi' }}
        </h1>
        <p class="text-[#838C9D] text-xs sm:text-base mt-1">
          {{ isEditMode ? 'Update the content for the course' : 'Give a best content for the course' }}
        </p>
      </div>
    </div>
    <router-link
      v-if="courseId"
      :to="`/kelas/${courseId}`"
      class="inline-flex items-center gap-2 rounded-full border border-[#CFDBEF] bg-white hover:bg-gray-50 px-5 py-2.5 text-xs sm:text-sm font-semibold text-[#060A23] transition-colors shrink-0 w-fit"
    >
      <i class="pi pi-arrow-left text-xs"></i>
      <span>Kembali ke Detail Kelas</span>
    </router-link>
  </header>
  <form @submit.prevent='onSubmit' class="flex flex-col w-full max-w-[930px] rounded-2xl sm:rounded-[30px] p-4 sm:p-[30px] gap-4 sm:gap-[30px] bg-[#F8FAFB]">
    <div class="flex flex-col gap-2">
      <label for="title" class="font-semibold text-xs sm:text-base">
        Judul Materi
      </label>
      <div
        class="flex items-center w-full rounded-full border border-[#CFDBEF] gap-2 sm:gap-3 px-4 sm:px-5 transition-all duration-300 focus-within:ring-2 focus-within:ring-[#662FFF] bg-white">
        <img src="/assets/images/icons/note-favorite-black.svg" class="w-5 h-5 sm:w-6 sm:h-6 shrink-0" alt="icon" />
        <input type="text" name="title" v-model="title" id="title"
          class="appearance-none outline-none w-full py-2.5 sm:py-3 text-xs sm:text-sm font-semibold placeholder:font-normal placeholder:text-[#838C9D] !bg-transparent"
          placeholder="Write better name for your course" />
      </div>
      <span class="error-message text-[#FF435A] text-xs">
        {{ errors?.title }}
      </span>
    </div>
    <div class="flex flex-col gap-2">
      <label for="type" class="font-semibold text-xs sm:text-base">
        Jenis Materi
      </label>
      <div
        class="flex items-center w-full rounded-full border border-[#CFDBEF] gap-2 sm:gap-3 px-4 sm:px-5 transition-all duration-300 focus-within:ring-2 focus-within:ring-[#662FFF] bg-white">
        <img src="/assets/images/icons/crown-black.svg" class="w-5 h-5 sm:w-6 sm:h-6 shrink-0" alt="icon" />
        <select name="type" id="type" v-model='type'
          class="appearance-none outline-none w-full py-2.5 sm:py-3 px-1 -mx-1 text-xs sm:text-sm font-semibold placeholder:font-normal placeholder:text-[#838C9D] !bg-transparent cursor-pointer">
          <option value="parent">Bab / Modul (Parent Accordion)</option>
          <option value="quiz">Quiz</option>
          <option value="text">Text</option>
          <option value="video">Video</option>
          <option value="dokumen">Dokumen</option>
          <option value="google_form">Google Form</option>
        </select>
        <img src="/assets/images/icons/arrow-down.svg" class="w-5 h-5 sm:w-6 sm:h-6 shrink-0 pointer-events-none" alt="icon" />
      </div>
      <span class="error-message text-[#FF435A] text-xs">
        {{ errors?.type }}
      </span>
    </div>
    <div class="flex flex-col gap-2" v-if="type !== 'parent'">
      <div class="flex items-center justify-between">
        <label for="parent_id" class="font-semibold text-xs sm:text-base">
          Bab / Parent Materi (Opsional)
        </label>
        <button
          v-if="availableParents.length > 0"
          type="button"
          @click="showManageParents = !showManageParents"
          class="text-xs text-[#662FFF] hover:underline font-semibold flex items-center gap-1 cursor-pointer"
        >
          <i class="pi pi-cog text-xs"></i>
          {{ showManageParents ? 'Sembunyikan Kelola Bab' : `Kelola / Hapus Bab (${availableParents.length})` }}
        </button>
      </div>

      <!-- Main Select with option to type a new parent -->
      <div
        class="flex items-center w-full rounded-full border border-[#CFDBEF] gap-2 sm:gap-3 px-4 sm:px-5 transition-all duration-300 focus-within:ring-2 focus-within:ring-[#662FFF] bg-white">
        <img src="/assets/images/icons/note-favorite-black.svg" class="w-5 h-5 sm:w-6 sm:h-6 shrink-0 opacity-60" alt="icon" />
        <select name="parent_id" id="parent_id" v-model="parentId"
          class="appearance-none outline-none w-full py-2.5 sm:py-3 px-1 -mx-1 text-xs sm:text-sm font-semibold placeholder:font-normal placeholder:text-[#838C9D] !bg-transparent cursor-pointer">
          <option value="">-- Tanpa Parent (Materi Mandiri / Level Utama) --</option>
          <option value="__new__">+ Tulis / Buat Bab Baru...</option>
          <optgroup label="Pilih Bab Yang Sudah Ada" v-if="availableParents.length">
            <option v-for="p in availableParents" :key="p.id" :value="String(p.id)">
              {{ p.title }} {{ p.type === 'parent' ? '(Bab / Modul)' : '' }}
            </option>
          </optgroup>
        </select>
        <img src="/assets/images/icons/arrow-down.svg" class="w-5 h-5 sm:w-6 sm:h-6 shrink-0 pointer-events-none" alt="icon" />
      </div>

      <!-- Text input when '+ Tulis / Buat Bab Baru...' is selected -->
      <div v-if="parentId === '__new__'" class="flex flex-col gap-1.5 p-3 sm:p-4 bg-purple-50 rounded-2xl border border-purple-200 mt-1">
        <label class="text-xs font-bold text-purple-900 flex items-center gap-1.5">
          <i class="pi pi-plus-circle text-[#662FFF]"></i>
          Tulis Nama Bab / Modul Baru:
        </label>
        <div class="flex items-center w-full rounded-xl border border-purple-300 bg-white px-3 py-2 focus-within:ring-2 focus-within:ring-[#662FFF]">
          <input
            type="text"
            v-model="newParentTitle"
            placeholder="Ketik nama bab baru, contoh: Bab 1: Pengenalan Gadai"
            class="w-full text-xs sm:text-sm font-semibold outline-none bg-transparent placeholder:font-normal placeholder:text-gray-400"
            maxlength="150"
          />
        </div>
        <p class="text-[11px] text-purple-700">
          Saat disimpan, bab baru ini otomatis dibuat dan materi ini langsung masuk sebagai anggota bab tersebut.
        </p>
      </div>

      <!-- Information and Delete button for selected parent -->
      <div v-if="selectedParentObj" class="flex flex-wrap items-center justify-between gap-2 px-1 text-xs mt-1">
        <span class="text-gray-600">
          Terpilih: <strong>{{ selectedParentObj.title }}</strong>
          <span class="text-gray-400 ml-1">({{ getParentChildrenCount(selectedParentObj.id) }} materi terdaftar)</span>
        </span>
        <button
          type="button"
          @click="handleDeleteParent(selectedParentObj.id)"
          class="text-red-600 hover:text-red-700 font-semibold flex items-center gap-1 hover:underline cursor-pointer"
        >
          <i class="pi pi-trash text-xs"></i>
          Hapus Bab Ini
        </button>
      </div>

      <!-- Expandable manager to delete any existing parent -->
      <div v-if="showManageParents && availableParents.length > 0" class="mt-2 p-3 bg-white rounded-2xl border border-gray-200 flex flex-col gap-2">
        <div class="flex items-center justify-between border-b pb-2">
          <span class="text-xs font-bold text-gray-700">Daftar Bab / Parent di Kelas Ini:</span>
          <span class="text-[11px] text-gray-400">Total: {{ availableParents.length }} Bab</span>
        </div>
        <div
          v-for="p in availableParents"
          :key="p.id"
          class="flex items-center justify-between gap-2 p-2 rounded-xl bg-gray-50 hover:bg-gray-100 transition-colors"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="pi pi-folder text-[#662FFF] text-xs"></i>
            <span class="text-xs font-semibold text-gray-800 truncate">{{ p.title }}</span>
            <span class="text-[10px] bg-purple-100 text-purple-800 px-2 py-0.5 rounded-full shrink-0">
              {{ getParentChildrenCount(p.id) }} materi
            </span>
          </div>
          <button
            type="button"
            @click="handleDeleteParent(p.id)"
            class="text-xs text-red-600 hover:text-red-800 px-2.5 py-1 rounded hover:bg-red-50 font-semibold flex items-center gap-1 cursor-pointer shrink-0"
            title="Hapus Bab ini"
          >
            <i class="pi pi-trash text-xs"></i>
            Hapus
          </button>
        </div>
      </div>
    </div>
    <div v-if="type === 'parent'" class="rounded-2xl border border-purple-200 bg-purple-50 p-4 sm:p-5 text-sm text-purple-900">
      <div class="flex items-center gap-2 font-bold text-base mb-1">
        <i class="pi pi-folder text-[#662FFF]"></i>
        <span>Bab / Modul (Parent Accordion)</span>
      </div>
      <p class="text-xs sm:text-sm text-purple-800">
        Item ini akan menjadi <strong>header accordion (Bab / Modul)</strong> di halaman pembelajaran. Materi-materi lain (Video, Teks, PDF, Quiz, Form) dapat dikelompokkan ke dalam Bab ini dengan memilih Bab ini pada dropdown Parent.
      </p>
    </div>
    <div v-if="type === 'quiz'" class="flex flex-col gap-4 rounded-2xl border border-purple-200 bg-white p-5">
      <h2 class="flex items-center gap-2 font-semibold"><img src="/assets/images/icons/quiz-purple.svg" class="w-6 h-6" alt="">Tambahkan Quiz ke Course</h2>
      <p class="text-sm text-gray-600">Pilih quiz yang sudah ada atau buat quiz baru di sini. Setelah itu, simpan materi untuk menambahkannya ke course.</p>
      <label for="quiz-id" class="text-sm font-semibold">Pilih Quiz</label>
      <select id="quiz-id" v-model="quizId" class="rounded-xl border border-gray-300 p-3" :disabled="quizzesLoading">
        <option value="">{{ quizzesLoading ? 'Memuat quiz…' : 'Pilih quiz untuk course ini' }}</option>
        <optgroup v-if="linkedQuizzes.length" label="Quiz untuk Kelas Ini">
          <option v-for="q in linkedQuizzes" :key="`linked-${q.id}`" :value="String(q.id)">{{ q.title }} — {{ q.status }} · {{ q.question_count }} soal</option>
        </optgroup>
        <optgroup v-if="otherBankQuizzes.length" label="Bank Quiz (Dari Kelas Lain)">
          <option v-for="q in otherBankQuizzes" :key="`bank-${q.id}`" :value="String(q.id)">{{ q.title }} — {{ q.status }} · {{ q.question_count }} soal</option>
        </optgroup>
      </select>
      <div v-if="!quizzesLoading && !quizError && !courseQuizzes.length" class="rounded-xl bg-purple-50 p-4 text-sm text-purple-900">
        Belum ada quiz untuk course ini. Klik <strong>Buat Quiz Baru</strong>; quiz yang disimpan akan otomatis dipilih.
      </div>
      <div v-if="selectedQuiz" class="rounded-xl bg-purple-50 p-4 text-sm text-purple-900">
        <p class="font-semibold">{{ selectedQuiz.title }}</p>
        <p>{{ selectedQuiz.question_count }} soal · {{ selectedQuiz.duration_minutes }} menit · Nilai minimal lulus {{ selectedQuiz.passing_grade }}</p>
        <div v-if="selectedQuiz.status === 'DRAFT'" class="mt-2 flex flex-wrap items-center justify-between gap-2 border-t border-purple-200 pt-2">
          <span>Quiz masih berupa draft. Terbitkan quiz agar dapat dikerjakan pada course ini.</span>
          <button type="button" @click="openQuizEditor(quizId)" class="rounded-lg bg-purple-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-purple-700">
            Terbitkan Quiz
          </button>
        </div>
      </div>
      <p v-if="errors.quizId || quizError" class="text-red-600 text-sm" role="alert">{{ errors.quizId || quizError }}</p>
      <p class="text-sm text-gray-500">Quiz hanya terhubung ke course ini. Peserta yang dapat membuka course dapat mengerjakan quiz sesuai jadwal.</p>
      <div class="flex flex-wrap gap-4 text-sm font-semibold text-purple-700">
        <button v-if="courseId" type="button" @click="openQuizEditor()" class="rounded-xl bg-purple-600 px-4 py-3 text-white">Buat Quiz Baru</button>
        <button v-if="quizId" type="button" @click="openQuizEditor(quizId)" class="rounded-xl border border-purple-600 bg-purple-50 px-4 py-3 text-purple-700 hover:bg-purple-100">Edit Quiz & Terbitkan</button>
        <button type="button" @click="loadQuizzes" :disabled="quizzesLoading">Perbarui Daftar</button>
      </div>
    </div>
    <div class="flex flex-col gap-2" v-if='type === "video"'>
      <label for="video" class="font-semibold text-xs sm:text-base">
        Youtube Video ID
      </label>
      <div
        class="flex items-center w-full rounded-full border border-[#CFDBEF] gap-2 sm:gap-3 px-4 sm:px-5 transition-all duration-300 focus-within:ring-2 focus-within:ring-[#662FFF] bg-white">
        <img src="/assets/images/icons/bill-black.svg" class="w-5 h-5 sm:w-6 sm:h-6 shrink-0" alt="icon" />
        <input type="text" name="video" v-model="youtubeId" id="video"
          class="appearance-none outline-none w-full py-2.5 sm:py-3 text-xs sm:text-sm font-semibold placeholder:font-normal placeholder:text-[#838C9D] !bg-transparent"
          placeholder="Write tagline for better copy" />
      </div>
    </div>
    <div class="flex flex-col gap-2" v-if='type === "google_form"'>
      <label for="formUrl" class="font-semibold text-xs sm:text-base">
        URL / Embed Code Google Form
      </label>
      <div
        class="flex items-center w-full rounded-full border border-[#CFDBEF] gap-2 sm:gap-3 px-4 sm:px-5 transition-all duration-300 focus-within:ring-2 focus-within:ring-[#662FFF] bg-white">
        <img src="/assets/images/icons/bill-black.svg" class="w-5 h-5 sm:w-6 sm:h-6 shrink-0" alt="icon" />
        <input type="text" name="formUrl" v-model="formUrl" id="formUrl"
          class="appearance-none outline-none w-full py-2.5 sm:py-3 text-xs sm:text-sm font-semibold placeholder:font-normal placeholder:text-[#838C9D] !bg-transparent"
          placeholder="Tempelkan link Google Form (https://docs.google.com/forms/...) atau kode iframe" />
      </div>
      <p class="text-[11px] sm:text-xs text-gray-500 ml-1">
        Tempelkan link Google Form atau kode embed &lt;iframe&gt;. Sistem akan otomatis merender Web View di halaman
        kursus.
      </p>
      <span class="error-message text-[#FF435A] text-xs">
        {{ errors?.formUrl }}
      </span>
    </div>
    <div class="flex flex-col gap-2" v-if='type === "text"'>
      <label class="font-semibold text-xs sm:text-base">Content Text</label>
      <div class="overflow-x-auto max-w-full">
        <ckeditor :editor="ClassicEditor" v-model="text" :config="editorConfig" />
      </div>
      <span class="error-message text-[#FF435A] text-xs">
        {{ errors?.text }}
      </span>
    </div>
    <div class="flex flex-col gap-2" v-if='type === "dokumen" || type === "pdf" || type === "text"'>
      <label for="pdf" class="font-semibold text-xs sm:text-base">
        {{ (type === "dokumen" || type === "pdf") ? "Upload Dokumen" : 'Lampiran' }}
      </label>
      <div
        class="flex items-center w-full rounded-full border border-[#CFDBEF] gap-2 sm:gap-3 px-4 sm:px-5 transition-all duration-300 focus-within:ring-2 focus-within:ring-[#662FFF] bg-white">
        <img src="/assets/images/icons/bill-black.svg" class="w-5 h-5 sm:w-6 sm:h-6 shrink-0" alt="icon" />
        <input type='file' name="pdf" id="pdf" v-bind='pdf'
          accept=".pdf,.ppt,.pptx,.doc,.docx,.xls,.xlsx"
          class="appearance-none outline-none w-full py-2.5 sm:py-3 text-xs sm:text-sm font-semibold placeholder:font-normal placeholder:text-[#838C9D] !bg-transparent cursor-pointer"
          @change='onFileChange' />
      </div>
      <p class="text-xs text-[#838C9D]">Format yang didukung: PDF, PPT, PPTX, DOCX, Excel (Maks. 5MB).</p>
      <span class="error-message text-[#FF435A] text-xs">
        {{ errors?.pdf }}
      </span>
    </div>
    <div class="flex flex-col sm:flex-row items-center gap-3 sm:gap-[14px] mt-2">
      <router-link v-if="courseId" :to="{
        name: 'courseDetail',
        params: { id: courseId }
      }"
        class="w-full rounded-full border border-[#060A23] py-3 sm:p-[14px_20px] font-semibold text-nowrap flex items-center justify-center text-xs sm:text-sm hover:bg-gray-50 transition-colors">
        Cancel
      </router-link>
      <button type="submit" :disabled="isSubmitting || (type === 'quiz' && (!quizId || quizzesLoading))"
        class="w-full rounded-full py-3 sm:p-[14px_20px] font-semibold text-white bg-[#662FFF] text-nowrap text-xs sm:text-sm hover:bg-[#5521cc] transition-colors cursor-pointer">
        {{ isSubmitting ? 'Menyimpan...' : type === 'quiz' ? 'Simpan Quiz ke Course' : isEditMode ? 'Simpan Perubahan Materi' : 'Tambah Materi' }}
      </button>
    </div>
  </form>
  <Teleport to="body">
    <dialog ref="quizDialog" class="quiz-app quiz-editor-dialog overflow-auto [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden" aria-label="Buat quiz untuk course" @cancel.prevent>
      <QuizEditor v-if="quizEditorOpen" :course-id="courseId" :quiz-id="quizEditorId" @saved="quizCreated" @cancel="closeQuizEditor" />
    </dialog>
  </Teleport>
</template>

<script setup>
import { ref, reactive, watchEffect, onMounted, onUnmounted, computed, watch, nextTick, defineAsyncComponent } from 'vue';
import CKEditor from '@ckeditor/ckeditor5-vue';
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
import { useMutation } from '@tanstack/vue-query';
import { createContent, updateContent, deleteContent } from '@/services/mutations/contentMutation.ts';
import { getContentsByCourseId, getContentById } from '@/services/queries/contentQuery.ts';
import { mutateContentSchema } from '@/utils/zodSchema';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { useRoute, useRouter } from '@/navigation';
import Swal from 'sweetalert2';
import { api as quizApi, errorMessage } from '../quiz/api';
import { courseContentType } from '@/utils/courseContentType.mjs';
import '../quiz/quiz.css';
const ckeditor = CKEditor.component;
const QuizEditor = defineAsyncComponent(() => import(/* webpackChunkName: "QuizEditor" */ '../quiz/QuizEditor.vue'));
const quizDialog = ref(null), quizEditorOpen = ref(false), quizEditorId = ref(null);
async function openQuizEditor(id = null) { quizEditorId.value = id; quizEditorOpen.value = true; await nextTick(); quizDialog.value.showModal(); }
function closeQuizEditor() { quizDialog.value.close(); quizEditorOpen.value = false; }
async function quizCreated(created) {
  closeQuizEditor();
  await loadQuizzes();
  quizId.value = String(created.id);
  if (!title.value?.trim()) title.value = created.title;
}

const editorData = ref('<p>Isi awal editor</p>');
// const content = reactive({
//   "title": "",
//   "type": "",
//   "content": "",
//   "order": 0,
// })

const { handleSubmit, isSubmitting, values, errors, defineField } = useForm({
  validationSchema: toTypedSchema(mutateContentSchema),
  initialValues: {
    title: '',
    type: 'text',
    text: '',
    youtubeId: '',
    formUrl: '',
    pdf: null,
    quiz: '',
    quizId: '',
    parent_id: ''
  }
});

const file = ref(null);
const pdfUrl = ref(null);
const content = ref(null);

const [title] = defineField('title');
const [type] = defineField('type');
const [parentId] = defineField('parent_id');
const [pdf] = defineField('pdf');
const [text] = defineField('text');
const [youtubeId] = defineField('youtubeId');
const [formUrl] = defineField('formUrl');
const [quiz] = defineField('quiz');
const [quizId] = defineField('quizId');
const courseQuizzes = ref([]), quizzesLoading = ref(false), quizError = ref('');
const selectedQuiz = computed(() => courseQuizzes.value.find(q => String(q.id) === String(quizId.value)));
watch(selectedQuiz, q => { if (q && !title.value?.trim()) title.value = q.title; });
const linkedQuizzes = computed(() => courseQuizzes.value.filter(q => Number(q.course_id) === Number(courseId.value) || (Array.isArray(q.course_ids) && q.course_ids.map(Number).includes(Number(courseId.value)))));
const otherBankQuizzes = computed(() => courseQuizzes.value.filter(q => !linkedQuizzes.value.some(l => Number(l.id) === Number(q.id))));
async function loadQuizzes() {
  if (!courseId.value || quizzesLoading.value) return;
  quizzesLoading.value = true; quizError.value = '';
  try { courseQuizzes.value = await quizApi('get', 'manage'); }
  catch (e) { quizError.value = errorMessage(e); }
  finally { quizzesLoading.value = false; }
}
const [route, router] = [useRoute(), useRouter()];
console.log('Route params:', route.params);
const courseId = ref(route.params.courseId);
const contentId = route.params.contentId; // For update mode
const contentById = ref([]);
const availableParents = computed(() => {
  return (contentById.value || []).filter(c => {
    if (contentId && String(c.id) === String(contentId)) return false;
    return c.type === 'parent' || !c.parent_id;
  });
});
const isEditMode = ref(!!contentId);
const existingOrder = ref(null);

const newParentTitle = ref('');
const showManageParents = ref(false);

const selectedParentObj = computed(() => {
  if (!parentId.value || parentId.value === '__new__') return null;
  return (contentById.value || []).find(c => String(c.id) === String(parentId.value));
});

const getParentChildrenCount = (id) => {
  return (contentById.value || []).filter(c => String(c.parent_id) === String(id) && String(c.id) !== String(id)).length;
};

const handleDeleteParent = async (id) => {
  const target = (contentById.value || []).find(c => String(c.id) === String(id));
  const pTitle = target?.title || 'Bab ini';
  const childrenCount = getParentChildrenCount(id);

  let text = `Apakah Anda yakin ingin menghapus "${pTitle}"?`;
  if (childrenCount > 0) {
    text = `Perhatian: "${pTitle}" memiliki ${childrenCount} materi di dalamnya. Jika Bab ini dihapus, materi-materi tersebut TIDAK akan terhapus, melainkan menjadi materi mandiri (tanpa Bab).`;
  }

  const result = await Swal.fire({
    title: childrenCount > 0 ? 'Peringatan: Bab Memiliki Materi!' : 'Hapus Bab / Modul?',
    text: text,
    icon: childrenCount > 0 ? 'warning' : 'question',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#838C9D',
    confirmButtonText: 'Ya, Tetap Hapus',
    cancelButtonText: 'Batal'
  });

  if (result.isConfirmed) {
    try {
      await deleteContent(id);
      if (String(parentId.value) === String(id)) {
        parentId.value = '';
      }
      await countContent();
      Swal.fire({
        title: 'Berhasil!',
        text: `Bab "${pTitle}" berhasil dihapus.`,
        icon: 'success',
        confirmButtonColor: '#662FFF',
        timer: 1500
      });
    } catch (error) {
      console.error('Error deleting parent:', error);
      Swal.fire({
        title: 'Gagal!',
        text: error?.response?.data?.message || 'Gagal menghapus bab.',
        icon: 'error',
        confirmButtonColor: '#662FFF'
      });
    }
  }
};

const countContent = async () => {
  contentById.value = await getContentsByCourseId(courseId.value);
  return Math.max(0, ...contentById.value.map(item => Number(item.order) || 0)) + 1;
};

// Load content data for edit mode
const loadContentData = async () => {
  if (isEditMode.value) {
    try {
      const content = await getContentById(contentId);
      courseId.value = content.course_id;
      existingOrder.value = content.order;

      // Update form values based on content type
      title.value = content.title;
      type.value = courseContentType(content);
      quizId.value = content.quiz_id ? String(content.quiz_id) : '';
      parentId.value = content.parent_id ? String(content.parent_id) : '';

      if (content.type === 'text') {
        text.value = content.content;
      } else if (content.type === 'video') {
        youtubeId.value = content.content;
      } else if (content.type === 'form' || content.type === 'google_form' || content.type === 'quiz') {
        formUrl.value = content.content || '';
      } else if (content.type === 'pdf' || content.type === 'dokumen') {
        type.value = 'dokumen';
      }

    } catch (error) {
      console.error('Error loading content:', error);
    }
  }
};

// Load content data when component mounts
onMounted(async () => {
  await loadContentData();
  if (courseId.value) {
    await countContent();
  }
  if (!isEditMode.value && route.query.type === 'quiz') type.value = 'quiz';
  if (!isEditMode.value && route.query.parent_id) parentId.value = String(route.query.parent_id);
  await loadQuizzes();
  window.addEventListener('focus', refreshQuizChoices);
});
function refreshQuizChoices() { if (type.value === 'quiz') loadQuizzes(); }
onUnmounted(() => window.removeEventListener('focus', refreshQuizChoices));


const mutateCreate = useMutation(createContent.options());

const mutateUpdate = useMutation(updateContent.options());


const editorConfig = {
  toolbar: [
    "undo",
    "redo",
    "|",
    "heading",
    "|",
    "bold",
    "italic",
    "|",
    "link",
    "insertTable",
    "mediaEmbed",
    "|",
    "bulletedList",
  "numberedList",
    "indent",
    "outdent",
  ],
  mediaEmbed: {
    previewsInData: true,
    providers: [{
      name: 'canva',
      url: /^https?:\/\/(?:www\.)?canva\.com\/design\/[\w-]+\/view(?:\?.*)?$/i,
      html: match => `<iframe src="${match[0].replace(/([?&])embed(?:=true)?/i, '$1embed=true')}${match[0].includes('?') ? '&' : '?'}embed=true" loading="lazy" allowfullscreen></iframe>`,
    }],
  },
};

const ALLOWED_DOC_EXT = ['pdf', 'ppt', 'pptx', 'doc', 'docx', 'xls', 'xlsx'];

const onFileChange = (event) => {
  const selectedFile = event.target.files?.[0];
  if (!selectedFile) return;
  const ext = (selectedFile.name.split('.').pop() || '').toLowerCase();
  if (!ALLOWED_DOC_EXT.includes(ext)) {
    Swal.fire({ icon: 'error', title: 'Format tidak didukung', text: 'Format yang didukung: PDF, PPT, PPTX, DOCX, Excel (Maks. 5MB).' });
    event.target.value = '';
    return;
  }
  if (selectedFile.size > 5 * 1024 * 1024) {
    Swal.fire({ icon: 'error', title: 'Ukuran file terlalu besar', text: 'Ukuran file maksimal 5MB.' });
    event.target.value = '';
    return;
  }
  file.value = selectedFile;
  pdfUrl.value = URL.createObjectURL(selectedFile);
  content.value = selectedFile;
};

// const changeType = (event) => {
//   console.log()
//   const selectedType = event.target.value;

//   if(selectedType === 'video') {
//     youtubeId = ''; // Reset video field if type is video
//   } else if (selectedType === 'pdf') {
//     pdf = ''; // Reset video field if type is pdf
//   }else if (selectedType === 'text') {
//     text = ''; // Reset text field if type is text
//   }else{
//     quiz = ''
//   }

//   // You can add additional logic here based on the selected type
// };

const onSubmit = handleSubmit(async () => {
  const formData = new FormData();
  try {

    console.log('Submitting form with values:', values);
    formData.append('title', values.title);
    // The legacy database stores embedded forms as quiz without quiz_id.
    // Native quizzes are linked by quiz_id; keep the UI types unambiguous.
    formData.append('type', values.type === 'google_form' ? 'quiz' : values.type);
    formData.append('quiz_id', values.type === 'quiz' ? values.quizId : '');

    if (values.type === 'parent') {
      formData.append('parent_id', '');
    } else if (parentId.value === '__new__' && newParentTitle.value.trim()) {
      formData.append('new_parent_title', newParentTitle.value.trim());
      formData.append('parent_id', '');
    } else {
      formData.append('parent_id', (parentId.value && parentId.value !== '__new__') ? parentId.value : '');
    }

    formData.append('course_id', courseId.value);
    if (values.type === 'text') {
      formData.append('content', values.text || '');
      if (file.value) {
        formData.append('lampiran', file.value);
      }
    } else if (values.type === 'video') {
      formData.append('content', values.youtubeId || '');
    } else if (values.type === 'google_form') {
      formData.append('content', values.formUrl || values.quiz || '');
    } else if ((values.type === 'dokumen' || values.type === 'pdf') && file.value) {
      formData.append('content', file.value);
    }

    if (isEditMode.value) {
      // Update existing content - preserve existing order
      if (existingOrder.value !== null && existingOrder.value !== undefined) {
        formData.append('order', existingOrder.value);
      }
      formData.append('_method', 'PUT');
      await mutateUpdate.mutateAsync([formData, contentId]);
      console.log('Content updated successfully');
      Swal.fire({
        title: 'Berhasil!',
        text: 'Materi kursus berhasil diperbarui.',
        icon: 'success',
        confirmButtonColor: '#662FFF',
        timer: 1500
      });
    } else {
      // Create new content - assign next order
      formData.append('order', await countContent());
      console.log('Creating content with data:', formData);
      await mutateCreate.mutateAsync([formData]);
      console.log('Content created successfully');
      Swal.fire({
        title: 'Berhasil!',
        text: 'Materi kursus berhasil ditambahkan.',
        icon: 'success',
        confirmButtonColor: '#662FFF',
        timer: 1500
      });
    }

    // Redirect back to course detail
    router.push({
      name: 'courseDetail',
      params: { id: courseId.value }
    });
  } catch (error) {
    console.error('Error saving content:', error);
    console.error('Error response:', error.response?.data);
    Swal.fire({
      title: 'Gagal!',
      text: error.response?.data?.message || 'Terjadi kesalahan saat menyimpan materi kursus.',
      icon: 'error',
      confirmButtonColor: '#662FFF'
    });
  }
});

//   }
// }

</script>
