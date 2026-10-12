import { getQuiz } from '../../services/queries/quizQuery.ts';
import { mutateQuiz } from '../../services/mutations/quizMutation.ts';

// Compatibility dispatcher for existing quiz editors and attempt workflows.
export const api = (method, path, data) => method.toLowerCase() === 'get'
  ? getQuiz(path)
  : mutateQuiz(method, path, data);
export { errorMessage } from './errors.mjs';
export const dateLabel = (value) =>
    value
        ? new Date(value).toLocaleString("id-ID", {
              dateStyle: "medium",
              timeStyle: "short",
          })
        : "—";
export const localInput = (value) => {
    if (!value) return "";
    const d = new Date(
        value.includes("T") ? value : value.replace(" ", "T") + "Z",
    );
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}T${String(d.getHours()).padStart(2, "0")}:${String(d.getMinutes()).padStart(2, "0")}`;
};
export const isoDate = (value) =>
    value ? new Date(value).toISOString() : null;
