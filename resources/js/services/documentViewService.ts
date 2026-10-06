// Compatibility exports. New callers import queries/ or mutations/ directly.
export { recordDocumentView } from "./mutations/documentViewMutation.ts";
export {
    checkDocumentViewed,
    getUnviewedUsers,
    getViewedUsers,
    getDocumentViewStats,
} from "./queries/documentViewQuery.ts";
