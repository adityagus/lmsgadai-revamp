import type { MaybeRefOrGetter } from 'vue';

import type { QuizReadMap, QuizReadPath } from '../types/quiz.ts';

import { quizHttp } from '../../api/quizHttp.ts';

import { defineQuery } from '../../api/query.ts';

function queryFor<Path extends QuizReadPath>() {
    return defineQuery<[
        path: Path
    ], QuizReadMap[Path]>('quiz', 'read', async ([path], signal) => (await quizHttp.get<QuizReadMap[Path]>(path, { signal })).data);
}

export function getQuiz<Path extends QuizReadPath>(path: MaybeRefOrGetter<Path>) {
    return queryFor<Path>()(path);
}

getQuiz.options = <Path extends QuizReadPath>(path: MaybeRefOrGetter<Path>) => queryFor<Path>().options(path);
