<template>
  <div class="p-0 w-full min-h-screen bg-gray-50">
    <div class="w-full min-w-0 mx-auto py-4">

      <!-- Header Section -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4">
        <div>
          <h1 class="text-2xl font-extrabold tracking-tight text-gray-900">Aktivitas Log</h1>
          <p class="mt-1 text-sm text-gray-500">Pantau dan verifikasi Aktivitas keterbacaan dokumen oleh seluruh
            karyawan.</p>
        </div>
        <div class="flex items-center gap-3">
          <button @click="exportToExcel" :disabled="exporting"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-sm font-semibold rounded-xl shadow transition disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer shrink-0">
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
            <span>{{ exporting ? 'Mengekspor Excel...' : 'Ekspor Excel Aktivitas' }}</span>
          </button>
        </div>
      </div>

      <!-- Main Statistics Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4">
          <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <div class="flex-grow">
            <span class="block text-sm text-gray-400 font-medium">Total Dokumen</span>
            <span v-if="loading" class="block h-7 w-16 bg-gray-200 rounded animate-pulse mt-1"></span>
            <span v-else class="text-2xl font-bold text-gray-900">{{ stats.length }}</span>
          </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4">
          <div class="p-3 bg-green-50 text-green-600 rounded-xl">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
          </div>
          <div class="flex-grow">
            <span class="block text-sm text-gray-400 font-medium">Total Pembaca Unik</span>
            <span v-if="loading" class="block h-7 w-16 bg-gray-200 rounded animate-pulse mt-1"></span>
            <span v-else class="text-2xl font-bold text-gray-900">{{ totalUniqueReaders }}</span>
          </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4">
          <div class="p-3 bg-purple-50 text-purple-600 rounded-xl">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
            </svg>
          </div>
          <div class="flex-grow">
            <span class="block text-sm text-gray-400 font-medium">Total Akumulasi Klik</span>
            <span v-if="loading" class="block h-7 w-16 bg-gray-200 rounded animate-pulse mt-1"></span>
            <span v-else class="text-2xl font-bold text-gray-900">{{ totalClicks }}</span>
          </div>
        </div>
      </div>

      <!-- Search, Sort & Rows Per Page Filters -->
      <div
        class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6 flex flex-col md:flex-row gap-4 items-center justify-between">

        <!-- Search Input -->
        <div class="relative w-full md:max-w-xs">
          <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </span>
          <input v-model="searchQuery" type="text" placeholder="Cari judul atau nomor surat..."
            class="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" />
        </div>

        <!-- Sort and Rows Selectors -->
        <div class="flex flex-wrap items-center gap-4 w-full md:w-auto justify-between md:justify-end">

          <!-- Rows count selector -->
          <div class="flex items-center gap-2">
            <select v-model="perPage"
              class="border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white transition cursor-pointer">
              <option :value="5">5 baris</option>
              <option :value="10">10 baris</option>
              <option :value="25">25 baris</option>
              <option :value="50">50 baris</option>
            </select>
          </div>
          <!-- Sort filter selector -->
          <div class="flex items-center gap-2">
            <select v-model="sortBy"
              class="border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white transition cursor-pointer">
              <option value="newest">Terbaru</option>
              <option value="oldest">Terlama</option>
              <option value="readers_desc">Terbanyak Dibaca</option>
              <option value="readers_asc">Tersedikit Dibaca</option>
              <option value="clicks_desc">Terbanyak Diklik</option>
              <option value="clicks_asc">Tersedikit Diklik</option>
            </select>
          </div>

        </div>
      </div>

      <!-- Stats Table Card -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full min-w-[760px] table-fixed border-collapse" :aria-busy="loading">
            <thead>
              <tr
                class="bg-gray-50 border-b border-gray-100 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                <th class="py-4 px-6 w-16 text-center">No</th>
                <th class="py-4 px-6">Informasi Dokumen</th>
                <th class="py-4 px-4 text-center w-36">Sudah Dibaca</th>
                <th class="py-4 px-4 text-center w-28">Total Klik</th>
                <th class="py-4 px-4 text-center w-56">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 align-middle">
              <!-- Loading Skeleton Rows -->
              <template v-if="loading">
                <tr v-for="i in perPage" :key="'skeleton-' + i" class="animate-pulse">
                  <td class="py-4 px-6 text-center">
                    <div class="h-4 w-6 bg-gray-200 rounded mx-auto"></div>
                  </td>
                  <td class="py-4 px-6">
                    <div class="h-4 w-3/4 bg-gray-200 rounded mb-2"></div>
                    <div class="h-3 w-1/2 bg-gray-100 rounded"></div>
                  </td>
                  <td class="py-4 px-6 text-center">
                    <div class="h-6 w-20 bg-gray-200 rounded-full mx-auto"></div>
                  </td>
                  <td class="py-4 px-6 text-center">
                    <div class="h-6 w-16 bg-gray-200 rounded-full mx-auto"></div>
                  </td>
                  <td class="py-4 px-6 text-center">
                    <div class="h-8 w-32 bg-gray-200 rounded-lg mx-auto"></div>
                  </td>
                </tr>
              </template>

              <tr v-else-if="error">
                <td colspan="5" class="px-6 py-10 text-center">
                  <p role="alert" class="text-sm text-red-600">{{ error }}</p>
                  <button type="button" @click="fetchStats" class="mt-3 rounded-lg bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">Coba lagi</button>
                </td>
              </tr>

              <!-- Real Data Rows -->
              <template v-else-if="paginatedStats.length > 0">
                <tr v-for="(item, idx) in paginatedStats" :key="item.document_id"
                  class="hover:bg-gray-50/50 transition">
                  <td class="py-4 px-6 text-center text-sm font-medium text-gray-500">{{ startRow + idx }}</td>
                  <td class="py-4 px-6">
                    <div class="font-semibold text-gray-900 text-sm break-words">{{ item.document_title }}</div>
                    <div class="text-xs text-gray-400 mt-0.5 break-words">No. Surat: {{ item.document_no_surat || '-' }}</div>
                  </td>
                  <td class="py-4 px-6 text-center">
                    <span
                      class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-50 text-green-700 border border-green-100">
                      {{ item.total_users_read }} Orang
                    </span>
                  </td>
                  <td class="py-4 px-6 text-center">
                    <span
                      class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-100">
                      {{ item.total_clicks }} Kali
                    </span>
                  </td>
                  <td class="py-4 px-6 text-center">
                    <button @click="showDetail(item)"
                      class="inline-flex items-center whitespace-nowrap px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold rounded-lg transition duration-150 gap-1.5 border border-indigo-100">
                      <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                      </svg>
                      Detail Keterbacaan
                    </button>
                  </td>
                </tr>
              </template>

              <!-- Empty State -->
              <tr v-else>
                <td colspan="5" class="py-12 text-center text-gray-400">
                  <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0V9a2 2 0 00-2-2H6a2 2 0 00-2 2v2m16 4h-2a2 2 0 00-2 2v3a2 2 0 00-2-2H6a2 2 0 01-2-2M9 11v6m3-3H6" />
                  </svg>
                  <p class="text-sm font-medium">Dokumen tidak ditemukan</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Table Footer: Pagination controls and rows description -->
        <div v-if="!loading && !error && sortedStats.length > 0"
          class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
          <div class="text-xs text-gray-500 font-medium">
            Menampilkan {{ startRow }} - {{ endRow }} dari {{ sortedStats.length }} dokumen
          </div>

          <div class="flex items-center gap-1.5">
            <!-- Prev Button -->
            <button @click="changePage(currentPage - 1)" :disabled="currentPage === 1"
              class="p-1.5 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-gray-500 disabled:opacity-50 disabled:hover:bg-white transition cursor-pointer">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
            </button>

            <!-- Page numbers -->
            <button v-for="p in visiblePages" :key="p" @click="changePage(p)"
              :class="['px-3 py-1 text-xs font-semibold rounded-lg border transition cursor-pointer', currentPage === p ? 'bg-indigo-600 border-indigo-600 text-white shadow-sm' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50']">
              {{ p }}
            </button>

            <!-- Next Button -->
            <button @click="changePage(currentPage + 1)" :disabled="currentPage === totalPages"
              class="p-1.5 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-gray-500 disabled:opacity-50 disabled:hover:bg-white transition cursor-pointer">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </button>
          </div>
        </div>

      </div>


    </div>

    <!-- Detail Unviewed / Viewed Users Modal -->
    <div v-if="showModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm transition-opacity duration-300">
      <div
        class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden relative animate-fadeIn flex flex-col max-h-[85vh]">

        <!-- Modal Header -->
        <div class="p-6 border-b border-gray-100 flex items-start justify-between bg-gray-50/50">
          <div>
            <h2 class="text-xl font-bold text-gray-900">Detail Keterbacaan Dokumen</h2>
            <p class="text-xs text-gray-400 mt-1">Dokumen: <strong class="text-gray-700 font-semibold">{{
              selectedDoc?.document_title }}</strong></p>
          </div>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600 rounded-lg p-1.5 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Tabs Section -->
        <div class="px-6 border-b border-gray-100 flex gap-4 bg-gray-50/20">
          <button @click="activeTab = 'unviewed'"
            :class="['py-3 text-sm font-semibold border-b-2 px-1 transition duration-150', activeTab === 'unviewed' ? 'border-amber-500 text-amber-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
            Belum Baca ({{ unviewedUsersList.length }})
          </button>
          <button @click="activeTab = 'viewed'"
            :class="['py-3 text-sm font-semibold border-b-2 px-1 transition duration-150', activeTab === 'viewed' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
            Sudah Baca ({{ viewedUsersList.length }})
          </button>
        </div>

        <!-- Modal Search Tab -->
        <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
          <div class="relative w-full sm:max-w-xs">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </span>
            <input v-model="modalSearchQuery" type="text" placeholder="Cari nama karyawan..."
              class="w-full border border-gray-200 rounded-lg pl-9 pr-4 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
          </div>
          <span v-if="activeTab === 'unviewed'"
            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-100">
            {{ filteredUnviewedUsers.length }} Karyawan Belum Membaca
          </span>
          <span v-else
            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-50 text-green-800 border border-green-100">
            {{ filteredViewedUsers.length }} Karyawan Sudah Membaca
          </span>
        </div>

        <!-- Modal Body -->
        <div class="p-6 overflow-y-auto flex-1 bg-gray-50/20">
          <div v-if="modalLoading" class="grid grid-cols-1 sm:grid-cols-2 gap-4 animate-pulse">
            <div v-for="i in 6" :key="'modal-skeleton-' + i"
              class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm flex items-center gap-3">
              <div class="w-8 h-8 rounded-full bg-gray-200"></div>
              <div class="flex-grow">
                <div class="h-3 bg-gray-200 rounded w-2/3 mb-2"></div>
                <div class="h-2 bg-gray-100 rounded w-1/3"></div>
              </div>
            </div>
          </div>

          <div v-else-if="modalError" class="text-center py-12 text-red-500 font-medium">
            {{ modalError }}
          </div>

          <div v-else>
            <!-- Tab Belum Membaca -->
            <div v-if="activeTab === 'unviewed'">
              <div v-if="filteredUnviewedUsers.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div v-for="user in filteredUnviewedUsers" :key="user.username"
                  class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm flex items-center gap-3">
                  <div
                    class="w-8 h-8 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-sm">
                    {{ user.full_name?.charAt(0).toUpperCase() || 'U' }}
                  </div>
                  <div class="overflow-hidden">
                    <h4 class="font-semibold text-gray-900 text-xs truncate">{{ user.full_name }}</h4>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-12 text-gray-400">
                <svg class="w-12 h-12 mx-auto text-green-400 mb-3" fill="none" stroke="currentColor"
                  viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-sm font-semibold text-gray-700">Luar Biasa!</p>
                <p class="text-xs text-gray-500 mt-0.5">Seluruh karyawan telah membaca dokumen ini.</p>
              </div>
            </div>

            <!-- Tab Sudah Membaca -->
            <div v-else>
              <div v-if="filteredViewedUsers.length > 0" class="grid grid-cols-1 gap-4">
                <div v-for="user in filteredViewedUsers" :key="user.username"
                  class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                  <div class="flex items-center gap-3">
                    <div
                      class="w-10 h-10 rounded-full bg-green-100 text-green-700 flex items-center justify-center font-bold text-sm">
                      {{ user.full_name?.charAt(0).toUpperCase() || 'U' }}
                    </div>
                    <div class="overflow-hidden">
                      <h4 class="font-semibold text-gray-900 text-xs truncate">{{ user.full_name }}</h4>
                    </div>
                  </div>
                  <div class="flex flex-wrap items-center gap-2 sm:text-right border-t sm:border-t-0 pt-2 sm:pt-0">
                    <div class="text-3xs text-gray-500 mr-2">
                      <span class="block">Pertama: <strong class="text-gray-700 font-semibold">{{
                        formatDetailDate(user.first_viewed_at) }}</strong></span>
                      <span class="block">Terakhir: <strong class="text-gray-700 font-semibold">{{
                        formatDetailDate(user.last_viewed_at) }}</strong></span>
                    </div>
                    <span
                      class="inline-flex items-center px-2 py-0.5 rounded text-3xs font-medium bg-purple-100 text-purple-800">
                      {{ user.view_count }}x Dibuka
                    </span>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-12 text-gray-400">
                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <p class="text-sm font-semibold text-gray-700">Belum Ada Pembaca</p>
                <p class="text-xs text-gray-500 mt-0.5">Karyawan belum ada yang membuka dokumen ini.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-6 border-t border-gray-100 flex justify-end bg-gray-50/50">
          <button @click="closeModal"
            class="px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg text-sm transition">
            Tutup
          </button>
        </div>

      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { exportActivityLogs } from '@/services/mutations/reportMutation.ts';
