export function errorMessage(error) {
    const status = error?.response?.status;
    if (error?.code === 'ECONNABORTED' || error?.code === 'ETIMEDOUT') {
        return 'Server belum merespons dalam batas waktu. Silakan coba lagi.';
    }
    if (status === 401 || status === 419) return 'Sesi login berakhir. Silakan masuk kembali.';
    if (status === 403) return 'Anda tidak memiliki akses ke data ini.';
    if (status >= 500) return 'Server sedang mengalami kendala saat memuat data. Silakan coba lagi.';
    const validation = Object.values(error?.response?.data?.errors ?? {}).flat().join(' ');
    if (validation) return validation;
    if (error?.response?.data?.message) return error.response.data.message;
    if (error?.code === 'ERR_NETWORK') return 'Server tidak dapat dijangkau. Periksa koneksi lalu coba lagi.';
    return 'Data belum berhasil dimuat. Silakan coba lagi.';
}
