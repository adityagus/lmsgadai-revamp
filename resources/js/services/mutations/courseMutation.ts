import type { ApiMessage, Id } from '../types/common.ts';

import type { ContentOrderPayload, CoursePayload, CourseSaved } from '../types/course.ts';

import { apiInstance as http } from '../../utils/axios.ts';

import { defineMutation } from '../../api/mutation.ts';

const related = ['courses', 'contents', 'progress', 'quiz', 'master'];

export const createCourse = defineMutation<[data: CoursePayload | FormData], CourseSaved>(
    'courses',
    'create',
    async (data) =>
        (await http.post<CourseSaved>('/api/courses', data)).data,
    related,
);

export const updateCourse = defineMutation<[data: Partial<CoursePayload> | FormData, id: Id], CourseSaved>(
    'courses',
    'update',
    async (data, id) => {
        if (data instanceof FormData) {
            data.set('_method', 'PUT');
            return (await http.post<CourseSaved>(`/api/courses/${id}`, data)).data;
        }
        return (await http.put<CourseSaved>(`/api/courses/${id}`, data)).data;
    },
    related,
);

export const deleteCourse = defineMutation<[id: Id], ApiMessage>(
    'courses',
    'delete',
    async (id) =>
        (await http.delete<ApiMessage>(`/api/courses/${id}`)).data,
    related,
);

export const addCourseParticipant = defineMutation<[id: Id, username: string], ApiMessage>(
    'courses',
    'add-participant',
    async (id, username) =>
        (await http.post<ApiMessage>(`/api/courses/${id}/participants`, { username })).data,
    related,
);

export const removeCourseParticipant = defineMutation<[id: Id, username: string], ApiMessage>(
    'courses',
    'remove-participant',
    async (id, username) =>
        (await http.delete<ApiMessage>(`/api/courses/${id}/participants`, { data: { username } })).data,
    related,
);

export const saveCourseContentOrder = defineMutation<[id: Id, data: ContentOrderPayload], ApiMessage>(
    'courses',
    'content-order',
    async (id, data) =>
        (await http.patch<ApiMessage>(`/api/courses/${id}/content-order`, data)).data,
    related,
);
