<template>
  <div>
    <section id="CourseList" class="flex flex-col w-full rounded-2xl sm:rounded-[30px] p-4 sm:p-[30px] gap-4 sm:gap-6 bg-[#F8FAFB]">
      <!-- Header -->
      <div class="header flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h2 class="font-bold text-lg sm:text-[22px] leading-tight sm:leading-[33px]">
            Isi Materi
          </h2>
          <p class="text-xs text-[#838C9D] mt-0.5">
            Atur Bab / Modul dan materi dalam tampilan accordion. Geser atau gunakan tombol panah untuk mengurutkan.
          </p>
        </div>
        <div class="flex flex-wrap gap-2.5">
          <button
            type="button"
            @click="openChapterModal()"
            class="rounded-full px-5 py-3 font-semibold text-[#662FFF] bg-purple-50 border border-purple-300 text-center text-xs sm:text-sm hover:bg-purple-100 transition-colors cursor-pointer"
          >
            + Tambah Bab / Modul
          </button>
          <RouterLink
            :to="{ path: `/course/create-contents/${props.courseId}`, query: { type: 'quiz' } }"
            class="rounded-full px-5 py-3 font-semibold text-white bg-[#662FFF] hover:bg-[#5422DB] text-center text-xs sm:text-sm transition-colors"
          >
            Tambah Quiz
          </RouterLink>
          <RouterLink
            :to="`/course/create-contents/${props.courseId}`"
            class="w-full sm:w-fit rounded-full px-5 py-3 font-semibold text-white bg-[#662FFF] text-nowrap text-center text-xs sm:text-sm hover:bg-[#5422DB] transition-colors"
          >
            Tambah Materi
          </RouterLink>
        </div>
      </div>

      <!-- Unsaved order bar -->
      <div
        v-if="isDirty"
        class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-2xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm"
      >
        <div class="flex items-center gap-2 text-amber-900 font-medium">
          <i class="pi pi-exclamation-triangle text-amber-600"></i>
          <span>Perubahan urutan atau grup materi belum disimpan.</span>
        </div>
        <div class="flex items-center gap-2">
          <button
            type="button"
            :disabled="savingOrder"
            @click="resetOrder"
            class="rounded-full border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 cursor-pointer"
          >
            Batalkan
          </button>
          <button
            type="button"
            :disabled="savingOrder"
            @click="saveOrder"
            class="rounded-full bg-[#662FFF] hover:bg-[#5422DB] px-5 py-2 text-xs font-semibold text-white cursor-pointer disabled:opacity-50"
          >
            {{ savingOrder ? 'Menyimpan...' : 'Simpan Urutan' }}
          </button>
        </div>
      </div>

      <p v-if="structureError" role="alert" class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-xs text-red-700 flex items-center justify-between">
        <span>{{ structureError }}</span>
        <button type="button" @click="loadStructure" class="underline font-semibold cursor-pointer">Muat ulang</button>
      </p>

      <!-- Loading skeleton -->
      <div v-if="loadingStructure" class="space-y-3 animate-pulse">
        <div v-for="i in 3" :key="i" class="h-16 rounded-2xl bg-white border border-gray-100"></div>
      </div>

      <!-- Empty state -->
      <div v-else-if="chapters.length === 0 && orphanItems.length === 0" class="rounded-2xl bg-white border border-dashed border-gray-200 p-8 text-center">
        <p class="text-sm font-semibold text-gray-700">Belum ada Bab atau Materi</p>
        <p class="text-xs text-[#838C9D] mt-1">Mulai dengan klik "+ Tambah Bab / Modul" atau "Tambah Materi".</p>
      </div>

      <!-- Accordion Groups -->
      <div v-else class="space-y-4">
        <!-- Bab / Modul Groups -->
        <div
          v-for="(group, gIdx) in chapters"
          :key="group.id"
          draggable="true"
          @dragstart="onGroupDragStart(gIdx, $event)"
          @dragover.prevent="onGroupDragOver(gIdx)"
          @drop.prevent="onGroupDrop(gIdx)"
          @dragend="clearDrag"
          :class="[
            'rounded-2xl bg-white border transition-all overflow-hidden shadow-xs',
            dragOverGroupIdx === gIdx ? 'border-[#662FFF] ring-2 ring-[#662FFF]/20' : 'border-[#E6ECF5]',
          ]"
        >
          <!-- Group Header -->
          <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-3.5 bg-purple-50/50 border-b border-[#E6ECF5]">
            <div class="flex items-center gap-2.5 min-w-0 flex-1">
              <span class="cursor-grab text-gray-400 hover:text-[#662FFF] px-1" title="Geser untuk urutkan grup Bab">
                <i class="pi pi-bars text-sm"></i>
              </span>
              <button
                type="button"
                @click="toggleExpand(group.id)"
                class="flex items-center gap-2 text-left min-w-0 flex-1 cursor-pointer"
              >
                <i :class="['pi text-xs text-[#662FFF] transition-transform', isExpanded(group.id) ? 'pi-chevron-down' : 'pi-chevron-right']"></i>
                <i class="pi pi-folder text-[#662FFF]"></i>
                <span class="font-bold text-sm sm:text-base text-[#060A23] truncate">{{ group.title }}</span>
                <span class="shrink-0 rounded-full bg-purple-100 text-[#662FFF] px-2.5 py-0.5 text-[11px] font-semibold">
                  {{ group.items.length }} materi
                </span>
              </button>
            </div>

            <div class="flex flex-wrap items-center gap-1.5">
              <button
                type="button"
                :disabled="gIdx === 0 || savingOrder"
                @click="moveGroup(gIdx, -1)"
                title="Naikkan Bab"
                class="rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs text-gray-700 hover:bg-gray-50 disabled:opacity-30 cursor-pointer"
              >
                <i class="pi pi-arrow-up text-[11px]"></i>
              </button>
              <button
                type="button"
                :disabled="gIdx === chapters.length - 1 || savingOrder"
                @click="moveGroup(gIdx, 1)"
                title="Turunkan Bab"
                class="rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs text-gray-700 hover:bg-gray-50 disabled:opacity-30 cursor-pointer"
              >
                <i class="pi pi-arrow-down text-[11px]"></i>
              </button>
              <RouterLink
                :to="{ path: `/course/create-contents/${props.courseId}`, query: { parent_id: group.id } }"
                class="rounded-full bg-[#662FFF] hover:bg-[#5422DB] text-white px-3 py-1.5 text-xs font-semibold transition-colors"
              >
                + Materi
              </RouterLink>
              <button
                type="button"
                @click="openChapterModal(group)"
                title="Ubah nama Bab"
                class="rounded-full border border-gray-300 bg-white hover:bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-700 cursor-pointer"
              >
                Ubah Nama
              </button>
              <button
                type="button"
                @click="handleDeleteChapter(group)"
                title="Hapus Bab"
                class="rounded-full bg-[#FF435A] hover:bg-red-600 text-white px-3 py-1.5 text-xs font-semibold cursor-pointer"
              >
                Hapus
              </button>
            </div>
          </div>

          <!-- Group Body -->
          <div
            v-show="isExpanded(group.id)"
            @dragover.prevent="onItemContainerDragOver(group.id)"
            @drop.prevent="onItemDropOnGroup(group.id)"
            class="p-3 sm:p-4 space-y-2.5 min-h-[52px]"
          >
            <div v-if="group.items.length === 0" class="rounded-xl border border-dashed border-gray-200 py-4 text-center text-xs text-[#838C9D]">
              Belum ada materi di dalam bab ini. Klik "+ Materi" atau seret materi ke sini.
            </div>
            <div
              v-for="(item, idx) in group.items"
              :key="item.id"
              draggable="true"
              @dragstart.stop="onItemDragStart(group.id, idx, $event)"
              @dragover.prevent.stop="onItemDragOver(group.id, idx)"
              @drop.prevent.stop="onItemDrop(group.id, idx)"
              @dragend="clearDrag"
              :class="[
                'flex flex-col sm:flex-row sm:items-center gap-3 bg-gray-50/70 hover:bg-purple-50/30 p-3 rounded-xl border transition-all',
                dragOverItem?.groupId === group.id && dragOverItem?.idx === idx ? 'border-[#662FFF]' : 'border-gray-100',
              ]"
            >
              <div class="flex items-center gap-3 flex-1 min-w-0">
                <span class="cursor-grab text-gray-400 hover:text-[#662FFF]" title="Geser materi">
                  <i class="pi pi-bars text-xs"></i>
                </span>
                <span class="shrink-0 w-7 h-7 rounded-full bg-[#662FFF] text-white text-xs font-bold flex items-center justify-center">
                  {{ idx + 1 }}
                </span>
                <div class="min-w-0 flex-1">
                  <p class="font-semibold text-sm text-[#060A23] truncate">{{ item.title }}</p>
                  <p class="text-[11px] text-[#838C9D] capitalize">{{ formatTypeLabel(item) }}</p>
                </div>
              </div>

              <div class="flex flex-wrap items-center gap-1.5 sm:justify-end">
                <button
                  type="button"
                  :disabled="idx === 0 || savingOrder"
                  @click="moveItem(group.id, idx, -1)"
                  title="Naikkan"
                  class="rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-xs text-gray-700 hover:bg-gray-50 disabled:opacity-30 cursor-pointer"
                >
                  <i class="pi pi-arrow-up text-[11px]"></i>
                </button>
                <button
                  type="button"
                  :disabled="idx === group.items.length - 1 || savingOrder"
                  @click="moveItem(group.id, idx, 1)"
                  title="Turunkan"
                  class="rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-xs text-gray-700 hover:bg-gray-50 disabled:opacity-30 cursor-pointer"
                >
                  <i class="pi pi-arrow-down text-[11px]"></i>
                </button>
                <select
                  :value="group.id"
                  @change="moveItemToGroup(group.id, idx, $event.target.value)"
                  title="Pindahkan ke Bab"
                  class="rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs text-gray-700 max-w-[150px]"
                >
                  <option value="">Tanpa Bab</option>
                  <option v-for="ch in chapters" :key="ch.id" :value="ch.id">{{ ch.title }}</option>
                </select>
                <RouterLink
                  v-if="item.quiz_id"
                  :to="`/quiz/manage/${item.quiz_id}/results`"
                  class="rounded-full border border-purple-400 px-3 py-1.5 text-xs font-semibold text-purple-700 hover:bg-purple-50"
                >
                  Hasil Quiz
                </RouterLink>
                <RouterLink
                  :to="`/course/update-contents/${props.courseId}/${item.id}`"
                  class="rounded-full border border-[#060A23] px-3 py-1.5 text-xs font-semibold text-[#060A23] hover:bg-gray-100"
                >
                  Ubah
                </RouterLink>
                <button
                  type="button"
                  @click="handleDeleteContent(item.id)"
                  class="rounded-full bg-[#FF435A] hover:bg-red-600 px-3 py-1.5 text-xs font-semibold text-white cursor-pointer"
                >
                  Hapus
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Tanpa Bab (Unassigned / Legacy materials) -->
        <div
          v-if="orphanItems.length > 0 || chapters.length > 0"
          class="rounded-2xl bg-white border border-[#E6ECF5] overflow-hidden shadow-xs"
        >
          <div class="flex items-center justify-between px-4 py-3.5 bg-gray-50 border-b border-[#E6ECF5]">
            <button
              type="button"
              @click="toggleExpand('orphan')"
              class="flex items-center gap-2 text-left cursor-pointer"
            >
              <i :class="['pi text-xs text-gray-500 transition-transform', isExpanded('orphan') ? 'pi-chevron-down' : 'pi-chevron-right']"></i>
              <i class="pi pi-list text-gray-500"></i>
              <span class="font-bold text-sm text-gray-700">Materi Tanpa Bab</span>
              <span class="rounded-full bg-gray-200 text-gray-700 px-2.5 py-0.5 text-[11px] font-semibold">
                {{ orphanItems.length }} materi
              </span>
            </button>
          </div>
          <div
            v-show="isExpanded('orphan')"
            @dragover.prevent="onItemContainerDragOver(null)"
            @drop.prevent="onItemDropOnGroup(null)"
            class="p-3 sm:p-4 space-y-2.5 min-h-[48px]"
          >
            <div v-if="orphanItems.length === 0" class="rounded-xl border border-dashed border-gray-200 py-3 text-center text-xs text-[#838C9D]">
              Tidak ada materi di luar Bab.
            </div>
            <div
              v-for="(item, idx) in orphanItems"
              :key="item.id"
              draggable="true"
              @dragstart.stop="onItemDragStart(null, idx, $event)"
              @dragover.prevent.stop="onItemDragOver(null, idx)"
              @drop.prevent.stop="onItemDrop(null, idx)"
              @dragend="clearDrag"
              class="flex flex-col sm:flex-row sm:items-center gap-3 bg-gray-50/70 hover:bg-purple-50/30 p-3 rounded-xl border border-gray-100 transition-all"
            >
              <div class="flex items-center gap-3 flex-1 min-w-0">
                <span class="cursor-grab text-gray-400 hover:text-[#662FFF]">
                  <i class="pi pi-bars text-xs"></i>
                </span>
                <span class="shrink-0 w-7 h-7 rounded-full bg-gray-600 text-white text-xs font-bold flex items-center justify-center">
                  {{ idx + 1 }}
                </span>
                <div class="min-w-0 flex-1">
                  <p class="font-semibold text-sm text-[#060A23] truncate">{{ item.title }}</p>
                  <p class="text-[11px] text-[#838C9D] capitalize">{{ formatTypeLabel(item) }}</p>
                </div>
              </div>

              <div class="flex flex-wrap items-center gap-1.5 sm:justify-end">
                <button
                  type="button"
                  :disabled="idx === 0 || savingOrder"
                  @click="moveItem(null, idx, -1)"
                  title="Naikkan"
                  class="rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-xs text-gray-700 hover:bg-gray-50 disabled:opacity-30 cursor-pointer"
                >
                  <i class="pi pi-arrow-up text-[11px]"></i>
                </button>
                <button
                  type="button"
                  :disabled="idx === orphanItems.length - 1 || savingOrder"
                  @click="moveItem(null, idx, 1)"
                  title="Turunkan"
                  class="rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-xs text-gray-700 hover:bg-gray-50 disabled:opacity-30 cursor-pointer"
                >
                  <i class="pi pi-arrow-down text-[11px]"></i>
                </button>
                <select
                  value=""
                  @change="moveItemToGroup(null, idx, $event.target.value)"
                  title="Pindahkan ke Bab"
                  class="rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs text-gray-700 max-w-[150px]"
                >
                  <option value="">Tanpa Bab</option>
                  <option v-for="ch in chapters" :key="ch.id" :value="ch.id">{{ ch.title }}</option>
                </select>
                <RouterLink
                  v-if="item.quiz_id"
                  :to="`/quiz/manage/${item.quiz_id}/results`"
                  class="rounded-full border border-purple-400 px-3 py-1.5 text-xs font-semibold text-purple-700 hover:bg-purple-50"
                >
                  Hasil Quiz
                </RouterLink>
                <RouterLink
                  :to="`/course/update-contents/${props.courseId}/${item.id}`"
                  class="rounded-full border border-[#060A23] px-3 py-1.5 text-xs font-semibold text-[#060A23] hover:bg-gray-100"
                >
                  Ubah
                </RouterLink>
                <button
                  type="button"
                  @click="handleDeleteContent(item.id)"
                  class="rounded-full bg-[#FF435A] hover:bg-red-600 px-3 py-1.5 text-xs font-semibold text-white cursor-pointer"
                >
                  Hapus
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Inline Modal Tambah / Edit Bab -->
    <Teleport to="body">
      <div
        v-if="chapterModal.open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="chapterModal.open = false"
      >
        <form
          @submit.prevent="submitChapterModal"
          class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl space-y-4 font-['Poppins']"
        >
          <div class="flex items-center justify-between">
            <h3 class="font-bold text-lg text-[#060A23]">
              {{ chapterModal.id ? 'Ubah Nama Bab / Modul' : 'Tambah Bab / Modul' }}
            </h3>
            <button type="button" @click="chapterModal.open = false" class="text-gray-400 hover:text-gray-700 text-xl font-bold cursor-pointer">&times;</button>
          </div>
          <div>
            <label for="chapter-title" class="block text-sm font-semibold text-[#060A23] mb-1.5">
              Judul Bab / Modul
            </label>
            <input
              id="chapter-title"
              v-model="chapterModal.title"
              type="text"
              maxlength="150"
              required
              autofocus
              placeholder="Contoh: Bab 1 - Pengenalan Gadai"
              class="w-full rounded-full border border-[#CFDBEF] px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-[#662FFF]"
            />
            <p v-if="chapterModal.error" class="text-xs text-red-600 mt-1.5">{{ chapterModal.error }}</p>
          </div>
          <div class="flex justify-end gap-2 pt-2">
            <button
              type="button"
              @click="chapterModal.open = false"
              class="rounded-full border border-gray-300 px-5 py-2 text-xs sm:text-sm font-semibold text-gray-700 hover:bg-gray-50 cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="chapterModal.saving"
              class="rounded-full bg-[#662FFF] hover:bg-[#5422DB] px-5 py-2 text-xs sm:text-sm font-semibold text-white cursor-pointer disabled:opacity-50"
            >
              {{ chapterModal.saving ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </form>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { RouterLink, onBeforeRouteLeave } from '@/navigation';
import { apiInstance as axios } from '@/utils/axios';
import { deleteContent } from '@/services/mutations/contentMutation.ts';
import { courseContentType } from '@/utils/courseContentType.mjs';
import Swal from 'sweetalert2';

const props = defineProps({
  contents: { type: Object, required: true },
  courseId: { type: [String, Number], required: true },
});

const emit = defineEmits(['update:contents']);

const loadingStructure = ref(false);
const savingOrder = ref(false);
const structureError = ref('');
const chapters = ref([]); // [{ id, title, items: [...] }]
const orphanItems = ref([]);
const originalIds = ref([]);
const initialSnapshot = ref('');
const expanded = ref({ orphan: true });

// Chapter modal
const chapterModal = ref({ open: false, id: null, title: '', saving: false, error: '' });

// Drag state
const dragState = ref(null);
const dragOverGroupIdx = ref(null);
const dragOverItem = ref(null);

function buildStructure(list) {
  const parents = list.filter((x) => x.type === 'parent');
  const children = list.filter((x) => x.type !== 'parent');
  const parentIds = new Set(parents.map((p) => Number(p.id)));

  const grouped = parents.map((p) => {
    expanded.value[p.id] = expanded.value[p.id] ?? true;
    return {
      id: Number(p.id),
      title: p.title,
      items: children.filter((c) => Number(c.parent_id) === Number(p.id)),
    };
  });

  const orphans = children.filter((c) => !c.parent_id || !parentIds.has(Number(c.parent_id)));
  chapters.value = grouped;
  orphanItems.value = orphans;
  originalIds.value = list.map((x) => Number(x.id));
  initialSnapshot.value = serializeState();
}

function flattenState() {
  const result = [];
  for (const item of orphanItems.value) {
    result.push({ id: Number(item.id), parent_id: null });
  }
  for (const group of chapters.value) {
    result.push({ id: Number(group.id), parent_id: null });
    for (const item of group.items) {
      result.push({ id: Number(item.id), parent_id: Number(group.id) });
    }
  }
  return result;
}

function serializeState() {
  return JSON.stringify(flattenState());
}

const isDirty = computed(() => {
  if (!initialSnapshot.value) return false;
  return serializeState() !== initialSnapshot.value;
});

async function loadStructure() {
  if (!props.courseId) return;
  loadingStructure.value = true;
  structureError.value = '';
  try {
    const { data } = await axios.get(`/api/courses/${props.courseId}/content-structure`);
    buildStructure(data.contents || []);
  } catch (e) {
    structureError.value = e?.response?.data?.message || 'Gagal memuat struktur materi.';
  } finally {
    loadingStructure.value = false;
  }
}

function resetOrder() {
  loadStructure();
}

async function saveOrder() {
  if (!isDirty.value || savingOrder.value) return;
  savingOrder.value = true;
  structureError.value = '';
  try {
    await axios.put(`/api/courses/${props.courseId}/content-structure`, {
      items: flattenState(),
      original_ids: originalIds.value,
    });
    await loadStructure();
    Swal.fire({
      title: 'Tersimpan!',
      text: 'Urutan bab dan materi berhasil disimpan.',
      icon: 'success',
      confirmButtonColor: '#662FFF',
      timer: 1400,
    });
  } catch (e) {
    structureError.value = e?.response?.data?.message || 'Gagal menyimpan urutan.';
  } finally {
    savingOrder.value = false;
  }
}

function toggleExpand(key) {
  expanded.value[key] = !isExpanded(key);
}

function isExpanded(key) {
  return expanded.value[key] !== false;
}

function formatTypeLabel(item) {
  const kind = courseContentType(item);
  if (kind === 'google_form') return 'Google Form';
  if (kind === 'quiz') return 'Quiz';
  if (kind === 'dokumen' || kind === 'pdf') return 'Dokumen';
  return kind || 'Materi';
}

// Reorder helpers
function moveGroup(idx, offset) {
  const target = idx + offset;
  if (target < 0 || target >= chapters.value.length) return;
  const copy = [...chapters.value];
  [copy[idx], copy[target]] = [copy[target], copy[idx]];
  chapters.value = copy;
}

function getGroupList(groupId) {
  if (groupId === null) return orphanItems.value;
  return chapters.value.find((g) => Number(g.id) === Number(groupId))?.items || null;
}

function moveItem(groupId, idx, offset) {
  const list = getGroupList(groupId);
  if (!list) return;
  const target = idx + offset;
  if (target < 0 || target >= list.length) return;
  [list[idx], list[target]] = [list[target], list[idx]];
}

function moveItemToGroup(fromGroupId, idx, toGroupVal) {
  const toGroupId = toGroupVal === '' || toGroupVal === null ? null : Number(toGroupVal);
  if (fromGroupId === toGroupId) return;
  const fromList = getGroupList(fromGroupId);
  const toList = getGroupList(toGroupId);
  if (!fromList || !toList) return;
  const [moved] = fromList.splice(idx, 1);
  if (moved) {
    toList.push(moved);
    if (toGroupId !== null) expanded.value[toGroupId] = true;
  }
}

// Drag & drop
function onGroupDragStart(gIdx, event) {
  dragState.value = { kind: 'group', gIdx };
  event.dataTransfer.effectAllowed = 'move';
}
function onGroupDragOver(gIdx) {
  if (dragState.value?.kind === 'group') dragOverGroupIdx.value = gIdx;
}
function onGroupDrop(targetIdx) {
  if (dragState.value?.kind === 'group') {
    const fromIdx = dragState.value.gIdx;
    if (fromIdx !== targetIdx) {
      const copy = [...chapters.value];
      const [moved] = copy.splice(fromIdx, 1);
      copy.splice(targetIdx, 0, moved);
      chapters.value = copy;
    }
  } else if (dragState.value?.kind === 'item') {
    const targetGroup = chapters.value[targetIdx];
    if (targetGroup) onItemDropOnGroup(targetGroup.id);
  }
  clearDrag();
}

function onItemDragStart(groupId, idx, event) {
  dragState.value = { kind: 'item', groupId, idx };
  event.dataTransfer.effectAllowed = 'move';
}
function onItemDragOver(groupId, idx) {
  if (dragState.value?.kind === 'item') dragOverItem.value = { groupId, idx };
}
function onItemContainerDragOver(groupId) {
  if (dragState.value?.kind === 'item') dragOverItem.value = { groupId, idx: -1 };
}
function onItemDrop(targetGroupId, targetIdx) {
  if (dragState.value?.kind !== 'item') return;
  const { groupId: fromGroupId, idx: fromIdx } = dragState.value;
  const fromList = getGroupList(fromGroupId);
  const toList = getGroupList(targetGroupId);
  if (!fromList || !toList) return;
  const [moved] = fromList.splice(fromIdx, 1);
  if (moved) {
    toList.splice(targetIdx, 0, moved);
  }
  clearDrag();
}
function onItemDropOnGroup(targetGroupId) {
  if (dragState.value?.kind !== 'item') return;
  const { groupId: fromGroupId, idx: fromIdx } = dragState.value;
  const fromList = getGroupList(fromGroupId);
  const toList = getGroupList(targetGroupId);
  if (!fromList || !toList) return;
  const [moved] = fromList.splice(fromIdx, 1);
  if (moved) {
    toList.push(moved);
  }
  clearDrag();
}
function clearDrag() {
  dragState.value = null;
  dragOverGroupIdx.value = null;
  dragOverItem.value = null;
}

// Chapter modal
function openChapterModal(group = null) {
  chapterModal.value = {
    open: true,
    id: group ? group.id : null,
    title: group ? group.title : '',
    saving: false,
    error: '',
  };
}

async function submitChapterModal() {
  const title = chapterModal.value.title.trim();
  if (!title) {
    chapterModal.value.error = 'Judul Bab / Modul wajib diisi.';
    return;
  }
  chapterModal.value.saving = true;
  chapterModal.value.error = '';
  try {
    if (chapterModal.value.id) {
      await axios.patch(`/api/courses/${props.courseId}/chapters/${chapterModal.value.id}`, { title });
    } else {
      await axios.post(`/api/courses/${props.courseId}/chapters`, { title });
    }
    chapterModal.value.open = false;
    await loadStructure();
  } catch (e) {
    chapterModal.value.error = e?.response?.data?.message || 'Gagal menyimpan Bab / Modul.';
  } finally {
    chapterModal.value.saving = false;
  }
}

async function handleDeleteChapter(group) {
  const res = await Swal.fire({
    title: `Hapus Bab "${group.title}"?`,
    text: group.items.length > 0
      ? `Bab ini berisi ${group.items.length} materi. Menghapus Bab tidak menghapus materi (materi akan dipindahkan ke "Materi Tanpa Bab").`
      : 'Bab ini akan dihapus dari kelas.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#FF435A',
    cancelButtonColor: '#838C9D',
    confirmButtonText: 'Ya, Hapus Bab',
    cancelButtonText: 'Batal',
  });
  if (!res.isConfirmed) return;
  try {
    await deleteContent(group.id);
    await loadStructure();
  } catch (e) {
    Swal.fire({ title: 'Gagal!', text: 'Gagal menghapus Bab.', icon: 'error', confirmButtonColor: '#662FFF' });
  }
}

async function handleDeleteContent(contentId) {
  const res = await Swal.fire({
    title: 'Apakah Anda yakin?',
    text: 'Materi ini akan dihapus dari kelas!',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#662FFF',
    cancelButtonColor: '#838C9D',
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
  });
  if (!res.isConfirmed) return;
  try {
    await deleteContent(contentId);
    await loadStructure();
  } catch (e) {
    Swal.fire({ title: 'Gagal!', text: 'Gagal menghapus materi.', icon: 'error', confirmButtonColor: '#662FFF' });
  }
}

const canDiscard = () => !isDirty.value || window.confirm('Urutan materi belum disimpan. Abaikan perubahan?');
function beforeUnload(e) {
  if (isDirty.value || savingOrder.value) {
    e.preventDefault();
    e.returnValue = '';
  }
}

onBeforeRouteLeave(() => !savingOrder.value && canDiscard());
onMounted(() => {
  loadStructure();
  window.addEventListener('beforeunload', beforeUnload);
});
onUnmounted(() => window.removeEventListener('beforeunload', beforeUnload));
watch(() => props.courseId, () => loadStructure());
</script>
