<template>
  <!-- Modal Detail Card -->
  <OpenModalPDF ref="modalRef" v-model:showModal="showModal" v-model:selectedCard="selectedCard" />

  <div class='flex gap-2 items-center -mb-4 text-gray-400 text-xs'>
    <router-link to='/pengumuman' class='flex gap-2 items-center text-gray-400 text-xs cursor-pointer'>
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
        class="w-4 h-4 text-sidebar">
        <path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5m7-7l-7 7 7 7" />
      </svg>
      Back to Pengumuman
    </router-link>
  </div>

  <!-- Skeleton Loading State for Detail Page (Presisi 100% dengan Layout Asli) -->
  <div v-if="isLoading" class="space-y-4 mt-6">
    <!-- Title Skeleton -->
    <div class="h-8 bg-gray-200 rounded-lg w-64 animate-pulse mb-4"></div>

    <!-- Filter & View Mode Skeleton -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
      <div class="flex flex-wrap gap-2">
        <div class="w-12 h-7 bg-gray-200 rounded-lg animate-pulse"></div>
        <div class="w-16 h-7 bg-gray-200 rounded-lg animate-pulse"></div>
        <div class="w-16 h-7 bg-gray-200 rounded-lg animate-pulse"></div>
        <div class="w-16 h-7 bg-gray-200 rounded-lg animate-pulse"></div>
      </div>
      <div class="flex gap-2 shrink-0 self-end sm:self-auto">
        <div class="w-16 h-7 bg-gray-200 rounded-lg animate-pulse"></div>
        <div class="w-16 h-7 bg-gray-200 rounded-lg animate-pulse"></div>
      </div>
    </div>

    <!-- Skeleton Cards Grid (Desain, Badge & Tombol Absolute Presisi dengan Card Asli) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
      <div v-for="n in 8" :key="n"
        class="relative rounded-2xl bg-white shadow-xl p-0 flex flex-col justify-between overflow-hidden border border-gray-200 animate-pulse min-h-[260px]">
        <!-- Badge Icon Top Right Skeleton -->
        <div class="rounded-tr-lg rounded-bl-3xl bg-gray-200 w-12 h-12 absolute -top-1 -right-0"></div>

        <div class="flex flex-col gap-2 z-10 p-6">
          <!-- Title Placeholder -->
          <div class="h-6 bg-gray-200 rounded-md w-3/4 mt-3 mb-3"></div>

          <!-- Info Box Placeholder (Nomor Surat & Tanggal Berlaku) -->
          <div class="bg-gray-50 rounded-lg p-3 flex flex-col gap-2 mb-2 border border-gray-100 space-y-2">
            <div class="flex justify-between items-center">
              <div class="h-3 bg-gray-200 rounded w-20"></div>
              <div class="h-3 bg-gray-200 rounded w-24"></div>
            </div>
            <div class="flex justify-between items-center">
              <div class="h-3 bg-gray-200 rounded w-20"></div>
              <div class="h-3 bg-gray-200 rounded w-16"></div>
            </div>
          </div>

          <!-- Terakhir Update Placeholder -->
          <div class="h-3 bg-gray-200 rounded w-36 mb-16"></div>
        </div>

        <!-- Action Buttons Bottom Absolute Skeleton (Edit & Archive) -->
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex gap-4 mt-2 justify-center items-center w-max">
          <div class="w-20 h-8 bg-gray-200 rounded-full"></div>
          <div class="w-24 h-8 bg-gray-200 rounded-full"></div>
        </div>
      </div>
    </div>
  </div>

  <div v-else-if="pageError" role="alert" class="mt-6 bg-white rounded-2xl p-8 text-center border border-gray-200">
    <p class="text-red-600">{{ pageError }}</p>
    <button type="button" class="mt-4 px-4 py-2 bg-sidebar text-white rounded-lg" @click="loadPage">Coba lagi</button>
  </div>

  <!-- Real Content when loaded -->
  <template v-else>
    <h1 class="text-xl sm:text-2xl font-bold text-sidebar mt-4 sm:mt-2 mb-3 sm:mb-4">{{ detail.name }}</h1>
    <!-- Baris filter area dan layout menu -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 sm:gap-3 mb-4 max-w-full">
      <!-- Filter Area Tabs -->
      <div
        class="flex items-center gap-1.5 sm:gap-2 overflow-x-auto max-w-full pb-1 sm:pb-0 scrollbar-hidden flex-1 flex-nowrap sm:flex-wrap touch-pan-x"
        style="-webkit-overflow-scrolling: touch;"
      >
        <!-- START: AREA TAB STATIC -->
        <button v-for="tab in memoTabs" :key="tab.kd_wilayah" @click="activeMemoTab = tab.kd_wilayah"
          :disabled="tab.kd_wilayah !== 'all' && (nextDocumentPage !== null || areasLoading || areasError)"
          :title="nextDocumentPage !== null ? 'Filter tersedia setelah semua dokumen dimuat' : ''"
          :class="['px-3 py-1.5 rounded-lg font-semibold text-xs transition cursor-pointer whitespace-nowrap shrink-0', activeMemoTab === tab.kd_wilayah ? 'bg-sidebar text-white shadow' : 'bg-white text-sidebar hover:bg-purple-100']">
          <span>{{ tab.nm_wilayah || (tab.kd_wilayah === 'all' ? 'All' : tab.kd_wilayah) }}</span>
        </button>

        <!-- END: AREA TAB STATIC -->
      </div>
      <!-- Layout Menu Icon -->
      <div class="flex gap-2 shrink-0 self-end sm:self-auto">
        <button @click="viewMode = 'card'"
          :class="['px-3 py-1.5 rounded-lg font-semibold text-xs transition flex items-center gap-1 cursor-pointer whitespace-nowrap shrink-0', viewMode === 'card' ? 'bg-sidebar text-white shadow' : 'bg-white text-sidebar hover:bg-purple-100']">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <rect x="3" y="3" width="7" height="7" rx="1.5" stroke-width="2" />
            <rect x="14" y="3" width="7" height="7" rx="1.5" stroke-width="2" />
            <rect x="14" y="14" width="7" height="7" rx="1.5" stroke-width="2" />
            <rect x="3" y="14" width="7" height="7" rx="1.5" stroke-width="2" />
          </svg>
          Card
        </button>
        <button @click="viewMode = 'table'"
          :class="['px-3 py-1.5 rounded-lg font-semibold text-xs transition flex items-center gap-1 cursor-pointer whitespace-nowrap shrink-0', viewMode === 'table' ? 'bg-sidebar text-white shadow' : 'bg-white text-sidebar hover:bg-purple-100']">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <rect x="3" y="6" width="18" height="2" rx="1" stroke-width="2" />
            <rect x="3" y="11" width="18" height="2" rx="1" stroke-width="2" />
            <rect x="3" y="16" width="18" height="2" rx="1" stroke-width="2" />
          </svg>
          Table
        </button>
      </div>
    </div>

    <div v-if="viewMode === 'card'" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
      <div v-for="(item, idx) in filteredCards" :key="item.id || idx"
        class="relative rounded-2xl bg-white shadow-xl p-0 flex flex-col justify-between overflow-hidden group announcement-card border border-gray-200 hover:border-sidebar transition cursor-pointer"
        @click="openDetail(item)">
        <div class="absolute inset-0 opacity-10 pointer-events-none pattern-bg"></div>
        <div class="flex flex-col gap-2 z-10 p-6">
          <div class="flex items-center gap-3 mb-3">
            <div
              class="rounded-tr-lg rounded-bl-3xl bg-sidebar p-2 absolute -top-1 -right-0 flex items-center justify-center flex-shrink-0">
              <img :src="detail.icon" class="w-8 h-8 filter-white-svg" alt="icon" />
              <!-- <img :src="item.icon" class="w-8 h-8 filter-white-svg" alt="icon" /> -->
            </div>
            <span
              class="text-sidebar text-lg font-bold transition-all duration-300 mt-3 hover:line-clamp-none cursor-pointer"
              :title="item.title">{{ item.title }}</span>
          </div>
          <div class="bg-gray-50 rounded-lg p-3 flex flex-col gap-2 mb-2 border border-gray-100">
            <div class="flex justify-between items-center">
              <span class="text-xs font-semibold text-gray-500">Nomor Surat</span>
              <span class="text-xs font-bold text-sidebar line-clamp-1 relative group">
                {{ item.no_surat }}
                <span v-if="item.no_surat && item.no_surat.length > 20"
                  class="absolute left-0 top-full mt-1 w-max bg-white border border-gray-300 shadow-lg rounded px-2 py-1 text-xs text-sidebar opacity-0 group-hover:opacity-100 transition pointer-events-none z-30">
                  {{ item.no_surat }}
                </span>
              </span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-xs font-semibold text-gray-500">Tanggal Berlaku</span>
              <span class="text-xs font-bold text-sidebar">{{ item.tgl_berlaku }}</span>
            </div>
          </div>
          <div class="text-xs text-gray-500 mb-16">Terakhir update: <br><span class="font-semibold text-sidebar">{{
            item.dateLastUpdate
              }}</span></div>
          <!-- Tombol aksi di tengah bawah -->
          <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex gap-4 mt-2 justify-center items-center w-max"
            v-if="canManageDocuments">
            <router-link :to="{ name: 'information-document-update', params: { id: item.id } }" @click.stop
              class="bg-white rounded-full px-4 py-2 shadow hover:bg-purple-100 transition group/edit flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                stroke="currentColor" class="w-5 h-5 text-sidebar group-hover/edit:text-purple-700">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M16.862 3.487a2.1 2.1 0 1 1 2.97 2.97L8.91 17.38a2.1 2.1 0 0 1-.88.53l-3.07.92a.525.525 0 0 1-.65-.65l.92-3.07a2.1 2.1 0 0 1 .53-.88L16.862 3.487z" />
              </svg>
              <span class="text-xs font-semibold text-sidebar">Edit</span>
            </router-link>
            <button @click.stop="deleteAnnouncement(item)"
              class="bg-red-100 rounded-full px-4 py-2 shadow hover:bg-red-200 transition group/delete flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                stroke="currentColor" class="w-5 h-5 text-red-600 group-hover/delete:text-red-800">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
              <span class="text-xs font-semibold text-red-600">Archive</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Table/List Layout -->
    <div v-else>
      <div class="datatable bg-white mb-16">
        <vue3-datatable :rows="tableRows" :columns="tableCols" :totalRows="tableRows.length" :search="search"
          :sortable="true" :selectRowOnClick="true" @row-click='handleRowClick'>
          <template #tglBerlakuRaw="data">
            <span>{{ data.value.tglBerlakuDisplay }}</span>
          </template>
          <template #lastUpdateRaw="data">
            <span>{{ data.value.lastUpdateDisplay }}</span>
          </template>
          <template #action="{ row, value }">
            <div class="flex gap-2 justify-center items-center" v-if="canManageDocuments">
              <button @click.stop="router.push({ name: 'information-document-update', params: { id: value.id } })"
                class="bg-white border border-sidebar text-sidebar px-2 py-1 rounded shadow hover:bg-purple-100 transition text-xs font-semibold inline-flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                  stroke="currentColor" class="w-3 h-3">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M16.862 3.487a2.1 2.1 0 1 1 2.97 2.97L8.91 17.38a2.1 2.1 0 0 1-.88.53l-3.07.92a.525.525 0 0 1-.65-.65l.92-3.07a2.1 2.1 0 0 1 .53-.88L16.862 3.487z" />
                </svg>
                Edit
              </button>
              <button @click.stop="deleteAnnouncementFromTable(value.id)"
                class="bg-red-100 border border-red-300 text-red-600 px-2 py-1 rounded shadow hover:bg-red-200 transition text-xs font-semibold inline-flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                  stroke="currentColor" class="w-3 h-3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Archive
              </button>
            </div>
          </template>
        </vue3-datatable>
      </div>
    </div>
  </template>
