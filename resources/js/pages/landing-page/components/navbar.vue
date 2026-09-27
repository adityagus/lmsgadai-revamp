<template>
  <nav id="nav-guest" class="w-full bg-white border-b border-obito-grey sticky top-0 z-40">
    <div class="flex w-full max-w-[1280px] px-4 md:px-[75px] py-5 items-center justify-between mx-auto relative">
      <!-- Hamburger (mobile) -->
      <button @click="sidebarOpen = true" class="md:hidden mr-3 p-2 rounded focus:outline-none">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
          class="w-7 h-7 text-obito-green">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>
      <!-- Logo -->
      <a href="index.html" class="flex shrink-0">
        <img src="assets/images/logos/logo.svg" class="flex shrink-0 w-32 md:w-auto" alt="logo">
      </a>
      <!-- Menu -->
      <ul
        :class="['flex-col md:flex-row md:flex items-center gap-6 md:gap-10 absolute md:static left-0 top-full w-full md:w-auto bg-white md:bg-transparent shadow-lg md:shadow-none transition-all duration-300', menuOpen ? 'flex' : 'hidden md:flex']">
        <li class="hover:font-semibold transition-all duration-300 font-semibold px-4 md:px-0 py-3 md:py-0"><a
            href="index.html">Home</a></li>
        <li class="hover:font-semibold transition-all duration-300 px-4 md:px-0 py-3 md:py-0"><a
            href="pricing.html">Pricing</a></li>
        <li class="hover:font-semibold transition-all duration-300 px-4 md:px-0 py-3 md:py-0"><a href="#">Features</a>
        </li>
        <li class="hover:font-semibold transition-all duration-300 px-4 md:px-0 py-3 md:py-0"><a
            href="#">Testimonials</a></li>
      </ul>
      <!-- Right Side -->
      <div class="flex items-center gap-3 md:gap-5 justify-end ml-2">
        <a href="#" class="flex shrink-0">
          <img src="assets/images/icons/device-message.svg" class="flex shrink-0 w-7 h-7" alt="icon">
        </a>
        <div class="h-[40px] md:h-[50px] flex shrink-0 bg-obito-grey w-px"></div>
        <!-- Profile Dropdown -->
        <div class="relative group">
          <button class="flex items-center gap-2 focus:outline-none">
            <img src="/assets/images/photos/photo-1.png" class="w-10 h-10 rounded-full object-cover" alt="profile" />
            <svg class="w-4 h-4 text-obito-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          <ul
            class="absolute right-0 top-full mt-2 w-44 bg-white rounded-xl shadow-lg border border-gray-100 py-2 z-50 hidden group-hover:block">
            <li><a href="#" class="block px-4 py-2 hover:bg-gray-100">My Account</a></li>
            <li><a href="#" class="block px-4 py-2 hover:bg-gray-100">Settings</a></li>
            <li><a href="#" class="block px-4 py-2 hover:bg-gray-100">Logout</a></li>
          </ul>
        </div>
        <div class="flex items-center gap-2 md:gap-3">
          <a href="signup.html"
            class="rounded-full border border-obito-grey py-2 md:py-3 px-4 md:px-5 gap-[10px] bg-white hover:border-obito-green transition-all duration-300">
            <span class="font-semibold">Sign Up</span>
          </a>
          <a href="signin.html"
            class="rounded-full py-2 md:py-3 px-4 md:px-5 gap-[10px] bg-obito-green hover:drop-shadow-effect transition-all duration-300">
            <span class="font-semibold text-white">My Account</span>
          </a>
        </div>
      </div>
      <!-- Sidebar Overlay (mobile) -->
      <div v-if="sidebarOpen" class="fixed inset-0 bg-black bg-opacity-40 z-40 md:hidden" @click="sidebarOpen = false">
      </div>
      <!-- Sidebar (mobile) -->
      <aside v-if="sidebarOpen"
        class="fixed top-0 left-0 h-full w-64 bg-white shadow-lg z-50 flex flex-col p-6 md:hidden transition-transform duration-300">
        <button @click="sidebarOpen = false" class="self-end mb-6 p-2 rounded focus:outline-none">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
            class="w-7 h-7 text-obito-green">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
        <a href="index.html" class="mb-8 flex items-center gap-2">
          <img src="assets/images/logos/logo.svg" class="w-24" alt="logo" />
        </a>
        <ul class="flex flex-col gap-4">
          <li><a href="index.html" class="block py-2 px-2 rounded hover:bg-gray-100">Home</a></li>
          <li><a href="pricing.html" class="block py-2 px-2 rounded hover:bg-gray-100">Pricing</a></li>
          <li><a href="#" class="block py-2 px-2 rounded hover:bg-gray-100">Features</a></li>
          <li><a href="#" class="block py-2 px-2 rounded hover:bg-gray-100">Testimonials</a></li>
        </ul>
        <div class="mt-auto flex flex-col gap-2 pt-8">
          <a href="signup.html"
            class="rounded-full border border-obito-grey py-2 px-4 bg-white hover:border-obito-green transition-all duration-300 text-center font-semibold">Sign
            Up</a>
          <a href="signin.html"
            class="rounded-full py-2 px-4 bg-obito-green hover:drop-shadow-effect transition-all duration-300 text-center font-semibold text-white">My
            Account</a>
        </div>
      </aside>
    </div>
  </nav>
</template>

<script setup>
import { ref } from 'vue';
const menuOpen = ref(false);
const sidebarOpen = ref(false);
</script>