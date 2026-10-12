<template>
    <section
        class="learning"
        aria-labelledby="batch-learning-title"
        :aria-busy="loading"
    >
        <div class="learning-heading">
            <div>
                <p class="eyebrow">TUGAS PELATIHAN</p>
                <h2 id="batch-learning-title">Pelatihan Saya</h2>
            </div>
            <button :disabled="loading" @click="load">
                {{ loading ? "Memuat…" : "Perbarui" }}
            </button>
        </div>
        <p class="intro">
            Pilih kelas di bawah, pelajari materi secara berurutan, lalu
            kerjakan kuis yang tersedia. Progres Anda tersimpan otomatis.
        </p>
        <p v-if="error" role="alert" class="error">
            Pelatihan gagal dimuat. Klik Perbarui untuk mencoba kembali.
        </p>
        <p v-else-if="loading && !loaded" role="status">
            Memuat pelatihan Anda…
        </p>
        <p v-else-if="!batches.length" class="empty">
            Belum ada batch pelatihan yang ditugaskan kepada Anda. Anda tetap
            dapat mengikuti kelas lain yang tersedia di Pembelajaran.
        </p>
        <article v-for="batch in batches" :key="batch.id" class="batch">
            <div class="learning-heading">
                <h3>{{ batch.name }}</h3>
                <span class="status" :class="batch.state">{{
                    labels[batch.state]
                }}</span>
            </div>
            <Link
                class="history-link"
                :href="`/riwayat-belajar?batch_id=${batch.id}`"
                >Riwayat kuis batch &#8594;</Link
            >
            <p class="period">
                {{ date(batch.starts_on) }} – {{ date(batch.ends_on) }}
            </p>
            <p v-if="batch.description">{{ batch.description }}</p>
            <p class="instruction">{{ instructions[batch.state] }}</p>
            <p v-if="!batch.courses.length">
                Belum ada kelas di batch ini. Hubungi trainer untuk menambahkan
                kelas.
            </p>
            <div
                v-for="course in batch.courses"
                :key="course.id"
                class="course"
            >
                <div class="course-info">
                    <h4>{{ course.name }}</h4>
                    <p>
                        {{
                            course.total
                                ? `${course.completed} dari ${course.total} materi selesai`
                                : "Materi belum tersedia. Hubungi trainer."
                        }}
                    </p>
                    <progress
                        v-if="course.total"
                        :value="course.completed"
                        :max="course.total"
                        :aria-label="`Progres ${course.name}`"
                    ></progress>
                    <p v-if="!course.can_open" class="blocked">
                        {{ blocked[batch.state] }}
                    </p>
                </div>
                <Link
                    v-if="course.can_open && course.total"
                    :href="`/student/kelas/${course.id}${batch.state === 'ready' ? `?batch_id=${batch.id}` : ''}`"
                    class="start"
                    >{{
                        course.completed === course.total
                            ? "Buka Kembali"
                            : course.completed
                              ? "Lanjutkan Belajar"
                              : "Mulai Belajar"
                    }}
                    →</Link
                >
                <span v-else class="unavailable">{{
                    !course.total ? "Menunggu materi" : "Belum dapat dibuka"
                }}</span>
            </div>
        </article>
        <p v-if="batches.length" class="footnote">
            Materi yang pernah Anda selesaikan tetap dihitung. Status batch
            ditentukan oleh trainer dan berbeda dari progres belajar Anda.
        </p>
    </section>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import { Link } from "@inertiajs/vue3";
import { apiInstance } from "@/utils/axios";
const batches = ref([]),
    loading = ref(false),
    loaded = ref(false),
    error = ref(false);
