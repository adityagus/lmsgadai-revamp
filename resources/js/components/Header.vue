<template>
  <div id="TopBar" class="flex items-center justify-between gap-2 sm:gap-4 md:gap-[30px] w-full min-w-0">
    <!-- Tombol hamburger, hanya tampil di mobile -->
    <button
      aria-label="Buka navigasi" class="md:hidden shrink-0 bg-[#060A23] p-2 rounded-lg shadow-md focus:outline-none transition duration-200 hover:scale-105"
      @click="$emit('toggleSidebar')">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
        class="w-6 h-6 sm:w-7 sm:h-7 text-white">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
      </svg>
    </button>

    <div
      class="flex items-center flex-1 max-w-[450px] rounded-full border border-[#CFDBEF] gap-2 px-3 sm:px-5 transition-all duration-300 focus-within:ring-2 focus-within:ring-[#662FFF] min-w-0">
      <button
        class="appearance-none outline-none w-full py-2 sm:py-3 font-semibold text-xs sm:text-sm text-[#838C9D] text-left truncate"
        @click="openSearch" placeholder="Search course, student, other file...">
        <span class="font-semibold text-[#838C9D] text-xs sm:text-sm">Pencarian...</span>
      </button>
      <img src="/assets/images/icons/search-normal.svg" class="w-5 h-5 sm:w-6 sm:h-6 shrink-0" alt="icon" />
    </div>

    <!-- Modal Search Custom -->
    <div v-if="showSearch" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-3"
      @click="closeSearch">
      <div
        class="bg-white rounded-2xl shadow-2xl w-full max-w-[500px] max-h-[90vh] relative flex flex-col overflow-hidden"
        @click.stop>
        <!-- Header -->
        <div class="flex items-center justify-between p-6 border-b border-gray-100">
          <h3 class="text-lg font-semibold text-gray-800">Cari</h3>
          <button @click="closeSearch" class="text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <!-- Search Input -->
        <div class="p-6 pb-2">
          <div class="relative">
            <input ref="searchInput" v-model="searchQuery" @input="handleSearch" type="text"
              placeholder="Ketik nama kelas, dokumen, kategori, atau deskripsi..."
              class="w-full px-4 py-3 pl-12 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
              autofocus />
            <svg class="absolute left-4 top-3.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
              viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z">
              </path>
            </svg>
          </div>
        </div>

        <!-- Tabs -->
        <div class="px-6 pt-2 pb-4">
          <div class="flex gap-2 border-b border-gray-200 mb-2">
            <button
              :class="['px-4 py-2 font-semibold', searchTab === 'kelas' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500']"
              @click="searchTab = 'kelas'">Kelas</button>
            <button
              :class="['px-4 py-2 font-semibold', searchTab === 'informasi' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500']"
              @click="searchTab = 'informasi'">Informasi</button>
          </div>
        </div>

        <!-- Search Results -->
        <div class="px-6 pb-6">
          <!-- Loading State -->
          <div v-if="isSearching" class="flex items-center justify-center py-8">
            <div class="flex items-center space-x-2 text-gray-500">
              <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
              </svg>
              <span>Mencari...</span>
            </div>
          </div>

          <!-- Results: Courses -->
          <div v-else-if="searchTab === 'kelas' && searchResultsCourse.length > 0"
            class="space-y-3 max-h-72 overflow-y-auto">
            <div v-for="item in searchResultsCourse" :key="'course-' + item.id"
              class="flex items-center gap-4 p-4 rounded-xl hover:bg-gray-50 cursor-pointer transition-colors border border-gray-100 hover:border-blue-200"
              @click="gotoDetail(item)">
              <div class="flex-shrink-0">
                <img :src="item.thumbnail_url" alt="Course thumbnail"
                  class="w-16 h-16 object-cover rounded-lg shadow-sm" v-if="item.thumbnail_url" />
                <div v-else class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center">
                  <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                    </path>
                  </svg>
                </div>
              </div>

              <div class="flex-1 min-w-0">
                <h4 class="font-semibold text-gray-900 truncate">{{ item.title }}</h4>
                <p class="text-sm text-gray-500 line-clamp-2 mt-1">{{ item.tagline }}</p>
                <div class="flex items-center mt-2">
                  <span
                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    {{ item.category || 'Uncategorized' }}
                  </span>
                </div>
              </div>

              <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
              </div>
            </div>
          </div>

          <!-- Results: Documents -->
          <div v-else-if="searchTab === 'informasi' && searchResultsDocument.length > 0"
            class="space-y-3 max-h-72 overflow-y-auto">
            <div v-for="item in searchResultsDocument" :key="'doc-' + item.id"
              class="flex items-center gap-4 p-4 rounded-xl hover:bg-gray-50 cursor-pointer transition-colors border border-gray-100 hover:border-blue-200"
              @click="openDocumentDetail(item)">
              <div class="flex-shrink-0">
                <img :src="item.thumbnail_url" alt="Document thumbnail"
                  class="w-16 h-16 object-cover rounded-lg shadow-sm fill-[#662FFF]" v-if="item.thumbnail_url" />
                <div v-else class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center">
                  <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h10M7 11h10M7 15h6">
                    </path>
                  </svg>
                </div>
              </div>
              <div class="flex-1 min-w-0">
                <h4 class="font-semibold text-gray-900">{{ item.title }}</h4>
                <p class="text-sm text-gray-500 line-clamp-2 mt-1">{{ item.tagline }}</p>
                <div class="flex items-center mt-2">
                  <span
                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    {{ item.category || 'Uncategorized' }}
                  </span>
                </div>
              </div>
              <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else-if="searchQuery && !isSearching" class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0118 12M6 20.291A7.962 7.962 0 016 12m0 8.291A7.962 7.962 0 0118 20.291M6 20.291A7.962 7.962 0 006 12.291">
              </path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada hasil</h3>
            <p class="mt-1 text-sm text-gray-500">Coba gunakan kata kunci yang berbeda</p>
          </div>

          <!-- Default State -->
          <div v-else-if="!searchQuery" class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z">
              </path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Cari</h3>
            <p class="mt-1 text-sm text-gray-500">Mulai ketik untuk mencari kelas atau informasi</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Course Detail -->
    <div v-if="showModal && selectedCourse"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
      <div class="bg-white rounded-xl shadow-lg p-6 w-[350px] relative">
        <button @click="closeModal"
          class="absolute top-2 right-2 text-gray-400 hover:text-gray-700 text-xl font-bold">&times;</button>
        <img :src="selectedCourse.thumbnail_url" alt="cover" class="w-20 h-20 object-cover rounded mb-3"
          v-if="selectedCourse.thumbnail_url" />
        <h2 class="font-bold text-lg mb-1">{{ selectedCourse.title }}</h2>
        <p class="text-sm text-gray-500 mb-2">{{ selectedCourse.tagline }}</p>
        <p class="mb-2">{{ selectedCourse.description }}</p>
        <span class="inline-block px-3 py-1 rounded bg-blue-100 text-blue-700 text-xs">{{ selectedCourse.category
        }}</span>
      </div>
    </div>
    <!-- Modal Document Detail pakai komponen openModalPdf -->
    <openModalPdf ref="modalRef" :cards="[selectedDocument]" v-model:showModal="showModal"
      v-model:selectedCard="selectedDocument" @close="closeModal" />
    <!-- End: Modal Search Algolia Style -->

    <div class="relative flex items-center justify-end gap-2 sm:gap-[14px] shrink-0 group">
      <div class="text-right hidden sm:block">
        <p class="font-semibold text-xs sm:text-base">{{ nama }}</p>
        <p class="text-[11px] sm:text-sm leading-[18px] sm:leading-[21px] text-[#838C9D]">{{ jabatan }}</p>
      </div>
      <button type="button" id="profileButton"
        class="flex shrink-0 w-9 h-9 sm:w-[50px] sm:h-[50px] rounded-full overflow-hidden border border-purple-100 shadow-sm">
        <img src="/assets/images/icons/profile.png" class="w-full h-full object-cover" alt="profile photos" />
      </button>
      <div id="ProfileDropdown" class="absolute top-full right-0 hidden group-hover:block z-50">
        <ul
          class="flex flex-col w-[180px] sm:w-[200px] rounded-[20px] border border-[#CFDBEF] p-4 sm:p-5 gap-3 sm:gap-4 bg-white mt-2 shadow-xl">
          <li class="sm:hidden">
            <p class="font-semibold text-xs sm:text-base">{{ nama }}</p>
            <p class="text-[11px] sm:text-sm leading-[18px] sm:leading-[21px] text-[#838C9D]">{{ jabatan }}</p>
          </li>
          <li class="font-semibold text-xs sm:text-base">
            <a href="#">My Account</a>
          </li>
          <li class="font-semibold text-xs sm:text-base">
            <a href="#">Settings</a>
          </li>
          <li class="font-semibold text-xs sm:text-base">
            <a href="#" @click.prevent="logout">Logout</a>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, nextTick, onMounted } from 'vue';
