import type { CourseContent } from '../types/course.ts';

import type { Id } from '../types/common.ts';

import { apiInstance as http } from '../../utils/axios.ts';

import { defineQuery } from '../../api/query.ts';

export const getContentsByCourseId = defineQuery<[id: Id], CourseContent[]>(
    'contents',
    'list',
    async ([id], signal) =>
        (await http.get<CourseContent[]>(`/course/contents/${id}`, { signal })).data,
);

export const getContentById = defineQuery<[id: Id], CourseContent>(
    'contents',
    'detail',
    async ([id], signal) =>
        (await http.get<CourseContent>(`/contents/${id}`, { signal })).data,
);
