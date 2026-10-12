<template>
    <main class="quiz-app quiz-exam" ref="root">
        <div v-if="loading" class="empty">Menyiapkan ruang ujian…</div>
        <div v-if="error" class="error" role="alert">
            {{ error }}
            <button v-if="conflict" @click="reload">
                Muat ulang jawaban server</button
            ><button v-else @click="retry">Coba lagi</button>
        </div>
        <template v-if="attempt">
            <header class="topbar">
                <div>
                    <p class="eyebrow">
                        {{
                            preview
                                ? "Preview · Tidak mencatat attempt"
                                : "LMS Gadai · Online Assessment"
                        }}
                    </p>
                    <h1>{{ attempt.title }}</h1>
                    <p class="muted">
                        {{ attempt.student || "Preview Trainer" }}
                        <span v-if="attempt.classes?.length"
                            >· {{ attempt.classes.join(", ") }}</span
                        >
                    </p>
                </div>
                <div class="row">
                    <button @click="fullscreen">Layar penuh</button>
                    <div
                        v-if="!finished"
                        class="timer"
                        :class="{ urgent: seconds <= 60 }"
                        role="timer"
                        aria-label="Sisa waktu"
                    >
                        <small style="display: block; font-size: 11px"
                            >SISA WAKTU</small
                        >{{ timerLabel }}
                    </div>
                    <router-link
                        v-if="finished || preview"
                        class="button"
                        :to="returnLink"
                        >{{ returnLabel }}</router-link
                    >
                </div>
            </header>
            <div v-if="warning && !finished" class="error" role="status">
                {{ warning }}
            </div>
            <template v-if="finished">
                <section class="panel stack">
                    <span class="badge" style="align-self: start">{{
                        attempt.status === "AUTO_SUBMITTED"
                            ? "Dikumpulkan otomatis"
                            : "Berhasil dikumpulkan"
                    }}</span>
                    <h2>Ujian berhasil dikumpulkan.</h2>
                    <p class="muted">
                        {{
                            attempt.pending_review
                                ? "Jawaban essay menunggu pemeriksaan trainer."
                                : "Hasil ditampilkan sesuai pengaturan trainer."
                        }}
                    </p>
                    <div class="stats">
                        <div v-if="attempt.score !== undefined" class="stat">
                            <span class="muted">Nilai attempt</span
                            ><strong>{{ attempt.score ?? "Menunggu" }}</strong>
                        </div>
                        <div
                            v-if="attempt.aggregate_score !== undefined"
                            class="stat"
                        >
                            <span class="muted"
                                >Nilai akhir seluruh attempt</span
                            ><strong>{{
                                attempt.aggregate_score ?? "Menunggu"
                            }}</strong>
                        </div>
                        <div v-if="attempt.passed !== undefined" class="stat">
                            <span class="muted"
                                >Passing grade {{ attempt.passing_grade }}</span
                            ><strong style="font-size: 20px">{{
                                attempt.passed === null
                                    ? "Menunggu"
                                    : attempt.passed
                                      ? "LULUS"
                                      : "TIDAK LULUS"
                            }}</strong>
                        </div>
                        <div
                            v-for="[key, label] in [
                                ['total_correct', 'Benar'],
                                ['total_wrong', 'Salah'],
                                ['total_unanswered', 'Tidak dijawab'],
                            ]"
                            :key="key"
                            v-show="attempt[key] !== undefined"
                            class="stat"
                        >
                            <span class="muted">{{ label }}</span
                            ><strong>{{ attempt[key] }}</strong>
                        </div>
                    </div>
                    <p class="muted">
                        {{ attempt.question_count }} soal · Attempt #{{
                            attempt.attempt_number
                        }}
                        · Durasi {{ completion }}<br />Dikumpulkan
                        {{ dateLabel(attempt.submitted_at) }}
                    </p>
                </section>
                <section
                    v-for="q in attempt.questions"
                    :key="q.id"
                    class="panel stack"
                    style="margin-top: 18px"
                >
                    <h3>{{ q.position }}. {{ q.question }}</h3>
                    <img v-if="q.image_url" :src="q.image_url" alt="Gambar soal" class="question-image" />
                    <p v-if="q.type === 'essay'" class="prewrap">
                        {{ q.essay || "Tidak dijawab" }}
                    </p>
                    <div
                        v-for="o in q.options"
                        :key="o.id"
                        :class="[
                            'option',
                            reviewOptionState(q, o),
                            { picked: q.selected_options.includes(o.id) },
                        ]"
                    >
                        {{ o.text }}
                        <span
                            v-if="reviewOptionState(q, o) === 'correct'"
                            class="answer-label"
                            >Jawaban benar</span
                        ><span
                            v-else-if="reviewOptionState(q, o) === 'incorrect'"
                            class="answer-label"
                            >Jawaban salah</span
                        ><span
                            v-if="q.selected_options.includes(o.id)"
                            class="muted"
                            >Pilihan Anda</span
                        >
                    </div>
                    <p v-if="q.points !== undefined" class="muted">
                        Poin: {{ q.points ?? "Menunggu pemeriksaan" }} /
                        {{ q.weight }}
                    </p>
                    <section
                        v-if="q.explanation !== undefined"
                        class="quiz-explanation"
                    >
                        <h4>Penjelasan jawaban</h4>
                        <p class="prewrap">
                            {{
                                q.explanation?.trim() ||
                                "Penjelasan belum ditambahkan oleh pengajar."
                            }}
                        </p>
                    </section>
                    <p v-if="q.feedback">Feedback: {{ q.feedback }}</p>
                </section>
            </template>
            <template v-else-if="current">
                <div class="panel" style="margin-bottom: 24px">
                    <div class="row" style="justify-content: space-between">
                        <span
                            ><strong
                                >{{ answered }} / {{ questions.length }}</strong
                            >
                            soal terjawab</span
                        ><span class="muted" role="status">{{
                            preview
                                ? "Mode latihan preview"
                                : saving
                                  ? "Menyimpan jawaban…"
                                  : dirty.size
                                    ? "Jawaban belum tersimpan"
                                    : "✓ Semua jawaban tersimpan"
                        }}</span
                        ><button class="mobile-toggle" @click="drawer = true">
                            Daftar Soal
                        </button>
                    </div>
                    <div class="progress">
                        <span
                            :style="{
                                width:
                                    (answered / questions.length) * 100 + '%',
                            }"
                        ></span>
                    </div>
                </div>
                <div class="exam-grid">
                    <section class="panel">
                        <div class="row" style="justify-content: space-between">
                            <span class="badge"
                                >Soal {{ index + 1 }} dari
                                {{ questions.length }}</span
                            ><span class="muted">{{
                                typeLabels[current.type]
                            }}</span>
                        </div>
                        <img v-if="current.image_url" :src="current.image_url" alt="Gambar soal" class="question-image my-3" />
                        <h2 class="question-text">{{ current.question }}</h2>
                        <div class="stack" style="gap: 12px">
                            <textarea
                                v-if="current.type === 'essay'"
                                :disabled="locked"
                                :value="current.essay"
                                aria-label="Jawaban essay"
                                placeholder="Tuliskan jawaban Anda di sini…"
                                @input="
                                    current.essay = $event.target.value;
                                    changed(current);
                                "
                            ></textarea
                            ><label
                                v-for="(o, i) in current.options"
                                :key="o.id"
                                class="option"
                                :class="{
                                    picked: current.selected_options.includes(
                                        o.id,
                                    ),
                                }"
                                ><input
                                    :type="
                                        current.type === 'multiple_answer'
                                            ? 'checkbox'
                                            : 'radio'
                                    "
                                    :name="'question-' + current.id"
                                    :checked="
                                        current.selected_options.includes(o.id)
                                    "
                                    :disabled="locked"
                                    @change="choose(o.id)"
                                /><strong
                                    >{{ String.fromCharCode(65 + i) }}.</strong
                                ><span>{{ o.text }}</span></label
                            >
                        </div>
                        <div class="row question-footer">
                            <button
                                :disabled="index === 0"
                                @click="go(index - 1)"
                            >
                                <i class="pi pi-arrow-left"></i> Sebelumnya
                            </button>
                            <button
                                :disabled="locked"
                                :class="{ primary: current.flagged }"
                                @click="
                                    current.flagged = !current.flagged;
                                    changed(current);
                                "
                            >
                                <span v-if="current.flagged"
                                    ><i
                                        class="pi pi-star-fill text-yellow-400 fill-yellow-400"
                                    ></i>
                                    Ditandai
                                </span>
                                <span v-else>
                                    <i class="pi pi-flag"></i> Ragu-ragu
                                </span></button
                            ><button
                                :disabled="index === questions.length - 1"
                                @click="go(index + 1)"
                            >
                                Selanjutnya <i class="pi pi-arrow-right"></i>
                            </button>
                        </div>
                    </section>
                    <div
                        v-if="drawer"
                        class="overlay"
                        @click.self="drawer = false"
                    ></div>
                    <aside class="panel navigator" :class="{ drawer }">
                        <div class="row" style="justify-content: space-between">
                            <h3>Navigasi Soal</h3>
                            <button
                                v-if="drawer"
                                @click="drawer = false"
                                aria-label="Tutup daftar soal"
                            >
                                ✕
                            </button>
                        </div>
                        <p class="muted">Bebas berpindah ke nomor mana pun.</p>
                        <div class="numbers">
                            <button
                                v-for="(q, i) in questions"
                                :key="q.id"
                                class="number"
                                :class="{
                                    seen: q.visited,
                                    answered: isAnswered(q),
                                    flagged: q.flagged,
                                    current: i === index,
                                }"
                                :aria-label="`Soal ${i + 1}, ${q.flagged ? 'ditandai' : isAnswered(q) ? 'sudah dijawab' : q.visited ? 'dibuka belum dijawab' : 'belum dibuka'}`"
                                :aria-current="i === index ? 'step' : undefined"
                                @click="
                                    go(i);
                                    drawer = false;
                                "
                            >
                                {{ i + 1 }}
                            </button>
                        </div>
                        <ul class="question-legend" aria-label="Keterangan status soal">
                            <li><span class="number current" aria-hidden="true"></span><span>Soal aktif</span></li>
                            <li><span class="number answered" aria-hidden="true"></span><span>Dijawab</span></li>
                            <li><span class="number flagged" aria-hidden="true"></span><span>Ragu-ragu</span></li>
                            <li><span class="number seen" aria-hidden="true"></span><span>Dilewati</span></li>
                            <li><span class="number" aria-hidden="true"></span><span>Belum dibuka</span></li>
                        </ul>
                        <div class="divider"></div>
                        <p class="muted">
                            {{ questions.length - answered }} belum dijawab ·
                            {{ flagged }} ditandai
                        </p>
                        <button
                            class="primary"
                            style="width: 100%; margin-top: 14px"
                            :disabled="submitting"
                            @click="
                                drawer = false;
                                confirm = true;
                            "
                        >
                            Submit Quiz
                        </button>
                    </aside>
                </div>
            </template>
            <div
                v-if="confirm"
                class="overlay"
                @click.self="!submitting && (confirm = false)"
            >
                <section
                    class="dialog stack"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="submit-title"
                >
                    <h2 id="submit-title">Kumpulkan quiz?</h2>
                    <p>
                        Anda yakin ingin mengumpulkan quiz? Jawaban tidak dapat
                        diubah setelah dikumpulkan.
                    </p>
                    <div class="stats">
                        <div class="stat">
                            <strong>{{ answered }}</strong
                            >Sudah dijawab
                        </div>
                        <div class="stat">
                            <strong>{{ questions.length - answered }}</strong
                            >Belum dijawab
                        </div>
                        <div class="stat">
                            <strong>{{ flagged }}</strong
                            >Ditandai
                        </div>
                    </div>
                    <p v-if="error" class="error">{{ error }}</p>
                    <div class="row">
                        <button :disabled="submitting" @click="confirm = false">
                            Kembali ke Quiz</button
                        ><button
                            class="primary"
                            :disabled="submitting || conflict"
                            @click="submit(false)"
                        >
                            {{ submitting ? "Mengumpulkan…" : "Submit Quiz" }}
                        </button>
                    </div>
                </section>
            </div>
        </template>
    </main>
