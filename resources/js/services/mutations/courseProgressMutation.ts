import type { CourseProgress } from '../types/course.ts';

import type { Id } from '../types/common.ts';

import { apiInstance as http } from '../../utils/axios.ts';

import { defineMutation } from '../../api/mutation.ts';

export const completeCourseContent = defineMutation<[id: Id, contentId: Id], CourseProgress>(
    'progress',
    'complete',
    async (id, contentId) =>
        (await http.post<CourseProgress>(`/api/courses/${id}/progress/${contentId}/complete`)).data,
    ['progress', 'quiz', 'courses'],
);
