import type { Id, JsonValue, SuccessResponse } from "./common.ts";

export interface Submenu {
    id: number;
    id_menu: number;
    name: string;
    icon?: string | null;
    count_tipe_announcement?: number;
}

export interface Announcement {
    id: number;
    submenu_id: number;
    title: string;
    no_surat: string | null;
    url: string | null;
    content: string | null;
    type: string;
    content_url?: string | null;
    tgl_berlaku: string | null;
    tgl_berlaku_formatted?: string;
    tgl_dibuka?: string | null;
    created_at: string | null;
    updated_at: string | null;
    deleted_at?: string | null;
    created_by?: string | null;
    updated_by?: string | null;
    deleted_by?: string | null;
    dateLastUpdate?: string;
    menu?: Partial<Submenu> | null;
    document_position?: {
        document_id: number;
        kd_jbt: string;
    }[];
    document_regional?: {
        document_id: number;
        regional_id: string;
    }[];
}

export interface AnnouncementPayload {
    title: string;
    submenu_id: Id;
    no_surat?: string | null;
    tgl_berlaku?: string | null;
    type: "text" | "pdf";
    content?: string;
    dokumen?: File;
    kd_jabatan: string[];
    regionals_id: string[];
}

export interface AnnouncementDetail {
    success: boolean;
    detail: {
        title: string;
        icon: string;
    };
    items: Announcement[];
}

export type AnnouncementList = SuccessResponse<Submenu[]>;

export type AnnouncementResponse = SuccessResponse<Announcement>;

export interface WizardSession {
    wizard: Record<string, {
        data: JsonValue;
    }> | [];
}
