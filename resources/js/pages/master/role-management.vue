<template>
  <div class="w-full pb-8 font-['Poppins']">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Manajemen Role Karyawan</h1>
        <p class="text-xs text-gray-500 mt-0.5">
          Atur hak akses role (Admin, Trainer, SOP, Audit) untuk karyawan.
        </p>
      </div>
      <div>
        <button
          type="button"
          @click="openAddModal"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#662FFF] hover:bg-[#5424D3] text-white rounded-lg text-sm font-medium transition cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Tambah Role
        </button>
      </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white rounded-xl p-3 border border-gray-200 mb-4 flex flex-col sm:flex-row items-center justify-between gap-3">
      <div class="relative w-full sm:w-72">
        <input
          v-model="search"
          type="text"
          placeholder="Cari nama, NPK, role..."
          class="w-full pl-9 pr-3 py-1.5 text-xs bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:border-[#662FFF]"
        />
        <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </div>

      <div class="flex items-center gap-1.5 overflow-x-auto w-full sm:w-auto">
        <button
          type="button"
          @click="roleFilter = ''"
          :class="[
            'px-3 py-1 rounded-lg text-xs font-medium transition cursor-pointer text-nowrap',
            roleFilter === '' ? 'bg-[#662FFF] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
          ]"
        >
          Semua ({{ items.length }})
        </button>
        <button
          v-for="role in ['admin', 'trainer', 'sop', 'audit']"
          :key="role"
          type="button"
          @click="roleFilter = role"
          :class="[
            'px-3 py-1 rounded-lg text-xs font-medium transition cursor-pointer text-nowrap uppercase',
            roleFilter === role ? 'bg-[#662FFF] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
          ]"
        >
          {{ role }} ({{ countByRole(role) }})
        </button>
      </div>
    </div>

    <!-- Alert -->
    <div v-if="successMsg" class="mb-3 px-3 py-2 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center justify-between">
      <span>{{ successMsg }}</span>
      <button @click="successMsg = ''" class="text-emerald-600 font-bold ml-2 cursor-pointer">&times;</button>
    </div>
    <div v-if="errorMsg" class="mb-3 px-3 py-2 rounded-lg bg-red-50 border border-red-200 text-red-800 text-xs flex items-center justify-between">
      <span>{{ errorMsg }}</span>
      <button @click="errorMsg = ''" class="text-red-600 font-bold ml-2 cursor-pointer">&times;</button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-gray-600">
          <thead class="bg-gray-50 text-[11px] uppercase font-semibold text-gray-500 border-b border-gray-200">
            <tr>
              <th class="py-2.5 px-3 text-center w-12">No</th>
              <th class="py-2.5 px-3">Nama Karyawan</th>
              <th class="py-2.5 px-3">NPK / Username</th>
              <th class="py-2.5 px-3">Jabatan</th>
              <th class="py-2.5 px-3">Role</th>
              <th class="py-2.5 px-3">Keterangan</th>
              <th class="py-2.5 px-3 text-center w-24">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="(row, idx) in filteredItems"
              :key="row.id || row.username"
              class="hover:bg-gray-50/80 transition"
            >
              <td class="py-2.5 px-3 text-center text-gray-400">{{ idx + 1 }}</td>
              <td class="py-2.5 px-3 font-semibold text-gray-900">{{ row.name || '-' }}</td>
              <td class="py-2.5 px-3 font-mono text-gray-600">{{ row.username }}</td>
              <td class="py-2.5 px-3 text-gray-600">{{ row.position || findEmployeePosition(row.username) || '-' }}</td>
              <td class="py-2.5 px-3">
                <div class="flex flex-wrap gap-1">
                  <span
                    v-for="r in getRowRoles(row)"
                    :key="r"
                    :class="['px-2 py-0.5 rounded text-[11px] font-medium border uppercase', getBadgeClass(r)]"
                  >
                    {{ r }}
                  </span>
                </div>
              </td>
              <td class="py-2.5 px-3 text-gray-500">{{ row.description || '-' }}</td>
              <td class="py-2.5 px-3 text-center">
                <div class="inline-flex items-center gap-2">
                  <button
                    type="button"
                    @click="openEditModal(row)"
                    class="text-blue-600 hover:text-blue-800 font-medium transition cursor-pointer"
                  >
                    Edit
                  </button>
                  <span class="text-gray-300">|</span>
                  <button
                    type="button"
                    @click="confirmDelete(row)"
                    class="text-red-600 hover:text-red-800 font-medium transition cursor-pointer"
                  >
                    Hapus
                  </button>
                </div>
              </td>
            </tr>

            <!-- Empty -->
            <tr v-if="!loading && filteredItems.length === 0">
              <td colspan="7" class="py-8 text-center text-gray-400">
                {{ search ? 'Tidak ada data yang sesuai pencarian.' : 'Belum ada role khusus yang diatur.' }}
              </td>
            </tr>

            <!-- Loading -->
            <tr v-if="loading && items.length === 0">
              <td colspan="7" class="py-8 text-center text-gray-400">Memuat data...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Form (Minimalis) -->
    <div
      v-if="showModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-[2px]"
    >
      <div class="bg-white rounded-xl border border-gray-200 max-w-md w-full overflow-hidden shadow-lg">
        <!-- Header -->
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200">
          <h2 class="text-sm font-bold text-gray-800">
            {{ editMode ? 'Edit Role Karyawan' : 'Atur Role Karyawan' }}
          </h2>
          <button
            type="button"
            @click="closeModal"
            class="text-gray-400 hover:text-gray-600 text-lg leading-none cursor-pointer"
          >
            &times;
          </button>
        </div>

        <!-- Body -->
        <form @submit.prevent="handleSubmit" class="p-5 space-y-4 text-xs">
          <!-- Pilih Karyawan -->
          <div>
            <label class="block font-medium text-gray-700 mb-1">
              Karyawan <span class="text-red-500">*</span>
            </label>

            <!-- State 1: Karyawan Terpilih -->
            <div
              v-if="form.username"
              class="p-2.5 bg-gray-50 border border-gray-200 rounded-lg flex items-center justify-between"
            >
              <div>
                <span class="font-semibold text-gray-900 block">{{ form.name || form.username }}</span>
                <span class="text-[11px] text-gray-500">
                  NPK: {{ form.username }} &bull; {{ form.position || 'Staff' }}
                </span>
              </div>
              <button
                v-if="!editMode"
                type="button"
                @click="resetSelectedEmployee"
                class="text-xs text-blue-600 hover:underline cursor-pointer"
              >
                Ganti
              </button>
            </div>

            <!-- State 2: Input Pencarian Karyawan -->
            <div v-else class="relative" ref="dropdownRef">
              <div class="relative">
                <input
                  v-model="employeeSearchQuery"
                  @focus="isDropdownOpen = true"
                  type="text"
                  placeholder="Ketik nama atau NPK karyawan..."
                  class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg focus:outline-none focus:border-[#662FFF]"
                  required
                />
                <button
                  v-if="employeeSearchQuery"
                  type="button"
                  @click="employeeSearchQuery = ''"
                  class="absolute right-2.5 top-2 text-gray-400 hover:text-gray-600 cursor-pointer"
                >
                  &times;
                </button>
              </div>

              <!-- Dropdown List -->
              <div
                v-if="isDropdownOpen"
                class="absolute z-20 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-md max-h-48 overflow-y-auto divide-y divide-gray-100"
              >
                <div
                  v-for="emp in searchedEmployees"
                  :key="emp.username"
                  @click="selectEmployee(emp)"
                  class="p-2 hover:bg-purple-50/60 cursor-pointer transition"
                >
                  <span class="font-semibold text-gray-800 block">{{ emp.name }}</span>
                  <span class="text-[11px] text-gray-500">
                    {{ emp.username }} &bull; {{ emp.position || 'Staff' }}
                  </span>
                </div>
                <div v-if="employeeLoading" class="p-3 text-center text-gray-400">
                  Mencari karyawan...
                </div>
                <div v-else-if="searchedEmployees.length === 0" class="p-3 text-center text-gray-400">
                  Tidak ditemukan.
                </div>
              </div>
            </div>
          </div>

          <!-- Checklist Role -->
          <div>
            <label class="block font-medium text-gray-700 mb-1">
              Role Akses (Bisa Pilih Lebih Dari Satu) <span class="text-red-500">*</span>
            </label>
            <div class="grid grid-cols-2 gap-2 mt-1">
              <label
                v-for="role in roleOptions"
                :key="role.value"
                :class="[
                  'flex items-center gap-2 p-2.5 rounded-lg border cursor-pointer transition select-none',
                  form.roles.includes(role.value)
                    ? 'border-[#662FFF] bg-purple-50/50 text-[#662FFF] font-medium'
                    : 'border-gray-200 text-gray-700 hover:bg-gray-50'
                ]"
              >
                <input
                  type="checkbox"
                  :value="role.value"
                  v-model="form.roles"
                  class="h-4 w-4 text-[#662FFF] rounded border-gray-300 focus:ring-[#662FFF] cursor-pointer"
                />
                <span>{{ role.label }}</span>
              </label>
            </div>
          </div>

          <!-- Keterangan -->
          <div>
            <label class="block font-medium text-gray-700 mb-1">
              Keterangan
            </label>
            <input
              v-model="form.description"
              type="text"
              placeholder="Catatan tambahan (opsional)..."
              class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg focus:outline-none focus:border-[#662FFF]"
            />
          </div>

          <!-- Error Alert -->
          <div v-if="modalError" class="p-2 bg-red-50 border border-red-200 rounded-lg text-red-600 text-xs">
            {{ modalError }}
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
            <button
              type="button"
              @click="closeModal"
              class="px-4 py-1.5 text-xs text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="saving || form.roles.length === 0 || !form.username"
              class="px-4 py-1.5 text-xs font-medium text-white bg-[#662FFF] hover:bg-[#5424D3] rounded-lg transition disabled:opacity-50 cursor-pointer"
            >
              {{ saving ? 'Menyimpan...' : (editMode ? 'Simpan' : 'Tambahkan') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div
      v-if="itemToDelete"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-[2px]"
    >
      <div class="bg-white rounded-xl border border-gray-200 max-w-sm w-full p-5 text-center shadow-lg">
        <h3 class="text-sm font-bold text-gray-900 mb-1">Hapus Role?</h3>
        <p class="text-xs text-gray-500 mb-4">
          Hapus penetapan role untuk <strong>{{ itemToDelete.name || itemToDelete.username }}</strong>? Hak akses akan kembali ke standar jabatan.
        </p>
        <div class="flex items-center justify-center gap-2">
          <button
            type="button"
            @click="itemToDelete = null"
            class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs rounded-lg transition cursor-pointer"
          >
            Batal
          </button>
          <button
            type="button"
            :disabled="deleting"
            @click="handleDelete"
            class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-lg transition disabled:opacity-50 cursor-pointer"
          >
            {{ deleting ? 'Menghapus...' : 'Hapus' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useQuery, useMutation } from '@tanstack/vue-query';
import { getPositionRoles, searchRoleEmployees } from '@/services/queries/masterQuery.ts';
import { createPositionRole, updatePositionRole, deletePositionRole } from '@/services/mutations/roleMutation.ts';

const search = ref('');
const roleFilter = ref('');
const showModal = ref(false);
const editMode = ref(false);
const modalError = ref('');
const successMsg = ref('');
const errorMsg = ref('');
const itemToDelete = ref(null);

const employeeSearchQuery = ref('');
const isDropdownOpen = ref(false);
const dropdownRef = ref(null);

function handleClickOutside(event) {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    isDropdownOpen.value = false;
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});

const form = ref({
  id: null,
  username: '',
  name: '',
  position: '',
  roles: ['trainer'],
  description: '',
});

const roleOptions = [
  { value: 'admin', label: 'Admin (IT SPV)' },
  { value: 'trainer', label: 'Trainer (Instruktur)' },
  { value: 'sop', label: 'SOP (Dokumen)' },
  { value: 'audit', label: 'Audit (Laporan)' },
];

// React Query
const { data: responseData, isFetching: loading, refetch } = useQuery(getPositionRoles.options());

const items = computed(() => {
  return responseData.value?.user_roles || responseData.value?.data || [];
});

const employeesList = computed(() => responseData.value?.employees || []);

// Server-side search to db_gadaimulia (debounced)
const debouncedEmployeeQuery = ref('');
let debounceTimer = null;
watch(employeeSearchQuery, (val) => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    debouncedEmployeeQuery.value = (val || '').trim();
  }, 300);
});

