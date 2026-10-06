import type { CsrfResponse, LoginPayload, LoginResponse } from '../types/auth.ts';

import { apiInstanceAuth as http } from '../../utils/axios.ts';

import { defineMutation } from '../../api/mutation.ts';

import { clearApiCache } from '../../api/queryClient.ts';

export const login = defineMutation<[data: LoginPayload], LoginResponse>(
    'auth',
    'login',
    async (data) => {
        const { data: session } = await http.get<CsrfResponse>('/csrf-token');
        const response = await http.post<LoginResponse>('/login', data, { headers: { 'X-CSRF-TOKEN': session.csrf_token } });
        clearApiCache();
        return response.data;
    },
    [],
);

export const signOut = defineMutation<[], string>(
    'auth',
    'logout',
    async () => {
        const response = await http.post<string>('/logout');
        clearApiCache();
        return response.data;
    },
    [],
);
