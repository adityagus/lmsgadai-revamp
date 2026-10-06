import type { ApiMessage, Id } from '../types/common.ts';

import type { CategoryPayload, CategorySaved } from '../types/course.ts';

import { apiInstance as http } from '../../utils/axios.ts';

import { defineMutation } from '../../api/mutation.ts';

const related = ['categories', 'courses', 'quiz', 'master'];

export const createCategory = defineMutation<[data: CategoryPayload], CategorySaved>(
    'categories',
    'create',
    async (data) =>
        (await http.post<CategorySaved>('/api/categories', data)).data,
    related,
);

export const updateCategory = defineMutation<[id: Id, data: CategoryPayload], CategorySaved>(
    'categories',
    'update',
    async (id, data) =>
        (await http.put<CategorySaved>(`/api/categories/${id}`, data)).data,
    related,
);

export const removeCategory = defineMutation<[id: Id], ApiMessage>(
    'categories',
    'delete',
    async (id) =>
        (await http.delete<ApiMessage>(`/api/categories/${id}`)).data,
    related,
);
