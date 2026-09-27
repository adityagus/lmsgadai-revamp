import { defineStore } from 'pinia';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: '',
    jabatan: '',
    auth: null,
  }),
  actions: {
    setAuth(auth) {
      this.auth = auth;
      this.user = auth?.user || '';
      this.jabatan = auth?.jabatan || '';
    },
    clearAuth() {
      this.auth = null;
      this.user = '';
      this.jabatan = '';
    }
  }
});
