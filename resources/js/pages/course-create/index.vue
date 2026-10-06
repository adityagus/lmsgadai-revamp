<script setup>
import { getCourseAccessOptions } from '@/services/queries/courseQuery.ts';
import Swal from "sweetalert2";
import { reactive, ref, defineProps, onMounted } from "vue";
import { useForm, useField } from 'vee-validate';
import { createCourseSchema, updateCourseSchema } from "@/utils/zodSchema";
import { toTypedSchema } from '@vee-validate/zod';
import { useMutation } from "@tanstack/vue-query";
import { createCourse, updateCourse } from '@/services/mutations/courseMutation.ts';
import { getCategories } from '@/services/queries/categoryQuery.ts';
import { getCourseById } from '@/services/queries/courseQuery.ts';
import { useRoute, useRouter } from "@/navigation";


const categories = ref([])
const file = ref(null);
const imageUrl = ref(null);
const inputFileRef = ref(null);
const accessPositions = ref([]);
const selectedAccessPositions = ref([]);
const accessSearch = ref('');


const router = useRouter();
const route = useRoute();
const id = route.params.courseId;
console.log("Course ID:", id);
const edit = route.params.courseId ? true : false;
const course = ref({
  name: "",
  tagline: "",
  categoryId: null,
  description: "",
  is_popular: 0,
});

const triggerFileInput = () => {
  event.preventDefault(); // Prevent default action
  inputFileRef.value?.click(); // Trigg
  // inputFileRef.value?.click();
};

const mutateCreate = useMutation(createCourse.options())

const mutateUpdate = useMutation(updateCourse.options())

// Function untuk menangani perubahan pada file input
const onFileChange = (event) => {
  console.log("File changed:", event.target.files[0]);
  const selectedFile = event.target.files[0];
  if (selectedFile) {
    file.value = selectedFile;
    imageUrl.value = URL.createObjectURL(selectedFile);
    thumbnail.value = selectedFile;
  }
};

// Function untuk menghapus gambar
const removeImage = () => {
  file.value = null;
  imageUrl.value = null;
  thumbnail.value = null;
  if (inputFileRef.value) {
    inputFileRef.value.value = '';
  }
};


const { values, errors, handleSubmit, isSubmitting, defineField, register, setFieldValue } = useForm({
  validationSchema: toTypedSchema(edit ? updateCourseSchema : createCourseSchema),
  initialValues: {
    name: course.value.name || '',
    tagline: course.value.tagline || '',
    // thumbnail tidak required saat edit
    thumbnail: edit ? undefined : (course.value.thumbnail_url || null),
    categoryId: course.value.categoryId || null,
    description: course.value.description || '',
    is_popular: course.value.is_popular || 0,
  },
});
const [name, nameAttrs] = defineField('name');
const [tagline, taglineAttrs] = defineField('tagline');
const [thumbnail, thumbnailAttrs] = defineField('thumbnail');
const [categoryId, categoryIdAttrs] = defineField('categoryId');
const [description, descriptionAttrs] = defineField('description');
const [isPopular, isPopularAttrs] = defineField('is_popular');


