<template>
    <main class="quiz-app stack workspace w-full min-w-0" :aria-busy="loading">
        <header class="workspace-hero"><div><span class="workspace-eyebrow">PUSAT EVALUASI</span><h1>Bank Soal & Peserta</h1><p>Soal yang tertata. Evaluasi yang bermakna.<br />Kelola pertanyaan, temukan peserta, dan telusuri hasil belajar.</p></div><router-link to="/batch-pelatihan" class="workspace-hero-link">Kelola batch </router-link></header>
        <div v-if="loading || meta" class="workspace-stats">
            <div v-for="stat in stats" :key="stat.label">
                <span class="stat-icon" :class="stat.color"><WorkspaceIcon :name="stat.icon" /></span>
                <div><strong :class="{ 'loading-placeholder loading-count': loading }"><span :class="{ invisible: loading }">{{ stat.value }}</span></strong><small>{{ stat.label }}</small></div>
            </div>
        </div>
        <div v-if="error" class="error" role="alert">{{ error }} <button v-if="!meta" :disabled="loading" @click="retryLoad">Coba lagi</button></div>
        <div v-if="message" class="success" role="status">{{ message }}</div>
        <nav class="tabs workspace-tabs" aria-label="Bagian pengelolaan evaluasi">
            <button
                v-for="t in ['Bank Soal', 'Peserta', 'Riwayat Kuis']"
                :key="t"
                :class="{ selected: tab === t }"
                :aria-pressed="tab === t"
                :disabled="loading || !meta"
                @click="tab = t"
            >
                {{ t }}</button
            ><router-link class="button" to="/student/kelas"
                >Pembelajaran Peserta</router-link
            >
        </nav>
        <p v-if="loading" class="sr-only" role="status">Memuat bank soal dan peserta...</p>
        <template v-if="loading || meta">
            <template v-if="tab === 'Bank Soal'"
                ><details class="panel bank-create mb-3">
                    <summary :aria-disabled="loading" @click="loading && $event.preventDefault()">
                        Buat bank soal baru
                        <span class="muted"
                            >{{ loading ? '...' : displayMeta.banks.length }} bank tersedia</span
                        >
                    </summary>
                    <form
                        class="row"
                        style="margin-top: 16px"
                        @submit.prevent="saveBank"
                    >
                        <input
                            v-model="bankName"
                            required
                            maxlength="255"
                            placeholder="Nama bank soal baru"
                            aria-label="Nama bank soal"
                            style="max-width: 360px"
                        /><button class="primary" :disabled="busy">
                            + Buat Bank
                        </button>
                    </form>
                </details>
                <div>
                    <dialog
                        ref="questionDialog"
                        class="question-dialog"
                        aria-labelledby="question-dialog-title"
                        @cancel.prevent="closeQuestion"
                    >
                        <section class="panel stack">
                            <header class="row question-dialog-header">
                                <h2 id="question-dialog-title">
                                    {{
                                        question.id ? (question.is_used ? "Edit Versi Baru" : "Edit Soal") : "Buat Soal"
                                    }}
                                </h2>
                                <button
                                    type="button"
                                    :disabled="busy"
                                    @click="closeQuestion"
                                    aria-label="Tutup form soal"
                                >
                                    Tutup
                                </button>
                            </header>
                            <p v-if="error" class="error" role="alert">
                                {{ error }}
                            </p>
                            <p v-if="question.id" class="muted">
                                {{ question.is_used ? "Soal ini sudah digunakan. Perubahan akan disimpan sebagai soal baru di bank soal. Pilih versi baru tersebut di editor kuis untuk menggunakannya; kuis dan riwayat jawaban lama tetap memakai soal sebelumnya." : "Jika soal sudah digunakan saat disimpan, perubahan akan dibuat sebagai versi baru agar riwayat jawaban tetap aman." }}
                            </p>
                            <form class="stack" @submit.prevent="saveQuestion">
                                <label class="field"
                                    >Bank soal<select
                                        v-model="question.bank_id"
                                        required
                                    >
                                        <option value="">
                                            Pilih bank soal
                                        </option>
                                        <option
                                            v-for="b in displayMeta.banks"
                                            :value="b.id"
                                            :key="b.id"
                                        >
                                            {{ b.name }}
                                        </option>
                                    </select></label
                                >
                                <div class="form-grid">
                                    <label class="field"
                                        >Jenis soal<select
                                            v-model="question.type"
                                            @change="changeType"
                                        >
                                            <option value="multiple_choice">
                                                Multiple Choice
                                            </option>
                                            <option value="multiple_answer">
                                                Multiple Answer
                                            </option>
                                            <option value="true_false">
                                                True / False
                                            </option>
                                            <option value="essay">Essay</option>
                                        </select></label
                                    ><label class="field"
                                        >Bobot<input
                                            v-model.number="question.weight"
                                            type="number"
                                            min="0.01"
                                            max="10000"
                                            step="0.01"
                                            required
                                    /></label>
                                </div>
                                <label class="field"
                                    >Pertanyaan<textarea
                                        v-model="question.question"
                                        required
                                        maxlength="20000"
                                    ></textarea>
                                </label>
                                <QuestionImageInput v-model="question.image_path" v-model:image-url="question.image_url" @busy="imageUploading = $event" />
                                <div
                                    v-if="question.type !== 'essay'"
                                    class="stack"
                                    style="gap: 10px"
                                >
                                    <p class="muted">
                                        Centang opsi jawaban benar.
                                    </p>
                                    <div
                                        v-for="(o, i) in question.options"
                                        :key="i"
                                        class="row"
                                    >
                                        <input
                                            v-model="o.is_correct"
                                            type="checkbox"
                                            :aria-label="
                                                'Jawaban benar opsi ' + (i + 1)
                                            "
                                        /><input
                                            v-model="o.text"
                                            required
                                            :aria-label="'Opsi ' + (i + 1)"
                                            style="flex: 1"
                                            maxlength="5000"
                                        /><button
                                            v-if="
                                                question.type !==
                                                    'true_false' &&
                                                question.options.length > 2
                                            "
                                            type="button"
                                            @click="
                                                question.options.splice(i, 1)
                                            "
                                            aria-label="Hapus opsi"
                                        >
                                            ×
                                        </button>
                                    </div>
                                    <button
                                        v-if="
                                            question.type !== 'true_false' &&
                                            question.options.length < 30
                                        "
                                        type="button"
                                        @click="
                                            question.options.push({
                                                text: '',
                                                is_correct: false,
                                            })
                                        "
                                    >
                                        + Tambah Opsi
                                    </button>
                                </div>
                                <div class="form-grid">
                                    <label class="field"
                                        >Difficulty<select
                                            v-model="question.difficulty"
                                        >
                                            <option value="easy">Easy</option>
                                            <option value="medium">
                                                Medium
                                            </option>
                                            <option value="hard">Hard</option>
                                        </select></label
                                    ><label class="field"
                                        >Kategori<input
                                            v-model="question.category"
                                            placeholder="Produk Gadai / SOP / Risk" /></label
                                    ><label class="field"
                                        >Course<select
                                            v-model="question.course_id"
                                        >
                                            <option :value="null">Umum</option>
                                            <option
                                                v-for="c in displayMeta.courses"
                                                :key="c.id"
                                                :value="c.id"
                                            >
                                                {{ c.name }}
                                            </option>
                                        </select></label
                                    ><label class="field"
                                        >Topik<input v-model="question.topic"
                                    /></label>
                                </div>
                                <label class="field"
                                    >Pembahasan<textarea
                                        v-model="question.explanation"
                                    ></textarea>
                                </label>
                                <div class="row">
                                    <button class="primary" :disabled="busy || imageUploading">
                                        {{
                                            busy ? "Menyimpan…" : (question.id && question.is_used ? "Simpan Versi Baru" : "Simpan Soal")
                                        }}</button
                                    ><button
                                        type="button"
                                        :disabled="busy"
                                        @click="closeQuestion"
                                    >
                                        Batal
                                    </button>
                                </div>
                            </form>
                        </section>
                    </dialog>
                    <section class="panel stack question-library">
                        <header class="row question-library-header">
                            <div>
                                <h2>
                                    Daftar Soal
                                    <span class="badge">{{
                                        (loading ? '...' : questions.length)
                                    }}</span>
                                </h2>
                                <p class="muted">
                                    Cari, edit, atau salin soal untuk digunakan
                                    dalam quiz kelas.
                                </p>
                            </div>
                            <button
                                class="primary"
                                :disabled="loading || busy || !displayMeta.banks.length"
                                @click="createQuestion"
                            >
                                + Tambah Soal
                            </button>
                        </header>
                        <p v-if="!loading && !displayMeta.banks.length" class="muted">
                            Buat bank soal terlebih dahulu melalui panel di
                            atas.
                        </p>
                        <fieldset class="question-filters" :disabled="loading" aria-label="Filter daftar soal">
                            <label class="field"
                                >Cari soal
                                <input
                                    v-model="search"
                                    placeholder="Cari pertanyaan / kategori…"
                                    aria-label="Cari soal" /></label
                            ><label class="field"
                                >Bank soal<select
                                    v-model="bankFilter"
                                    aria-label="Filter bank"
                                >
                                    <option value="">Semua bank</option>
                                    <option
                                        v-for="b in displayMeta.banks"
                                        :value="b.id"
                                        :key="b.id"
                                    >
                                        {{ b.name }}
                                    </option>
                                </select></label
                            >
                            <label class="field"
                                >Jenis soal<select v-model="typeFilter">
                                    <option value="">Semua jenis</option>
                                    <option
                                        v-for="(label, value) in typeLabels"
                                        :key="value"
                                        :value="value"
                                    >
                                        {{ label }}
                                    </option>
                                </select></label
                            >
                            <label class="field"
                                >Kesulitan<select v-model="difficultyFilter">
                                    <option value="">Semua tingkat</option>
                                    <option value="easy">Mudah</option>
                                    <option value="medium">Sedang</option>
                                    <option value="hard">Sulit</option>
                                </select></label
                            >
                        </fieldset>
                        <div class="row question-library-header">
                            <p class="muted" role="status">
                                {{ loading ? '...' : filteredQuestions.length }} soal ditemukan
                            </p>
                            <button
                                v-if="
                                    search ||
                                    bankFilter ||
                                    typeFilter ||
                                    difficultyFilter
                                "
                                @click="resetFilters"
                            >
                                Reset filter
                            </button>
                        </div>
                        <div
                            class="question-list"
                            ref="questionList"
                            tabindex="0"
                            aria-label="Daftar soal"
                        >
                            <template v-if="loading">
                                <article v-for="n in pageSize" :key="`loading-${n}`" class="question-card manage-loading" aria-hidden="true">
                                    <div class="question-copy">
                                        <div class="row"><span class="badge loading-placeholder"><span class="invisible">Pilihan tunggal</span></span><span class="muted loading-placeholder"><span class="invisible">Sedang</span></span></div>
                                        <p class="question-text loading-placeholder"><span class="invisible">Pertanyaan sedang dimuat dari bank soal.</span></p>
                                        <p class="muted loading-placeholder"><span class="invisible">Bank soal · Kategori · Bobot</span></p>
                                    </div>
                                    <div class="row question-actions"><button disabled tabindex="-1">Edit</button><button disabled tabindex="-1">Salin</button></div>
                                </article>
                            </template>
                            <article
                                v-for="(q, index) in pagedQuestions"
                                :key="q.id"
                                class="question-card"
                            >
                                <div class="question-copy">
                                    <div class="row">
                                        <span class="muted"
                                            >{{ pageStart + index + 1 }}.</span
                                        ><span class="badge">{{
                                            typeLabels[q.type] || q.type
                                        }}</span
                                        ><span class="muted">{{
                                            difficultyLabels[q.difficulty] ||
                                            q.difficulty
                                        }}</span>
                                    </div>
                                    <p class="question-text">
                                        {{ q.question }}
                                    </p>
                                    <img v-if="q.image_url" :src="q.image_url" alt="Gambar soal" class="question-image" loading="lazy" />
                                    <details
                                        v-if="
                                            q.question.length > 180 ||
                                            q.question.split('\n').length > 3
                                        "
                                        class="question-full"
                                    >
                                        <summary :aria-disabled="loading" @click="loading && $event.preventDefault()">
                                            Lihat pertanyaan lengkap
                                        </summary>
                                        <p>{{ q.question }}</p>
                                    </details>
                                    <p class="muted">
                                        {{
                                            bankNames[q.bank_id] || "Tanpa bank"
                                        }}
                                        · {{ q.category || "Umum" }} ·
                                        {{ q.weight }} poin
                                    </p>
                                </div>
                                <div class="row question-actions">
                                    <button
                                        :disabled="busy"
                                        @click="editQuestion(q)"
                                    >
                                        {{ q.is_used ? "Edit Versi Baru" : "Edit" }}</button
                                    ><button
                                        :disabled="busy"
                                        @click="editQuestion(q, true)"
                                    >
                                        Salin
                                    </button>
                                </div>
                            </article>
                            <p v-if="!loading && !filteredQuestions.length" class="empty">
                                {{
                                    questions.length
                                        ? "Tidak ada soal yang cocok. Coba ubah filter atau kata pencarian."
                                        : "Belum ada soal. Tambahkan soal pertama untuk bank Anda."
                                }}
                            </p>
                        </div>
                        <nav
                            class="question-pagination text-xs"
                            aria-label="Halaman daftar soal"
                        >
                            <label class="row"
                                >Per halaman<select v-model.number="pageSize" :disabled="loading">
                                    <option :value="5">5</option>
                                    <option :value="10">10</option>
                                    <option :value="20">20</option>
                                </select></label
                            >
                            <span class="muted"
                                >{{
                                    filteredQuestions.length
                                        ? pageStart + 1
                                        : 0
                                }}–{{
                                    Math.min(
                                        pageStart + pageSize,
                                        filteredQuestions.length,
                                    )
                                }}
                                dari {{ filteredQuestions.length }}</span
                            >
                            <div class="row">
                                <button
                                    :disabled="loading || page === 1"
                                    @click="page--"
                                    aria-label="Halaman sebelumnya"
                                >
                                    ←</button
                                ><span
                                    >Halaman {{ page }} / {{ totalPages }}</span
                                ><button
                                    :disabled="loading || page >= totalPages"
                                    @click="page++"
                                    aria-label="Halaman berikutnya"
                                >
                                    →
                                </button>
                            </div>
                        </nav>
                    </section>
                </div></template
            >
            <section v-if="tab === 'Peserta'" class="panel participant-library"><header class="topbar"><div><h2>Direktori Peserta</h2><p class="muted">Data karyawan aktif, sesuai nama dan jabatan dari sistem karyawan.</p></div><router-link to="/batch-pelatihan" class="button primary">Atur penugasan batch &#8594;</router-link></header><label class="field">Cari karyawan<input v-model="studentSearch" placeholder="Cari nama atau username..." /></label><p class="muted">{{ filteredStudents.length }} karyawan ditemukan</p><div class="participant-grid"><article v-for="student in pagedStudents" :key="student.id" class="participant-card"><div class="avatar">{{ student.name?.slice(0, 1).toUpperCase() }}</div><div><h3>{{ student.name }}</h3><p class="muted">{{ student.username }}</p><span class="badge">{{ student.role }}</span></div><router-link :to="`/riwayat-belajar?username=${encodeURIComponent(student.username)}`" class="participant-history" :aria-label="`Riwayat ${student.name}`">&#8599;</router-link></article></div><p v-if="!filteredStudents.length" class="empty">Karyawan tidak ditemukan. Coba nama atau username lain.</p><nav class="question-pagination" aria-label="Halaman karyawan"><button :disabled="studentPage === 1" @click="studentPage--">Sebelumnya</button><span>{{ studentPage }} / {{ studentPages }}</span><button :disabled="studentPage >= studentPages" @click="studentPage++">Berikutnya</button></nav></section>
            <LearningHistory v-if="tab === 'Riwayat Kuis'" />
        </template>
    </main>
