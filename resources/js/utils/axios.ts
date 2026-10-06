import axios from 'axios';
import type { AxiosError, InternalAxiosRequestConfig } from 'axios';
axios.defaults.withCredentials = true;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
const csrf = (config: InternalAxiosRequestConfig) => {
  const token = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;
  if (token && !config.headers['X-CSRF-TOKEN']) config.headers['X-CSRF-TOKEN'] = token;
  return config;
};
axios.interceptors.request.use(csrf);
const handleExpired = (error: AxiosError) => {
  if (error.response && error.response.status === 419) {
    window.location.reload();
  }
  return Promise.reject(error);
};
axios.interceptors.response.use(response => response, handleExpired);
export const apiInstance = axios.create({baseURL:window.location.origin,timeout:15000,withCredentials:true});
export const apiInstanceAuth = axios.create({baseURL:window.location.origin,timeout:15000,withCredentials:true});
apiInstance.interceptors.request.use(csrf);
apiInstanceAuth.interceptors.request.use(csrf);
apiInstance.interceptors.response.use(response => response, handleExpired);
apiInstanceAuth.interceptors.response.use(response => response, handleExpired);
