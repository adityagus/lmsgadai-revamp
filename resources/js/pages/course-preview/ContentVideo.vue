<template>
  <div class="flex flex-col gap-5 w-full pb-[160px]">
    <!-- Video Player -->
    <div class="flex shrink-0 h-[calc(100vh-110px-104px)] rounded-[20px] overflow-hidden bg-black">
      <iframe v-if="videoUrl" class="w-full aspect-video" :src="videoUrl" :title="content.title || 'Video Player'"
        frameBorder="0"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
        allowFullScreen="true"></iframe>

      <!-- Fallback when no video URL -->
      <div v-else class="w-full flex items-center justify-center bg-gray-100">
        <div class="text-center">
          <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
          </svg>
          <p class="text-gray-500 mb-2">No video available</p>
          <p class="text-xs text-gray-400" v-if="content.content">Content: {{ content.content.substring(0, 100) }}...
          </p>
        </div>
      </div>
    </div>

    <!-- Video Title -->
    <div class="flex items-center justify-between gap-5">
      <h1 class="font-bold text-[32px] leading-[48px]">
        {{ content.title || 'Video Title' }}
      </h1>
    </div>

    <!-- Video Description (if available) -->
    <div v-if="content.description || content.content" class="prose max-w-none">
      <div v-html="content.description || content.content"></div>
    </div>

    <!-- Download Attachment if available -->
    <div class="flex justify-end" v-if="content.attachment">
      <button @click="handleDownloadAttachment"
        class="btn bg-purple-600 py-2 px-5 rounded-lg text-white hover:bg-purple-700 transition-colors inline-flex items-center gap-2 text-sm font-medium cursor-pointer">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
          class="w-4 h-4">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
        </svg>
        Download Resource
      </button>
    </div>
  </div>
</template>

<script setup>
import { defineProps, computed, onMounted } from 'vue';
import { downloadWatermarkedPdf } from '@/utils/pdfWatermark';

const props = defineProps({
  content: {
    type: Object,
    default: () => ({})
  }
});

function handleDownloadAttachment() {
  const url = props.content.attachment;
  if (!url) return;
  const filename = url.split('/').pop() || 'resource.pdf';
  if (url.toLowerCase().includes('.pdf')) {
    downloadWatermarkedPdf(url, filename);
  } else {
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.target = '_blank';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
  }
}

// Debug content data
onMounted(() => {
  console.log('ContentVideo received content:', props.content);
});

// Function to extract video URL and convert to embeddable format
const videoUrl = computed(() => {
  let url = props.content.video_url || props.content.url || props.content.content || props.content.body;

  console.log('Processing video URL:', url);

  if (!url) return null;
  url = String(url).trim();

  // If input is an oembed tag like <oembed url="https://..."></oembed>
  const oembedMatch = url.match(/url=["']([^"']+)["']/i);
  if (oembedMatch) {
    url = oembedMatch[1];
  } else {
    // If input is an HTML string containing a URL
    const hrefMatch = url.match(/https?:\/\/[^\s<>"']+/i);
    if (hrefMatch && !url.startsWith('http')) {
      url = hrefMatch[0];
    }
  }

  // 1. YouTube watch, youtu.be, embed, or shorts URL
  const ytMatch = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([\w-]{11})/i);
  if (ytMatch && ytMatch[1]) {
    return `https://www.youtube.com/embed/${ytMatch[1]}`;
  }

  // 2. Direct 11-character YouTube Video ID (e.g. dQw4w9WgXcQ)
  if (/^[\w-]{11}$/.test(url)) {
    return `https://www.youtube.com/embed/${url}`;
  }

  // 3. Vimeo URL conversion
  if (url.includes('vimeo.com/')) {
    const vimeoMatch = url.match(/vimeo\.com\/(?:video\/)?(\d+)/i);
    if (vimeoMatch && vimeoMatch[1]) {
      return `https://player.vimeo.com/video/${vimeoMatch[1]}`;
    }
  }

  // 4. Check if it's already an embed URL
  if (url.includes('/embed/') || url.includes('player.vimeo.com')) {
    return url;
  }

  // 5. Direct video file URL (.mp4, .webm, .ogg)
  if (url.startsWith('http')) {
    return url;
  }

  return null;
});
</script>

<style scoped>
.prose {
  line-height: 1.7;
  color: #374151;
}

.prose h1,
.prose h2,
.prose h3,
.prose h4,
.prose h5,
.prose h6 {
  font-weight: bold;
  margin-top: 1.5em;
  margin-bottom: 0.5em;
  color: #111827;
}

.prose p {
  margin-bottom: 1em;
  text-align: justify;
}

.prose ul,
.prose ol {
  margin-bottom: 1em;
  padding-left: 1.5em;
}

.prose li {
  margin-bottom: 0.5em;
}

.prose a {
  color: #662FFF;
  text-decoration: underline;
}

.prose a:hover {
  color: #5521cc;
}

.prose strong {
  font-weight: bold;
  color: #111827;
}

.btn {
  display: inline-block;
  text-decoration: none;
}
</style>