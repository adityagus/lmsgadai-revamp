// Compatibility exports. New callers import queries/ or mutations/ directly.
export {
    getContentsByCourseId,
    getContentById,
} from "./queries/contentQuery.ts";
export {
    createContent,
    updateContent,
    deleteContent,
} from "./mutations/contentMutation.ts";
