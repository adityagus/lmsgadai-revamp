<script setup>
import { ref, watch } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import Sidebar from '@/components/Sidebar.vue';
import Header from '@/components/Header.vue';
import Bot from '@/components/ChatbotWidget.vue';
const page = usePage();
const sidebarOpen = ref(false);
watch(() => page.url, () => { sidebarOpen.value = false; });
</script>

<template>
  <Head :title="page.props.title || 'LMS Gadai Mulia'" />
  <div class="flex min-h-screen bg-[#F6F7FB]">
    <Sidebar :sidebarOpen="sidebarOpen" @closeSidebar="sidebarOpen = false" />
    <main class="flex flex-col flex-1 gap-6 md:gap-[30px] p-4 md:p-[30px] md:ml-[290px] transition-all duration-300 min-w-0">
      <Header @toggleSidebar="sidebarOpen = true" />
      <p v-if="page.props.flash?.message" role="status" class="rounded-xl bg-purple-50 p-4 text-sm text-purple-700">{{ page.props.flash.message }}</p>
      <slot />
      <Bot />
    </main>
  </div>
</template>
