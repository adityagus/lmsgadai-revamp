<template>
  <!-- Top Buttons (Skeleton hanya untuk role yang dapat mengelola dokumen) -->
  <div v-if="canManageDocuments" class="flex justify-end mb-4 gap-2 min-h-[40px]">
    <template v-if="isLoading && canManageDocuments">
      <div class="w-36 h-10 bg-gray-200 rounded-lg animate-pulse"></div>
      <div class="w-24 h-10 bg-gray-200 rounded-lg animate-pulse"></div>
    </template>
    <template v-else-if="!isLoading && canManageDocuments">
      <router-link :to="{ name: 'information-document-create', query: { type: category } }"
        class="px-4 py-2 bg-green-500 text-white rounded-lg font-semibold shadow hover:bg-green-700 transition flex items-center">
        + Create &nbsp;<span>{{ category == 1 ? 'Pengumuman' : category == 2 ? 'Formulir' : 'Report' }}</span>
      </router-link>
      <router-link :to="{ name: 'archive-pengumuman', query: { submenu_id: category } }"
        class="px-4 py-2 bg-gray-200 text-sidebar rounded-lg font-semibold shadow hover:bg-gray-300 transition flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
          class="size-6">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0-3-3m3 3 3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
        </svg>
        Arsip
      </router-link>
    </template>
  </div>

  <!-- Loading Skeleton Cards (Menyesuaikan desain card asli) -->
  <div v-if="isLoading" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
    <div v-for="n in 7" :key="n"
      class="relative rounded-2xl bg-white shadow-xl p-0 flex flex-col justify-between overflow-hidden border border-gray-200 animate-pulse min-h-[220px]">
      <div class="flex flex-col gap-2 p-6">
        <div class="flex items-center gap-3 mb-3">
          <div class="rounded-lg bg-gray-200 w-12 h-12 flex-shrink-0"></div>
          <div class="h-6 bg-gray-200 rounded-md w-2/3"></div>
        </div>
        <div class="bg-gray-50 rounded-lg p-3 flex flex-col gap-2 mb-2 border border-gray-100">
          <div class="flex justify-between items-center">
            <div class="h-3 bg-gray-200 rounded w-12"></div>
            <div class="h-7 bg-gray-200 rounded w-10"></div>
          </div>
        </div>
      </div>
      <div class="w-full h-10 bg-gray-200 rounded-b-2xl"></div>
    </div>
  </div>

  <div v-else-if="loadError" role="alert" class="bg-white rounded-2xl p-8 text-center border border-gray-200 shadow-xl">
    <p class="text-red-600">{{ loadError }}</p>
    <button type="button" class="mt-4 px-4 py-2 bg-sidebar text-white rounded-lg" @click="loadAllData">Coba lagi</button>
  </div>

  <!-- Real Data Cards (Original Layout & Styling) -->
  <div v-else-if="cards && cards.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
    <div v-for="(item, idx) in cards" :key="item.title || item.name || idx"
      class="relative rounded-2xl bg-white shadow-xl p-0 flex flex-col justify-between overflow-hidden group statistik-card border border-gray-200 hover:border-sidebar transition">
      <router-link :to="`detail-pengumuman/${item.id}`" :auth="auth">
        <div class="absolute inset-0 opacity-10 pointer-events-none pattern-bg"></div>
        <div class="flex flex-col gap-2 z-10 p-6">
          <div class="flex items-center gap-3 mb-3">
            <div class="rounded-lg bg-sidebar p-2 flex items-center justify-center">
              <img :src="icons[idx % icons.length]" class="w-8 h-8 filter-white-svg" alt="icon" />
            </div>
            <span class="text-sidebar text-lg font-bold">{{ item.name }}</span>
          </div>
          <div class="bg-gray-50 rounded-lg p-3 flex flex-col gap-2 mb-2 border border-gray-100">
            <div class="flex justify-between items-center">
              <span class="text-xs font-semibold text-gray-500">Jumlah</span>
              <span class="text-2xl font-bold text-sidebar">{{ item.count_tipe_announcement ?
                item.count_tipe_announcement
                : 0 }}</span>
            </div>
          </div>
        </div>

        <button
          class="w-full flex items-center justify-center gap-2 px-3 py-2 bg-sidebar text-sm font-semibold rounded-b-2xl text-white hover:bg-purple-700 transition z-10">
          <router-link :to="`detail-pengumuman/${item.id}`" :auth="auth">
            Selengkapnya
          </router-link>
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
            class="w-4 h-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 12H6.75m4.5-4.5l4.5 4.5-4.5 4.5" />
          </svg>
        </button>
      </router-link>
    </div>
  </div>

  <!-- Empty State View -->
  <div v-else class="bg-white rounded-2xl p-12 text-center border border-gray-200 shadow-xl">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 mx-auto text-gray-300 mb-3" fill="none" viewBox="0 0 24 24"
      stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
    </svg>
    <h3 class="text-lg font-bold text-gray-700">Belum ada data {{ category == 1 ? 'Pengumuman' : category == 2 ?
      'Formulir' : 'Dokumen' }}</h3>
    <p class="text-sm text-gray-500 mt-1">Data dokumen untuk kategori ini belum tersedia.</p>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { getAnnouncements } from '@/services/queries/announcementQuery.ts';
import { useRoute } from '@/navigation';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
  category: {
    type: Number,
    default: null
  }
});

const cards = ref([]);
const isLoading = ref(true);
const loadError = ref('');
const route = useRoute();

const category = computed(() => {
  return props.category ?? (route.meta.values || 1);
});

const page = usePage();
const auth = computed(() => page.props.auth || null);
const canManageDocuments = computed(() => {
  const user = auth.value;
  return ['admin', 'sop'].includes(user?.role);
});

const icons = ref([
  'https://unpkg.com/heroicons@2.0.13/24/solid/document.svg',
  'https://unpkg.com/heroicons@2.0.13/24/solid/clipboard-document-check.svg',
  'https://unpkg.com/heroicons@2.0.13/24/solid/envelope.svg',
  'https://unpkg.com/heroicons@2.0.13/24/solid/book-open.svg',
  'https://unpkg.com/heroicons@2.0.13/24/solid/adjustments-horizontal.svg',
  'https://unpkg.com/heroicons@2.0.13/24/solid/shield-check.svg'
]);

const loadAllData = async () => {
  isLoading.value = true;
  loadError.value = '';
  try {
    const resAnnouncement = await getAnnouncements(category.value);
    cards.value = resAnnouncement?.data || resAnnouncement || [];
  } catch (error) {
    loadError.value = ['ECONNABORTED', 'ETIMEDOUT'].includes(error?.code)
      ? 'Daftar dokumen terlalu lama dimuat. Silakan coba lagi.'
      : 'Daftar dokumen gagal dimuat. Silakan coba lagi.';
    cards.value = [];
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  loadAllData();
});

watch(category, () => {
  loadAllData();
});
</script>

<style scoped>
.statistik-card {
  transition: box-shadow 0.2s, transform 0.2s;
}

.statistik-card:hover {
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
</style>
