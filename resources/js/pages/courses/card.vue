<template>
  <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition">
    <div class="card flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-5">
      <div class="flex shrink-0 w-full sm:w-[140px] h-[160px] sm:h-[110px] rounded-[16px] sm:rounded-[20px] bg-[#D9D9D9] overflow-hidden">
        <img :src="imageUrl" class="w-full h-full object-cover" alt="thumbnail" />
      </div>
      <div class="w-full flex-1">
        <router-link :to="{ name: readOnly ? 'content-preview' : 'courseDetail', params: { id } }" class='cursor-pointer'>
          <h3
            class="font-bold text-lg sm:text-xl leading-snug sm:leading-[30px] hover:text-[#662FFF] transition-colors">
            {{ name }}
          </h3>
        </router-link>
        <div class="flex items-center gap-5 mt-2">
          <div class="flex items-center gap-[6px]">
            <img src="/assets/images/icons/crown-purple.svg" class="w-4 h-4 sm:w-5 sm:h-5" alt="icon" />
            <p class="text-[#838C9D] text-xs sm:text-sm font-medium">{{ category }}</p>
          </div>
        </div>
      </div>
      <div class="flex justify-between sm:justify-end items-center gap-2 sm:gap-3 pt-2 sm:pt-0 border-t sm:border-t-0 border-gray-100" v-if="!readOnly">
        <button type='button' @click="deleteCourse(`${id}`)"
          class="flex-1 sm:flex-none w-fit rounded-full bg-red-500 hover:bg-red-600 text-white px-4 sm:px-5 py-2.5 sm:py-3 font-semibold text-xs sm:text-sm text-nowrap transition cursor-pointer text-center">
          Hapus
        </button>
        <RouterLink :to="`/kelas/${id}`"
          class="flex-1 sm:flex-none w-fit rounded-full border border-[#060A23] hover:bg-[#060A23] hover:text-white px-4 sm:px-5 py-2.5 sm:py-3 font-semibold text-xs sm:text-sm text-nowrap transition text-center">
          Kelola
        </RouterLink>
      </div>
    </div>
  </div>
</template>

<!-- <script setup lang='ts'>

defineOptions({
  name: 'CardCourse'
})

const props = withDefaults(defineProps<{
  id?: any,
  imageUrl?: any,
  name?: any,
  totalStudent?: any,
  category?: any
}>(), {
  id: 1,
  imageUrl: '/assets/images/thumbnails/th-2.png',
  name: 'HTMX JavaScript 2020',
  totalStudent: '554',
  category: 'Marketing'
})
console.log(props);
</script> -->

<script setup>
import axios from "axios";
import { Button } from "primevue";
import { RouterLink } from "@/navigation";

const emit = defineEmits(['delete-course'])

const deleteCourse = (id) => {
  emit('delete-course', id);  // Emit the delete-course event with the course ID
};

// const deleteCourse = async (id) => {
//   console.log('tesss');
//   Swal.fire({
//   title: "Are you sure?",
//   text: "You won't be able to revert this course!",
//   icon: "warning",
//   height: 100,
//   showCancelButton: true,
//   confirmButtonColor: "#3085d6",
//   cancelButtonColor: "#d33",
//   confirmButtonText: "Yes, delete it!"
// }).then(async (result) => {
//   if (result.isConfirmed) {

//      try {
//       const result = await axios.delete(`http://127.0.0.1:8000/api/kelas/${id}`)

//       if(result.data.status == 200){
//         Swal.fire({
//           title: "Deleted!",
//           text: "Your file has been deleted.",
//           icon: "success",
//           timer: 1500
//         });
//       }

//       throw new Error({'message': "Tidak dapat dikirim"});


//     } catch (error) {
//       console.log("ERROR", error);
//     } 
//   }
// });
// }


defineProps({
  id: {
    type: Number,
    default: 1
  },
  imageUrl: {
    type: String,
    default: '/assets/images/thumbnails/th-2.png'
  },
  name: {
    type: String,
    default: "HTMX JavaScript 2020"
  },
  totalStudent: {
    type: String,
    default: "320"
  },
  category: {
    type: String,
    default: "Programming"
  },
  readOnly: {
    type: Boolean,
    default: false
  },
})
</script>