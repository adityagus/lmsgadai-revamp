import type { AxiosResponse } from 'axios';

import type { DocumentReportRow } from '../types/report.ts';

import { apiInstance as http } from '../../utils/axios.ts';

import { defineMutation } from '../../api/mutation.ts';

export const exportDocumentReport = defineMutation<[data: DocumentReportRow[]], AxiosResponse<Blob>>(
    'reports',
    'export-documents',
    async (data) =>
        (await http.post<Blob>('/api/v1/report/documents/export-excel', { data }, { responseType: 'blob' })),
    [],
);

export const exportActivityLogs = defineMutation<[], AxiosResponse<Blob>>(
    'reports',
    'export-activity',
    async () =>
        (await http.post<Blob>('/api/v1/report/activity-logs/export-excel', {}, { responseType: 'blob' })),
    [],
);
