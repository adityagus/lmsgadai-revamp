import type { ApiMessage, DirectoryOption, Id } from "./common.ts";
import type { Role } from "./auth.ts";

export type QuestionType = "multiple_choice" | "multiple_answer" | "true_false" | "essay";

export type QuizStatus = "DRAFT" | "PUBLISHED" | "CLOSED";

export type AttemptStatus = "IN_PROGRESS" | "SUBMITTED" | "AUTO_SUBMITTED";

export interface QuizUser {
    id: number;
    username: string;
    name: string;
    role: Role;
}

export interface QuestionOption {
    id: number;
    text: string;
    is_correct?: boolean;
    answer_status?: "correct" | "incorrect";
}

export interface Question {
    id: number;
    question: string;
    type: QuestionType;
    weight: number;
    difficulty: string;
    category: string | null;
    explanation: string | null;
    bank_id: number | null;
    course_id: number | null;
    options: QuestionOption[];
}

export interface QuizSettings {
    title: string;
    description: string | null;
    course_id: Id | null;
    status: QuizStatus;
    duration_minutes: number;
    question_count: number;
    passing_grade: number;
    available_from: string | null;
    available_until: string | null;
    no_schedule: boolean;
    maximum_attempt: number;
    randomize_question: boolean;
    randomize_answer: boolean;
    show_score: boolean;
    show_total_correct: boolean;
    show_passing_status: boolean;
    show_correct_answer: boolean;
    show_wrong_answer: boolean;
    show_explanation: boolean;
    release_after_close: boolean;
    grading_method: "highest" | "latest" | "average" | "first";
}

export interface Quiz extends QuizSettings {
    id: number;
    created_by: number;
    created_at: string;
    updated_at: string;
}

export interface QuizDetail extends Quiz {
    question_ids: number[];
    questions?: Question[];
    class_ids: number[];
    overrides: QuizOverride[];
}

export interface QuizOverride {
    student_id: number;
    additional_minutes: number;
    available_from?: string | null;
    available_until?: string | null;
    maximum_attempt?: number | null;
}

export interface AttemptQuestion {
    id: number;
    position: number;
    question: string;
    type: QuestionType;
    options: QuestionOption[];
    selected_options: number[];
    essay: string;
    flagged: boolean;
    visited: boolean;
    revision?: number;
    points?: number | null;
    explanation?: string | null;
    feedback?: string | null;
}

export interface QuizAttempt {
    id: number;
    quiz_id: number;
    course_id: number | null;
    title: string;
    student: string;
    classes: string[];
    status: AttemptStatus;
    attempt_number: number;
    started_at: string;
    expired_at: string;
    submitted_at: string | null;
    server_now: string;
    pending_review: boolean;
    question_count: number;
    questions: AttemptQuestion[];
    score?: number | null;
    aggregate_score?: number | null;
    passed?: boolean | null;
    passing_grade?: number;
    total_correct?: number;
    total_wrong?: number;
    total_unanswered?: number;
}

export interface QuizPreview {
    title: string;
    duration_minutes: number;
    questions: AttemptQuestion[];
}

export interface StudentQuiz {
    id: number;
    title: string;
    description: string | null;
    course_id: number | null;
    course: string | null;
    classes: string[];
    status: string;
    active_attempt_id: number | null;
    attempts: Pick<QuizAttempt, "id" | "status" | "attempt_number">[];
    duration_minutes: number;
    question_count: number;
    sequence_locked: boolean;
}

export interface StudentQuizList {
    user: QuizUser;
    quizzes: StudentQuiz[];
    server_now: string;
}

export interface QuizMetadata {
    user: QuizUser;
    students: QuizUser[];
    courses: {
        id: number;
        name: string;
    }[];
    classes: {
        id: number;
        name: string;
        student_ids: number[];
    }[];
    banks: {
        id: number;
        name: string;
        created_by: number;
    }[];
}

export interface QuizAudience {
    positions: DirectoryOption[];
    branches: DirectoryOption[];
}

export interface QuestionStatistics {
    id: number;
    question: string;
    difficulty: string;
    responses: number;
    correct: number;
    correct_rate: number;
    wrong_rate: number;
}

