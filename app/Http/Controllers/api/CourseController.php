<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Http\Resources\CourseResource;
use App\Services\CourseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Services\Quiz\AudienceDirectory;

class CourseController extends Controller
{
    /**
     * @var CourseService
     */
    protected $courseService;

    /**
     * CourseController Constructor.
     *
     * @param CourseService $courseService
     */
    public function __construct(CourseService $courseService)
    {
        $this->courseService = $courseService;
    }

    /**
     * Display a listing of courses.
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $page = $request->query('page', 1);
        $courses = $this->courseService->getCoursesPaginated((int) $page, $request);

        return CourseResource::collection($courses);
    }

    public function popular()
    {
        $courses = \App\Models\Course::query()->where('is_popular', true)
            ->orderByDesc('id')->get(['id', 'name', 'description', 'thumbnail']);

        return response()->json(['data' => $courses->map(fn ($course) => [
            'id' => $course->id,
            'name' => $course->name,
            'description' => $course->description,
            'thumbnail_url' => $course->thumbnail
                ? config('services.mix.img_url').ltrim($course->thumbnail, '/') : null,
        ])]);
    }

    /**
     * Display the specified course with contents.
     *
     * @param Request $request
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        $this->courseService->assertAccessible((int) $id, $request);
        $page = $request->query('page', 1);
        $result = $this->courseService->getCourseWithContents((int) $id, (int) $page);

        return response()->json([
            'course' => new CourseResource($result['course']),
            'contents' => $result['contents']
        ]);
    }

    /**
     * Display the specified course contents for preview.
     *
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function preview($id)
    {
        // Keep public landing previews available for guests; signed-in employees are filtered.
        if (request()->session()->get('auth.status') === 'login') {
            $this->courseService->assertAccessible((int) $id, request());
        }
        $result = $this->courseService->previewCourse((int) $id);

        return response()->json([
            'course' => new CourseResource($result['course']),
            'contents' => $result['contents']
            , 'access_positions' => request()->session()->get('auth.status') === 'login'
                ? $this->courseService->accessPositions((int) $id) : []
        ]);
    }

    public function accessOptions(Request $request)
    {
        return ['positions' => app(AudienceDirectory::class)->positions()];
    }

    public function access(Request $request, $id)
    {
        $this->courseService->assertAccessible((int) $id, $request);
        return ['positions' => $this->courseService->accessPositions((int) $id)];
    }

    /**
     * Store a newly created course in storage.
     *
     * @param StoreCourseRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreCourseRequest $request)
    {
        $access = app(\App\Services\CourseAudience::class)->validate($request);
        $course = $this->courseService->createCourse(
            $request->validated(),
            $request->file('thumbnail')
        );
        app(\App\Services\CourseAudience::class)->save($course->id, $access);

        return response()->json([
            'status' => 201,
            'message' => 'Course created successfully',
            'data' => new CourseResource($course)
        ]);
    }

    /**
     * Update the specified course in storage.
     *
     * @param UpdateCourseRequest $request
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateCourseRequest $request, $id)
    {
        try {
            $access = app(\App\Services\CourseAudience::class)->validate($request);
            $course = $this->courseService->updateCourse(
                (int) $id,
                $request->validated(),
                $request->file('thumbnail')
            );
            app(\App\Services\CourseAudience::class)->save((int) $id, $access);

            return response()->json([
                'status' => 200,
                'message' => 'Course updated successfully',
                'data' => new CourseResource($course)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to update course: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified course from storage.
     *
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $this->courseService->deleteCourse((int) $id);

            return response()->json([
                'status' => 200,
                'message' => 'Course deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 400,
                'message' => 'Course not found'
            ], 400);
        }
    }

    /**
     * Search courses and documents.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');

        if (empty($query)) {
            return response()->json([
                'success' => false,
                'message' => 'Query parameter is required',
                'data' => []
            ]);
        }

        $jbt = Session::get('auth.idgrup');
        $cabang = Session::get('auth.cabang');

        $result = $this->courseService->search($query, $jbt, $cabang);

        $courseResults = $result['courses']->map(function ($course) {
            $mixImg = config('services.mix.img_url');
            $thumbUrl = $course->thumbnail
                ? ($mixImg ? ($mixImg . ltrim($course->thumbnail, '/')) : asset('storage/' . ltrim($course->thumbnail, '/')))
                : null;

            return [
                'id' => $course->id,
                'title' => $course->name,
                'tagline' => $course->tagline,
                'description' => $course->description,
                'category' => $course->category ? $course->category->name : null,
                'thumbnail_url' => $thumbUrl,
                'type' => 'course',
            ];
        });

        $documentResults = $result['documents']->map(function ($doc) {
            $mixUrl = config('services.mix.url');
            $contentUrl = $doc->url
                ? ($mixUrl ? ($mixUrl . ltrim($doc->url, '/')) : asset('storage/aktif/' . ltrim($doc->url, '/')))
                : null;

            $dateLastUpdate = $doc->updated_at
                ? $doc->updated_at->format('d M Y H:i:s') . ' WIB'
                : ($doc->created_at ? $doc->created_at->format('d M Y H:i:s') . ' WIB' : null);

            $tglBerlaku = $doc->tgl_berlaku
                ? (\Illuminate\Support\Carbon::parse($doc->tgl_berlaku)->format('d-m-Y'))
                : null;

            return [
                'id' => $doc->id,
                'title' => $doc->title,
                'tagline' => $doc->no_surat ?? '',
                'description' => $doc->content ?? '',
                'submenu_id' => $doc->submenu_id,
                'tgl_berlaku' => $tglBerlaku,
                'url' => $doc->url,
                'content_url' => $contentUrl,
                'no_surat' => $doc->no_surat,
                'content' => $doc->content,
                'dateLastUpdate' => $dateLastUpdate,
                'menu' => $doc->menu ? $doc->menu->name : null,
                'category' => $doc->menu ? $doc->menu->name : null,
                'thumbnail_url' => $doc->menu ? $doc->menu->icon : null,
                'type' => $doc->type ?? 'pdf',
            ];
        });

        return response()->json([
            'success' => true,
            'courses' => $courseResults,
            'documents' => $documentResults,
            'total' => $courseResults->count() + $documentResults->count(),
            'data' => [
                'courses' => $courseResults,
                'documents' => $documentResults,
            ]
        ]);
    }
}