const { data: employeeData, isFetching: employeeLoading } = useQuery({
  ...searchRoleEmployees.options(debouncedEmployeeQuery),
  enabled: isDropdownOpen,
  staleTime: 60_000,
});

const searchedEmployees = computed(() => employeeData.value?.data || []);

// Mutations
const createMutation = useMutation(createPositionRole.options());
const updateMutation = useMutation(updatePositionRole.options());
const deleteMutation = useMutation(deletePositionRole.options());

const saving = computed(() => createMutation.isPending.value || updateMutation.isPending.value);
const deleting = computed(() => deleteMutation.isPending.value);

// Filtered list
const filteredItems = computed(() => {
  let list = items.value;
  if (roleFilter.value) {
    list = list.filter(i => {
      const roles = getRowRoles(i);
      return roles.includes(roleFilter.value.toLowerCase());
    });
  }
  if (search.value.trim()) {
    const q = search.value.toLowerCase().trim();
    list = list.filter(i =>
      (i.username || '').toLowerCase().includes(q) ||
      (i.name || '').toLowerCase().includes(q) ||
      (i.position || '').toLowerCase().includes(q) ||
      (i.role || '').toLowerCase().includes(q) ||
      (i.description || '').toLowerCase().includes(q)
    );
  }
  return list;
});

