import type { Id, JsonValue, Paginated, ResourcePage, SavedResponse, } from "./common.ts";

export interface Category {
    id: number;
    name: string;
    description: string | null;
}

export interface CategoryPayload {
    name: string;
    description: string;
}

export interface CategorySaved {
    message: string;
    category: Category;
}

export type ContentType = "text" | "video" | "pdf" | "form" | "google_form" | "quiz";

export interface CourseContent {
    id: number;
    course_id: number;
    title: string;
    type: ContentType;
    content: string | null;
    order: number;
    quiz_id: number | null;
    lampiran?: string | null;
    user_name?: string;
    created_at?: string;
    updated_at?: string;
}

export interface Course {
    id: number;
    name: string;
    slug: string;
    tagline: string;
    description: string;
    thumbnail: string | null;
    thumbnail_url: string | null;
    category_id: number;
    is_popular: boolean | number;
    students: JsonValue;
    details: JsonValue;
    created_at: string;
    updated_at: string;
    category?: Category;
    contents?: CourseContent[];
}

export interface CoursePayload {
    name: string;
    slug: string;
    tagline: string;
    description: string;
    category_id: Id;
    is_popular?: boolean | number;
    thumbnail?: File | null;
    access_positions?: string[];
    students?: JsonValue;
    details?: JsonValue;
}

export interface ContentPayload {
    course_id: Id;
    title: string;
    type: ContentType;
    order: number;
    content?: string | File | null;
    quiz_id?: Id | null;
    lampiran?: File | null;
}

export type CourseList = ResourcePage<Course>;

export interface CourseDetail {
    course: Course;
    contents: Paginated<CourseContent>;
}

export interface CoursePreview {
    course: Course;
    contents: CourseContent[];
    access_positions: string[];
}

export type CourseSaved = SavedResponse<Course>;

export type ContentSaved = SavedResponse<CourseContent>;

export interface Participant {
    username: string;
    name: string;
}

export interface CourseParticipants {
    participants: Participant[];
    students_count: number;
    participants_count: number;
    total_students: number;
}

export interface CourseContentOrder {
    contents: Pick<CourseContent, "id" | "title" | "type" | "quiz_id">[];
}

export interface ContentOrderPayload {
    content_ids: number[];
    original_ids: number[];
}

export interface CourseProgress {
    items: {
        content_id: number;
        completed_at: string | null;
    }[];
    completed_count: number;
    total: number;
    resume_content_id: number | null;
    is_complete: boolean;
}

export interface SearchItem {
    id: number;
    title: string;
    type: string;
    tagline: string;
    description: string;
    category: string | null;
    thumbnail_url: string | null;
    submenu_id?: number;
    content_url?: string | null;
    no_surat?: string | null;
}

export type CourseSearch = {
    success: true;
    courses: SearchItem[];
    documents: SearchItem[];
    total: number;
    data: {
        courses: SearchItem[];
        documents: SearchItem[];
    };
} | {
    success: false;
    message: string;
    data: [];
};