import { getDocumentViewStats, getUnviewedUsers, getViewedUsers } from '@/services/queries/documentViewQuery.ts';

const stats = ref([]);
const loading = ref(false);
const error = ref('');
const exporting = ref(false);

const searchQuery = ref('');
const sortBy = ref('readers_desc'); // 'readers_desc', 'readers_asc', 'clicks_desc', 'clicks_asc'
const currentPage = ref(1);
const perPage = ref(10);

const showModal = ref(false);
const activeTab = ref('unviewed'); // 'unviewed' atau 'viewed'
const selectedDoc = ref(null);
const unviewedUsersList = ref([]);
const viewedUsersList = ref([]);
const modalLoading = ref(false);
const modalError = ref('');
const modalSearchQuery = ref('');

// Reset to page 1 whenever filters change
watch([searchQuery, sortBy, perPage], () => {
  currentPage.value = 1;
});

// Fetch overall document tracking stats
const fetchStats = async () => {
  loading.value = true;
  error.value = '';
  try {
    const res = await getDocumentViewStats();
    if (res?.success && Array.isArray(res.data)) {
      stats.value = res.data;
    } else {
      error.value = 'Gagal memuat statistik log audit.';
    }
  } catch (err) {
    error.value = 'Terjadi kesalahan sistem saat memuat data.';
  } finally {
    loading.value = false;
  }
};

