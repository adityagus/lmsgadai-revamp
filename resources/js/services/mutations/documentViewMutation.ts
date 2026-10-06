import type { ApiMessage, Id } from '../types/common.ts';

import { apiInstance as http } from '../../utils/axios.ts';

import { defineMutation } from '../../api/mutation.ts';

export const recordDocumentView = defineMutation<[documentId: Id], ApiMessage>(
    'documentViews',
    'record',
    async (documentId) =>
        (await http.post<ApiMessage>('/document-views', { document_id: documentId })).data,
    ['documentViews', 'reports', 'master', 'announcements'],
);
