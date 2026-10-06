# Akses berdasarkan jabatan login

Kode jabatan berasal dari employee database setelah password berhasil diverifikasi, lalu disimpan sebagai `auth.idgrup` di session server. Parameter query/body dan `auth.role` yang dikirim klien tidak menjadi sumber hak akses.

`config/access.php` adalah sumber pemetaan jabatan ke role:

- `JBT-032` dan `JBT-038`: admin, sesuai aturan yang dipertahankan.
- `JBT-037`, `JBT-039`, `JBT-040`, dan `JBT-SOP`: sop (akses dokumen).
- `JBT-019`, `JBT-020`, `042`, dan `JBT-TRAINER`: trainer.
- Jabatan yang belum dipetakan menjadi student. Role hanya dibaca dari sesi login Sopiga Pro, bukan dari tabel role LMS.

`LmsAccess` dipakai oleh middleware API, props Inertia, endpoint session, dan identitas kuis. Hak akses kelas, peserta, urutan materi, kepemilikan kuis, serta audience dokumen tetap diperiksa oleh domain masing-masing.

Peserta khusus kursus tetap dapat membuka kursus dan kuis tertaut walaupun jabatan mereka tidak ada di daftar posisi kursus. Admin, teacher, dan trainer tetap melewati pembatasan audience kursus sesuai aturan manager yang sudah ada.

## Middleware route

```php
// Grup manager: admin, teacher, trainer.
Route::get('/contoh', $handler)->middleware('jabatan:manager');

// Hanya admin hasil resolusi jabatan Sopiga Pro.
Route::get('/contoh-admin', $handler)->middleware('jabatan:admin');

// Kode jabatan secara langsung; admin lain tidak otomatis boleh masuk.
Route::get('/contoh-jabatan', $handler)->middleware('jabatan:JBT-032,JBT-038');
```

Daftar parameter memakai hubungan OR. Middleware tanpa parameter menolak akses. Nama grup tersedia di `access.groups`; parameter lainnya dicocokkan persis dengan kode jabatan session.

Untuk route yang membutuhkan satu role khusus tersedia alias middleware berikut:

```php
Route::get('/manager-only', $handler)->middleware('jabatan:manager');
Route::get('/admin-only', $handler)->middleware('jabatan:admin');
```

Keempat alias tersebut memakai resolver yang sama dan tidak membaca role dari database LMS.

Halaman dalam `config/pages.php` otomatis memakai middleware sesuai kolom `access`. Endpoint JSON tetap dilindungi `LmsAuthorization` dan pemeriksaan domain; keduanya memakai role dari pemetaan yang sama. Guest diarahkan ke login untuk halaman, atau menerima 401 untuk JSON. Pengguna login yang tidak berhak menerima 403.

Setelah mengubah konfigurasi pada deployment yang menggunakan config cache, bangun ulang config cache pada proses deploy. Jabatan berasal dari sesi login; perubahan jabatan pada employee database baru tercermin setelah login ulang.
