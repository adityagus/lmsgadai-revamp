<template>
  <div class="max-w-lg mx-auto mt-10 bg-white p-8 rounded shadow">
    <h1 class="text-2xl font-bold mb-6 text-sidebar">Migrasi Data (Upload Excel)</h1>
    <form @submit.prevent="submitExcel">
      <input type="file" accept=".xlsx,.xls" @change="onFileChange" class="mb-4" required />
      <button type="submit"
        class="px-4 py-2 bg-sidebar text-white rounded font-semibold bg-purple-700 transition hover:bg-purple-800">
        Upload & Migrasi
      </button>
    </form>
    <div v-if="success" class="mt-4 text-green-600 font-semibold">Migrasi berhasil!</div>
    <div v-if="error" class="mt-4 text-red-600 font-semibold">{{ error }}</div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'

const file = ref(null)
const success = ref(false)
const error = ref('')

function onFileChange(e) {
  file.value = e.target.files[0]
}

async function submitExcel() {
  success.value = false
  error.value = ''
  if (!file.value) {
    error.value = 'Pilih file terlebih dahulu.'
    return
  }
  const formData = new FormData()
  formData.append('file', file.value)
  try {
    await axios.post('/api/migrasi-data', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    success.value = true
  } catch (e) {
    error.value = e.response?.data?.message || 'Migrasi gagal!' + e.message
  }
}
</script>
