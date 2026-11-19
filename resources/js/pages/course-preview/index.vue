<script setup>
import Header from "@/components/Header.vue";
import ContentVideo from "./ContentVideo.vue";
import ContentText from "./ContentText.vue";
import ContentDocument from "./ContentDocument.vue";
import ContentForm from "./ContentForm.vue";
import ContentQuiz from "./ContentQuiz.vue";
import { courseContentType } from "@/utils/courseContentType.mjs";
import { ref, onMounted, onUnmounted, computed, watch } from "vue";
import { useRoute, useRouter, onBeforeRouteLeave } from "@/navigation";
import { usePage } from "@inertiajs/vue3";
import { getCourseById } from "@/services/queries/courseQuery.ts";
import Swal from "sweetalert2";
import { getCourseProgress } from "@/services/queries/courseProgressQuery.ts";
import { completeCourseContent } from "@/services/mutations/courseProgressMutation.ts";

const route = useRoute();
const router = useRouter();

// Reactive data
const course = ref({});
const contents = ref([]);
const loading = ref(true);
const error = ref(null);
const currentContentIndex = ref(0);
const progress = ref({
    items: [],
    completed_count: 0,
    total: 0,
    is_complete: false,
});
const savingProgress = ref(false);
const progressPercent = computed(() =>
    progress.value.total
        ? Math.round(
              (progress.value.completed_count / progress.value.total) * 100,
          )
        : 0,
);
const completedAt = (id) =>
    progress.value.items.find((item) => String(item.content_id) === String(id))
        ?.completed_at;
const completedLabel = (id) =>
    `Selesai: ${new Date(completedAt(id)).toLocaleString("id-ID")}`;
const isContentCompleted = (id) => Boolean(completedAt(id));
const nextIncompleteIndex = computed(() =>
    contents.value.findIndex((item) => item.type !== 'parent' && !isContentCompleted(item.id)),
);
const page = usePage();
const isManager = computed(() => {
    const role = page.props.auth?.role;
    const roles = page.props.auth?.roles || [];
    const idgrup = page.props.auth?.idgrup;
    return ['admin', 'trainer', 'teacher'].includes(role) ||
        roles.some(r => ['admin', 'trainer', 'teacher'].includes(r)) ||
        ['JBT-032', 'JBT-038', 'JBT-019', '042'].includes(idgrup);
});

const canOpenContent = (index) => {
    const target = contents.value[index];
    if (!target || target.type === 'parent') return false;
    return (
        index >= 0 &&
        index < contents.value.length &&
        (isManager.value ||
            route.name === "content-admin-preview" ||
            index === nextIncompleteIndex.value ||
            isContentCompleted(contents.value[index]?.id))
    );
};
const contentSections = computed(() => {
    if (!contents.value.length) return [];

    const explicitParents = contents.value.filter(c => c.type === 'parent');
    const childContents = contents.value.filter(c => c.type !== 'parent');
    const hasAnyParent = explicitParents.length > 0 || childContents.some(c => c.parent_id);

    if (!hasAnyParent) {
        return [
            {
                id: 'default',
                title: 'Materi Pembelajaran',
                items: childContents.map((c) => ({
                    ...c,
                    originalIndex: contents.value.findIndex(item => item.id === c.id),
                })),
            }
        ];
    }

    const parentMap = new Map();
    explicitParents.forEach(p => {
        parentMap.set(String(p.id), {
            id: String(p.id),
            title: p.title,
            items: [],
        });
    });

    const unassignedItems = [];

    childContents.forEach((c) => {
        const originalIdx = contents.value.findIndex(item => item.id === c.id);
        const itemWithIdx = { ...c, originalIndex: originalIdx };
        const pId = c.parent_id ? String(c.parent_id) : null;

        if (pId && parentMap.has(pId)) {
            parentMap.get(pId).items.push(itemWithIdx);
        } else if (pId) {
            const parentObj = contents.value.find(item => String(item.id) === pId);
            if (parentObj) {
                if (!parentMap.has(pId)) {
                    parentMap.set(pId, {
                        id: pId,
                        title: parentObj.title,
                        items: [],
                    });
                }
                parentMap.get(pId).items.push(itemWithIdx);
            } else {
                unassignedItems.push(itemWithIdx);
            }
        } else {
            unassignedItems.push(itemWithIdx);
        }
    });

    const sections = Array.from(parentMap.values());
    if (unassignedItems.length > 0) {
        sections.unshift({
            id: 'unassigned',
            title: sections.length > 0 ? 'Materi Utama' : 'Materi Pembelajaran',
            items: unassignedItems,
        });
    }

    return sections;
});

