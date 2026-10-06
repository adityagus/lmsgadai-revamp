import type { AdminOverview, BranchNode, Position, Region, UserOverview } from '../types/master.ts';

import type { CourseSearch } from '../types/course.ts';

import type { DataResponse, Id } from '../types/common.ts';

import type { Submenu } from '../types/announcement.ts';

import { apiInstance as http } from "../../utils/axios.ts";

import { defineQuery } from "../../api/query.ts";

export const getWilayah = defineQuery<[], Region[]>(
    "master",
    "wilayah",
    async ([], signal) =>
        (await http.get<Region[]>("/master/wilayah", { signal })).data,
);

export const getTypesByIdMenu = defineQuery<[id: Id], Submenu[]>(
    "master",
    "types",
    async ([id], signal) =>
        (await http.get<Submenu[]>(`/master/types/${id}`, { signal })).data,
);

export const getJabatan = defineQuery<[], Position[]>(
    "master",
    "jabatan",
    async ([], signal) =>
        (await http.get<Position[]>("/master/jabatan", { signal })).data,
);

export const getCabang = defineQuery<[], BranchNode[] | { error: string; }>(
    "master",
    "cabang",
    async ([], signal) =>
        (await http.get<BranchNode[] | {
        error: string;
    }>("/master/cabang", { signal })).data,
);

export const searchContent = defineQuery<[query: string], CourseSearch>(
    "master",
    "search",
    async ([q], signal) =>
        (await http.get<CourseSearch>("/content/search", { params: { q }, signal })).data,
);

export const userOverview = defineQuery<[], DataResponse<UserOverview>>(
    "master",
    "user-overview",
    async ([], signal) =>
        ({
        data: (await http.get<UserOverview>("/api/user-overview", { signal })).data,
    }),
);

export const adminOverview = defineQuery<[], DataResponse<AdminOverview>>(
    "master",
    "admin-overview",
    async ([], signal) =>
        ({
        data: (await http.get<AdminOverview>("/api/admin-overview", { signal })).data,
    }),
);

export const getOverview = defineQuery<[], DataResponse<AdminOverview>>(
    "master",
    "overview",
    async ([], signal) =>
        ({
        data: (await http.get<AdminOverview>("/api/overview", { signal })).data,
    }),
);
