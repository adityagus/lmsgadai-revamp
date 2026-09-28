<template>
  <Head title="Beladiri - Belajar Mandiri" />
  <!-- Navbar -->
  <nav :class="[
    'w-full fixed top-0 left-0 z-50 transition-all duration-300',
    isScrolled
      ? 'bg-white/80 backdrop-blur-md shadow-lg border-b border-white/20'
      : 'bg-white shadow'
  ]">
    <div class="max-w-6xl mx-auto flex items-center justify-between gap-4 px-4 sm:px-6 py-4">
      <a href="/" class="flex items-center gap-2 transition-transform duration-300 hover:scale-105">
        <img src="/assets/images/logos/lms.png" alt="Beladiri" :class="[
          'transition-all duration-300',
          isScrolled ? 'w-32' : 'w-36'
        ]">
      </a>
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
      <router-link :to="signedIn ? '/overview' : '/sign-in'" :class="[
        'font-semibold py-2 px-6 rounded-lg shadow transition-all duration-300 transform hover:scale-105',
        isScrolled
          ? 'bg-indigo-700 hover:bg-indigo-800 text-white shadow-lg'
          : 'bg-indigo-600 hover:bg-indigo-700 text-white'
      ]">
        {{ signedIn ? "Dashboard" : 'Login' }}
      </router-link>
    </div>
  </nav>

  <!-- Hero Section -->
  <section
    class="landing-hero bg-gradient-to-br from-indigo-50 to-white flex flex-col items-center text-center px-4 sm:px-6 pt-36 md:pt-44 pb-16 md:pb-20"
    data-aos="fade-up" data-aos-duration="1500">
    <div class="w-full max-w-3xl mx-auto">
      <h1 class="text-3xl md:text-5xl font-extrabold text-indigo-800 mb-4 leading-tight">
        Selamat Datang di <span class="text-indigo-600">Beladiri</span>
      </h1>
      <p class="text-base md:text-lg leading-relaxed text-gray-600 mt-6 mb-8">
        Platform <strong>Belajar Mandiri</strong> (Beladiri) modern untuk pelatihan, kursus, dan pengembangan SDM
        berbasis digital. Mudah, aman, dan terintegrasi.
      </p>
      <router-link :to="signedIn ? '/student/kelas' : '/sign-in'"
        class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-8 rounded-lg shadow-lg transition-colors text-lg">
        Lihat Kursus
      </router-link>
    </div>
    <div class="mt-16 md:mt-20 w-full max-w-6xl mx-auto text-gray-600">
      <h2 class="text-2xl font-bold mb-4 text-indigo-700">Fitur Unggulan</h2>
      <ul class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <li class="bg-white rounded-2xl border border-indigo-100 shadow-sm p-6">
          <h3 class="font-semibold text-lg mb-2 text-indigo-600">Kelas Online Interaktif</h3>
          <p>Belajar dengan video, dokumen, dan kuis interaktif yang mudah diakses kapan saja.</p>
        </li>
        <li class="bg-white rounded-2xl border border-indigo-100 shadow-sm p-6">
          <h3 class="font-semibold text-lg mb-2 text-indigo-600">Manajemen Sertifikat</h3>
          <p>Otomatisasi penerbitan sertifikat digital setelah menyelesaikan kursus.</p>
        </li>
        <li class="bg-white rounded-2xl border border-indigo-100 shadow-sm p-6">
          <h3 class="font-semibold text-lg mb-2 text-indigo-600">Dashboard & Laporan</h3>
          <p>Pantau progres belajar, statistik peserta, dan laporan aktivitas secara real-time.</p>
        </li>
      </ul>
    </div>
  </section>

  <!-- Category Card Section -->
  <section id="categories" class="max-w-6xl mx-auto py-14 md:py-20 px-4 sm:px-6" data-aos="fade-up" data-aos-duration="1500">
    <h2 class="text-3xl font-bold text-indigo-700 mb-8 text-center">Kategori Kursus</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-8">
      <div class="bg-white rounded-2xl border border-indigo-100 shadow-sm flex flex-col items-center p-6">
        <h3 class="font-semibold text-indigo-700 mb-2">Marketing</h3>
        <p class="text-gray-600 text-center">Strategi pemasaran, branding, dan promosi digital untuk bisnis masa kini.
        </p>
      </div>
      <div class="bg-white rounded-2xl border border-indigo-100 shadow-sm flex flex-col items-center p-6">
        <h3 class="font-semibold text-indigo-700 mb-2">Programming</h3>
        <p class="text-gray-600 text-center">Belajar coding, pengembangan aplikasi, dan teknologi software modern.</p>
      </div>
      <div class="bg-white rounded-2xl border border-indigo-100 shadow-sm flex flex-col items-center p-6">
        <h3 class="font-semibold text-indigo-700 mb-2">Design</h3>
        <p class="text-gray-600 text-center">Desain grafis, UI/UX, dan kreativitas visual untuk kebutuhan digital.</p>
      </div>
      <div class="bg-white rounded-2xl border border-indigo-100 shadow-sm flex flex-col items-center p-6">
        <h3 class="font-semibold text-indigo-700 mb-2">Finance</h3>
        <p class="text-gray-600 text-center">Manajemen keuangan, investasi, dan akuntansi untuk masa depan.</p>
      </div>
    </div>
  </section>
  <!-- About Us Section -->
  <section id="about" class="max-w-5xl mx-auto py-14 md:py-20 px-4 sm:px-6 text-center" data-aos="fade-up" data-aos-duration="1500">
    <h2 class="text-3xl font-bold text-indigo-700 mb-6">Tentang Kami</h2>
    <p class="text-base md:text-lg leading-relaxed text-gray-600 mb-4">Beladiri (Belajar Mandiri) adalah platform pembelajaran digital yang
      didedikasikan untuk meningkatkan kualitas SDM melalui pelatihan dan kursus online yang interaktif dan
      terintegrasi. Kami berkomitmen untuk memberikan pengalaman belajar terbaik dengan teknologi terkini dan dukungan
      penuh bagi para peserta.</p>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl mx-auto mt-8">
      <div class="bg-white rounded-2xl border border-indigo-100 shadow-sm p-6 w-full">
        <h3 class="font-semibold text-indigo-600 mb-2">Visi</h3>
        <p>Mewujudkan SDM unggul melalui pembelajaran digital yang mudah diakses dan berkualitas.</p>
      </div>
      <div class="bg-white rounded-2xl border border-indigo-100 shadow-sm p-6 w-full">
        <h3 class="font-semibold text-indigo-600 mb-2">Misi</h3>
        <p>Menyediakan platform LMS yang inovatif, aman, dan terintegrasi untuk pelatihan dan pengembangan SDM.</p>
      </div>
    </div>
  </section>

  <!-- Courses Card Section -->
  <section id="courses" class="bg-indigo-50 py-14 md:py-20 px-4 sm:px-6" data-aos="fade-up" data-aos-duration="1500">
    <div class="max-w-6xl mx-auto text-center mb-12">
      <h2 class="text-3xl font-bold text-indigo-700 mb-4">Kursus Populer</h2>
      <p class="text-gray-700">Pilih kursus terbaik sesuai kebutuhan Anda dan mulai belajar mandiri sekarang!</p>
    </div>
    <div class="flex flex-wrap justify-center gap-8 max-w-6xl mx-auto">

      <div v-for="course in courses" :key="course.id"
        class="course-card group w-full md:w-[calc(33.333%-1.34rem)] bg-white rounded-lg shadow-lg flex flex-col">
        <img :src="course.thumbnail_url || '/assets/images/logos/lms.png'" :alt="course.name"
          class="w-full h-48 object-cover rounded-t-lg transition-transform duration-300 group-hover:scale-110">
        <div class="p-6 flex flex-col items-center">
          <h3
            class="font-semibold text-lg text-indigo-700 text-center group-hover:text-indigo-800 transition-colors duration-300">
            {{ course.name }}
          </h3>
          <p
            class="text-gray-600 mb-4 text-center text-sm my-4 group-hover:text-gray-700 transition-colors duration-300">
            {{ truncateDescription(course.description, 20) }}
          </p>
          <router-link :to="{ path: `/detail-course/${course.id}`, hash: '#details' }"
            class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 transition-all duration-300 transform group-hover:scale-105 group-hover:shadow-lg">
            Lihat Detail
          </router-link>
        </div>
      </div>

      <p v-if="loadingCourses" role="status" class="w-full text-center text-gray-500 py-10">Memuat kursus...</p>
      <div v-if="!loadingCourses && courses.length === 0" class="w-full text-center text-gray-500 py-10">
        {{ courseError || (signedIn ? 'Belum ada kursus populer.' : 'Silakan login untuk melihat kursus yang tersedia.') }}
      </div>
    </div>
  </section>

  <!-- Testimonial Section -->
  <section id="testimonial" class="max-w-6xl mx-auto py-14 md:py-20 px-4 sm:px-6" data-aos="fade-up" data-aos-duration="1500">
    <h2 class="text-3xl font-bold text-indigo-700 mb-8 text-center">Testimoni Peserta</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
      <figure class="bg-white rounded-2xl border border-indigo-100 shadow-sm p-6 md:p-8 flex flex-col">
        <blockquote class="text-gray-600 leading-relaxed mb-6 flex-1">"Platform Beladiri sangat membantu saya dalam memahami materi secara
          praktis dan mudah!"</blockquote>
        <figcaption class="border-t border-indigo-100 pt-4 text-sm">
          <span class="font-semibold text-indigo-600">Budi Santoso</span>
        </figcaption>
      </figure>
      <figure class="bg-white rounded-2xl border border-indigo-100 shadow-sm p-6 md:p-8 flex flex-col">
        <blockquote class="text-gray-600 leading-relaxed mb-6 flex-1">"Materi kursus sangat lengkap dan mudah dipahami. Sertifikat digitalnya
          juga langsung didapat!"</blockquote>
        <figcaption class="border-t border-indigo-100 pt-4 text-sm">
          <span class="font-semibold text-indigo-600">Siti Aminah</span>
        </figcaption>
      </figure>
      <figure class="bg-white rounded-2xl border border-indigo-100 shadow-sm p-6 md:p-8 flex flex-col">
        <blockquote class="text-gray-600 leading-relaxed mb-6 flex-1">"Dashboard dan laporan progres sangat membantu saya memantau perkembangan
          belajar."</blockquote>
        <figcaption class="border-t border-indigo-100 pt-4 text-sm">
          <span class="font-semibold text-indigo-600">Agus Wijaya</span>
        </figcaption>
      </figure>
    </div>
  </section>

  <!-- FAQ Section -->
  <section id="faq" class="bg-indigo-50 py-14 md:py-20 px-4 sm:px-6" data-aos="fade-up" data-aos-duration="1500">
    <div class="max-w-4xl mx-auto">
      <h2 class="text-3xl font-bold text-indigo-700 mb-8 text-center">FAQ</h2>
      <div class="space-y-6">
        <div class="bg-white rounded-2xl border border-indigo-100 shadow-sm p-6">
          <h3 class="font-semibold text-indigo-600 mb-2">Siapa saja yang bisa mengakses Beladiri?</h3>
          <p class="text-gray-700">Beladiri dapat diakses oleh seluruh karyawan dan peserta pelatihan yang telah
            terdaftar oleh admin perusahaan.</p>
        </div>
        <div class="bg-white rounded-2xl border border-indigo-100 shadow-sm p-6">
          <h3 class="font-semibold text-indigo-600 mb-2">Bagaimana cara login ke platform?</h3>
          <p class="text-gray-700">Silakan klik tombol Login di pojok kanan atas dan masukkan username serta password
            yang sudah diberikan oleh admin.</p>
        </div>
        <div class="bg-white rounded-2xl border border-indigo-100 shadow-sm p-6">
          <h3 class="font-semibold text-indigo-600 mb-2">Apakah materi bisa diakses kapan saja?</h3>
          <p class="text-gray-700">Ya, seluruh materi pelatihan dapat diakses kapan saja selama masa pelatihan
            berlangsung.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-indigo-900 text-white pt-12 pb-6">
    <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-8 px-4">
      <!-- Logo & Deskripsi -->
      <div class="flex flex-col items-start md:items-start">
        <img src="/assets/images/logos/lms.png" alt="Beladiri" class="w-32 mb-4">
        <p class="text-sm">Platform Belajar Mandiri (Beladiri) modern untuk pelatihan, kursus, dan pengembangan SDM
          berbasis digital.</p>
      </div>
      <!-- Navigasi -->
      <div>
        <h3 class="font-semibold text-lg mb-4">Navigasi</h3>
        <ul class="space-y-2">
          <li><a href="#about" class="hover:underline">Tentang Kami</a></li>
          <li><a href="#categories" class="hover:underline">Kategori Kursus</a></li>
          <li><a href="#courses" class="hover:underline">Kursus Populer</a></li>
          <li><a href="#testimonial" class="hover:underline">Testimoni</a></li>
          <li><a href="#faq" class="hover:underline">FAQ</a></li>
        </ul>
      </div>
      <!-- Kontak -->
      <div>
        <h3 class="font-semibold text-lg mb-4">Kontak</h3>
        <ul class="space-y-2 text-sm">
          <li>Email: <a href="mailto:info@gadaimulia.com" class="hover:underline">info@gadaimulia.com</a></li>
          <li>Telepon: <a href="tel:+622112345678" class="hover:underline">(021) 123-45678</a></li>
          <li>Alamat: <a href="https://g.co/kgs/rfm8rsp" class='hover:underline'>Kirana Three Office Tower, Jl.
              Boulevard Raya No.1 Lantai 15, Klp. Gading Tim., Kec. Klp. Gading, Jkt Utara, Daerah Khusus Ibukota
              Jakarta 14240</a></li>
        </ul>
      </div>
      <!-- Social Media -->
      <div>
        <h3 class="font-semibold text-lg mb-4">Ikuti Kami</h3>
        <div class="flex gap-4 mt-2">
          <a href="#" aria-label="Instagram" class="hover:text-indigo-300">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
              <path
                d="M7.75 2h8.5A5.75 5.75 0 0 1 22 7.75v8.5A5.75 5.75 0 0 1 16.25 22h-8.5A5.75 5.75 0 0 1 2 16.25v-8.5A5.75 5.75 0 0 1 7.75 2zm0 1.5A4.25 4.25 0 0 0 3.5 7.75v8.5A4.25 4.25 0 0 0 7.75 20.5h8.5A4.25 4.25 0 0 0 20.5 16.25v-8.5A4.25 4.25 0 0 0 16.25 3.5h-8.5zm4.25 3.25a5.25 5.25 0 1 1 0 10.5a5.25 5.25 0 0 1 0-10.5zm0 1.5a3.75 3.75 0 1 0 0 7.5a3.75 3.75 0 0 0 0-7.5zm5.25.75a1 1 0 1 1-2 0a1 1 0 0 1 2 0z" />
            </svg>
          </a>
          <a href="#" aria-label="Facebook" class="hover:text-indigo-300">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
              <path
                d="M22 12c0-5.522-4.477-10-10-10S2 6.478 2 12c0 5.019 3.676 9.163 8.438 9.877v-6.987h-2.54v-2.89h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89c1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.242 0-1.63.771-1.63 1.562v1.875h2.773l-.443 2.89h-2.33v6.987C18.324 21.163 22 17.019 22 12z" />
            </svg>
          </a>
          <a href="#" aria-label="LinkedIn" class="hover:text-indigo-300">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
              <path
                d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.761 0 5-2.239 5-5v-14c0-2.761-2.239-5-5-5zm-11 19h-3v-10h3v10zm-1.5-11.268c-.966 0-1.75-.784-1.75-1.75s.784-1.75 1.75-1.75s1.75.784 1.75 1.75s-.784 1.75-1.75 1.75zm13.5 11.268h-3v-5.604c0-1.337-.026-3.063-1.868-3.063c-1.868 0-2.154 1.459-2.154 2.967v5.7h-3v-10h2.881v1.367h.041c.401-.761 1.381-1.563 2.841-1.563c3.039 0 3.601 2.002 3.601 4.604v5.592z" />
            </svg>
          </a>
        </div>
      </div>
    </div>
    <div class="border-t border-indigo-700 mt-10 pt-6 text-center text-sm text-indigo-300">&copy; 2025 Beladiri (Belajar
      Mandiri). All rights reserved.</div>
  </footer>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AOS from 'aos';
