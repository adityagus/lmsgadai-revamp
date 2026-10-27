<template>
  <div class="flex flex-col gap-5 pb-[160px]">
    <div class="header w-full flex justify-between items-center">
      <div class="title">
        <h1 class="font-bold text-[32px] leading-[48px] text-gray-700">
          {{ content.title || 'Content Title' }}
        </h1>
      </div>
      <!-- Download Attachment if available -->
      <div class="" v-if="content.lampiran || content.url">
        <a :href="getAttachmentUrl(content.lampiran || content.url)" target="_blank"
          class="inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition cursor-pointer">
          Download Lampiran
        </a>
      </div>
    </div>

    <!-- Content Body -->
    <article id="Content-wrapper" class="ck-content max-w-none" ref="contentWrapper">
      <div v-if="content.body" v-html="content.body"></div>
      <div v-else-if="content.content" v-html="content.content"></div>
      <div v-else class="text-center py-8">
        <p class="text-gray-500">No content available</p>
      </div>
    </article>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUpdated, defineProps } from 'vue';
import { getSession } from '@/services/queries/authQuery.ts';
import { downloadWatermarkedPdf } from '@/utils/pdfWatermark';

const props = defineProps({
  content: {
    type: Object,
    default: () => ({})
  }
});



const contentWrapper = ref(null);

function getAttachmentUrl(path) {
  if (!path) return '#';
  if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('blob:')) {
    return path.replace('/storage/aktif/uploads/', '/storage/uploads/');
  }
  let clean = path.replace(/^\/+/, '').replace(/^storage\/aktif\/uploads\//, 'storage/uploads/').replace(/^aktif\/uploads\//, 'uploads/');
  if (clean.startsWith('storage/')) {
    return '/' + clean;
  }
  if (clean.startsWith('uploads/') || clean.startsWith('aktif/') || clean.startsWith('Memo/')) {
    return '/storage/' + clean;
  }
  return '/storage/aktif/' + clean;
}


function buildIframeForUrl(rawUrl) {
  if (!rawUrl) return null;
  const url = rawUrl.trim();

  // 1. YouTube (watch?v=, youtu.be, embed, shorts)
  const ytMatch = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([\w-]{11})/i);
  if (ytMatch) {
    const iframe = document.createElement('iframe');
    iframe.src = `https://www.youtube.com/embed/${ytMatch[1]}`;
    iframe.style.width = '100%';
    iframe.style.aspectRatio = '16/9';
    iframe.style.minHeight = '360px';
    iframe.style.border = '0';
    iframe.style.borderRadius = '12px';
    iframe.style.display = 'block';
    iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
    iframe.allowFullscreen = true;
    return iframe;
  }

  // 2. Canva
  if (url.includes('canva.com/design/')) {
    let canvaUrl = url;
    if (!canvaUrl.includes('embed=true')) {
      canvaUrl = canvaUrl.replace(/([?&])embed(=[^&]*)?/i, '');
      canvaUrl += (canvaUrl.includes('?') ? '&' : '?') + 'embed=true';
    }
    const iframe = document.createElement('iframe');
    iframe.src = canvaUrl;
    iframe.style.width = '100%';
    iframe.style.aspectRatio = '16/9';
    iframe.style.minHeight = '480px';
    iframe.style.border = '0';
    iframe.style.borderRadius = '12px';
    iframe.style.display = 'block';
    iframe.loading = 'lazy';
    iframe.allowFullscreen = true;
    return iframe;
  }

  // 3. Google Workspace
  if (url.includes('docs.google.com/document/')) {
    const iframe = document.createElement('iframe');
    iframe.src = url.replace(/\/edit.*$/, '/preview');
    iframe.style.width = '100%';
    iframe.style.minHeight = '560px';
    iframe.style.border = '0';
    iframe.style.borderRadius = '12px';
    iframe.loading = 'lazy';
    iframe.allowFullscreen = true;
    return iframe;
  }

  if (url.includes('docs.google.com/presentation/')) {
    const iframe = document.createElement('iframe');
    iframe.src = url.replace(/\/edit.*$/, '/embed?start=false&loop=false&delayms=3000');
    iframe.style.width = '100%';
    iframe.style.aspectRatio = '16/9';
    iframe.style.minHeight = '480px';
    iframe.style.border = '0';
    iframe.style.borderRadius = '12px';
    iframe.loading = 'lazy';
    iframe.allowFullscreen = true;
    return iframe;
  }

  if (url.includes('docs.google.com/spreadsheets/')) {
    const iframe = document.createElement('iframe');
    iframe.src = url.replace(/\/edit.*$/, '/preview?widget=true&headers=false');
    iframe.style.width = '100%';
    iframe.style.minHeight = '500px';
    iframe.style.border = '0';
    iframe.style.borderRadius = '12px';
    iframe.loading = 'lazy';
    iframe.allowFullscreen = true;
    return iframe;
  }

  if (url.includes('docs.google.com/forms/') || url.includes('forms.gle/')) {
    const iframe = document.createElement('iframe');
    iframe.src = url.includes('embedded=true') ? url : `${url}${url.includes('?') ? '&' : '?'}embedded=true`;
    iframe.style.width = '100%';
    iframe.style.minHeight = '560px';
    iframe.style.border = '0';
    iframe.style.borderRadius = '12px';
    iframe.loading = 'lazy';
    iframe.allowFullscreen = true;
    return iframe;
  }

  if (url.includes('drive.google.com/file/d/')) {
    const iframe = document.createElement('iframe');
    iframe.src = url.replace(/\/view.*$/, '/preview');
    iframe.style.width = '100%';
    iframe.style.aspectRatio = '16/9';
    iframe.style.minHeight = '480px';
    iframe.style.border = '0';
    iframe.style.borderRadius = '12px';
    iframe.loading = 'lazy';
    iframe.allowFullscreen = true;
    return iframe;
  }

  // 4. Vimeo
  const vimeoMatch = url.match(/(?:vimeo\.com\/(?:video\/)?|player\.vimeo\.com\/video\/)(\d+)/i);
  if (vimeoMatch) {
    const iframe = document.createElement('iframe');
    iframe.src = `https://player.vimeo.com/video/${vimeoMatch[1]}`;
    iframe.style.width = '100%';
    iframe.style.aspectRatio = '16/9';
    iframe.style.minHeight = '400px';
    iframe.style.border = '0';
    iframe.style.borderRadius = '12px';
    iframe.allow = 'autoplay; fullscreen; picture-in-picture';
    iframe.allowFullscreen = true;
    return iframe;
  }

  // 5. Loom
  const loomMatch = url.match(/loom\.com\/share\/([a-zA-Z0-9]+)/i);
  if (loomMatch) {
    const iframe = document.createElement('iframe');
    iframe.src = `https://www.loom.com/embed/${loomMatch[1]}`;
    iframe.style.width = '100%';
    iframe.style.aspectRatio = '16/9';
    iframe.style.minHeight = '400px';
    iframe.style.border = '0';
    iframe.style.borderRadius = '12px';
    iframe.allowFullscreen = true;
    return iframe;
  }

  // 6. Generic web URL
  if (/^https?:\/\//i.test(url)) {
    const iframe = document.createElement('iframe');
    iframe.src = url;
    iframe.style.width = '100%';
    iframe.style.minHeight = '500px';
    iframe.style.border = '0';
    iframe.style.borderRadius = '12px';
    iframe.loading = 'lazy';
    iframe.allowFullscreen = true;
    return iframe;
  }

  return null;
}