const onSubmit = handleSubmit(async () => {
  const formData = new FormData();
  try {
    // Gunakan values dari form untuk memastikan data terbaru
    formData.append('name', values.name);
    formData.append('slug', values.name.toLowerCase().replace(/\s+/g, '-'));
    // Hanya kirim thumbnail jika ada file baru
    if (file.value) {
      formData.append('thumbnail', file.value);
      console.log('file.value', file.value);
    }
    formData.append('tagline', values.tagline);
    formData.append('description', values.description);
    formData.append('students', JSON.stringify([1, 2, 3]));
    formData.append('details', JSON.stringify({ info: 'Additional details' }));
    formData.append('category_id', values.categoryId);
    formData.append('is_popular', values.is_popular ? 1 : 0);
    selectedAccessPositions.value.forEach((position) => formData.append('access_positions[]', position));

    for (let [key, value] of formData.entries()) {
      console.log(`FormData ${key}:`, value);  // Log each key-value pair to ensure all fields are included
    }

    // Debug: Tampilkan perbedaan reactive values vs form values
    console.log("Reactive values:", {
      name: name.value,
      tagline: tagline.value,
      categoryId: categoryId.value,
      description: description.value,
      isPopular: isPopular.value
    });
    console.log("Form values:", values);

    if (edit) {
      await mutateUpdate.mutateAsync([formData, id]);
    } else {
      await mutateCreate.mutateAsync([formData]);
    }


    await Swal.fire({
      title: "Berhasil!",
      text: `Kelas berhasil ${edit ? 'diperbarui' : 'dibuat'}.`,
      icon: "success",
      confirmButtonColor: '#662FFF',
      timer: 1500,
    });
    router.push('/lms');
  } catch (err) {
    console.dir(err);
    Swal.fire({
      title: "Gagal!",
      text: err.message || 'Terjadi kesalahan saat menyimpan kelas.',
      icon: "error",
      confirmButtonColor: '#662FFF',
      timer: 2000,
    });
  }
});

onMounted(async () => {
  try {
    const fetchCategories = await getCategories();
    categories.value = fetchCategories;

    if (route.params.courseId) {
      const fetchedCourse = await getCourseById(route.params.courseId);
      console.log("Fetched Course:", fetchedCourse);
      // Assign ke field form
      name.value = fetchedCourse.course?.name;
      tagline.value = fetchedCourse.course?.tagline;
      thumbnail.value = undefined; // thumbnail tidak required saat edit
      categoryId.value = fetchedCourse.course?.category_id;
      description.value = fetchedCourse.course?.description;
      isPopular.value = fetchedCourse.course?.is_popular;
      // Untuk thumbnail preview dari server
      if (fetchedCourse.course?.thumbnail) {
        imageUrl.value = fetchedCourse.course?.thumbnail_url;
        file.value = null;
      }
      selectedAccessPositions.value = fetchedCourse.access_positions || [];
    }

    const accessResponse = await getCourseAccessOptions();
    accessPositions.value = accessResponse.positions || [];


  } catch (error) {
    console.log(error);
  }
});


</script>

