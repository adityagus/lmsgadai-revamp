<template>
  <div class="flex flex-col gap-6 w-full min-w-0 pb-10">

    <!-- Header Section -->
    <div
      class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
      <div>
        <h1 class="text-2xl md:text-3xl font-extrabold text-[#060A23] tracking-tight">Laporan Dokumen</h1>
        <p class="text-sm text-[#838C9D] mt-1">Rekapitulasi seluruh dokumen pengumuman, perihal, dan masa berlakunya.
        </p>
      </div>

      <div class="flex items-center gap-3">
        <button @click="exportToExcel" :disabled="exporting || filteredData.length === 0"
          class="inline-flex items-center gap-2 px-5 py-2.5 bg-green-500 hover:bg-green-600 active:bg-green-700 text-white text-sm font-semibold rounded-xl shadow transition disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer shrink-0">
          <svg v-if="!exporting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <svg v-else class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
            </path>
          </svg>
          <span>{{ exporting ? 'Mengekspor...' : 'Ekspor Excel' }}</span>
        </button>
      </div>
    </div>

    <!-- Controls Section -->
    <div
      class="flex flex-col sm:flex-row items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-gray-200 shadow-sm">
      <div class="relative w-full sm:w-80">
        <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor"
          viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input v-model="searchQuery" type="text" placeholder="Cari perihal, nomor surat, atau jabatan..."
          class="w-full pl-10 pr-4 py-2 text-sm bg-gray-50 border border-[#CFDBEF] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#662FFF] focus:border-[#662FFF] transition" />
      </div>

      <div class="flex items-center gap-2 text-xs text-gray-500 self-end sm:self-auto">
        <span class="font-semibold text-gray-600">Tampilkan:</span>
        <select v-model="perPage"
          class="bg-gray-50 border border-[#CFDBEF] rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-[#662FFF] font-semibold text-gray-700">
          <option :value="10">10 baris</option>
          <option :value="25">25 baris</option>
          <option :value="50">50 baris</option>
          <option :value="100">100 baris</option>
        </select>
      </div>
    </div>

    <!-- Table Section -->
    <div class="w-full min-w-0 bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
      <div class="w-full overflow-x-auto">
        <table class="w-full min-w-[900px] border-collapse">
          <thead>
            <tr class="bg-[#060A23] text-white text-xs font-bold uppercase tracking-wider text-left">
              <th class="py-3.5 px-4 w-12 text-center">No</th>
              <th class="py-3.5 px-4 min-w-[250px]">Jabatan</th>
              <th class="py-3.5 px-4 min-w-[150px]">PT / Wilayah</th>
              <th class="py-3.5 px-4 whitespace-nowrap">Tanggal Upload</th>
              <th class="py-3.5 px-4 whitespace-nowrap text-center">Ketentuan Dokumen</th>
              <th class="py-3.5 px-4 whitespace-nowrap">Tanggal Berlaku</th>
              <th class="py-3.5 px-4 whitespace-nowrap min-w-[150px]">Nomor Surat</th>
              <th class="py-3.5 px-4 min-w-[250px]">Perihal / Judul</th>
              <th class="py-3.5 px-4 text-center whitespace-nowrap">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="(item, index) in paginatedData" :key="index" class="hover:bg-purple-50/40 transition">
              <td class="py-3 px-4 text-xs font-semibold text-gray-400 text-center">{{ startRow + index }}</td>
              <td class="py-3 px-4 text-sm font-semibold text-[#060A23]">{{ item.position || '-' }}</td>
              <td class="py-3 px-4 text-sm font-medium text-gray-600">{{ item.pt || '-' }}</td>
              <td class="py-3 px-4 text-sm text-gray-500 whitespace-nowrap">{{ formatDate(item.tanggal_upload) }}</td>
              <td class="py-3 px-4 text-sm text-gray-700 whitespace-nowrap text-center">
                <span
                  class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-[#7F33FF15] text-[#7F33FF] border border-[#7F33FF33]">
                  {{ item.ketentuan_dokumen || '-' }}
                </span>
              </td>
              <td class="py-3 px-4 text-sm text-gray-500 whitespace-nowrap">{{ formatDate(item.tanggal_berlaku) }}</td>
              <td class="py-3 px-4 text-sm font-semibold text-gray-700 whitespace-nowrap">{{ item.nomor_surat || '-' }}
              </td>
              <td class="py-3 px-4 text-sm text-gray-900 font-bold" :title="item.perihal_judul">{{ item.perihal_judul ||
                '-' }}</td>
              <td class="py-3 px-4 text-center whitespace-nowrap">
                <span
                  :class="['inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border', item.status === 'Active' ? 'bg-green-100 text-green-700 border-green-200' : 'bg-red-100 text-red-700 border-red-200']">
                  {{ item.status }}
                </span>
              </td>
            </tr>
            <tr v-if="loading">
              <td colspan="9" class="text-center py-8">
                <div class="flex flex-col items-center justify-center text-[#662FFF] font-medium">
                  <svg class="animate-spin h-8 w-8 mb-2 text-[#662FFF]" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                  </svg>
                  Memuat data laporan...
                </div>
              </td>
            </tr>
            <tr v-if="error && !loading">
              <td colspan="9" class="py-8">
                <div class="p-4 bg-red-50 border border-red-100 text-red-600 text-center rounded-xl font-medium">
                  {{ error }}
                </div>
              </td>
            </tr>
            <tr v-if="paginatedData.length === 0 && !loading && !error">
              <td colspan="9" class="py-12 text-center text-gray-400">
                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="text-sm font-medium">Laporan kosong atau tidak ditemukan</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="filteredData.length > 0"
        class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="text-xs text-gray-500 font-medium">
          Menampilkan {{ startRow }} - {{ endRow }} dari {{ filteredData.length }} baris
        </div>
        <div class="flex items-center gap-1.5">
          <button @click="changePage(currentPage - 1)" :disabled="currentPage === 1"
            class="p-1.5 rounded-lg border border-gray-200 bg-white hover:bg-gray-100 text-gray-500 disabled:opacity-50 transition cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <button v-for="p in visiblePages" :key="p" @click="changePage(p)"
            :class="['px-3 py-1 text-xs font-semibold rounded-lg border transition cursor-pointer', currentPage === p ? 'bg-[#662FFF] border-[#662FFF] text-white shadow-sm' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50']">
            {{ p }}
          </button>
          <button @click="changePage(currentPage + 1)" :disabled="currentPage === totalPages"
            class="p-1.5 rounded-lg border border-gray-200 bg-white hover:bg-gray-100 text-gray-500 disabled:opacity-50 transition cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';

const data = ref([]);
const loading = ref(false);
const error = ref('');
const exporting = ref(false);

const searchQuery = ref('');
const currentPage = ref(1);
const perPage = ref(10);

watch([searchQuery, perPage], () => {
  currentPage.value = 1;
});

const fetchReport = async () => {
  loading.value = true;
  error.value = '';
  try {
    const response = await axios.get('/api/v1/report/documents');
    if (response.data && response.data.success) {
      data.value = response.data.data;
    } else {
      error.value = 'Gagal memuat data laporan dokumen.';
    }
  } catch (err) {
    console.error(err);
    error.value = 'Terjadi kesalahan saat menghubungi server.';
  } finally {
    loading.value = false;
  }
};

const filteredData = computed(() => {
  if (!searchQuery.value.trim()) return data.value;
  const q = searchQuery.value.toLowerCase().trim();
  return data.value.filter(item =>
    item.perihal_judul?.toLowerCase().includes(q) ||
    item.nomor_surat?.toLowerCase().includes(q) ||
    item.position?.toLowerCase().includes(q) ||
    item.pt?.toLowerCase().includes(q)
  );
});

const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  const end = start + perPage.value;
  return filteredData.value.slice(start, end);
});

