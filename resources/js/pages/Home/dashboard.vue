<template>

  <!-- openmodalpdf -->
  <OpenModalPDF ref="modalRef" v-model:showModal="showModal" v-model:selectedCard="selectedCard" />

  <!-- User Dashboard Section -->
  <section class="">
    <h2 class="font-bold text-xl mb-2">Hi, {{ user.nama }} 👋</h2>
    <p class="text-gray-500 mb-4">Selamat datang di dashboard beladiri!</p>
    <!-- Kursus dan Dokumen Terakhir -->
    <div class="grid grid-cols-2 gap-6 mt-4">
      <div class="bg-white rounded-xl p-6 shadow">
        <p class="text-lg font-semibold mb-2">Kursus Terakhir Dilihat </p>
        <ul>
          <div v-for="course in lastCourses" :key="course.id">
            <CardCourses :id="course.id" :imageUrl="course.thumbnail_url" :name="course.name" :totalStudent="'12'"
              :category="course.category?.name" :readOnly='true' />
          </div>
        </ul>
      </div>
      <div class="bg-white rounded-xl p-6 shadow">
        <p class="text-lg font-semibold mb-2">Dokumen Terakhir Diakses</p>
        <ul>
          <li v-for="doc in lastDocuments" :key="doc.id" class="mb-1">
            <a href="#" @click.prevent="openDetail(doc)" class="text-blue-600 hover:underline">{{ doc.title }}</a>
            <span class="text-gray-500 text-sm"> ({{ doc.accessed_at }})</span>
          </li>
        </ul>
      </div>
    </div>
  </section>

  <section class="-mt-6">
    <div class="bg-white rounded-[20px] p-6 shadow-[0_4px_4px_0_#E0E2EF] mt-4">
      <h3 class="font-extrabold text-xl leading-[30px] mb-4">Dokumen Terbaru Diupload</h3>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
          <thead>
            <tr>
              <th class="px-4 py-3 text-left font-bold text-[#838C9D] uppercase tracking-wider">Judul Dokumen</th>
              <th class="px-4 py-3 text-left font-bold text-[#838C9D] uppercase tracking-wider">Kategori</th>
              <th class="px-4 py-3 text-left font-bold text-[#838C9D] uppercase tracking-wider">Tanggal Upload</th>
              <th class="px-4 py-3 text-left font-bold text-[#838C9D] uppercase tracking-wider">Diupload Oleh</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="doc in latestUploadedDocuments" :key="doc.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-4 py-3 whitespace-nowrap">
                <a href="#" @click.prevent="openDetail(doc)" class="text-blue-600 hover:underline font-semibold">{{
                  doc.title }}</a>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-gray-600">
                <span
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                  {{ doc.menu?.name || 'Dokumen' }}
                </span>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-gray-500">{{ doc.dateLastUpdate }}</td>
              <td class="px-4 py-3 whitespace-nowrap">
                <span
                  class="inline-flex items-center gap-1.5 py-1 px-2 rounded-lg text-xs font-semibold bg-blue-50 text-blue-600">
                  {{ doc.uploader_name }}
                </span>
              </td>
            </tr>
            <tr v-if="!latestUploadedDocuments || latestUploadedDocuments.length === 0">
              <td colspan="4" class="px-4 py-8 text-center text-gray-400">Belum ada dokumen terbaru.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- Overview Perusahaan -->
  <div class="overview"
    v-if='user.idgrup === "JBT-032" || user.idgrup === "JBT-038" || user.idgrup === "JBT-037" || user.idgrup === "JBT-039" || user.idgrup === "JBT-040"'>
    <header class="flex items-center justify-between gap-[30px]">
      <div>
        <h1 class="font-extrabold text-[28px] leading-[42px]">Overview Company</h1>
        <p class="text-[#838C9D] mt-[1]">Grow your company quickly</p>
      </div>
      <!-- <div class="flex items-center gap-3">
         <a href="#" class="w-fit rounded-full border border-[#060A23] p-[14px_20px] font-semibold text-nowrap">
           Customize
         </a>
         <a href="" class="w-fit rounded-full p-[14px_20px] font-semibold text-[#FFFFFF] bg-[#662FFF] text-nowrap">
           Export Data
         </a>
       </div> -->
    </header>
    <section id="Stats" class="flex rounded-[30px] p-[30px] gap-[30px] bg-[#F8FAFB]">
      <div class="grid grid-cols-3 gap-[30px] w-full">
        <!-- <div class="flex flex-col rounded-[20px] p-5 gap-5 bg-white shadow-[0_4px_4px_0_#E0E2EF]">
          <img src="/assets/images/icons/profile-2user-purple.svg" class="w-[46px] h-[46px]" alt="icon" />
          <div>
            <p class="font-extrabold text-2xl leading-[36px]">{{ overview.active_users }}</p>
            <p class="text-[#838C9D]">Active in the Last 7 Days</p>
          </div>
        </div> -->
        <div class="flex flex-col rounded-[20px] p-5 gap-5 bg-white shadow-[0_4px_4px_0_#E0E2EF]">
          <img src="/assets/images/icons/note-favorite-purple.svg" class="w-[46px] h-[46px]" alt="icon" />
          <div>
            <p class="font-extrabold text-2xl leading-[36px]">{{ overview.total_courses }}</p>
            <p class="text-[#838C9D]">Total Courses</p>
          </div>
        </div>
        <div class="flex flex-col rounded-[20px] p-5 gap-5 bg-white shadow-[0_4px_4px_0_#E0E2EF]">
          <img src="/assets/images/icons/video-play-purple.svg" class="w-[46px] h-[46px]" alt="icon" />
          <div>
            <p class="font-extrabold text-2xl leading-[36px]">{{ overview.total_pengumuman }}</p>
            <p class="text-[#838C9D]">Total Pengumuman</p>
          </div>
        </div>
        <div class="flex flex-col rounded-[20px] p-5 gap-5 bg-white shadow-[0_4px_4px_0_#E0E2EF]">
          <img src="/assets/images/icons/note-purple.svg" class="w-[46px] h-[46px]" alt="icon" />
          <div>
            <p class="font-extrabold text-2xl leading-[36px]">{{ overview.total_formulir }}</p>
            <p class="text-[#838C9D]">Total Formulir</p>
          </div>
        </div>

      </div>
      <!-- <div class="flex flex-col flex-1 rounded-[20px] p-5 gap-5 bg-white shadow-[0_4px_4px_0_#E0E2EF]">
         <div class="relative flex items-center justify-center shrink-0 m-auto rounded-full w-[230px] h-[230px]">
           <div class="absolute rounded-full w-[230px] h-[230px] z-10"
             :style="`background: conic-gradient(#C2ACFF 0% ${overview.not_completed_percent}%,#662FFF ${overview.not_completed_percent}% 100%)`">
           </div>
           <div class="flex justify-center items-center w-[130px] h-[130px] rounded-full bg-white z-10">
             <p class="w-fit h-fit text-center font-bold text-lg leading-[27px]">Our<br />Rapport</p>
           </div>
         </div>
         <div class="flex flex-col gap-[10px]">
           <div class="flex items-center gap-3">
             <div class="w-5 h-5 rounded-full bg-[#662FFF]"></div>
             <p class="font-semibold text-sm leading-[21px]">Completed {{ overview.completed_percent }}%</p>
           </div>
           <div class="flex items-center gap-3">
             <div class="w-5 h-5 rounded-full bg-[#C2ACFF]"></div>
             <p class="font-semibold text-sm leading-[21px]">Not Completed {{ overview.not_completed_percent }}%</p>
           </div>
         </div>
       </div> -->
    </section>
    <div class="grid grid-cols-2 gap-[30px]">
      <!-- <Courses/>
      <Students/> -->
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import CardCourses from '../courses/card.vue'
import { adminOverview, userOverview } from '../../services/masterService'
import { getLastDocumentPreview } from '../../services/announcementService'
import { recordDocumentView } from '../../services/documentViewService'
import OpenModalPDF from '@/components/openModalPDF.vue';


