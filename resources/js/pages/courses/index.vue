<template>
  <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 sm:gap-[30px] mb-2 sm:mb-0">
    <div>
      <h1 class="font-extrabold text-2xl sm:text-[28px] leading-tight sm:leading-[42px]">Kelola Kelas</h1>
      <p class="text-[#838C9D] text-xs sm:text-base mt-1">Berikan masa depan terbaik untuk karyawan hebat Anda</p>
    </div>
    <div class="flex items-center gap-3">
      <RouterLink to="/courses/create"
        v-if="canManage"
        class="w-full sm:w-fit rounded-full px-5 py-3 font-semibold text-white bg-[#662FFF] text-nowrap text-center text-xs sm:text-sm shadow-md transition hover:bg-[#5521cc]">
        + Tambah Kelas
      </RouterLink>
    </div>
  </header>
  <section :aria-busy="loading" id="CourseList" class="flex flex-col w-full rounded-2xl sm:rounded-[30px] p-2 gap-4 sm:gap-[10px]">

    <!-- <div class="" role="status" aria-live="polite">
      <span v-if="loading && loaded" class="text-[#662FFF]">Memperbarui kelas...</span>
      <span v-else-if="error" class="text-red-600">{{ error }} <button type="button" class="underline" @click="fetchCourses()">Coba lagi</button></span>
      <span v-else-if="loaded && !courses.length" class="text-gray-500">Belum ada kelas tersedia.</span>
    </div> -->
    <!-- Konten utama (CardCourses) saat data sudah siap -->
    <div v-for="item in courses" :key="item.id" v-if='loaded'>
      <CardCourses :id="item.id" :imageUrl="item.thumbnail_url" :name="item.name" :totalStudent="'12'"
        :category="item.category?.name" @delete-course='handleDelete'
        :read-only="!canManage" />
    </div>

    <!-- Konten loading (CardSkeleton) saat data sedang dimuat -->
    <template v-if='loading && !loaded'>
      <div v-for="n in 5" :key='n'>
        <CardSkeleton />
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
import { RouterLink } from '@/navigation';
import CardCourses from './card.vue';
import { onMounted, ref, computed } from 'vue';
import { useCourseList } from '@/composables/useCourseList.mjs';
import Swal from "sweetalert2";
import { deleteCourse } from '@/services/mutations/courseMutation.ts';
import { getCourses } from '@/services/queries/courseQuery.ts';
import { getSession } from '@/services/queries/authQuery.ts';
import CardSkeleton from '../../components/skeletons/CardSkeleton.vue';
// import { deleteCourse, getCourses } from './../../../services/courseService.js';

const { courses, pagination, totalPages, loading, loaded, error, fetchCourses, goToPage } = useCourseList(getCourses);
const auth = ref(null);
const canManage = computed(() => ['admin', 'trainer', 'teacher'].includes(auth.value?.role));

// Fungsi untuk menghapus kursus
const handleDelete = async (id) => {
  Swal.fire({
    title: "Apakah Anda yakin?",
    text: "Anda tidak dapat mengembalikan kursus ini!",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#662FFF",
    cancelButtonColor: "#838C9D",
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal"
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        const res = await deleteCourse(id);
        if (res && (res.status == 200 || res.success)) {
          Swal.fire({
            title: "Berhasil dihapus!",
            text: "Kursus telah dihapus.",
            icon: "success",
            confirmButtonColor: "#662FFF",
            timer: 1500
          });
          fetchCourses(pagination.value);
        }
      } catch (error) {
        console.log("Terjadi kesalahan:", error);
        Swal.fire({
          title: "Gagal!",
          text: "Gagal menghapus kursus.",
          icon: "error",
          confirmButtonColor: "#662FFF"
        });
      }
    }
  });
}

onMounted(() => {
  fetchCourses();
  getSession().then(result => { auth.value = result.auth; }).catch(() => { auth.value = null; });
});

</script>
