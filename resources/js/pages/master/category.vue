<template>
	<div class="p-0 w-full min-h-screen bg-gray-50">
		<div class="w-full max-w-6xl mx-auto py-8 px-4">
			<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
				<h1 class="text-3xl font-bold tracking-tight text-gray-800">Category Management</h1>
				<button @click="openAddModal"
					class="bg-blue-600 text-white px-6 py-2 rounded-lg shadow hover:bg-blue-700 transition">Add
					Category</button>
			</div>

			<div class="bg-white rounded-xl shadow border overflow-x-auto">
				<table class="w-full min-w-[500px]">
					<thead>
						<tr class="bg-gray-100 text-gray-700">
							<th class="py-3 px-4 border-b text-center w-16">#</th>
							<th class="py-3 px-4 border-b text-left">Name</th>
							<th class="py-3 px-4 border-b text-left">Keterangan</th>
							<th class="py-3 px-4 border-b text-center w-40">Actions</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="(cat, idx) in categories" :key="cat.id" class="hover:bg-gray-50">
							<td class="py-2 px-4 border-b text-center">{{ idx + 1 }}</td>
							<td class="py-2 px-4 border-b">{{ cat.name }}</td>
							<td class="py-2 px-4 border-b">{{ cat.description }}</td>
							<td class="py-2 px-4 border-b text-center">
								<button @click="openEditModal(cat)"
									class="inline-flex items-center text-blue-600 hover:text-blue-800 font-semibold mr-3">
									<svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
											d="M15.232 5.232l3.536 3.536M9 13h3l8-8a2.828 2.828 0 10-4-4l-8 8v3z">
										</path>
									</svg>
									Edit
								</button>
								<button @click="deleteCategory(cat.id)"
									class="inline-flex items-center text-red-600 hover:text-red-800 font-semibold">
									<svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
											d="M6 18L18 6M6 6l12 12"></path>
									</svg>
									Delete
								</button>
							</td>
						</tr>
						<tr v-if="categories.length === 0">
							<td colspan="3" class="py-6 text-center text-gray-400">No categories found.</td>
						</tr>
					</tbody>
				</table>
			</div>

			<div v-if="error" class="mt-4 text-red-600 text-center">{{ error }}</div>
			<div v-if="success" class="mt-4 text-green-600 text-center">{{ success }}</div>
			<div v-if="loading" class="mt-4 text-center text-gray-500">Loading...</div>
		</div>

		<!-- Modal -->
		<div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
			<div class="bg-white rounded-xl shadow-lg p-8 w-full max-w-lg mx-2 relative animate-fadeIn">
				<h2 class="text-xl font-bold mb-4">{{ editMode ? 'Edit' : 'Add' }} Category</h2>
				<form @submit.prevent="editMode ? updateCategory() : addCategory()">
					<div class="mb-5">
						<label class="block mb-1 font-medium">Name</label>
						<input v-model="form.name" type="text"
							class="w-full border px-4 py-2 rounded focus:outline-none focus:ring focus:ring-blue-200"
							required />
					</div>
					<div class="flex justify-end gap-2">
						<button type="button" @click="closeModal"
							class="px-5 py-2 bg-gray-200 rounded hover:bg-gray-300">Cancel</button>
						<button type="submit" :disabled="modalLoading"
							class="px-5 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-60">{{
								modalLoading ? (editMode ? 'Updating...' : 'Adding...') : (editMode ? 'Update' : 'Add')
							}}</button>
					</div>
				</form>
				<button @click="closeModal"
					class="absolute top-2 right-2 text-gray-400 hover:text-gray-700 text-2xl">&times;</button>
				<div v-if="modalError" class="mt-4 text-red-600 text-center">{{ modalError }}</div>
			</div>
		</div>
	</div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useQuery } from '@tanstack/vue-query';
import { getCategories } from '../../services/courseService';

const categories = ref([]);
const loading = ref(false);
const showModal = ref(false);
const editMode = ref(false);
const form = ref({ id: null, name: '' });
const error = ref('');
const success = ref('');
const modalError = ref('');
const modalLoading = ref(false);


// const {data, isPending} = useQuery(
//   ['getCategories'], fetchCategories
// );

// console.log('getCategories', data, isPending);

function fetchCategories() {
	loading.value = true;
	error.value = '';
	axios.get('/api/categories')
		.then(res => {
			// console.log(res.data);
			categories.value = res.data;
		})
		.catch(() => {
			categories.value = [];
			error.value = 'Failed to fetch categories.';
		})
		.finally(() => {
			loading.value = false;
		});
}

function openAddModal() {
	form.value = { id: null, name: '' };
	editMode.value = false;
	showModal.value = true;
	modalError.value = '';
}

function openEditModal(cat) {
	form.value = { id: cat.id, name: cat.name };
	editMode.value = true;
	showModal.value = true;
	modalError.value = '';
}

function closeModal() {
	showModal.value = false;
	modalError.value = '';
}

function addCategory() {
	modalLoading.value = true;
	modalError.value = '';
	axios.post('/api/categories', { name: form.value.name })
		.then(() => {
			fetchCategories();
			closeModal();
			success.value = 'Category added successfully!';
			setTimeout(() => success.value = '', 2000);
		})
		.catch(err => {
			modalError.value = err.response?.data?.message || 'Failed to add category.';
		})
		.finally(() => {
			modalLoading.value = false;
		});
}

function updateCategory() {
	modalLoading.value = true;
	modalError.value = '';
	axios.put(`/api/categories/${form.value.id}`, { name: form.value.name })
		.then(() => {
			fetchCategories();
			closeModal();
			success.value = 'Category updated successfully!';
			setTimeout(() => success.value = '', 2000);
		})
		.catch(err => {
			modalError.value = err.response?.data?.message || 'Failed to update category.';
		})
		.finally(() => {
			modalLoading.value = false;
		});
}

function deleteCategory(id) {
	if (confirm('Are you sure you want to delete this category?')) {
		loading.value = true;
		axios.delete(`/api/categories/${id}`)
			.then(() => {
				fetchCategories();
				success.value = 'Category deleted successfully!';
				setTimeout(() => success.value = '', 2000);
			})
			.catch(() => {
				error.value = 'Failed to delete category.';
			})
			.finally(() => {
				loading.value = false;
			});
	}
}

onMounted(fetchCategories);
</script>

<style scoped>
table th,
table td {
	white-space: nowrap;
}

.animate-fadeIn {
	animation: fadeIn .2s;
}

@keyframes fadeIn {
	from {
		opacity: 0;
		transform: translateY(20px);
	}

	to {
		opacity: 1;
		transform: translateY(0);
	}
}
</style>