function getRowRoles(row) {
  if (Array.isArray(row.roles) && row.roles.length > 0) {
    return row.roles;
  }
  if (row.role) {
    return [row.role];
  }
  return ['student'];
}

function countByRole(role) {
  return items.value.filter(i => {
    const roles = getRowRoles(i);
    return roles.includes(role.toLowerCase());
  }).length;
}

function findEmployeePosition(username) {
  const emp = employeesList.value.find(e => (e.username || '').toLowerCase() === (username || '').toLowerCase());
  return emp?.position || '';
}

function selectEmployee(emp) {
  form.value.username = emp.username;
  form.value.name = emp.name;
  form.value.position = emp.position || '';
  isDropdownOpen.value = false;
  employeeSearchQuery.value = '';
}

function resetSelectedEmployee() {
  form.value.username = '';
  form.value.name = '';
  form.value.position = '';
  employeeSearchQuery.value = '';
  isDropdownOpen.value = true;
}

function getBadgeClass(role) {
  switch (role) {
    case 'admin':
      return 'bg-purple-50 text-purple-700 border-purple-200';
    case 'trainer':
      return 'bg-blue-50 text-blue-700 border-blue-200';
    case 'sop':
      return 'bg-emerald-50 text-emerald-700 border-emerald-200';
    case 'audit':
      return 'bg-amber-50 text-amber-700 border-amber-200';
    default:
      return 'bg-gray-50 text-gray-600 border-gray-200';
  }
}

