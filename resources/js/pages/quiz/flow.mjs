export function primaryQuizAction(quiz) {
    if (!quiz) return { kind: 'unavailable', label: 'Belum tersedia' };
    if (quiz.sequence_locked) return { kind: 'unavailable', label: 'Selesaikan materi sebelumnya' };
    if (quiz.active_attempt) return { kind: 'resume', label: 'Lanjutkan Quiz', attempt: quiz.active_attempt };
    if (quiz.can_start) return { kind: 'start', label: quiz.attempts?.length ? 'Kerjakan Lagi' : 'Mulai Quiz' };
    const completed = (quiz.attempts || []).filter(a => a.status !== 'IN_PROGRESS');
    if (completed.length) {
        const latest = [...completed].sort((a,b) => b.attempt_number - a.attempt_number)[0];
        return { kind: 'result', label: 'Lihat Hasil', attempt: latest.id };
    }
    return { kind: 'unavailable', label: quiz.status === 'Belum Dimulai' ? 'Belum dimulai' : 'Quiz ditutup' };
}

export function quizStepError(form, step) {
    if (step === 0) {
        const title = (form.title || '').trim();
        if (!title) return 'Judul quiz wajib diisi.';
        if (title.length > 150) return 'Judul quiz maksimal 150 karakter.';
        if (!(Number.isInteger(Number(form.duration_minutes)) && Number(form.duration_minutes) >= 1 && Number(form.duration_minutes) <= 1440)) return 'Durasi harus 1–1.440 menit.';
        if (form.maximum_attempt != null && !(Number.isInteger(Number(form.maximum_attempt)) && Number(form.maximum_attempt) >= 0 && Number(form.maximum_attempt) <= 100)) return 'Jumlah kesempatan harus berupa angka 0 sampai 100.';
        if (!(Number(form.passing_grade) >= 0 && Number(form.passing_grade) <= 100)) return 'Nilai kelulusan harus 0–100.';
        const start = Date.parse(form.available_from), end = Date.parse(form.available_until);
        if (!form.no_schedule && (!Number.isFinite(start) || !Number.isFinite(end) || end <= start)) return 'Isi jadwal mulai dan selesai dengan urutan waktu yang benar.';
    }
    if (step === 1 && (!form.question_ids?.length || !Number.isInteger(Number(form.question_count)) || Number(form.question_count) < 1 || Number(form.question_count) > form.question_ids.length)) {
        return `Quiz membutuhkan ${form.question_count || 1} soal. Saat ini ${form.question_ids?.length || 0} soal dipilih.`;
    }
    if (step === 2 && (!Number.isInteger(Number(form.course_id)) || Number(form.course_id) < 1 || Array.isArray(form.course_id))) {
        return 'Pilih satu course sebelum menerbitkan quiz.';
    }
    return '';
}

export function passingGradeError(grade) {
    if (grade === null || grade === undefined || String(grade).trim() === '' || !Number.isFinite(Number(grade)) || Number(grade) < 0 || Number(grade) > 100) return 'Nilai minimal lulus harus antara 0 dan 100.';
    return '';
}
