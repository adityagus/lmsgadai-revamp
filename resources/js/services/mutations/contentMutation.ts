import type { ApiMessage, Id } from '../types/common.ts';

import type { ContentPayload, ContentSaved } from '../types/course.ts';

import { apiInstance as http } from '../../utils/axios.ts';

import { defineMutation } from '../../api/mutation.ts';

const related = ['contents', 'courses', 'progress', 'quiz'];

export const createContent = defineMutation<[data: ContentPayload | FormData], ContentSaved>(
    'contents',
    'create',
    async (data) =>
        (await http.post<ContentSaved>('/contents', data)).data,
    related,
);

export const updateContent = defineMutation<[data: Partial<ContentPayload> | FormData, id: Id], ContentSaved>(
    'contents',
    'update',
    async (data, id) => {
        if (data instanceof FormData) {
            data.set('_method', 'PUT');
            return (await http.post<ContentSaved>(`/contents/${id}`, data)).data;
        }
        return (await http.put<ContentSaved>(`/contents/${id}`, data)).data;
    },
    related,
);

export const deleteContent = defineMutation<[id: Id], ApiMessage>(
    'contents',
    'delete',
    async (id) =>
        (await http.delete<ApiMessage>(`/contents/${id}`)).data,
    related,
);
