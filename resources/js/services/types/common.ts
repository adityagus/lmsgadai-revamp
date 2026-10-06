import type { AxiosError, AxiosProgressEvent } from "axios";

export type Id = number | string;

export type JsonValue = string | number | boolean | null | JsonValue[] | {
    [key: string]: JsonValue;
};

export type UploadProgress = (event: AxiosProgressEvent) => void;

export interface ApiMessage {
    message: string;
    status?: number | string;
    success?: boolean;
}

export interface DataResponse<T> {
    data: T;
}

export interface SuccessResponse<T> extends DataResponse<T> {
    success: boolean;
    message?: string;
}

export interface SavedResponse<T> extends DataResponse<T>, ApiMessage {
}

export interface ValidationError {
    message: string;
    errors?: Record<string, string[]>;
}

export type ApiError = AxiosError<ValidationError>;

export interface PageMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

export interface Paginated<T> extends PageMeta {
    data: T[];
}

export interface ResourcePage<T> {
    data: T[];
    meta: PageMeta;
    links: Record<string, string | null>;
}

export interface DirectoryOption {
    value: string;
    label: string;
}
