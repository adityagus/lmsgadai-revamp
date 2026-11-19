<template>
  <div class="w-full max-w-3xl mx-auto py-6 sm:py-10 font-['Poppins']">
    <!-- Loading -->
    <div v-if="loading" class="rounded-[30px] border border-[#CFDBEF] bg-white p-8 sm:p-12 text-center animate-pulse space-y-4">
      <div class="w-20 h-20 rounded-full bg-purple-100 mx-auto"></div>
      <div class="h-6 bg-gray-200 rounded w-2/3 mx-auto"></div>
      <div class="h-4 bg-gray-100 rounded w-1/2 mx-auto"></div>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="rounded-[30px] border border-red-200 bg-white p-8 text-center space-y-4">
      <p class="text-sm text-red-600 font-medium">{{ error }}</p>
      <button
        type="button"
        @click="loadStatus"
        class="rounded-full bg-[#662FFF] hover:bg-[#5422DB] px-6 py-3 text-sm font-semibold text-white cursor-pointer"
      >
        Coba Lagi
      </button>
    </div>

    <!-- Not Complete Yet -->
    <div v-else-if="!status.is_complete" class="rounded-[30px] border border-[#CFDBEF] bg-white p-8 sm:p-12 text-center space-y-5 shadow-xs">
      <div class="w-20 h-20 rounded-full bg-amber-50 text-amber-600 mx-auto flex items-center justify-center">
        <i class="pi pi-info-circle text-4xl"></i>
      </div>
      <div>
        <h1 class="text-xl sm:text-2xl font-bold text-[#060A23]">Pembelajaran Belum Selesai</h1>
        <p class="text-sm text-[#838C9D] mt-2">
          Anda baru menyelesaikan <strong>{{ status.completed_contents }}</strong> dari <strong>{{ status.total_contents }}</strong> materi pada kelas <strong>{{ status.course?.name }}</strong>.
        </p>
      </div>
      <div class="flex flex-wrap justify-center gap-3 pt-2">
        <RouterLink
          :to="`/student/kelas/${courseId}`"
          class="rounded-full bg-[#662FFF] hover:bg-[#5422DB] px-6 py-3 text-sm font-semibold text-white transition-colors"
        >
          Kembali ke Ruang Belajar
        </RouterLink>
        <RouterLink
          to="/student/kelas"
          class="rounded-full border border-[#060A23] px-6 py-3 text-sm font-semibold text-[#060A23] hover:bg-gray-50 transition-colors"
        >
          Daftar Kelas
        </RouterLink>
      </div>
    </div>

    <!-- Celebratory Completion Card -->
    <div v-else class="rounded-[30px] border border-[#CFDBEF] bg-white p-6 sm:p-12 text-center space-y-6 shadow-sm">
      <div class="w-24 h-24 rounded-full bg-purple-100 text-[#662FFF] mx-auto flex items-center justify-center">
        <img src="/assets/images/icons/cup-purple.svg" class="w-12 h-12" alt="Selesai" />
      </div>

      <div class="space-y-2">
        <span class="inline-block rounded-full bg-emerald-50 border border-emerald-200 px-4 py-1 text-xs font-semibold text-emerald-700">
          100% Materi Selesai
        </span>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-[#060A23]">
          Selamat! Anda Berhasil Menyelesaikan Pembelajaran
        </h1>
        <p class="text-sm sm:text-base text-[#838C9D]">
          Kelas <strong class="text-[#060A23]">{{ status.course?.name }}</strong> telah selesai Anda pelajari
          <span v-if="status.completed_at"> pada {{ formatDate(status.completed_at) }}</span>.
        </p>
      </div>

      <div class="rounded-2xl bg-[#F8FAFB] border border-[#E6ECF5] p-5 text-left max-w-lg mx-auto space-y-2">
        <div class="flex justify-between text-xs sm:text-sm">
          <span class="text-[#838C9D]">Nama Peserta</span>
          <span class="font-semibold text-[#060A23]">{{ status.certificate?.recipient_name || status.default_recipient_name }}</span>
        </div>
        <div class="flex justify-between text-xs sm:text-sm">
          <span class="text-[#838C9D]">Materi Diselesaikan</span>
          <span class="font-semibold text-[#060A23]">{{ status.completed_contents }} / {{ status.total_contents }} Materi</span>
        </div>
        <div v-if="status.certificate?.certificate_number" class="flex justify-between text-xs sm:text-sm">
          <span class="text-[#838C9D]">Nomor Sertifikat</span>
          <span class="font-semibold text-[#662FFF]">{{ status.certificate.certificate_number }}</span>
        </div>
      </div>

      <p v-if="downloadError" role="alert" class="text-xs text-red-600">{{ downloadError }}</p>

      <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
        <button
          type="button"
          :disabled="downloading"
          @click="handleDownloadCertificate"
          class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-full border border-[#662FFF] bg-purple-50 hover:bg-purple-100 text-[#662FFF] px-7 py-3.5 text-sm font-semibold transition-colors cursor-pointer disabled:opacity-50"
        >
          <i class="pi pi-download"></i>
          <span>{{ downloading ? 'Menyiapkan Sertifikat...' : 'Unduh Sertifikat' }}</span>
        </button>

        <RouterLink
          to="/student/kelas"
          class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-full bg-[#662FFF] hover:bg-[#5422DB] text-white px-8 py-3.5 text-sm font-semibold transition-colors"
        >
          <span>Lanjutkan</span>
          <i class="pi pi-arrow-right text-xs"></i>
        </RouterLink>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { RouterLink, useRoute } from '@/navigation';
import { apiInstance as axios } from '@/utils/axios';
import { generateCertificatePdf } from '@/utils/certificatePdf';

const route = useRoute();
const courseId = computed(() => route.params.id);

const loading = ref(true);
const error = ref('');
const status = ref({});
const downloading = ref(false);
const downloadError = ref('');

async function loadStatus() {
  loading.value = true;
  error.value = '';
  try {
    const { data } = await axios.get(`/api/courses/${courseId.value}/certificate-status`);
    status.value = data;
  } catch (e) {
    error.value = e?.response?.data?.message || 'Gagal memuat status penyelesaian kelas.';
  } finally {
    loading.value = false;
  }
}

async function handleDownloadCertificate() {
  if (downloading.value) return;
  downloading.value = true;
  downloadError.value = '';
  try {
    const { data } = await axios.post(`/api/courses/${courseId.value}/certificate/issue`);
    status.value.certificate = data.certificate;
    await generateCertificatePdf({
      courseName: data.course?.name || status.value.course?.name,
      recipientName: data.certificate.recipient_name,
      certificateNumber: data.certificate.certificate_number,
      issuedAt: data.certificate.issued_at,
      template: data.template,
    });
  } catch (e) {
    downloadError.value = e?.response?.data?.message || e?.message || 'Gagal mengunduh sertifikat.';
  } finally {
    downloading.value = false;
  }
}

function formatDate(dateStr) {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });
}

onMounted(loadStatus);
</script>
