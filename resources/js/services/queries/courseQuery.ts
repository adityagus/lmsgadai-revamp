import type { CourseContentOrder, CourseDetail, CourseList, CourseParticipants, CoursePreview, CourseSearch, Participant } from '../types/course.ts';

import type { DataResponse, DirectoryOption, Id } from '../types/common.ts';

import { apiInstance as http } from "../../utils/axios.ts";

import { defineQuery } from "../../api/query.ts";

export const getPopularCourses = defineQuery<[], { data: Array<{ id: number; name: string; description: string | null; thumbnail_url: string | null }> }>(
    "courses",
    "popular",
    async ([], signal) => (await http.get("/api/popular-courses", { signal })).data,
);

export const getCourses = defineQuery<[page?: number], CourseList>(
    "courses",
    "list",
    async ([page = 1], signal) =>
        (await http.get<CourseList>("/api/courses", { params: { page }, signal })).data,
);

export const searchContent = defineQuery<[query: string], DataResponse<CourseSearch>>(
    "courses",
    "search",
    async ([q], signal) =>
        ({
        data: (await http.get<CourseSearch>("/api/search", { params: { q }, signal })).data,
    }),
);

export const getCourseById = defineQuery<[id: Id], CoursePreview>(
    "courses",
    "preview",
    async ([id], signal) =>
        (await http.get<CoursePreview>(`/api/preview-course/${id}`, { signal })).data,
);

export const getCourseByIdPagination = defineQuery<[id: Id, page?: number], CourseDetail>(
    "courses",
    "detail",
    async ([id, page = 1], signal) =>
        (await http.get<CourseDetail>(`/api/courses/${id}`, { params: { page }, signal }))
        .data,
);

export const getCourseAccessOptions = defineQuery<[], { positions: DirectoryOption[]; }>(
    "courses",
    "access-options",
    async ([], signal) =>
        (await http.get<{
        positions: DirectoryOption[];
    }>("/api/course-access-options", { signal })).data,
);

export const getCourseParticipants = defineQuery<[id: Id], CourseParticipants>(
    "courses",
    "participants",
    async ([id], signal) =>
        (await http.get<CourseParticipants>(`/api/courses/${id}/participants`, { signal })).data,
);

export const getCourseParticipantOptions = defineQuery<[query: string], { employees: Participant[]; }>(
    "courses",
    "participant-options",
    async ([q], signal) =>
        (await http.get<{
        employees: Participant[];
    }>("/api/course-participant-options", {
        params: { q },
        signal,
    })).data,
);

export const getCourseContentOrder = defineQuery<[id: Id], CourseContentOrder>(
    "courses",
    "content-order",
    async ([id], signal) =>
        (await http.get<CourseContentOrder>(`/api/courses/${id}/content-order`, { signal })).data,
);
