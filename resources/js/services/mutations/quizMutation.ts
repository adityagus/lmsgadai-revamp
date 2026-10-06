import type { QuizMutationArgs, QuizMutationResponse } from '../types/quiz.ts';

import { quizHttp } from '../../api/quizHttp.ts';

import { defineMutation } from '../../api/mutation.ts';

export const mutateQuiz = defineMutation<QuizMutationArgs, QuizMutationResponse>(
    'quiz',
    'write',
    async (...[method, path, data]: QuizMutationArgs) =>
        (await quizHttp.request<QuizMutationResponse>({ method, url: path, data })).data,
    ['quiz', 'courses', 'contents', 'progress'],
);