<template>
  <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 sm:gap-[30px] mb-4 sm:mb-6">
    <div>
      <h1 class="font-extrabold text-2xl sm:text-[28px] leading-tight sm:leading-[42px]">
        {{ !edit ? "Create " : "Update" }} Course
      </h1>
      <p class="text-[#838C9D] text-xs sm:text-base mt-1">{{ !edit ? "Create " : "Update" }} new future for company</p>
    </div>
  </header>
  <form @submit.prevent='onSubmit' class="flex flex-col w-full max-w-[550px] rounded-2xl sm:rounded-[30px] p-4 sm:p-[30px] gap-4 sm:gap-[30px] bg-[#F8FAFB]">
    <div class="flex flex-col gap-2">
      <label htmlFor="title" class="font-semibold text-xs sm:text-base">Course Name</label>
      <div
        class="flex items-center w-full rounded-full border border-[#CFDBEF] gap-2 sm:gap-3 px-4 sm:px-5 transition-all duration-300 focus-within:ring-2 focus-within:ring-[#662FFF] bg-white"
        :class='{ "ring-2 ring-[#FF435A]": errors?.name }'>
        <img src="/assets/images/icons/note-favorite-black.svg" class="w-5 h-5 sm:w-6 sm:h-6 shrink-0" alt="icon" />
        <input type="text" name="title" id="title"
          class="appearance-none outline-none w-full py-2.5 sm:py-3 text-xs sm:text-sm font-semibold placeholder:font-normal placeholder:text-[#838C9D] !bg-transparent"
          placeholder="Write better name for your course" v-model='name' v-bind="nameAttrs" />
      </div>
      <span class="error-message text-[#FF435A] text-xs">
        {{ errors?.name }}
      </span>
    </div>
    <fieldset class="flex flex-col gap-3 border-t border-[#CFDBEF] pt-5">
      <legend class="font-semibold text-xs sm:text-base">Akses kelas berdasarkan jabatan</legend>
      <p class="text-[#838C9D] text-xs sm:text-sm">Kosongkan jika semua jabatan boleh mengikuti kelas ini.</p>
      <input v-model="accessSearch" type="search" placeholder="Cari jabatan..."
        class="w-full rounded-full border border-[#CFDBEF] px-4 py-3 text-xs sm:text-sm outline-none focus:ring-2 focus:ring-[#662FFF]" />
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto rounded-2xl border border-[#CFDBEF] p-3 bg-white">
        <label v-for="position in accessPositions.filter(item => (item.label + ' ' + item.value).toLowerCase().includes(accessSearch.toLowerCase()))"
          :key="position.value" class="flex items-center gap-2 text-xs sm:text-sm cursor-pointer">
          <input type="checkbox" :value="String(position.value)" v-model="selectedAccessPositions" class="accent-[#662FFF]" />
          <span>{{ position.label }} <small class="text-[#838C9D]">({{ position.value }})</small></span>
        </label>
        <p v-if="!accessPositions.length" class="text-[#838C9D] text-xs sm:text-sm">Pilihan jabatan belum tersedia.</p>
      </div>
      <small class="text-[#838C9D] text-xs">{{ selectedAccessPositions.length ? selectedAccessPositions.length + ' jabatan dipilih' : 'Semua jabatan' }}</small>
    </fieldset>
    <div class="relative flex flex-col gap-2">
      <label htmlFor="thumbnail" class="font-semibold text-xs sm:text-base">Add a Thumbnail</label>
      <div id="thumbnail-preview-container" :class="[
        'relative flex shrink-0 w-full h-[180px] sm:h-[200px] rounded-2xl sm:rounded-[20px] border border-[#CFDBEF] overflow-hidden bg-white',
        errors.thumbnail ? 'ring-2 ring-[#FF435A]' : ''
      ]">
        <button @click='triggerFileInput' type="button" id="trigger-input"
          :class="['absolute top-0 left-0 w-full h-full flex justify-center items-center gap-2.5 sm:gap-3 z-0', imageUrl ? 'hidden' : '']">
          <img src="/assets/images/icons/gallery-add-black.svg" class="w-5 h-5 sm:w-6 sm:h-6" alt="icon" />
          <span class="text-[#838C9D] text-xs sm:text-sm font-medium">Add an attachment</span>
        </button>
        <img id="thumbnail-preview" :src="imageUrl" :class="['w-full h-full object-cover', !imageUrl ? 'hidden' : '']"
          alt="thumbnail" />

        <!-- Button untuk ganti gambar -->
        <button type="button" @click="triggerFileInput"
          :class="['absolute top-[10px] left-[10px] bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 rounded-lg z-10 text-xs sm:text-sm font-medium cursor-pointer', !imageUrl ? 'hidden' : '']">
          Ganti Gambar
        </button>

        <!-- Button untuk hapus gambar -->
        <button type="button" @click="removeImage" id="delete-preview"
          :class="['absolute right-[10px] bottom-[10px] w-10 h-10 sm:w-12 sm:h-12 rounded-full z-10 bg-red-500 hover:bg-red-600 flex items-center justify-center cursor-pointer', !imageUrl ? 'hidden' : '']">
          <img src="/assets/images/icons/delete.svg" alt="delete" class="w-5 h-5 sm:w-6 sm:h-6" />
        </button>
      </div>
      <span class="error-message text-[#FF435A] text-xs">
        {{ errors?.thumbnail }}
      </span>
      <input ref="inputFileRef" @change='onFileChange' type="file" id="thumbnail" accept="image/*"
        v-bind='thumbnailAttrs' class="absolute bottom-0 left-1/4 -z-10" />
    </div>
    <div class="flex flex-col gap-2">
      <label htmlFor="tagline" class="font-semibold text-xs sm:text-base">Course Tagline</label>
      <div
        class="flex items-center w-full rounded-full border border-[#CFDBEF] gap-2 sm:gap-3 px-4 sm:px-5 transition-all duration-300 focus-within:ring-2 focus-within:ring-[#662FFF] bg-white"
        :class='{ "ring-2 ring-[#FF435A]": errors?.tagline }'>
        <img src="/assets/images/icons/bill-black.svg" class="w-5 h-5 sm:w-6 sm:h-6 shrink-0" alt="icon" />
        <input type="text" name="tagline" id="tagline"
          class="appearance-none outline-none w-full py-2.5 sm:py-3 text-xs sm:text-sm font-semibold placeholder:font-normal placeholder:text-[#838C9D] !bg-transparent"
          placeholder="Write tagline for better copy" v-model='tagline' v-bind="taglineAttrs" />
      </div>
      <span class="error-message text-[#FF435A] text-xs">
        {{ errors?.tagline }}
      </span>
    </div>
    <div class="flex flex-col gap-2">
      <label htmlFor="category" class="font-semibold text-xs sm:text-base">Select Category</label>
      <div
        class="flex items-center w-full rounded-full border border-[#CFDBEF] gap-2 sm:gap-3 px-4 sm:px-5 transition-all duration-300 focus-within:ring-2 focus-within:ring-[#662FFF] bg-white"
        :class='{ "ring-2 ring-[#FF435A]": errors?.categoryId }'>
        <img src="/assets/images/icons/bill-black.svg" class="w-5 h-5 sm:w-6 sm:h-6 shrink-0" alt="icon" />
        <select name="category" id="category"
          class="appearance-none outline-none w-full py-2.5 sm:py-3 px-1 -mx-1 text-xs sm:text-sm font-semibold placeholder:font-normal placeholder:text-[#838C9D] !bg-transparent cursor-pointer"
          v-model="categoryId" v-bind="categoryIdAttrs">
          <option :value="categoryId == null ? categoryId : ''" selected>
            Choose content type
          </option>
          <option :value="parseInt(category.id)" :key="category.id" v-for='category in categories'>{{ category.name }}
          </option>
        </select>
        <img src="/assets/images/icons/arrow-down.svg" class="w-5 h-5 sm:w-6 sm:h-6 shrink-0 pointer-events-none" alt="icon" />
      </div>
      <span class="error-message text-[#FF435A] text-xs">
        {{ errors?.categoryId }}
      </span>
    </div>
    <div class="flex flex-col gap-2">
      <label htmlFor="desc" class="font-semibold text-xs sm:text-base">Description</label>
      <div
        class="flex w-full rounded-2xl border border-[#CFDBEF] gap-2 sm:gap-3 p-4 sm:p-5 transition-all duration-300 focus-within:ring-2 focus-within:ring-[#662FFF] bg-white"
        :class='{ "ring-2 ring-[#FF435A]": errors?.description }'>
        <img src="/assets/images/icons/note-black.png" class="w-5 h-5 sm:w-6 sm:h-6 shrink-0 mt-0.5" alt="icon" />
        <textarea name="desc" id="desc" rows="4"
          class="appearance-none outline-none w-full text-xs sm:text-sm font-semibold placeholder:font-normal placeholder:text-[#838C9D] !bg-transparent"
          placeholder="Explain what this course about" v-model="description" v-bind='descriptionAttrs'></textarea>
      </div>
      <span class="error-message text-[#FF435A] text-xs">
        {{ errors?.description }}
      </span>
    </div>
    <div class="flex flex-col sm:flex-row items-center gap-3 sm:gap-[14px] mt-2">
      <router-link to="/kelas"
        class="w-full rounded-full border border-[#060A23] py-3 sm:p-[14px_20px] font-semibold text-nowrap flex items-center justify-center text-xs sm:text-sm hover:bg-gray-50 transition-colors">
        Cancel
      </router-link>
      <button type="submit" :disabled="isSubmitting"
        class="w-full rounded-full py-3 sm:p-[14px_20px] font-semibold text-white bg-[#662FFF] text-nowrap text-xs sm:text-sm hover:bg-[#5521cc] transition-colors cursor-pointer">
        {{ !edit ? "Create " : "Update" }} Now
      </button>
    </div>
  </form>
</template>
