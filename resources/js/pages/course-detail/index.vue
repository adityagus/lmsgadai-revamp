<template>
  <div id="Breadcrumb" class="flex flex-wrap items-center gap-2 sm:gap-5 text-xs sm:text-sm *:after:content-['/'] *:after:ml-2 sm:*:after:ml-5 mb-2">
    <span class="last-of-type:after:content-[''] last-of-type:font-semibold">
      Dashboard
    </span>
    <span class="last-of-type:after:content-[''] last-of-type:font-semibold">
      <RouterLink to="/kelas" class="hover:underline">Kelola Kelas</RouterLink>
    </span>
    <span class="last-of-type:after:content-[''] last-of-type:font-semibold">
      Detail
    </span>
  </div>
  <CourseDetailSkeleton v-if="isLoading" />
  <div v-else-if="loadError" role="alert" class="rounded-2xl border border-red-100 bg-white p-6 text-center">
    <p class="text-red-600">{{ loadError }}</p>
    <button type="button" class="mt-4 rounded-full bg-[#662FFF] px-5 py-3 text-sm font-semibold text-white" @click="loadCourse">Coba lagi</button>
  </div>
  <template v-else>
  <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 sm:gap-[30px] mb-4 sm:mb-6">
    <div>
      <h1 class="font-extrabold text-2xl sm:text-[28px] leading-tight sm:leading-[42px]">
        {{ course?.name }}
      </h1>
    </div>
    <div class="flex items-center gap-2 sm:gap-3 w-full sm:w-auto">
      <RouterLink :to="`/courses/${course.id}/edit`"
        class="flex-1 sm:flex-none w-fit rounded-full border border-[#060A23] px-4 py-2.5 sm:px-[20px] sm:py-[14px] font-semibold text-nowrap text-center text-xs sm:text-sm hover:bg-gray-50 transition-colors">
        Edit Kelas
      </RouterLink>
      <RouterLink :to="`/course/${course.id}/preview`"
        class="flex-1 sm:flex-none w-fit rounded-full px-4 py-2.5 sm:px-[20px] sm:py-[14px] font-semibold text-white bg-[#662FFF] text-nowrap text-center text-xs sm:text-sm hover:bg-[#5521cc] transition-colors">
        Pratinjau Kelas
      </RouterLink>
    </div>
  </header>
  <section id="CourseInfo" class="flex flex-col lg:flex-row gap-5 lg:gap-[50px] mb-6 sm:mb-8">

    <div id="Thumbnail" class="flex shrink-0 w-full lg:w-[480px] h-[200px] sm:h-[250px] rounded-[20px] bg-[#D9D9D9] overflow-hidden">
      <img v-if="course.thumbnail_url && !thumbnailFailed" :src="course.thumbnail_url" class="w-full h-full object-cover" :alt="course.name" @error="thumbnailFailed = true" />
      <div v-else class="flex w-full flex-col items-center justify-center gap-3 bg-purple-50 text-[#662FFF]">
        <img src="/assets/images/icons/note-favorite-purple.svg" class="h-12 w-12" alt="" />
        <span class="text-sm font-medium">Gambar kelas belum tersedia</span>
      </div>
    </div>
    <div class="grid grid-cols-2 gap-3 sm:gap-5 w-full">
      <div class="flex flex-col rounded-[20px] border border-[#CFDBEF] p-4 sm:p-5 gap-2 sm:gap-4">
        <img src="/assets/images/icons/profile-2user-purple.svg" class="w-6 h-6 sm:w-8 sm:h-8" alt="icon" />
        <p class="font-semibold text-xs sm:text-base" aria-live="polite">{{ totalStudents ?? '—' }} Students</p>
      </div>
      <div class="flex flex-col rounded-[20px] border border-[#CFDBEF] p-4 sm:p-5 gap-2 sm:gap-4">
        <img src="/assets/images/icons/crown-purple.svg" class="w-6 h-6 sm:w-8 sm:h-8" alt="icon" />
        <p class="font-semibold text-xs sm:text-base line-clamp-1">{{ course?.category?.name || '-' }}</p>
      </div>
      <div class="flex flex-col rounded-[20px] border border-[#CFDBEF] p-4 sm:p-5 gap-2 sm:gap-4">
        <img src="/assets/images/icons/note-favorite-purple.svg" class="w-6 h-6 sm:w-8 sm:h-8" alt="icon" />
        <p class="font-semibold text-xs sm:text-base">{{ contents?.total || contents?.data?.length || 0 }} Contents</p>
      </div>
      <div class="flex flex-col rounded-[20px] border border-[#CFDBEF] p-4 sm:p-5 gap-2 sm:gap-4">
        <img src="/assets/images/icons/cup-purple.svg" class="w-6 h-6 sm:w-8 sm:h-8" alt="icon" />
        <p class="font-semibold text-xs sm:text-base">Certificate</p>
      </div>
    </div>
  </section>
  <CourseParticipants v-if="course.id" :key="course.id" :course-id="course.id" @total-students="totalStudents = $event" />
  <TableContentPage :contents="contents" :courseId="course.id" />
  </template>
</template>

<script setup>
import { RouterLink, useRoute } from '@/navigation';
import TableContentPage from './table-content.vue';
import CourseParticipants from './CourseParticipants.vue';
import { getCourseByIdPagination } from '@/services/queries/courseQuery.ts';
import { onMounted, ref, reactive } from 'vue';
import { useForm } from 'vee-validate';
import { mutateContentSchema } from '@/utils/zodSchema';
import CourseDetailSkeleton from './CourseDetailSkeleton.vue';

import "primeicons/primeicons.css";

// interface Props {
//   id: number
//   index: number
//   type: string
//   title: string
//   courseId: number
// }

const { handleSubmit, isSubmitting, values, errors } = useForm({
  validationSchema: mutateContentSchema
});
const route = useRoute();

const course = ref({});
const isLoading = ref(true);
const loadError = ref('');
const thumbnailFailed = ref(false);
const totalStudents = ref(null);
const contents = ref({});
const props = defineProps({
  id: {
    type: Number,
    default: 1
  },
  index: {
    type: Number,
    default: 1
  },
  type: {
    type: String,
    default: "Video"
  },
  title: {
    type: String,
    default: "Install VSCode di Windows"
  },
  courseId: {
    type: Number,
    default: 2
  }
})

async function loadCourse() {
  isLoading.value = true;
  loadError.value = '';
  thumbnailFailed.value = false;
  totalStudents.value = null;
  try {
    const result = await getCourseByIdPagination(route.params.id);
    course.value = result.course;
    contents.value = result.contents;
  } catch (error) {
    loadError.value = 'Detail kelas gagal dimuat. Silakan coba lagi.';
  } finally {
    isLoading.value = false;
  }
}

onMounted(loadCourse);

// const props = withDefaults(defineProps<Props>(), {
//   id: 1,
//   index: 1,
//   type: "Video",
//   title: "Install VSCode di Windows",
//   courseId: 2,
// })

</script>