import { useRoute, useRouter } from '@/navigation';
import { usePage, router as inertiaRouter } from '@inertiajs/vue3';
import { getSession } from '@/services/queries/authQuery.ts';
import { signOut } from '@/services/mutations/authMutation.ts';
import { searchContent } from '@/services/queries/courseQuery.ts';
import openModalPdf from './openModalPDF.vue'
import Swal from 'sweetalert2';

const showSearch = ref(false);
const showModal = ref(false);
const selectedCourse = ref(null);
const selectedDocument = ref(null);
const modalRef = ref(null);
const router = useRouter();
const route = useRoute();
const page = usePage();
const nama = ref(page.props.auth?.user || '');
const jabatan = ref(page.props.auth?.jabatan || '');
const searchResultsCourse = ref([]);
const searchResultsDocument = ref([]);
const isSearching = ref(false);
const searchQuery = ref('');
const searchInput = ref(null);
const searchTab = ref('kelas');

// Cek route berdasarkan name



onMounted(async () => {
  try {
    const [resAuth] = await Promise.all([
      getSession()
    ]);
    const auth = resAuth.auth
    console.log('session', auth);
    nama.value = auth.user || '';
    jabatan.value = auth.jabatan || '';
  } catch (error) {
    console.error('Error fetching session:', error);
  }


});

