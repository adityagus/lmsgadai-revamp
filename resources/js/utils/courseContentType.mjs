export function googleFormUrl(content) {
    const raw = typeof content === 'string' ? content : content?.form_url || content?.url || content?.content || content?.body || '';
    if (typeof raw !== 'string') return '';
    const iframe = raw.match(/<iframe\b[^>]*\bsrc\s*=\s*["']([^"']+)["']/i);
    const candidate = iframe ? iframe[1] : raw.trim();
    try {
        const url = new URL(candidate.replace(/&amp;/g, '&'));
        const googleForms = url.hostname === 'docs.google.com' && /^\/forms(?:\/|$)/.test(url.pathname);
        if (url.protocol !== 'https:' || (!googleForms && url.hostname !== 'forms.gle')) return '';
        return url.href;
    } catch { return ''; }
}

export function courseContentType(content) {
    if (Number(content?.quiz_id) > 0) return 'quiz';
    if (['form', 'google_form', 'google form'].includes(content?.type)) return 'google_form';
    // Legacy Google Forms used type=quiz. Unconfigured native quizzes must never load an iframe.
    if (content?.type === 'quiz' && googleFormUrl(content)) return 'google_form';
    return content?.type || '';
}
