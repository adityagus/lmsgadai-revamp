<template>
  <aside class="sidebar-container fixed h-[calc(100vh-20px)] w-full max-w-[260px] my-[10px] ml-[10px] bg-white overflow-hidden flex flex-1 rounded-[20px] shadow-md border border-[#E5E7EB]">
    <nav class="flex flex-col w-full h-fit p-[28px] gap-[24px] z-10">
      <div class="flex flex-col items-center gap-2 mb-8">
        <img src="/assets/images/logo.svg" alt="Logo" class="w-14 h-14 rounded-full bg-white shadow" />
        <span class="font-bold text-base text-[#374151]">Student Area</span>
      </div>
      <RouterLink
        to="/student/courses"
        class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium text-[#374151] hover:bg-[#F3F4F6] transition-all"
        :class="{ 'active-student': $route.path.startsWith('/student/courses') }"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0v6m0 0H6m6 0h6" />
        </svg>
        <span>Kursus Saya</span>
      </RouterLink>
      <RouterLink
        to="/student/profile"
        class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium text-[#374151] hover:bg-[#F3F4F6] transition-all"
        :class="{ 'active-student': $route.path.startsWith('/student/profile') }"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 15c2.5 0 4.847.655 6.879 1.804M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <span>Profil</span>
      </RouterLink>
      <RouterLink
        to="/student/settings"
        class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium text-[#374151] hover:bg-[#F3F4F6] transition-all"
        :class="{ 'active-student': $route.path.startsWith('/student/settings') }"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6 0a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>Pengaturan</span>
      </RouterLink>
      <div class="flex-1"></div>
      <button class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium text-[#EF4444] hover:bg-[#FEE2E2] transition-all mt-8">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7" />
        </svg>
        <span>Keluar</span>
      </button>
    </nav>
  </aside>
</template>

<script setup>
// No script needed for static sidebar
</script>

<style scoped>
.sidebar-container {
  min-width: 200px;
  background: #fff;
  border: 1px solid #E5E7EB;
}
.active-student {
  background: #EAEAEA !important;
  color: #662FFF !important;
}
</style>
