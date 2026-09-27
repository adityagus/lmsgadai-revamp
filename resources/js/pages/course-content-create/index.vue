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
          <option value="quiz">Quiz</option>
          <option value="text">Text</option>
          <option value="video">Video</option>
          <option value="pdf">PDF</option>
          <option value="google_form">Google Form</option>
        </select>
        <img src="/assets/images/icons/arrow-down.svg" class="w-5 h-5 sm:w-6 sm:h-6 shrink-0 pointer-events-none" alt="icon" />
      </div>
      <span class="error-message text-[#FF435A] text-xs">
        {{ errors?.type }}
      </span>
    </div>
    <div v-if="type === 'quiz'" class="flex flex-col gap-4 rounded-2xl border border-purple-200 bg-white p-5">
      <h2 class="flex items-center gap-2 font-semibold"><img src="/assets/images/icons/quiz-purple.svg" class="w-6 h-6" alt="">Tambahkan Quiz ke Course</h2>
      <p class="text-sm text-gray-600">Pilih quiz yang sudah ada atau buat quiz baru di sini. Setelah itu, simpan materi untuk menambahkannya ke course.</p>
      <label for="quiz-id" class="text-sm font-semibold">Pilih Quiz</label>
      <select id="quiz-id" v-model="quizId" class="rounded-xl border border-gray-300 p-3" :disabled="quizzesLoading">
        <option value="">{{ quizzesLoading ? 'Memuat quiz…' : 'Pilih quiz untuk course ini' }}</option>
        <option v-for="q in courseQuizzes" :key="q.id" :value="String(q.id)">{{ q.title }} — {{ q.status }} · {{ q.question_count }} soal</option>
      </select>
      <div v-if="!quizzesLoading && !quizError && !courseQuizzes.length" class="rounded-xl bg-purple-50 p-4 text-sm text-purple-900">
        Belum ada quiz untuk course ini. Klik <strong>Buat Quiz Baru</strong>; quiz yang disimpan akan otomatis dipilih.
      </div>
      <div v-if="selectedQuiz" class="rounded-xl bg-purple-50 p-4 text-sm text-purple-900">
        <p class="font-semibold">{{ selectedQuiz.title }}</p>
        <p>{{ selectedQuiz.question_count }} soal · {{ selectedQuiz.duration_minutes }} menit · Nilai minimal lulus {{ selectedQuiz.passing_grade }}</p>
        <p v-if="selectedQuiz.status === 'DRAFT'" class="mt-2">Quiz masih berupa draft. Terbitkan quiz agar dapat dikerjakan pada course ini.</p>
      </div>
      <p v-if="errors.quizId || quizError" class="text-red-600 text-sm" role="alert">{{ errors.quizId || quizError }}</p>
      <p class="text-sm text-gray-500">Quiz hanya terhubung ke course ini. Peserta yang dapat membuka course dapat mengerjakan quiz sesuai jadwal.</p>
      <div class="flex flex-wrap gap-4 text-sm font-semibold text-purple-700">
        <button v-if="courseId" type="button" @click="openQuizEditor()" class="rounded-xl bg-purple-600 px-4 py-3 text-white">Buat Quiz Baru</button>
        <button v-if="quizId" type="button" @click="openQuizEditor(quizId)">Edit Quiz</button>
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
    <div class="flex flex-col gap-2" v-if='type === "pdf" || type === "text"'>
      <label for="pdf" class="font-semibold text-xs sm:text-base">
        {{ type === "pdf" ? "Upload PDF" : 'Lampiran' }}
      </label>
      <div
        class="flex items-center w-full rounded-full border border-[#CFDBEF] gap-2 sm:gap-3 px-4 sm:px-5 transition-all duration-300 focus-within:ring-2 focus-within:ring-[#662FFF] bg-white">
        <img src="/assets/images/icons/bill-black.svg" class="w-5 h-5 sm:w-6 sm:h-6 shrink-0" alt="icon" />
        <input type='file' name="pdf" id="pdf" v-bind='pdf'
          class="appearance-none outline-none w-full py-2.5 sm:py-3 text-xs sm:text-sm font-semibold placeholder:font-normal placeholder:text-[#838C9D] !bg-transparent cursor-pointer"
          placeholder="Write tagline for better copy" @change='onFileChange' />
      </div>
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
import * as ClassicEditor from '@ckeditor/ckeditor5-build-classic';
import axios from 'axios';
import { useMutation } from '@tanstack/vue-query';
import { createContent, getContentsByCourseId, updateContent, getContentById } from '@/services/contentService';
import { mutateContentSchema } from '@/utils/zodSchema';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { useRoute, useRouter } from '@/navigation';
import Swal from 'sweetalert2';
import { api as quizApi, errorMessage } from '../quiz/api';
import { courseContentType } from '@/utils/courseContentType.mjs';
import '../quiz/quiz.css';
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
    quizId: ''
  }
});

