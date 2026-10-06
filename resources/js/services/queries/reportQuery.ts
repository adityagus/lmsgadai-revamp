import type { DataResponse, SuccessResponse } from '../types/common.ts';

import type { DocumentReportRow } from '../types/report.ts';

import { apiInstance as http } from "../../utils/axios.ts";

import { defineQuery } from "../../api/query.ts";

export const getDocumentReport = defineQuery<[], DataResponse<SuccessResponse<DocumentReportRow[]>>>(
    "reports",
    "documents",
    async ([], signal) =>
        ({
        data: (await http.get<SuccessResponse<DocumentReportRow[]>>("/api/v1/report/documents", { signal })).data,
    }),
);