function replaceOembedWithIframe() {
  if (!contentWrapper.value) return;

  // 1. Process <oembed url="..."> or data-oembed-url
  const oembeds = contentWrapper.value.querySelectorAll('oembed[url], [data-oembed-url]');
  oembeds.forEach(el => {
    const url = el.getAttribute('url') || el.getAttribute('data-oembed-url');
    const iframe = buildIframeForUrl(url);
    if (iframe) {
      el.parentNode.replaceChild(iframe, el);
    }
  });

  // 2. Process standalone URL paragraphs (e.g. <p>https://www.canva.com/design/.../view?embed</p> or <p><a href="...">...</a></p>)
  const paragraphs = contentWrapper.value.querySelectorAll('p');
  paragraphs.forEach(p => {
    const textContent = p.textContent.trim();
    const linkChild = p.children.length === 1 && p.children[0].tagName === 'A' ? p.children[0] : null;
    const targetUrl = linkChild ? (linkChild.getAttribute('href') || textContent) : textContent;

    if (
      targetUrl &&
      /^(https?:\/\/)?(www\.)?(canva\.com\/design|youtube\.com|youtu\.be|docs\.google\.com|drive\.google\.com|forms\.gle|vimeo\.com|player\.vimeo\.com|loom\.com\/share)/i.test(targetUrl)
    ) {
      const iframe = buildIframeForUrl(targetUrl);
      if (iframe) {
        p.parentNode.replaceChild(iframe, p);
      }
    }
  });

  // 3. Style existing iframes
  const existingIframes = contentWrapper.value.querySelectorAll('iframe');
  existingIframes.forEach(iframe => {
    if (!iframe.style.width) iframe.style.width = '100%';
    if (!iframe.style.borderRadius) iframe.style.borderRadius = '12px';
    iframe.style.border = '0';
    iframe.loading = 'lazy';
    iframe.allowFullscreen = true;
  });
}

onMounted(() => {
  replaceOembedWithIframe();
});
onUpdated(() => {
  replaceOembedWithIframe();
});
</script>

