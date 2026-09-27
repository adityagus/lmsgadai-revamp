<template>
  <!-- Skeleton Loading State for Edit / Create Form -->
  <div class="w-full bg-white rounded-xl shadow-lg p-8 mt-8 animate-pulse" v-if="showLoading">
    <!-- Title Skeleton -->
    <div class="h-8 bg-gray-200 rounded-lg w-56 mb-6"></div>

    <!-- Stepper Skeleton -->
    <div class="flex items-center mb-8 justify-center">
      <div class="flex items-center">
        <div class="w-8 h-8 rounded-full bg-gray-200"></div>
        <div class="h-4 w-24 bg-gray-200 rounded ml-2 mr-4"></div>
        <div class="w-8 h-1 bg-gray-200 rounded mx-2"></div>
      </div>
      <div class="flex items-center">
        <div class="w-8 h-8 rounded-full bg-gray-200"></div>
        <div class="h-4 w-20 bg-gray-200 rounded ml-2 mr-4"></div>
        <div class="w-8 h-1 bg-gray-200 rounded mx-2"></div>
      </div>
      <div class="flex items-center">
        <div class="w-8 h-8 rounded-full bg-gray-200"></div>
        <div class="h-4 w-20 bg-gray-200 rounded ml-2"></div>
      </div>
    </div>

    <!-- Form Controls Skeleton -->
    <div class="space-y-5">
      <!-- Judul Field -->
      <div>
        <div class="h-4 w-28 bg-gray-200 rounded mb-2"></div>
        <div class="h-10 w-full bg-gray-200 rounded-lg"></div>
      </div>

      <!-- Nomor Surat Field -->
      <div>
        <div class="h-4 w-32 bg-gray-200 rounded mb-2"></div>
        <div class="h-10 w-full bg-gray-200 rounded-lg"></div>
      </div>

      <!-- Tanggal Berlaku Field -->
      <div>
        <div class="h-4 w-36 bg-gray-200 rounded mb-2"></div>
        <div class="h-10 w-full bg-gray-200 rounded-lg"></div>
      </div>

      <!-- Tipe Dokumen Field -->
      <div>
        <div class="h-4 w-32 bg-gray-200 rounded mb-2"></div>
        <div class="h-10 w-full bg-gray-200 rounded-lg"></div>
      </div>

      <!-- Tipe File Field -->
      <div>
        <div class="h-4 w-24 bg-gray-200 rounded mb-2"></div>
        <div class="h-10 w-full bg-gray-200 rounded-lg"></div>
      </div>

      <!-- Content / Upload Area -->
      <div>
        <div class="h-4 w-32 bg-gray-200 rounded mb-2"></div>
        <div class="h-28 w-full bg-gray-200 rounded-lg"></div>
      </div>
    </div>

    <!-- Action Buttons Skeleton -->
    <div class="flex gap-2 mt-8">
      <div class="flex-1 h-10 bg-gray-200 rounded-lg"></div>
      <div class="flex-1 h-10 bg-gray-200 rounded-lg"></div>
    </div>
  </div>
  <div class="w-full bg-white rounded-xl shadow-lg p-4 sm:p-8 mt-4 sm:mt-8" v-else>
    <h1 class="text-xl sm:text-2xl font-bold mb-4 sm:mb-6 text-sidebar">{{ isEditMode ? 'Edit' : 'Create' }} {{ name }}</h1>
    <div class="flex items-center mb-6 justify-center overflow-x-auto pb-2 scrollbar-hidden max-w-full">
      <div v-for="(stepLabel, idx) in stepLabels" :key="idx" class="flex items-center shrink-0">
        <div
          :class="['w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center rounded-full font-bold text-xs sm:text-sm shrink-0', step === idx + 1 ? 'bg-sidebar text-white' : 'bg-gray-200 text-sidebar']">
          {{ idx + 1 }}</div>
        <span class="ml-1.5 mr-2 sm:ml-2 sm:mr-4 font-semibold text-xs sm:text-sm whitespace-nowrap" :class="step === idx + 1 ? 'text-sidebar' : 'text-gray-400'">{{ stepLabel
        }}</span>
        <span v-if="idx < stepLabels?.length - 1" class="w-4 sm:w-8 h-1 bg-gray-200 rounded mx-1 sm:mx-2 shrink-0"></span>
      </div>
    </div>
    <form @submit.prevent="onSubmit" novalidate>
      <div v-show="step === 1">
        <div class="mb-4">
          <label class="block text-xs sm:text-sm font-semibold mb-1" for="title">Judul <span class="text-red-500">*</span></label>
          <input v-model="title" id="title" type="text" class="w-full border rounded px-3 py-2 text-xs sm:text-sm focus:outline-sidebar"
            :class="{ 'border-red-500': errors.title }" :placeholder="`Masukkan judul ${name.toLowerCase()}`"
            required />
          <span v-if="errors.title" class="text-xs text-red-500 mt-1">{{ errors.title }}</span>
        </div>
        <div class="mb-4">
          <label class="block text-xs sm:text-sm font-semibold mb-1" for="no_surat">Nomor Surat <span
              class="text-red-500">*</span></label>
          <input v-model="no_surat" id="no_surat" type="text"
            class="w-full border rounded px-3 py-2 text-xs sm:text-sm focus:outline-sidebar" :class="{ 'border-red-500': errors.no_surat }"
            placeholder="Masukkan nomor surat" required />
          <span v-if="errors.no_surat" class="text-xs text-red-500 mt-1">{{ errors.no_surat }}</span>
        </div>
        <div class="mb-4">
          <label class="block text-xs sm:text-sm font-semibold mb-1" for="tgl_berlaku">Tanggal Berlaku <span
              class="text-red-500">*</span></label>
          <input v-model="tgl_berlaku" id="tgl_berlaku" type="date"
            class="w-full border rounded px-3 py-2 text-xs sm:text-sm focus:outline-sidebar"
            :class="{ 'border-red-500': errors.tgl_berlaku }" required />
          <span v-if="errors.tgl_berlaku" class="text-xs text-red-500 mt-1">{{ errors.tgl_berlaku }}</span>
        </div>
        <div class="mb-4">
          <label class="block text-xs sm:text-sm font-semibold mb-1" for="type">Tipe {{ name }} <span
              class="text-red-500">*</span></label>
          <select v-model="submenu_id" v-bind='submenuIdAttrs' id='submenu_id'
            class="w-full border rounded px-3 py-2 text-xs sm:text-sm focus:outline-sidebar"
            :class="{ 'border-red-500': errors.submenu_id }" @change='handleTypeChange(submenu_id)' required>
            <option disabled value="">Pilih Tipe {{ name }}</option>
            <option v-for="typeDoc in typeDocuments" :key="typeDoc.id" :value="typeDoc.id">{{ typeDoc.name }}</option>
          </select>
          <span v-if="errors.submenu_id" class="text-xs text-red-500 mt-1">{{ errors.submenu_id }}</span>
        </div>
        <div class="mb-4">
          <label class="block text-xs sm:text-sm font-semibold mb-1" for="type">Tipe File <span
              class="text-red-500">*</span></label>
          <select v-model="type" v-bind="typeAttrs" id="type"
            class="w-full border rounded px-3 py-2 text-xs sm:text-sm focus:outline-sidebar" :class="{ 'border-red-500': errors.type }"
            required>
            <option disabled value="">Pilih Tipe File</option>
            <option value="text">Text</option>
            <option value="pdf">PDF</option>
          </select>
          <span v-if="errors.type" class="text-xs text-red-500 mt-1">{{ errors.type }}</span>
        </div>
        <div class="flex flex-col gap-[10px]" v-if='type === "text"'>
          <label class="font-semibold text-xs sm:text-sm">Content</label>
          <div class="overflow-x-auto max-w-full">
            <ckeditor :editor="ClassicEditor" v-model="content" :config="editorConfig" @change="() => {
              const data = editor.getData();
              console.log('data adalah', data);
              content.value = data;
              console.log('Editor content changed:', content);
            }" />
          </div>

          <span class="error-message text-[#FF435A] text-xs">
            {{ errors?.text }}
          </span>
        </div>
        <div>
          <!-- Input file baru -->
          <div class="mb-4">
            <label class="block text-xs sm:text-sm font-semibold mb-1" for="file">
              Upload File PDF <span class="text-red-500">*</span>
            </label>
            <input id="file" type="file" accept="application/pdf" @change="onFileChange"
              class="w-full border rounded px-3 py-2 text-xs sm:text-sm focus:outline-sidebar" :class="{ 'border-red-500': errors.file }"
              :required="!isEditMode" />
            <span class='text-xs text-gray-500'>(Max. File size: {{ MAX_FILE_SIZE_MB }} MB)</span>
            <small class="block text-gray-500 text-xs" v-if='isEditMode'>
              Boleh dikosongkan jika tidak ingin mengganti file
            </small>
            <span v-if="errors.file" class="text-xs text-red-500 mt-1 block">
              {{ errors.file }}
            </span>
            <span v-if="fileName" class="text-xs text-gray-500 mt-1 block">
              File: {{ fileName }}
            </span>
            <!-- Tampilkan file lama jika mode edit dan file lama ada -->
            <div v-if="isEditMode && urlThumbnail" class="mb-2">
              <a :href="`/storage/aktif/${urlThumbnail}`" target="_blank" class="text-blue-500 text-xs underline">Lihat File
                Lama</a>
            </div>
          </div>
        </div>
      </div>
      <div v-show="step === 2">
        <div class="flex flex-col gap-2 text-xs sm:text-sm">
          <label class="flex items-center gap-2 font-semibold border-b border-gray-300 pb-2">
            <input type="checkbox" :checked="isAllCabangChecked" @change="toggleAllCabang" /> Pilih Semua
          </label>
          <AreaCheckbox v-for="area in areas" :key="area.id_area" :area="area" v-model:checked="regionals_id" />
        </div>
        <span v-if="regionals_id.length == 0" class="text-xs text-red-500 mt-1 block">
          Required
        </span>
      </div>
      <div v-show="step === 3">
        <div class="mb-4">
          <div class="flex flex-col gap-2 text-xs sm:text-sm">
            <label class="flex items-center gap-2 font-semibold border-b border-gray-300 pb-2">
              <input type="checkbox" :checked="isAllJabatanChecked" @change="toggleAllJabatan" /> Pilih Semua
            </label>
            <label v-for="jab in daftarJabatan" :key="jab.id" class="flex items-center gap-2">
              <input type="checkbox" :value="jab.id" v-model="kd_jabatan" /> {{ jab.nama }}
            </label>
            <span v-if="kd_jabatan.length == 0" class="text-xs text-red-500 mt-1 block">
              Required
            </span>
          </div>
        </div>
      </div>
      <div class="flex flex-col sm:flex-row gap-2 mt-6">
        <button type="button" @click="fnBack()" v-if='step == 1'
          class="w-full sm:flex-1 py-2.5 sm:py-2 text-xs sm:text-sm rounded bg-gray-200 text-sidebar font-semibold hover:bg-gray-400 transition cursor-pointer">
          Kembali
        </button>
        <button v-if="step > 1" type="button" @click="prevStep"
          class="w-full sm:flex-1 py-2.5 sm:py-2 text-xs sm:text-sm rounded bg-gray-200 text-sidebar font-semibold hover:bg-gray-300 transition cursor-pointer">Sebelumnya</button>
        <button v-if="step < 3" type="button" @click="nextStep" :disabled="!canProceedStep"
          class="w-full sm:flex-1 py-2.5 sm:py-2 text-xs sm:text-sm rounded bg-sidebar text-white font-semibold hover:bg-purple-700 disabled:opacity-50 transition cursor-pointer">
          Selanjutnya
        </button>
        <button v-if="step === 3" type="submit" :disabled="isSubmitting"
          class="w-full sm:flex-1 py-2.5 sm:py-2 text-xs sm:text-sm rounded bg-sidebar text-white font-semibold hover:bg-purple-700 disabled:opacity-50 transition cursor-pointer">
          <span v-if='!isSubmitting'>{{ isEditMode ? 'Update' : 'Simpan' }}</span>
          <div class="loader justify-items-center" v-if="isSubmitting">
            <svg aria-hidden="true" class="w-5 h-5 text-gray-200 animate-spin dark:text-gray-600 fill-white"
              viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path
                d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z"
                fill="currentColor" />
              <path
                d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z"
                fill="currentFill" />
            </svg>
            <span class="sr-only">Loading...</span>
          </div>
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import CKEditor from '@ckeditor/ckeditor5-vue';
import * as ClassicEditor from '@ckeditor/ckeditor5-build-classic';
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from '@/navigation'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { AnnouncementInfoSchema } from '@/utils/zodSchema'
import { createAnnouncement, updateAnnouncement, wizardSession, wizardStep, finishWizard, getDocumentById } from '@/services/announcementService'
import { getWilayah, getTypesByIdMenu, getJabatan, getCabang } from '@/services/masterService';
import AreaCheckbox from '@/components/AreaCheckbox.vue'
import axios from 'axios'
import { result } from 'lodash'
import Swal from 'sweetalert2'
// Select All Jabatan logic
import { computed } from 'vue';



