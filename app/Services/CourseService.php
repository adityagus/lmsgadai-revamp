<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Announcement;
use App\Contracts\FileUploadServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CourseService
{
    /**
     * @var FileUploadServiceInterface
     */
    protected $fileUploadService;

    /**
     * CourseService Constructor.
     *
     * @param FileUploadServiceInterface $fileUploadService
     */
    public function __construct(FileUploadServiceInterface $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Get the current paginated courses list.
     *
     * @param int $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getCoursesPaginated(int $page, $request = null)
    {
        // Query fresh: Laravel 13 disallows cached PHP objects, and both apps edit these courses.
        $query = Course::with('category:id,name')->orderByDesc('id');
        if ($request && Schema::hasTable('course_access_rules') && $request->session()->get('auth.status') === 'login' && !in_array(app(LmsAccess::class)->role($request), ['admin', 'trainer', 'teacher'], true)) {
            $allowed = DB::table('course_access_rules')->where('position_code', $request->session()->get('auth.idgrup'))->pluck('course_id');
            $allowed = $allowed->merge(app(TrainingBatchAccess::class)->courseIds((string) $request->session()->get('auth.user')));
            if (Schema::hasTable('course_participants')) {
                $allowed = $allowed->merge(DB::table('course_participants')->where('username', $request->session()->get('auth.user'))->pluck('course_id'));
            }
            $query->where(function ($q) use ($allowed) {
                $q->whereNotIn('id', DB::table('course_access_rules')->select('course_id')->distinct())
                    ->orWhereIn('id', $allowed);
            });
        }
        return $query->paginate(5, ['*'], 'page', max(1, $page));
    }

    /**
     * Get single course details with paginated contents.
     *
     * @param int $id
     * @param int $page
     * @return array
     */
    public function getCourseWithContents(int $id, int $page)
    {
        $course = Course::with('category:id,name')->findOrFail($id);
        $contents = $course->contents()->orderBy('order', 'asc')->paginate(5, ['*'], 'page', $page);

        return [
            'course' => $course,
            'contents' => $contents
        ];
    }

    /**
     * Preview single course details with all contents and cache as last previewed.
     *
     * @param int $id
     * @return array
     */
    public function previewCourse(int $id)
    {
        $course = Course::with('category:id,name')->findOrFail($id);
        $contents = $course->contents()->orderBy('order')->get();

        Cache::put('last_previewed_course_' . session('auth.user'), $course->toArray(), now()->addDays(7));

        return [
            'course' => $course,
            'contents' => $contents
        ];
    }

    public function assertAccessible(int $id, $request): void
    {
        app(CourseAudience::class)->assertAllowed($id, $request);
    }

    public function accessPositions(int $id): array
    {
        return app(CourseAudience::class)->read($id);
    }

    /**
     * Store new course.
     *
     * @param array $data
     * @param \Illuminate\Http\UploadedFile $thumbnail
     * @return Course
     */
    public function createCourse(array $data, $thumbnail)
    {
        $slug = Str::slug($data['name']);
        $originalSlug = $slug;
        $count = 1;
        while (Course::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        $thumbnailPath = $this->fileUploadService->upload($thumbnail, 'uploads/courses');

        $course = new Course();
        $course->name = $data['name'];
        $course->slug = $slug;
        $course->students = 'null';
        $course->details = 'null';
        $course->tagline = $data['tagline'];
        $course->thumbnail = $thumbnailPath;
        $course->description = $data['description'];
        $course->category_id = $data['category_id'];
        $course->is_popular = 0;
        $course->save();


        return $course;
    }

    /**
     * Update an existing course.
     *
     * @param int $id
     * @param array $data
     * @param \Illuminate\Http\UploadedFile|null $thumbnail
     * @return Course
     */
    public function updateCourse(int $id, array $data, $thumbnail = null)
    {
        $course = Course::findOrFail($id);

        if ($data['name'] !== $course->name) {
            $slug = Str::slug($data['name']);
            $originalSlug = $slug;
            $count = 1;
            while (Course::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                $slug = $originalSlug . '-' . $count;
                $count++;
            }
            $course->slug = $slug;
        }

        if ($thumbnail) {
            $thumbnailPath = $this->fileUploadService->upload($thumbnail, 'uploads/courses');

            if ($course->thumbnail) {
                $this->fileUploadService->delete($course->thumbnail, 'aktif');
            }

            $course->thumbnail = $thumbnailPath;
        }

        $course->name = $data['name'];
        $course->tagline = $data['tagline'];
        $course->description = $data['description'];
        $course->category_id = $data['category_id'];
        $course->is_popular = $data['is_popular'];

        if (isset($data['students'])) {
            $course->students = $data['students'];
        }
        if (isset($data['details'])) {
            $course->details = $data['details'];
        }

        $course->save();


        return $course;
    }

    /**
     * Delete a course.
     *
     * @param int $id
     * @return void
     */
    public function deleteCourse(int $id)
    {
        $course = Course::findOrFail($id);
        $course->delete();
    }

    /**
     * Search courses and documents.
     *
     * @param string $query
     * @param string|null $jbt
     * @param string|null $cabang
     * @return array
     */
    public function search(string $query, $jbt, $cabang)
    {
        $courses = Course::with('category:id,name')
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('tagline', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%")
                  ->orWhereHas('category', function ($catQ) use ($query) {
                      $catQ->where('name', 'LIKE', "%{$query}%");
                  });
            })
            ->limit(15)
            ->get();

        $docQuery = Announcement::with('menu')->where(function ($q) use ($query) {
            $q->where('title', 'LIKE', "%{$query}%")
              ->orWhere('no_surat', 'LIKE', "%{$query}%")
              ->orWhere('content', 'LIKE', "%{$query}%")
              ->orWhereHas('menu', function ($menuQ) use ($query) {
                  $menuQ->where('name', 'LIKE', "%{$query}%");
              });
        });

        if ($cabang === null || $cabang === '') {
            $documents = $docQuery->orderBy('tgl_berlaku', 'desc')->limit(15)->get();
        } else {
            $documents = $docQuery->whereHas('document_position', function ($qp) use ($jbt) {
                $qp->where('kd_jbt', $jbt);
            })
            ->whereHas('document_regional', function ($qr) use ($cabang) {
                $qr->where('regional_id', $cabang);
            })
            ->orderBy('tgl_berlaku', 'desc')
            ->limit(15)
            ->get();
        }

        return [
            'courses' => $courses,
            'documents' => $documents
        ];
    }

}
