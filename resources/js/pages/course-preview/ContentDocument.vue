<template>
  <ContentPdf v-if="isPdf" :content="content" />
  <div v-else class="w-full rounded-2xl border border-[#CFDBEF] bg-white p-6 sm:p-10 flex flex-col items-center text-center gap-4 shadow-xs">
    <div class="w-16 h-16 rounded-2xl bg-purple-50 text-[#662FFF] flex items-center justify-center">
      <i class="pi pi-file-arrow-up text-3xl"></i>
    </div>
    <div>
      <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-[#662FFF] uppercase mb-2">
        {{ extensionLabel }}
      </span>
      <h3 class="font-bold text-lg text-[#060A23] break-all">{{ fileName }}</h3>
      <p class="text-sm text-[#838C9D] mt-1">
        Dokumen {{ extensionLabel }} siap diunduh untuk dipelajari di perangkat Anda.
      </p>
    </div>
    <a
      :href="downloadUrl"
      download
      target="_blank"
      rel="noopener noreferrer"
      class="inline-flex items-center gap-2 rounded-full bg-[#662FFF] hover:bg-[#5422DB] text-white font-semibold text-sm px-6 py-3 transition-colors"
    >
      <i class="pi pi-download"></i>
      Unduh Dokumen
    </a>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import ContentPdf from './ContentPdf.vue';

const props = defineProps({
  content: { type: String, default: '' },
  title: { type: String, default: '' },
});

const cleanPath = computed(() => {
  const raw = String(props.content || '').trim();
  return raw.replace(/^\/+/, '').replace(/^storage\/aktif\/uploads\//, 'uploads/').replace(/^aktif\/uploads\//, 'uploads/');
});

const fileName = computed(() => {
  const parts = cleanPath.value.split('?')[0].split('/');
  return parts[parts.length - 1] || props.title || 'dokumen';
});

const extension = computed(() => {
  const match = fileName.value.toLowerCase().match(/\.([a-z0-9]+)$/);
  return match ? match[1] : '';
});

const isPdf = computed(() => extension.value === 'pdf' || extension.value === '');

const extensionLabel = computed(() => {
  const ext = extension.value.toUpperCase();
  if (ext === 'XLS' || ext === 'XLSX') return `Excel (${ext})`;
  if (ext === 'PPT' || ext === 'PPTX') return `PowerPoint (${ext})`;
  if (ext === 'DOC' || ext === 'DOCX') return `Word (${ext})`;
  return ext || 'DOKUMEN';
});

const downloadUrl = computed(() => {
  const raw = String(props.content || '').trim();
  if (!raw) return '#';
  if (raw.startsWith('http://') || raw.startsWith('https://') || raw.startsWith('blob:')) return raw;
  const path = cleanPath.value.startsWith('storage/') ? `/${cleanPath.value}` : `/storage/${cleanPath.value}`;
  return `${path}?download=1`;
});
</script>
