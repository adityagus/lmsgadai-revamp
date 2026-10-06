import type { AssistantResponse } from '../types/assistant.ts';

import type { AxiosResponse } from 'axios';

import axios from 'axios';

import { defineMutation } from '../../api/mutation.ts';

// Legacy widget remains disabled (empty token); never attach LMS credentials.
const http = axios.create({ withCredentials: false, timeout: 15000 });

export const sendAssistantMessage = defineMutation<[message: string, token: string], AxiosResponse<AssistantResponse>>(
    'assistant',
    'send',
    async (message, token) => {
        if (!token)
            throw new Error('Layanan asisten belum dikonfigurasi.');
        return http.post<AssistantResponse>('https://openrouter.ai/api/v1/chat/completions', {
            model: 'deepseek/deepseek-r1-0528:free', messages: [{ role: 'user', content: message }],
        }, { headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' } });
    },
    [],
);