function openAddModal() {
  editMode.value = false;
  modalError.value = '';
  employeeSearchQuery.value = '';
  isDropdownOpen.value = false;
  form.value = {
    id: null,
    username: '',
    name: '',
    position: '',
    roles: ['trainer', 'sop'],
    description: '',
  };
  showModal.value = true;
}

function openEditModal(item) {
  editMode.value = true;
  modalError.value = '';
  employeeSearchQuery.value = '';
  isDropdownOpen.value = false;
  form.value = {
    id: item.id,
    username: item.username,
    name: item.name || '',
    position: item.position || findEmployeePosition(item.username),
    roles: [...getRowRoles(item)],
    description: item.description || '',
  };
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  modalError.value = '';
  isDropdownOpen.value = false;
}

async function handleSubmit() {
  modalError.value = '';
  if (!form.value.username) {
    modalError.value = 'Silakan pilih karyawan terlebih dahulu.';
    return;
  }
  if (!form.value.roles || form.value.roles.length === 0) {
    modalError.value = 'Pilih minimal 1 role untuk karyawan.';
    return;
  }

  try {
    const payload = {
      username: form.value.username.trim().toLowerCase(),
      name: form.value.name ? form.value.name.trim() : null,
      position: form.value.position ? form.value.position.trim() : null,
      roles: form.value.roles,
      description: form.value.description ? form.value.description.trim() : null,
    };

    if (editMode.value) {
      await updateMutation.mutateAsync([form.value.id, payload]);
      successMsg.value = `Role untuk ${payload.name || payload.username} berhasil diperbarui.`;
    } else {
      await createMutation.mutateAsync([payload]);
      successMsg.value = `Role untuk ${payload.name || payload.username} berhasil ditetapkan.`;
    }
    closeModal();
    refetch();
  } catch (err) {
    modalError.value = err?.response?.data?.message || err?.message || 'Gagal menyimpan role karyawan.';
  }
}

function confirmDelete(item) {
  itemToDelete.value = item;
}

async function handleDelete() {
  if (!itemToDelete.value) return;
  try {
    const name = itemToDelete.value.name || itemToDelete.value.username;
    await deleteMutation.mutateAsync([itemToDelete.value.id]);
    successMsg.value = `Role khusus untuk ${name} berhasil dihapus.`;
    itemToDelete.value = null;
    refetch();
  } catch (err) {
    errorMsg.value = err?.response?.data?.message || err?.message || 'Gagal menghapus role karyawan.';
    itemToDelete.value = null;
  }
}
</script>
