<template>
  <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-2 sm:p-4">
    <div class="bg-white w-full max-w-[1200px] max-h-[96vh] sm:max-h-[95vh] rounded-2xl shadow-2xl p-0 relative flex flex-col overflow-hidden">
      <!-- Fixed Modal Header Bar (Outside scroll area so overlap is physically impossible) -->
      <div v-if="selectedCard" class="w-full bg-white border-b border-gray-100 shadow-xs px-4 sm:px-8 py-3 relative z-30 shrink-0">
        <div class="flex items-center gap-2.5 sm:gap-3 pr-10" id='header'>
          <div class="rounded-lg bg-sidebar p-2 flex items-center justify-center shrink-0">
            <img src="https://unpkg.com/heroicons@2.0.13/24/solid/document.svg" class="w-6 h-6 sm:w-8 sm:h-8 filter-white-svg"
              alt="icon" />
          </div>
          <div class="flex flex-col sm:flex-row justify-between w-full items-start sm:items-center gap-2">
            <div>
              <h2 class="font-bold text-sm sm:text-base text-sidebar line-clamp-2 sm:line-clamp-1 leading-snug">{{ selectedCard.title }}</h2>
              <div class="text-[11px] sm:text-xs text-gray-500 mt-0.5">Terakhir Update: <span class="font-semibold text-sidebar">
                  {{ selectedCard.dateLastUpdate }}</span></div>
            </div>
            <div v-if="selectedCard.no_surat || selectedCard.tgl_berlaku" class="bg-gray-50 rounded-lg p-2 sm:p-3 flex flex-wrap sm:flex-col border border-gray-100 text-[11px] sm:text-xs gap-x-4 gap-y-1 w-full sm:w-auto">
              <div v-if="selectedCard.no_surat" class="flex items-center justify-between sm:justify-start gap-2 w-full sm:w-auto">
                <span class="font-semibold text-gray-500">Nomor Surat:</span>
                <span class="font-bold text-sidebar line-clamp-1">{{ selectedCard.no_surat }}</span>
              </div>
              <div v-if="selectedCard.tgl_berlaku" class="flex items-center justify-between sm:justify-start gap-2 w-full sm:w-auto">
                <span class="font-semibold text-gray-500">Tanggal Berlaku:</span>
                <span class="font-bold text-sidebar">{{ selectedCard.tgl_berlaku }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Responsive Close Button -->
        <button @click="closeModal"
          class="absolute top-3.5 right-3.5 text-gray-400 hover:text-gray-700 text-xl font-bold z-50 w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 shadow-xs flex items-center justify-center transition active:scale-90 cursor-pointer"
          title="Tutup Modal">&times;</button>
      </div>

      <!-- Separate Scrollable Body Container Below Header -->
      <div v-if="selectedCard" class="overflow-y-auto scrollbar-hidden p-3 sm:p-6 flex-1 bg-gray-50/40">
        <!-- Document Viewer Container -->
        <div class="overflow-hidden rounded-xl sm:rounded-2xl mx-0 relative">
          <ContentPdf
            :content="selectedCard.content_url || selectedCard.url || selectedCard.file || selectedCard.content || selectedCard.lampiran || selectedCard.path || selectedCard.file_path"
            class="min-h-[350px] sm:min-h-[500px]"
            v-if="selectedCard.type === 'pdf' || selectedCard.type === null || !selectedCard.type"
          />
          <ContentText :content="selectedCard"
            class='shadow-sm shadow-purple-600 mt-4 pb-3 px-3 border mx-2 rounded-lg'
            v-if='selectedCard.type === "text"' />
        </div>
        <p v-if="selectedCard.desc" class="text-gray-700 my-3 text-xs sm:text-sm px-1 sm:px-2">{{ selectedCard.desc }}</p>
      </div>
      <div class="p-2 border-t border-gray-100 bg-white z-10 shrink-0">
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, defineExpose } from 'vue';
import ContentPdf from '@/pages/course-preview/ContentPdf.vue';
import ContentText from '@/pages/course-preview/ContentText.vue';
import { getSession } from '@/services/queries/authQuery.ts';
import { formDownloadUrl, downloadForm } from '@/utils/formDownload.js';
import Swal from 'sweetalert2';

const props = defineProps({
  // cards: Array,
  showModal: Boolean,
  selectedCard: Object
});

const auth = ref({});

const userWatermarkText = computed(() => {
  const nik = auth.value?.npk || auth.value?.npk || '';
  const nama = auth.value?.nama || auth.value?.name || '';
  if (nik || nama) {
    return [nik, nama].filter(Boolean).join(' - ');
  }
  return 'DOKUMEN RESMI GADAI MULIA';
});

onMounted(async () => {
  try {
    const restAuth = await getSession();
    auth.value = restAuth?.auth || {};
  } catch (e) {
    console.warn('Error getting session:', e);
  }
});

console.log("openModalPDF component loaded with cards:", props.cards);


const emit = defineEmits(['update:showModal', 'update:selectedCard']);



function closeModal() {
  emit('update:showModal', false);
  emit('update:selectedCard', null);
}

function openModal(card) {
  const downloadUrl = formDownloadUrl(card);
  if (downloadUrl !== null) {
    closeModal();
    if (downloadUrl) downloadForm(downloadUrl);
    else Swal.fire({ icon: 'error', title: 'File formulir tidak tersedia', text: 'Hubungi pengelola dokumen untuk melengkapi file.' });
    return;
  }
  emit('update:showModal', true);
  emit('update:selectedCard', card);
}

defineExpose({ openModal });
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
