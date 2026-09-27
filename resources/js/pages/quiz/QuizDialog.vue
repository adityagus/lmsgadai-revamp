<template>
  <dialog ref="dialog" class="quiz-author-dialog" :aria-labelledby="titleId" @cancel.prevent="close">
    <header class="row quiz-section-heading"><h2 :id="titleId">{{ title }}</h2><button type="button" :disabled="busy" @click="close">Tutup</button></header>
    <slot />
  </dialog>
</template>
<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
const props = defineProps({ title: String, busy: Boolean });
const emit = defineEmits(['close']);
const dialog = ref(null);
const titleId = 'quiz-author-dialog-title';
const close = () => { if (!props.busy) emit('close'); };
onMounted(() => dialog.value.showModal());
onBeforeUnmount(() => dialog.value?.close());
</script>