const totalPages = computed(() => Math.ceil(filteredData.value.length / perPage.value) || 1);

const startRow = computed(() => {
  if (filteredData.value.length === 0) return 0;
  return (currentPage.value - 1) * perPage.value + 1;
});

const endRow = computed(() => {
  const end = currentPage.value * perPage.value;
  return end > filteredData.value.length ? filteredData.value.length : end;
});

const visiblePages = computed(() => {
  const total = totalPages.value;
  const current = currentPage.value;
  const pages = [];
  let start = Math.max(1, current - 2);
  let end = Math.min(total, start + 4);
  if (end - start < 4) start = Math.max(1, end - 4);
  for (let i = start; i <= end; i++) pages.push(i);
  return pages;
});

const changePage = (page) => {
  if (page >= 1 && page <= totalPages.value) currentPage.value = page;
};

const formatDate = (dateStr) => {
  if (!dateStr) return '-';
  const date = new Date(dateStr);
  return date.toLocaleDateString('id-ID', {
    day: 'numeric', month: 'short', year: 'numeric'
  });
};

const exportToExcel = async () => {
  if (filteredData.value.length === 0 || exporting.value) return;
  try {
    exporting.value = true;
    const response = await axios.post('/api/v1/report/documents/export-excel', {
      data: filteredData.value
    }, {
      responseType: 'blob'
    });

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement("a");
    link.href = url;
    link.setAttribute("download", `laporan_dokumen_${new Date().getTime()}.xlsx`);
    document.body.appendChild(link);
    link.click();
    link.remove();
  } catch (err) {
    console.error(err);
    alert('Gagal mengekspor data ke Excel.');
  } finally {
    exporting.value = false;
  }
};

onMounted(() => {
  fetchReport();
});
</script>