const [route, router] = [useRoute(), useRouter()];
console.log("route", route);
const name = ref('');
const announcementId = route.params.id; // For update mode
console.log("announcementId:", announcementId);
const isEditMode = ref(!!announcementId);
console.log("isEditMode:", isEditMode);

const createType = ref(null); // default 1 kalau gak ada
console.log("Category create from route:", createType);

watch(createType, (newVal) => {
  console.log('newval', newVal);
  if (newVal == 1) {
    name.value = 'Pengumuman';
  } else if (newVal == 2) {
    name.value = 'Formulir';
  } else if (newVal == 3) {
    name.value = 'Report';
  } else {
    name.value = '';
  }
});


const regionals_arr = [];
const step = ref(1)
const stepLabels = [name, 'Wilayah', 'Jabatan']
const daftarJabatan = ref([]);
const urlThumbnail = ref('');
const ckeditor = CKEditor.component
const editorConfig = {
  toolbar: [
    "undo",
    "redo",
    "|",
    "heading",
    "|",
    "bold",
    "italic",
    "|",
    "link",
    "insertTable",
    "mediaEmbed",
    "|",
    "bulletedList",
    "numberedList",
    "indent",
    "outdent",
  ],
  initialData: content == null ? "" : content,
};


const canProceedStep = computed(() => {
  console.log('errors', errors.title);
  if (step.value === 1) {
    return (
      title.value &&
      no_surat.value &&
      tgl_berlaku.value &&
      submenu_id.value &&
      type.value &&
      ((type.value === 'text' && content.value) || (type.value === 'pdf' && (file.value || isEditMode.value))) &&
      !errors.value.title &&
      !errors.value.no_surat &&
      !errors.value.tgl_berlaku &&
      !errors.value.submenu_id &&
      !errors.value.type &&
      (type.value !== 'pdf' || !errors.file)
    );
  }
  if (step.value === 2) {
    return regionals_id.value && regionals_id.value.length > 0;
  }
  return true;
});

