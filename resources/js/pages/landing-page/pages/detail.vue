<template>
  <Navbar />

  <div class="bg-[#F6F7FB] min-h-screen py-32" id='details'>
    <div class="container mx-auto max-w-6xl">
      <div class="flex flex-col md:flex-row gap-8">
        <!-- Left: Course Card -->
        <div class="md:w-2/3 w-full">
          <div class="bg-white rounded-2xl shadow-lg p-6 flex flex-col md:flex-row gap-6">
            <AnimatedPlaceholder v-if="!course.thumbnail_url" width='240px' height='192px' borderRadius='12px' />
            <img :src="course.thumbnail_url" v-else alt="Course Thumbnail"
              class="w-full md:w-60 h-40 md:h-48 object-cover rounded-xl border" />
            <div class="flex-1 flex flex-col justify-between">
              <div>
                <h1 class="text-2xl md:text-3xl font-extrabold mb-2">{{ course.name || 'Online Course' }}</h1>
                <div class="flex flex-wrap items-center gap-2 mb-2">
                  <span v-if="course.category"
                    class="bg-[#662FFF]/10 text-[#662FFF] px-3 py-1 rounded-full text-xs font-semibold">{{
                      course.category.name }}</span>
                  <span v-if="course.is_popular"
                    class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs font-semibold">Popular</span>
                </div>
                <div class="text-gray-500 text-sm mb-2">{{ course.tagline }}</div>
                <div class="flex flex-wrap gap-4 text-xs text-gray-400 mb-2">
                  <span v-if="course.created_at"><i class="fa fa-globe"></i> Rilis {{ new
                    Date(course.created_at).toLocaleDateString('id-ID', {
                      year: 'numeric', month: 'long', day: 'numeric'
                    }) }}</span>
                  <span v-if="course.updated_at"><i class="fa fa-sync"></i> Update {{ new
                    Date(course.updated_at).toLocaleDateString('id-ID', {
                      year: 'numeric', month: 'long', day: 'numeric'
                    }) }}</span>
                </div>
                <p class="text-gray-700 mb-4 line-clamp-3">{{ course.description }}</p>
              </div>
              <div class="flex flex-wrap gap-4 mt-2">
                <div class="flex flex-col items-center">
                  <span class="font-bold text-lg">{{ course.students && course.students !== 'null' ?
                    course.students.length : '-' }}</span>
                  <span class="text-xs text-gray-400">Member</span>
                </div>
                <div class="flex flex-col items-center">
                  <span class="font-bold text-lg">Yes</span>
                  <span class="text-xs text-gray-400">Sertifikat</span>
                </div>
                <div class="flex flex-col items-center">
                  <span class="font-bold text-lg">All Level</span>
                  <span class="text-xs text-gray-400">Tingkatan</span>
                </div>
                <div class="flex flex-col items-center">
                  <span class="font-bold text-lg">Available</span>
                  <span class="text-xs text-gray-400">Konsultasi</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Tabs -->
          <div class="mt-8">
            <div class="flex gap-2 md:gap-4 mb-6">
              <button @click="activeTab = 'about'"
                :class="activeTab === 'about' ? 'bg-[#2D1A5A] text-white' : 'bg-gray-100 text-gray-500'"
                class="px-6 py-2 rounded-full font-semibold transition">About</button>
              <button @click="activeTab = 'lessons'"
                :class="activeTab === 'lessons' ? 'bg-[#2D1A5A] text-white' : 'bg-gray-100 text-gray-500'"
                class="px-6 py-2 rounded-full font-semibold transition">Lessons</button>
              <button @click="activeTab = 'materials'"
                :class="activeTab === 'materials' ? 'bg-[#2D1A5A] text-white' : 'bg-gray-100 text-gray-500'"
                class="px-6 py-2 rounded-full font-semibold transition">Materials</button>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow min-h-[200px]">
              <div v-if="activeTab === 'about'">
                <h3 class="text-xl font-bold mb-2">Tentang Kelas</h3>
                <p class="text-gray-700">{{ course.description }}</p>
              </div>
              <div v-if="activeTab === 'lessons'">
                <h3 class="text-xl font-bold mb-2">Lessons</h3>
                <ul class="flex flex-col gap-4" v-if="contents.length > 0">
                  <li v-for="(content, index) in contents" :key="content.id">
                    <button @click="goToContent(index)" class="w-full text-left">
                      <div
                        class="flex items-center gap-3 w-full rounded-full border p-[14px_20px] bg-[#662FFF] border-[#8661EE] shadow-[-10px_-6px_10px_0_#7F33FF_inset]">
                        <p
                          class="flex shrink-0 w-[30px] h-[30px] rounded-full items-center justify-center text-center text-[#662FFF] bg-white mr-3">
                          <span class="font-bold text-sm leading-[21px]">{{ index + 1 }}</span>
                        </p>
                        <img :src="getContentIcon(content.type)" class="w-6 h-6" alt="icon" />
                        <span
                          class="w-full font-semibold text-white line-clamp-1 transition-all duration-300 hover:line-clamp-none">
                          {{ content.title }}
                        </span>
                      </div>
                    </button>
                  </li>
                </ul>

                <!-- Empty State -->
                <div v-else class="text-center py-8">
                  <p class="text-gray-400">No content available for this course</p>
                </div>
              </div>
              <!-- <div v-if="activeTab === 'materials'">
                <h3 class="text-xl font-bold mb-2">Materi / Konten</h3>
                <ul v-if="contents && contents.length" class="list-disc ml-6">
                  <li v-for="content in contents" :key="content.id" class="mb-2">
                    <span class="font-medium">{{ content.title }}</span>
                    <span v-if="content.type === 'pdf'">
                      - <a :href="`/storage/${content.content}`" target="_blank" class="text-blue-600 underline">Lihat PDF</a>
                    </span>
                    <span v-else>
                      - {{ content.type }}
                    </span>
                  </li>
                </ul>
                <div v-else class="text-gray-400">Belum ada materi.</div>
              </div> -->
            </div>
          </div>
        </div>
        <!-- Right: Sidebar Info (optional, for future expansion) -->
        <div class="md:w-1/3 w-full flex flex-col gap-6">
          <div class="bg-white rounded-2xl shadow-lg p-6">
            <h4 class="font-bold text-lg mb-2">Info Kelas</h4>
            <ul class="text-sm text-gray-600 space-y-2">
              <li><b>Instruktur:</b> {{ course.instructor ? course.instructor.name : '-' }}</li>
              <li><b>Kategori:</b> {{ course.category ? course.category.name : '-' }}</li>
              <li><b>Popular:</b> {{ course.is_popular ? 'Ya' : 'Tidak' }}</li>
              <li><b>Rilis:</b> <span v-if="course.created_at">{{ new
                Date(course.created_at).toLocaleDateString('id-ID', {
                  year:
                    'numeric', month: 'long', day: 'numeric'
                }) }}</span></li>
              <li><b>Update:</b> <span v-if="course.updated_at">{{ new
                Date(course.updated_at).toLocaleDateString('id-ID', {
                  year:
                    'numeric', month: 'long', day: 'numeric'
                }) }}</span></li>
              rte {{ course.students }}
              <li><b>Member:</b> {{ course.students && course.students !== 'null' ? course.students.length : '-' }}</li>
            </ul>
            <button
              class="w-full py-2 px-4 mt-4 bg-[#662FFF] text-white rounded-lg hover:bg-[#7F33FF] transition duration-200">
              <router-link :to="`/student/courses/${course.id}`">
                Gabung Kelas
              </router-link>
            </button>
          </div>

        </div>
      </div>
    </div>
  </div>
  <Footer />
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from '@/navigation'
import { getCourseById } from '@/services/courseService'
import Navbar from '@/components/Navbar.vue'
import Footer from '@/components/Footer.vue'
import AnimatedPlaceholder from '../../../components/AnimatedPlaceholder.vue'

const route = useRoute()
const course = ref({})
const contents = ref([])
const activeTab = ref('about')

const getContentIcon = (type) => {
  switch (type) {
    case 'video':
      return '/assets/images/icons/video-play-white.svg';
    case 'text':
      return '/assets/images/icons/note-white.svg';
    case 'pdf':
      return '/assets/images/icons/pdf-white.svg';
    case 'finished':
      return '';
    default:
      return '/assets/images/icons/note-white.svg';
  }
};

onMounted(async () => {
  const res = await getCourseById(route.params.id)
  course.value = res.course
  contents.value = res.contents || []
})
</script>