// Debounce function untuk search
let searchTimeout;
async function handleSearch() {
  const query = searchQuery.value.trim();

  clearTimeout(searchTimeout);

  if (query.length < 2) {
    searchResultsCourse.value = [];
    searchResultsDocument.value = [];
    return;
  }

  searchTimeout = setTimeout(async () => {
    isSearching.value = true;
    try {
      const response = await searchContent(encodeURIComponent(query));
      if (response.data && response.data.success !== false) {
        searchResultsCourse.value = response.data.courses || response.data.data?.courses || [];
        searchResultsDocument.value = response.data.documents || response.data.data?.documents || [];
        console.log('Search results:', response.data);
      } else {
        searchResultsCourse.value = [];
        searchResultsDocument.value = [];
      }
    } catch (error) {
      console.error('Search error:', error);
      searchResultsCourse.value = [];
      searchResultsDocument.value = [];
    } finally {
      isSearching.value = false;
    }
  }, 300);
}

function openSearch() {
  showSearch.value = true;
  searchResultsCourse.value = [];
  searchResultsDocument.value = [];
  searchQuery.value = '';
  searchTab.value = 'kelas';
  nextTick(() => {
    if (searchInput.value) {
      searchInput.value.focus();
    }
  });
}

function closeSearch() {
  showSearch.value = false;
  searchResultsCourse.value = [];
  searchResultsDocument.value = [];
  searchQuery.value = '';
}

function gotoDetail(course) {
  // Clear cache sebelum navigate untuk mendapatkan data terbaru
  if (window.queryClient) {
    window.queryClient.invalidateQueries({ queryKey: ['course', course.id] });
    window.queryClient.invalidateQueries({ queryKey: ['courses'] });
  }

  // If already on the same route, force reload
  if (route.path === `/student/kelas/${course.id}`) {
    router.replace({ path: `/student/kelas/${course.id}`, query: { t: Date.now() } });
  } else {
    router.push(`/student/kelas/${course.id}`);
  }
  closeSearch();
}

function openDocumentDetail(document) {
  console.log('openDocumentDetail', document);
  selectedDocument.value = document;
  selectedCourse.value = null;
  showModal.value = true;

  closeSearch();
}

function logout() {
  inertiaRouter.post('/logout');
}

function closeModal() {
  showModal.value = false;
  selectedCourse.value = null;
  selectedDocument.value = null;
}

</script>

<style scoped>
.announcement-card {
  transition: box-shadow 0.2s, transform 0.2s;
}

.announcement-card:hover {
  box-shadow: 0 8px 32px 0 rgba(127, 51, 255, 0.37), 0 1.5px 6px 0 #7F33FF;
  transform: translateY(-2px) scale(1.03);
  border-color: #7F33FF;
}

.pattern-bg {
  background-image: url('https://www.toptal.com/designers/subtlepatterns/uploads/dot-grid.png');
  background-size: 40px 40px;
}

.bg-sidebar {
  background: #7F33FF;
}

.filter-white-svg {
  filter: brightness(0) invert(1);
}

.line-clamp-2 {

  /* Untuk satu baris clamp nomor surat */
  .line-clamp-1 {
    display: -webkit-box;
    line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  display: -webkit-box;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Hide scrollbar utility */
.scrollbar-hidden {
  scrollbar-width: none;
  /* Firefox */
  -ms-overflow-style: none;
  /* IE 10+ */
}

.scrollbar-hidden::-webkit-scrollbar {
  display: none;
  /* Chrome/Safari/Webkit */
}
</style>
