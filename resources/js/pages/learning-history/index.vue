<template>
    <main class="history-page">
        <Link href="/batch-pelatihan" class="back">← Batch Pelatihan</Link
        ><LearningHistory
            :batch-id="route.query.batch_id"
            :username="route.query.username || ''"
        />
    </main>
</template>

<script setup>
import { Link } from "@inertiajs/vue3";
import { useRoute } from "@/navigation";
import LearningHistory from "@/components/LearningHistory.vue";
const route = useRoute();
</script>
<style scoped>
.history-page {
    padding: 24px;
}
.back {
    display: inline-block;
    color: #7834c7;
    font-size: 13px;
    margin-bottom: 20px;
}
@media (max-width: 640px) {
    .history-page {
        padding: 12px;
    }
}
</style>
