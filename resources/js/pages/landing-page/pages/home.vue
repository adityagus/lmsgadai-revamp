<template>
    <Head title="Beladiri - Belajar Mandiri" />
    <div class="landing">
        <a class="skip-link" href="#main-content">Lewati ke konten</a>
        <header class="site-header">
            <div class="container header-inner">
                <Link href="/" class="brand"
                    ><img
                        src="/assets/images/logos/lms.png"
                        alt="Beladiri"
                        width="160"
                        height="52"
                /></Link>
                <nav class="desktop-nav" aria-label="Navigasi utama">
                    <a
                        v-for="item in navigation"
                        :key="item.id"
                        :href="`#${item.id}`"
                        >{{ item.label }}</a
                    >
                </nav>
                <div class="header-actions">
                    <Link
                        :href="signedIn ? '/overview' : '/sign-in'"
                        class="button lms-primary-button"
                        >{{ signedIn ? "Dashboard" : "Sign In" }}</Link
                    ><button
                        class="menu-toggle"
                        :aria-expanded="menuOpen"
                        aria-controls="mobile-navigation"
                        :aria-label="menuOpen ? 'Tutup menu' : 'Buka menu'"
                        @click="menuOpen = !menuOpen"
                    >
                        <i
                            class="pi"
                            :class="menuOpen ? 'pi-times' : 'pi-bars'"
                            aria-hidden="true"
                        ></i>
                    </button>
                </div>
            </div>
            <nav
                v-if="menuOpen"
                id="mobile-navigation"
                class="mobile-nav container"
                aria-label="Navigasi ponsel"
                @keydown.esc="menuOpen = false"
            >
                <a
                    v-for="item in navigation"
                    :key="item.id"
                    :href="`#${item.id}`"
                    @click="menuOpen = false"
                    >{{ item.label }}</a
                >
            </nav>
        </header>
        <main id="main-content">
            <section class="hero container">
                <div class="hero-copy">
                    <h1>
                        Langkah kecil belajar.<br /><span
                            >Dampak besar<br class="desktop-break" />
                            untuk berkembang.</span
                        >
                    </h1>
                    <p>
                        Kenali hal baru, asah keterampilan, dan tumbuh bersama.
                        Beladiri menemani perjalanan belajar mandiri Anda, dalam
                        satu tempat.
                    </p>
                    <div class="hero-actions">
                        <Link
                            :href="signedIn ? '/student/kelas' : '/sign-in'"
                            class="button lms-primary-button"
                            >Mulai belajar
                        </Link
                        ><a href="#about" class="button lms-secondary-button border border-white"
                            >Kenali Beladiri</a>
                    </div>
                </div>
                <div class="hero-visual">
                    <div class="photo-frame">
                        <img
                            src="/assets/images/backgrounds/discuss.png"
                            alt="Tim Gadai Mulia berdiskusi dan belajar bersama"
                            width="941"
                            height="1672"
                            fetchpriority="high"
                        />
                        <div class="photo-caption">
                            <span>BELAJAR BERSAMA. TUMBUH BERSAMA.</span>
                            <p>
                                Potensi terbaik dimulai<br />dari rasa ingin
                                tahu.
                            </p>
                        </div>
                    </div>
                    <div class="photo-tag">
                        <span class="icon-box"
                            ><i
                                class="pi pi-check-circle"
                                aria-hidden="true"
                            ></i
                        ></span>
                        <div>
                            <strong>Satu langkah lebih baik</strong
                            ><small
                                >Setiap hari, ada yang bisa dipelajari.</small
                            >
                        </div>
                    </div>
                    <i
                        class="pi pi-asterisk visual-spark"
                        aria-hidden="true"
                    ></i>
                </div>
            </section>
            <section class="features container" aria-label="Fitur unggulan">
                <article
                    v-for="(feature, index) in features"
                    :key="feature.title"
                >
                    <span class="icon-box"
                        ><i
                            class="pi"
                            :class="feature.icon"
                            aria-hidden="true"
                        ></i
                    ></span>
                    <div>
                        <span class="item-number">0{{ index + 1 }}</span>
                        <h2>{{ feature.title }}</h2>
                        <p>{{ feature.description }}</p>
                    </div>
                </article>
            </section>
            <section id="about" class="section container about-section">
                <div>
                    <span class="eyebrow">TENTANG BELADIRI</span>
                    <h2>
                        Ruang belajar untuk<br /><span
                            >potensi yang lebih besar.</span
                        >
                    </h2>
                </div>
                <div class="about-copy">
                    <p>
                        Beladiri (Belajar Mandiri) adalah platform pembelajaran
                        digital Gadai Mulia. Kami menghubungkan pelatihan,
                        materi, dan pengembangan keterampilan untuk mendukung
                        perjalanan setiap karyawan.
                    </p>
                    <div class="purpose-grid">
                        <article>
                            <span>01 / VISI</span>
                            <h3>Bertumbuh bersama</h3>
                            <p>
                                Mewujudkan SDM unggul melalui pembelajaran
                                digital yang mudah diakses dan berkualitas.
                            </p>
                        </article>
                        <article>
                            <span>02 / MISI</span>
                            <h3>Belajar lebih mudah</h3>
                            <p>
                                Menyediakan platform yang aman dan terintegrasi
                                untuk pelatihan dan pengembangan SDM.
                            </p>
                        </article>
                    </div>
                </div>
            </section>
            <section id="categories" class="section categories-section">
                <div class="container">
                    <div class="section-heading">
                        <div>
                            <span class="eyebrow">JELAJAHI MINAT ANDA</span>
                            <h2>Banyak cara untuk berkembang.</h2>
                        </div>
                        <p>
                            Temukan bidang yang mendukung keterampilan dan
                            pekerjaan Anda.
                        </p>
                    </div>
                    <div class="category-grid">
                        <article
                            v-for="(category, index) in categories"
                            :key="category.name"
                        >
                            <i
                                class="pi category-symbol"
                                :class="category.icon"
                                aria-hidden="true"
                            ></i
                            ><span class="item-number">0{{ index + 1 }}</span>
                            <h3>{{ category.name }}</h3>
                            <p>{{ category.description }}</p>
                        </article>
                    </div>
                </div>
            </section>
            <section id="courses" class="section container">
                <div class="section-heading">
                    <div>
                        <span class="eyebrow">LANJUTKAN PERJALANAN ANDA</span>
                        <h2>Jelajahi kelas populer.</h2>
                    </div>
                    <Link
                        :href="signedIn ? '/student/kelas' : '/sign-in'"
                        class="text-link"
                        >{{
                            signedIn
                                ? "Lihat semua kelas"
                                : "Masuk untuk melihat kelas"
                        }}
                        <i class="pi pi-angle-right" aria-hidden="true"></i
                    ></Link>
                </div>
                <div v-if="loadingCourses" class="course-grid" role="status">
                    <span class="sr-only">Memuat kelas...</span>
                    <div
                        v-for="n in 3"
                        :key="n"
                        class="course-skeleton"
                        aria-hidden="true"
                    ></div>
                </div>
                <div v-else-if="courseError" role="alert">
                    <p>{{ courseError }}</p>
                    <button type="button" class="button lms-primary-button mt-4" @click="refetch()">Coba lagi</button>
                </div>
                <div v-else-if="courses.length" class="course-grid">
                    <article
                        v-for="course in courses"
                        :key="course.id"
                        class="course-card"
                    >
                        <div class="course-image">
                            <img
                                :src="
                                    course.thumbnail_url ||
                                    '/assets/images/logos/lms.png'
                                "
                                :class="{
                                    'fallback-logo': !course.thumbnail_url,
                                }"
                                :alt="course.name"
                                loading="lazy"
                            />
                        </div>
                        <div class="course-body">
                            <span class="eyebrow">KELAS BELADIRI</span>
                            <h3>{{ course.name }}</h3>
                            <p>
                                {{
                                    truncateDescription(course.description, 20)
                                }}
                            </p>
                            <Link
                                :href="`/detail-course/${course.id}#details`"
                                class="text-link w-full justify-end"
                                >Lihat kelas</Link>
                        </div>
                    </article>
                </div>
                <p v-else>Belum ada kelas populer yang tersedia.</p>
            </section>
            <section id="testimonial" class="section testimonial-section">
                <div class="container">
                    <div class="section-heading">
                        <div>
                            <span class="eyebrow">CERITA PEMBELAJAR</span>
                            <h2>Pengalaman yang berarti.</h2>
                        </div>
                        <p>Setiap proses belajar punya ceritanya sendiri.</p>
                    </div>
                    <div class="testimonial-grid">
                        <figure
                            v-for="(testimonial, index) in testimonials"
                            :key="testimonial.name"
                        >
                            <span class="quote-mark" aria-hidden="true">&ldquo;</span>
                            <blockquote>{{ testimonial.quote }}</blockquote>
                            <figcaption>
                                <!-- <span
                                    class="avatar"
                                    :class="`avatar-${index}`"
                                    >{{
                                        testimonial.name
                                            .split(" ")
                                            .map((word) => word[0])
                                            .join("")
                                    }}</span
                                > -->
                                <img :src="testimonial.img" class="avatar" alt="">
                                <strong>{{ testimonial.name }}</strong>
                            </figcaption>
                        </figure>
                    </div>
                </div>
            </section>
            <section id="faq" class="section container faq-section">
                <div>
                    <span class="eyebrow">ADA PERTANYAAN?</span>
                    <h2>Mari mulai<br />dengan lebih mudah.</h2>
                    <p>
                        Beberapa hal yang perlu Anda ketahui sebelum mulai
                        belajar.
                    </p>
                </div>
                <div class="faq-list">
                    <details
                        v-for="(faq, index) in faqs"
                        :key="faq.question"
                        :open="index === 0"
                    >
                        <summary>
                            {{ faq.question
                            }}<i class="pi pi-plus" aria-hidden="true"></i>
                        </summary>
                        <p>{{ faq.answer }}</p>
                    </details>
                </div>
            </section>
            <section class="container closing-section">
                <div class="closing-card">
                    <div>
                        <span class="eyebrow"
                            >BELAJAR HARI INI, BERTUMBUH ESOK HARI</span
                        >
                        <h2>Langkah selanjutnya<br />dimulai dari Anda.</h2>
                    </div>
                    <Link
                        :href="signedIn ? '/student/kelas' : '/sign-in'"
                        class="button closing-button"
                        >Mulai perjalanan belajar
                        <i
                            class="pi pi-arrow-up-right"
                            aria-hidden="true"
                        ></i></Link
                    ><span class="closing-orbit" aria-hidden="true"></span>
                </div>
            </section>
        </main>
        <footer>
            <div class="container footer-grid">
                <div class="footer-brand">
                    <img
                        src="/assets/images/logos/lms.png"
                        alt="Beladiri"
                        width="160"
                        height="52"
                        loading="lazy"
                    />
                    <p>
                        Ruang belajar mandiri.<br />Untuk kita yang terus ingin
                        berkembang.
                    </p>
                </div>
                <div>
                    <h2>Jelajahi Beladiri</h2>
                    <a
                        v-for="item in navigation"
                        :key="item.id"
                        :href="`#${item.id}`"
                        >{{ item.label }}</a
                    ><a href="#categories">Kategori Kursus</a>
                </div>
                <div class="footer-contact">
                    <h2>Terhubung dengan kami</h2>
                    <a href="mailto:info@gadaimulia.com"
                        >info@gadaimulia.com
                        <i
                            class="pi pi-arrow-up-right"
                            aria-hidden="true"
                        ></i></a
                    ><a href="https://g.co/kgs/rfm8rsp"
                        >Kirana Three Office Tower, Lantai 15<br />Jl. Boulevard
                        Raya No. 1, Kelapa Gading<br />Jakarta Utara, 14240</a
                    >
                </div>
            </div>
            <div class="container footer-bottom">
                <span
                    >© {{ new Date().getFullYear() }} Gadai Mulia ·
                    Beladiri</span
                ><span>Belajar Mandiri, Tumbuh Bersama.</span>
            </div>
        </footer>
    </div>
