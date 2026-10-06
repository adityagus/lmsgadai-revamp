export interface DocumentReportRow {
    position: string;
    pt: string;
    tanggal_upload: string | null;
    ketentuan_dokumen: string;
    tanggal_berlaku: string | null;
    nomor_surat: string | null;
    perihal_judul: string;
    status: "Active" | "Inactive";
}

export interface DocumentReader {
    username: string;
    full_name: string;
    first_viewed_at?: string | null;
    last_viewed_at?: string | null;
    view_count?: number;
}

export interface DocumentViewStat {
    document_id: number;
    document_title: string;
    document_no_surat: string | null;
    created_at: string;
    total_users_read: number;
    total_clicks: number;
}
