import type { Announcement, AnnouncementDetail, AnnouncementList, AnnouncementResponse, WizardSession } from '../types/announcement.ts';
import type { Id, SuccessResponse } from '../types/common.ts';
import { apiInstance as http } from "../../utils/axios.ts";
import { defineQuery } from "../../api/query.ts";

export const getAnnouncements = defineQuery<[category: Id], AnnouncementList>(
    "announcements",
    "list",
    async ([category], signal) =>
        (await http.get<AnnouncementList>("/announcements", { params: { category }, signal }))
        .data,
);

export const getDetailAnnouncement = defineQuery<[id: Id], AnnouncementDetail>(
    "announcements",
    "detail",
    async ([id], signal) =>
        (await http.get<AnnouncementDetail>(`/announcement/${id}`, { signal })).data,
);

export const getDocumentById = defineQuery<[id: Id], AnnouncementResponse>(
    "announcements",
    "document",
    async ([id], signal) =>
        (await http.get<AnnouncementResponse>(`/announcements/document/${id}`, { signal })).data,
);

export const getLastDocumentPreview = defineQuery<[id: Id], AnnouncementResponse>(
    "announcements",
    "preview",
    async ([id], signal) =>
        (await http.get<AnnouncementResponse>(`/announcements/document/preview/${id}`, { signal }))
        .data,
);

export const trashAnnouncement = defineQuery<[category: Id], SuccessResponse<Announcement[]>>(
    "announcements",
    "trash",
    async ([category], signal) =>
        (await http.get<SuccessResponse<Announcement[]>>("/announcements/trash", {
        params: { category },
        signal,
    })).data,
);

export const wizardSession = defineQuery<[], WizardSession>(
    "announcements",
    "wizard",
    async ([], signal) =>
        (await http.get<WizardSession>("/wizard/session", { signal })).data,
);