const openSections = ref(new Set());

function toggleSection(id) {
    if (openSections.value.has(id)) {
        openSections.value.delete(id);
    } else {
        openSections.value.add(id);
    }
}

function isSectionOpen(id) {
    return openSections.value.has(id);
}

const sectionCompletedCount = (sec) => {
    return sec.items.filter(item => isContentCompleted(item.id)).length;
};

watch(
    currentContentIndex,
    (idx) => {
        const activeItem = contents.value[idx];
        if (!activeItem) return;
        const activeSec = contentSections.value.find(s => s.items.some(item => item.id === activeItem.id));
        if (activeSec) {
            openSections.value.add(activeSec.id);
        }
    },
    { immediate: true }
);

watch(
    contentSections,
    (secs) => {
        if (secs.length > 0 && openSections.value.size === 0) {
            secs.forEach(s => openSections.value.add(s.id));
        }
    },
    { immediate: true }
);

const isCurrentFormSubmitted = ref(false);
const isMobileSidebarOpen = ref(false);

// Computed properties
const courseId = computed(() => {
    const fromRoute = route.params.id || route.params.courseId;
    if (fromRoute) return fromRoute;
    const match = window.location.pathname.match(
        /(?:student\/kelas|course)\/(\d+)/,
    );
    return match?.[1] || null;
});
const currentContent = computed(() => {
    const item = contents.value[currentContentIndex.value];
    if (item && item.type !== 'parent') return item;
    const firstPlayable = contents.value.find(c => c.type !== 'parent');
    return firstPlayable || item || {};
});
const isLastContent = computed(
    () => currentContentIndex.value === contents.value.length - 1,
);

const isFormContent = (type) => {
    return courseContentType(currentContent.value) === "google_form";
};

const handleFormSubmittedChange = (val) => {
    isCurrentFormSubmitted.value = val;
};

// Check if form is submitted before internal navigation
const checkFormSubmitBeforeNavigate = (onProceed) => {
    if (
        isFormContent(currentContent.value?.type) &&
        !isCurrentFormSubmitted.value
    ) {
        Swal.fire({
            title: "Formulir Belum Di-submit!",
            html: 'Anda wajib mengisi dan mengirimkan (submit) Google Form terlebih dahulu sebelum melanjutkan ke materi berikutnya.<br><br><small class="text-gray-500">Jika Anda sudah menekan tombol Kirim di dalam form atau membukanya di tab baru, klik <b>Saya Sudah Submit & Lanjutkan</b>.</small>',
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#662FFF",
            cancelButtonColor: "#838C9D",
            confirmButtonText: "Lewati",
            cancelButtonText: "Kembali ke Form",
        }).then((result) => {
            if (result.isConfirmed) {
                isCurrentFormSubmitted.value = true;
                onProceed();
            }
        });
    } else {
        onProceed();
    }
};

// Route leave guard
onBeforeRouteLeave((to, from, next) => {
    if (
        isFormContent(currentContent.value?.type) &&
        !isCurrentFormSubmitted.value
    ) {
        Swal.fire({
            title: "Formulir Belum Di-submit!",
            html: 'Anda wajib mengisi dan mengirimkan (submit) Google Form terlebih dahulu sebelum melanjutkan ke materi berikutnya.<br><br><small class="text-gray-500">Jika Anda sudah menekan tombol Kirim di dalam form atau membukanya di tab baru, klik <b>Saya Sudah Submit & Lanjutkan</b>.</small>',
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#662FFF",
            cancelButtonColor: "#838C9D",
            confirmButtonText: "Lewati",
            cancelButtonText: "Kembali ke Form",
        }).then((result) => {
            if (result.isConfirmed) {
                isCurrentFormSubmitted.value = true;
                next();
            } else {
                next(false);
            }
        });
    } else {
        next();
    }
});

