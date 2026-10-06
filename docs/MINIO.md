# MinIO untuk LMS

Integrasi menggunakan server MinIO yang sudah ada, adapter S3 Laravel, dan bucket private. URL frontend `/storage/...` tetap digunakan; Laravel meneruskan file dari MinIO setelah login. Gambar thumbnail course tetap dapat dibuka tamu untuk halaman preview publik. Tidak perlu membuka bucket untuk anonymous access atau mengatur CORS browser. Endpoint harus bisa diakses oleh PHP/container.

## Konfigurasi

Isi `.env` (jangan commit kredensial):

```dotenv
LMS_STORAGE_DRIVER=s3
AWS_ACCESS_KEY_ID=isi-access-key
AWS_SECRET_ACCESS_KEY=isi-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=nama-bucket-lms
AWS_ENDPOINT=https://endpoint-api-minio
AWS_USE_PATH_STYLE_ENDPOINT=true
MINIO_PUBLIC_PREFIX=public
```

Gunakan endpoint **API S3**, bukan URL web console. Bucket harus sudah tersedia. Akun aplikasi memerlukan list bucket serta read/write/delete object untuk upload, arsip, dan restore. Gunakan region yang sesuai server. Kredensial diteruskan ke Docker melalui `.env` yang sudah dikonfigurasi di `docker-compose.yaml`.

Nama `MINIO_ENDPOINT`, `MINIO_ACCESS_KEY`, `MINIO_SECRET_KEY`, dan `MINIO_BUCKET` juga didukung jika nilai AWS terkait kosong. Endpoint harus berupa URL lengkap dengan `http://` atau `https://` dan port API bila bukan port standar. Konfigurasi MINIO memakai path-style secara default. Uji baca tanpa mencetak kredensial dengan `php scripts/check-minio.php`.

Pada komputer ini, container `shared_minio` memetakan API ke port host **9092**, sedangkan console memakai **9001**. Gunakan `MINIO_ENDPOINT=http://localhost:9092` untuk PHP Laragon dan `DOCKER_MINIO_ENDPOINT=http://host.docker.internal:9092` untuk aplikasi Docker. Compose meneruskan override tersebut sebagai MINIO_ENDPOINT; perubahan berlaku setelah container dibuat ulang.

Bucket `lmsgadai` yang sudah diisi pengguna menyimpan `aktif/`, `tidakaktif/`, dan `uploads/` langsung di root. Untuk layout ini, konfigurasi lokal memakai **`MINIO_PUBLIC_PREFIX=` (kosong)**. Tidak perlu memindahkan object ke folder `public`. Path database dan URL browser tetap sama; aplikasi menyesuaikan prefix saat upload, arsip, restore, dan hapus.

## Data lama dan aktivasi

Sebelum mengaktifkan `LMS_STORAGE_DRIVER=s3`, salin **isi** folder legacy `storage/app` ke root bucket, mempertahankan struktur berikut:

Tabel berikut untuk prefix default `public`. Jika `MINIO_PUBLIC_PREFIX` kosong, salin isi `storage/app/public` ke root bucket dan hilangkan awalan `public/` pada kolom object.

| Disk aplikasi | Prefix object MinIO |
| --- | --- |
| aktif | public/aktif/ |
| public | public/ |
| default (s3) | root bucket |

Contoh: `storage/app/public/aktif/SOP/test.pdf` menjadi object `public/aktif/SOP/test.pdf`. Folder `public/tidakaktif` dan `public/uploads` juga harus disalin. Jangan menghapus sumber atau mengubah URL/path di database. Koordinasikan penghentian upload legacy saat menyalin dan beralih; aplikasi legacy tetap memakai folder lokal dan tidak otomatis menyinkronkan perubahan MinIO.

Pada Laragon, lepaskan **symlink/junction** `public/storage` setelah salinan diverifikasi agar file lokal tidak melewati route MinIO. Jangan menghapus target folder legacy. Untuk Docker, entrypoint melepas symlink tersebut otomatis saat mode S3 aktif.

```powershell
php artisan config:clear
docker compose up -d --build app
```

Jika menjalankan melalui Laragon saja, cukup `composer install` dan `php artisan config:clear`, lalu restart server aplikasi. Tidak ada migrasi database atau penyalinan data otomatis. Session/cache tetap lokal.

Uji upload PDF/thumbnail, buka file, arsipkan, dan restore dengan data uji setelah koneksi siap. Dokumen dari bucket private memerlukan login. Endpoint streaming tidak menyediakan HTTP byte-range, sehingga PDF dibaca penuh.

Untuk kembali ke local, set `LMS_STORAGE_DRIVER=local`, clear config, dan pulihkan link `public/storage`. File baru yang hanya ada di MinIO perlu disalin kembali terlebih dahulu.

`php scripts/check-minio.php --verify-legacy` membandingkan path dan ukuran semua file di `LMS_UPLOAD_ROOT` dengan MinIO serta membaca satu sampel object. Tambahkan `--copy-missing` hanya untuk menyalin file yang belum ada; operasi menggunakan kondisi `If-None-Match: *` agar tidak menimpa object. Jalankan verifikasi lagi setelah penyalinan. Pemeriksaan ukuran bukan verifikasi checksum isi file.

Referensi: [Laravel filesystem / S3](https://laravel.com/framework/docs/filesystem).