</template>

<script setup>
import { softDeleteAnnouncement } from '@/services/mutations/announcementMutation.ts';
import OpenModalPDF from '@/components/openModalPDF.vue';
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from '@/navigation'
import Vue3Datatable from '@bhplugin/vue3-datatable';
import { getDetailAnnouncement, getLastDocumentPreview, getAnnouncementRegions } from '@/services/queries/announcementQuery.ts';
import { usePage } from '@inertiajs/vue3';
import { getWilayah } from '@/services/queries/masterQuery.ts';
import { recordDocumentView } from '@/services/mutations/documentViewMutation.ts';
import Swal from 'sweetalert2';

const route = useRoute();
const router = useRouter();

const detail = ref({});
const title = ref({})
const icon = ref({})
const memoTabs = ref([{ kd_wilayah: 'all', nm_wilayah: 'All', kd_cabang: 'All' }]);
const activeMemoTab = ref('all')
const modalRef = ref(null);
const page = usePage();
const auth = computed(() => page.props.auth || null);
const isLoading = ref(true);
const loadError = ref('');
const areasLoading = ref(false);
const areasError = ref(false);
const documentRegions = ref({});
const nextDocumentPage = ref(null);
const remainingLoading = ref(false);
const remainingError = ref(false);
const pageError = computed(() => loadError.value || (areasError.value
  ? 'Tab wilayah gagal dimuat. Silakan coba lagi.'
  : remainingError.value ? 'Sebagian dokumen gagal dimuat. Silakan coba lagi.' : ''));


