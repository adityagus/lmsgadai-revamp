<template>
  <div>
    <section id="CourseList" class="flex flex-col w-full rounded-2xl sm:rounded-[30px] p-4 sm:p-[30px] gap-4 sm:gap-[30px] bg-[#F8FAFB]">
      <div class="header flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <h2 class="font-bold text-lg sm:text-[22px] leading-tight sm:leading-[33px]">
          Isi Materi
        </h2>
        <div class="flex flex-wrap gap-3">
        <RouterLink :to="{path: `/course/create-contents/${props.courseId}`, query: {type: 'quiz'}}"
          class="rounded-full px-5 py-3 font-semibold text-white bg-[#662FFF] text-center text-sm">
          Tambah Quiz
        </RouterLink>
        <RouterLink :to="`/course/create-contents/${props.courseId}`"
          class="w-full sm:w-fit rounded-full px-5 py-3 font-semibold text-white bg-[#662FFF] text-nowrap text-center text-xs sm:text-sm hover:bg-[#5521cc] transition-colors">
          Tambah Materi
        </RouterLink>
        </div>
      </div>
      <ContentItem v-for="(content, index) in currentContents.data" :key="content?.id || index" :id="content?.id"
        :index="((currentContents.current_page - 1) * currentContents.per_page) + index + 1" :type="content?.type"
        :title="content?.title" :courseId="content?.course_id" :quizId="content?.quiz_id" :body="content?.content" @delete-content="handleDeleteContent" />

      <div id="Pagination" class="flex items-center gap-2 sm:gap-3 overflow-x-auto pb-2 scrollbar-hidden max-w-full" v-if='currentContents.last_page > 1'>
        <!-- Previous button -->
        <button type="button" @click='goToPage(pagination - 1)' :disabled="pagination === 1"
          class="flex shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded-full items-center justify-center text-center transition-all duration-300 hover:bg-[#662FFF] hover:text-white disabled:opacity-50 disabled:cursor-not-allowed bg-[#EAEAEA] text-[#662FFF] cursor-pointer">
          <span class="font-semibold text-xs sm:text-sm leading-[21px]">‹</span>
        </button>

        <!-- Page numbers -->
        <button type="button" v-for='page in currentContents.last_page' :key='page' @click='goToPage(page)'
          class="flex shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded-full items-center justify-center text-center transition-all duration-300 hover:bg-[#662FFF] hover:text-white hover:border-0 cursor-pointer"
          :class="{
            'bg-[#662FFF] text-white': page === pagination,
            'bg-[#EAEAEA] text-[#662FFF]': page !== pagination
          }">
          <span class="font-semibold text-xs sm:text-sm leading-[21px]">{{ page }}</span>
        </button>

        <!-- Next button -->
        <button type="button" @click='goToPage(pagination + 1)' :disabled="pagination === currentContents.last_page"
          class="flex shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded-full items-center justify-center text-center transition-all duration-300 hover:bg-[#662FFF] hover:text-white disabled:opacity-50 disabled:cursor-not-allowed bg-[#EAEAEA] text-[#662FFF] cursor-pointer">
          <span class="font-semibold text-xs sm:text-sm leading-[21px]">›</span>
        </button>
      </div>

      <!-- <div id="Pagination" class="flex items-center gap-3">
          <button
            type="button"
            class="flex shrink-0 w-9 h-9 rounded-full items-center justify-center text-center transition-all duration-300 hover:bg-[#662FFF] hover:text-white hover:border-0 bg-[#662FFF] text-white"
          >
            <span class="font-semibold text-sm leading-[21px]">1</span>
          </button>
          <button
            type="button"
            class="flex shrink-0 w-9 h-9 rounded-full items-center justify-center text-center transition-all duration-300 hover:bg-[#662FFF] hover:text-white hover:border-0 border border-[#060A23]"
          >
            <span class="font-semibold text-sm leading-[21px]">2</span>
          </button>
          <button
            type="button"
            class="flex shrink-0 w-9 h-9 rounded-full items-center justify-center text-center transition-all duration-300 hover:bg-[#662FFF] hover:text-white hover:border-0 border border-[#060A23]"
          >
            <span class="font-semibold text-sm leading-[21px]">3</span>
          </button>
          <button
            type="button"
            class="flex shrink-0 w-9 h-9 rounded-full items-center justify-center text-center transition-all duration-300 hover:bg-[#662FFF] hover:text-white hover:border-0 border border-[#060A23]"
          >
            <span class="font-semibold text-sm leading-[21px]">4</span>
          </button>
          <button
            type="button"
            class="flex shrink-0 w-9 h-9 rounded-full items-center justify-center text-center transition-all duration-300 hover:bg-[#662FFF] hover:text-white hover:border-0 border border-[#060A23]"
          >
            <span class="font-semibold text-sm leading-[21px]">5</span>
          </button>
        </div> -->
    </section>
  </div>

  <!-- Debug info (remove in production) -->
  <div class="mt-4 p-4 bg-gray-100 rounded text-xs" v-if="false">
    <h3 class="font-bold mb-2">Debug Info:</h3>
    <p>Current Page: {{ currentContents.current_page }}</p>
    <p>Last Page: {{ currentContents.last_page }}</p>
    <p>Per Page: {{ currentContents.per_page }}</p>
    <p>Total: {{ currentContents.total }}</p>
    <p>Pagination State: {{ pagination }}</p>
  </div>