</template>
<script setup>
import { ref, computed, onMounted, watch, nextTick } from "vue";
import { useRoute, onBeforeRouteLeave } from "@/navigation";
import { api, errorMessage } from "./api";
import WorkspaceIcon from '@/components/WorkspaceIcon.vue';
import LearningHistory from '@/components/LearningHistory.vue';
import '@/styles/learning-workspace.css';
import QuestionImageInput from './QuestionImageInput.vue';
const imageUploading = ref(false);
import "./quiz.css";
const route = useRoute(),
    tab = ref("Bank Soal"),
    error = ref(""),
    message = ref(""),
    busy = ref(false),
    loading = ref(true),
    meta = ref(null),
    questions = ref([]),
    bankName = ref(""),
    search = ref(""),
    bankFilter = ref(""),
    studentSearch = ref("");
const displayMeta = computed(() => meta.value ?? { banks: [], courses: [], students: [] });
const stats = computed(() => [
    { label: 'Bank soal', icon: 'book', color: '', value: displayMeta.value.banks.length },
    { label: 'Pertanyaan tersedia', icon: 'question', color: 'mint', value: questions.value.length },
    { label: 'Soal digunakan', icon: 'check', color: 'peach', value: questions.value.filter(q => q.is_used).length },
    { label: 'Karyawan aktif', icon: 'users', color: 'sky', value: displayMeta.value.students.length },
]);
const page = ref(1),
    pageSize = ref(5),
    typeFilter = ref(""),
    difficultyFilter = ref("");