const nextStep = async function () {
  // Simpan data step ke session
  // let payload = {};
  // if (step.value === 1) {
  //   payload = { ...values };
  // } else if (step.value === 3) {
  //   payload = { regionals_id: regionals_id.value };
  // } else if (step.value === 2) {
  //   payload = { kd_jabatan: kd_jabatan.value };
  // }
  // await wizardStep(step.value, payload);
  if (step.value < 3) step.value++;
}

const fnBack = () => {
  Swal.fire({
    title: 'Apakah Anda yakin?',
    text: "Data yang telah diisi akan hilang!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Ya, kembali',
    cancelButtonText: 'Batal'
  }).then((result) => {
    if (result.isConfirmed) {
      router.back();
    }
  });
}

const isAllJabatanChecked = computed(() => daftarJabatan?.value.length > 0 && kd_jabatan?.value.length === daftarJabatan.value.length);

function toggleAllJabatan(e) {
  if (e.target.checked) {
    kd_jabatan.value = daftarJabatan.value.map(j => j.id);
  } else {
    kd_jabatan.value = [];
  }
}

const isAllCabangChecked = computed(() => {
  const allChildIds = [];
  areas.value.forEach(area => {
    if (area.children && area.children.length > 0) {
      area.children.forEach(child => {
        allChildIds.push(String(child.id_area));
      });
    }
  });
  const checkedStrings = (regionals_id.value || []).map(String);
  return allChildIds.length > 0 && allChildIds.every(id => checkedStrings.includes(id));
});

