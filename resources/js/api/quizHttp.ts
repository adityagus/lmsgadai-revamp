import axios from "axios";
const base = document.querySelector<HTMLBaseElement>("base")?.href || `${window.location.origin}/`;
const http = axios.create({
    baseURL: new URL("api/quiz/", base).href,
    withCredentials: true,
    timeout: 15000,
    headers: {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
    },
});
http.interceptors.request.use((config) => {
    const token = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;
    if (token) {
        config.headers["X-CSRF-TOKEN"] = token;
    }
    return config;
});
http.interceptors.response.use((response) => response, (error) => {
    if (error.response && error.response.status === 419) {
        window.location.reload();
    }
    return Promise.reject(error);
});
export { http as quizHttp };
