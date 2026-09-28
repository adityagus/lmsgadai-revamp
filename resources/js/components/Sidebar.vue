<template>
    <div>
        <div
            v-if="sidebarOpen && isMobile"
            :class="[
                'fixed inset-0 bg-black bg-opacity-40 z-40 md:hidden transition-opacity duration-300',
            ]"
            @click="$emit('closeSidebar')"
        ></div>
        <aside
            v-if="sidebarOpen || !isMobile"
            :class="[
                'sidebar-container fixed h-[calc(100vh-20px)] w-full max-w-[280px] my-[10px] bg-[#060A23] overflow-hidden flex flex-col rounded-[20px] z-50 ml-[10px] transition-transform duration-300',
                sidebarOpen
                    ? 'translate-x-0 '
                    : '-translate-x-full md:translate-x-0',
                'shadow-2xl',
            ]"
        >
            <!-- Logo Fixed at the top -->
            <div class="flex justify-center p-[30px] pb-2 z-20">
                <router-link to="/" class="flex justify-center">
                    <img
                        src="/assets/images/logos/lms.png"
                        alt="logo"
                        class="transition duration-200 hover:scale-105"
                    />
                </router-link>
            </div>

            <div
                class="scroll-container flex w-full overflow-y-auto hide-scrollbar custom-scrollbar flex-1"
            >
                <nav
                    class="flex flex-col w-full h-fit px-[30px] pt-4 pb-[30px] gap-10 z-10"
                >
                    <ul class="flex flex-col gap-1">
                        <p
                            class="font-semibold text-xs leading-[18px] text-white"
                        >
                            UMUM
                        </p>
                        <li
                            v-if="auth && auth.role !== 'trainer'"
                            :class="{ active: $route.name === 'overview' }"
                            class="transition-all duration-200"
                        >
                            <div
                                @click="handleMainMenuClick('/overview')"
                                class="flex items-center gap-3 w-full py-3 px-5 rounded-xl transition-all duration-200 hover:bg-[#7F33FF33] cursor-pointer group"
                            >
                                <img
                                    src="/assets/images/icons/3dcube-white.svg"
                                    class="w-6 h-6"
                                    alt="icon"
                                />
                                <span
                                    class="font-semibold text-white group-hover:text-[#7F33FF] transition"
                                    >Ringkasan</span
                                >
                            </div>
                        </li>
                        <li
                            :class="{
                                active:
                                    $route.path === '/kelas' ||
                                    $route.path === '/student/kelas',
                            }"
                            class="transition-all duration-200"
                        >
                            <div
                                @click="handleMainMenuClick(quizManager ? '/kelas' : '/student/kelas')"
                                class="flex items-center gap-3 w-full py-3 px-5 rounded-lg transition-all duration-200 hover:bg-[#7F33FF33] cursor-pointer group"
                            >
                                <img
                                    src="/assets/images/icons/note-favorite-white.svg"
                                    class="w-6 h-6"
                                    alt="icon"
                                />
                                <span
                                    class="font-semibold text-white group-hover:text-[#7F33FF] transition"
                                    >Pembelajaran</span
                                >
                            </div>
                        </li>
                        <li v-if="auth && auth.role !== 'trainer'" class="transition-all duration-200">
                            <div
                                @click="handleParentClick('info')"
                                :class="[
                                    'flex items-center gap-3 w-full py-3 px-5 rounded-lg transition-all duration-200 cursor-pointer group',
                                    openMenu === 'info' ? 'active' : '',
                                    'hover:bg-[#7F33FF33]',
                                ]"
                            >
                                <img
                                    src="/assets/images/icons/crown-white.svg"
                                    class=""
                                    alt="icon"
                                />
                                <span
                                    class="font-semibold text-white group-hover:text-[#7F33FF] transition"
                                    >Informasi</span
                                >
                                <svg
                                    width="24"
                                    height="24"
                                    :class="[
                                        'ml-auto transition-transform w-7',
                                        openMenu === 'info' ? 'rotate-90' : '',
                                    ]"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path
                                        d="M9 5L16 12L9 19"
                                        stroke-width="2"
                                        stroke="white"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>

                                <!-- <svg :class="['ml-auto transition-transform w-7', openMenu === 'info' ? 'rotate-90' : '']" width="25" height="25"
                  fill="white" stroke="currentColor" stroke-width="2">
                  <path d="M6 9l6-3-6-3" />
                </svg> -->
                            </div>
                            <transition name="fade">
                                <ul
                                    v-if="openMenu === 'info'"
                                    class="pl-12 mt-2 space-y-2"
                                >
                                    <li
                                        :class="{
                                            active: activeSub === '/pengumuman',
                                        }"
                                        class="transition-all duration-200"
                                    >
                                        <div
                                            @click.prevent="
                                                handleSubClick('/pengumuman')
                                            "
                                            href="#"
                                            class="text-white hover:text-[#7F33FF] px-5 py-2 rounded-lg cursor-pointer transition-all duration-200"
                                        >
                                            <span
                                                class="font-semibold text-white group-hover:text-[#7F33FF] transition"
                                                >Pengumuman</span
                                            >
                                        </div>
                                    </li>
                                    <li
                                        :class="{
                                            active: activeSub === '/formulir',
                                        }"
                                        class="transition-all duration-200"
                                    >
                                        <div
                                            @click.prevent="
                                                handleSubClick('/formulir')
                                            "
                                            href="#"
                                            class="text-white hover:text-[#7F33FF] px-5 py-2 rounded-lg cursor-pointer transition-all duration-200"
                                        >
                                            <span
                                                class="font-semibold text-white group-hover:text-[#7F33FF] transition"
                                                >Formulir</span
                                            >
                                        </div>
                                    </li>
                                </ul>
                            </transition>
                        </li>
                    </ul>
                    <!-- IT SPV,  -->
                    <ul
                        class="flex flex-col gap-4"
                        v-if="
                            quizManager || auth &&
                            (auth.idgrup === 'JBT-032' ||
                                auth.idgrup === 'JBT-038')
                        "
                    >
                        <p
                            class="font-semibold text-xs leading-[18px] text-white"
                        >
                            Master
                        </p>
                        <li
                            v-if="auth && (auth.role === 'trainer' || ['JBT-032', 'JBT-038'].includes(auth.idgrup))"
                            :class="{
                                active: $route.path === '/master/categories',
                            }"
                            class="transition-all duration-200"
                        >
                            <div
                                @click="
                                    handleMainMenuClick('/master/categories')
                                "
                                class="flex items-center gap-3 w-full py-3 px-5 rounded-lg transition-all duration-200 hover:bg-[#7F33FF33] cursor-pointer group"
                            >
                                <img
                                    src="/assets/images/icons/crown-white.svg"
                                    class="w-6 h-6"
                                    alt="icon"
                                />
                                <span
                                    class="font-semibold text-white group-hover:text-[#7F33FF] transition"
                                >
                                    Kategori
                                </span>
                            </div>
                        </li>
                        <li v-if="quizManager">
                            <router-link
                                to="/quiz/manage"
                                class="flex items-center gap-3 py-3 px-5 rounded-lg text-white font-semibold hover:bg-[#7F33FF33]"
                                @click="$emit('closeSidebar')"
                                ><img
                                    src="/assets/images/icons/bank-quiz.svg"
                                    class="w-6 h-6 shrink-0"
                                    alt=""
                                />Bank Soal & Peserta</router-link
                            >
                        </li>
                    </ul>
                    <ul
                        class="flex flex-col gap-4"
                        v-if="
                            auth &&
                            (auth.role === 'trainer' || auth.idgrup === 'JBT-032' ||
                                auth.idgrup === 'JBT-037' ||
                                auth.idgrup === 'JBT-019' ||
                                auth.idgrup === 'JBT-020' ||
                                auth.idgrup === '042')
                        "
                    >
                        <p
                            class="font-semibold text-xs leading-[18px] text-white"
                        >
                            Laporan
                        </p>
                        <li
                            :class="{
                                active:
                                    $route.path === '/report/documents' ||
                                    $route.path === '/report',
                            }"
                            class="transition-all duration-200"
                        >
                            <div
                                @click="handleParentClick('report')"
                                :class="['flex items-center gap-3 w-full py-3 px-5 rounded-lg transition-all duration-200 hover:bg-[#7F33FF33] cursor-pointer group', activeSub === 'info' ? 'bg-[#7F33FF33]' : '']"
                            >
                                <img
                                    src="/assets/images/icons/report.svg"
                                    class="w-5 h-5"
                                    alt="icon"
                                />
                                <span
                                    class="font-semibold text-white group-hover:text-[#7F33FF] transition"
                                >
                                    Dokumen
                                </span>
                                <svg
                                    width="24"
                                    height="24"
                                    :class="[
                                        'ml-auto transition-transform w-7',
                                        openMenu === 'report' ? 'rotate-90' : '',
                                    ]"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path
                                        d="M9 5L16 12L9 19"
                                        stroke-width="2"
                                        stroke="white"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </div>
                                                        <transition name="fade">
                                <ul
                                    v-if="openMenu === 'report'"
                                    class="pl-12 mt-2 space-y-2"
                                >
                                    <li
                                        :class="{
                                            active: activeSub === '/pengumuman',
                                        }"
                                        class="transition-all duration-200"
                                    >
                                        <div
                                            @click.prevent="
                                                handleSubClick('/pengumuman')
                                            "
                                            href="#"
                                            class="text-white hover:text-[#7F33FF] px-5 py-2 rounded-lg cursor-pointer transition-all duration-200"
                                        >
                                            <span
                                                class="font-semibold text-white group-hover:text-[#7F33FF] transition"
                                                >Pengumuman</span
                                            >
                                        </div>
                                    </li>
                                    <li
                                        :class="{
                                            active: activeSub === '/formulir',
                                        }"
                                        class="transition-all duration-200"
                                    >
                                        <div
                                            @click.prevent="
                                                handleSubClick('/formulir')
                                            "
                                            href="#"
                                            class="text-white hover:text-[#7F33FF] px-5 py-2 rounded-lg cursor-pointer transition-all duration-200"
                                        >
                                            <span
                                                class="font-semibold text-white group-hover:text-[#7F33FF] transition"
                                                >Formulir</span
                                            >
                                        </div>
                                    </li>
                                </ul>
                            </transition>
                        </li>
                        <li
                            :class="{
                                active: $route.path === '/master/audit-logs',
                            }"
                            class="transition-all duration-200"
                        >
                            <div
                                @click="
                                    handleMainMenuClick('/master/audit-logs')
                                "
                                class="flex items-center gap-3 w-full py-3 px-5 rounded-lg transition-all duration-200 hover:bg-[#7F33FF33] cursor-pointer group"
                            >
                                <img
                                    src="/assets/images/icons/log-document.png"
                                    class="w-6 h-6"
                                    alt="icon"
                                />
                                <span
                                    class="font-semibold text-white group-hover:text-[#7F33FF] transition"
                                >
                                    Aktivitas Log
                                </span>
                            </div>
                        </li>
                    </ul>
                </nav>
            </div>
            <img
                src="/assets/images/backgrounds/sidebar-glow.png"
                class="absolute object-contain object-bottom bottom-0"
                alt="background"
            />
        </aside>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from "vue";
