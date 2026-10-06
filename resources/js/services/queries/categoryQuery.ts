import type { Category } from '../types/course.ts';

import { apiInstance as http } from "../../utils/axios.ts";

import { defineQuery } from "../../api/query.ts";

export const getCategories = defineQuery<[], Category[]>(
    "categories",
    "list",
    async ([], signal) =>
        (await http.get<Category[]>("/api/categories", { signal })).data,
);
