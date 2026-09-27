<template>
  <div class="card flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-5 bg-white p-4 rounded-2xl border border-gray-100 shadow-xs">
    <div class="relative flex shrink-0 w-full sm:w-[140px] h-[140px] sm:h-[110px]">
      <p
        class="absolute -top-[10px] -left-[10px] flex shrink-0 w-[30px] h-[30px] rounded-full items-center justify-center text-center bg-[#662FFF] text-white shadow-md z-10">
        <span class="font-bold text-sm leading-[21px]">{{ props.index }}</span>
      </p>
      <div class="rounded-[20px] bg-[#D9D9D9] overflow-hidden w-full h-full">
        <div v-if="props.type === 'quiz'" class="w-full h-full flex items-center justify-center bg-purple-50"><img src="/assets/images/icons/quiz-purple.svg" class="w-14 h-14" alt="Quiz"></div>
        <img v-else :src="`/assets/images/thumbnails/cover-${props.type}.png`" class="w-full h-full object-cover"
          alt="thumbnail" />
      </div>
    </div>
    <div class="w-full flex-1">
      <h3 class="font-bold text-base sm:text-xl leading-snug sm:leading-[30px] line-clamp-2 sm:line-clamp-1">
        {{ props.title }}
      </h3>
      <div class="flex items-center gap-5 mt-1 sm:mt-0">
        <div class="flex items-center gap-[6px] mt-[6px]">
          <img :src="props.type === 'quiz' ? '/assets/images/icons/quiz-purple.svg' : '/assets/images/icons/note-favorite-purple.svg'" class="w-4 h-4 sm:w-5 sm:h-5" alt="" />
          <p class="text-[#838C9D] text-xs sm:text-sm font-medium capitalize">{{ contentKind === 'google_form' ? 'Google Form' : contentKind === 'quiz' ? 'Quiz' : contentKind }}</p>
        </div>
      </div>
    </div>
    <div class="flex justify-between sm:justify-end items-center gap-2 sm:gap-3 w-full sm:w-auto pt-2 sm:pt-0 border-t sm:border-t-0 border-gray-100">
      <RouterLink v-if="props.quizId" :to="`/quiz/manage/${props.quizId}/results`"
        class="rounded-full border border-purple-500 px-4 py-2.5 text-sm font-semibold text-purple-700">
        Hasil Quiz
      </RouterLink>
      <RouterLink :to="`/course/update-contents/${props.courseId}/${props.id}`"
        class="flex-1 sm:flex-none w-fit rounded-full border border-[#060A23] px-4 py-2.5 sm:p-[14px_20px] font-semibold text-nowrap text-center text-xs sm:text-sm hover:bg-gray-50 transition-colors">
        Ubah Materi
      </RouterLink>
      <button type="button" @click="$emit('delete-content', props.id)"
        class="flex-1 sm:flex-none w-fit rounded-full px-4 py-2.5 sm:p-[14px_20px] bg-[#FF435A] font-semibold text-white text-nowrap text-center text-xs sm:text-sm hover:bg-red-600 transition-colors cursor-pointer">
        Hapus
      </button>
    </div>
  </div>
</template>

<script setup>
import { RouterLink } from '@/navigation';
import { computed } from 'vue';
import { courseContentType } from '@/utils/courseContentType.mjs';

const props = defineProps({
  body: { type: String, default: '' },
  quizId: { type: [Number, String], default: null },
  id: {
    type: Number,
    default: 1
  },
  index: {
    type: Number,
    default: 1
  },
  type: {
    type: String,
    default: "Video"
  },
  title: {
    type: String,
    default: "Install VSCode di Windows"
  },
  courseId: {
    type: Number,
    default: 2
  }
});

const emit = defineEmits(['delete-content']);
const contentKind = computed(() => courseContentType({type:props.type,quiz_id:props.quizId,content:props.body}));
</script>