import { usePage } from "@inertiajs/vue3";
import { useRoute, useRouter } from "@/navigation";

const props = defineProps({
    sidebarOpen: Boolean,
});
const emit = defineEmits(["closeSidebar"]);

const route = useRoute();
const router = useRouter();
const openMenu = ref("");
const activeSub = ref(route.path);
const page = usePage();
const auth = computed(() => page.props.auth);
const quizManager = computed(() => ["admin", "trainer", "teacher"].includes(auth.value?.role));
const isMobile = ref(window.innerWidth < 768);

const handleResize = () => {
    isMobile.value = window.innerWidth < 768;
    if (!isMobile.value) {
        emit("closeSidebar");
    }
};

// Klik menu utama seperti "Overview" atau "LMS"
const handleMainMenuClick = async (mainPath) => {
    openMenu.value = "";
    activeSub.value = mainPath;
    // Tutup sidebar lebih dulu, lalu navigasi
    emit("closeSidebar");
    await nextTick();
    router.push(mainPath);
};

// Klik menu dengan sub-menu seperti "Informasi"
const handleParentClick = (menu) => {
    if (openMenu.value !== menu) {
        activeSub.value = "";
    }
    openMenu.value = openMenu.value === menu ? "" : menu;
};

// Klik sub-menu seperti "Pengumuman" atau "Formulir"
const handleSubClick = async (subPath) => {
    activeSub.value = subPath;
    if (openMenu.value !== "info") {
        openMenu.value = "info";
    }
    emit("closeSidebar");
    await nextTick();
    router.push(subPath);
};