</template>
<script setup>
import { computed, ref } from "vue";
import { useQuery } from "@tanstack/vue-query";
import { Head, Link, usePage } from "@inertiajs/vue3";
import { getPopularCourses } from "@/services/queries/courseQuery.ts";
import "primeicons/primeicons.css";
const page = usePage();
const signedIn = computed(() => Boolean(page.props.auth?.user));
const menuOpen = ref(false);
const {
    data: courseData,
    isFetching: loadingCourses,
    error: queryError,
    refetch,
} = useQuery(getPopularCourses.options());
const courses = computed(() => courseData.value?.data || []);
const courseError = computed(() =>
    queryError.value
        ? "Silakan coba lagi untuk memuat kelas yang tersedia."
        : "",
);
const truncateDescription = (text, maxWords) => {
    if (!text) return "";
    const words = String(text)
        .replace(/<[^>]*>/g, " ")
        .trim()
        .split(/\s+/);
    return words.length <= maxWords
        ? words.join(" ")
        : `${words.slice(0, maxWords).join(" ")}…`;
};
const navigation = [
    { id: "about", label: "Tentang Kami" },
    { id: "courses", label: "Kelas" },
    { id: "testimonial", label: "Testimoni" },
    { id: "faq", label: "FAQ" },
];
const features = [
    {
        icon: "pi-book",
        title: "Belajar lebih fleksibel",
        description:
            "Video, dokumen, dan materi pembelajaran dalam satu ruang.",
    },
    {
        icon: "pi-question-circle",
        title: "Uji pemahaman Anda",
        description: "Asah kemampuan melalui kuis dan evaluasi pembelajaran.",
    },
    {
        icon: "pi-check-circle",
        title: "Lihat perkembangan",
        description: "Ikuti progres, nilai, dan riwayat kuis selama belajar.",
    },
];
const categories = [
    {
        name: "Operasional Gadai",
        icon: "pi-building",
        description: "Prosedur transaksi gadai, pengelolaan barang jaminan, dan operasional cabang.",
    },
    {
        name: "Penaksiran Barang",
        icon: "pi-search",
        description: "Pemeriksaan keaslian, kondisi, dan nilai taksiran barang jaminan.",
    },
    {
        name: "Pelayanan Nasabah",
        icon: "pi-users",
        description: "Komunikasi yang ramah, pemahaman kebutuhan, dan penanganan keluhan nasabah.",
    },
    {
        name: "Keuangan",
        icon: "pi-wallet",
        description: "Pengelolaan kas, pencatatan transaksi, dan pelaporan keuangan cabang.",
    },
];
const testimonials = [
    {
        name: "Fachmi Ghazali",
        quote: "Beladiri sangat membantu saya dalam memahami materi secara praktis dan mudah!",
        img: "/assets/images/testimonials/fahmi.png",
    },
    {
        name: "Asep",
        quote: "Materi kursus sangat lengkap dan mudah dipahami dan dokumen nya sangat membantu!",
        img: "/assets/images/testimonials/asep.png",
    },
    {
        name: "Nanda Jalu",
        quote: "Beladiri sangat membantu mempermudah pekerjaan, mempercepat proses operasional, dan meningkatkan efisiensi kerja dengan sistem yang praktis dan mudah digunakan.",
        img: "/assets/images/testimonials/nanda.png",
    },
];
const faqs = [
    {
        question: "Siapa yang dapat mengakses Beladiri?",
        answer: "Beladiri dapat diakses oleh karyawan dan peserta pelatihan yang sudah terdaftar oleh admin perusahaan.",
    },
    {
        question: "Bagaimana cara masuk ke platform?",
        answer: "Klik Sign In, lalu masukkan username dan password akun karyawan Anda. Hubungi admin apabila memerlukan bantuan akses.",
    },
    {
        question: "Apakah materi bisa diakses kapan saja?",
        answer: "Materi dapat diakses selama Anda memiliki akses ke kelas dan masa pelatihan masih berlaku. Buka halaman pembelajaran untuk melihat kelas Anda.",
    },
];
</script>
<style scoped>
@media (prefers-reduced-motion: no-preference) {
    :global(html:has(.landing)) {
        scroll-behavior: smooth;
    }
}

