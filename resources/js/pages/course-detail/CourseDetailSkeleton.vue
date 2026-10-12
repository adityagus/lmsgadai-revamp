<template>
  <div role="status" aria-busy="true" aria-label="Memuat detail kelas">
    <span class="sr-only">Memuat detail kelas...</span>
    <div aria-hidden="true" class="motion-safe:animate-pulse">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 sm:gap-[30px] mb-4 sm:mb-6">
        <div class="h-8 sm:h-[42px] w-2/3 sm:w-72 max-w-full rounded-lg bg-gray-200"></div>
        <div class="flex gap-2 sm:gap-3 w-full sm:w-auto">
          <div class="h-11 sm:h-[50px] flex-1 sm:flex-none sm:w-28 rounded-full bg-gray-200"></div>
          <div class="h-11 sm:h-[50px] flex-1 sm:flex-none sm:w-40 rounded-full bg-gray-200"></div>
        </div>
      </div>
      <div class="flex flex-col lg:flex-row gap-5 lg:gap-[50px] mb-6 sm:mb-8">
        <div class="shrink-0 w-full lg:w-[480px] h-[200px] sm:h-[250px] rounded-[20px] bg-gray-200"></div>
        <div class="grid grid-cols-2 gap-3 sm:gap-5 w-full">
          <div v-for="n in 4" :key="n" class="flex flex-col rounded-[20px] border border-[#CFDBEF] p-4 sm:p-5 gap-2 sm:gap-4">
            <div class="w-6 h-6 sm:w-8 sm:h-8 rounded-lg bg-gray-200"></div>
            <div class="h-4 sm:h-5 w-3/4 rounded bg-gray-200"></div>
          </div>
        </div>
      </div>
      <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 mb-6 space-y-4">
        <div class="h-6 w-40 rounded bg-gray-200"></div>
        <div class="h-4 w-3/4 rounded bg-gray-100"></div>
        <div class="h-11 w-full rounded-xl bg-gray-100"></div>
        <div class="h-4 w-1/2 rounded bg-gray-100"></div>
      </div>
      <div class="rounded-2xl sm:rounded-[30px] p-4 sm:p-[30px] space-y-4 sm:space-y-[30px] bg-[#F8FAFB]">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div class="h-7 w-32 rounded bg-gray-200"></div>
          <div class="flex gap-3">
            <div class="h-11 w-28 rounded-full bg-gray-200"></div>
            <div class="h-11 w-32 rounded-full bg-gray-200"></div>
          </div>
        </div>
        <div class="h-11 w-56 max-w-full rounded-full bg-gray-200"></div>
        <div v-for="n in 3" :key="n" class="flex items-center gap-4 rounded-2xl bg-white p-4 sm:p-5">
          <div class="w-12 h-12 shrink-0 rounded-xl bg-gray-200"></div>
          <div class="flex-1 min-w-0 space-y-3">
            <div class="h-4 w-1/3 rounded bg-gray-100"></div>
            <div class="h-5 w-3/4 rounded bg-gray-200"></div>
          </div>
          <div class="hidden sm:block h-9 w-24 rounded-full bg-gray-200"></div>
        </div>
      </div>
    </div>
  </div>
</template>
