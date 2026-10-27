import type { ApiMessage, Id } from '../types/common.ts';
import type { PositionRole, PositionRolePayload } from '../types/master.ts';
import { apiInstance as http } from '../../utils/axios.ts';
import { defineMutation } from '../../api/mutation.ts';

const related = ['master', 'roles'];

export const createPositionRole = defineMutation<[data: PositionRolePayload], { status: number; message: string; data: PositionRole }>(
    'roles',
    'create',
    async (data) =>
        (await http.post<{ status: number; message: string; data: PositionRole }>('/master/roles', data)).data,
    related,
);

export const updatePositionRole = defineMutation<[id: Id, data: PositionRolePayload], { status: number; message: string; data: PositionRole }>(
    'roles',
    'update',
    async (id, data) =>
        (await http.put<{ status: number; message: string; data: PositionRole }>(`/master/roles/${id}`, data)).data,
    related,
);

export const deletePositionRole = defineMutation<[id: Id], ApiMessage>(
    'roles',
    'delete',
    async (id) =>
        (await http.delete<ApiMessage>(`/master/roles/${id}`)).data,
    related,
);