import 'aos/dist/aos.css';

const page = usePage();
const signedIn = computed(() => Boolean(page.props.auth?.user));
const courses = ref([]);
const loadingCourses = ref(false);
const courseError = ref('');
const isScrolled = ref(false);
const abortController = new AbortController();
const handleScroll = () => { isScrolled.value = window.scrollY > 50; };
const truncateDescription = (text, maxWords) => {
  if (!text) return '';
  const words = text.split(' ');
  return words.length <= maxWords ? text : words.slice(0, maxWords).join(' ') + '...';
};

onMounted(async () => {
  AOS.init({ once: true, disable: () => window.matchMedia('(prefers-reduced-motion: reduce)').matches });
  handleScroll();
  window.addEventListener('scroll', handleScroll, { passive: true });
  if (!signedIn.value) return;
  loadingCourses.value = true;
  try {
    const response = await fetch('/api/courses', {
      headers: { Accept: 'application/json' }, signal: abortController.signal,
    });
    if (!response.ok) throw new Error('Gagal memuat kursus');
    const data = await response.json();
    courses.value = Array.isArray(data) ? data : (data.data || []);
    AOS.refresh();
  } catch (error) {
    if (error.name !== 'AbortError') courseError.value = 'Kursus belum dapat dimuat. Silakan coba kembali nanti.';
  } finally {
    loadingCourses.value = false;
  }
});
onUnmounted(() => {
  abortController.abort();
  window.removeEventListener('scroll', handleScroll);
});
</script>

