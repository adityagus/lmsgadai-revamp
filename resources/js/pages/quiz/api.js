import axios from "axios";

const base =
    document.querySelector("base")?.href || `${window.location.origin}/`;
const http = axios.create({
    baseURL: new URL("api/quiz/", base).href,
    withCredentials: true,
    timeout: 15000,
    headers: {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
            ?.content,
    },
});
export const api = async (method, path, data) =>
    (await http.request({ method, url: path, data })).data;
export const errorMessage = (e) =>
    Object.values(e.response?.data?.errors || {})
        .flat()
        .join(" ") ||
    e.response?.data?.message ||
    "Koneksi terputus. Silakan coba kembali.";
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