<style scoped>
body {
  font-family: "Poppins";
  color: #060A23;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

/* Hide scrollbar for Chrome, Safari, and Opera */
.hide-scrollbar::-webkit-scrollbar {
  display: none;
}

/* Hide scrollbar for IE, Edge, and Firefox */
.hide-scrollbar {
  -ms-overflow-style: none;
  /* IE and Edge */
  scrollbar-width: none;
  /* Firefox */
}

.ck-content blockquote {
  display: block;
  margin-block-start: 1__qem;
  margin-block-end: 1em;
  margin-inline-start: 40px;
  margin-inline-end: 40px;
}

.ck-content strong,
.ck-content b {
  font-weight: bold;
}

.ck-content i,
.ck-content address {
  font-style: italic;
}

/* heading elements */

.ck-content h1 {
  display: block;
  font-size: 2em;
  margin-block-start: 0.67__qem;
  margin-block-end: 0.67em;
  margin-inline-start: 0;
  margin-inline-end: 0;
  font-weight: bold;
}

.ck-content h2 {
  display: block;
  font-size: 1.5em;
  margin-block-start: 0.83__qem;

  margin-block-end: 0.83em;
  margin-inline-start: 0;
  margin-inline-end: 0;
  font-weight: bold;
}

.ck-content h3 {
  display: block;
  font-size: 1.17em;
  margin-block-start: 1__qem;
  margin-block-end: 1em;
  margin-inline-start: 0;
  margin-inline-end: 0;
  font-weight: bold;
}

.ck-content h4 {
  display: block;
  margin-block-start: 1.33__qem;
  margin-block-end: 1.33em;
  margin-inline-start: 0;
  margin-inline-end: 0;
  font-weight: bold;
}

.ck-content h5 {
  display: block;
  font-size: .83em;
  margin-block-start: 1.67__qem;
  margin-block-end: 1.67em;
  margin-inline-start: 0;
  margin-inline-end: 0;
  font-weight: bold;
}

.ck-content h6 {
  display: block;
  font-size: .67em;
  margin-block-start: 2.33__qem;
  margin-block-end: 2.33em;
  margin-inline-start: 0;
  margin-inline-end: 0;
  font-weight: bold;
}

/* tables */

.ck-content table {
  display: table;
  border-collapse: separate;
  border-spacing: 2px;
  border-color: gray;
}

.ck-content .table table td {
  border: 1px solid #bfbfbf !important;
}

.ck-content thead {
  display: table-header-group;
  vertical-align: middle;
  border-color: inherit;
}

.ck-content tbody {
  display: table-row-group;
  vertical-align: middle;
  border-color: inherit;
}

.ck-content tfoot {
  display: table-footer-group;
  vertical-align: middle;
  border-color: inherit;
}

/* for tables without table section elements (can happen with XHTML or dynamically created tables) */
.ck-content table>tr {
  vertical-align: middle;
}

.ck-content col {
  display: table-column;
}

.ck-content colgroup {
  display: table-column-group;
}

.ck-content tr {
  display: table-row;
  vertical-align: inherit;
  border-color: inherit;
}

.ck-content td,
.ck-content th {
  display: table-cell;
  vertical-align: inherit;
}

.ck-content th {
  font-weight: bold;
}

.ck-content caption {
  display: table-caption;
  text-align: center;
}

/* lists */

.ck-content ul,
.ck-content menu,
.ck-content dir {
  display: block;
  list-style-type: disc;
  margin-block-start: 1__qem;
  margin-block-end: 1em;
  margin-inline-start: 0;
  margin-inline-end: 0;
  padding-inline-start: 40px;
}

.ck-content ol {
  display: block;
  list-style-type: decimal;
  margin-block-start: 1__qem;
  margin-block-end: 1em;
  margin-inline-start: 0;
  margin-inline-end: 0;
  padding-inline-start: 40px;
}

.ck-content li {
  display: list-item;
  text-align: match-parent;
}

.ck-content ::marker {
  unicode-bidi: isolate;
  font-variant-numeric: tabular-nums;
  white-space: pre;
  text-transform: none;
}

.ck-content ul ul,
.ck-content ol ul {
  list-style-type: circle;
}

.ck-content ol ol ul,
.ck-content ol ul ul,
.ck-content ul ol ul,
.ck-content ul ul ul {
  list-style-type: square;
}

.ck-content a:any-link {
  color: blue;
  text-decoration: underline;
  cursor: auto;
}

.ck-content a:any-link:active {
  color: red;
}

.btn {
  display: inline-block;
  text-decoration: none;
}

.ck-content iframe {
  width: 100% !important;
  max-width: 100% !important;
  border-radius: 12px !important;
  border: 0 !important;
  margin-top: 1rem !important;
  margin-bottom: 1rem !important;
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
}

.ck-content .media {
  clear: both;
  margin-top: 1rem;
  margin-bottom: 1rem;
  display: block;
  width: 100%;
}
</style>
