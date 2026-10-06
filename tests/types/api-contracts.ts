import { ref } from 'vue';
import { useMutation, useQuery } from '@tanstack/vue-query';
import { getCourses } from '../../resources/js/services/queries/courseQuery.ts';
import { getQuiz } from '../../resources/js/services/queries/quizQuery.ts';
import { createCourse, saveCourseContentOrder } from '../../resources/js/services/mutations/courseMutation.ts';
import { login } from '../../resources/js/services/mutations/authMutation.ts';
import { mutateQuiz } from '../../resources/js/services/mutations/quizMutation.ts';
import type { Course, CourseList } from '../../resources/js/services/types/course.ts';
import type { QuizAttempt, QuizMetadata } from '../../resources/js/services/types/quiz.ts';

function expectType<T>(_value: T): void {}
type IsAny<T> = 0 extends (1 & T) ? true : false;

// Checked by tsc only: no requests or Vue hooks execute in this file.
export async function checkApiContracts() {
  const courses = await getCourses(1);
  expectType<CourseList>(courses);
  expectType<Course>(courses.data[0]);
  expectType<false>(false as IsAny<typeof courses>);
  const result = useQuery(getCourses.options(ref(1)));
  expectType<CourseList | undefined>(result.data.value);

  const create = useMutation(createCourse.options());
  expectType<Course>((await create.mutateAsync([new FormData()])).data);
  expectType<string | undefined>(create.error.value?.response?.data.message);
  await saveCourseContentOrder(51, { content_ids: [1, 2], original_ids: [2, 1] });
  await login({ user: 'fixture', pass: 'fixture' });
  expectType<QuizMetadata>(await getQuiz('metadata'));
  expectType<QuizAttempt>(await getQuiz('attempts/7'));
  expectType<QuizMetadata | undefined>(useQuery(getQuiz.options('metadata')).data.value);
  await mutateQuiz('put', 'attempts/7/answers/2', { revision: 1, selected_options: [3] });

  // @ts-expect-error Pagination must be numeric.
  await getCourses('one');
  // @ts-expect-error Course does not expose arbitrary untyped fields.
  courses.data[0].missingField;
  // @ts-expect-error Mutation variables use a typed argument tuple.
  await create.mutateAsync(new FormData());
  // @ts-expect-error The original order is required for conflict detection.
  await saveCourseContentOrder(51, { content_ids: [1, 2] });
  // @ts-expect-error Laravel login uses user/pass, not username/password.
  await login({ username: 'fixture', password: 'fixture' });
  // @ts-expect-error Quiz answer writes require a revision.
  await mutateQuiz('put', 'attempts/7/answers/2', { selected_options: [3] });
  // @ts-expect-error Read endpoints must exist in the quiz route map.
  await getQuiz('missing-route');
}