<style scoped>
/* Scroll smooth */
section[id] {
  scroll-margin-top: 100px;
}

.landing-hero {
  background: linear-gradient(135deg, #eef2ff 0%, #fff 100%);
}

/* Navbar blur effects */
nav {
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  /* Fallback for browsers that don't support backdrop-filter */
  background-color: rgba(255, 255, 255, 0.9);
}

/* Enhanced navbar blur effect when scrolled */
nav.bg-white\/80 {
  background-color: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(20px) saturate(180%);
  -webkit-backdrop-filter: blur(20px) saturate(180%);
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

/* Smooth transition for navbar elements */
nav a,
nav span {
  transition: color 0.3s ease;
}

/* Course Card Hover Effects */
.course-card {
  position: relative;
  overflow: hidden;
  transform-origin: center;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.course-card:hover {
  box-shadow:
    0 20px 40px rgba(99, 102, 241, 0.15),
    0 15px 25px rgba(0, 0, 0, 0.1),
    0 5px 10px rgba(0, 0, 0, 0.1);
}

.course-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  transition: left 0.5s;
  z-index: 1;
}

.course-card:hover::before {
  left: 100%;
}

/* Image hover effect with better overflow handling */
.course-card img {
  transition: transform 0.4s ease-out;
  transform-origin: center center;
}

.course-card:hover img {
  transform: scale(1.05);
}

/* Button hover glow effect */
.course-card a {
  position: relative;
  overflow: hidden;
}

.course-card a::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
  transition: left 0.3s;
}

.course-card:hover a::before {
  left: 100%;
}

/* Add subtle border glow on hover */
.course-card {
  border: 2px solid transparent;
}

.course-card:hover {
  border-color: rgba(99, 102, 241, 0.2);
}

/* Additional blur effects for modern browsers */
@supports (backdrop-filter: blur(20px)) {
  nav {
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
  }
}

/* Fallback for older browsers */
@supports not (backdrop-filter: blur(20px)) {
  nav {
    background-color: rgba(255, 255, 255, 0.95);
  }

  nav.bg-white\/80 {
    background-color: rgba(255, 255, 255, 0.9);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  }
}
</style>
