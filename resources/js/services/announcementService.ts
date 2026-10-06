// Compatibility exports. New callers import queries/ or mutations/ directly.
export {
    getAnnouncements,
    getDetailAnnouncement,
    getDocumentById,
    getLastDocumentPreview,
    trashAnnouncement,
    wizardSession,
} from "./queries/announcementQuery.ts";

export {
    createAnnouncement,
    updateAnnouncement,
    softDeleteAnnouncement,
    restoreAnnouncement,
    hardDeleteAnnouncement,
    wizardStep,
    finishWizard,
} from "./mutations/announcementMutation.ts";
