import type { SessionResponse } from '../types/auth.ts';

import { apiInstanceAuth as http } from "../../utils/axios.ts";

import { defineQuery } from "../../api/query.ts";

export const getSession = defineQuery<[], SessionResponse>(
    "auth",
    "session",
    async ([], signal) =>
        (await http.get<SessionResponse>("/session", { signal })).data,
);
