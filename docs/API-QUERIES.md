# Pemanggilan API dengan TanStack Query

Seluruh request JSON LMS memakai satu `QueryClient` dari `resources/js/api/queryClient.ts`, termasuk alur async yang sudah ada. Axios tetap menjadi transport HTTP untuk CSRF, cookie sesi, upload, dan blob. Tidak ada persistensi cache ke localStorage.

- `resources/js/services/queries/*Query.ts`: request baca per domain, query key dan dukungan pembatalan request.
- `resources/js/services/mutations/*Mutation.ts`: request perubahan, upload dan ekspor, beserta invalidasi domain terkait.
- `resources/js/api/query.ts`: `defineQuery`, menggunakan `fetchQuery` untuk pemanggilan async dan menyediakan `.options()` untuk `useQuery`.
- `resources/js/api/mutation.ts`: `defineMutation`, menggunakan `MutationObserver` untuk pemanggilan async dan menyediakan `.options()` untuk `useMutation`.
- `resources/js/services/types/*.ts`: tipe entitas, respons, pagination, payload mutation dan error per domain.
- `resources/js/services/*Service.ts`: re-export kompatibilitas; kode baru mengimpor langsung dari query/mutation.
- `resources/js/pages/quiz/api.js`: dispatcher kompatibilitas untuk alur kuis; GET menuju `quizQuery`, aksi lain menuju `quizMutation`.

Domain: auth, kelas/peserta/urutan, kategori, materi, progres, pengumuman/wizard, keterbacaan dokumen, master/dashboard/pencarian, laporan/ekspor, kuis dan migrasi. Widget asisten tetap nonaktif karena token kosong; transportnya berada di mutation terpisah tanpa cookie LMS. Pengambilan berkas PDF untuk watermark merupakan unduhan aset, bukan query JSON.

## Komponen reaktif

```ts
import { ref } from 'vue';
import { useQuery, useMutation } from '@tanstack/vue-query';
import { getCourses } from '@/services/queries/courseQuery.ts';
import { createCourse } from '@/services/mutations/courseMutation.ts';

const page = ref(1);
const { data, isFetching, error, refetch } = useQuery(getCourses.options(page));
const { mutateAsync, isPending } = useMutation(createCourse.options());

// Variables berupa array argumen, sesuai urutan parameter fungsi domain.
await mutateAsync([formData]);
```

Untuk update kelas: `useMutation(updateCourse.options()).mutateAsync([formData, id])`. Buat hook di `setup`, bukan di event handler. Jangan membungkus fungsi callable dengan `mutationFn: createCourse`, karena callable itu sendiri sudah menjalankan mutation.

## Alur async yang sudah ada

```ts
const result = await getCourses(2);
await updateCourse(formData, courseId);
```

Alur ini tetap tercatat di cache TanStack, menduplikasi request baca yang sedang berlangsung, serta menginvalidasi cache setelah write berhasil. Pemanggilan query async mengembalikan salinan data supaya perubahan lokal di editor tidak mengubah cache. Komponen dengan state lokal tetap memanggil fungsi load setelah aksi; hanya observer `useQuery` aktif yang otomatis refetch saat invalidasi.

`staleTime: 0` menjaga pembacaan berikutnya tetap segar karena database juga digunakan aplikasi legacy. Retry otomatis dan refetch saat fokus dinonaktifkan. Mutation tidak mengantre ketika offline: kegagalan diteruskan ke penanganan error yang sudah ada. Cache dibersihkan saat login/logout berhasil atau identitas/otorisasi Inertia berubah. Ekspor hanya dijalankan lewat mutation agar tidak terulang karena refetch.

Referensi: [TanStack Vue Query quick start](https://tanstack.com/query/latest/docs/framework/vue/quick-start) dan [mutations](https://tanstack.com/query/latest/docs/framework/vue/guides/mutations).

## TypeScript dan kontrak data

Lapisan API menggunakan TypeScript strict tanpa `any` atau `@ts-ignore`. Parameter query mendukung nilai, Vue ref, dan getter; tipe hasil otomatis tersedia di pemanggilan async maupun `useQuery`. Mutation menerima tuple argumen bertipe, termasuk FormData, progres upload dan respons Blob. Query kuis menginferensikan hasil berdasarkan path; mutation kuis membatasi kombinasi metode, path dan payload.

Jalankan `npm run typecheck` untuk memeriksa helper, transport, services, tipe domain dan `tests/types/api-contracts.ts`. Pengujian kontrak juga memastikan parameter yang salah ditolak compiler. Pemeriksaan ini belum mencakup seluruh template Vue legacy. Tipe TypeScript tidak memvalidasi JSON pada runtime; backend tetap memvalidasi request.

`npm run test:frontend` memakai dukungan TypeScript Node (`--experimental-strip-types`, Node >= 22.12). Tidak diperlukan loader atau dependency baru.
