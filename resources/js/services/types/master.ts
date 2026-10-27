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

export interface PositionRole {
    id: Id;
    position_code: string;
    position_name?: string | null;
    role: 'admin' | 'trainer' | 'sop' | 'audit' | 'student';
    description?: string | null;
    created_at?: string;
    updated_at?: string;
}

export interface RoleOption {
    role: string;
    label: string;
    badge_color: string;
    description: string;
}

export interface UserRoleItem {
    id: Id;
    username: string;
    name?: string | null;
    position?: string | null;
    role: string;
    roles: string[];
    description?: string | null;
    created_at?: string;
    updated_at?: string;
}

export interface EmployeeItem {
    username: string;
    name: string;
    position?: string | null;
}

export interface UserRolePayload {
    username: string;
    name?: string | null;
    position?: string | null;
    roles: string[];
    description?: string | null;
}

export interface PositionRoleResponse {
    status: number;
    data: any[];
    user_roles?: UserRoleItem[];
    position_roles?: PositionRole[];
    employees?: EmployeeItem[];
    positions?: Array<{ position_code: string; position_name: string }>;
    available_roles: RoleOption[];
}

export interface PositionRolePayload {
    position_code?: string;
    position_name?: string | null;
    username?: string;
    name?: string | null;
    position?: string | null;
    role?: string;
    roles?: string[];
    description?: string | null;
}
