import type { Announcement, AnnouncementPayload, AnnouncementResponse } from '../types/announcement.ts';

import type { ApiMessage, Id, JsonValue, SavedResponse, UploadProgress } from '../types/common.ts';

import { apiInstance as http } from "../../utils/axios.ts";

import { defineMutation } from "../../api/mutation.ts";

const related = ["announcements", "documentViews", "reports", "master"];

export const createAnnouncement = defineMutation<[data: AnnouncementPayload | FormData, onUploadProgress?: UploadProgress], AnnouncementResponse>(
    "announcements",
    "create",
    async (data, onUploadProgress) =>
        (await http.post<AnnouncementResponse>("/announcements", data, {
        onUploadProgress,
        headers: { "Content-Type": "multipart/form-data" },
    })).data,
    related,
);

export const updateAnnouncement = defineMutation<[data: Partial<AnnouncementPayload> | FormData, id?: Id, onUploadProgress?: UploadProgress], AnnouncementResponse>(
    "announcements",
    "update",
    async (data, id, onUploadProgress) =>
        (await http.post<AnnouncementResponse>(id ? `/announcements/${id}` : "/announcements", data, {
        onUploadProgress,
        headers: { "Content-Type": "multipart/form-data" },
    })).data,
    related,
);

export const softDeleteAnnouncement = defineMutation<[id: Id], ApiMessage>(
    "announcements",
    "archive",
    async (id) =>
        (await http.delete<ApiMessage>(`/announcements/${id}`)).data,
    related,
);

export const restoreAnnouncement = defineMutation<[id: Id], SavedResponse<Announcement>>(
    "announcements",
    "restore",
    async (id) =>
        (await http.patch<SavedResponse<Announcement>>(`/announcements/${id}/restore`)).data,
    related,
);

export const hardDeleteAnnouncement = defineMutation<[id: Id], ApiMessage>(
    "announcements",
    "delete",
    async (id) =>
        (await http.delete<ApiMessage>(`/announcements/hardDelete/${id}`)).data,
    related,
);

export const wizardStep = defineMutation<[step: Id, data: JsonValue], ApiMessage>(
    "announcements",
    "wizard-step",
    async (step, data) =>
        (await http.post<ApiMessage>(`/wizard/step/${step}`, { data })).data,
    related,
);

export const finishWizard = defineMutation<[], ApiMessage>(
    "announcements",
    "wizard-finish",
    async () =>
        (await http.post<ApiMessage>("/wizard/finish")).data,
    related,
);
