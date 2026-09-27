<template>
  <div class="flex flex-col gap-5 w-full pb-[160px]">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div>
        <h1 class="font-bold text-[28px] md:text-[32px] leading-[40px] text-gray-800">
          {{ content.title || 'Formulir / Kuis Google Form' }}
        </h1>
        <p class="text-xs text-gray-500 mt-1" v-if="content.description">
          {{ content.description }}
        </p>
      </div>

      <!-- Action Button: Open in New Tab -->
      <a v-if="rawFormUrl" :href="rawFormUrl" target="_blank"
        class="inline-flex items-center gap-2 px-4 py-2 bg-purple-100 text-purple-700 font-semibold text-xs rounded-xl hover:bg-purple-200 transition shrink-0">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
          class="w-4 h-4">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
        </svg>
        Buka di Tab Baru
      </a>
    </div>

    <!-- Web View Google Form Container -->
    <div
      class="relative w-full h-[700px] md:h-[800px] rounded-2xl overflow-hidden border border-gray-200 shadow-md bg-white flex items-center justify-center">
      <!-- Loading State -->
      <div v-if="isLoading"
        class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-gray-50 text-purple-700 gap-3">
        <svg class="animate-spin h-8 w-8 text-purple-600" xmlns="http://www.w3.org/2000/svg" fill="none"
          viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
          </path>
        </svg>
        <span class="text-sm font-medium">Memuat Google Form...</span>
      </div>

      <!-- Iframe Google Form -->
      <iframe v-if="embedUrl" :src="embedUrl" class="w-full h-full border-0 relative z-10" frameborder="0"
        marginheight="0" marginwidth="0" allowfullscreen @load="handleIframeLoad">Memuat Google Form...</iframe>

      <!-- Fallback when no valid URL -->
      <div v-else-if="!isLoading" class="text-center p-8 text-gray-500">
        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none"
          viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <p class="font-semibold text-gray-600">Tautan Google Form Belum Diatur</p>
        <p class="text-xs text-gray-400 mt-1">Pastikan URL atau embed code Google Form dimasukkan dengan benar.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { googleFormUrl } from '@/utils/courseContentType.mjs';

const props = defineProps({
  content: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['form-submitted-change']);

const isLoading = ref(true);
const loadCount = ref(0);
const isFormSubmitted = ref(false);

function handleIframeLoad() {
  isLoading.value = false;
  loadCount.value++;
  console.log('Google Form iframe load count:', loadCount.value);
  // Detect automatic redirect on Google Form submit (loadCount >= 2)
  if (loadCount.value >= 2) {
    isFormSubmitted.value = true;
    emit('form-submitted-change', true);
  }
}

function toggleSubmitted() {
  isFormSubmitted.value = !isFormSubmitted.value;
  emit('form-submitted-change', isFormSubmitted.value);
}

// Extract raw link from content (supports props.content.url, props.content.form_url, props.content.content, or string)
const rawFormUrl = computed(() => googleFormUrl(props.content));

// Format embed URL with embedded=true parameter
const embedUrl = computed(() => {
  const url = rawFormUrl.value;
  if (!url) return '';

  // If already contains embedded=true
  if (url.includes('embedded=true')) return url;

  // Add embedded=true query param
  if (url.includes('?')) {
    return `${url}&embedded=true`;
  }
  return `${url}?embedded=true`;
});

watch(() => props.content, () => {
  isLoading.value = Boolean(embedUrl.value);
  loadCount.value = 0;
  isFormSubmitted.value = false;
  emit('form-submitted-change', false);
}, { immediate: true });
</script>
