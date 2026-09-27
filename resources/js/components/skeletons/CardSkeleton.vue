<template>
  <div>
    <div class="card flex items-center gap-5">
      <AnimatedPlaceholder height='110px' width='140px' borderRadius='10px' class='flex shrink-0 rounded-[20px]'/>
  
      <div class="w-full">
        <AnimatedPlaceholder height='36px' width='200px' borderRadius='10px'/>
        <div class="flex items-center gap-5">
          <!-- <div class="flex items-center gap-[6px] mt-[6px]">
            <AnimatedPlaceholder height='16px' width='150px' borderRadius='5px'/>
          </div> -->
          <div class="flex items-center gap-[6px] mt-[6px]">
            <AnimatedPlaceholder height='16px' width='150px' borderRadius='5px' />
          </div>
        </div>
      </div>
      <div class="flex justify-end items-center gap-3">
          <AnimatedPlaceholder height='40px' width='120px' borderRadius='20px' class='font-bold text-xl leading-[30px] line-clamp-1'/>
           <AnimatedPlaceholder height='40px' width='100px' borderRadius='20px' class='font-bold text-xl leading-[30px] line-clamp-1'/>
        
        <!-- <RouterLink
          class="w-fit rounded-full border border-[#060A23] p-[14px_20px] font-semibold text-nowrap"
        >
          Manage
        </RouterLink> -->
      </div>
    </div>
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