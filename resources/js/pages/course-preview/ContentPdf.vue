<template>
  <div class="w-full flex flex-col justify-center items-center select-none">
    <!-- Skeleton Loading State -->
    <div v-if="isLoading" class="w-full flex flex-col items-center select-none">
      <!-- Download Button Skeleton -->
      <div v-if="allowDownload" class="w-full flex justify-end items-center mb-2 px-1">
        <div class="h-8 sm:h-9 w-32 sm:w-36 bg-purple-200/80 rounded-lg animate-pulse"></div>
      </div>
      <div
        class="w-full flex flex-col items-center gap-4 sm:gap-6 max-h-[60vh] sm:max-h-[580px] overflow-hidden p-4 sm:p-6 bg-[#2a2a2e] rounded-xl border border-gray-700 shadow-2xl select-none"
      >
        <!-- Skeleton Paper Simulation -->
        <div class="w-[85%] sm:w-[78%] max-w-[800px] h-[480px] sm:h-[510px] bg-white rounded-sm shadow-2xl p-6 sm:p-10 flex flex-col gap-5 justify-between border border-gray-200/60 animate-pulse">
          <!-- Skeleton Header -->
          <div class="flex items-center justify-between gap-4 border-b border-gray-100 pb-4">
            <div class="h-9 w-28 bg-gray-200/90 rounded-md"></div>
            <div class="flex flex-col items-end gap-1.5">
              <div class="h-3 w-36 bg-gray-200/80 rounded"></div>
              <div class="h-2.5 w-24 bg-gray-100 rounded"></div>
            </div>
          </div>

          <!-- Skeleton Content Lines -->
          <div class="flex flex-col gap-3 my-2">
            <div class="h-4 w-3/4 bg-gray-200/90 rounded"></div>
            <div class="h-3 w-full bg-gray-100 rounded"></div>
            <div class="h-3 w-11/12 bg-gray-100 rounded"></div>
            <div class="h-3 w-4/5 bg-gray-100 rounded"></div>
          </div>

          <!-- Skeleton Table Box -->
          <div class="w-full border border-gray-200/80 rounded-lg p-3 flex flex-col gap-2.5 bg-gray-50/70">
            <div class="h-3.5 w-1/3 bg-gray-200/90 rounded"></div>
            <div class="grid grid-cols-3 gap-2 mt-1">
              <div class="h-6 bg-gray-200/80 rounded"></div>
              <div class="h-6 bg-gray-200/80 rounded"></div>
              <div class="h-6 bg-gray-200/80 rounded"></div>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <div class="h-6 bg-gray-100 rounded"></div>
              <div class="h-6 bg-gray-100 rounded"></div>
              <div class="h-6 bg-gray-100 rounded"></div>
            </div>
          </div>

          <!-- Skeleton Footer Lines -->
          <div class="flex flex-col gap-2.5 mt-auto">
            <div class="h-3 w-full bg-gray-100 rounded"></div>
            <div class="h-3 w-5/6 bg-gray-100 rounded"></div>
            <div class="h-3 w-2/3 bg-gray-100 rounded"></div>
          </div>
        </div>
      </div>
    </div>

    <template v-else-if="pdfUrl">
      <!-- Download Button Bar above the dark PDF viewer container -->
      <div v-if="allowDownload && totalPages > 0" class="w-full flex justify-end items-center mb-2 px-1">
        <button
          @click="handleDownload"
          :disabled="isDownloading"
          class="flex items-center gap-1.5 px-3.5 py-2 bg-purple-600 hover:bg-purple-700 active:scale-95 text-white font-medium text-xs sm:text-sm rounded-lg shadow-md transition disabled:opacity-50 cursor-pointer"
          title="Unduh PDF"
        >
          <svg v-if="!isDownloading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
          </svg>
          <svg v-else class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span>{{ isDownloading ? 'Mengunduh...' : 'Unduh PDF' }}</span>
        </button>
      </div>

      <!-- Canvas Container for PDF Rendering (Dark Viewer Style matching screenshot) -->
      <div
        ref="containerRef"
        @scroll="handleContainerScroll"
        class="w-full flex flex-col items-center gap-4 sm:gap-6 max-h-[62vh] sm:max-h-[600px] overflow-y-auto overflow-x-auto p-4 sm:p-6 bg-[#2a2a2e] rounded-xl border border-gray-700 shadow-2xl scroll-smooth relative"
        style="-webkit-overflow-scrolling: touch; touch-action: pan-y;"
      >
        <!-- Canvas pages rendered by PDF.js -->
        <div v-if="totalPages > 0" class="flex flex-col items-center gap-4 sm:gap-6 w-full">
          <div
            v-for="pageIndex in totalPages"
            :key="pageIndex"
            :id="'pdf-page-wrapper-' + pageIndex"
            class="flex flex-col items-center w-full relative"
          >
            <canvas
              :id="'pdf-page-canvas-' + pageIndex"
              :ref="el => setCanvasRef(el, pageIndex)"
              class="bg-white shadow-2xl rounded-sm max-w-full touch-pan-y"
              style="touch-action: pan-y;"
            ></canvas>
          </div>
        </div>

        <div v-else-if="renderError" class="p-6 text-center text-sm text-red-400 font-medium">
          Gagal memuat halaman PDF. Silakan coba lagi.
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { createWatermarkedPdf, downloadWatermarkedPdf } from '@/utils/pdfWatermark'