function toggleAllCabang(e) {
  if (e.target.checked) {
    // Pilih semua id_area dari areas dan children-nya
    const allIds = [];
    areas.value.forEach(area => {
      if (area.children && area.children.length > 0) {
        area.children.forEach(child => {
          allIds.push(String(child.id_area));
        });
      }
    });
    regionals_id.value = allIds;
  } else {
    regionals_id.value = [];
  }
}

const loadContentData = async () => {
  const data = await getDocumentById(announcementId);
  const result = data.data
  console.log('Loaded document data:', result);

  title.value = result.title;
  no_surat.value = result.no_surat;

  if (result.tgl_berlaku) {
    const rawDate = String(result.tgl_berlaku).trim();
    if (rawDate.includes('-')) {
      const parts = rawDate.split('T')[0].split('-');
      if (parts[0].length === 4) {
        tgl_berlaku.value = `${parts[0]}-${parts[1].padStart(2, '0')}-${parts[2].padStart(2, '0')}`;
      } else if (parts[2].length === 4) {
        tgl_berlaku.value = `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
      } else {
        tgl_berlaku.value = rawDate;
      }
    } else {
      tgl_berlaku.value = rawDate;
    }
  } else {
    tgl_berlaku.value = '';
  }

  submenu_id.value = result.submenu_id;
  kd_jabatan.value = (result.document_position || [])
    .map(item => item.kd_jbt) || [];
  // Ensure regionals_id values match the type used by AreaCheckbox (id_area from getCabang)
  // Keep both number and string-compatible by storing as the original type
  regionals_id.value = (result.document_regional || [])
    .map(item => item.regional_id) || [];
  urlThumbnail.value = result.url;
  content.value = result.content;
  type.value = result.type;

  console.log("regionals_id", regionals_id.value, kd_jabatan.value);

  // Set createType and name label based on loaded document's menu
  createType.value = result.menu.id_menu;
  if (createType.value === 1) {
    name.value = 'Pengumuman';
  } else if (createType.value === 2) {
    name.value = 'Formulir';
  } else if (createType.value === 3) {
    name.value = 'Report';
  } else {
    name.value = '';
  }

  console.log('kd_jabatan after loading document data:', kd_jabatan.value);

  if (step.value === 1) {
    // Load data for step 1
  } else if (step.value === 2) {
    // Load data for step 2
  } else if (step.value === 3) {
    // Load data for step 3
  }
}

function prevStep() {
  if (step.value > 1) step.value--
}
console.log("Category create from route:", createType);
const file = ref(null)
const fileName = ref('')
const uploadProgress = ref(0)
const MAX_FILE_SIZE_MB = 2

const typeDocuments = ref('')
const areas = ref([
  { id_area: 'ho', nm_area: 'HO', children: [] },
  {
    id_area: 'jaya', nm_area: 'Jaya', children: [
      { id_area: 'kl', nm_area: 'KL', children: [] },
      { id_area: 'ku', nm_area: 'Ku', children: [] }
    ]
  },
  {
    id_area: 'jabar', nm_area: 'Jabar', children: [
      { id_area: 'hi', nm_area: 'HI', children: [] },
      { id_area: 'bogor', nm_area: 'Bogor', children: [] }
    ]
  },
  {
    id_area: 'kepri', nm_area: 'Kepri', children: [
      { id_area: 'batam', nm_area: 'Batam Center', children: [] },
      { id_area: 'batuaji', nm_area: 'Batu Aji', children: [] }
    ]
  }
]);

const submenu = ref('')

const { handleSubmit, isSubmitting, values, errors, defineField, isLoading } = useForm({
  validationSchema: toTypedSchema(AnnouncementInfoSchema),
  initialValues: {
    submenu_id: '',
    title: '',
    no_surat: '',
    dokumen: null,
    type: '', // default kosong agar select bisa validasi
    content: '',
    tgl_berlaku: '',
    regionals_id: [],
    kd_jabatan: []
  }
});

// const fieldNames = [
//   'title',
//   'no_surat',
//   'tgl_berlaku',
//   'submenu',
//   'submenu_id',
//   'regionals_id',
//   'dokumen'
// ];
// const fields = {}
// fieldNames.forEach(key => {
//   const [value, attrs] = defineField(key)
//   fields[key] = { value, attrs }
// })

const [title] = defineField('title');
const [no_surat] = defineField('no_surat');
const [type, typeAttrs] = defineField('type');
const [content] = defineField('content');
const [tgl_berlaku] = defineField('tgl_berlaku');
const [submenu_id, submenuIdAttrs] = defineField('submenu_id');
const [regionals_id] = defineField('regionals_id');
const [kd_jabatan] = defineField('kd_jabatan');
const [dokumen] = defineField('dokumen');

const showLoading = ref(true);
onMounted(async () => {
  const lastStep = ref(1);
  // jika udah ke load baru dipanggil
  try {
    const [resTypes, resAreas, resJabatan, wizardRes] = await Promise.all([
      getTypesByIdMenu(createType),
      getCabang(),
      getJabatan(),
      // wizardSession()
    ]);


    daftarJabatan.value = resJabatan.map(j => ({ id: j.kd_jabatan, nama: j.nm_jabatan }));
    typeDocuments.value = resTypes;



    // cara ngambil ke database, gimana
    areas.value = resAreas.map(area => ({
      id_area: area.id_area,
      nm_area: area.nm_area,
      children: area.children ? area.children.map(child => ({
        id_area: child.id_area,
        nm_area: child.nm_area,
        children: []
      })) : []
    }));

    if (isEditMode.value) {
      await loadContentData();
      // karena createType berubah, reload types sesuai menu.id
      const resCabangEdit = await getCabang();
      areas.value = resCabangEdit.map(area => ({
        id_area: area.id_area,
        nm_area: area.nm_area,
        children: area.children ? area.children.map(child => ({
          id_area: child.id_area,
          nm_area: child.nm_area,
          children: []
        })) : []
      }));
      typeDocuments.value = await getTypesByIdMenu(createType.value);
    } else {
      createType.value = route.query.type || 1; // default 1 kalau gak ada
      typeDocuments.value = await getTypesByIdMenu(createType.value);
    }

    // const wizard = wizardRes.wizard || {};
    // if (wizard[1]) { Object.assign(values, wizard[1]); lastStep.value = 1; }
    // // Ambil regionals_id dari session wizard jika ada
    // if (wizard[2]) {
    //   // Jika regionals_id sudah ada di session, pakai itu
    //   if (wizard[2].regionals_id && wizard[2].regionals_id.length > 0) {
    //     regionals_id.value = wizard[2].regionals_id;
    //   }
    //   lastStep.value = 2;
    // }
    // if (wizard[3]) { kd_jabatan.value = wizard[3].kd_jabatan || []; lastStep.value = 3; }
    // step.value = lastStep.value;

    showLoading.value = false;
  } catch (error) {
    console.error(error);
  }
});



function handleTypeChange(submenuId) {
  submenu.value = typeDocuments.value.find(type => type.id === submenuId).name;
}


const onSubmit = handleSubmit(async () => {
  try {
    const formData = new FormData();
    formData.append('title', title.value || '');
    formData.append('no_surat', no_surat.value || '');
    formData.append('tgl_berlaku', tgl_berlaku.value || '');
    formData.append('submenu_id', submenu_id.value || '');
    formData.append('type', type.value || '');
    formData.append('content', content.value || '');

    (regionals_id.value || []).forEach(id => formData.append('regionals_id[]', id));
    (kd_jabatan.value || []).forEach(id => formData.append('kd_jabatan[]', id));

    if (file.value) {
      formData.append('dokumen', file.value, file.value.name);
      dokumen.value = file.value; // set vee-validate field at submit time
    }

    if (isEditMode.value) {
      // pass announcementId and onUploadProgress
      await updateAnnouncement(formData, announcementId, (ev) => {
        uploadProgress.value = ev.total ? Math.round((ev.loaded * 100) / ev.total) : 0;
      });
      Swal.fire({ title: "Updated!", text: "Content has been updated.", icon: "success", timer: 1500 });
    } else {
      await createAnnouncement(formData, (ev) => {
        uploadProgress.value = ev.total ? Math.round((ev.loaded * 100) / ev.total) : 0;
      });
      Swal.fire({ title: "Created!", text: "Content has been created.", icon: "success", timer: 1500 });
    }

    router.push(`/detail-pengumuman/${submenu_id.value}`);
  } catch (error) {
    console.error(error);
    Swal.fire({
      title: "Error!",
      text: `${error?.response?.data?.message || error?.message || 'An error occurred.'}`,
      icon: "error",
      timer: 2000
    });
  } finally {
    uploadProgress.value = 0;
  }
});

function onFileChange(e) {
  const f = e.target.files?.[0];
  if (!f) {
    file.value = null;
    fileName.value = '';
    return;
  }

  if ((f.size / 1024 / 1024) > MAX_FILE_SIZE_MB) {
    Swal.fire({
      title: 'File terlalu besar',
      text: `Ukuran file maksimal ${MAX_FILE_SIZE_MB} MB.`,
      icon: 'warning'
    });
    e.target.value = ''; // reset input
    file.value = null;
    fileName.value = '';
    return;
  }

  // HANYA simpan di ref lokal — jangan set dokumen.value di sini
  file.value = f;
  fileName.value = f.name;
  uploadProgress.value = 0;
  console.log('Selected file:', f.name, f.size);
}
</script>

<style scoped>
.bg-sidebar {
  background: #7F33FF;
}
</style>
