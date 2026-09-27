<template>
  <nav :class="[
      'w-full fixed top-0 left-0 z-50 transition-all duration-300',
      isScrolled 
        ? 'bg-white/80 backdrop-blur-md shadow-lg border-b border-white/20' 
        : 'bg-white shadow'
    ]">
    <div class="max-w-7xl mx-auto flex items-center justify-between px-6 py-4">
      <router-link to="/" class="flex items-center gap-2 transition-transform duration-300 hover:scale-105">
        <img 
          src="/assets/images/logos/lms.png" 
          alt="Beladiri" 
          :class="[
            'transition-all duration-300',
            isScrolled ? 'w-32' : 'w-36'
          ]"
        >
      </router-link>
      <div class="hidden md:flex gap-8">
        <a href="#about" :class="[
          'font-medium transition-all duration-300',
          isScrolled 
            ? 'text-gray-800 hover:text-indigo-700' 
            : 'text-gray-700 hover:text-indigo-600'
        ]">Tentang Kami</a>
        <a href="#courses" :class="[
          'font-medium transition-all duration-300',
          isScrolled 
            ? 'text-gray-800 hover:text-indigo-700' 
            : 'text-gray-700 hover:text-indigo-600'
        ]">Kursus</a>
        <a href="#testimonial" :class="[
          'font-medium transition-all duration-300',
          isScrolled 
            ? 'text-gray-800 hover:text-indigo-700' 
            : 'text-gray-700 hover:text-indigo-600'
        ]">Testimoni</a>
        <a href="#faq" :class="[
          'font-medium transition-all duration-300',
          isScrolled 
            ? 'text-gray-800 hover:text-indigo-700' 
            : 'text-gray-700 hover:text-indigo-600'
        ]">FAQ</a>
      </div>
      <router-link 
        :to="idgrup ? '/dashboard' : '/sign-in'" 
        :class="[
          'font-semibold py-2 px-6 rounded-lg shadow transition-all duration-300 transform hover:scale-105',
          isScrolled 
            ? 'bg-indigo-700 hover:bg-indigo-800 text-white shadow-lg' 
            : 'bg-indigo-600 hover:bg-indigo-700 text-white'
        ]"
      >
        {{ idgrup ? "Dashboard" : 'Login' }}
      </router-link>
    </div>
  </nav>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const idgrup = ref(localStorage.getItem('idgrup'));
const isScrolled = ref(false);

const handleScroll = () => {
  isScrolled.value = window.scrollY > 50;
};

onMounted(() => {
  window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
});
</script>

<style scoped>
nav {
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  background-color: rgba(255, 255, 255, 0.9);
}
nav.bg-white\/80 {
  background-color: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(20px) saturate(180%);
  -webkit-backdrop-filter: blur(20px) saturate(180%);
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}
nav a, nav span {
  transition: color 0.3s ease;
}
</style>