onMounted(async () => {
    window.addEventListener("resize", handleResize);
    const infoSubs = [
        "/pengumuman",
        "/overview/subitem2",
        "/overview/subitem3",
    ];
    if (infoSubs.includes(route.path)) {
        openMenu.value = "info";
        activeSub.value = route.path;
    }
});

watch(() => route.path, path => {
    activeSub.value = path;
    if (['/pengumuman', '/formulir'].includes(path)) openMenu.value = 'info';
});
onUnmounted(() => window.removeEventListener('resize', handleResize));
</script>

<style>
.active {
    background-color: #7f33ff !important;
    color: white !important;
    border-radius: 10px !important;
    box-shadow: 0 2px 8px #7f33ff44;
}

/* Pastikan menu aktif tetap jelas saat hover */
.active:hover,
.active:focus {
    background-color: #7f33ff !important;
    color: white !important;
    filter: none !important;
    opacity: 1 !important;
}

/* Untuk span di dalam menu aktif agar tidak berubah warna saat hover */
.active .group-hover\:text-\[\#7F33FF\] {
    color: white !important;
}

/* Untuk sub menu aktif juga tetap jelas saat hover */
li.active > div,
li.active > div:hover,
li.active > div:focus {
    background-color: #7f33ff !important;
    color: white !important;
    filter: none !important;
    opacity: 1 !important;
}

li.active > div > span,
li.active > div:hover > span,
li.active > div:focus > span {
    color: white !important;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #7f33ff55;
    border-radius: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.hide-scrollbar::-webkit-scrollbar {
    display: none;
}

@media (min-width: 768px) {
    .sidebar-container {
        transform: translateX(0) !important;
    }
}
</style>
