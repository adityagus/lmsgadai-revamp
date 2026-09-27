<template>
  <section class="quiz-app quiz-course-card" aria-labelledby="course-quiz-title">
    <header class="quiz-card-heading"><div class="quiz-card-icon"><img src="/assets/images/icons/quiz-purple.svg" alt="" width="32" height="32"></div><div><p class="eyebrow">Evaluasi Pembelajaran</p><h2 id="course-quiz-title">{{ quiz?.title || content.title }}</h2></div><span v-if="quiz" class="badge">{{ quiz.status }}</span></header>
    <p v-if="loading" class="empty" role="status">Menyiapkan informasi quiz...</p>
    <div v-else-if="error" class="error" role="alert">{{ error }} <button @click="load">Coba Lagi</button></div>
    <template v-else-if="quiz">
      <p v-if="quiz.description" class="muted">{{ quiz.description }}</p>
      <dl class="quiz-facts"><div><dt>Jumlah soal</dt><dd>{{ quiz.question_count }} <small>soal</small></dd></div><div><dt>Waktu pengerjaan</dt><dd>{{ quiz.duration_minutes }} <small>menit</small></dd></div><div><dt>Nilai kelulusan</dt><dd>{{ quiz.passing_grade }} <small>/ 100</small></dd></div></dl>
      <div class="quiz-schedule"><span class="muted">Periode pengerjaan</span><p v-if="quiz.no_schedule">Tanpa batas tanggal pengerjaan</p><p v-else>{{ quiz.available_from ? dateLabel(quiz.available_from) : 'Kapan saja' }} — {{ quiz.available_until ? dateLabel(quiz.available_until) : 'Tanpa batas akhir' }}</p></div>
      <div class="quiz-card-action"><div><h3>{{ actionHeading }}</h3><p class="muted">{{ actionHint }}</p></div><button v-if="action.kind==='start'" class="primary" :disabled="starting" @click="openConfirm">{{ action.label }} →</button><router-link v-else-if="action.attempt" class="button primary" :to="attemptLink(action.attempt)">{{ action.label }} →</router-link><span v-else class="badge">{{ action.label }}</span></div>
      <details v-if="completed.length" class="quiz-details"><summary>Riwayat pengerjaan ({{ completed.length }})</summary><div class="stack"><router-link v-for="a in completed" :key="a.id" class="quiz-history-link" :to="attemptLink(a.id)"><span>Pengerjaan ke-{{ a.attempt_number }}</span><span>Lihat Hasil →</span></router-link></div></details>
    </template>
    <div v-else class="quiz-unavailable"><h3>{{ !content.quiz_id ? 'Quiz sedang disiapkan' : 'Quiz belum tersedia' }}</h3><p class="muted">{{ manager ? (!content.quiz_id ? 'Pilih quiz yang akan digunakan untuk materi ini.' : 'Periksa status terbit, jadwal, dan course quiz.') : 'Trainer akan membuka akses setelah quiz siap. Anda dapat melanjutkan ke materi lainnya.' }}</p></div>
    <div v-if="manager && !loading" class="quiz-manager-tools"><span class="muted">Pengelolaan trainer</span><template v-if="content.quiz_id"><router-link :to="`/quiz/manage/${content.quiz_id}`">Edit Quiz</router-link><router-link :to="`/quiz/manage/${content.quiz_id}/preview`">Preview</router-link></template><router-link v-else class="button primary" :to="`/course/update-contents/${content.course_id}/${content.id}`">Pilih Quiz untuk Materi</router-link></div>
    <dialog ref="confirmDialog" class="quiz-native-dialog" aria-labelledby="begin-quiz-title" @cancel="cancelConfirm">
      <form method="dialog" class="stack" @submit.prevent="start">
        <div><p class="eyebrow">Sebelum mulai</p><h2 id="begin-quiz-title">Siap mengerjakan quiz?</h2></div>
        <p>{{ quiz?.question_count }} soal dengan waktu maksimal {{ quiz?.duration_minutes }} menit.</p><p class="muted">Timer langsung berjalan setelah Anda mulai, termasuk saat browser ditutup. Jawaban disimpan otomatis. Batas akhir quiz tetap mengikuti jadwal.</p>
        <p v-if="startError" class="error" role="alert">{{ startError }}</p><div class="row" style="justify-content:flex-end"><button type="button" :disabled="starting" @click="confirmDialog.close()">Belum, Kembali</button><button class="primary" :disabled="starting">{{ starting ? 'Menyiapkan...' : 'Ya, Mulai Quiz' }}</button></div>
      </form>
    </dialog>
  </section>
</template>
<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useRouter } from '@/navigation';
import { api, errorMessage, dateLabel } from '../quiz/api';
import { primaryQuizAction } from '../quiz/flow.mjs';
import '../quiz/quiz.css';
const props=defineProps({content:{type:Object,required:true}}), router=useRouter();
const quiz=ref(null), loading=ref(true), starting=ref(false), error=ref(''), startError=ref(''), manager=ref(false), confirmDialog=ref(null);
const action=computed(()=>primaryQuizAction(quiz.value));
const completed=computed(()=>(quiz.value?.attempts||[]).filter(a=>a.status!=='IN_PROGRESS'));
const actionHeading=computed(()=>({start:'Siap menguji pemahaman Anda?',resume:'Lanjutkan dari jawaban terakhir',result:'Quiz sudah dikumpulkan',unavailable:quiz.value?.status==='Belum Dimulai'?'Quiz belum dimulai':'Periode quiz sudah berakhir'}[action.value.kind]));
const actionHint=computed(()=>({start:'Pastikan koneksi stabil sebelum mulai.',resume:'Waktu tetap berjalan sejak Anda mulai.',result:'Hasil ditampilkan sesuai pengaturan trainer.',unavailable:'Periksa kembali jadwal pengerjaan di atas.'}[action.value.kind]));
const attemptLink=id=>({path:'/student/quiz/attempt/'+id,query:{course:props.content.course_id,content:props.content.id}});
let requestVersion=0, timer;
async function load(){const version=++requestVersion;try{const data=await api('get','student');if(version!==requestVersion)return;quiz.value=data.quizzes.find(q=>Number(q.id)===Number(props.content.quiz_id))||null;manager.value=['admin','trainer','teacher'].includes(data.user.role);error.value='';}catch(e){if(version===requestVersion)error.value=errorMessage(e);}finally{if(version===requestVersion)loading.value=false;}}
watch(()=>props.content.quiz_id,()=>{loading.value=true;quiz.value=null;load();},{immediate:true});
function openConfirm(){startError.value='';confirmDialog.value.showModal();}
function cancelConfirm(event){if(starting.value)event.preventDefault();}
async function start(){if(starting.value)return;starting.value=true;startError.value='';try{const data=await api('post',`${props.content.quiz_id}/start`);confirmDialog.value.close();await router.push(attemptLink(data.id));}catch(e){startError.value=errorMessage(e);await load();}finally{starting.value=false;}}
function refresh(){if(document.visibilityState==='visible')load();}
onMounted(()=>{timer=setInterval(load,30000);document.addEventListener('visibilitychange',refresh);});
onUnmounted(()=>{requestVersion++;clearInterval(timer);document.removeEventListener('visibilitychange',refresh);});
</script>
