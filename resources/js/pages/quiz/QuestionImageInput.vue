<script setup>
import { ref, watch } from 'vue';
import { uploadQuestionImage } from '@/services/mutations/quizMutation.ts';

const props = defineProps({ modelValue: String, imageUrl: String });
const emit = defineEmits(['update:modelValue', 'update:imageUrl', 'busy']);
const uploading = ref(false), error = ref('');
const previewFailed = ref(false), previewRetry = ref(0);
watch(() => props.imageUrl, () => { previewFailed.value = false; previewRetry.value = 0; });
async function selectImage(event) {
  const file = event.target.files?.[0];
  event.target.value = '';
  if (!file || uploading.value) return;
  error.value = '';
  if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
    error.value = 'Pilih gambar JPG, PNG, atau WebP maksimal 5 MB.';
    return;
  }
  uploading.value = true;
  emit('busy', true);
  try {
    const result = await uploadQuestionImage(file);
    emit('update:modelValue', result.image_path);
    emit('update:imageUrl', result.image_url);
  } catch (failure) {
    error.value = failure.response?.data?.errors?.image?.[0] || 'Gambar gagal diunggah. Silakan coba lagi.';
  } finally {
    uploading.value = false;
    emit('busy', false);
  }
}
function removeImage() {
  emit('update:modelValue', null);
  emit('update:imageUrl', null);
  error.value = '';
}
</script>

<template>
  <div class="stack">
    <label class="field">Gambar soal (opsional)
      <input type="file" accept="image/jpeg,image/png,image/webp" :disabled="uploading" @change="selectImage">
      <small class="muted">JPG, PNG, atau WebP. Maksimal 5 MB dan 8000 × 8000 piksel.</small>
    </label>
    <p v-if="uploading" role="status" class="muted">Mengunggah gambar...</p>
    <p v-if="error" role="alert" class="error">{{ error }}</p>
    <img v-if="imageUrl && !previewFailed" :key="previewRetry" :src="imageUrl" alt="Pratinjau gambar soal" class="question-image" @error="previewFailed = true">
    <p v-if="imageUrl && previewFailed" role="alert" class="error">
      Gambar tersimpan, tetapi pratinjau gagal dimuat.
      <button type="button" @click="previewRetry++; previewFailed = false">Coba lagi</button>
    </p>
    <button v-if="modelValue" type="button" :disabled="uploading" @click="removeImage">Hapus gambar</button>
  </div>
</template>
