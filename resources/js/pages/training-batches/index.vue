<template>
    <main class="batches workspace">
        <header class="workspace-hero">
            <div>
                <span class="workspace-eyebrow">RUANG PELATIHAN</span>
                <h1>Batch Pelatihan</h1>
                <p>
                    {{
                        canManage
                            ? "Satukan peserta dalam perjalanan belajar yang terarah. Atur periode, hubungkan kelas, dan ikuti perkembangan mereka."
                            : "Pelatihan yang ditugaskan kepada Anda, dengan langkah belajar dan riwayat yang mudah ditemukan."
                    }}
                </p>
            </div>
            <button v-if="canManage" class="primary" @click="create">
                + Buat batch baru</button
            ><Link v-else href="/riwayat-belajar" class="workspace-hero-link"
                >Riwayat kuis </Link
            >
        </header>
        <div v-if="canManage" class="workspace-stats">
            <div>
                <span class="stat-icon"><WorkspaceIcon name="calendar" /></span>
                <div>
                    <strong>{{ batches.length }}</strong
                    ><small>Total batch</small>
                </div>
            </div>
            <div>
                <span class="stat-icon mint"
                    ><WorkspaceIcon name="play"
                /></span>
                <div>
                    <strong>{{
                        batches.filter((b) => b.status === "active").length
                    }}</strong
                    ><small>Berstatus aktif</small>
                </div>
            </div>
            <div>
                <span class="stat-icon peach"
                    ><WorkspaceIcon name="edit"
                /></span>
                <div>
                    <strong>{{
                        batches.filter((b) => b.status === "draft").length
                    }}</strong
                    ><small>Dalam persiapan</small>
                </div>
            </div>
            <div>
                <span class="stat-icon sky"
                    ><WorkspaceIcon name="check"
                /></span>
                <div>
                    <strong>{{
                        batches.filter((b) => b.status === "completed").length
                    }}</strong
                    ><small>Batch ditutup</small>
                </div>
            </div>
        </div>
        <div v-if="error" class="notice error" role="alert">
            {{ error }} <button @click="load">Muat ulang</button>
        </div>
        <p v-if="message" class="notice" role="status">{{ message }}</p>
        <BatchLearning v-if="!loading && !canManage" />
        <details v-if="canManage" class="guide">
            <summary>
                Cara menggunakan batch
                <span class="hint">&#183; Panduan singkat</span>
            </summary>
            <div class="steps">
                <p><b>01</b> Buat kelompok & periode</p>
                <p><b>02</b> Pilih karyawan & kelas</p>
                <p><b>03</b> Aktifkan & pantau progres</p>
            </div>
            <p>
                Contoh: “Karyawan Baru Oktober” mengikuti kelas Dasar Gadai dan
                Layanan Nasabah. Kelas yang sama dapat digunakan oleh batch
                lain.
            </p>
        </details>
        <template v-else-if="canManage">
            <section v-if="!batches.length" class="empty">
                <h2>
                    {{
                        canManage
                            ? "Mulai batch pelatihan pertama"
                            : "Belum ada batch untuk Anda"
                    }}
                </h2>
                <p>
                    {{
                        canManage
                            ? "Siapkan peserta dan kelas sebagai draft terlebih dahulu. Aktifkan setelah semuanya siap."
                            : "Batch akan tampil setelah Anda ditambahkan dan batch diaktifkan oleh trainer."
                    }}
                </p>
                <button v-if="canManage" class="primary" @click="create">
                    Buat batch pertama
                </button>
            </section>
            <div v-if="batches.length" class="workspace-filterbar">
                <label class="batch-search"
                    >Cari batch<input
                        v-model="batchSearch"
                        placeholder="Cari nama atau catatan pelatihan..." /></label
                ><label
                    >Status<select v-model="statusFilter">
                        <option value="">Semua status</option>
                        <option value="draft">Draft</option>
                        <option value="active">Aktif</option>
                        <option value="completed">Ditutup</option>
                    </select></label
                >
            </div>
            <p v-if="batches.length" class="hint">
                {{ filteredBatches.length }} batch ditemukan
            </p>
            <div class="cards">
                <button
                    v-for="batch in pagedBatches"
                    :key="batch.id"
                    class="card"
                    :class="{ selected: detail?.batch.id === batch.id }"
                    @click="open(batch.id)"
                >
                    <span class="badge" :class="`batch-${batch.status}`">{{
                        labels[batch.status]
                    }}</span>
                    <h2>{{ batch.name }}</h2>
                    <p>
                        {{ date(batch.starts_on) }} – {{ date(batch.ends_on) }}
                    </p>
                    <div class="counts">
                        <span>{{ batch.member_count }} peserta</span
                        ><span>{{ batch.course_count }} kelas</span>
                    </div>
                    <span class="link">Lihat detail & progres →</span>
                </button>
            </div>
            <p v-if="batches.length && !filteredBatches.length" class="empty">
                Tidak ada batch yang cocok. Ubah pencarian atau status.
            </p>
            <nav
                v-if="batchPages > 1"
                class="batch-pagination"
                aria-label="Halaman batch"
            >
                <button :disabled="batchPage === 1" @click="batchPage--">
                    Sebelumnya</button
                ><span>{{ batchPage }} / {{ batchPages }}</span
                ><button
                    :disabled="batchPage === batchPages"
                    @click="batchPage++"
                >
                    Berikutnya
                </button>
            </nav>
        </template>
        <p v-if="detailLoading" aria-live="polite" class="text-center mt-4 mb-4">
            Memuat detail dan progres…
        </p>
        <section
            v-if="detail && !detailLoading"
            ref="detailPanel"
            class="panel detail-panel"
        >
            <div class="heading">
                <div>
                    <p class="eyebrow">DETAIL BATCH</p>
                    <h2>{{ detail.batch.name }}</h2>
                    <p>{{ detail.batch.description }}</p>
                </div>
                <button v-if="canManage" @click="edit">Edit batch</button>
            </div>
            <nav class="detail-tabs" aria-label="Detail batch">
                <button
                    :class="{ selected: detailTab === 'progress' }"
                    @click="detailTab = 'progress'"
                >
                    Progres peserta</button
                ><button
                    :class="{ selected: detailTab === 'history' }"
                    @click="detailTab = 'history'"
                >
                    Riwayat kuis
                </button>
            </nav>
            <LearningHistory
                v-if="detailTab === 'history'"
                :batch-id="detail.batch.id"
            /><template v-else
                ><p class="notice">
                    Progres mengikuti riwayat belajar karyawan pada kelas,
                    termasuk pembelajaran sebelum batch dimulai. Batch ini tidak
                    mengulang atau menghapus progres lama.
                </p>
                <div class="course-links">
                    <template v-for="course in detail.courses" :key="course.id"
                        ><Link
                            v-if="
                                canManage ||
                                detail.accessible_course_ids.includes(course.id)
                            "
                            :href="`/student/kelas/${course.id}`"
                            >{{ course.name }} ↗</Link
                        ><span v-else
                            >{{ course.name }} · akses batch di luar periode /
                            selesai</span
                        ></template
                    >
                </div>
                <p v-if="!detail.progress.length">
                    Tambahkan peserta dan kelas untuk melihat progres.
                </p>
                <div v-else class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Karyawan</th>
                                <th>Kelas</th>
                                <th>Progres</th>
                                <th>Status belajar</th>
                                <th>Riwayat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in detail.progress"
                                :key="`${row.username}-${row.course_id}`"
                            >
                                <td>
                                    <strong>{{ row.name }}</strong
                                    ><small>{{ row.username }}</small>
                                </td>
                                <td>{{ row.course_name }}</td>
                                <td>
                                    <progress
                                        :value="row.completed"
                                        :max="row.total || 1"
                                        :aria-label="`Progres ${row.name}`"
                                    ></progress
                                    ><small
                                        >{{ row.completed }}/{{
                                            row.total
                                        }}
                                        materi · {{ row.percent }}%</small
                                    >
                                </td>
                                <td>
                                    {{
                                        !row.total
                                            ? "Belum ada materi"
                                            : row.completed === row.total
                                              ? "Selesai"
                                              : row.completed
                                                ? "Sedang belajar"
                                                : "Belum mulai"
                                    }}
                                </td>
                                <td>
                                    <Link
                                        :href="`/riwayat-belajar?batch_id=${detail.batch.id}&username=${encodeURIComponent(row.username)}`"
                                        >Lihat riwayat &#8599;</Link
                                    >
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div></template
            >
        </section>
        <div v-if="form" class="overlay" @click.self="closeForm">
            <section
                ref="editor"
                class="editor"
                role="dialog"
                aria-modal="true"
                aria-labelledby="batch-title"
                @keydown.esc="closeForm"
                @keydown.tab="trapFocus"
            >
                <div class="heading">
                    <h2 id="batch-title">
                        {{
                            form.id
                                ? "Edit batch pelatihan"
                                : "Buat batch pelatihan"
                        }}
                    </h2>
                    <button
                        :disabled="saving"
                        aria-label="Tutup formulir"
                        @click="closeForm"
                    >
                        ✕
                    </button>
                </div>
                <form @submit.prevent="save">
                    <fieldset :disabled="saving">
                        <label
                            >Nama batch<input
                                v-model="form.name"
                                required
                                maxlength="255"
                                placeholder="Contoh: Karyawan Baru Oktober"
                                autofocus /></label
                        ><label
                            >Catatan<textarea
                                v-model="form.description"
                                maxlength="2000"
                                placeholder="Tujuan pelatihan atau arahan untuk peserta"
                            />
                        </label>
                        <div class="columns">
                            <label
                                >Tanggal mulai<input
                                    v-model="form.starts_on"
                                    type="date"
                                    required /></label
                            ><label
                                >Tanggal selesai<input
                                    v-model="form.ends_on"
                                    type="date"
                                    :min="form.starts_on"
                                    required
                            /></label>
                        </div>
                        <label
                            >Status pelaksanaan (bukan progres peserta)<select
                                v-model="form.status"
                            >
                                <option value="draft">Draft — persiapan</option>
                                <option value="active">
                                    Aktif — tampil kepada peserta
                                </option>
                                <option value="completed">
                                    Selesai — simpan untuk laporan
                                </option>
                            </select></label
                        >
                        <p class="hint">
                            Akses dari batch berlaku saat status aktif dan
                            tanggal berada dalam periode. Akses kelas dari
                            jabatan atau penugasan lain tetap berlaku.
                        </p>
                        <p v-if="form.status === 'completed'" class="notice">
                            Status Selesai menghentikan akses peserta melalui
                            batch. Gunakan Aktif jika peserta masih harus
                            belajar, meskipun semua persiapan batch sudah
                            selesai.
                        </p>
                        <h3>
                            1. Pilih kelas
                            <small>({{ form.course_ids.length }})</small>
                        </h3>
                        <p v-if="!courses.length" class="hint">
                            Belum ada kelas. Buat kelas pembelajaran terlebih
                            dahulu.
                        </p>
                        <div class="choices">
                            <label
                                v-for="course in courses"
                                :key="course.id"
                                class="check"
                                ><input
                                    v-model="form.course_ids"
                                    type="checkbox"
                                    :value="course.id"
                                />{{ course.name }}</label
                            >
                        </div>
                        <h3>
                            2. Pilih peserta
                            <small>({{ form.members.length }}/200)</small>
                        </h3>
                        <div class="search">
                            <input
                                v-model="search"
                                placeholder="Cari nama / username karyawan (min. 2 huruf)"
                                aria-label="Cari karyawan"
                                @input="scheduleSearch"
                            /><button type="button" @click="searchEmployees">
                                Cari
                            </button>
                        </div>
                        <p v-if="searching" class="hint">Mencari karyawan…</p>
                        <p
                            v-else-if="searched && !employees.length"
                            class="hint"
                        >
                            Tidak ada karyawan aktif yang sesuai.
                        </p>
                        <div class="choices">
                            <button
                                v-for="employee in employees"
                                :key="employee.username"
                                type="button"
                                :disabled="
                                    form.members.some(
                                        (m) => m.username === employee.username,
                                    ) || form.members.length >= 200
                                "
                                @click="form.members.push(employee)"
                            >
                                {{ employee.name }} ({{ employee.username }}) +
                            </button>
                        </div>
                        <div class="chips">
                            <button
                                v-for="member in form.members"
                                :key="member.username"
                                type="button"
                                :aria-label="`Hapus ${member.name} dari batch`"
                                @click="
                                    form.members = form.members.filter(
                                        (m) => m.username !== member.username,
                                    )
                                "
                            >
                                {{ member.name }} ×
                            </button>
                        </div>
                    </fieldset>
                    <p v-if="formError" class="notice error" role="alert">
                        {{ formError }}
                    </p>
                    <div class="actions">
                        <button
                            type="button"
                            :disabled="saving"
                            @click="closeForm"
                        >
                            Batal</button
                        ><button
                            class="primary"
                            :disabled="saving || optionsLoading"
                        >
                            {{ saving ? "Menyimpan…" : "Simpan batch" }}
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </main>
</template>

