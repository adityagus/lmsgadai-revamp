# Docker Compose

Jalankan dari root proyek menggunakan Docker Desktop (Linux containers) dan Docker Compose v2. Image memakai PHP 8.3/Apache, ekstensi MySQL/PostgreSQL/GD/ZIP untuk database dan Excel, serta Node 22 untuk build Vite. Aset dibundel saat build; perubahan kode membutuhkan rebuild.

## Konfigurasi

Gunakan `.env` proyek yang sudah berisi `APP_KEY`, koneksi LMS (`DB_*`), koneksi karyawan (`DB2_*`), dan pengaturan email. Jika belum ada, salin `.env.example` ke `.env` lalu lengkapi nilainya. Jangan mengganti key aplikasi yang sudah ada.

Tambahkan pengaturan berikut ke `.env` jika kedua database berjalan di komputer host/Laragon:

```dotenv
DOCKER_APP_PORT=8002
DOCKER_APP_URL=http://localhost:8002
DOCKER_DB_HOST=host.docker.internal
DOCKER_DB2_HOST=host.docker.internal
```

Untuk database remote, Compose menggunakan `DB_HOST`/`DB2_HOST` yang ada; jangan tambahkan override host tersebut. `127.0.0.1` di container menunjuk ke container sendiri. Database host harus menerima koneksi dari Docker. Port, nama database, dan kredensial tetap berasal dari `.env`. Jika email memakai SMTP di host, sesuaikan `MAIL_HOST` ke `host.docker.internal`.

Upload hanya menggunakan MinIO, tanpa bind mount upload legacy. Isi `AWS_ENDPOINT` (endpoint API S3, bukan console), `AWS_BUCKET`, `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, dan `AWS_USE_PATH_STYLE_ENDPOINT=true` di `.env`. Alias `MINIO_*` yang didukung aplikasi juga dapat digunakan. Endpoint harus dapat dijangkau dari container; gunakan override jaringan bersama di bawah bila diperlukan. `.env` dan upload tidak disalin ke image.

## Jalankan

```powershell
docker compose config --quiet
docker compose up -d --build
docker compose logs -f app
```

Buka http://localhost:8002. Jika port diubah, ubah juga `DOCKER_APP_URL`. `APP_PORT` masih diterima jika `DOCKER_APP_PORT` belum diisi. Debug container default mati; gunakan `DOCKER_APP_DEBUG=true` hanya saat debugging lokal. Port hanya dipublikasikan ke localhost. Konfigurasi ini untuk menjalankan aplikasi lokal; publikasi ke internet memerlukan konfigurasi HTTPS/domain tersendiri.

Compose utama membuat jaringan sendiri, sehingga tidak membutuhkan `staging_shared_network`. Jika database atau MinIO memakai nama container pada jaringan bersama, tambahkan `DOCKER_NETWORK=shared_db_network` (atau nama jaringan yang sudah ada) dan jalankan:

```powershell
docker compose -f docker-compose.yaml -f docker-compose.shared.yaml up -d --build
```

Gunakan kedua argumen `-f` tersebut juga untuk perintah `config`, `logs`, dan `down` pada konfigurasi jaringan bersama. Set `DOCKER_DB_HOST`/`DOCKER_DB2_HOST` ke nama container database yang sesuai; endpoint MinIO tetap berasal dari `AWS_ENDPOINT`. Seluruh konfigurasi runtime, termasuk database karyawan dan MinIO, dibaca dari `.env` melalui `env_file`.

```powershell
docker compose down
```

Session/cache disimpan dalam named volume `runtime`; seluruh upload memakai MinIO. Compose tidak membuat database, menjalankan migrasi, reset, seed, scheduler, atau worker. Perintah email tetap dijalankan manual bila dibutuhkan.

Validasi konfigurasi dengan `--quiet` agar nilai kredensial tidak dicetak. Setelah perubahan kode/dependency, jalankan kembali `docker compose up -d --build`.

Jika halaman masuk terbuka tetapi daftar kelas atau login menunggu sampai timeout, pastikan server `DB_HOST` dan `DB2_HOST` dapat dijangkau dari komputer dan container (termasuk VPN bila diperlukan). `127.0.0.1` perlu override host seperti contoh di atas; alamat server remote tetap memerlukan jalur jaringan yang aktif. Jangan mengganti tujuan database tanpa memastikan database LMS dan karyawan yang dimaksud tersedia di sana.