</template>
<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useRoute, onBeforeRouteLeave } from "@/navigation";
import { api, errorMessage, dateLabel } from "./api";
import { reviewOptionState } from "./review.mjs";
import "./quiz.css";
import "primeicons/primeicons.css";

const route = useRoute(),
    preview = route.meta.quizPreview === true;
const returnLink = computed(() => {
    const course = attempt.value?.course_id || route.query.course;
    if (preview) return course ? "/kelas/" + course : "/kelas";
    return /^\d+$/.test(String(course || ""))
        ? {
              path: "/student/kelas/" + course,
              query: /^\d+$/.test(String(route.query.content || ""))
                  ? { content: route.query.content, ...(attempt.value?.batch_id ? { batch_id: attempt.value.batch_id } : {}) }
                  : (attempt.value?.batch_id ? { batch_id: attempt.value.batch_id } : {}),
          }
        : "/student/kelas";
});
const returnLabel = computed(() => "Kembali ke Kelas");
const attempt = ref(null),
    loading = ref(true),
    error = ref(""),
    index = ref(0),
    drawer = ref(false),
    confirm = ref(false),
    submitting = ref(false),
    saving = ref(false),
    conflict = ref(false),
    dirty = ref(new Set()),
    seconds = ref(0),
    warning = ref(""),
    root = ref(null);
