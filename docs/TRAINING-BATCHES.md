# Menggunakan Batch Pelatihan

Buka menu **Batch Pelatihan** di sidebar, atau `/batch-pelatihan`.

## Admin dan trainer

1. Klik **Buat batch**. Isi nama, misalnya **Karyawan Baru Oktober**, catatan, tanggal mulai, dan tanggal selesai.
2. Pilih kelas pembelajaran yang sudah tersedia. Materi dan kuis tetap dikelola di kelas tersebut.
3. Cari nama atau username karyawan (minimal dua huruf), lalu klik hasil pencarian untuk menambahkan peserta. Sumbernya akun karyawan aktif di `db_gadaimulia`.
4. Simpan sebagai **Draft** bila masih menyiapkan peserta. Draft hanya terlihat oleh pengelola.
5. Ubah status menjadi **Aktif** setelah peserta dan kelas siap. Minimal satu peserta dan satu kelas diperlukan.
6. Klik kartu batch untuk melihat progres setiap peserta pada setiap kelas. Gunakan **Edit batch** untuk mengubah peserta, kelas, periode, atau status.
7. Status **Selesai** menghentikan pemberian akses melalui batch, tetapi data batch dan progres tetap tersedia untuk laporan.

Trainer mengelola batch buatannya sendiri. Admin dapat mengelola semua batch. Jika batch sudah diubah di tab lain, muat ulang detail sebelum mengedit kembali.

## Karyawan

Menu yang sama menampilkan batch non-draft yang diikuti karyawan. Detail hanya menampilkan progres miliknya sendiri. Klik nama kelas untuk membuka ruang belajar selama akses batch berlaku.

Peserta sekarang melihat **Pelatihan Saya** pada menu **Pembelajaran** dan **Batch Pelatihan**, dengan tombol **Mulai Belajar**, **Lanjutkan Belajar**, atau **Buka Kembali**. Kelas penugasan tampil terpisah dari paginasi katalog kelas. Klik **Perbarui** setelah trainer memperbarui penugasan.

Jika muncul **Ditutup oleh trainer**, status pelaksanaan batch adalah **Selesai**; itu bukan penanda peserta sudah menyelesaikan materi. Admin/trainer perlu membuka **Edit batch**, memilih **Aktif**, memastikan periode berlaku, lalu menyimpan. Batch di luar periode juga menjelaskan kapan akses dimulai atau meminta peserta menghubungi trainer. Hak akses dari sumber lain tetap dapat membuka kelas meskipun batch telah ditutup.

Akses batch berlaku pada status **Aktif**, mulai tanggal awal sampai tanggal akhir (inklusif, mengikuti zona waktu aplikasi). Akses kelas melalui jabatan, penugasan individu, atau batch lain tetap berlaku secara terpisah.

## Batas versi pertama

- Progres bersifat kumulatif per karyawan dan kelas, termasuk aktivitas sebelum batch dibuat. Persentase selesai menunjukkan materi/kuis yang sudah diselesaikan; bukan status lulus ujian.
- Menambahkan karyawan ke batch berikutnya tidak mereset progres materi atau menghapus nilai. Percobaan kuis baru yang dimulai dari link batch di Pelatihan Saya kini dicatat per batch, dengan kuota percobaan terpisah; jadwal kuis tetap berlaku. Pengulangan seluruh materi dengan progres terpisah belum tersedia. Lihat [riwayat dan rekaman versi](LEARNING-HISTORY.md).
- Pemilihan peserta dilakukan per akun melalui pencarian. Pemilihan massal berdasarkan cabang/jabatan dan penunjukan trainer lain belum tersedia.
- Maksimal 200 peserta dan 20 kelas per batch. Keanggotaan tersimpan, tidak berubah otomatis mengikuti mutasi jabatan.
- Tidak ada batch contoh atau karyawan palsu yang dimasukkan ke database bersama.

## Catatan implementasi

Batch menggunakan tabel baru `training_batches`, `training_batch_members`, dan `training_batch_courses`. Tabel `classes`/`class_students`/`quiz_classes` tetap untuk kompatibilitas kuis lama. Penghubung ID kuis pada `lms_users` tetap mengikuti keputusan sebelumnya; nama karyawan dibaca dari database karyawan.