const user = ref({ name: 'User' })
const overview = ref(null);
const userStats = ref({
  courses_joined: 0,
  documents_accessed: 0
})

const showModal = ref(false);
const modalRef = ref(null);
const lastCourses = ref([])
const lastDocuments = ref([])
const latestUploadedDocuments = ref([])

const formatDate = (dateStr) => {
  if (!dateStr) return '-';
  const date = new Date(dateStr);
  return date.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

async function openDetail(card) {
  if (modalRef.value && modalRef.value.openModal) {
    modalRef.value.openModal(card);
    try {
      await Promise.all([
        getLastDocumentPreview(card.id),
        recordDocumentView(card.id)
      ]);
    } catch (err) {
      console.error('Failed to log document view or update preview history:', err);
    }
  }

  console.log('openDetail', modalRef.value, card)
}


onMounted(async () => {
  try {
    // Ambil data user
    const [userRes, adminRes] = await Promise.all([
      userOverview(),
      adminOverview()
    ]);
    user.value = userRes.data.user
    userStats.value = userRes.data.stats
    overview.value = adminRes.data.stats
    latestUploadedDocuments.value = adminRes.data.latest_documents || []
    // Ambil kursus dan dokumen terakhir
    lastCourses.value = userRes.data.last_courses || []
    lastDocuments.value = userRes.data.last_documents || []

    // Ambil data overview perusahaan
  } catch (e) {
    // fallback dummy data jika gagal
    user.value = { name: 'User' }
    userStats.value = { courses_joined: 3, documents_accessed: 12 }
    lastCourses.value = [
      { id: 1, title: 'Dasar Manajemen', viewed_at: '2025-09-10' },
      { id: 2, title: 'Teknik Komunikasi', viewed_at: '2025-09-09' }
    ]
    lastDocuments.value = [
      { id: 1, title: 'Panduan SOP', accessed_at: '2025-09-10' },
      { id: 2, title: 'Formulir Pengajuan', accessed_at: '2025-09-08' }
    ]
    overview.value = {
      active_users: 189498,
      total_courses: 7221,
      video_content: 893891,
      text_content: 12812,
      completed_percent: 75,
      not_completed_percent: 25
    }
  }
})
</script>
