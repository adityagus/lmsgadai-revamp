# Riwayat belajar dan rekaman versi

## Membuka riwayat

- Buka **Riwayat Belajar** di sidebar (`/riwayat-belajar`).
- Pengelola dapat memakai tab **Riwayat** di **Bank Soal & Peserta**.
- Pada detail batch, pilih **Riwayat kuis** untuk memfilter satu batch, atau **Lihat riwayat** pada baris peserta.
- Peserta dapat memakai **Riwayat kuis batch** pada kartu Pelatihan Saya. Untuk melihat percobaan sebelum pencatatan batch, buka semua riwayat.

Setiap baris menampilkan peserta, batch jika tercatat, versi kuis, nomor percobaan, waktu, status dan nilai yang sudah dirilis. **Lihat detail** menampilkan jawaban, poin, feedback dan rekaman materi. Hasil peserta lain tidak dapat dibuka oleh peserta biasa; trainer hanya melihat kuis miliknya atau riwayat batch yang dikelolanya, admin seluruh riwayat.

## Apa yang dipertahankan

Percobaan baru dari revamp merekam teks soal, pilihan, kunci, bobot, pembahasan, konfigurasi penilaian dan isi/referensi materi pada saat kuis dimulai. Validasi jawaban, penilaian, review dan penilaian esai menggunakan rekaman soal tersebut. Perubahan sumber setelah mulai tidak mengubah rekaman ini. Soal dan materi yang belum pernah direkam tidak direkonstruksi dari dugaan.

Koreksi nilai esai tetap diperbolehkan; log menyimpan poin sebelum/sesudah, feedback, waktu dan penilai. Jadi skor hasil penilaian dapat diperbarui dengan jejak perubahan. Pengaturan rilis nilai/kunci tetap mengikuti kendali trainer, termasuk pada halaman riwayat.

Detail yang dibuka peserta hanya menampilkan materi yang sudah tercatat selesai ketika percobaan dimulai. Pengelola dapat melihat seluruh rekaman kelas. Teks dan referensi file disimpan; berkas tidak disalin dan konten layanan eksternal tidak dapat dijamin tetap sama.

## Kaitan dengan batch

Mulai kelas melalui **Pelatihan Saya → Mulai/Lanjutkan Belajar** agar konteks batch dibawa sampai ke percobaan. Server memeriksa keanggotaan, kelas, status aktif dan periode batch. Percobaan dari batch lain memiliki kuota sendiri; nomor percobaan internal tetap berurutan untuk menjaga relasi database lama. Satu kuis tidak dapat dikerjakan serentak dari dua penugasan berbeda.

Menutup batch atau menghapus keanggotaan tidak menghapus riwayat yang telah tercatat. Peserta tetap dapat melihat hasil miliknya yang sudah dikumpulkan, mengikuti aturan rilis. Batch asal dan namanya disimpan bersama rekaman.

Percobaan lama dan percobaan dari aplikasi legacy tidak otomatis diberi versi atau batch. Tampilan **Sebelum pencatatan versi** / **Tanpa catatan batch** menjelaskan keterbatasan tersebut. Nilai lama tidak ditimpa atau dipindahkan ke batch berdasarkan perkiraan.

## Batas perubahan ini

Ini adalah rekaman pada saat percobaan, bukan penguncian seluruh kelas ketika batch diaktifkan. Materi aktif tetap mengikuti kelas bersama dan progres materi masih kumulatif. Mengunci versi seluruh kelas untuk semua peserta batch, memilih pembaruan kelas per batch, dan mengulang seluruh materi dengan progres terpisah belum diterapkan.

Perubahan materi melalui controller juga merekam versi sebelum/sesudah pengeditan/penghapusan di `learning_content_versions`; belum ada editor pemulihan versi materi. Tidak ada backfill isi lama yang sudah hilang.

## Pemeriksaan

Backend diuji pada SQLite memory; tampilan diuji di Edge headless dengan data contoh, bukan akun karyawan sungguhan. Jalankan `node scripts/check-learning-browser.mjs` dengan Edge tersedia untuk memeriksa tab, pagination peserta, riwayat, formulir batch dan overflow desktop/mobile. Screenshot berada di `storage/app/ui-checks`.
