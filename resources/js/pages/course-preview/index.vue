<script setup>
import Header from "@/components/Header.vue";
import ContentVideo from "./ContentVideo.vue";
import ContentText from "./ContentText.vue";
import ContentPdf from "./ContentPdf.vue";
import ContentForm from "./ContentForm.vue";
import ContentQuiz from "./ContentQuiz.vue";
import { courseContentType } from "@/utils/courseContentType.mjs";
import { ref, onMounted, onUnmounted, computed, watch } from "vue";
import { useRoute, useRouter, onBeforeRouteLeave } from "@/navigation";
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
const canOpenContent = (index) => {
    return (
        index >= 0 &&
        index < contents.value.length &&
        (route.name === "content-admin-preview" ||
            index <= currentContentIndex.value ||
            isContentCompleted(contents.value[index]?.id))
    );
};
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
const currentContent = computed(
    () => contents.value[currentContentIndex.value] || {},
);
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
                (item) => !completedAt(item.id),
            );
            currentContentIndex.value =
                route.name === "content-admin-preview" && requested >= 0
                    ? requested
                    : resume >= 0
                      ? resume
                      : Math.max(0, contents.value.length - 1);
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
    console.log("index", index, "contentindex", currentContentIndex.value);
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
                Swal.fire({
                    title: "Selamat!",
                    text: "Seluruh materi kelas telah selesai. Progres Anda tersimpan.",
                    icon: "success",
                    confirmButtonColor: "#662FFF",
                });
            } else {
                isCurrentFormSubmitted.value = false;
                const next = contents.value.findIndex(
                    (item) => !completedAt(item.id),
                );
                currentContentIndex.value = Math.max(0, next);
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
        if (newContents.length > 0 && currentContentIndex.value === 0) {
            currentContentIndex.value = 0;
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

                        <div
                            class="text-white text-sm space-y-2"
                            aria-live="polite"
                        >
                            <div class="flex justify-between">
                                <p class="">
                                    {{ progress.completed_count }} dari
                                    {{ progress.total }}
                                </p>
                                <span class="">{{ progressPercent }}%</span>
                            </div>
                            <div
                                class="h-2 rounded-full bg-gray-700 overflow-hidden"
                                role="progressbar"
                                :aria-valuenow="progressPercent"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                aria-label="Progres pembelajaran"
                            >
                                <div
                                    class="h-full bg-[#7F33FF] transition-all"
                                    :style="{ width: progressPercent + '%' }"
                                ></div>
                            </div>
                        </div>

                        <!-- Course Contents -->
                        <ul
                            class="flex flex-col gap-3 sm:gap-4"
                            v-if="contents.length > 0"
                        >
                            <li
                                v-for="(content, index) in contents"
                                :key="content.id"
                            >
                                <button
                                    @click="goToContent(index)"
                                    :disabled="!canOpenContent(index)  && !completedAt(content.id)"
                                    class="w-full text-left disabled:cursor-not-allowed"
                                    :class="{
                                        'opacity-50': !canOpenContent(index) && !completedAt(content.id),
                                    }"
                                    :title="
                                        canOpenContent(index) && !completedAt(content.id)
                                            ? content.title
                                            : 'Perpindahan materi hanya melalui penyelesaian langkah aktif'
                                    "
                                    :aria-current="
                                        index === currentContentIndex
                                            ? 'step'
                                            : undefined
                                    "
                                >
                                    <div
                                        class="flex items-center gap-3 w-full rounded-full border p-3 sm:p-[14px_20px] transition-all duration-300 hover:bg-[#662FFF] hover:border-[#8661EE] hover:shadow-[-10px_-6px_10px_0_#7F33FF_inset]"
                                        :class="{
                                            'bg-[#662FFF] border-[#8661EE] shadow-[-10px_-6px_10px_0_#7F33FF_inset]':
                                                index === currentContentIndex,
                                            'bg-[#070B24] border-[#24283E] shadow-[-10px_-6px_10px_0_#181A35_inset]':
                                                index !== currentContentIndex,
                                        }"
                                    >
                                        <img
                                            :src="getContentIcon(content.type)"
                                            class="w-5 h-5 sm:w-6 sm:h-6 shrink-0"
                                            alt="icon"
                                        />
                                        <span
                                            class="w-full font-semibold text-white text-xs sm:text-sm line-clamp-1 transition-all duration-300 hover:line-clamp-none"
                                        >
                                            {{ content.title }}
                                        </span>
                                        <span
                                            v-if="!canOpenContent(index) && !completedAt(content.id)"
                                            class="shrink-0 text-white text-xs"
                                            aria-label="Terkunci"
                                            >🔒</span
                                        >
                                        <span
                                            v-if="completedAt(content.id)"
                                            class="shrink-0 text-green-300 font-bold"
                                            :title="completedLabel(content.id)"
                                            aria-label="Materi selesai"
                                        >
                                            <img
                                                src="/assets/images/icons/checklist.svg"
                                                alt="check"
                                                width="20px"
                                            />
                                        </span>
                                    </div>
                                </button>
                            </li>
                        </ul>

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
                    class="relative flex flex-col gap-4 sm:gap-[26px]"
                    v-if="currentContent.id"
                >
                    <div
                        class="text-sm text-gray-600 flex flex-wrap gap-2"
                        aria-live="polite"
                    >
                        <span
                            >Progres: {{ progress.completed_count }}/{{
                                progress.total
                            }}
                            materi selesai</span
                        >
                        <span
                            v-if="completedAt(currentContent.id)"
                            class="text-green-700 font-semibold"
                            >✓ {{ completedLabel(currentContent.id) }}</span
                        >
                    </div>
                    <!-- Dynamic Content Rendering -->
                    <ContentText
                        v-if="currentContent.type === 'text'"
                        :content="currentContent"
                    />
                    <ContentVideo
                        v-else-if="currentContent.type === 'video'"
                        :content="currentContent"
                    />
                    <ContentPdf
                        v-else-if="currentContent.type === 'pdf'"
                        :content="currentContent.content"
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
                                >Mark as Completed & Continue</span
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