const cards = ref([]);
// const cards = ref([
//   { title: 'Title text memo ...', count: 0, icon: 'https://unpkg.com/heroicons@2.0.13/24/solid/document.svg', desc: 'Memo text detail ...', date: '02.08.2025', type: 'text', nomorSurat: '001/MEMO/2025', tanggalBerlaku: '02.08.2025' },
//   { title: 'Title voice memo ...', count: 0, icon: 'https://unpkg.com/heroicons@2.0.13/24/solid/envelope.svg', desc: 'Memo voice detail ...', date: '02.08.2025', type: 'voice', nomorSurat: '002/VOICE/2025', tanggalBerlaku: '02.08.2025' },
//   { title: 'Title draw memo ...', count: 0, icon: 'https://unpkg.com/heroicons@2.0.13/24/solid/book-open.svg', desc: 'Memo draw detail ...', date: '02.08.2016', type: 'draw', nomorSurat: '003/DRAW/2016', tanggalBerlaku: '02.08.2016' },
//   { title: 'Title text memo ...', count: 0, icon: 'https://unpkg.com/heroicons@2.0.13/24/solid/document.svg', desc: 'Memo text detail ...', date: '02.08.2016', type: 'text', nomorSurat: '004/MEMO/2016', tanggalBerlaku: '02.08.2016' },
//   { title: 'Title voice memo ...', count: 0, icon: 'https://unpkg.com/heroicons@2.0.13/24/solid/envelope.svg', desc: 'Memo voice detail ...', date: '02.08.2016', type: 'voice', nomorSurat: '005/VOICE/2016', tanggalBerlaku: '02.08.2016' },
//   { title: 'Title draw memo ...', count: 0, icon: 'https://unpkg.com/heroicons@2.0.13/24/solid/book-open.svg', desc: 'Memo draw detail ...', date: '02.08.2016', type: 'draw', nomorSurat: '006/DRAW/2016', tanggalBerlaku: '02.08.2016' },
// ])