const props = defineProps({
  content: {
    type: String,
    default: () => ''
  },
  allowDownload: {
    type: Boolean,
    default: true
  }
})

const pdfUrl = ref('')
const originalPdfUrl = ref('')
const isLoading = ref(false)
const isDownloading = ref(false)
const renderError = ref(false)
const totalPages = ref(0)
const currentPage = ref(1)
const scale = ref(1.0)
const activeScale = ref(1.0)
const isManualZoom = ref(false)
const containerRef = ref(null)

const pdfFileName = computed(() => {
  if (props.content) {
    const rawName = props.content.split('/').pop() || 'dokumen.pdf'
    return rawName.endsWith('.pdf') ? rawName : `${rawName}.pdf`
  }
  return 'dokumen_gadai_mulia.pdf'
})

async function handleDownload() {
  if (!originalPdfUrl.value || isDownloading.value) return
  isDownloading.value = true
  try {
    await downloadWatermarkedPdf(originalPdfUrl.value, pdfFileName.value)
  } catch (err) {
    console.error('Failed to download watermarked PDF:', err)
  } finally {
    isDownloading.value = false
  }
}

let currentBlobUrl = null
let pdfDocInstance = null
let pdfBytesData = null
const canvasRefs = ref({})

function setCanvasRef(el, index) {
  if (el) {
    canvasRefs.value[index] = el
  }
}

function cleanBlob() {
  if (currentBlobUrl) {
    URL.revokeObjectURL(currentBlobUrl)
    currentBlobUrl = null
  }
}

let resizeObserver = null

onMounted(() => {
  if (typeof ResizeObserver !== 'undefined') {
    resizeObserver = new ResizeObserver(() => {
      if (!isManualZoom.value && pdfDocInstance && totalPages.value > 0) {
        renderPages()
      }
    })
  }
})

watch(() => containerRef.value, (newEl) => {
  if (newEl && resizeObserver) {
    resizeObserver.disconnect()
    resizeObserver.observe(newEl)
  }
})

onUnmounted(() => {
  cleanBlob()
  if (resizeObserver) {
    resizeObserver.disconnect()
    resizeObserver = null
  }
})

