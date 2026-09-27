import axios from 'axios';
axios.defaults.withCredentials = true;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
const csrf = config => {
  const token = document.querySelector('meta[name="csrf-token"]')?.content;
  if (token) config.headers['X-CSRF-TOKEN'] = token;
  return config;
};
axios.interceptors.request.use(csrf);
export const apiInstance = axios.create({baseURL:window.location.origin,timeout:15000,withCredentials:true});
export const apiInstanceAuth = axios.create({baseURL:window.location.origin,timeout:15000,withCredentials:true});
apiInstance.interceptors.request.use(csrf);
apiInstanceAuth.interceptors.request.use(csrf);
