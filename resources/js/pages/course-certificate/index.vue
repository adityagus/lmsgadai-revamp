<template>
  <div class="w-full pb-10 space-y-6 font-['Poppins']">
    <!-- Header -->
    <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2 text-xs text-[#838C9D] mb-1">
          <RouterLink :to="`/kelas/${courseId}`" class="hover:text-[#662FFF]">← Kembali ke Detail Kelas</RouterLink>
        </div>
        <h1 class="text-2xl font-extrabold text-[#060A23]">Sertifikat Kelas</h1>
        <p class="text-sm text-[#838C9D]">{{ course?.name || 'Memuat kelas...' }}</p>
      </div>
    </header>

    <!-- Guide: Cara Upload Template Sertifikat -->
    <section class="rounded-[24px] border border-purple-200 bg-purple-50/60 p-5 sm:p-6 space-y-3">
      <div class="flex items-center gap-2 text-[#662FFF] font-bold text-base">
        <i class="pi pi-info-circle"></i>
        <h2>Cara Upload Template Sertifikat</h2>
      </div>
      <ol class="list-decimal list-inside text-xs sm:text-sm text-[#060A23] space-y-1.5 leading-relaxed">
        <li>Siapkan desain sertifikat ukuran <strong>A4 Landscape</strong> dengan area kosong untuk <strong>Nama Peserta</strong>, <strong>Nomor Sertifikat</strong>, dan <strong>Tanggal</strong>.</li>
        <li>Simpan template dalam format <strong>PDF</strong>, <strong>PNG</strong>, atau <strong>JPG</strong> (maksimal <strong>5MB</strong>).</li>
        <li>Pilih cakupan template (khusus kelas ini atau default untuk semua kelas), lalu unggah file template di bawah.</li>
        <li>Pilih jenis penanda (Nama / Nomor / Tanggal), lalu <strong>klik pada pratinjau template</strong> untuk menempatkan posisinya secara akurat.</li>
        <li>Klik <strong>Simpan Template</strong>. Anda dapat langsung menguji hasilnya dengan fitur <strong>Generate Sertifikat</strong> di samping.</li>
      </ol>
    </section>

    <div v-if="loading" class="rounded-[24px] bg-white border border-gray-200 p-8 text-center text-sm text-gray-500">
      Memuat pengaturan sertifikat...
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Left: Template Upload & Visual Positioner (2 cols) -->
      <section class="lg:col-span-2 rounded-[24px] bg-white border border-[#CFDBEF] p-5 sm:p-6 space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <h2 class="font-bold text-lg text-[#060A23]">Pengaturan Template Sertifikat</h2>
          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="switchScope('course')"
              :class="['rounded-full px-4 py-1.5 text-xs font-semibold cursor-pointer transition-colors', form.scope === 'course' ? 'bg-[#662FFF] text-white' : 'bg-gray-100 text-gray-700']"
            >
              Khusus Kelas Ini
            </button>
            <button
              type="button"
              @click="switchScope('default')"
              :class="['rounded-full px-4 py-1.5 text-xs font-semibold cursor-pointer transition-colors', form.scope === 'default' ? 'bg-[#662FFF] text-white' : 'bg-gray-100 text-gray-700']"
            >
              Template Default
            </button>
          </div>
        </div>

        <form @submit.prevent="saveTemplate" class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-[#060A23] mb-1.5">Upload File Template (PDF, PNG, JPG · Maks 5MB)</label>
              <input
                type="file"
                accept=".pdf,.png,.jpg,.jpeg"
                @change="onTemplateFileChange"
                class="w-full rounded-xl border border-[#CFDBEF] px-3 py-2 text-xs bg-white cursor-pointer"
              />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-semibold text-[#060A23] mb-1.5">Ukuran Font Nama</label>
                <input v-model.number="form.font_size" type="number" min="10" max="120" class="w-full rounded-xl border border-[#CFDBEF] px-3 py-2 text-xs" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-[#060A23] mb-1.5">Warna Teks</label>
                <input v-model="form.font_color" type="color" class="w-full h-9 rounded-xl border border-[#CFDBEF] p-1 cursor-pointer" />
              </div>
            </div>
          </div>

          <!-- Active marker selector -->
          <div class="flex flex-wrap items-center gap-3 pt-1">
            <span class="text-xs font-semibold text-[#060A23]">Klik Pratinjau untuk memindahkan:</span>
            <button
              type="button"
              @click="activeMarker = 'name'"
              :class="['rounded-full px-3.5 py-1.5 text-xs font-semibold cursor-pointer', activeMarker === 'name' ? 'bg-[#662FFF] text-white' : 'bg-purple-50 text-[#662FFF]']"
            >
              Nama Peserta ({{ form.name_x }}%, {{ form.name_y }}%)
            </button>
            <button
              type="button"
              @click="activeMarker = 'number'"
              :class="['rounded-full px-3.5 py-1.5 text-xs font-semibold cursor-pointer', activeMarker === 'number' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700']"
            >
              Nomor Sertifikat ({{ form.number_x }}%, {{ form.number_y }}%)
            </button>
            <button
              type="button"
              @click="activeMarker = 'date'"
              :class="['rounded-full px-3.5 py-1.5 text-xs font-semibold cursor-pointer', activeMarker === 'date' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-700']"
            >
              Tanggal ({{ form.date_x }}%, {{ form.date_y }}%)
            </button>
          </div>

          <div class="flex flex-wrap gap-4 text-xs">
            <label class="inline-flex items-center gap-2 cursor-pointer">
              <input v-model="form.show_number" type="checkbox" class="rounded accent-[#662FFF]" />
              <span>Tampilkan Nomor Sertifikat</span>
            </label>
            <label class="inline-flex items-center gap-2 cursor-pointer">
              <input v-model="form.show_date" type="checkbox" class="rounded accent-[#662FFF]" />
              <span>Tampilkan Tanggal Terbit</span>
            </label>
          </div>

          <!-- Interactive Preview Canvas -->
          <div
            ref="previewBox"
            @click="onPreviewClick"
            class="relative w-full aspect-[1.414/1] rounded-2xl border-2 border-dashed border-[#CFDBEF] bg-[#F8FAFB] overflow-hidden cursor-crosshair select-none flex items-center justify-center"
          >
            <canvas v-if="previewType === 'pdf'" ref="pdfCanvas" class="w-full h-full object-contain pointer-events-none"></canvas>
            <img v-else-if="previewUrl && previewType !== 'pdf'" :src="previewUrl" class="w-full h-full object-contain pointer-events-none" alt="Preview Template" />
            <div v-else class="text-center p-6 text-xs text-[#838C9D]">
              Belum ada template diunggah untuk cakupan ini.<br />
              Unggah file PDF / PNG / JPG atau gunakan desain sertifikat bawaan sistem.
            </div>

            <!-- Markers -->
            <div
              class="absolute -translate-x-1/2 -translate-y-1/2 pointer-events-none px-2 py-0.5 rounded border border-purple-400 bg-white/80 font-bold whitespace-nowrap shadow-xs"
              :style="{ left: form.name_x + '%', top: form.name_y + '%', color: form.font_color, fontSize: Math.max(12, Math.round(form.font_size * 0.45)) + 'px' }"
            >
              {{ sampleName || 'Nama Peserta' }}
            </div>
            <div
              v-if="form.show_number"
              class="absolute -translate-x-1/2 -translate-y-1/2 pointer-events-none px-2 py-0.5 rounded border border-emerald-400 bg-white/80 text-[11px] font-medium whitespace-nowrap"
              :style="{ left: form.number_x + '%', top: form.number_y + '%', color: form.font_color }"
            >
              No: CERT/{{ courseId }}/2026/0001
            </div>
            <div
              v-if="form.show_date"
              class="absolute -translate-x-1/2 -translate-y-1/2 pointer-events-none px-2 py-0.5 rounded border border-amber-400 bg-white/80 text-[11px] font-medium whitespace-nowrap"
              :style="{ left: form.date_x + '%', top: form.date_y + '%', color: form.font_color }"
            >
              18 November 2026
            </div>
          </div>

          <p v-if="saveError" class="text-xs text-red-600">{{ saveError }}</p>
          <p v-if="saveMessage" class="text-xs text-emerald-700 font-semibold">{{ saveMessage }}</p>

          <div class="flex justify-end">
            <button
              type="submit"
              :disabled="saving"
              class="rounded-full bg-[#662FFF] hover:bg-[#5422DB] text-white font-semibold text-sm px-7 py-3 cursor-pointer disabled:opacity-50"
            >
              {{ saving ? 'Menyimpan...' : 'Simpan Template Sertifikat' }}
            </button>
          </div>
        </form>
      </section>

      <!-- Right: Generate Sertifikat (Custom Name) & Issued List -->
      <div class="space-y-6">
        <section class="rounded-[24px] bg-white border border-[#CFDBEF] p-5 sm:p-6 space-y-4">
          <h2 class="font-bold text-lg text-[#060A23]">Generate Sertifikat</h2>
          <p class="text-xs text-[#838C9D]">
            Masukkan nama peserta untuk langsung membuat dan mengunduh sertifikat PDF menggunakan template aktif.
          </p>
          <div>
            <label for="gen-name" class="block text-xs font-semibold text-[#060A23] mb-1.5">Nama pada Sertifikat</label>
            <input
              id="gen-name"
              v-model="sampleName"
              type="text"
              placeholder="Contoh: Budi Santoso"
              class="w-full rounded-full border border-[#CFDBEF] px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-[#662FFF]"
            />
          </div>
          <p v-if="genError" class="text-xs text-red-600">{{ genError }}</p>
          <button
            type="button"
            :disabled="generating || !sampleName.trim()"
            @click="handleGenerateSample"
            class="w-full inline-flex items-center justify-center gap-2 rounded-full bg-[#662FFF] hover:bg-[#5422DB] text-white font-semibold text-sm px-5 py-3 cursor-pointer disabled:opacity-50"
          >
            <i class="pi pi-file-pdf"></i>
            <span>{{ generating ? 'Membuat PDF...' : 'Generate & Unduh Sertifikat' }}</span>
          </button>
        </section>

        <section class="rounded-[24px] bg-white border border-[#CFDBEF] p-5 sm:p-6 space-y-3">
          <h2 class="font-bold text-base text-[#060A23]">Sertifikat Terbit ({{ issuedList.length }})</h2>
          <div v-if="issuedList.length === 0" class="text-xs text-[#838C9D] py-4 text-center">
            Belum ada peserta yang mengunduh sertifikat kelas ini.
          </div>
          <div v-else class="space-y-2.5 max-h-[340px] overflow-y-auto pr-1">
            <div v-for="cert in issuedList" :key="cert.id" class="rounded-xl bg-[#F8FAFB] border border-gray-100 p-3 text-xs space-y-1">
              <p class="font-bold text-[#060A23]">{{ cert.recipient_name }}</p>
              <p class="text-[#662FFF] font-medium">{{ cert.certificate_number }}</p>
              <p class="text-[#838C9D]">Diunduh {{ cert.download_count }}x</p>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { RouterLink, useRoute } from '@/navigation';
import { apiInstance as axios } from '@/utils/axios';
import { generateCertificatePdf } from '@/utils/certificatePdf';
import * as pdfjsLib from 'pdfjs-dist';
import pdfjsWorker from 'pdfjs-dist/build/pdf.worker.min.js?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfjsWorker;

const route = useRoute();
const courseId = computed(() => route.params.id);

const loading = ref(true);
const course = ref(null);
const courseTemplate = ref(null);
const defaultTemplate = ref(null);
const issuedList = ref([]);

const activeMarker = ref('name');
const previewBox = ref(null);
const pdfCanvas = ref(null);
const selectedFile = ref(null);
const previewUrl = ref('');
const previewType = ref('');

const saving = ref(false);
const saveError = ref('');
const saveMessage = ref('');

const sampleName = ref('');
const generating = ref(false);
const genError = ref('');

const form = ref({
  scope: 'course',
  name_x: 50,
  name_y: 48,
  font_size: 36,
  font_color: '#1F2937',
  show_number: true,
  number_x: 50,
  number_y: 80,
  show_date: true,
  date_x: 50,
  date_y: 86,
});

function applyTemplateToForm(tpl, scope) {
  form.value.scope = scope;
  selectedFile.value = null;
  if (tpl) {
    form.value.name_x = Number(tpl.name_x ?? 50);
    form.value.name_y = Number(tpl.name_y ?? 48);
    form.value.font_size = Number(tpl.font_size ?? 36);
    form.value.font_color = tpl.font_color || '#1F2937';
    form.value.show_number = tpl.show_number !== false;
    form.value.number_x = Number(tpl.number_x ?? 50);
    form.value.number_y = Number(tpl.number_y ?? 80);
    form.value.show_date = tpl.show_date !== false;
    form.value.date_x = Number(tpl.date_x ?? 50);
    form.value.date_y = Number(tpl.date_y ?? 86);
    previewUrl.value = tpl.file_url;
    previewType.value = tpl.file_type;
    if (tpl.file_type === 'pdf') renderPdfPreview(tpl.file_url);
  } else {
    previewUrl.value = '';
    previewType.value = '';
  }
}

function switchScope(scope) {
  saveMessage.value = '';
  saveError.value = '';
  applyTemplateToForm(scope === 'default' ? defaultTemplate.value : courseTemplate.value, scope);
}

async function renderPdfPreview(source) {
  await nextTick();
  const canvas = pdfCanvas.value;
  if (!canvas || !source) return;
  try {
    const res = await fetch(source, { credentials: 'include' });
    const bytes = new Uint8Array(await res.arrayBuffer());
    const pdf = await pdfjsLib.getDocument({ data: bytes }).promise;
    const page = await pdf.getPage(1);
    const viewport = page.getViewport({ scale: 1.5 });
    canvas.width = viewport.width;
    canvas.height = viewport.height;
    await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
  } catch (e) {
    console.error('Gagal merender pratinjau PDF:', e);
  }
}

function onTemplateFileChange(e) {
  const file = e.target.files?.[0];
  if (!file) return;
  if (file.size > 5 * 1024 * 1024) {
    saveError.value = 'Ukuran template maksimal 5MB.';
    e.target.value = '';
    return;
  }
  saveError.value = '';
  selectedFile.value = file;
  const ext = (file.name.split('.').pop() || '').toLowerCase();
  const url = URL.createObjectURL(file);
  previewUrl.value = url;
  previewType.value = ext === 'pdf' ? 'pdf' : 'image';
  if (ext === 'pdf') renderPdfPreview(url);
}

function onPreviewClick(e) {
  if (!previewBox.value) return;
  const rect = previewBox.value.getBoundingClientRect();
  const x = Math.round(((e.clientX - rect.left) / rect.width) * 1000) / 10;
  const y = Math.round(((e.clientY - rect.top) / rect.height) * 1000) / 10;
  const clampedX = Math.max(0, Math.min(100, x));
  const clampedY = Math.max(0, Math.min(100, y));
  if (activeMarker.value === 'name') {
    form.value.name_x = clampedX;
    form.value.name_y = clampedY;
  } else if (activeMarker.value === 'number') {
    form.value.number_x = clampedX;
    form.value.number_y = clampedY;
  } else {
    form.value.date_x = clampedX;
    form.value.date_y = clampedY;
  }
}

async function loadData() {
  loading.value = true;
  try {
    const { data } = await axios.get(`/api/courses/${courseId.value}/certificate-template`);
    course.value = data.course;
    courseTemplate.value = data.course_template;
    defaultTemplate.value = data.default_template;
    issuedList.value = data.issued_certificates || [];
    applyTemplateToForm(data.course_template || data.default_template, data.course_template ? 'course' : 'default');
  } finally {
    loading.value = false;
  }
}

async function saveTemplate() {
  saving.value = true;
  saveError.value = '';
  saveMessage.value = '';
  try {
    const fd = new FormData();
    fd.append('scope', form.value.scope);
    if (selectedFile.value) fd.append('file', selectedFile.value);
    fd.append('name_x', String(form.value.name_x));
    fd.append('name_y', String(form.value.name_y));
    fd.append('font_size', String(form.value.font_size));
    fd.append('font_color', form.value.font_color);
    fd.append('show_number', form.value.show_number ? '1' : '0');
    fd.append('number_x', String(form.value.number_x));
    fd.append('number_y', String(form.value.number_y));
    fd.append('show_date', form.value.show_date ? '1' : '0');
    fd.append('date_x', String(form.value.date_x));
    fd.append('date_y', String(form.value.date_y));

    const { data } = await axios.post(`/api/courses/${courseId.value}/certificate-template`, fd);
    saveMessage.value = data.message || 'Template sertifikat berhasil disimpan.';
    if (form.value.scope === 'default') defaultTemplate.value = data.template;
    else courseTemplate.value = data.template;
    applyTemplateToForm(data.template, form.value.scope);
  } catch (e) {
    saveError.value = e?.response?.data?.message || 'Gagal menyimpan template sertifikat.';
  } finally {
    saving.value = false;
  }
}

async function handleGenerateSample() {
  if (!sampleName.value.trim() || generating.value) return;
  generating.value = true;
  genError.value = '';
  try {
    const { data } = await axios.post(`/api/courses/${courseId.value}/certificate/preview-issue`, {
      recipient_name: sampleName.value.trim(),
    });
    await generateCertificatePdf({
      courseName: data.course?.name || course.value?.name,
      recipientName: data.certificate.recipient_name,
      certificateNumber: data.certificate.certificate_number,
      issuedAt: data.certificate.issued_at,
      template: data.template,
    });
  } catch (e) {
    genError.value = e?.response?.data?.message || e?.message || 'Gagal membuat sertifikat.';
  } finally {
    generating.value = false;
  }
}

onMounted(loadData);
</script>