// Methods
let courseRequest = 0;
const fetchCourseData = async () => {
    const request = ++courseRequest;
    error.value = null;
    try {
        loading.value = true;
        if (!courseId.value) throw new Error("Kelas tidak ditemukan.");

        const [response, savedProgress] = await Promise.all([
            getCourseById(courseId.value),
            getCourseProgress(courseId.value),
        ]);
        if (request !== courseRequest) return;

        if (response.success || response.course) {
            const courseData = response.course || response.data;
            const contentsData = response.contents || courseData.contents || [];

            course.value = courseData;
            contents.value = Array.isArray(contentsData)
                ? contentsData
                : contentsData.data || [];
            const requested = contents.value.findIndex(
                (item) => String(item.id) === String(route.query.content),
            );
            progress.value = savedProgress;
            const resume = contents.value.findIndex(
                (item) => item.type !== 'parent' && !completedAt(item.id),
            );
            const firstPlayable = contents.value.findIndex(
                (item) => item.type !== 'parent',
            );

            let targetIndex = 0;
            if (route.name === "content-admin-preview" && requested >= 0 && contents.value[requested]?.type !== 'parent') {
                targetIndex = requested;
            } else if (resume >= 0) {
                targetIndex = resume;
            } else if (firstPlayable >= 0) {
                targetIndex = firstPlayable;
            }
            currentContentIndex.value = targetIndex;
            isCurrentFormSubmitted.value = false;
        } else {
            throw new Error(response.message || "Failed to fetch course data");
        }
    } catch (err) {
        if (request !== courseRequest) return;
        error.value = err.message || "Failed to load course data";
    } finally {
        if (request === courseRequest) loading.value = false;
    }
};

const goToContent = (index) => {
    if (index >= 0 && index < contents.value.length && canOpenContent(index)) {
        if (index === currentContentIndex.value) {
            isMobileSidebarOpen.value = false;
            return;
        }
        checkFormSubmitBeforeNavigate(() => {
            isCurrentFormSubmitted.value = false;
            currentContentIndex.value = index;
            isMobileSidebarOpen.value = false;
        });
    }
};

const markAsCompleted = () => {
    if (savingProgress.value || !currentContent.value.id) return;
    checkFormSubmitBeforeNavigate(async () => {
        if (savingProgress.value) return;
        savingProgress.value = true;
        const request = courseRequest;
        try {
            const saved = await completeCourseContent(
                courseId.value,
                currentContent.value.id,
            );
            if (request !== courseRequest) return;
            progress.value = saved;
            if (saved.is_complete) {
                isCurrentFormSubmitted.value = false;
                if (route.name !== "content-admin-preview") {
                    router.push(`/student/kelas/${courseId.value}/selesai`);
                } else {
                    Swal.fire({
                        title: "Selamat!",
                        text: "Seluruh materi kelas telah selesai. Progres Anda tersimpan.",
                        icon: "success",
                        confirmButtonColor: "#662FFF",
                    });
                }
            } else {
                isCurrentFormSubmitted.value = false;
                const next = contents.value.findIndex(
                    (item) => item.type !== 'parent' && !completedAt(item.id),
                );
                if (next >= 0) {
                    currentContentIndex.value = next;
                }
            }
        } catch (err) {
            if (request !== courseRequest) return;
            Swal.fire({
                title: "Progres belum tersimpan",
                text:
                    err.response?.data?.message ||
                    "Silakan coba lagi. Anda tetap berada di materi ini.",
                icon: "error",
                confirmButtonColor: "#662FFF",
            });
        } finally {
            savingProgress.value = false;
        }
    });
};

const getContentIcon = (type) => {
    switch (type) {
        case "video":
            return "/assets/images/icons/video-play-white.svg";
        case "text":
            return "/assets/images/icons/note-white.svg";
        case "pdf":
        case "dokumen":
            return "/assets/images/icons/pdf-white.svg";
        case "form":
        case "google_form":
        case "quiz":
            return "/assets/images/icons/quiz-white.svg";
        case "finished":
            return "";
        default:
            return "/assets/images/icons/note-white.svg";
    }
};

