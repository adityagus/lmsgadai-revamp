<template>
        <div
        class="relative group cursor-pointer rounded-2xl bg-white border border-gray-200 shadow-md px-0 py-0 overflow-hidden transition-all duration-200 hover:shadow-xl hover:scale-[1.025] hover:border-[#662FFF]"
        @click="goToDetail(item.id)"
      >
        <div class="flex flex-col md:flex-row items-stretch">
          <div class="w-full md:w-48 h-40 md:h-auto flex-shrink-0 bg-gray-100 flex items-center justify-center overflow-hidden">
            <AnimatedPlaceholder width="100%" height="192px" alt="thumbnail" />
          </div>
          <div class="flex-1 flex flex-col justify-between p-5">
            <div>
              <div class="flex items-center gap-2 mb-2">
                <AnimatedPlaceholder width="100px" height="15px" borderRadius="9999px" class='flex items-center gap-2 mb-2'/>
                <AnimatedPlaceholder class='ml-auto' width="40px" height="15px" borderRadius="12px" />
              </div>
              <AnimatedPlaceholder width="570px" height="25px" class='mb-1' borderRadius="7px"/>
              <AnimatedPlaceholder width="700px" height="50px" class='mb-2' borderRadius="7px"/>
            </div>
            <div class="flex items-center justify-between mt-4">
              <AnimatedPlaceholder width="150px" height="12px" class='text-xs text-gray-400' borderRadius="7px"/>
            </div>
          </div>
        </div>
        <div class="absolute inset-0 pointer-events-none rounded-2xl border-2 border-transparent group-hover:border-[#662FFF] transition-all duration-200"></div>
      </div>
</template>

<script setup>
import AnimatedPlaceholder from '@/components/AnimatedPlaceholder.vue';

</script>

<style scoped>
#title{
  display: flex;
  flex-direction: column;
  gap: 8px;
  width: 100%;
}
</style>