// Computeds for Stats Summary
const totalUniqueReaders = computed(() => {
  return stats.value.reduce((acc, curr) => acc + parseInt(curr.total_users_read || 0), 0);
});

const totalClicks = computed(() => {
  return stats.value.reduce((acc, curr) => acc + parseInt(curr.total_clicks || 0), 0);
});

// 1. Filtered stats by search query
const filteredStats = computed(() => {
  if (!searchQuery.value.trim()) return stats.value;
  const q = searchQuery.value.toLowerCase().trim();
  return stats.value.filter(item =>
    item.document_title?.toLowerCase().includes(q) ||
    item.document_no_surat?.toLowerCase().includes(q)
  );
});

// 2. Sorted stats based on sortBy selection (terbanyak / tersedikit)
const sortedStats = computed(() => {
  const items = [...filteredStats.value];
  if (sortBy.value === 'readers_desc') {
    return items.sort((a, b) => b.total_users_read - a.total_users_read);
  }
  if (sortBy.value === 'readers_asc') {
    return items.sort((a, b) => a.total_users_read - b.total_users_read);
  }
  if (sortBy.value === 'clicks_desc') {
    return items.sort((a, b) => b.total_clicks - a.total_clicks);
  }
  if (sortBy.value === 'clicks_asc') {
    return items.sort((a, b) => a.total_clicks - b.total_clicks);
  }
  if (sortBy.value === 'newest') {
    return items.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
  }
  if (sortBy.value === 'oldest') {
    return items.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
  }
  return items;
});