const file = ref(null);
const pdfUrl = ref(null);
const content = ref(null);

const [title] = defineField('title');
const [type] = defineField('type');
const [pdf] = defineField('pdf');
const [text] = defineField('text');
const [youtubeId] = defineField('youtubeId');
const [formUrl] = defineField('formUrl');
const [quiz] = defineField('quiz');
const [quizId] = defineField('quizId');
const courseQuizzes = ref([]), quizzesLoading = ref(false), quizError = ref('');
const selectedQuiz = computed(() => courseQuizzes.value.find(q => String(q.id) === String(quizId.value)));
watch(selectedQuiz, q => { if (q && !title.value?.trim()) title.value = q.title; });
async function loadQuizzes() {
  if (!courseId.value || quizzesLoading.value) return;
  quizzesLoading.value = true; quizError.value = '';
  try { courseQuizzes.value = (await quizApi('get', 'manage')).filter(q => Number(q.course_id) === Number(courseId.value)); }
  catch (e) { quizError.value = errorMessage(e); }
  finally { quizzesLoading.value = false; }
}
const [route, router] = [useRoute(), useRouter()];
console.log('Route params:', route.params);
const courseId = ref(route.params.courseId);
const contentId = route.params.contentId; // For update mode
const contentById = ref([]);
const isEditMode = ref(!!contentId);
const existingOrder = ref(null);

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

      if (content.type === 'text') {
        text.value = content.content;
      } else if (content.type === 'video') {
        youtubeId.value = content.content;
      } else if (content.type === 'form' || content.type === 'google_form' || content.type === 'quiz') {
        formUrl.value = content.content || '';
      } else if (content.type === 'pdf') {
        // For PDF, handle file if needed
      }

    } catch (error) {
      console.error('Error loading content:', error);
    }
  }
};

// Load content data when component mounts
onMounted(async () => {
  await loadContentData();
  if (!isEditMode.value && route.query.type === 'quiz') type.value = 'quiz';
  await loadQuizzes();
  window.addEventListener('focus', refreshQuizChoices);
});
function refreshQuizChoices() { if (type.value === 'quiz') loadQuizzes(); }
onUnmounted(() => window.removeEventListener('focus', refreshQuizChoices));


const mutateCreate = useMutation({
  mutationFn: async (data) => await createContent(data)
});

const mutateUpdate = useMutation({
  mutationFn: async (data) => await updateContent(data, contentId)
});


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
  initialData: content == null ? "" : content.text,
};

const onFileChange = (event) => {
  console.log("File changed:", event.target.files[0]);
  const selectedFile = event.target.files[0];
  if (selectedFile) {
    file.value = selectedFile;
    pdfUrl.value = URL.createObjectURL(selectedFile);
    content.value = selectedFile;
  }
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
    } else if (values.type === 'pdf' && file.value) {
      formData.append('content', file.value);
    }

    if (isEditMode.value) {
      // Update existing content - preserve existing order
      if (existingOrder.value !== null && existingOrder.value !== undefined) {
        formData.append('order', existingOrder.value);
      }
      formData.append('_method', 'PUT');
      await mutateUpdate.mutateAsync(formData);
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
      await mutateCreate.mutateAsync(formData);
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

const ckeditor = CKEditor.component

// export default {
//   components: {
//     ckeditor: CKEditor.component
//   }
// }

</script>
