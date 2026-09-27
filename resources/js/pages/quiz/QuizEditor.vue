<template>
  <main class="quiz-app quiz-editor stack">
    <header class="topbar scroll-">
      <div>
        <p class="eyebrow">{{ embedded ? 'Materi Pembelajaran' : 'Kelola Quiz' }}</p>
        <h1>{{ editingExisting ? 'Edit Quiz' : 'Buat Quiz' }}</h1>
        <p class="muted">Pilih satu course, isi informasi quiz, lalu tambahkan soal.</p>
      </div>
      <button type="button" :disabled="busy || modalBusy" @click="leaveEditor">{{ embedded ? 'Kembali ke Materi' : 'Kembali ke Kelas' }}</button>
    </header>
    <div v-if="error" ref="errorPanel" class="error" role="alert" tabindex="-1">{{ error }}</div>
    <p v-if="message" class="success" role="status">{{ message }}</p>
    <p v-if="loading" class="panel empty">Memuat quiz...</p>
    <template v-else-if="meta">
      <p v-if="form.has_attempts" class="quiz-notice">Quiz sudah pernah dikerjakan. Soal, jadwal, dan course dikunci untuk menjaga riwayat nilai. Pembahasan soal, jumlah kesempatan, tampilan hasil, dan pengaturan peserta khusus tetap bisa diubah.</p>

      <section class="quick-card stack" aria-labelledby="quiz-information">
        <div class="row quiz-section-heading"><h2 id="quiz-information">Informasi Quiz</h2><span class="badge">{{ statusLabel }}</span></div>
        <fieldset :disabled="form.has_attempts || busy" class="stack quiz-fieldset">
          <label class="field">Judul Quiz<input v-model="form.title" maxlength="150" placeholder="Contoh: K3 atau Produk Gadai" required><small class="muted">Maksimal 150 karakter.</small></label>
          <div class="form-grid">
            <label class="field">Durasi pengerjaan (menit)<input v-model.number="form.duration_minutes" type="number" min="1" max="1440" required></label>
            <label class="field">Nilai minimal untuk lulus<input v-model.number="form.passing_grade" type="number" :min="totalQuestionWeight" max="100" step="0.01" :aria-invalid="!!gradeError"><small class="muted">Total bobot: {{ totalQuestionWeight }} poin. Nilai lulus harus minimal {{ totalQuestionWeight }} dan maksimal 100.</small><small v-if="gradeError" class="error" role="alert">{{ gradeError }}</small></label>
          </div>
          <label class="field">Course
            <select v-model="form.course_id" :disabled="embedded || form.course_locked" required>
              <option :value="null" disabled>Pilih satu course</option>
              <option v-for="c in meta.courses" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <small class="muted">Satu quiz hanya terhubung ke satu course. Akses peserta mengikuti course dan pembatasan akses di bawah.</small>
          </label>
        </fieldset>
        <QuizAudience v-model="form.audience" :disabled="busy" />
        <label class="field quiz-attempt-field">Jumlah kesempatan mengerjakan<input v-model.number="form.maximum_attempt" :disabled="busy" type="number" min="0" max="100"><small class="muted">Jumlah Kesempatan Percobaan Pengerjaan. Tulis 0 jika tanpa batas</small></label>
        <label class="check"><input v-model="form.no_schedule" type="checkbox" :disabled="busy || form.has_attempts" @change="form.no_schedule && (form.release_after_close = false)">Tanpa batas tanggal pengerjaan</label>
        <p v-if="form.no_schedule" class="muted">Quiz dapat dikerjakan kapan saja setelah diterbitkan. Durasi dan batas kesempatan tetap berlaku.</p>
        <p v-else class="muted">Jadwal: {{ dateLabel(form.available_from) }} sampai {{ dateLabel(form.available_until) }}. Ubah melalui Pengaturan Tambahan.</p>

        <details class="quiz-details">
          <summary>Pengaturan Tambahan</summary>
          <div class="stack">
            <fieldset :disabled="form.has_attempts || busy" class="stack quiz-fieldset">
              <div class="form-grid">
                <label v-if="!form.no_schedule" class="field">Mulai tersedia<input v-model="form.available_from" type="datetime-local"></label>
                <label v-if="!form.no_schedule" class="field">Batas akhir pengerjaan<input v-model="form.available_until" type="datetime-local"></label>
                <label class="field">Nilai yang digunakan jika mengerjakan ulang<select v-model="form.grading_method"><option value="highest">Nilai tertinggi</option><option value="latest">Nilai terakhir</option><option value="average">Rata-rata nilai</option><option value="first">Nilai pertama</option></select></label>
              </div>
              <label class="field">Petunjuk pengerjaan (opsional)<textarea v-model="form.description" maxlength="20000" rows="3"></textarea></label>
              <div class="form-grid"><label class="check"><input v-model="form.randomize_question" type="checkbox">Acak urutan soal</label><label class="check"><input v-model="form.randomize_answer" type="checkbox">Acak pilihan jawaban</label></div>
              <label class="field">Jumlah soal yang dikerjakan<input v-model.number="form.question_count" type="number" min="1" :max="Math.max(1, form.question_ids.length)"><small class="muted">Biasanya semua soal. Jika lebih sedikit, soal diambil dari daftar di bawah.</small></label>
            </fieldset>
            <fieldset :disabled="busy" class="stack quiz-fieldset">
              <h3>Hasil yang dapat dilihat peserta</h3>
              <div class="form-grid"><label v-for="setting in resultSettings" :key="setting.key" class="check"><input v-model="form[setting.key]" type="checkbox" :disabled="form.no_schedule && setting.key === 'release_after_close'">{{ setting.label }}</label></div>
              <label v-if="form.status !== 'DRAFT'" class="field">Status Quiz<select v-model="form.status"><option value="PUBLISHED">Diterbitkan</option><option value="CLOSED">Ditutup</option></select><small class="muted">Quiz yang ditutup tidak dapat dimulai lagi. Pengerjaan yang sedang berjalan tetap mengikuti batas waktunya.</small></label>
            </fieldset>
            <section class="stack">
              <div><h3>Peserta Khusus</h3><p class="muted">Atur waktu atau kesempatan berbeda untuk peserta course ini. Kolom kosong mengikuti pengaturan quiz.</p></div>
              <article v-for="(o,index) in form.overrides" :key="index" class="panel stack">
                <fieldset :disabled="busy || modalBusy" class="quiz-fieldset form-grid">
                  <label class="field">Peserta<select v-model="o.student_id" :disabled="form.has_attempts && !!o.persisted"><option value="">Pilih peserta</option><option v-for="s in meta.students" :key="s.id" :value="s.id">{{ s.name }} ({{ s.username }})</option></select></label>
                  <label class="field">Tambahan waktu (menit)<input v-model.number="o.additional_minutes" type="number" min="0" max="1440"></label>
                  <label class="field">Jumlah kesempatan<input v-model="o.maximum_attempt" type="number" min="0" max="100" placeholder="Ikuti pengaturan quiz"></label>
                  <label class="field">Mulai tersedia<input v-model="o.available_from" type="datetime-local"></label>
                  <label class="field">Batas akhir pengerjaan<input v-model="o.available_until" type="datetime-local"></label>
                </fieldset>
                <button v-if="form.has_attempts" type="button" :disabled="busy || modalBusy" @click="saveOverride(o)">Simpan Pengaturan Peserta</button>
                <button v-else type="button" :disabled="busy" @click="form.overrides.splice(index,1)">Hapus Peserta Khusus</button>
              </article>
              <button type="button" :disabled="busy" @click="form.overrides.push(newOverride())">Tambah Peserta Khusus</button>
            </section>
          </div>
        </details>
      </section>

      <section class="quick-card stack" aria-labelledby="quiz-question-list">
        <div class="row quiz-section-heading"><div><h2 id="quiz-question-list">Daftar Soal</h2><p class="muted">{{ quizQuestions.length }} soal ditambahkan.</p></div></div>
        <div v-if="!form.has_attempts" class="row">
          <button type="button" class="primary" :disabled="busy" @click="openQuestion()">Tambah Soal</button>
          <button type="button" :disabled="busy" @click="openModal('bank')">Pilih dari Bank Soal</button>
          <button type="button" :disabled="busy" @click="openModal('generate')">Ambil Soal Acak</button>
          <button type="button" :disabled="busy" @click="openModal('bulk')">Tambah Banyak Soal</button>
        </div>
        <p v-if="!quizQuestions.length" class="quiz-empty">Belum ada soal. Buat soal baru atau pilih dari bank soal.</p>
        <article v-for="(q,index) in quizQuestions" :key="q.id" class="question-card-item stack">
          <div class="row quiz-section-heading"><h3 class="prewrap">{{ index + 1 }}. {{ q.question }}</h3><span class="muted">{{ typeLabels[q.type] }} · {{ q.weight }} poin</span></div>
          <div v-if="q.options.length" class="quick-choice-grid"><div v-for="(o,i) in q.options" :key="i" class="quick-choice-item" :class="{correct:!!o.is_correct, incorrect:!o.is_correct}"><span class="letter">{{ letter(i) }}</span><span class="prewrap">{{ o.text }}</span><small>{{ o.is_correct ? 'Jawaban benar' : 'Jawaban salah' }}</small></div></div>
          <p v-else class="muted">Jawaban uraian dinilai oleh pengajar.</p>
          <div class="quiz-explanation stack">
            <label class="field">Pembahasan soal<textarea v-model="q.explanation" :disabled="busy" maxlength="20000" placeholder="Jelaskan alasan jawaban yang benar." @input="explanationDirty.add(q.id)"></textarea></label>
            <button type="button" :disabled="busy || !explanationDirty.has(q.id)" @click="saveExplanation(q)">Simpan Pembahasan</button>
          </div>
          <div v-if="!form.has_attempts" class="row quiz-question-actions">
            <button type="button" :disabled="busy" @click="openQuestion(q,index)">Edit Soal</button>
            <button type="button" :disabled="busy" @click="openQuestion(q,null)">Salin Soal</button>
            <button type="button" :disabled="busy" @click="replaceQuestion(index)">Ganti dengan Soal Lain</button>
            <button type="button" class="danger" :disabled="busy" @click="removeQuestion(index)">Hapus dari Quiz</button>
          </div>
        </article>
      </section>

      <footer class="panel quiz-editor-actions">
        <div class="stack quiz-footer-status"><span class="save-status-pill" :class="saveStatus" role="status" aria-live="polite">{{ saveLabel }}</span><small class="muted">{{ form.status === 'DRAFT' ? 'Perubahan disimpan otomatis sebagai draft.' : 'Klik Simpan Perubahan setelah selesai mengedit.' }}</small></div>
        <div class="row">
          <button v-if="id && !embedded" type="button" :disabled="busy || modalBusy || !form.question_ids.length" @click="preview">Lihat Pratinjau</button>
          <button v-if="form.status === 'DRAFT'" type="button" :disabled="busy || modalBusy" @click="saveManual('DRAFT')">{{ embedded ? 'Simpan & Gunakan Quiz' : 'Simpan Draft' }}</button>
          <button v-if="form.status === 'DRAFT'" type="button" class="primary" :disabled="busy || modalBusy" @click="requestPublish">Terbitkan Quiz</button>
          <button v-else type="button" class="primary" :disabled="busy || modalBusy" @click="saveManual(form.status)">{{ busy ? 'Menyimpan...' : 'Simpan Perubahan' }}</button>
        </div>
      </footer>
    </template>

    <QuizDialog v-if="modal" :title="modalTitle" :busy="modalBusy || busy" @close="closeModal">
      <div v-if="modalError" class="error" role="alert">{{ modalError }}</div>
      <template v-if="modal === 'question'">
        <form class="stack" @submit.prevent="saveQuestion(false)">
          <fieldset :disabled="modalBusy" class="stack quiz-fieldset">
            <label class="field">Pertanyaan<textarea v-model="questionForm.question" maxlength="20000" rows="3" autofocus required></textarea></label>
            <label class="field">Jenis soal<select v-model="questionForm.type" @change="changeQuestionType"><option v-for="(label,type) in typeLabels" :key="type" :value="type">{{ label }}</option></select></label>
            <div v-if="questionForm.type !== 'essay'" class="stack">
              <p class="muted">{{ questionForm.type === 'multiple_answer' ? 'Centang semua jawaban yang benar.' : 'Pilih satu jawaban yang benar.' }}</p>
              <div v-for="(o,i) in questionForm.options" :key="i" class="quiz-option-input" :class="{correct:o.is_correct, incorrect:questionForm.options.some(option => option.is_correct) && !o.is_correct}">
                <input :type="questionForm.type === 'multiple_answer' ? 'checkbox' : 'radio'" name="quiz-correct-answer" :checked="o.is_correct" :aria-label="'Jawaban benar: ' + letter(i)" @change="setCorrect(i,$event.target.checked)">
                <label class="field"><span>Pilihan {{ letter(i) }}</span><input v-model="o.text" maxlength="5000" :readonly="questionForm.type === 'true_false'" required></label>
                <button v-if="questionForm.type !== 'true_false' && questionForm.options.length > 2" type="button" :aria-label="'Hapus pilihan ' + letter(i)" @click="questionForm.options.splice(i,1)">Hapus</button>
              </div>
              <button v-if="questionForm.type !== 'true_false'" type="button" :disabled="questionForm.options.length >= 26" @click="questionForm.options.push({text:'',is_correct:false})">Tambah Pilihan</button>
            </div>
            <div class="form-grid"><label class="field">Poin soal<input v-model.number="questionForm.weight" type="number" min="0.01" max="10000" step="0.01" required></label><label class="field">Kategori (opsional)<input v-model="questionForm.category" maxlength="255" placeholder="Contoh: Produk Gadai"></label></div>
            <label class="field">Penjelasan jawaban (opsional)<textarea v-model="questionForm.explanation" maxlength="20000" placeholder="Jelaskan alasan jawaban yang benar."></textarea><small class="muted">Ditampilkan pada hasil jika Tampilkan penjelasan diaktifkan.</small></label>
            <details class="quiz-details"><summary>Pengaturan Soal</summary><div class="stack"><label class="field">Bank soal<select v-model="questionForm.bank_id"><option :value="null">Bank soal utama</option><option v-for="b in meta.banks" :key="b.id" :value="b.id">{{ b.name }}</option></select></label><label class="field">Tingkat kesulitan<select v-model="questionForm.difficulty"><option value="easy">Mudah</option><option value="medium">Sedang</option><option value="hard">Sulit</option></select></label></div></details>
          </fieldset>
          <div class="row quiz-modal-actions"><button type="button" :disabled="modalBusy" @click="closeModal">Batal</button><button v-if="editingQuestionIndex === null" type="button" :disabled="modalBusy" @click="saveQuestion(true)">Simpan & Tambah Soal Lagi</button><button type="submit" class="primary" :disabled="modalBusy">{{ modalBusy ? 'Menyimpan...' : 'Simpan Soal' }}</button></div>
        </form>
      </template>
      <template v-else-if="modal === 'bank'">
        <div class="stack"><label class="field">Cari soal<input v-model="questionSearch" type="search" placeholder="Ketik pertanyaan atau kategori"></label><label class="field">Bank soal<select v-model="bankFilter"><option value="">Semua bank soal</option><option v-for="b in meta.banks" :key="b.id" :value="b.id">{{ b.name }}</option></select></label><div class="quiz-bank-list"><label v-for="q in filteredBankQuestions" :key="q.id" class="check quiz-question-choice"><input v-model="bankSelection" type="checkbox" :value="q.id" :disabled="form.question_ids.includes(q.id)"><span>{{ q.question }}<small class="muted">{{ form.question_ids.includes(q.id) ? 'Sudah ditambahkan' : typeLabels[q.type] }}</small></span></label><p v-if="!filteredBankQuestions.length" class="muted">Tidak ada soal yang sesuai.</p></div><div class="row quiz-modal-actions"><button type="button" @click="closeModal">Batal</button><button type="button" class="primary" :disabled="!bankSelection.length" @click="addSelectedQuestions">Tambahkan {{ bankSelection.length }} Soal</button></div></div>
      </template>
      <template v-else-if="modal === 'generate'">
        <form class="stack" @submit.prevent="generateQuestions">
          <p class="muted">Soal dipilih secara acak dari bank soal. Daftar soal quiz saat ini akan diganti.</p>
          <label class="field">Bank soal<select v-model="genForm.bank_id"><option value="">Semua bank soal saya</option><option v-for="b in meta.banks" :key="b.id" :value="b.id">{{ b.name }}</option></select></label>
          <label class="field">Jumlah soal<input v-model.number="genForm.count" type="number" min="1" max="500" required></label>
          <label class="field">Kategori (opsional)<input v-model="genForm.category" placeholder="Contoh: Produk Gadai"></label>
          <label class="field">Tingkat kesulitan<select v-model="genForm.difficulty"><option value="">Semua tingkat</option><option value="easy">Mudah</option><option value="medium">Sedang</option><option value="hard">Sulit</option></select></label>
          <div class="row quiz-modal-actions"><button type="button" :disabled="modalBusy" @click="closeModal">Batal</button><button type="submit" class="primary" :disabled="modalBusy">{{ modalBusy ? 'Memilih soal...' : 'Ambil Soal' }}</button></div>
        </form>
      </template>
      <template v-else-if="modal === 'bulk'">
        <div class="stack">
          <p class="muted">Tempel pertanyaan beserta pilihan jawabannya. Akhiri setiap soal dengan KUNCI: A (atau huruf jawaban yang benar).</p>
          <label class="field">Teks soal<textarea v-model="bulkText" rows="10" :placeholder="bulkExample" :disabled="modalBusy"></textarea></label>
          <div v-if="bulkPreview.errors.length" class="error" role="alert"><p v-for="issue in bulkPreview.errors" :key="issue">{{ issue }}</p></div>
          <div v-if="bulkPreview.questions.length" class="bulk-preview-box stack"><h3>Pratinjau {{ bulkPreview.questions.length }} Soal</h3><article v-for="(q,i) in bulkPreview.questions" :key="i"><strong>{{ i+1 }}. {{ q.question }}</strong><p v-for="(o,j) in q.options" :key="j" class="muted">{{ letter(j) }}. {{ o.text }}{{ o.is_correct ? ' (Jawaban benar)' : '' }}</p></article></div>
          <div class="row quiz-modal-actions"><button type="button" :disabled="modalBusy" @click="closeModal">Batal</button><button type="button" class="primary" :disabled="modalBusy || !bulkPreview.questions.length || !!bulkPreview.errors.length" @click="importQuestions">{{ modalBusy ? 'Menyimpan...' : 'Tambahkan ' + bulkPreview.questions.length + ' Soal' }}</button></div>
        </div>
      </template>
      <template v-else-if="modal === 'publish'">
        <p class="muted">Quiz <strong>{{ form.title }}</strong> dapat dikerjakan melalui course yang dipilih sesuai jadwal.</p>
        <p class="quiz-summary">{{ form.question_count }} soal · {{ form.duration_minutes }} menit · {{ selectedCourseName }}<span v-if="form.overrides.length"> · {{ form.overrides.length }} peserta khusus</span></p>
        <div class="row quiz-modal-actions"><button type="button" :disabled="busy" @click="closeModal">Batal</button><button type="button" class="primary" :disabled="busy" @click="saveManual('PUBLISHED')">{{ busy ? 'Menerbitkan...' : 'Terbitkan' }}</button></div>
      </template>
    </QuizDialog>
  </main>
