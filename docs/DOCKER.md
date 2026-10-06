# Docker Compose

Jalankan dari root proyek menggunakan Docker Desktop (Linux containers) dan Docker Compose v2. Image memakai PHP 8.3/Apache, ekstensi MySQL/PostgreSQL/GD/ZIP untuk database dan Excel, serta Node 22 untuk build Vite. Aset dibundel saat build; perubahan kode membutuhkan rebuild.

## Konfigurasi

Gunakan `.env` proyek yang sudah berisi `APP_KEY`, koneksi LMS (`DB_*`), koneksi karyawan (`DB2_*`), dan pengaturan email. Jika belum ada, salin `.env.example` ke `.env` lalu lengkapi nilainya. Jangan mengganti key aplikasi yang sudah ada.

Tambahkan pengaturan berikut ke `.env` jika kedua database berjalan di komputer host/Laragon:

```dotenv
DOCKER_APP_PORT=8001
DOCKER_APP_URL=http://localhost:8001
DOCKER_DB_HOST=host.docker.internal
DOCKER_DB2_HOST=host.docker.internal
DOCKER_UPLOAD_PATH=C:/laragon/www/lmsgadai/storage/app
```

Untuk database remote, Compose menggunakan `DB_HOST`/`DB2_HOST` yang ada; jangan tambahkan override host tersebut. `127.0.0.1` di container menunjuk ke container sendiri. Database host harus menerima koneksi dari Docker. Port, nama database, dan kredensial tetap berasal dari `.env`. Jika email memakai SMTP di host, sesuaikan `MAIL_HOST` ke `host.docker.internal`.

`DOCKER_UPLOAD_PATH` harus menunjuk ke folder **storage/app**, yang berisi folder `public`, agar upload aktif dan arsip tetap memakai lokasi legacy. Folder harus sudah ada dan dapat ditulis oleh container (www-data/UID 33 pada Linux). Startup tidak mengubah kepemilikan folder legacy. `.env` dan upload tidak disalin ke image.

## Jalankan

```powershell
docker compose config --quiet
docker compose up -d --build
docker compose logs -f app
```

Buka http://localhost:8001. Jika port diubah, ubah juga `DOCKER_APP_URL`. Port hanya dipublikasikan ke localhost. Konfigurasi ini untuk menjalankan aplikasi lokal; publikasi ke internet memerlukan konfigurasi HTTPS/domain tersendiri.

```powershell
docker compose down
```

Session/cache disimpan dalam named volume `runtime`; upload tetap di folder legacy. Compose tidak membuat database, menjalankan migrasi, reset, seed, scheduler, atau worker. Perintah email tetap dijalankan manual bila dibutuhkan.

Validasi konfigurasi dengan `--quiet` agar nilai kredensial tidak dicetak. Setelah perubahan kode/dependency, jalankan kembali `docker compose up -d --build`.
