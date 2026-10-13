# MinIO untuk LMS

Seluruh upload menggunakan MinIO dan bucket private. URL `/storage/...` tetap digunakan; Laravel meneruskan file setelah login. Thumbnail course tetap dapat dibuka tamu untuk preview publik. Endpoint harus bisa diakses oleh PHP/container.

## Konfigurasi

Isi `.env` (jangan commit kredensial):

```dotenv
AWS_ACCESS_KEY_ID=isi-access-key
AWS_SECRET_ACCESS_KEY=isi-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=nama-bucket-lms
AWS_ENDPOINT=https://endpoint-api-minio
AWS_USE_PATH_STYLE_ENDPOINT=true
MINIO_PUBLIC_PREFIX=public
```

Gunakan endpoint API S3, bukan console. Bucket harus sudah tersedia. Akun aplikasi memerlukan list bucket serta read/write/delete object untuk upload, arsip, dan restore. Kredensial diteruskan ke Docker melalui `.env`.

`MINIO_ENDPOINT`, `MINIO_ACCESS_KEY`, `MINIO_SECRET_KEY`, dan `MINIO_BUCKET` juga didukung jika nilai AWS terkait kosong. Endpoint harus berupa URL lengkap beserta port API bila bukan port standar. Di container, `localhost` menunjuk ke container itu sendiri; gunakan alamat server atau nama service pada jaringan bersama.

## Struktur object

| Disk aplikasi | Prefix object default |
| --- | --- |
| aktif | public/aktif/ |
| public | public/ |
| default (s3) | root bucket |

Bucket `lmsgadai` yang sudah diisi pengguna menyimpan `aktif/`, `tidakaktif/`, dan `uploads/` langsung di root. Untuk layout ini gunakan **`MINIO_PUBLIC_PREFIX=` (kosong)**. Path database dan URL browser tetap sama; aplikasi menyesuaikan prefix saat upload, arsip, restore, dan hapus.

Aplikasi tidak menyediakan mode upload lokal atau pembuatan storage symlink. Entrypoint Docker melepas symlink lama `public/storage` bila ada agar file tidak melewati route MinIO; target symlink tidak dihapus. Session, cache, views, dan log tetap lokal.

## Jalankan dan periksa

Setelah memperbarui kode atau konfigurasi Docker:

```powershell
docker compose up -d --build app
```

Jika memakai Laragon, jalankan `composer install` dan `php artisan config:clear`, lalu restart server aplikasi. Tidak ada migrasi database atau penyalinan data otomatis.

`php scripts/check-minio.php` memeriksa koneksi dan keberadaan object tanpa menulis ke bucket atau membandingkan folder lokal. Uji upload PDF/thumbnail, baca, arsip, dan restore dengan data uji setelah koneksi siap. Dokumen private memerlukan login. Endpoint streaming belum menyediakan HTTP byte-range, sehingga PDF dibaca penuh.
