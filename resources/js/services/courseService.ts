// Compatibility exports. New callers import queries/ or mutations/ directly.
export {
    getCourses,
    searchContent,
    getCourseById,
    getCourseByIdPagination,
} from "./queries/courseQuery.ts";
export {
    deleteCourse,
    createCourse,
    updateCourse,
} from "./mutations/courseMutation.ts";
export { getCategories } from "./queries/categoryQuery.ts";
