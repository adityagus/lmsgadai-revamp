import type { Id } from "./common.ts";
import type { Course } from "./course.ts";
import type { Announcement } from "./announcement.ts";
import type { SessionUser } from "./auth.ts";

export interface Position {
    kd_jabatan: string;
    nm_jabatan: string;
    status_karyawan: string;
    jabatan_active: boolean;
}

export interface Region {
    kd_wilayah: Id;
    nm_wilayah: string;
    branches: string[];
}

export interface BranchNode {
    id_area: Id;
    nm_area: string;
    children: BranchNode[];
}

export interface AdminOverview {
    success: boolean;
    message: string;
    stats: {
        total_pengumuman: number;
        total_courses: number;
        total_formulir: number;
        active_in_last_7_days: number;
    };
    latest_documents: Partial<Announcement>[];
}

export interface UserOverview {
    user: SessionUser;
    stats: {
        courses_joined: number;
        documents_accessed: number;
    };
    last_courses: Course[];
    last_documents: Announcement[];
}
