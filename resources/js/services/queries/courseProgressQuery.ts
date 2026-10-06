import type { CourseProgress } from '../types/course.ts';

import type { Id } from '../types/common.ts';

import { apiInstance as http } from "../../utils/axios.ts";

import { defineQuery } from "../../api/query.ts";

export const getCourseProgress = defineQuery<[id: Id], CourseProgress>(
    "progress",
    "detail",
    async ([id], signal) =>
        (await http.get<CourseProgress>(`/api/courses/${id}/progress`, { signal })).data,
);
