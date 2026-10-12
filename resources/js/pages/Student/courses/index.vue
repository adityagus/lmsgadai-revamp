<template>
  <header class="flex flex-col sm:flex-row sm:items-center justify-between mb-2 sm:mb-0">
    <div>
      <h1 class="font-extrabold text-2xl sm:text-[28px] leading-tight sm:leading-[42px]">Daftar Kelas</h1>
      <p class="text-[#838C9D] text-xs sm:text-base mt-1">Lihat semua kelas yang tersedia</p>
    </div>
  </header>
  <BatchLearning />
  <div v-if="error" role="alert" class="mt-4 rounded-xl bg-red-50 p-4 text-red-700">{{ error }} <button @click="fetchCourses()">Coba lagi</button></div>
  <section :aria-busy="loading" id="CourseList" class="flex flex-col w-full rounded-2xl sm:rounded-[30px] gap-4  mt-4">
    <!-- Konten utama (CardCourses) saat data sudah siap -->
    <div class="flex w-full items-center justify-center" role="status" aria-live="polite">
      <span v-if="!courses.length && loaded" class="text-gray-500">Belum ada kelas tersedia.</span>
    </div>
    <!-- Konten utama (CardCourses) saat data sudah siap -->
    <div v-for="item in courses" :key="item.id" v-if='loaded'>
      <div
        class="relative group cursor-pointer rounded-2xl bg-white border border-gray-200 shadow-sm px-0 py-0 overflow-hidden transition-all duration-200 hover:shadow-xl hover:scale-[1.01] hover:border-[#662FFF]"
        @click="goToDetail(item.id)">
        <div class="flex flex-col sm:flex-row items-stretch">
          <div
            class="w-full sm:w-48 h-44 sm:h-auto flex-shrink-0 bg-gray-100 flex items-center justify-center overflow-hidden">
            <img :src="item.thumbnail_url" alt="thumbnail"
              class="object-cover w-full h-full min-h-[170px] sm:min-h-0 transition-transform duration-300 group-hover:scale-105" />
          </div>
          <div class="flex-1 flex flex-col justify-between p-4 sm:p-5">
            <div>
              <div class="flex items-center gap-2 mb-2">
                <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-[#F3E8FF] text-[#662FFF]">{{
                  item.category?.name || 'Tanpa Kategori' }}</span>
                <span class="ml-auto text-xs text-gray-400">ID: {{ item.id }}</span>
              </div>
              <h2 class="font-bold text-base sm:text-lg text-[#2D1A5A] mb-1 line-clamp-2">{{ item.name }}</h2>
              <p class="text-xs sm:text-sm text-gray-500 line-clamp-2 mb-2">{{ item.description || 'Tidak ada deskripsi.' }}</p>
            </div>
            <div class="flex items-center justify-between mt-3 sm:mt-4">
              <span class="text-xs text-gray-400">Dibuat: {{ item.created_at ? item.created_at.split('T')[0] : '-'
              }}</span>
            </div>
          </div>
        </div>
        <div
          class="absolute inset-0 pointer-events-none rounded-2xl border-2 border-transparent group-hover:border-[#662FFF] transition-all duration-200">
        </div>
      </div>
    </div>

    <!-- Konten loading (CardSkeleton) saat data sedang dimuat -->
    <template v-if='loading && !loaded'>
      <div v-for="n in 5" :key='n'>
        <CardSkeletonStudent />
      </div>
    </template>

    <div id="Pagination" class="flex items-center gap-2 sm:gap-3 overflow-x-auto pb-2 scrollbar-hidden max-w-full" v-if='courses.length > 0'>
      <button type="button" v-for='(key) in totalPages' :key='key' @click='goToPage(key)'
        class="flex shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded-full items-center justify-center text-center transition-all duration-300 hover:bg-[#662FFF] hover:text-white hover:border-0 bg-[#EAEAEA] text-[#662FFF] cursor-pointer"
        :class="{ 'active': key == pagination }">
        <span class="font-semibold text-xs sm:text-sm leading-[21px]">{{ key }}</span>
      </button>
    </div>
  </section>
</template>

<script setup>
import { onMounted } from 'vue';
import { useCourseList } from '@/composables/useCourseList.mjs';
import { queryClient } from '@/api/queryClient';
import { getCourses } from '@/services/queries/courseQuery.ts';
import { useRouter } from '@/navigation';
import CardSkeletonStudent from '../../../components/skeletons/CardSkeletonStudent.vue';
import BatchLearning from '@/components/BatchLearning.vue';

const router = useRouter();
const { courses, pagination, totalPages, loading, loaded, error, fetchCourses, goToPage } = useCourseList(async (page) => {
  await queryClient.invalidateQueries({ queryKey: ['courses', 'list', page], refetchType: 'none' });
  return getCourses(page);
});

// Fungsi untuk menuju halaman detail kelas
const goToDetail = (id) => {
  router.push({ name: 'content-preview', params: { id } });
};

onMounted(() => { fetchCourses(); });
</script>