</template>

<script setup>
import { getCourseByIdPagination } from '@/services/courseService';
import { deleteContent } from '@/services/contentService';
import ContentItem from './content-item.vue';
import { ref, defineProps, defineEmits, watch, onMounted } from 'vue';
import Swal from 'sweetalert2';

const totalPages = ref(5);
const pagination = ref(1);
const currentContents = ref({});

const props = defineProps({
  contents: {
    type: Object,
    required: true
  },
  courseId: {
    type: [String, Number],
    required: true
  }
});


const emit = defineEmits(['update:contents']);

// Initialize current contents with props
currentContents.value = props.contents;
console.log("Initial props:", props);

// Watch for changes in props.contents
watch(() => props.contents, (newContents) => {
  currentContents.value = newContents;
  if (newContents.current_page) {
    pagination.value = newContents.current_page;
  }
}, { immediate: true });

const fetchContent = async (page = 1) => {
  try {
    console.log("Fetching content for page:", props?.courseId, page);
    const result = await getCourseByIdPagination(props?.courseId, page);

    if (result && result.contents) {
      currentContents.value = result.contents;
      // Emit the updated contents to parent component
      emit('update:contents', result.contents);
      console.log("Fetched contents:", result.contents);
    }
  } catch (error) {
    console.error("Error fetching content:", error);
  }
};

const goToPage = async (page = 1) => {
  if (page !== pagination.value && page >= 1 && page <= (currentContents.value.last_page || 1)) {
    pagination.value = page;
    await fetchContent(page);
    console.log("Navigated to page:", page);
  }
}

const handleDeleteContent = async (contentId) => {
  console.log('Deleting content:', contentId);

  Swal.fire({
    title: "Apakah Anda yakin?",
    text: "Materi ini akan dihapus dari kelas!",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#662FFF",
    cancelButtonColor: "#838C9D",
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal"
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        const response = await deleteContent(contentId);

        if (response) {
          Swal.fire({
            title: "Berhasil Dihapus!",
            text: "Materi kursus telah dihapus.",
            icon: "success",
            confirmButtonColor: "#662FFF",
            timer: 1500
          });

          // Refresh content list
          await fetchContent(pagination.value);
        }
      } catch (error) {
        console.error("Error deleting content:", error);
        Swal.fire({
          title: "Gagal!",
          text: "Gagal menghapus materi.",
          icon: "error",
          confirmButtonColor: "#662FFF"
        });
      }
    }
  });
}


</script>