.landing {
    color: #211735;
    background: #fff;
    font-family: Poppins, sans-serif;
    font-size: 14px;
    line-height: 1.7;
}
.container {
    width: min(1200px, calc(100% - 64px));
    margin-inline: auto;
}
.landing h1,
.landing h2,
.landing h3,
.landing p {
    margin: 0;
}
.landing a {
    text-decoration: none;
}
.landing button {
    font: inherit;
}
.landing a:focus-visible,
.landing button:focus-visible,
.landing summary:focus-visible {
    outline: 3px solid #bda4ff;
    outline-offset: 5px;
}
.landing section[id] {
    scroll-margin-top: 108px;
}
.skip-link {
    position: fixed;
    top: -100px;
    left: 16px;
    z-index: 100;
    background: #fff;
    padding: 12px;
}
.skip-link:focus {
    top: 12px;
}
.site-header {
    position: sticky;
    top: 0;
    z-index: 50;
    background: #ffffffed;
    border-bottom: 1px solid #f0ebf7;
    backdrop-filter: blur(16px);
}
.header-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 92px;
    gap: 28px;
}
.brand img {
    width: 158px;
    height: auto;
}
.desktop-nav {
    display: flex;
    gap: 32px;
}
.desktop-nav a {
    color: #70677d;
    font-size: 13px;
    font-weight: 500;
}
.desktop-nav a:hover,
.text-link:hover {
    color: var(--lms-primary);
}
.header-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}
.button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 24px;
    min-height: 48px;
    padding: 12px 23px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    white-space: nowrap;
}
.button > .pi,
.text-link > .pi {
    font-size: 15px;
    line-height: 1;
}
.menu-toggle {
    display: none;
    border: 1px solid #e6deef;
    border-radius: 10px;
    padding: 9px;
    background: white;
}
.menu-toggle .pi {
    font-size: 20px;
    width: 22px;
    height: 22px;
    display: grid;
    place-items: center;
}
.mobile-nav {
    display: none;
}
.hero {
    display: grid;
    grid-template-columns: 1.1fr 1fr;
    align-items: center;
    gap: 72px;
    padding-block: 74px 90px;
}
.eyebrow {
    display: block;
    font-size: 10px;
    letter-spacing: 1.6px;
    line-height: 1.7;
    color: #8059ad;
    font-weight: 650;
}
.hero-copy > .eyebrow {
    display: flex;
    align-items: center;
    gap: 9px;
    font-size: 9px;
    letter-spacing: 1.2px;
}
.status-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--lms-primary);
    box-shadow: 0 0 0 4px #eee6ff;
    flex-shrink: 0;
}
.hero h1 {
    font-size: clamp(35px, 3.55vw, 51px);
    font-weight: 650;
    line-height: 1.22;
    letter-spacing: -2px;
    margin: 24px 0;
}
.hero h1 > span {
    color: var(--lms-primary);
}
.hero-copy > p {
    color: #82788e;
    font-size: 14px;
    line-height: 1.95;
    max-width: 440px;
}
.hero-actions {
    display: flex;
    align-items: center;
    gap: 18px;
    margin-top: 32px;
}
.text-link {
    display: inline-flex;
    align-items: center;
    gap: 16px;
    font-size: 12px;
    font-weight: 600;
    color: #4b385e;
}
.hero-note {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 43px;
}
.icon-box {
    font-size: 20px;
    display: grid;
    place-items: center;
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: #f2ebff;
    color: #8c62b9;
    flex-shrink: 0;
}
.hero-note strong,
.photo-tag strong {
    font-size: 11px;
    font-weight: 600;
    display: block;
}
.hero-note small,
.photo-tag small {
    display: block;
    font-size: 10px;
    color: #9a91a3;
    margin-top: 2px;
}
.hero-visual {
    position: relative;
    padding: 0 15px 22px 0;
    min-width: 0;
}
.photo-frame {
    position: relative;
    aspect-ratio: 1/1.1;
    border-radius: 100px 26px 26px 26px;
    overflow: hidden;
    background: #eae4de;
}
.photo-frame > img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: 50% 66%;
}
.photo-frame::after {
    content: "";
    position: absolute;
    inset: 40% 0 0;
    background: linear-gradient(transparent, #171024bd);
}
.photo-caption {
    position: absolute;
    z-index: 1;
    bottom: 30px;
    left: 30px;
    color: #fff;
}
.photo-caption > span {
    font-size: 8px;
    letter-spacing: 1.5px;
    color: #e6d9f1;
}
.photo-caption p {
    font-size: 24px;
    line-height: 1.35;
    font-weight: 500;
    margin-top: 10px;
}
.photo-tag {
    position: absolute;
    bottom: 0;
    right: -10px;
    display: flex;
    gap: 12px;
    align-items: center;
    padding: 17px 21px;
    background: #fff;
    border: 1px solid #eee6f5;
    box-shadow: 0 14px 42px #30204612;
    border-radius: 16px;
    z-index: 2;
}
.photo-tag .icon-box {
    background: #edf8ed;
    color: #599466;
    border-radius: 50%;
    width: 37px;
    height: 37px;
}
.photo-tag small {
    font-size: 9px;
}
.visual-spark {
    position: absolute;
    top: -26px;
    right: -9px;
    font-size: 74px;
    line-height: 1;
    color: #bfa2f2;
    transform: rotate(12deg);
}
.features {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    padding: 30px 0;
    border-block: 1px solid #ece5f3;
    gap: 30px;
}
.features article {
    display: flex;
    gap: 16px;
    align-items: flex-start;
}
.item-number {
    font-size: 10px;
    letter-spacing: 1px;
    color: #b4a7c1;
}
.features h2 {
    font-size: 14px;
    font-weight: 600;
    margin: 4px 0;
}
.features p {
    font-size: 11px;
    line-height: 1.8;
    color: #93879f;
    max-width: 255px;
}
.section {
    padding-block: 88px;
}
.section h2 {
    font-size: clamp(26px, 2.6vw, 35px);
    line-height: 1.35;
    font-weight: 600;
    letter-spacing: -1px;
    margin-top: 13px;
}
.about-section {
    display: grid;
    grid-template-columns: 1fr 1.3fr;
    gap: 70px;
}
.about-section h2 span {
    color: #9a87b0;
}
.about-copy > p {
    color: #8a7e94;
    font-size: 13px;
    line-height: 1.9;
}
.purpose-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 28px;
    margin-top: 28px;
}
.purpose-grid article {
    border-top: 1px solid #e7dff0;
    padding-top: 21px;
}
.purpose-grid span {
    font-size: 9px;
    letter-spacing: 1.5px;
    color: #9a7cb8;
}
.purpose-grid h3 {
    font-size: 15px;
    font-weight: 600;
    margin: 10px 0;
}
.purpose-grid p {
    font-size: 11px;
    color: #94869f;
}
.categories-section {
    background: #f9f7fd;
}
.section-heading {
    display: flex;
    justify-content: space-between;
    align-items: end;
    gap: 30px;
    margin-bottom: 34px;
}
.section-heading > p {
    font-size: 12px;
    color: #91839e;
    max-width: 260px;
}
.category-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
}
.category-grid article {
    background: white;
    border: 1px solid #ebe4f3;
    border-radius: 18px;
    padding: 26px;
    position: relative;
}
.category-symbol {
    font-size: 29px;
    color: #9465cf;
    line-height: 1;
    display: block;
    height: 45px;
}
.category-grid .item-number {
    position: absolute;
    top: 27px;
    right: 25px;
}
.category-grid h3 {
    font-size: 17px;
    font-weight: 600;
    margin: 19px 0 8px;
}
.category-grid p {
    font-size: 11px;
    color: #9a8da5;
    line-height: 1.8;
}
.course-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 24px;
}
.course-card {
    border: 1px solid #eae3f1;
    border-radius: 20px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: box-shadow 0.2s;
}
.course-card:hover {
    box-shadow: 0 12px 32px #30134f0c;
}
.course-image {
    aspect-ratio: 1.7;
    background: #f4f0fa;
    overflow: hidden;
}
.course-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.course-image img.fallback-logo {
    object-fit: contain;
    padding: 45px;
}
.course-body {
    padding: 24px;
    display: flex;
    flex: 1;
    flex-direction: column;
    align-items: flex-start;
}
.course-body .eyebrow {
    font-size: 8px;
}
.course-body h3 {
    font-size: 17px;
    line-height: 1.5;
    font-weight: 600;
    margin: 9px 0;
}
.course-body p {
    font-size: 12px;
    color: #96889f;
    margin-bottom: 22px;
}
.course-body a {
    margin-top: auto;
}
.course-empty {
    display: flex;
    align-items: center;
    gap: 24px;
    padding: 36px;
    background: #faf8fd;
    border: 1px dashed #dacbea;
    border-radius: 20px;
}
.course-empty > div {
    flex: 1;
}
.course-empty h3 {
    font-size: 17px;
    font-weight: 600;
}
.course-empty p {
    font-size: 12px;
    color: #91829d;
    margin-top: 6px;
    max-width: 550px;
}
.course-skeleton {
    height: 315px;
    border-radius: 20px;
    background: linear-gradient(100deg, #f0eaf7, #faf8fd, #f0eaf7);
    background-size: 200% 100%;
    animation: shimmer 1.6s infinite;
}
.testimonial-section {
    background: #fcfaf7;
}
.testimonial-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 24px;
}
.testimonial-grid figure {
    margin: 0;
    display: flex;
    flex-direction: column;
    padding: 29px;
    background: #fff;
    border: 1px solid #eee6e2;
    border-radius: 19px;
}
.quote-mark {
    font-family: Georgia, "Times New Roman", serif;
    font-size: 64px;
    font-weight: 700;
    color: #bca1d8;
    height: 43px;
    line-height: 1;
}
.testimonial-grid blockquote {
    font-size: 13px;
    line-height: 1.9;
    color: #82778c;
    flex: 1;
    margin: 12px 0 26px;
}
.testimonial-grid figcaption {
    display: flex;
    align-items: center;
    gap: 12px;
    padding-top: 20px;
    border-top: 1px solid #f2edf5;
}
.avatar {
    display: grid;
    place-items: center;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #eee6fa;
    font-size: 10px;
    color: #8c68b0;
}
.avatar-1 {
    background: #e6f1ed;
    color: #679383;
}
.avatar-2 {
    background: #f9eadc;
    color: #b58a67;
}
.testimonial-grid strong {
    font-size: 11px;
    font-weight: 600;
}
.faq-section {
    display: grid;
    grid-template-columns: 1fr 1.4fr;
    gap: 80px;
}
.faq-section > div > p {
    font-size: 12px;
    color: #91849c;
    max-width: 280px;
    margin-top: 18px;
}
.faq-list details {
    border-bottom: 1px solid #e9e1f0;
    padding: 21px 0;
}
.faq-list details:first-child {
    padding-top: 0;
}
.faq-list summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    list-style: none;
}
.faq-list summary::-webkit-details-marker {
    display: none;
}
.faq-list summary > .pi {
    font-size: 16px;
    color: #9675b4;
    transition: transform 0.2s;
}
.faq-list details[open] summary > .pi {
    transform: rotate(45deg);
}
.faq-list details p {
    margin-top: 13px;
    color: #94879f;
    font-size: 12px;
    line-height: 1.9;
    padding-right: 30px;
}
.closing-section {
    padding-bottom: 80px;
}
.closing-card {
    position: relative;
    overflow: hidden;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 35px;
    border-radius: 26px;
    background: #241637;
    padding: 48px 52px;
    color: white;
}
.closing-card .eyebrow {
    color: #bba6d3;
    font-size: 9px;
}
.closing-card h2 {
    font-size: 34px;
    font-weight: 500;
    line-height: 1.4;
    margin-top: 15px;
    letter-spacing: -0.7px;
}
.closing-button {
    color: #39234e;
    background: #e3d6fc;
    z-index: 1;
}
.closing-button:hover {
    background: #f0e7ff;
}
.closing-orbit {
    position: absolute;
    width: 360px;
    height: 360px;
    border: 1px solid #ffffff0c;
    border-radius: 50%;
    right: 40px;
    top: -130px;
    box-shadow:
        0 0 0 50px #ffffff03,
        0 0 0 100px #ffffff03;
    pointer-events: none;
}
footer {
    background: #faf8fc;
    border-top: 1px solid #eee8f3;
    padding-top: 52px;
}
.footer-grid {
    display: grid;
    grid-template-columns: 1.3fr 0.8fr 1fr;
    gap: 65px;
    padding-bottom: 45px;
}
.footer-brand img {
    width: 160px;
    height: auto;
}
.footer-brand p {
    font-size: 12px;
    color: #998ca3;
    margin-top: 18px;
}
.footer-grid h2 {
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 16px;
}
.footer-grid > div > a {
    display: block;
    font-size: 11px;
    color: #96869f;
    margin-top: 9px;
    line-height: 1.9;
}
.footer-grid > div > a:hover {
    color: var(--lms-primary);
}
.footer-contact > a:last-child {
    margin-top: 18px;
}
.footer-bottom {
    border-top: 1px solid #eae3f0;
    padding-block: 20px;
    display: flex;
    justify-content: space-between;
    gap: 16px;
    font-size: 10px;
    color: #a094aa;
}
@keyframes shimmer {
    to {
        background-position: -200% 0;
    }
}
@media (max-width: 1050px) {
    .hero {
        gap: 35px;
    }
    .hero h1 {
        font-size: 38px;
    }
    .desktop-nav {
        gap: 20px;
    }
    .hero-copy > .eyebrow {
        font-size: 8px;
        letter-spacing: 0.8px;
    }
    .hero-actions {
        gap: 18px;
    }
    .photo-tag {
        padding: 13px;
    }
    .features {
        gap: 20px;
    }
    .about-section,
    .faq-section {
        gap: 40px;
    }
    .category-grid article {
        padding: 21px;
    }
    .course-empty {
        flex-wrap: wrap;
    }
    .course-empty .button {
        margin-left: 66px;
    }
    .section-heading > p {
        max-width: 220px;
    }
}
@media (max-width: 767px) {
    .container {
        width: calc(100% - 40px);
    }
    .header-inner {
        height: 76px;
        gap: 14px;
    }
    .brand img {
        width: 125px;
    }
    .header-actions .button {
        min-height: 40px;
        padding: 9px 14px;
        font-size: 11px;
        gap: 12px;
    }
    .desktop-nav {
        display: none;
    }
    .menu-toggle {
        display: block;
    }
    .mobile-nav {
        display: grid;
        padding-block: 5px 20px;
        gap: 3px;
    }
    .mobile-nav a {
        padding: 10px;
        color: #75617f;
        font-size: 13px;
    }
    .hero {
        grid-template-columns: 1fr;
        gap: 42px;
        padding-block: 43px 54px;
    }
    .hero h1 {
        font-size: 39px;
        letter-spacing: -1.6px;
        margin: 21px 0;
    }
    .desktop-break {
        display: none;
    }
    .hero-copy > .eyebrow {
        font-size: 8px;
        letter-spacing: 1px;
    }
    .hero-copy > p {
        font-size: 13px;
        max-width: 100%;
    }
    .hero-actions {
        margin-top: 25px;
        gap: 23px;
    }
    .hero-note {
        margin-top: 28px;
    }
    .hero-visual {
        max-width: 500px;
        width: 100%;
        margin: auto;
        padding-right: 12px;
    }
    .photo-frame {
        aspect-ratio: 1.12;
        border-top-left-radius: 70px;
    }
    .photo-caption {
        left: 25px;
        bottom: 65px;
    }
    .photo-caption p {
        font-size: 20px;
    }
    .photo-tag {
        right: 0;
        padding: 12px 15px;
    }
    .visual-spark {
        font-size: 60px;
        right: 0;
        top: -19px;
    }
    .features {
        grid-template-columns: 1fr;
        gap: 23px;
        padding-block: 27px;
    }
    .features p {
        max-width: 100%;
    }
    .section {
        padding-block: 55px;
    }
    .section h2 {
        font-size: 28px;
    }
    .about-section,
    .faq-section {
        grid-template-columns: 1fr;
        gap: 26px;
    }
    .purpose-grid {
        gap: 21px;
    }
    .purpose-grid h3 {
        font-size: 14px;
    }
    .section-heading {
        align-items: start;
        flex-direction: column;
        gap: 15px;
        margin-bottom: 25px;
    }
    .section-heading > p {
        max-width: 100%;
    }
    .category-grid {
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    .category-grid article {
        padding: 20px 17px;
    }
    .category-grid h3 {
        font-size: 15px;
    }
    .category-grid .item-number {
        right: 17px;
    }
    .course-grid,
    .testimonial-grid {
        grid-template-columns: 1fr;
    }
    .course-empty {
        padding: 24px;
        gap: 17px;
    }
    .course-empty > div {
        flex-basis: calc(100% - 65px);
    }
    .course-empty h3 {
        font-size: 15px;
    }
    .course-empty .button {
        margin-left: 0;
    }
    .testimonial-grid {
        gap: 15px;
    }
    .testimonial-grid figure {
        padding: 25px;
    }
    .faq-list summary {
        font-size: 13px;
    }
    .closing-section {
        padding-bottom: 50px;
    }
    .closing-card {
        padding: 33px 26px;
        flex-direction: column;
        align-items: start;
        gap: 27px;
        border-radius: 22px;
    }
    .closing-card h2 {
        font-size: 29px;
    }
    .closing-card .eyebrow {
        font-size: 8px;
    }
    .closing-button {
        font-size: 12px;
        gap: 18px;
    }
    .footer-grid {
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        padding-bottom: 30px;
    }
    .footer-brand {
        grid-column: 1/-1;
    }
    .footer-bottom {
        font-size: 9px;
        flex-direction: column;
        gap: 5px;
    }
    .footer-grid > div > a {
        overflow-wrap: anywhere;
    }
    .landing section[id] {
        scroll-margin-top: 90px;
    }
}
@media (prefers-reduced-motion: reduce) {
    .landing * {
        animation: none !important;
        transition: none !important;
    }
}
</style>
