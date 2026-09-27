<!-- <template>
        <div class="flex flex-col gap-5 max-w-[800px] pb-[160px]">
        <h1 class="font-bold text-[32px] leading-[48px]">
          Pengenalan UI UX
        </h1>
        <article id="Content-wrapper">
          <p>
            Dalam artikel ini, kita akan membahas tentang Laravel, mengapa
            framework ini dibuat, alasan mengapa belajar Laravel, keunggulan
            utama Laravel, persiapan yang diperlukan, langkah-langkah belajar
            Laravel, dan bagaimana menemukan mentor serta proyek sampingan untuk
            meningkatkan keterampilan Anda.
          </p>
          <h3>Dasar Design Principle</h3>
          <p>
            Laravel adalah framework open-source untuk PHP yang dibuat oleh
            Taylor Otwell. Framework ini dirancang untuk pengembang web yang
            ingin bekerja dengan cara yang elegan dan menyenangkan.
          </p>
        </article>
      </div>
      <div class="fixed bottom-0 w-[calc(100%-400px)] h-[151px] flex items-end justify-end pb-5 bg-[linear-gradient(0deg,#FFFFFF_49.67%,rgba(255,255,255,0)_84.11%)]">
        <button
          type="button"
          class="w-fit rounded-full p-[14px_20px] font-semibold text-[#FFFFFF] bg-[#662FFF] text-nowrap"
        >
          Mark as Completed
        </button>
      </div>
</template> -->

<template>
  <div class="flex flex-col gap-5 max-w-[800px] pb-[160px]">
    <h1 class="font-bold text-[32px] leading-[48px]">
      {{ content.title || 'Content Title' }}
    </h1>

    <!-- Download Attachment if available -->
    <div class="flex justify-end" v-if="content.attachment">
      <a :href="content.attachment" target="_blank"
        class="btn bg-red-500 py-2 px-5 rounded-lg text-white hover:bg-red-400 transition-colors">
        Download Lampiran
      </a>
    </div>

    <!-- Content Body -->
    <article id="Content-wrapper" class="prose max-w-none">
      <div v-if="content.body" v-html="content.body"></div>
      <div v-else>
        <p class="text-gray-500">No content available</p>
      </div>
    </article>
  </div>
</template>

<script setup>
import { defineProps } from 'vue';

const props = defineProps({
  content: {
    type: Object,
    default: () => ({})
  }
});
</script>
      </div>
    </div>
    <div class="form-section">
      <label>Deskripsi Berita Acara</label>
      <textarea></textarea>
    </div>

    <div class="section-title">KOLOM TANDA TANGAN</div>
    <table class="table-container">
      <tr>
        <td class="label">Penyusun Berita Acara</td>
        <td colspan="2">Mengetahui (Atasan Langsung)</td>
      </tr>
      <tr>
        <td>Nama:</td>
        <td>Nama:</td>
      </tr>
      <tr>
        <td>Tgl :</td>
        <td>Tgl :</td>
      </tr>
    </table>

    <div class="section-title">PERNYATAAN KEBENARAN DATA</div>
    <p>Dengan ditandatanganinya Berita Acara ini, maka Penyusun Berita Acara bertanggung jawab atas isi dan kebenaran laporan yang telah disusun sesuai dengan hak dan kewajibannya</p>
    <p style="text-align: right;">M.2021.

`
</script>