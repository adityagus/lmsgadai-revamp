import { onScopeDispose, ref } from 'vue';

export function useCourseList(getCourses) {
  const courses = ref([]);
  const pagination = ref(1);
  const totalPages = ref(0);
  const loading = ref(true);
  const loaded = ref(false);
  const error = ref('');
  let version = 0;
  let requestedPage = 1;

  async function fetchCourses(page = pagination.value) {
    const request = ++version;
    requestedPage = page;
    loading.value = true;
    error.value = '';
    try {
      const result = await getCourses(page);
      if (request !== version) return;
      courses.value = result.data;
      totalPages.value = result.meta?.last_page ?? result.last_page ?? 1;
      pagination.value = page;
      loaded.value = true;
    } catch (e) {
      if (request === version) error.value = 'Kelas gagal dimuat. Silakan coba lagi.';
    } finally {
      if (request === version) loading.value = false;
    }
  }

  function goToPage(page) {
    if (loading.value && requestedPage === page) return;
    if (!loading.value && loaded.value && pagination.value === page) return;
    return fetchCourses(page);
  }

  onScopeDispose(() => { version++; });
  return { courses, pagination, totalPages, loading, loaded, error, fetchCourses, goToPage };
}
