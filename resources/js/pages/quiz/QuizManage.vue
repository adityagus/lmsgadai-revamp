<template>
    <main class="quiz-app stack">
        <header class="topbar">
            <div>
                <h1>Master Kuis</h1>
                <p class="muted">
                    Siapkan evaluasi, kelola peserta, dan pantau hasil belajar.
                </p>
            </div>
        </header>
        <div v-if="error" class="error" role="alert">{{ error }}</div>
        <div v-if="message" class="success" role="status">{{ message }}</div>
        <nav class="tabs">
            <button
                v-for="t in ['Bank Soal']"
                :key="t"
                :class="{ selected: tab === t }"
                @click="tab = t"
            >
                {{ t }}</button
            ><router-link class="button" to="/student/kelas"
                >Pembelajaran Peserta</router-link
            >
        </nav>
        <p v-if="loading" class="empty">Memuat data…</p>
        <template v-else-if="meta">
            <template v-if="tab === 'Bank Soal'"
                ><details class="panel bank-create mb-3">
                    <summary>
                        Buat bank soal baru
                        <span class="muted"
                            >{{ meta.banks.length }} bank tersedia</span
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
                                        question.id ? "Edit Soal" : "Buat Soal"
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
                                            v-for="b in meta.banks"
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
                                                v-for="c in meta.courses"
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
                                    <button class="primary" :disabled="busy">
                                        {{
                                            busy ? "Menyimpan…" : "Simpan Soal"
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
                                        questions.length
                                    }}</span>
                                </h2>
                                <p class="muted">
                                    Cari, edit, atau salin soal untuk digunakan
                                    dalam quiz kelas.
                                </p>
                            </div>
                            <button
                                class="primary"
                                :disabled="busy || !meta.banks.length"
                                @click="createQuestion"
                            >
                                + Tambah Soal
                            </button>
                        </header>
                        <p v-if="!meta.banks.length" class="muted">
                            Buat bank soal terlebih dahulu melalui panel di
                            atas.
                        </p>
                        <div class="question-filters">
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
                                        v-for="b in meta.banks"
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
                        </div>
                        <div class="row question-library-header">
                            <p class="muted" role="status">
                                {{ filteredQuestions.length }} soal ditemukan
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
                                    <details
                                        v-if="
                                            q.question.length > 180 ||
                                            q.question.split('\n').length > 3
                                        "
                                        class="question-full"
                                    >
                                        <summary>
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
                                        Edit</button
                                    ><button
                                        :disabled="busy"
                                        @click="editQuestion(q, true)"
                                    >
                                        Salin
                                    </button>
                                </div>
                            </article>
                            <p v-if="!filteredQuestions.length" class="empty">
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
                                >Per halaman<select v-model.number="pageSize">
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
                                    :disabled="page === 1"
                                    @click="page--"
                                    aria-label="Halaman sebelumnya"
                                >
                                    ←</button
                                ><span
                                    >Halaman {{ page }} / {{ totalPages }}</span
                                ><button
                                    :disabled="page >= totalPages"
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
            <template v-if="tab === 'Kelas & Student'"
                ><div class="grid">
                    <section class="panel stack">
                        <h2>
                            {{ classForm.id ? "Edit Kelas" : "Buat Kelas" }}
                        </h2>
                        <form class="stack" @submit.prevent="saveClass">
                            <label class="field"
                                >Nama kelas<input
                                    v-model="classForm.name"
                                    required
                                    maxlength="255"
                                    placeholder="Gadai Batch 01" /></label
                            ><label class="field"
                                >Cari student<input
                                    v-model="studentSearch"
                                    placeholder="Nama atau username"
                            /></label>
                            <div class="scroll-list">
                                <label
                                    v-for="s in filteredStudents"
                                    :key="s.id"
                                    class="check"
                                    ><input
                                        type="checkbox"
                                        v-model="classForm.student_ids"
                                        :value="s.id"
                                    />{{ s.name }} ({{ s.username }})</label
                                >
                            </div>
                            <p class="muted">
                                {{ classForm.student_ids.length }} student
                                dipilih. Pengguna muncul setelah login pertama
                                atau didaftarkan admin.
                            </p>
                            <div class="row">
                                <button class="primary" :disabled="busy">
                                    Simpan Kelas</button
                                ><button
                                    type="button"
                                    @click="
                                        classForm = {
                                            name: '',
                                            student_ids: [],
                                        }
                                    "
                                >
                                    Reset
                                </button>
                            </div>
                        </form>
                        <div class="divider"></div>
                        <article
                            v-for="c in meta.classes"
                            :key="c.id"
                            class="row"
                            style="justify-content: space-between"
                        >
                            <div>
                                <h3>{{ c.name }}</h3>
                                <p class="muted">
                                    {{ c.student_ids.length }} student
                                </p>
                            </div>
                            <button
                                @click="
                                    classForm = JSON.parse(JSON.stringify(c))
                                "
                            >
                                Edit
                            </button>
                        </article>
                    </section>
                    <section class="panel stack">
                        <h2>Pengguna LMS</h2>
                        <p class="muted">
                            Pemetaan menggunakan username login karyawan yang
                            sudah ada.
                        </p>
                        <form
                            v-if="meta.user.role === 'admin'"
                            class="stack"
                            @submit.prevent="saveUser"
                        >
                            <label class="field"
                                >Username karyawan<input
                                    v-model="userForm.username"
                                    required
                                    maxlength="150" /></label
                            ><label class="field"
                                >Nama<input
                                    v-model="userForm.name"
                                    required
                                    maxlength="255" /></label
                            ><label class="field"
                                >Role<select v-model="userForm.role">
                                    <option value="student">Student</option>
                                    <option value="trainer">Trainer</option>
                                    <option value="teacher">
                                        Teacher (legacy)
                                    </option>
                                    <option value="admin">Admin</option>
                                </select></label
                            ><button class="primary" :disabled="busy">
                                Simpan Pengguna / Role
                            </button>
                        </form>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Username</th>
                                        <th>Role</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="s in meta.students" :key="s.id">
                                        <td>{{ s.name }}</td>
                                        <td>{{ s.username }}</td>
                                        <td>{{ s.role }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div></template
            >
        </template>
    </main>
</template>
<script setup>
import { ref, computed, onMounted, watch, nextTick } from "vue";
import { useRoute } from "@/navigation";
import { api, errorMessage } from "./api";
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
    if (busy.value) return;
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
    type: "multiple_choice",
    weight: 1,
    category: "",
    topic: "",
    difficulty: "medium",
    explanation: "",
    options: Array.from({ length: 4 }, () => ({ text: "", is_correct: false })),
});
const question = ref(newQuestion()),
    classForm = ref({ name: "", student_ids: [] }),
    userForm = ref({ username: "", name: "", role: "student" });
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
        await fn();
        await load();
        message.value = msg;
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
    action(async () => {
        await api(
            question.value.id ? "put" : "post",
            question.value.id ? `questions/${question.value.id}` : "questions",
            question.value,
        );
        question.value = newQuestion();
        questionDialog.value.close();
    }, "Soal disimpan.");
const saveClass = () =>
    action(async () => {
        await api("post", "classes", classForm.value);
        classForm.value = { name: "", student_ids: [] };
    }, "Kelas disimpan.");
const saveUser = () =>
    action(async () => {
        await api("post", "users", userForm.value);
        userForm.value = { username: "", name: "", role: "student" };
    }, "Pengguna disimpan.");
onMounted(async () => {
    try {
        await load();
    } catch (e) {
        error.value = errorMessage(e);
    } finally {
        loading.value = false;
    }
});
</script>

<style scoped>
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
    margin: auto;
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