const questionDialog = ref(null),
    questionList = ref(null);
const typeLabels = {
    multiple_choice: "Pilihan tunggal",
    multiple_answer: "Pilihan majemuk",
    true_false: "Benar / Salah",
    essay: "Esai",
};
const difficultyLabels = { easy: "Mudah", medium: "Sedang", hard: "Sulit" };
const bankNames = computed(() =>
    Object.fromEntries((meta.value?.banks || []).map((b) => [b.id, b.name])),
);
let initialQuestion = "";
function openQuestion() {
    error.value = "";
    initialQuestion = JSON.stringify(question.value);
    questionDialog.value.showModal();
}
function createQuestion() {
    question.value = newQuestion();
    if (bankFilter.value) question.value.bank_id = bankFilter.value;
    openQuestion();
}
function closeQuestion() {
    if (busy.value || imageUploading.value) return;
    if (
        JSON.stringify(question.value) !== initialQuestion &&
        !window.confirm("Tutup dan buang perubahan soal yang belum disimpan?")
    )
        return;
    questionDialog.value.close();
}
function resetFilters() {
    search.value = "";
    bankFilter.value = "";
    typeFilter.value = "";
    difficultyFilter.value = "";
}
const newQuestion = () => ({
    bank_id: meta.value?.banks[0]?.id || "",
    course_id: null,
    question: "",
    image_path: null,
    image_url: null,
    type: "multiple_choice",
    weight: 1,
    category: "",
    topic: "",
    difficulty: "medium",
    explanation: "",
    options: Array.from({ length: 4 }, () => ({ text: "", is_correct: false })),
});
const question = ref(newQuestion());
onBeforeRouteLeave(() => {
    if (!questionDialog.value?.open) return true;
    if (busy.value || imageUploading.value) return false;
    return JSON.stringify(question.value) === initialQuestion || window.confirm('Tinggalkan form soal? Perubahan belum disimpan.');
});
const filteredQuestions = computed(() =>
    questions.value.filter(
        (q) =>
            (!bankFilter.value ||
                String(q.bank_id) === String(bankFilter.value)) &&
            (!typeFilter.value || q.type === typeFilter.value) &&
            (!difficultyFilter.value ||
                q.difficulty === difficultyFilter.value) &&
            `${q.question} ${q.category}`
                .toLowerCase()
                .includes(search.value.trim().toLowerCase()),
    ),
);
const totalPages = computed(() =>
    Math.max(1, Math.ceil(filteredQuestions.value.length / pageSize.value)),
);
const pageStart = computed(() => (page.value - 1) * pageSize.value);
const pagedQuestions = computed(() =>
    filteredQuestions.value.slice(
        pageStart.value,
        pageStart.value + pageSize.value,
    ),
);
watch([search, bankFilter, typeFilter, difficultyFilter, pageSize], () => {
    page.value = 1;
});
watch(totalPages, (count) => {
    page.value = Math.min(page.value, count);
});
watch(
    [page, search, bankFilter, typeFilter, difficultyFilter, pageSize],
    async () => {
        await nextTick();
        if (questionList.value) questionList.value.scrollTop = 0;
    },
);
const filteredStudents = computed(() =>
    (meta.value?.students || []).filter((s) =>
        `${s.name} ${s.username}`
            .toLowerCase()
            .includes(studentSearch.value.toLowerCase()),
    ),
);
const studentPage = ref(1);
const studentPages = computed(() => Math.max(1, Math.ceil(filteredStudents.value.length / 12)));
const pagedStudents = computed(() => filteredStudents.value.slice((studentPage.value - 1) * 12, studentPage.value * 12));
watch(studentSearch, () => { studentPage.value = 1; });
watch(studentPages, max => { studentPage.value = Math.min(studentPage.value, max); });
async function retryLoad() { loading.value = true; error.value = ""; try { await load(); } catch (e) { error.value = errorMessage(e); } finally { loading.value = false; } }
async function load() {
    const data = await Promise.all([
        api("get", "metadata"),
        api("get", "questions"),
    ]);
    [meta.value, questions.value] = data;
}
async function action(fn, msg) {
    busy.value = true;
    error.value = "";
    message.value = "";
    try {
        const resultMessage = await fn();
        await load();
        message.value = typeof resultMessage === "string" ? resultMessage : msg;
    } catch (e) {
        error.value = errorMessage(e);
    } finally {
        busy.value = false;
    }
}
function changeType() {
    if (question.value.type === "essay") question.value.options = [];
    else if (question.value.type === "true_false")
        question.value.options = [
            { text: "Benar", is_correct: true },
            { text: "Salah", is_correct: false },
        ];
    else if (!question.value.options.length)
        question.value.options = newQuestion().options;
}
function editQuestion(q, copy = false) {
    question.value = JSON.parse(JSON.stringify(q));
    question.value.options.forEach((o) => (o.is_correct = !!o.is_correct));
    if (copy) delete question.value.id;
    openQuestion();
}
const saveBank = () =>
    action(async () => {
        await api("post", "banks", { name: bankName.value });
        bankName.value = "";
    }, "Bank soal dibuat.");