const questions = computed(() => attempt.value?.questions || []),
    current = computed(() => questions.value[index.value]),
    finished = computed(
        () => attempt.value && attempt.value.status !== "IN_PROGRESS",
    ),
    locked = computed(
        () => seconds.value <= 0 || submitting.value || conflict.value,
    );
const isAnswered = (q) =>
    q.type === "essay" ? !!q.essay.trim() : q.selected_options.length > 0;
const answered = computed(() => questions.value.filter(isAnswered).length),
    flagged = computed(() => questions.value.filter((q) => q.flagged).length);
const timerLabel = computed(
    () =>
        `${String(Math.floor(seconds.value / 60)).padStart(2, "0")}:${String(seconds.value % 60).padStart(2, "0")}`,
);
const completion = computed(() => {
    const n = Math.max(
        0,
        Math.round(
            (new Date(attempt.value.submitted_at) -
                new Date(attempt.value.started_at)) /
                1000,
        ),
    );
    return `${Math.floor(n / 60)}m ${n % 60}s`;
});
const typeLabels = {
    multiple_choice: "Pilih satu jawaban",
    multiple_answer: "Pilih semua jawaban yang benar",
    true_false: "Benar atau salah",
    essay: "Jawaban uraian",
};
let deadline = 0,
    tick,
    sync,
    debounce,
    retryAt = 0,
    savePromise = null;
