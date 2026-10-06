export type Role = "admin" | "trainer" | "teacher" | "student";

export interface SessionUser {
    user: string;
    nama: string;
    cabang: string | null;
    jabatan: string | null;
    idgrup: string | null;
    npk: string | null;
    status: "login";
    role?: Role;
}

export interface SessionResponse {
    auth: SessionUser | null;
}

export interface LoginPayload {
    user: string;
    pass: string;
}

export type LoginResponse = {
    success: true;
    user: SessionUser;
    redirect: string;
} | {
    success: false;
    message: string;
};

export interface CsrfResponse {
    csrf_token: string;
}