<script setup>
import {
    ref,
    computed,
    nextTick,
    watch,
    onMounted,
    onBeforeUnmount,
} from "vue";
import { Link, router } from "@inertiajs/vue3";
import { apiInstance } from "@/utils/axios";
import WorkspaceIcon from "@/components/WorkspaceIcon.vue";
import LearningHistory from "@/components/LearningHistory.vue";
import "@/styles/learning-workspace.css";
import BatchLearning from "@/components/BatchLearning.vue";
import { queryClient } from "@/api/queryClient";
const batches = ref([]),
    canManage = ref(false),
    loading = ref(true),
    error = ref(""),
    message = ref("");
const detail = ref(null),
    detailLoading = ref(false),
    form = ref(null),
    formError = ref(""),
    saving = ref(false);
const courses = ref([]),
    optionsLoading = ref(false),
    employees = ref([]),
    search = ref(""),
    searching = ref(false),
    searched = ref(false);
const batchSearch = ref(""),
    statusFilter = ref(""),
    batchPage = ref(1),
    detailTab = ref("progress"),
    detailPanel = ref(null);
const filteredBatches = computed(() =>
    batches.value.filter(
        (b) =>
            (!statusFilter.value || b.status === statusFilter.value) &&
            `${b.name} ${b.description || ""}`
                .toLowerCase()
                .includes(batchSearch.value.trim().toLowerCase()),
    ),
);
const batchPages = computed(() =>
    Math.max(1, Math.ceil(filteredBatches.value.length / 6)),
);
const pagedBatches = computed(() =>
    filteredBatches.value.slice((batchPage.value - 1) * 6, batchPage.value * 6),
);
watch([batchSearch, statusFilter], () => {
    batchPage.value = 1;
});
watch(batchPages, (max) => {
    batchPage.value = Math.min(batchPage.value, max);
});
const labels = {
    draft: "Draft",
    active: "Aktif",
    completed: "Ditutup oleh trainer",
};
const date = (value) =>
    new Date(`${value}T00:00:00`).toLocaleDateString("id-ID", {
        day: "numeric",
        month: "short",
        year: "numeric",
    });
