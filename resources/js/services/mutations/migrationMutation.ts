import { apiInstance as http } from '../../utils/axios.ts';

import { defineMutation } from '../../api/mutation.ts';

export const migrateData = defineMutation<[data: FormData], string>(
    'migration',
    'upload',
    async (data) =>
        (await http.post<string>('/api/migrasi-data', data)).data,
    ['courses', 'contents', 'categories', 'announcements', 'documentViews', 'reports', 'master', 'quiz', 'progress'],
);
