import type { QuizMutationArgs, QuizMutationResponse } from '../types/quiz.ts';

import { quizHttp } from '../../api/quizHttp.ts';

import { defineMutation } from '../../api/mutation.ts';

export const uploadQuestionImage = defineMutation<[file: File], { image_path: string; image_url: string }>(
    'quiz', 'upload-question-image',
    async (file) => {
        const data = new FormData();
        data.append('image', file);
        return (await quizHttp.post<{ image_path: string; image_url: string }>('question-images', data, { timeout: 120000 })).data;
    },
    [],
);

export const mutateQuiz = defineMutation<QuizMutationArgs, QuizMutationResponse>(
    'quiz',
    'write',
    async (...[method, path, data]: QuizMutationArgs) =>
        (await quizHttp.request<QuizMutationResponse>({ method, url: path, data })).data,
    ['quiz', 'courses', 'contents', 'progress'],
);