const failure = (e) =>
    Object.values(e.response?.data?.errors || {})
        .flat()
        .join(" ") ||
    e.response?.data?.message ||
    "Permintaan gagal. Silakan coba lagi.";
let timer,
    searchVersion = 0,
    detailVersion = 0,
    previousFocus;
const editor = ref(null),
    originalForm = ref("");
const dirty = computed(
    () => form.value && JSON.stringify(form.value) !== originalForm.value,
);
watch(form, async (value, old) => {
    if (value && !old) {
        previousFocus = document.activeElement;
        await nextTick();
        editor.value?.querySelector("input")?.focus();
    } else if (!value) previousFocus?.focus();
});
function trapFocus(e) {
    const nodes = [
        ...editor.value.querySelectorAll(
            "button:not(:disabled), input:not(:disabled), select:not(:disabled), textarea:not(:disabled), a[href]",
        ),
    ];
    const first = nodes[0],
        last = nodes.at(-1);
    if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last?.focus();
    } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first?.focus();
    }
}
async function load() {
    loading.value = true;
    error.value = "";
    try {
        const { data } = await apiInstance.get("/api/training-batches");
        batches.value = data.batches;
        canManage.value = data.can_manage;
    } catch (e) {
        error.value = failure(e);
    } finally {
        loading.value = false;
    }
}
async function open(id) {
    const version = ++detailVersion;
    detail.value = null;
    detailTab.value = "progress";
    detailLoading.value = true;
    error.value = "";
    try {
        const { data } = await apiInstance.get(`/api/training-batches/${id}`);
        if (version === detailVersion) detail.value = data;
    } catch (e) {
        if (version === detailVersion) error.value = failure(e);
    } finally {
        if (version === detailVersion) {
            detailLoading.value = false;
            await nextTick();
            detailPanel.value?.scrollIntoView({
                behavior: window.matchMedia("(prefers-reduced-motion: reduce)")
                    .matches
                    ? "instant"
                    : "smooth",
                block: "start",
            });
        }
    }
}
async function options() {
    optionsLoading.value = true;
    try {
        courses.value = (
            await apiInstance.get("/api/training-batches/options")
        ).data.courses;
    } catch (e) {
        formError.value = failure(e);
    } finally {
        optionsLoading.value = false;
    }
}
function resetSearch() {
    search.value = "";
    employees.value = [];
    searched.value = false;
    formError.value = "";
    searchVersion++;
    searching.value = false;
    clearTimeout(timer);
}
function create() {
    resetSearch();
    form.value = {
        name: "",
        description: "",
        starts_on: "",
        ends_on: "",
        status: "draft",
        course_ids: [],
        members: [],
    };
    originalForm.value = JSON.stringify(form.value);
    options();
}
function edit() {
    resetSearch();
    form.value = {
        ...detail.value.batch,
        description: detail.value.batch.description || "",
        course_ids: detail.value.courses.map((c) => c.id),
        members: detail.value.members.map((m) => ({ ...m })),
    };
    originalForm.value = JSON.stringify(form.value);
    options();
}
function closeForm() {
    if (
        saving.value ||
        (dirty.value &&
            !window.confirm(
                "Tutup formulir? Perubahan yang belum disimpan akan dibatalkan.",
            ))
    )
        return;
    form.value = null;
    resetSearch();
}
function scheduleSearch() {
    clearTimeout(timer);
    searchVersion++;
    employees.value = [];
    searched.value = false;
    timer = setTimeout(searchEmployees, 350);
}
async function searchEmployees() {
    clearTimeout(timer);
    const version = ++searchVersion;
    if (search.value.trim().length < 2) {
        employees.value = [];
        searching.value = false;
        return;
    }
    searching.value = true;
    try {
        const { data } = await apiInstance.get(
            "/api/training-batches/options",
            { params: { q: search.value.trim() } },
        );
        if (version === searchVersion) {
            employees.value = data.employees;
            searched.value = true;
        }
    } catch (e) {
        if (version === searchVersion) formError.value = failure(e);
    } finally {
        if (version === searchVersion) searching.value = false;
    }
}
async function save() {
    saving.value = true;
    formError.value = "";
    try {
        const { members, id, ...values } = form.value;
        const { data } = await apiInstance.request({
            method: id ? "put" : "post",
            url: `/api/training-batches${id ? `/${id}` : ""}`,
            data: { ...values, usernames: members.map((m) => m.username) },
        });
        form.value = null;
        resetSearch();
        message.value = data.message;
        await queryClient.invalidateQueries({
            queryKey: ["courses", "list"],
            refetchType: "none",
        });
        await load();
        await open(data.id);
    } catch (e) {
        formError.value = failure(e);
    } finally {
        saving.value = false;
    }
}
const beforeUnload = (e) => {
    if (dirty.value) {
        e.preventDefault();
        e.returnValue = "";
    }
};
let removeGuard;
onMounted(() => {
    load();
    window.addEventListener("beforeunload", beforeUnload);
    removeGuard = router.on("before", () => {
        if (saving.value) return false;
        if (
            dirty.value &&
            !window.confirm("Tinggalkan formulir? Perubahan belum disimpan.")
        )
            return false;
    });
});
onBeforeUnmount(() => {
    clearTimeout(timer);
    searchVersion++;
    detailVersion++;
    removeGuard?.();
    window.removeEventListener("beforeunload", beforeUnload);
});
</script>