const goBack = () => {
    checkFormSubmitBeforeNavigate(() => {
        router.push(
            route.name === "content-admin-preview"
                ? { name: "courseDetail", params: { id: courseId.value } }
                : { name: "studentCourses" },
        );
    });
};

const handleBeforeUnload = (e) => {
    if (
        isFormContent(currentContent.value?.type) &&
        !isCurrentFormSubmitted.value
    ) {
        e.preventDefault();
        e.returnValue =
            "Formulir belum di-submit. Yakin ingin meninggalkan halaman ini?";
    }
};

// Lifecycle
onMounted(() => {
    window.addEventListener("beforeunload", handleBeforeUnload);
    fetchCourseData();
});

onUnmounted(() => {
    courseRequest++;
    window.removeEventListener("beforeunload", handleBeforeUnload);
});

watch(courseId, (newId, oldId) => {
    if (newId !== oldId) {
        fetchCourseData();
    }
});

watch(
    contents,
    (newContents) => {
        if (newContents.length > 0) {
            const currentItem = newContents[currentContentIndex.value];
            if (!currentItem || currentItem.type === 'parent') {
                const firstPlayable = newContents.findIndex(c => c.type !== 'parent');
                if (firstPlayable >= 0) {
                    currentContentIndex.value = firstPlayable;
                }
            }
        }
    },
    { immediate: true },
);
</script>