// Cleanly resolve document URLs across all storage subfolders
function resolveFileUrl(path) {
  if (!path) return ''

  let clean = String(path).trim()

  if (clean.startsWith('blob:')) {
    return clean
  }

  // Extract pathname if full URL with domain or IP
  if (clean.startsWith('http://') || clean.startsWith('https://')) {
    try {
      const parsed = new URL(clean)
      clean = decodeURIComponent(parsed.pathname)
    } catch (e) {}
  }

  // Remove leading slashes and storage/ prefix
  clean = clean.replace(/^\/+/, '').replace(/^storage\//, '')
  clean = clean.replace(/^aktif\/uploads\//, 'uploads/')

  let finalPath = clean
  if (clean.startsWith('uploads/') || clean.startsWith('aktif/') || clean.startsWith('tidakaktif/')) {
    finalPath = 'storage/' + clean
  } else {
    finalPath = 'storage/aktif/' + clean
  }

  const parts = finalPath.split('/').map(part => encodeURIComponent(part))
  return '/' + parts.join('/')
}

// Load local pdf.min.js & set pdf.worker.min.js path
function loadPdfJsScript() {
  if (window.pdfjsLib) {
    return Promise.resolve(window.pdfjsLib)
  }
  
  const localScriptUrl = window.location.origin + '/assets/js/pdfjs/pdf.min.js'
  const cdnScriptUrl = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js'

  return new Promise((resolve, reject) => {
    const script = document.createElement('script')
    script.src = localScriptUrl
    
    script.onload = () => {
      if (window.pdfjsLib) {
        window.pdfjsLib.GlobalWorkerOptions.workerSrc = window.location.origin + '/assets/js/pdfjs/pdf.worker.min.js'
        resolve(window.pdfjsLib)
      } else {
        reject(new Error('Local PDF.js library failed to load'))
      }
    }

    script.onerror = () => {
      console.warn('Local PDF.js asset failed, trying CDN fallback...')
      const cdnScript = document.createElement('script')
      cdnScript.src = cdnScriptUrl
      cdnScript.onload = () => {
        if (window.pdfjsLib) {
          window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js'
          resolve(window.pdfjsLib)
        } else {
          reject(new Error('CDN PDF.js library failed to load'))
        }
      }
      cdnScript.onerror = (cdnErr) => reject(cdnErr)
      document.head.appendChild(cdnScript)
    }

    document.head.appendChild(script)
  })
}

// Safely get canvas element with retry loop
async function getCanvasElement(pageNum) {
  for (let i = 0; i < 25; i++) {
    const el = canvasRefs.value[pageNum] || document.getElementById(`pdf-page-canvas-${pageNum}`)
    if (el) return el
    await new Promise(r => setTimeout(r, 40))
  }
  return null
}

let isRenderingProcess = false
let pendingRenderRequest = false
const activePageRenderTasks = {}

async function renderPages() {
  if (!pdfDocInstance || totalPages.value <= 0) return

  if (isRenderingProcess) {
    pendingRenderRequest = true
    return
  }

  isRenderingProcess = true
  pendingRenderRequest = false
  await nextTick()

  try {
    for (let pageNum = 1; pageNum <= totalPages.value; pageNum++) {
      const canvas = await getCanvasElement(pageNum)
      if (!canvas) continue

      if (activePageRenderTasks[pageNum]) {
        try {
          activePageRenderTasks[pageNum].cancel()
        } catch (e) {}
        delete activePageRenderTasks[pageNum]
      }

      try {
        const page = await pdfDocInstance.getPage(pageNum)
        let targetScale = scale.value

        if (!isManualZoom.value) {
          const isMobile = window.innerWidth < 640
          const padding = isMobile ? 8 : 32
          let containerWidth = containerRef.value ? containerRef.value.clientWidth - padding : 0

          if (containerWidth <= 100) {
            const estimatedModalWidth = Math.min(window.innerWidth - 16, 1100)
            containerWidth = estimatedModalWidth - padding
          }

          const unscaledViewport = page.getViewport({ scale: 1.0 })

          if (containerWidth > 0 && unscaledViewport.width > 0) {
            const scaleRatio = isMobile ? 0.95 : 0.78
            const autoFitScale = (containerWidth * scaleRatio) / unscaledViewport.width
            targetScale = parseFloat(Math.max(0.4, Math.min(autoFitScale, 1.5)).toFixed(2))
          }
        }

        const outputScale = window.devicePixelRatio || 1
        activeScale.value = targetScale
        const viewport = page.getViewport({ scale: targetScale })
        const context = canvas.getContext('2d')

        canvas.width = Math.floor(viewport.width * outputScale)
        canvas.height = Math.floor(viewport.height * outputScale)
        canvas.style.width = Math.floor(viewport.width) + 'px'
        canvas.style.height = Math.floor(viewport.height) + 'px'

        context.clearRect(0, 0, canvas.width, canvas.height)

        const transform = outputScale !== 1 ? [outputScale, 0, 0, outputScale, 0, 0] : null

        const renderContext = {
          canvasContext: context,
          transform: transform,
          viewport: viewport
        }

        const renderTask = page.render(renderContext)
        activePageRenderTasks[pageNum] = renderTask
        await renderTask.promise
        delete activePageRenderTasks[pageNum]
      } catch (renderErr) {
        delete activePageRenderTasks[pageNum]
        if (renderErr?.name !== 'RenderingCancelledException') {
          console.warn(`Error rendering page ${pageNum}:`, renderErr)
        }
      }
    }
  } finally {
    isRenderingProcess = false
    if (pendingRenderRequest) {
      pendingRenderRequest = false
      renderPages()
    }
  }
}

async function loadPdfWithCanvas(bytesOrUrl) {
  renderError.value = false
  try {
    const pdfjsLib = await loadPdfJsScript()
    let loadingTask

    if (bytesOrUrl instanceof ArrayBuffer || ArrayBuffer.isView(bytesOrUrl)) {
      loadingTask = pdfjsLib.getDocument({ data: bytesOrUrl })
    } else if (typeof bytesOrUrl === 'string') {
      loadingTask = pdfjsLib.getDocument(bytesOrUrl)
    } else {
      loadingTask = pdfjsLib.getDocument(bytesOrUrl)
    }

    pdfDocInstance = await loadingTask.promise
    totalPages.value = pdfDocInstance.numPages
    currentPage.value = 1
    isLoading.value = false
    await nextTick()
    await renderPages()

    // Auto re-render once modal layout finishes expanding so containerWidth is measured at 100% precision!
    setTimeout(() => {
      if (!isManualZoom.value && pdfDocInstance) {
        renderPages()
      }
    }, 150)

    return true
  } catch (err) {
    console.warn('PDF.js canvas loading attempt failed:', err)
    return false
  }
}

function zoomIn() {
  isManualZoom.value = true
  if (scale.value < 3.0) {
    scale.value = parseFloat((scale.value + 0.2).toFixed(1))
    renderPages()
  }
}

function zoomOut() {
  isManualZoom.value = true
  if (scale.value > 0.5) {
    scale.value = parseFloat((scale.value - 0.2).toFixed(1))
    renderPages()
  }
}

function resetZoom() {
  isManualZoom.value = false
  scale.value = 1.0
  renderPages()
}

function scrollToPage(pageIndex) {
  if (pageIndex < 1 || pageIndex > totalPages.value) return
  currentPage.value = pageIndex
  const wrapperEl = containerRef.value?.querySelector(`#pdf-page-wrapper-${pageIndex}`)
  if (wrapperEl) {
    wrapperEl.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

function prevPage() {
  if (currentPage.value > 1) {
    scrollToPage(currentPage.value - 1)
  }
}

function nextPage() {
  if (currentPage.value < totalPages.value) {
    scrollToPage(currentPage.value + 1)
  }
}

function handleContainerScroll() {
  if (!containerRef.value || totalPages.value <= 1) return
  const containerTop = containerRef.value.getBoundingClientRect().top

  for (let pageNum = 1; pageNum <= totalPages.value; pageNum++) {
    const wrapperEl = containerRef.value.querySelector(`#pdf-page-wrapper-${pageNum}`)
    if (wrapperEl) {
      const rect = wrapperEl.getBoundingClientRect()
      if (rect.top <= containerTop + 150 && rect.bottom >= containerTop + 50) {
        currentPage.value = pageNum
        break
      }
    }
  }
}

const watermarkCache = new Map()

watch(() => props.content, async (newContent) => {
  cleanBlob()
  pdfDocInstance = null
  canvasRefs.value = {}
  totalPages.value = 0
  currentPage.value = 1
  renderError.value = false
  isManualZoom.value = false

  if (!newContent) {
    pdfUrl.value = ''
    originalPdfUrl.value = ''
    isLoading.value = false
    return
  }

  let rawPath = String(newContent).trim()
  if (rawPath.startsWith('http://') || rawPath.startsWith('https://')) {
    try {
      rawPath = decodeURIComponent(new URL(rawPath).pathname)
    } catch (e) {}
  }
  rawPath = rawPath.replace(/^\/+/, '').replace(/^storage\//, '')

  const filenameOnly = rawPath.split('/').pop()
  const rawPathDecoded = decodeURIComponent(rawPath)
  const filenameDecoded = decodeURIComponent(filenameOnly)

  const candidates = [
    resolveFileUrl(rawPath),
    '/storage/aktif/' + rawPathDecoded.split('/').map(encodeURIComponent).join('/'),
    '/storage/aktif/FORM%20PP/' + encodeURIComponent(filenameDecoded),
    '/storage/aktif/FORM%20HRD/' + encodeURIComponent(filenameDecoded),
    '/storage/aktif/Juklak/' + encodeURIComponent(filenameDecoded),
    '/storage/aktif/Policy/' + encodeURIComponent(filenameDecoded),
    '/storage/aktif/Memo/' + encodeURIComponent(filenameDecoded),
    '/storage/aktif/SOP/' + encodeURIComponent(filenameDecoded),
    '/storage/aktif/SE/' + encodeURIComponent(filenameDecoded),
    '/storage/aktif/SK/' + encodeURIComponent(filenameDecoded),
    '/storage/uploads/content/' + encodeURIComponent(filenameDecoded),
    '/storage/tidakaktif/' + rawPathDecoded.split('/').map(encodeURIComponent).join('/')
  ].filter((v, i, a) => a.indexOf(v) === i)

  isLoading.value = true

  let success = false
  for (const targetUrl of candidates) {
    originalPdfUrl.value = targetUrl
    try {
      let watermarkResult
      if (watermarkCache.has(targetUrl)) {
        watermarkResult = watermarkCache.get(targetUrl)
      } else {
        watermarkResult = await createWatermarkedPdf(targetUrl)
        watermarkCache.set(targetUrl, watermarkResult)
      }

      currentBlobUrl = watermarkResult.blobUrl
      pdfUrl.value = watermarkResult.blobUrl
      pdfBytesData = watermarkResult.bytes

      if (pdfBytesData) {
        success = await loadPdfWithCanvas(pdfBytesData)
      }
      if (!success && watermarkResult.blobUrl) {
        success = await loadPdfWithCanvas(watermarkResult.blobUrl)
      }
      if (success) break
    } catch (err) {
      console.warn(`Watermark PDF attempt failed for ${targetUrl}:`, err)
      pdfUrl.value = targetUrl
      success = await loadPdfWithCanvas(targetUrl)
      if (success) break
    }
  }

  if (!success) {
    renderError.value = true
  }

  isLoading.value = false
}, { immediate: true })
</script>