const warned = new Set();
function setClock(data) {
    const remaining = Math.max(
        0,
        new Date(data.expired_at) - new Date(data.server_now),
    );
    deadline = performance.now() + remaining;
    seconds.value = Math.ceil(remaining / 1000);
}
async function load() {
    loading.value = true;
    try {
        if (preview) {
            const data = await api("get", `manage/${route.params.id}/preview`);
            attempt.value = { ...data, status: "IN_PROGRESS" };
            deadline = performance.now() + data.duration_minutes * 60000;
            seconds.value = data.duration_minutes * 60;
        } else {
            attempt.value = await api("get", `attempts/${route.params.id}`);
            setClock(attempt.value);
        }
        index.value = 0;
        dirty.value.clear();
        conflict.value = false;
        error.value = "";
        if (!finished.value && current.value) go(0);
    } catch (e) {
        error.value = errorMessage(e);
    } finally {
        loading.value = false;
    }
}
function changed(q) {
    if (preview) return;
    dirty.value.add(q.id);
    clearTimeout(debounce);
    debounce = setTimeout(
        () => flush().catch(() => {}),
        q.type === "essay" ? 500 : 0,
    );
}
function go(i) {
    index.value = i;
    if (!locked.value && !questions.value[i].visited) {
        questions.value[i].visited = true;
        changed(questions.value[i]);
    }
}
function choose(id) {
    if (locked.value) return;
    const q = current.value;
    q.selected_options =
        q.type === "multiple_answer"
            ? q.selected_options.includes(id)
                ? q.selected_options.filter((x) => x !== id)
                : [...q.selected_options, id]
            : [id];
    changed(q);
}
async function flush() {
    if (preview || finished.value) return;
    if (savePromise) {
        await savePromise;
        if (dirty.value.size) return flush();
        return;
    }
    savePromise = (async () => {
        saving.value = true;
        try {
            while (dirty.value.size && !conflict.value) {
                const id = [...dirty.value][0],
                    q = questions.value.find((x) => x.id === id);
                const snapshot = JSON.stringify({
                    selected_options: q.selected_options,
                    essay: q.essay,
                    flagged: q.flagged,
                    visited: q.visited,
                });
                const response = await api(
                    "put",
                    `attempts/${attempt.value.id}/answers/${id}`,
                    { ...JSON.parse(snapshot), revision: q.revision },
                );
                q.revision = response.revision;
                if (
                    snapshot ===
                    JSON.stringify({
                        selected_options: q.selected_options,
                        essay: q.essay,
                        flagged: q.flagged,
                        visited: q.visited,
                    })
                )
                    dirty.value.delete(id);
            }
            error.value = "";
        } catch (e) {
            error.value = errorMessage(e);
            if (e.response?.status === 409) {
                conflict.value = true;
                await heartbeat();
            }
            throw e;
        } finally {
            saving.value = false;
        }
    })();
    try {
        await savePromise;
    } finally {
        savePromise = null;
    }
}
async function heartbeat() {
    if (preview || !attempt.value || finished.value) return;
    try {
        const data = await api(
            "get",
            `attempts/${attempt.value.id}?heartbeat=1`,
        );
        setClock(data);
        if (data.status !== "IN_PROGRESS") {
            attempt.value = data;
            dirty.value.clear();
            confirm.value = false;
            conflict.value = false;
        }
    } catch (e) {
        error.value = errorMessage(e);
    }
}
async function submit(auto) {
    if (submitting.value || finished.value) return;
    submitting.value = true;
    try {
        if (preview) {
            confirm.value = false;
            warning.value =
                "Preview selesai. Tidak ada nilai atau attempt yang disimpan.";
            return;
        }
        if (!auto) await flush();
        attempt.value = await api(
            "post",
            `attempts/${attempt.value.id}/submit`,
        );
        dirty.value.clear();
        confirm.value = false;
        error.value = "";
    } catch (e) {
        error.value = errorMessage(e);
        retryAt = performance.now() + 10000;
    } finally {
        submitting.value = false;
    }
}
async function retry() {
    if (!attempt.value) return load();
    if (seconds.value <= 0) return submit(true);
    try {
        await flush();
    } catch {}
}
async function reload() {
    if (savePromise) {
        try {
            await savePromise;
        } catch {}
    }
    await load();
}
function beforeUnload(e) {
    if (!finished.value && dirty.value.size) {
        e.preventDefault();
        e.returnValue = "";
    }
}
async function fullscreen() {
    try {
        if (document.fullscreenElement) await document.exitFullscreen();
        else await root.value.requestFullscreen();
    } catch {
        error.value = "Mode layar penuh tidak didukung browser ini.";
    }
}
function resume() {
    if (document.visibilityState === "visible") {
        heartbeat();
        retry();
    }
}
onBeforeRouteLeave(() => {
    if (!finished.value && !preview && (dirty.value.size || saving.value)) {
        error.value =
            "Tunggu sampai semua jawaban tersimpan sebelum meninggalkan halaman.";
        return false;
    }
});
onMounted(async () => {
    await load();
    tick = setInterval(() => {
        if (!attempt.value || finished.value) return;
        seconds.value = Math.max(
            0,
            Math.ceil((deadline - performance.now()) / 1000),
        );
        for (const n of [600, 300, 60])
            if (seconds.value <= n && !warned.has(n)) {
                warned.add(n);
                warning.value = `Waktu pengerjaan tersisa ${Math.ceil(seconds.value / 60)} menit.`;
            }
        if (seconds.value === 0 && !preview && performance.now() >= retryAt)
            submit(true);
    }, 250);
    sync = setInterval(() => {
        heartbeat();
        if (dirty.value.size && !conflict.value && seconds.value > 0)
            flush().catch(() => {});
    }, 15000);
    window.addEventListener("beforeunload", beforeUnload);
    window.addEventListener("online", retry);
    document.addEventListener("visibilitychange", resume);
});
onUnmounted(() => {
    clearInterval(tick);
    clearInterval(sync);
    clearTimeout(debounce);
    window.removeEventListener("beforeunload", beforeUnload);
    window.removeEventListener("online", retry);
    document.removeEventListener("visibilitychange", resume);
});
</script>