<template>
    <div class="flex flex-col lg:flex-row min-h-screen relative">
        <!-- Loading State -->
        <div
            v-if="loading"
            class="flex w-full min-h-screen gap-2 p-4"
            role="status"
            aria-label="Memuat materi kelas"
            aria-busy="true"
        >
            <aside
                class="hidden lg:block w-[300px] shrink-0 rounded-[20px] bg-[#060A23] p-6 space-y-5"
                aria-hidden="true"
            >
                <div
                    class="h-6 w-3/4 rounded bg-white/20 motion-safe:animate-pulse"
                ></div>
                <div
                    v-for="n in 6"
                    :key="n"
                    class="h-12 rounded-xl bg-white/10 motion-safe:animate-pulse"
                ></div>
            </aside>
            <div class="flex-1 space-y-6 py-4" aria-hidden="true">
                <div
                    class="h-5 w-28 rounded bg-gray-200 motion-safe:animate-pulse"
                ></div>
                <div
                    class="h-8 w-2/3 rounded bg-gray-200 motion-safe:animate-pulse"
                ></div>
                <div
                    class="w-full aspect-video rounded-2xl bg-gray-100 motion-safe:animate-pulse"
                ></div>
                <div
                    class="h-4 w-1/2 rounded bg-gray-200 motion-safe:animate-pulse"
                ></div>
            </div>
            <span class="sr-only">Memuat materi kelas...</span>
        </div>

        <!-- Error State -->
        <div
            v-else-if="error"
            class="flex items-center justify-center w-full h-screen"
        >
            <div class="text-center">
                <h2 class="text-2xl font-bold text-red-600 mb-4">
                    Error Loading Course
                </h2>
                <p class="text-gray-600 mb-4">{{ error }}</p>
                <button
                    @click="fetchCourseData"
                    class="px-4 py-2 bg-[#662FFF] text-white rounded-lg hover:bg-[#5521cc] transition-colors"
                >
                    Try Again
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <template v-else>
            <!-- Mobile Drawer Backdrop -->
            <div
                v-if="isMobileSidebarOpen"
                @click="isMobileSidebarOpen = false"
                class="fixed inset-0 bg-black/60 backdrop-blur-xs z-40 lg:hidden transition-opacity"
            ></div>

            <!-- Sidebar Container (Responsive: Fixed Drawer on Mobile, Fixed Sidebar on Desktop) -->
            <aside
                :class="[
                    'sidebar-container fixed z-50 h-[calc(100vh-20px)] w-[300px] sm:w-[330px] my-[10px] bg-[#060A23] overflow-hidden flex flex-col rounded-[20px] transition-all duration-300 shadow-2xl lg:shadow-none',
                    isMobileSidebarOpen
                        ? 'left-[10px]'
                        : '-left-[360px] lg:left-[10px]',
                ]"
            >
                <div
                    class="scroll-container flex w-full overflow-y-scroll hide-scrollbar flex-1"
                >
                    <nav
                        class="flex flex-col w-full h-fit p-5 sm:p-[30px] gap-5 sm:gap-[30px] z-10"
                    >
                        <div class="flex items-center justify-between w-full">
                            <button
                                @click="goBack"
                                class="font-semibold text-white hover:underline text-left text-xs sm:text-sm"
                            >
                                <span
                                    ><i class="pi pi-arrow-left"></i> Kembali ke
                                    Kelas</span
                                >
                            </button>
                            <button
                                @click="isMobileSidebarOpen = false"
                                class="lg:hidden text-gray-400 hover:text-white p-1 text-xl font-bold"
                            >
                                &times;
                            </button>
                        </div>

                        <!-- Course Info -->
                        <div class="flex flex-col gap-3 sm:gap-4">
                            <div
                                class="flex shrink-0 w-[110px] sm:w-[130px] h-[80px] sm:h-[100px] rounded-[14px] bg-[#D9D9D9] overflow-hidden"
                            >
                                <img
                                    :src="
                                        course.thumbnail_url ||
                                        course.thumbnail ||
                                        '/assets/images/thumbnails/th-1.png'
                                    "
                                    class="w-full h-full object-cover"
                                    :alt="course.name || 'Course Thumbnail'"
                                />
                            </div>
                            <h2
                                class="font-bold text-lg sm:text-xl leading-snug sm:leading-[34px] text-white"
                            >
                                {{ course.name || "Course Title" }}
                            </h2>
                        </div>

                        <!-- Course Contents (Accordion per Bab/Parent) -->
                        <div class="flex flex-col gap-3" v-if="contents.length > 0">
                            <div
                                v-for="section in contentSections"
                                :key="section.id"
                                class="rounded-[18px] border border-[#24283E] bg-[#070B24] overflow-hidden transition-all duration-300"
                            >
                                <!-- Accordion Header -->
                                <button
                                    type="button"
                                    @click="toggleSection(section.id)"
                                    class="flex items-center justify-between w-full p-3 sm:p-3.5 text-left font-semibold text-white hover:bg-white/5 transition-colors gap-2 cursor-pointer"
                                >
                                    <div class="flex items-center gap-2 min-w-0">
                                        <i
                                            class="pi text-xs text-[#7F33FF] shrink-0 transition-transform duration-200"
                                            :class="isSectionOpen(section.id) ? 'pi-chevron-down' : 'pi-chevron-right'"
                                        ></i>
                                        <span class="text-xs sm:text-sm font-bold truncate text-white">
                                            {{ section.title }}
                                        </span>
                                    </div>
                                    <span class="text-[11px] font-semibold text-purple-300 bg-white/10 px-2.5 py-0.5 rounded-full shrink-0">
                                        {{ sectionCompletedCount(section) }}/{{ section.items.length }}
                                    </span>
                                </button>

                                <!-- Accordion Items -->
                                <div
                                    v-show="isSectionOpen(section.id)"
                                    class="px-2.5 pb-2.5 pt-1 border-t border-[#181A35] flex flex-col gap-2"
                                >
                                    <div
                                        v-for="content in section.items"
                                        :key="content.id"
                                    >
                                        <button
                                            @click="goToContent(content.originalIndex)"
                                            :disabled="!canOpenContent(content.originalIndex)"
                                            class="w-full text-left disabled:cursor-not-allowed cursor-pointer"
                                            :class="{
                                                'opacity-50': !canOpenContent(content.originalIndex),
                                            }"
                                            :title="
                                                canOpenContent(content.originalIndex)
                                                    ? content.title
                                                    : 'Perpindahan materi hanya melalui penyelesaian langkah aktif'
                                            "
                                            :aria-current="
                                                content.originalIndex === currentContentIndex
                                                    ? 'step'
                                                    : undefined
                                            "
                                        >
                                            <div
                                                class="flex items-center gap-2.5 w-full rounded-xl border p-2.5 sm:p-3 transition-all duration-300 hover:bg-[#662FFF] hover:border-[#8661EE] hover:shadow-[-10px_-6px_10px_0_#7F33FF_inset]"
                                                :class="{
                                                    'bg-[#662FFF] border-[#8661EE] shadow-[-10px_-6px_10px_0_#7F33FF_inset]':
                                                        content.originalIndex === currentContentIndex,
                                                    'bg-[#0C1033] border-[#24283E]':
                                                        content.originalIndex !== currentContentIndex,
                                                }"
                                            >
                                                <img
                                                    :src="getContentIcon(content.type)"
                                                    class="w-4 h-4 sm:w-5 sm:h-5 shrink-0"
                                                    alt="icon"
                                                />
                                                <span
                                                    class="w-full font-medium text-white text-xs line-clamp-1 transition-all duration-300 hover:line-clamp-none"
                                                >
                                                    {{ content.title }}
                                                </span>
                                                <span
                                                    v-if="!canOpenContent(content.originalIndex)"
                                                    class="shrink-0 text-white text-xs"
                                                    aria-label="Terkunci"
                                                >🔒</span>
                                                <span
                                                    v-if="completedAt(content.id)"
                                                    class="shrink-0 text-green-300 font-bold"
                                                    :title="completedLabel(content.id)"
                                                    aria-label="Materi selesai"
                                                >
                                                    <img
                                                        src="/assets/images/icons/checklist.svg"
                                                        alt="check"
                                                        width="16px"
                                                    />
                                                </span>
                                            </div>
                                        </button>
                                    </div>
                                    <p
                                        v-if="!section.items.length"
                                        class="text-xs text-gray-500 italic p-2 text-center"
                                    >
                                        Belum ada materi di bab ini.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-8">
                            <p class="text-gray-400 text-sm">
                                No content available for this course
                            </p>
                        </div>
                    </nav>
                </div>
                <img
                    src="/assets/images/backgrounds/sidebar-glow.png"
                    class="absolute object-contain object-bottom bottom-0 pointer-events-none"
                    alt="background"
                />
            </aside>

            <!-- Main Content Area (Responsive margins and paddings) -->
            <main
                class="flex flex-col flex-1 gap-4 sm:gap-[30px] p-3 sm:p-6 lg:p-[30px] ml-0 lg:ml-[340px] mb-28 lg:mb-12 min-w-0"
            >
                <Header />

                <!-- Mobile Header Bar for Course Navigation -->
                <div
                    class="lg:hidden flex items-center justify-between bg-[#060A23] text-white p-3.5 sm:p-4 rounded-2xl shadow-lg border border-gray-800"
                >
                    <div class="flex items-center gap-3 overflow-hidden">
                        <img
                            :src="
                                course.thumbnail_url ||
                                course.thumbnail ||
                                '/assets/images/thumbnails/th-1.png'
                            "
                            class="w-10 h-10 rounded-xl object-cover shrink-0"
                        />
                        <div class="flex flex-col min-w-0">
                            <span
                                class="text-[10px] text-purple-300 font-medium uppercase tracking-wider"
                                >Materi {{ currentContentIndex + 1 }} dari
                                {{ contents.length }}</span
                            >
                            <span
                                class="font-bold text-xs sm:text-sm truncate text-white"
                                >{{ currentContent.title || course.name }}</span
                            >
                        </div>
                    </div>
                    <button
                        @click="isMobileSidebarOpen = true"
                        class="px-3 py-2 bg-[#662FFF] hover:bg-[#5521cc] text-white rounded-xl text-xs font-semibold flex items-center gap-1.5 shrink-0 shadow-md transition active:scale-95"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-4 h-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                        </svg>
                        <span>Daftar Materi</span>
                    </button>
                </div>

                <div
                    class="relative flex flex-col gap-4 sm:gap-6"
                    v-if="currentContent.id"
                >
                    <!-- Top Back & Context Bar -->
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <button
                            type="button"
                            @click="goBack"
                            class="inline-flex items-center gap-2 rounded-full border border-[#CFDBEF] bg-white hover:bg-gray-50 px-4 py-2 text-xs sm:text-sm font-semibold text-[#060A23] transition-colors cursor-pointer shadow-2xs"
                        >
                            <i class="pi pi-arrow-left text-xs"></i>
                            <span>{{ route.name === 'content-admin-preview' ? 'Kembali ke Detail Kelas' : 'Kembali ke Daftar Kelas' }}</span>
                        </button>
                        <RouterLink
                            v-if="progress.is_complete && route.name !== 'content-admin-preview'"
                            :to="`/student/kelas/${courseId}/selesai`"
                            class="inline-flex items-center gap-1.5 rounded-full bg-[#662FFF] hover:bg-[#5422DB] text-white px-4 py-2 text-xs sm:text-sm font-semibold transition-colors"
                        >
                            Lihat Halaman Selesai &amp; Sertifikat
                        </RouterLink>
                    </div>

                    <!-- Dynamic Content Rendering Card -->
                    <div class="rounded-[22px] border border-[#E6ECF5] bg-white p-4 sm:p-6 shadow-xs">
                    <ContentText
                        v-if="currentContent.type === 'text'"
                        :content="currentContent"
                    />
                    <ContentVideo
                        v-else-if="currentContent.type === 'video'"
                        :content="currentContent"
                    />
                    <ContentDocument
                        v-else-if="currentContent.type === 'dokumen' || currentContent.type === 'pdf'"
                        :content="currentContent.content"
                        :title="currentContent.title"
                    />
                    <ContentQuiz
                        v-else-if="courseContentType(currentContent) === 'quiz'"
                        :key="currentContent.id"
                        :content="currentContent"
                    />
                    <ContentForm
                        v-else-if="
                            courseContentType(currentContent) === 'google_form'
                        "
                        :content="currentContent"
                        @form-submitted-change="handleFormSubmittedChange"
                    />
                    </div>

                    <!-- Action Button Sticky Footer Bar -->
                    <div
                        v-if="courseContentType(currentContent) !== 'quiz'"
                        class="fixed bottom-0 left-0 lg:left-[350px] right-0 h-[80px] sm:h-[100px] flex items-center justify-end px-4 sm:px-8 bg-gradient-to-t from-white via-white/95 to-transparent z-20 pointer-events-none"
                    >
                        <button
                            type="button"
                            @click="markAsCompleted"
                            :disabled="savingProgress || progress.is_complete"
                            class="pointer-events-auto w-full sm:w-fit rounded-full py-3.5 px-6 font-semibold text-white bg-[#662FFF] hover:bg-[#5521cc] shadow-xl transition-all text-center text-sm sm:text-base cursor-pointer"
                        >
                            <span v-if="savingProgress"
                                >Menyimpan progres...</span
                            >
                            <span v-else-if="progress.is_complete"
                                >✓ Pembelajaran selesai</span
                            >
                            <span v-else-if="!isLastContent"
                                >Tandai Selesai &amp; Lanjutkan</span
                            >
                            <span v-else>Tandai Materi Selesai</span>
                        </button>
                    </div>
                </div>

                <!-- No Content Selected -->
                <div
                    v-else
                    class="flex items-center justify-center h-80 sm:h-96"
                >
                    <div class="text-center p-4">
                        <h2
                            class="text-xl sm:text-2xl font-bold text-gray-600 mb-2 sm:mb-4"
                        >
                            Select Content to Start Learning
                        </h2>
                        <p class="text-sm sm:text-base text-gray-500 mb-4">
                            Choose a lesson from the sidebar to begin
                        </p>
                        <button
                            @click="isMobileSidebarOpen = true"
                            class="lg:hidden px-4 py-2 bg-[#662FFF] text-white rounded-xl text-xs font-semibold"
                        >
                            Buka Daftar Materi
                        </button>
                    </div>
                </div>
            </main>
        </template>
    </div>
</template>
