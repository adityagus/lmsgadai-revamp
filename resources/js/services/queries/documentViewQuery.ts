import type { DocumentReader, DocumentViewStat } from '../types/report.ts';

import type { Id, SuccessResponse } from '../types/common.ts';

import { apiInstance as http } from "../../utils/axios.ts";

import { defineQuery } from "../../api/query.ts";

export const checkDocumentViewed = defineQuery<[id: Id], SuccessResponse<{ has_viewed: boolean; }>>(
    "documentViews",
    "check",
    async ([id], signal) =>
        (await http.get<SuccessResponse<{
        has_viewed: boolean;
    }>>(`/document-views/document/${id}/check`, { signal }))
        .data,
);

export const getUnviewedUsers = defineQuery<[id: Id], SuccessResponse<DocumentReader[]>>(
    "documentViews",
    "unviewed",
    async ([id], signal) =>
        (await http.get<SuccessResponse<DocumentReader[]>>(`/document-views/document/${id}/unviewed`, { signal }))
        .data,
);

export const getViewedUsers = defineQuery<[id: Id], SuccessResponse<DocumentReader[]>>(
    "documentViews",
    "viewed",
    async ([id], signal) =>
        (await http.get<SuccessResponse<DocumentReader[]>>(`/document-views/document/${id}/viewed`, { signal }))
        .data,
);

export const getDocumentViewStats = defineQuery<[], SuccessResponse<DocumentViewStat[]>>(
    "documentViews",
    "stats",
    async ([], signal) =>
        (await http.get<SuccessResponse<DocumentViewStat[]>>("/document-views/stats", { signal })).data,
);