const formatWilayahName = (name) => {
  if (!name) return '';
  const lower = name.toLowerCase();
  if (lower === 'all') return 'All';
  if (lower === 'jakarta') return 'Jaya';
  if (lower === 'jawa barat') return 'Jabar';
  if (lower === 'kepri') return 'Kepri';

  return name.split(' ')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
    .join(' ');
};

const filteredCards = computed(() => {
  const currentTabVal = activeMemoTab.value;
  const allCards = cards.value || [];

  if (currentTabVal === 'all') return allCards;

  const selectedTab = memoTabs.value.find(t => String(t.kd_wilayah) === String(currentTabVal));
  if (!selectedTab) return allCards;

  const validBranchCodes = Array.isArray(selectedTab.branches) ? selectedTab.branches.map(b => String(b)) : [];

  return allCards.filter(card => {
    const regions = documentRegions.value[card.id] || card.document_regional || [];
    // Documents without specific regional restriction apply to all areas
    if (!Array.isArray(regions) || regions.length === 0) {
      return true;
    }

    return regions.some(dr => {
      const regId = dr.regional_id ? String(dr.regional_id).trim() : '';
      if (!regId) return true;

      // 1. Direct match with company_id (kd_wilayah)
      if (regId === String(selectedTab.kd_wilayah)) return true;

      // 2. Match with branch_code in table master.branch
      if (validBranchCodes.includes(regId)) return true;

      // 3. Match 2nd char of 4-digit code e.g. "0201" -> '2'
      if (regId.length >= 2 && regId.charAt(1) === String(selectedTab.kd_wilayah)) return true;

      // 4. Substring match
      if (regId.includes(String(selectedTab.kd_wilayah))) return true;

      return false;
    });
  });
});

