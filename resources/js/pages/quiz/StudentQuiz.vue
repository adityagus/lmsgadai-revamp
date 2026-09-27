<template>
    <main class="quiz-app stack">
        <header class="topbar">
            <div>
                <p class="eyebrow">Pembelajaran Saya</p>
                <h1>Quiz Saya</h1>
                <p class="muted">
                    Uji pemahaman Anda, lanjutkan belajar dengan percaya diri.
                </p>
            </div>
            <router-link
                v-if="user && ['admin', 'trainer', 'teacher'].includes(user.role)"
                class="button primary"
                to="/quiz/manage"
                >Kelola Quiz →</router-link
            >
        </header>
        <div v-if="error" class="error" role="alert">
            {{ error }} <button @click="load">Coba lagi</button>
        </div>
        <div class="tabs">
            <button
                v-for="tab in [
                    'Semua',
                    'Tersedia',
                    'Sedang Dikerjakan',
                    'Selesai',
                ]"
                :key="tab"
                :class="{ selected: filter === tab }"
                @click="filter = tab"
            >
                {{ tab }}
            </button>
        </div>
        <p v-if="loading" class="empty">Memuat quiz Anda…</p>
        <div v-else-if="!filtered.length" class="panel empty">
            Belum ada quiz pada kategori ini. Quiz yang ditugaskan akan muncul
            di sini.
        </div>
        <div class="grid">
            <article v-for="q in filtered" :key="q.id" class="panel stack">
                <div class="row" style="justify-content: space-between">
                    <span class="eyebrow">{{
                        q.course || "Evaluasi Pembelajaran"
                    }}</span
                    ><span class="badge">{{ q.status }}</span>
                </div>
                <div>
                    <h2>{{ q.title }}</h2>
                    <p class="muted">
                        {{ q.classes.join(", ") || "Penugasan individual" }}
                    </p>
                </div>
                <p v-if="q.description" class="muted">{{ q.description }}</p>
                <div class="stats">
                    <div>
                        <strong>{{ q.question_count }} soal</strong>
                        <p class="muted">Jumlah pertanyaan</p>
                    </div>
                    <div>
                        <strong>{{ q.duration_minutes }} menit</strong>
                        <p class="muted">Durasi pengerjaan</p>
                    </div>
                    <div>
                        <strong>{{ q.passing_grade }}</strong>
                        <p class="muted">Nilai kelulusan</p>
                    </div>
                    <div>
                        <strong
                            >{{ q.attempts.length }} /
                            {{ q.maximum_attempt || "∞" }}</strong
                        >
                        <p class="muted">Kesempatan digunakan</p>
                    </div>
                </div>
                <div class="muted">
                    {{ dateLabel(q.available_from) }}<br />sampai
                    {{ dateLabel(q.available_until) }}
                </div>
                <div class="row">
                    <button v-if="primaryQuizAction(q).kind === 'start'" class="primary" @click="selected = q">{{ primaryQuizAction(q).label }}</button>
                    <router-link v-else-if="primaryQuizAction(q).attempt" class="button primary" :to="'/student/quiz/attempt/' + primaryQuizAction(q).attempt">{{ primaryQuizAction(q).label }}</router-link>
                    <span v-else class="muted">{{ primaryQuizAction(q).label }}</span>
                </div>
                <details v-if="q.attempts.some(a => a.status !== 'IN_PROGRESS')" class="quiz-details"><summary>Riwayat pengerjaan</summary><div class="stack"><router-link v-for="a in q.attempts.filter(a => a.status !== 'IN_PROGRESS')" :key="a.id" class="quiz-history-link" :to="'/student/quiz/attempt/' + a.id">Pengerjaan ke-{{ a.attempt_number }} <span>Lihat Hasil</span></router-link></div></details>
            </article>
        </div>
        <div v-if="selected" class="overlay" @click.self="selected = null">
            <section
                class="dialog stack"
                role="dialog"
                aria-modal="true"
                aria-labelledby="start-title"
            >
                <h2 id="start-title">{{ selected.title }}</h2>
                <p>
                    {{ selected.question_count }} soal ·
                    {{ selected.duration_minutes }} menit
                </p>
                <p class="muted">
                    Timer dimulai setelah Anda menekan Mulai Quiz dan tetap
                    berjalan saat browser ditutup. Batas akhir mengikuti periode
                    quiz. Jawaban tersimpan otomatis; pastikan indikator
                    penyimpanan berhasil sebelum menutup halaman.
                </p>
                <div class="row">
                    <button :disabled="starting" @click="selected = null">
                        Kembali</button
                    ><button
                        class="primary"
                        :disabled="starting"
                        @click="start"
                    >
                        {{ starting ? "Menyiapkan…" : "Mulai Quiz" }}
                    </button>
                </div>
            </section>
        </div>
    </main>
</template>
<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useRouter } from "@/navigation";
import { api, errorMessage, dateLabel } from "./api";
import "./quiz.css";
import { primaryQuizAction } from "./flow.mjs";
const router = useRouter(),
    quizzes = ref([]),
    user = ref(null),
    filter = ref("Semua"),
    loading = ref(true),
    error = ref(""),
    selected = ref(null),
    starting = ref(false);
const filtered = computed(() =>
    quizzes.value.filter(
        (q) => filter.value === "Semua" || q.status === filter.value,
    ),
);
async function load() {
    try {
        const data = await api("get", "student");
        quizzes.value = data.quizzes;
        user.value = data.user;
        error.value = "";
    } catch (e) {
        error.value = errorMessage(e);
    } finally {
        loading.value = false;
    }
}
async function start() {
    starting.value = true;
    try {
        const a = await api("post", `${selected.value.id}/start`);
        router.push("/student/quiz/attempt/" + a.id);
    } catch (e) {
        selected.value = null;
        await load();
        error.value = errorMessage(e);
    } finally {
        starting.value = false;
    }
}
let interval;
onMounted(() => {
    load();
    interval = setInterval(load, 30000);
});
onUnmounted(() => clearInterval(interval));
</script>