</template>

<script setup>
import QuizAudience from "./QuizAudience.vue";
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { useRoute, useRouter, onBeforeRouteLeave } from '@/navigation';
import { api, errorMessage, localInput, isoDate, dateLabel } from './api';
import { quizStepError, passingGradeError } from './flow.mjs';
import { createSaveQueue, parseBulkQuestions, questionPayload } from './authoring.mjs';
import QuizDialog from './QuizDialog.vue';
import './quiz.css';

const props = defineProps({ courseId: { type: [Number, String], default: null }, quizId: { type: [Number, String], default: null } });
const emit = defineEmits(['saved', 'cancel']);
const route = useRoute(), router = useRouter();
const embedded = computed(() => !!props.courseId);
const id = ref(props.quizId || (embedded.value || route.params.id === 'new' ? null : route.params.id));
const editingExisting = ref(!!id.value);
const meta = ref(null), allQuestions = ref([]), error = ref(''), errorPanel = ref(null), message = ref('');
const loading = ref(true), busy = ref(false), modalBusy = ref(false), saveStatus = ref('empty');
const modal = ref(''), modalError = ref('');
const form = ref({
  title:'', description:'', course_id:Number(props.courseId || route.query.course_id) || null,
  duration_minutes:45, passing_grade:75, maximum_attempt:1, grading_method:'highest', question_count:1,
  no_schedule:false, available_from:localInput(new Date().toISOString()), available_until:localInput(new Date(Date.now()+30*86400000).toISOString()),
  status:'DRAFT', randomize_question:true, randomize_answer:false,
  show_score:true, show_correct_answer:false, show_wrong_answer:false, show_explanation:false, show_total_correct:true, show_passing_status:true, release_after_close:false,
  audience:{positions:[],branches:[]},
  class_ids:[], question_ids:[], overrides:[], has_attempts:false,
});
const resultSettings = [
  {key:'show_score',label:'Tampilkan nilai'}, {key:'show_passing_status',label:'Tampilkan status kelulusan'},
  {key:'show_total_correct',label:'Tampilkan jumlah jawaban benar'}, {key:'show_correct_answer',label:'Tampilkan kunci jawaban'},
  {key:'show_wrong_answer',label:'Tampilkan jawaban yang salah'}, {key:'show_explanation',label:'Tampilkan penjelasan'},
  {key:'release_after_close',label:'Tampilkan hasil setelah jadwal quiz berakhir'},
];
const statusLabel = computed(() => ({DRAFT:'Draft',PUBLISHED:'Diterbitkan',CLOSED:'Ditutup'})[form.value.status]);
const saveLabel = computed(() => ({empty:'Belum disimpan',pending:'Ada perubahan belum disimpan',saving:'Menyimpan...',saved:'Tersimpan',error:'Gagal menyimpan. Coba simpan kembali.'})[saveStatus.value]);
const selectedCourseName = computed(() => meta.value?.courses.find(c => Number(c.id)===Number(form.value.course_id))?.name || 'Course belum dipilih');
const quizQuestions = computed(() => form.value.question_ids.map(id => allQuestions.value.find(q => Number(q.id)===Number(id))).filter(Boolean));
const totalQuestionWeight = computed(() => Math.round(quizQuestions.value.reduce((sum, q) => sum + Number(q.weight), 0) * 100) / 100);
const gradeError = computed(() => passingGradeError(form.value.passing_grade, quizQuestions.value));
const typeLabels = {multiple_choice:'Pilihan ganda',multiple_answer:'Pilihan lebih dari satu',true_false:'Benar atau salah',essay:'Uraian'};
const letter = i => String.fromCharCode(65+i);
const questionForm = ref({}), editingQuestionIndex = ref(null);
const explanationDirty = ref(new Set());
watch(explanationDirty, () => {if(explanationDirty.value.size)saveStatus.value='pending';}, {deep:true});
const questionSearch = ref(''), bankFilter = ref(''), bankSelection = ref([]);
const genForm = ref({bank_id:'',count:20,category:'',difficulty:''});
const bulkText = ref('');
const bulkExample = '1. Barang yang dapat dijadikan jaminan gadai adalah?\nA. Emas\nB. Buku\nC. Kertas\nD. Plastik\nKUNCI: A';
const bulkPreview = computed(() => parseBulkQuestions(bulkText.value));
const filteredBankQuestions = computed(() => allQuestions.value.filter(q => (!bankFilter.value || Number(q.bank_id)===Number(bankFilter.value)) && `${q.question} ${q.category || ''}`.toLowerCase().includes(questionSearch.value.trim().toLowerCase())));
const modalTitle = computed(() => ({question:editingQuestionIndex.value===null?'Tambah Soal':'Edit Soal',bank:'Pilih dari Bank Soal',generate:'Ambil Soal Acak',bulk:'Tambah Banyak Soal',publish:'Terbitkan quiz ini sekarang?'})[modal.value]);
const newOverride = () => ({student_id:'',additional_minutes:0,maximum_attempt:'',available_from:'',available_until:''});
const serializeOverride = o => ({student_id:Number(o.student_id),additional_minutes:Number(o.additional_minutes),maximum_attempt:o.maximum_attempt==='' || o.maximum_attempt==null?null:Number(o.maximum_attempt),available_from:isoDate(o.available_from),available_until:isoDate(o.available_until)});
const draftStorageKey = () => `quiz-draft:${meta.value.user.id}:${props.courseId || route.query.course_id || 'general'}`;
function rememberDraft(value) { try { value ? sessionStorage.setItem(draftStorageKey(),String(value)) : sessionStorage.removeItem(draftStorageKey()); } catch {} }
function showError(text) { error.value=text; nextTick(() => errorPanel.value?.focus()); }

