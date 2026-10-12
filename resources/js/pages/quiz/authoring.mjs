// Serialize draft and explicit saves so a late draft cannot undo publication.
export function createSaveQueue() {
    let tail = Promise.resolve();
    return task => {
        const result = tail.then(task);
        tail = result.catch(() => {});
        return result;
    };
}

export function parseBulkQuestions(text) {
    const questions = [], errors = [];
    const blocks = text.trim().split(/\n\s*\n|\n(?=\s*\d+[.)]\s)/).filter(s => s.trim());
    blocks.forEach((block, index) => {
        const lines = block.split(/\r?\n/).map(s => s.trim()).filter(Boolean);
        const question = lines.shift().replace(/^\d+[.)]\s*/, '').trim();
        const options = [], answers = [];
        for (const line of lines) {
            const key = line.match(/^(?:ANSWER|JAWABAN|KUNCI)\s*:\s*([A-Z])\s*$/i);
            const option = line.match(/^([A-Z])[.)]\s*(.+)$/i);
            if (key) answers.push(key[1].toUpperCase());
            else if (option) options.push({ letter: option[1].toUpperCase(), text: option[2].trim() });
            else errors.push(`Soal ${index + 1}: baris "${line}" tidak dikenali.`);
        }
        if (!question || options.length < 2 || options.length > 26) errors.push(`Soal ${index + 1}: isi pertanyaan dan 2 sampai 26 pilihan jawaban.`);
        if (new Set(options.map(o => o.letter)).size !== options.length) errors.push(`Soal ${index + 1}: huruf pilihan jawaban tidak boleh sama.`);
        if (answers.length !== 1 || !options.some(o => o.letter === answers[0])) errors.push(`Soal ${index + 1}: tulis satu kunci yang sesuai pilihan, misalnya KUNCI: A.`);
        questions.push({ question, type: 'multiple_choice', weight: 1, difficulty: 'medium', options: options.map(o => ({ text: o.text, is_correct: o.letter === answers[0] })) });
    });
    if (questions.length > 200) errors.push('Maksimal 200 soal dalam sekali tambah.');
    return { questions, errors };
}

export function questionPayload(question, courseId) {
    return {
        bank_id: question.bank_id || null, course_id: courseId || question.course_id || null,
        question: question.question.trim(), type: question.type, weight: Number(question.weight),
        image_path: question.image_path || null,
        category: question.category?.trim() || null, topic: question.topic || null,
        difficulty: question.difficulty || 'medium', explanation: question.explanation || null,
        options: question.type === 'essay' ? [] : question.options.map(o => ({ text: o.text.trim(), is_correct: !!o.is_correct })),
    };
}
