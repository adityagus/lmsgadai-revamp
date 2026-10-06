<template>
  <div>
    <SkeletonBreadcrumb v-if='loading' />
    <nav v-else class="flex items-center text-sm text-gray-500 mb-4" aria-label="Breadcrumb">
      <router-link to="/pengumuman" class="hover:underline text-purple-600 font-semibold">Pengumuman</router-link>
      <span class="mx-2">/</span>
      <span class="text-gray-700 font-semibold">Arsip Pengumuman</span>
    </nav>

    <div>
      <div class="overflow-x-auto rounded-lg shadow">
        <table class="min-w-full bg-white border border-gray-200 rounded-lg overflow-hidden">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-4 py-2 text-left">Judul</th>
              <th class="px-4 py-2 text-left">Tanggal Dihapus</th>
              <th class="px-4 py-2 text-left">Dihapus Oleh</th>
              <th class="px-4 py-2 text-left">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="4" class="py-8 text-center text-gray-500">
                <div class="flex items-center gap-2 justify-center">
                  <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                  </svg>
                  Loading data arsip...
                </div>
              </td>
            </tr>
            <tr v-else v-for="item in archives" :key="item.id"
              :class="(archives.length > 0 && $index % 2 === 1) ? 'bg-gray-50' : ''"
              class="hover:bg-blue-50 transition">
              <td class="px-4 py-2 font-medium text-sidebar">{{ item.title }}</td>
              <td class="px-4 py-2">{{ formatDate(item.deleted_at) }}</td>
              <td class="px-4 py-2">
                <span v-if="item.deleted_by"
                  class="inline-block px-2 py-1 text-xs rounded bg-blue-100 text-blue-700 font-semibold">{{
                    item.deleted_by }}</span>
                <span v-else class="text-gray-400">-</span>
              </td>
              <td class="px-4 py-2">
                <div class="flex gap-2 justify-center">
                  <button @click="restore(item.id)"
                    class="px-3 py-1 bg-green-500 text-white rounded-lg font-semibold hover:bg-green-600 transition border border-green-600">Restore</button>
                  <button @click="remove(item.id)" v-if="auth.idgrup == 'JBT-032'"
                    class="px-3 py-1 bg-red-500 text-white rounded-lg font-semibold hover:bg-red-600 transition border border-red-600">Delete
                    Permanen</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="archives.length === 0" class="flex flex-col items-center justify-center py-12 text-gray-400">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-2" fill="none" viewBox="0 0 24 24"
          stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M19 7v4a2 2 0 01-2 2H7a2 2 0 01-2-2V7m5 4v6m4-6v6M5 7h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V9a2 2 0 012-2z" />
        </svg>
        Tidak ada data arsip.
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, defineProps } from 'vue';
import { trashAnnouncement } from '@/services/queries/announcementQuery.ts';
import { restoreAnnouncement } from '@/services/mutations/announcementMutation.ts';
import SkeletonBreadcrumb from '@/components/SkeletonBreadcrumb.vue';
import { useRoute } from '@/navigation';
import { hardDeleteAnnouncement } from '@/services/mutations/announcementMutation.ts';
import Swal from 'sweetalert2';
import { getSession } from '@/services/queries/authQuery.ts';


const route = useRoute();
const archives = ref([]);
const loading = ref(true);
const auth = ref();

const props = defineProps({
  submenu_id: {
    type: Number,
    required: false,
  }
});

const userSession = async () => {
  const response = await getSession();
  if (response.status === 200) {
    user.value = response.data;
  }
}



// Determine submenu_id from prop or query
const getSubmenuId = () => {
  return props.submenu_id || route.query.submenu_id || null;
};

const fetchArchives = async () => {
  loading.value = true;
  try {
    const submenuId = getSubmenuId();
    const res = await trashAnnouncement(submenuId);
    archives.value = res.data;
  } catch (e) {
    archives.value = [];
  }
  loading.value = false;
};

// Refetch if prop changes
watch(() => props.submenu_id, () => {
  fetchArchives();
});

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleString('id-ID');
};

const restore = async (id) => {
  // if (!confirm('Yakin ingin mengembalikan data ini?')) return;
  Swal.fire({
    title: "Are you sure archive?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Yes, archive it!"
  }).then(async (result) => {
    if (result.isConfirmed) {
      Swal.fire({
        title: "Restoring...",
        text: "Please wait",
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        },
        willClose: () => {
          Swal.hideLoading();
        }, timer: 1500
      });
      await restoreAnnouncement(id);
      fetchArchives();
      Swal.fire({
        title: "Success!",
        text: "Data has been restored.",
        icon: "success",
        timer: 1500
      });
    }
  }).catch(() => {
    Swal.fire({
      title: "Error!",
      text: "Failed to restore data!",
      icon: "error",
      timer: 1500
    });
  });
};

const remove = async (id) => {
  if (!confirm('Hapus permanen data ini?')) return;
  try {
    await hardDeleteAnnouncement(id);
    fetchArchives();
  } catch (e) {
    alert('Gagal hapus permanen!');
  }
};

onMounted(async () => {
  try {
    const [resAuth] = await Promise.all([
      getSession()
    ]);
    auth.value = resAuth.auth
    console.log('session 2', auth);
  } catch (error) {
    console.error('Error fetching session:', error);
  }
  fetchArchives();
});
</script>

<style scoped>
table {
  border-collapse: collapse;
}

th,
td {
  border-bottom: 1px solid #e5e7eb;
}

tr:last-child td {
  border-bottom: none;
}

.bg-blue-100 {
  background: #dbeafe;
}

.text-blue-700 {
  color: #1d4ed8;
}

.hover\:bg-blue-50:hover {
  background: #eff6ff;
}

/* Button group style for action buttons */
.flex.gap-2 button {
  min-width: 120px;
  box-shadow: 0 1px 4px 0 rgba(0, 0, 0, 0.04);
}

.flex.gap-2 button:first-child {
  border-right: none;
}

.flex.gap-2 button:last-child {
  border-left: none;
}
</style>