let timer, revision=0, savedRevision=0, applying=false, disposed=false;
const enqueueSave = createSaveQueue();
watch(form, () => {
  if (loading.value || applying) return;
  revision++;
  saveStatus.value='pending'; message.value='';
  clearTimeout(timer);
  if (form.value.status==='DRAFT' && !form.value.has_attempts) timer=setTimeout(autoSave,900);
}, {deep:true,flush:'sync'});

function validation(status) {
  if (form.value.has_attempts) return Number.isInteger(Number(form.value.maximum_attempt)) && Number(form.value.maximum_attempt)>=0 && Number(form.value.maximum_attempt)<=100 ? '' : 'Jumlah kesempatan harus berupa angka 0 sampai 100.';
  for (const step of status==='DRAFT' ? [0] : [0,1,2]) { const issue=quizStepError(form.value,step); if (issue) return issue; }
  return gradeError.value;
}
async function persist(status) {
  const issue=validation(status); if (issue) throw new Error(issue);
  const version=revision;
  const payload={...form.value,class_ids:[],title:form.value.title.trim(),status,available_from:form.value.no_schedule?null:isoDate(form.value.available_from),available_until:form.value.no_schedule?null:isoDate(form.value.available_until),overrides:form.value.overrides.map(serializeOverride)};
  saveStatus.value='saving';
  if (form.value.has_attempts) {
    const settings={status,maximum_attempt:form.value.maximum_attempt,overrides:payload.overrides,audience:payload.audience};
    resultSettings.forEach(s => settings[s.key]=form.value[s.key]);
    await api('patch',`manage/${id.value}/visibility`,settings);
  } else {
    const data=await api(id.value?'put':'post',id.value?`manage/${id.value}`:'manage',payload);
    const created=!id.value; id.value=data.id;
    if (created && !embedded.value && !disposed) await router.replace('/quiz/manage/'+data.id);
  }
  applying=true;
  form.value.status=status;
  if (revision===version) form.value.title=payload.title;
  applying=false;
  savedRevision=version;
  saveStatus.value=revision===version && !explanationDirty.value.size?'saved':'pending';
  rememberDraft(status==='DRAFT'?id.value:null);
}
const failureMessage = e => e.response || e.request ? errorMessage(e) : e.message;
async function autoSave() {
  if (disposed || busy.value || !form.value.title.trim() || form.value.status!=='DRAFT') return;
  try {
    await enqueueSave(async () => {
      if (disposed || form.value.status!=='DRAFT' || savedRevision===revision) return;
      await persist('DRAFT'); error.value='';
    });
  } catch(e) { saveStatus.value='error'; error.value=failureMessage(e); }
}
async function saveManual(status, finish=true) {
  if (busy.value || modalBusy.value) return false;
  const wasDraft=form.value.status==='DRAFT';
  clearTimeout(timer); busy.value=true; error.value=''; modalError.value='';
  try {
    for (const q of quizQuestions.value.filter(q => explanationDirty.value.has(q.id))) await persistExplanation(q);
    await enqueueSave(() => persist(status));
    message.value=status==='DRAFT'?'Draft quiz disimpan.':status==='CLOSED'?'Quiz ditutup.':wasDraft?'Quiz berhasil diterbitkan.':'Perubahan quiz disimpan.';
    modal.value='';
    if (embedded.value && finish) { rememberDraft(null); emit('saved',{id:id.value,title:form.value.title}); }
    return true;
  } catch(e) {
    saveStatus.value='error';
    if (modal.value) modalError.value=failureMessage(e); else showError(failureMessage(e));
    return false;
  } finally { busy.value=false; }
}
function requestPublish() { const issue=validation('PUBLISHED'); if(issue) return showError(issue); openModal('publish'); }
async function preview() { if(await saveManual(form.value.status,false)) router.push(`/quiz/manage/${id.value}/preview`); }
async function leaveEditor() {
  if ((revision!==savedRevision || explanationDirty.value.size) && !(await saveManual(form.value.status,false))) return;
  rememberDraft(null);
  if (embedded.value) emit('cancel'); else router.push(form.value.course_id ? '/kelas/'+form.value.course_id : '/kelas');
}
async function saveOverride(o) {
  modalBusy.value=true; error.value='';
  try { await api('post',`manage/${id.value}/overrides`,serializeOverride(o)); o.persisted=true; message.value='Pengaturan peserta disimpan untuk pengerjaan berikutnya.'; }
  catch(e) { showError(failureMessage(e)); } finally { modalBusy.value=false; }
}
function openModal(name) { modalError.value=''; modal.value=name; if(name==='bank') bankSelection.value=[]; }
function closeModal() { if(!busy.value && !modalBusy.value) { modal.value=''; modalError.value=''; } }
function blankQuestion() { return {bank_id:null,question:'',type:'multiple_choice',weight:1,category:'',difficulty:'medium',explanation:'',options:Array.from({length:4},()=>({text:'',is_correct:false}))}; }
function openQuestion(q=null,index=null) {
  if(form.value.has_attempts) return;
  editingQuestionIndex.value=index;
  questionForm.value=q?{...q,options:q.options.map(o=>({text:o.text,is_correct:!!o.is_correct}))}:blankQuestion();
  openModal('question');
}
function changeQuestionType() {
  const q=questionForm.value;
  if(q.type==='essay') q.options=[];
  else if(q.type==='true_false') q.options=[{text:'Benar',is_correct:false},{text:'Salah',is_correct:false}];
  else if(!q.options.length) q.options=blankQuestion().options;
  if(q.type!=='multiple_answer') { let selected=false; q.options.forEach(o=>{o.is_correct=!!o.is_correct&&!selected; selected=selected||o.is_correct;}); }
}
function setCorrect(index,checked) { questionForm.value.options.forEach((o,i)=>{if(questionForm.value.type==='multiple_answer') {if(i===index)o.is_correct=checked;} else o.is_correct=i===index;}); }
function cacheQuestions(items) { items.forEach(q=>{const index=allQuestions.value.findIndex(x=>Number(x.id)===Number(q.id)); if(index<0)allQuestions.value.push(q); else allQuestions.value[index]=q;}); }
function setQuestions(ids) {
  for(const id of explanationDirty.value) {if(!ids.includes(id))explanationDirty.value.delete(id);}
  const previous=form.value.question_ids.length;
  const usedAll=form.value.question_count===previous || previous===0;
  form.value.question_ids=ids;
  if(usedAll || form.value.question_count>ids.length) form.value.question_count=Math.max(1,ids.length);
}
async function saveQuestion(another) {
  if(modalBusy.value || form.value.has_attempts) return;
  modalError.value='';
  const payload=questionPayload(questionForm.value,form.value.course_id);
  if(!payload.question) {modalError.value='Tuliskan pertanyaan terlebih dahulu.';return;}
  if(payload.type!=='essay') {
    if(payload.options.length<2 || payload.options.some(o=>!o.text)) {modalError.value='Isi setiap pilihan jawaban atau hapus pilihan yang tidak digunakan.';return;}
    const correct=payload.options.filter(o=>o.is_correct).length;
    if(!correct || (payload.type!=='multiple_answer' && correct!==1)) {modalError.value='Pilih jawaban yang benar.';return;}
  }
  modalBusy.value=true;
  try {
    // A new version keeps questions already used elsewhere and past answers intact.
    const data=await api('post','questions',payload);
    cacheQuestions([{...payload,id:data.id}]);
    const ids=[...form.value.question_ids];
    if(editingQuestionIndex.value===null)ids.push(data.id); else {explanationDirty.value.delete(ids[editingQuestionIndex.value]);ids[editingQuestionIndex.value]=data.id;}
    setQuestions(ids);
    if(another) {questionForm.value=blankQuestion(); editingQuestionIndex.value=null; await nextTick(); document.querySelector('.quiz-author-dialog textarea')?.focus();}
    else modal.value='';
  } catch(e) {modalError.value=failureMessage(e);} finally {modalBusy.value=false;}
}
async function persistExplanation(q) {
  if(form.value.has_attempts) {
    await api('patch',`manage/${id.value}/questions/${q.id}/explanation`,{explanation:q.explanation || ''});
    explanationDirty.value.delete(q.id);
    return;
  }
  const payload=questionPayload(q,form.value.course_id);
  const data=await api('post','questions',payload);
  cacheQuestions([{...payload,id:data.id}]);
  setQuestions(form.value.question_ids.map(id=>id===q.id?data.id:id));
  explanationDirty.value.delete(q.id);
}
async function saveExplanation(q) {
  if(busy.value)return;
  busy.value=true;error.value='';
  try {await persistExplanation(q);await enqueueSave(() => persist(form.value.status));message.value='Pembahasan soal disimpan.';}
  catch(e) {showError(failureMessage(e));} finally {busy.value=false;}
}
function removeQuestion(index) {if(!form.value.has_attempts){explanationDirty.value.delete(form.value.question_ids[index]);setQuestions(form.value.question_ids.filter((_,i)=>i!==index));}}
function addSelectedQuestions() {setQuestions([...new Set([...form.value.question_ids,...bankSelection.value])]);closeModal();}
async function replaceQuestion(index) {
  if(busy.value || form.value.has_attempts)return;
  busy.value=true; error.value='';
  const old=quizQuestions.value[index];
  try {
    const data=await api('post','questions/replace-one',{exclude_ids:form.value.question_ids,bank_id:old.bank_id || null,course_id:old.course_id || null,category:old.category || null,difficulty:old.difficulty});
    cacheQuestions([data.question]); const ids=[...form.value.question_ids];ids[index]=data.question.id;setQuestions(ids);
    message.value=`Soal nomor ${index+1} sudah diganti.`;
  } catch(e) {showError(failureMessage(e));} finally {busy.value=false;}
}
async function generateQuestions() {
  if(modalBusy.value)return;modalBusy.value=true;modalError.value='';
  try {
    const data=await api('post','generate',{bank_id:genForm.value.bank_id || null,course_id:form.value.course_id,buckets:[{count:genForm.value.count,category:genForm.value.category,difficulty:genForm.value.difficulty}]});
    const fetched=await api('get','questions');cacheQuestions(fetched);setQuestions(data.question_ids);form.value.question_count=data.question_ids.length;modal.value='';
  } catch(e) {modalError.value=failureMessage(e);} finally {modalBusy.value=false;}
}
async function importQuestions() {
  if(modalBusy.value || bulkPreview.value.errors.length || !bulkPreview.value.questions.length)return;
  modalBusy.value=true;modalError.value='';
  try {
    const data=await api('post','questions/bulk',{bank_id:null,course_id:form.value.course_id,questions:bulkPreview.value.questions});
    cacheQuestions(data.questions);setQuestions([...form.value.question_ids,...data.question_ids]);bulkText.value='';modal.value='';
  } catch(e) {modalError.value=failureMessage(e);} finally {modalBusy.value=false;}
}
function beforeUnload(e) {if(revision!==savedRevision || explanationDirty.value.size || modalBusy.value) {e.preventDefault();e.returnValue='';}}
onMounted(async()=>{
  window.addEventListener('beforeunload',beforeUnload);
  try {
    [meta.value,allQuestions.value]=await Promise.all([api('get','metadata'),api('get','questions')]);
    if(!id.value) {
      try {
        const remembered=sessionStorage.getItem(draftStorageKey());
        if(remembered) {const draft=await api('get',`manage/${remembered}`);if(draft.status==='DRAFT' && Number(draft.course_id)===Number(form.value.course_id)) {id.value=draft.id;message.value='Draft terakhir dibuka kembali.';} else rememberDraft(null);}
      } catch {rememberDraft(null);}
    }
    if(id.value) {
      const data=await api('get',`manage/${id.value}`);
      data.no_schedule=!!data.no_schedule;data.available_from=localInput(data.available_from);data.available_until=localInput(data.available_until);
      [...resultSettings.map(s=>s.key),'randomize_question','randomize_answer'].forEach(k=>data[k]=!!data[k]);
      data.overrides=data.overrides.map(o=>({...o,persisted:true,available_from:localInput(o.available_from),available_until:localInput(o.available_until)}));
      for(const q of allQuestions.value) {if(Object.prototype.hasOwnProperty.call(data.question_explanations || {},q.id))q.explanation=data.question_explanations[q.id];}
      form.value=data;saveStatus.value='saved';
      if(!embedded.value && String(route.params.id)!==String(id.value))await router.replace('/quiz/manage/'+id.value);
    }
  } catch(e) {showError(failureMessage(e));} finally {loading.value=false;}
});
onBeforeUnmount(()=>{disposed=true;clearTimeout(timer);window.removeEventListener('beforeunload',beforeUnload);});
onBeforeRouteLeave(async()=>{
  if(busy.value || modalBusy.value)return false;
  if((revision!==savedRevision || explanationDirty.value.size) && !(await saveManual(form.value.status,false)))return false;
  rememberDraft(null);
});
</script>