export interface QuizResults {
    quiz: Quiz;
    attempts: (Partial<QuizAttempt> & {
        id: number;
        student_id: number;
    })[];
    statistics: {
        participants: number;
        completed: number;
        not_started: number;
        graded: number;
        average_score: number | null;
        highest_score: number | null;
        lowest_score: number | null;
        pass_rate: number | null;
        average_completion_seconds: number;
    };
    questions: QuestionStatistics[];
    hardest_questions: QuestionStatistics[];
    classes: {
        id: number;
        name: string;
        graded: number;
        average_score: number | null;
        pass_rate: number | null;
    }[];
}

export interface QuizPayload extends QuizSettings {
    question_ids: number[];
    class_ids?: number[];
    overrides: QuizOverride[];
}

export interface QuestionPayload {
    question: string;
    type: QuestionType;
    weight: number;
    difficulty: string;
    bank_id?: Id | null;
    course_id?: Id | null;
    category?: string | null;
    explanation?: string | null;
    options: {
        text: string;
        is_correct: boolean;
    }[];
}

export interface AnswerPayload {
    selected_options?: number[];
    essay?: string;
    flagged?: boolean;
    visited?: boolean;
    revision: number;
}

export interface AnswerSaved {
    revision: number;
    server_now: string;
}

export interface GeneratePayload {
    bank_id?: Id | null;
    course_id?: Id | null;
    buckets: {
        count: number;
        category?: string | null;
        difficulty?: string;
    }[];
}

export interface ReplacementPayload {
    exclude_ids: number[];
    bank_id?: Id | null;
    course_id?: Id | null;
    category?: string | null;
    difficulty?: string;
}
// Endpoint maps keep response inference tied to the requested quiz route.

export type QuizReadMap = {
    student: StudentQuizList;
    manage: Quiz[];
    metadata: QuizMetadata;
    questions: Question[];
    "audience-options": QuizAudience;
} & {
    [P in `attempts/${number}` | `results/${number}`]: QuizAttempt;
} & {
    [P in `manage/${number}`]: QuizDetail;
} & {
    [P in `manage/${number}/preview`]: QuizPreview;
} & {
    [P in `manage/${number}/results`]: QuizResults;
};

export type QuizReadPath = keyof QuizReadMap;

export type QuizReadResponse = QuizReadMap[QuizReadPath];

export type QuizMutationArgs = [
    method: "post",
    path: "manage",
    data: QuizPayload
] | [
    method: "put",
    path: `manage/${number}`,
    data: QuizPayload
] | [
    method: "post",
    path: "questions",
    data: QuestionPayload
] | [
    method: "put",
    path: `questions/${number}`,
    data: QuestionPayload
] | [
    method: "post",
    path: "questions/replace-one",
    data: ReplacementPayload
] | [
    method: "post",
    path: "questions/bulk",
    data: {
        bank_id?: Id | null;
        course_id?: Id | null;
        questions: QuestionPayload[];
    }
] | [
    method: "post",
    path: "generate",
    data: GeneratePayload
] | [
    method: "post",
    path: "banks",
    data: {
        name: string;
    }
] | [
    method: "post",
    path: "classes",
    data: {
        name: string;
        student_ids: Id[];
    }
] | [
    method: "post",
    path: "users",
    data: {
        username: string;
        name: string;
        role: Role;
    }
] | [
    method: "post",
    path: `${number}/start` | `attempts/${number}/submit` | `manage/${number}/duplicate`,
    data?: undefined
] | [
    method: "put",
    path: `attempts/${number}/answers/${number}`,
    data: AnswerPayload
] | [
    method: "post",
    path: `manage/${number}/overrides`,
    data: QuizOverride
] | [
    method: "patch",
    path: `manage/${number}/visibility`,
    data: Partial<QuizSettings>
] | [
    method: "patch",
    path: `manage/${number}/questions/${number}/explanation`,
    data: {
        explanation: string;
    }
] | [
    method: "put",
    path: `results/${number}/grade/${number}`,
    data: {
        points: number;
        feedback?: string | null;
    }
];

export type QuizMutationResponse = ApiMessage | {
    id: number;
} | QuizAttempt | AnswerSaved | {
    question_ids: number[];
    questions?: Question[];
} | {
    question: Question;
};