const search = ref('');

const isAdmin = computed(() =>
  auth.value &&
  (
    ['admin', 'sop'].includes(auth.value.role)
  )
);

const canManageDocuments = computed(() =>
  isAdmin.value && !['JBT-019', 'JBT-020'].includes(auth.value?.idgrup)
);

const tableCols = computed(() => {
  const cols = [
    { field: 'id', title: 'ID', isUnique: true },
    { field: 'title', title: 'Judul' },
    { field: 'nomorSurat', title: 'Nomor Surat' },
    { field: 'tglBerlakuRaw', title: 'Tanggal Berlaku', cellClass: 'text-center', headerClass: 'text-center justify-center' },
    { field: 'lastUpdateRaw', title: 'Terakhir Diperbarui' }
  ];
  if (canManageDocuments.value) {
    cols.push({
      field: 'action',
      title: 'Aksi',
      sortable: false,
      type: 'html', // Tambahkan type html untuk render HTML content
      width: '180px'
    });
  }
  return cols;
});

const formatDateDDMMYYYY = (dateStr) => {
  if (!dateStr || dateStr === '-') return '-';
  if (typeof dateStr === 'string' && /^\d{2}-\d{2}-\d{4}$/.test(dateStr.trim())) return dateStr.trim();
  const date = new Date(dateStr);
  if (isNaN(date.getTime())) return dateStr;
  const day = String(date.getDate()).padStart(2, '0');
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const year = date.getFullYear();
  return `${day}-${month}-${year}`;
};

const formatDateTimeWib = (dateStr) => {
  if (!dateStr || dateStr === '-') return '-';
  if (typeof dateStr === 'string' && dateStr.includes('WIB')) return dateStr;
  const date = new Date(dateStr);
  if (isNaN(date.getTime())) return dateStr;
  const day = String(date.getDate()).padStart(2, '0');
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const year = date.getFullYear();
  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');
  return `${day}-${month}-${year} ${hours}:${minutes} WIB`;
};

const tableRows = computed(() => {
  console.log('Generating tableRows from filteredCards:', filteredCards.value);
  return filteredCards.value.map((item, index) => {
    // Raw YYYY-MM-DD format used for true chronological sorting (ASC / DESC)
    const rawTglBerlakuIso = item.tgl_berlaku || item.tgl_berlaku_raw || '';
    const rawLastUpdateIso = item.updated_at || item.created_at || '';

    return {
      id: item.id || item.id_pengumuman || item.submenu_id || index,
      title: item.title,
      nomorSurat: item.no_surat,
      tglBerlakuRaw: rawTglBerlakuIso,
      tglBerlakuDisplay: formatDateDDMMYYYY(item.tgl_berlaku_formatted || item.tgl_berlaku),
      lastUpdateRaw: rawLastUpdateIso,
      lastUpdateDisplay: formatDateTimeWib(item.dateLastUpdate || item.updated_at || item.created_at)
    };
  });
});