<style scoped>
.batch-search {
    flex: 1;
}
.workspace-filterbar label {
    font-size: 12px;
    color: #83788e;
    display: flex;
    flex-direction: column;
    gap: 7px;
}
.batch-pagination {
    display: flex;
    justify-content: flex-end;
    gap: 15px;
    align-items: center;
    margin-top: 20px;
    font-size: 13px;
}
.detail-tabs {
    display: flex;
    gap: 8px;
    margin-top: 20px;
    border-bottom: 1px solid #eee;
    padding-bottom: 12px;
}
.detail-tabs .selected {
    color: #7a3ec2;
    background: #f1e9fb;
    border-color: #e4d5f5;
}
.detail-panel {
    scroll-margin-top: 22px;
}
.detail-panel td a {
    font-size: 12px;
    color: #8746c9;
    white-space: nowrap;
}
.guide summary {
    cursor: pointer;
    color: #795296;
    font-size: 13px;
    font-weight: 600;
}
.card .link {
    display: flex;
    justify-content: space-between;
    border-top: 1px solid #f1ecf7;
    padding-top: 14px;
}
.overlay {
    backdrop-filter: blur(4px);
}

.batches {
    padding: 28px;
    color: #20243b;
}
.heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    flex-wrap: wrap;
}
.eyebrow {
    font-size: 11px;
    letter-spacing: 2px;
    font-weight: 700;
    color: #7f33ff;
}
h1 {
    font-size: 28px;
    font-weight: 700;
}
h2 {
    font-size: 19px;
    font-weight: 600;
}
h3 {
    font-weight: 600;
    margin-top: 20px;
}
p {
    color: #6b7280;
    line-height: 1.7;
}
button {
    border: 1px solid #dddce7;
    border-radius: 10px;
    padding: 10px 16px;
    font-weight: 600;
    background: white;
}
button:hover {
    border-color: #7f33ff;
}
button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
.primary {
    background: #7f33ff;
    color: white;
    border-color: #7f33ff;
}
.guide {
    background: #f2ebff;
    border: 1px solid #e5d6fc;
    border-radius: 18px;
    padding: 22px;
    margin: 26px 0;
}
.steps {
    display: flex;
    gap: 30px;
    flex-wrap: wrap;
    margin: 12px 0;
}
.steps b {
    color: #7f33ff;
    margin-right: 6px;
}
.guide p {
    font-size: 13px;
}
.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 18px;
}
.card {
    text-align: left;
    padding: 22px;
    border-radius: 18px;
}
.card h2 {
    margin: 14px 0 8px;
}
.card p,
.counts {
    font-size: 13px;
}
.counts {
    display: flex;
    gap: 18px;
    margin: 20px 0;
}
.selected {
    border-color: #7f33ff;
    background: #fcfaff;
}
.badge {
    font-size: 11px;
    background: #f0f1f5;
    padding: 5px 10px;
    border-radius: 20px;
}
.badge.batch-active {
    background: #e2f5ed;
    color: #147550;
}
.badge.batch-completed {
    background: #ede8ff;
    color: #6334b5;
}
.link,
.course-links a {
    color: #7f33ff;
    font-size: 13px;
}
.empty {
    text-align: center;
    padding: 45px;
    background: white;
    border: 1px dashed #cfc4e5;
    border-radius: 18px;
}
.empty p {
    margin: 12px auto 20px;
    max-width: 560px;
}
.panel {
    background: white;
    border: 1px solid #e9e7ef;
    border-radius: 18px;
    padding: 24px;
    margin-top: 24px;
}
.notice {
    padding: 13px 16px;
    background: #f3f0fa;
    border-radius: 10px;
    font-size: 13px;
    margin: 16px 0;
}
.error {
    background: #fff0f0;
    color: #a02035;
}
.table-wrap {
    overflow-x: auto;
}
table {
    width: 100%;
    font-size: 13px;
    text-align: left;
}
th,
td {
    padding: 14px;
    border-bottom: 1px solid #eee;
}
th {
    color: #6b7280;
    font-weight: 500;
}
small {
    display: block;
    color: #6b7280;
    margin-top: 4px;
}
progress {
    accent-color: #7f33ff;
    width: 130px;
    height: 7px;
}
.course-links {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin: 18px 0;
}
.course-links > a,
.course-links > span {
    background: #f6f4fb;
    padding: 10px;
    border-radius: 8px;
    font-size: 13px;
}
.overlay {
    position: fixed;
    inset: 0;
    background: #060a2380;
    z-index: 100;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
}
.editor {
    background: white;
    width: 700px;
    max-height: 92vh;
    overflow: auto;
    border-radius: 20px;
    padding: 26px;
}
.editor label {
    display: flex;
    flex-direction: column;
    gap: 6px;
    font-size: 13px;
    margin: 13px 0;
}
input,
textarea,
select {
    border: 1px solid #dddce7;
    border-radius: 9px;
    padding: 10px;
    width: 100%;
    background: white;
}
input:focus,
textarea:focus,
select:focus {
    outline: 2px solid #b994f5;
    outline-offset: 1px;
}
textarea {
    min-height: 65px;
}
.columns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}
.hint {
    font-size: 12px;
}
.choices {
    max-height: 180px;
    overflow: auto;
    display: flex;
    flex-direction: column;
    gap: 5px;
    margin: 10px 0;
}
.choices .check {
    flex-direction: row;
    align-items: center;
    margin: 0;
    padding: 8px;
    background: #faf9fc;
}
.check input {
    width: 16px;
    accent-color: #7f33ff;
}
.choices button {
    text-align: left;
    font-size: 12px;
}
.search {
    display: flex;
    gap: 8px;
    margin-top: 8px;
}
.chips {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.chips button {
    font-size: 12px;
    background: #f2ebff;
    color: #6334b5;
    padding: 6px 10px;
}
.actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 22px;
}
fieldset {
    min-width: 0;
}
h3 small {
    display: inline;
}
@media (max-width: 640px) {
    .batches {
        padding: 16px;
    }
    h1 {
        font-size: 23px;
    }
    .steps {
        gap: 5px;
        flex-direction: column;
    }
    .columns {
        grid-template-columns: 1fr;
    }
    .editor {
        padding: 18px;
    }
    .overlay {
        padding: 10px;
    }
    .panel {
        padding: 16px;
    }
}
</style>