const saveQuestion = () =>
    !imageUploading.value && action(async () => {
        const saved = await api(
            question.value.id ? "put" : "post",
            question.value.id ? `questions/${question.value.id}` : "questions",
            { ...question.value, version_if_used: true },
        );
        question.value = newQuestion();
        questionDialog.value.close();
        return saved.created_version
            ? "Versi baru soal disimpan di bank soal. Pilih versi baru di editor kuis untuk menggunakannya. Kuis dan riwayat lama tetap memakai soal sebelumnya."
            : "Soal disimpan.";
    }, "Soal disimpan.");
onMounted(retryLoad);
</script>

<style scoped>
.loading-placeholder {
    display: block;
    border-radius: 8px;
    background: linear-gradient(100deg, #eee8f5 25%, #faf7ff 50%, #eee8f5 75%);
    background-size: 200% 100%;
    animation: manage-loading 1.6s ease-in-out infinite;
}
.loading-count { width: 42px; max-width: 100%; }
.manage-loading .question-copy { width: 100%; }
.manage-loading .question-text { width: 90%; }
.manage-loading .question-copy > .muted { width: 65%; }
@keyframes manage-loading { to { background-position: -200% 0; } }
@media (prefers-reduced-motion: reduce) {
    .loading-placeholder { animation: none; }
}
.bank-create summary {
    cursor: pointer;
    font-weight: 650;
}
.bank-create summary span {
    margin-left: 12px;
    font-weight: 400;
}
.question-library-header {
    justify-content: space-between;
}
.question-library-header h2 {
    margin-bottom: 6px;
}
.question-filters {
    min-width: 0;
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr;
    gap: 12px;
}
.question-list {
    max-height: 520px;
    overflow-y: auto;
    overscroll-behavior: contain;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
}
.question-list:focus-visible {
    outline: 2px solid #7f33ff;
    outline-offset: 3px;
}
.question-card {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 18px;
    border-bottom: 1px solid #e5e7eb;
}
.question-card:last-child {
    border-bottom: 0;
}
.question-card:hover {
    background: #faf8ff;
}
.question-copy {
    min-width: 0;
    flex: 1;
}
.question-text {
    margin: 10px 0;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    overflow-wrap: anywhere;
    white-space: pre-line;
}
.question-full {
    margin-bottom: 10px;
    font-size: 14px;
}
.question-full summary {
    color: #662fff;
    cursor: pointer;
}
.question-full p {
    white-space: pre-wrap;
    overflow-wrap: anywhere;
    margin-top: 10px;
}
.question-actions {
    flex-shrink: 0;
}
.question-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}
.question-pagination select {
    width: auto;
}
.question-dialog {
    width: min(760px, calc(100vw - 32px));
    max-height: 90dvh;
    padding: 0;
    border: 0;
    border-radius: 20px;
    overflow-y: auto;
}
.question-dialog::backdrop {
    background: rgb(6 10 35 / 55%);
}
.question-dialog-header {
    justify-content: space-between;
}
@media (max-width: 900px) {
    .question-filters {
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 600px) {
    .question-filters {
        grid-template-columns: 1fr;
    }
    .question-card {
        flex-direction: column;
        padding: 14px;
        gap: 10px;
    }
    .question-list {
        max-height: 460px;
    }
    .question-pagination {
        align-items: flex-start;
    }
}
</style>