// 3. Paginated stats
const paginatedStats = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  const end = start + perPage.value;
  return sortedStats.value.slice(start, end);
});

// Pagination Info Computeds
const totalPages = computed(() => {
  return Math.ceil(sortedStats.value.length / perPage.value) || 1;
});

watch(totalPages, (pages) => {
  currentPage.value = Math.min(currentPage.value, pages);
});

const startRow = computed(() => {
  if (sortedStats.value.length === 0) return 0;
  return (currentPage.value - 1) * perPage.value + 1;
});

const endRow = computed(() => {
  const end = currentPage.value * perPage.value;
  return end > sortedStats.value.length ? sortedStats.value.length : end;
});

// Visible page numbers selector logic
const visiblePages = computed(() => {
  const total = totalPages.value;
  const current = currentPage.value;
  const pages = [];

  let start = Math.max(1, current - 2);
  let end = Math.min(total, start + 4);

  if (end - start < 4) {
    start = Math.max(1, end - 4);
  }

  for (let i = start; i <= end; i++) {
    pages.push(i);
  }
  return pages;
});

const changePage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page;
  }
};

// Modal Actions
const showDetail = async (item) => {
  selectedDoc.value = item;
  showModal.value = true;
  modalLoading.value = true;
  modalError.value = '';
  unviewedUsersList.value = [];
  viewedUsersList.value = [];
  modalSearchQuery.value = '';
  activeTab.value = 'unviewed';

  try {
    const [resUnviewed, resViewed] = await Promise.all([
      getUnviewedUsers(item.document_id),
      getViewedUsers(item.document_id)
    ]);

    if (resUnviewed.success && resViewed.success) {
      unviewedUsersList.value = resUnviewed.data;
      viewedUsersList.value = resViewed.data;
    } else {
      modalError.value = 'Gagal memuat data keterbacaan karyawan.';
    }
  } catch (err) {
    modalError.value = 'Gagal memuat data dari server.';
  } finally {
    modalLoading.value = false;
  }
};

