import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useAuth() {
    const page = usePage();

    const user = computed(() => page.props.auth || null);
    const role = computed(() => user.value?.role || 'student');
    const roles = computed<string[]>(() => {
        const r = user.value?.roles;
        if (Array.isArray(r) && r.length > 0) return r.map((s) => String(s).toLowerCase());
        return [role.value.toLowerCase()];
    });

    const hasRole = (check: string | string[]) => {
        const arr = Array.isArray(check) ? check : [check];
        return arr.some((c) => roles.value.includes(String(c).toLowerCase()));
    };

    const isAdmin = computed(() => hasRole('admin'));
    const isTrainer = computed(() => hasRole('trainer'));
    const isSop = computed(() => hasRole('sop'));
    const isAudit = computed(() => hasRole('audit'));
    const isStudent = computed(() => roles.value.includes('student'));

    // Hak akses fungsional (multi-role checklist)
    const canManageLearning = computed(() => hasRole(['admin', 'trainer']));
    const canManageDocuments = computed(() => hasRole(['admin', 'sop']));
    const canViewReports = computed(() => hasRole(['admin', 'sop', 'audit', 'trainer']));
    const canManageQuiz = computed(() => hasRole(['admin', 'trainer']));

    return {
        user,
        role,
        roles,
        hasRole,
        isAdmin,
        isTrainer,
        isSop,
        isAudit,
        isStudent,
        canManageLearning,
        canManageDocuments,
        canViewReports,
        canManageQuiz,
    };
}
