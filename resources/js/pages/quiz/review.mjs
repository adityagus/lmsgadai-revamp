export function reviewOptionState(question, option) {
    if (option.is_correct === true) return 'correct';
    if (!question.selected_options.includes(option.id)) return '';
    if (option.is_correct === false || option.answer_status === 'incorrect') return 'incorrect';
    return option.answer_status === 'correct' ? 'correct' : '';
}