async function openDetail(card) {
  console.log('Opening detail for card:', card);
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

function openDetailById(id) {
  console.log('openDetailById called with id:', id);
  console.log('filteredCards:', filteredCards.value);
  const card = filteredCards.value.find(item => item.id === id);
  console.log('Found card:', card);
  if (card) openDetail(card);
}

function handleRowClick(row) {
  console.log('Row clicked:', row);
  const card = filteredCards.value.find(item => item.id === row.id);
  if (card) {
    openDetail(card);
  }
}

const deleteAnnouncement = (item) => {
  // archive

  Swal.fire({
    title: "Are you sure archive?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Yes, archive it!"
  }).then(async (result) => {
    if (result.isConfirmed) {
      // Soft delete (archive)
      softDeleteAnnouncement(item.id_pengumuman || item.id)
        .then(() => {
          // Hapus dari list
          cards.value = cards.value.filter(c => (c.id_pengumuman || c.id) !== (item.id_pengumuman || item.id));
          Swal.fire({
            title: "Archived!",
            text: "Content has been archived.",
            icon: "success",
            timer: 1500
          });
        });
    }
  })
    .catch(() => {
      Swal.fire({
        title: "Error!",
        text: "Failed to archive data!",
        icon: "error",
        timer: 1500
      });
    });
}

const deleteAnnouncementFromTable = (id) => {
  console.log('deleteAnnouncementFromTable called with id:', id);

  // Cari item berdasarkan id dari filteredCards
  const item = filteredCards.value.find(card => {
    const cardId = card.id || card.id_pengumuman || card.submenu_id;
    return cardId == id;
  });

  if (item) {
    // Gunakan function deleteAnnouncement yang sudah ada
    deleteAnnouncement(item);
  } else {
    console.error('Item not found for deletion:', id);
    Swal.fire({
      title: "Error!",
      text: "Item not found!",
      icon: "error",
      timer: 1500
    });
  }
}

const showModal = ref(false)
const selectedCard = ref(null)
const viewMode = ref('card'); // 'card' atau 'table'

// Prevent background scroll when modal is open
watch(showModal, (val) => {
  if (val) {
    document.body.classList.add('overflow-hidden');
  } else {
    document.body.classList.remove('overflow-hidden');
  }
});



async function loadAreas() {
  if (areasLoading.value) return;
  areasLoading.value = true;
  areasError.value = false;
  try {
    const [areas, regions] = await Promise.all([getWilayah(), getAnnouncementRegions(route.params.id)]);
    documentRegions.value = regions;
    memoTabs.value = [{ kd_wilayah: 'all', nm_wilayah: 'All', kd_cabang: 'All' }, ...areas];
  } catch {
    areasError.value = true;
  } finally {
    areasLoading.value = false;
  }
}

async function loadDocuments() {
  loadError.value = '';
  try {
    const result = await getDetailAnnouncement(route.params.id, 1);
    nextDocumentPage.value = result.next_page ?? null;
    let items = [];
    if (Array.isArray(result)) {
      items = result;
      detail.value = result[0] || {};
    } else {
      items = result.items || [];
      detail.value = result.detail || result;
      title.value = result.title || 'Detail Pengumuman';
      icon.value = result.icon || 'https://unpkg.com/heroicons@2.0.13/24/solid/document.svg';
    }
    // Mapping agar field sesuai dengan template
    cards.value = items.map(item => ({
      ...item,
      id: item.id || item.id_pengumuman || item.submenu_id, // pastikan ada ID
      nomorSurat: item.nomorSurat || item.nomor_surat || item.nomor || '-',
      tanggalBerlaku: item.tgl_berlaku || '-',
      date: item.dateLastUpdate || '-',
      title: item.title || item.judul || '-',
      icon: item.icon || '/default-icon.svg',
      desc: item.desc || item.keterangan || item.deskripsi || '',
      type: item.type,
    }));
    // Untuk detail utama, mapping juga
    detail.value = {
      ...detail.value,
      name: detail.value.name || detail.value.title || detail.value.judul || '-',
      nomorSurat: detail.value.nomorSurat || detail.value.nomor_surat || detail.value.nomor || '-',
      tanggalBerlaku: detail.value.tanggalBerlaku || detail.value.tanggal_berlaku || detail.value.tanggal || '-',
      date: detail.value.dateLastUpdate ? detail.value.dateLastUpdate : '-',
      desc: detail.value.desc || detail.value.keterangan || detail.value.deskripsi || '',
    };
    await loadRemainingDocuments();
  } catch (error) {
    loadError.value = ['ECONNABORTED', 'ETIMEDOUT'].includes(error?.code)
      ? 'Daftar dokumen terlalu lama dimuat. Silakan coba lagi.'
      : 'Daftar dokumen gagal dimuat. Silakan coba lagi.';
  }
}

async function loadRemainingDocuments() {
  if (remainingLoading.value) return;
  remainingLoading.value = true;
  remainingError.value = false;
  try {
    while (nextDocumentPage.value !== null) {
      const result = await getDetailAnnouncement(route.params.id, nextDocumentPage.value);
      const ids = new Set(cards.value.map(item => item.id));
      cards.value.push(...result.items.filter(item => !ids.has(item.id)));
      nextDocumentPage.value = result.next_page ?? null;
    }
  } catch {
    remainingError.value = true;
  } finally {
    remainingLoading.value = false;
  }
}

async function loadPage() {
  isLoading.value = true;
  activeMemoTab.value = 'all';
  try {
    await Promise.all([loadDocuments(), loadAreas()]);
  } finally {
    isLoading.value = false;
  }
}

onMounted(loadPage);


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