const closeModal = () => {
  showModal.value = false;
  selectedDoc.value = null;
  unviewedUsersList.value = [];
  viewedUsersList.value = [];
};

const filteredUnviewedUsers = computed(() => {
  if (!modalSearchQuery.value.trim()) return unviewedUsersList.value;
  const q = modalSearchQuery.value.toLowerCase().trim();
  return unviewedUsersList.value.filter(user =>
    user.full_name?.toLowerCase().includes(q) ||
    user.username?.toLowerCase().includes(q)
  );
});

const filteredViewedUsers = computed(() => {
  if (!modalSearchQuery.value.trim()) return viewedUsersList.value;
  const q = modalSearchQuery.value.toLowerCase().trim();
  return viewedUsersList.value.filter(user =>
    user.full_name?.toLowerCase().includes(q) ||
    user.username?.toLowerCase().includes(q)
  );
});

const formatDetailDate = (dateStr) => {
  if (!dateStr) return '-';
  const date = new Date(dateStr);
  return date.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const exportToExcel = async () => {
  if (exporting.value) return;
  try {
    exporting.value = true;
    const response = await exportActivityLogs();

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement("a");
    link.href = url;
    link.setAttribute("download", `laporan_aktivitas_log_${new Date().getTime()}.xlsx`);
    document.body.appendChild(link);
    link.click();
    link.remove();
  } catch (err) {
    console.error(err);
    alert('Gagal mengekspor data aktivitas log ke Excel.');
  } finally {
    exporting.value = false;
  }
};

onMounted(() => {
  fetchStats();
});
</script>

<style scoped>
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: scale(0.95);
  }

  to {
    opacity: 1;
    transform: scale(1);
  }
}

.animate-fadeIn {
  animation: fadeIn 0.2s ease-out forwards;
}

/* text-3xs styling helper */
.text-3xs {
  font-size: 0.65rem;
}
</style>
