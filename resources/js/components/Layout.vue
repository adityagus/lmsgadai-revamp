<template>
  <div v-if='isManagerPreviewPage'>
    <router-view :key="$route.meta.values"></router-view>
  </div>
  <div v-else class="flex min-h-screen bg-[#F6F7FB]">
    <Sidebar :sidebarOpen="sidebarOpen" @closeSidebar="sidebarOpen = false" />
    <main class="flex flex-col flex-1 gap-6 md:gap-[30px] p-4 md:p-[30px] md:ml-[290px] transition-all duration-300 min-w-0">
      <Header @toggleSidebar="sidebarOpen = true" />
      <router-view :key="$route.meta.values"></router-view>
      <Bot v-if="$route.name !== 'content-preview'" />
      <!-- <Outlet/> -->
    </main>
  </div>
</template>

<script setup>
import Header from '@/components/Header.vue';
import Sidebar from '@/components/Sidebar.vue';
import Bot from '@/components/ChatbotWidget.vue';
import { useRoute } from '@/navigation';
import { ref, watch } from 'vue';

const route = useRoute();
const isManagerPreviewPage = ref(route.name === 'content-preview' || route.name === 'content-admin-preview');
watch( () => route.path, () => {
  isManagerPreviewPage.value = (route.name === 'content-preview' || route.name === 'content-admin-preview');
});

const sidebarOpen = ref(false)
// const isStudentPreviewPage = computed(() =>
//   route.name === 'StudentPreview'
// );

</script>