const labels = {
    ready: "Siap dipelajari",
    scheduled: "Belum dimulai",
    expired: "Periode berakhir",
    completed: "Ditutup oleh trainer",
};
const instructions = {
    ready: "Mulai dari salah satu kelas berikut. Klik Lanjutkan Belajar untuk meneruskan materi terakhir.",
    scheduled:
        "Batch belum dimulai. Akses melalui batch tersedia mulai tanggal di atas.",
    expired:
        "Periode batch sudah berakhir. Hubungi trainer bila Anda masih perlu menyelesaikan pelatihan.",
    completed:
        "Trainer menandai batch ini selesai. Ini tidak berarti semua materi Anda sudah selesai. Hubungi trainer untuk mengaktifkan kembali batch jika masih perlu belajar.",
};
const blocked = {
    ready: "Akses belum tersedia. Hubungi trainer untuk memeriksa penugasan Anda.",
    scheduled:
        "Tunggu tanggal mulai atau hubungi trainer jika jadwal perlu disesuaikan.",
    expired:
        "Minta trainer memperpanjang periode dan memastikan batch berstatus Aktif.",
    completed:
        "Minta trainer mengubah status batch menjadi Aktif dan memastikan periode masih berlaku.",
};
const date = (value) =>
    new Date(`${value}T00:00:00`).toLocaleDateString("id-ID", {
        day: "numeric",
        month: "short",
        year: "numeric",
    });
let version = 0;
async function load() {
    const current = ++version;
    loading.value = true;
    error.value = false;
    try {
        const { data } = await apiInstance.get(
            "/api/training-batches/my-learning",
        );
        if (current === version) {
            batches.value = data.batches;
            loaded.value = true;
        }
    } catch {
        if (current === version) error.value = true;
    } finally {
        if (current === version) loading.value = false;
    }
}
onMounted(load);
onBeforeUnmount(() => {
    version++;
});
</script>

<style scoped>
.history-link {
    display: inline-block;
    color: #7536bd;
    font-size: 12px;
    margin: 8px 0;
    text-decoration: underline;
    text-underline-offset: 3px;
}
.learning {
    margin: 24px 0;
    padding: 24px;
    border: 1px solid #e5d9fb;
    border-radius: 20px;
    background: #faf7ff;
    color: #20243b;
}
.learning-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.eyebrow {
    color: #7f33ff;
    font-size: 11px;
    letter-spacing: 1.5px;
    font-weight: 700;
}
h2 {
    font-size: 23px;
    font-weight: 700;
}
h3 {
    font-size: 18px;
    font-weight: 600;
}
h4 {
    font-weight: 600;
}
p {
    font-size: 13px;
    line-height: 1.7;
    color: #616575;
}
.intro {
    margin: 12px 0 20px;
}
.batch {
    padding: 20px;
    border-radius: 15px;
    background: white;
    border: 1px solid #eee5fc;
    margin-top: 16px;
}
.period {
    margin: 5px 0 10px;
}
.instruction {
    padding: 12px;
    background: #f6f3fc;
    border-radius: 9px;
    margin: 12px 0;
}
.status {
    font-size: 12px;
    padding: 5px 10px;
    border-radius: 20px;
    background: #f1edf6;
    color: #655677;
}
.status.ready {
    background: #e3f5ed;
    color: #176b4a;
}
.course {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 16px 0;
    border-bottom: 1px solid #eee;
}
.course:last-child {
    border-bottom: 0;
}
.course-info {
    flex: 1;
    min-width: 0;
}
.start {
    display: inline-flex;
    justify-content: center;
    padding: 11px 16px;
    border-radius: 10px;
    background: #7f33ff;
    color: white;
    font-size: 13px;
    font-weight: 600;
    white-space: nowrap;
}
.start:hover {
    background: #6625d2;
}
.unavailable {
    font-size: 12px;
    color: #777;
}
.blocked {
    color: #966322;
    margin-top: 5px;
}
.footnote {
    font-size: 12px;
    margin-top: 16px;
}
.error {
    color: #a02035;
}
progress {
    width: 100%;
    max-width: 300px;
    height: 7px;
    accent-color: #7f33ff;
}
button {
    font-size: 12px;
    padding: 8px 12px;
    border-radius: 8px;
    background: white;
    border: 1px solid #d8c4f3;
    color: #7236c7;
}
button:disabled {
    opacity: 0.5;
}
.empty {
    padding: 15px;
    background: white;
    border-radius: 10px;
}
@media (max-width: 640px) {
    .learning {
        padding: 16px;
    }
    .batch {
        padding: 15px;
    }
    .course {
        align-items: stretch;
        flex-direction: column;
    }
    .start {
        align-self: flex-start;
    }
    h2 {
        font-size: 20px;
    }
}
</style>
