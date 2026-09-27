<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnnouncementRequest;
use App\Http\Requests\UpdateAnnouncementRequest;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use App\Models\Menu;
use App\Services\AnnouncementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class AnnouncementController extends Controller
{
    /**
     * @var AnnouncementService
     */
    protected $announcementService;

    /**
     * AnnouncementController Constructor.
     *
     * @param AnnouncementService $announcementService
     */
    public function __construct(AnnouncementService $announcementService)
    {
        $this->announcementService = $announcementService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $category = $request->query('category');
        $jbt = Session::get('auth.idgrup');
        $cabang = Session::get('auth.cabang');

        $announcements = $this->announcementService->getAnnouncementsByCategory($category, $jbt, $cabang);

        return response()->json([
            'success' => true,
            'data' => $announcements
        ]);
    }

    /**
     * Display details of a menu/submenu.
     *
     * @param Request $request
     * @param int|string $menu_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail(Request $request, $menu_id)
    {
        $jbt = Session::get('auth.idgrup');
        $cabang = Session::get('auth.cabang');

        $result = $this->announcementService->getAnnouncementDetails($menu_id, $jbt, $cabang);

        return response()->json([
            'success' => true,
            'detail' => $result['detail'],
            'items' => AnnouncementResource::collection($result['items'])
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreAnnouncementRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreAnnouncementRequest $request)
    {
        $announcement = $this->announcementService->createAnnouncement(
            $request->validated(),
            $request->file('dokumen')
        );

        return response()->json([
            'success' => true,
            'message' => 'Announcement created successfully',
            'data' => new AnnouncementResource($announcement)
        ], 201);
    }

    /**
     * Display the specified resource by ID.
     *
     * @param int|string $document_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function documentById($document_id)
    {
        try {
            $announcement = Announcement::with(['document_position:document_id,kd_jbt', 'menu:id,id_menu', 'document_regional'])
                ->findOrFail($document_id);

            return response()->json([
                'success' => true,
                'data' => new AnnouncementResource($announcement)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Announcement not found: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateAnnouncementRequest $request
     * @param int|string $document_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateAnnouncementRequest $request, $document_id)
    {
        try {
            $content = $request->all();
            // dd($content);
            $announcement = $this->announcementService->updateAnnouncement(
                (int) $document_id,
                $request->validated(),
                $request->file('dokumen')
            );

            return response()->json([
                'success' => true,
                'message' => 'Announcement updated successfully',
                'data' => new AnnouncementResource($announcement)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage (Soft Delete).
     *
     * @param int|string $document_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($document_id)
    {
        try {
            $this->announcementService->deleteAnnouncement((int) $document_id);

            return response()->json([
                'success' => true,
                'message' => 'Announcement deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of soft-deleted resources.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function trash(Request $request)
    {
        $category = $request->query('category');

        $trashedAnnouncements = Announcement::with('menu')
            ->onlyTrashed()
            ->whereHas('menu', function ($q) use ($category) {
                $q->where('id_menu', $category);
            })
            ->get();

        return response()->json([
            'success' => true,
            'data' => AnnouncementResource::collection($trashedAnnouncements)
        ], 200);
    }

    /**
     * Restore the specified soft-deleted resource.
     *
     * @param int|string $document_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore($document_id)
    {
        try {
            $announcement = $this->announcementService->restoreAnnouncement((int) $document_id);

            return response()->json([
                'status' => 'success',
                'message' => 'Announcement restored successfully',
                'data' => new AnnouncementResource($announcement)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Internal Server Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch document details for preview and cache last accessed documents.
     *
     * @param int|string $document_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function lastDocumentPreview($document_id)
    {
        try {
            $announcement = Announcement::with(['document_position:document_id,kd_jbt', 'menu:id,id_menu', 'document_regional'])
                ->findOrFail($document_id);

            $resource = new AnnouncementResource($announcement);
            $announcementArr = [
                'id' => $resource->id,
                'title' => $resource->title,
                'url' => $resource->content_url,
                'no_surat' => $resource->no_surat,
                'tgl_berlaku' => $resource->tgl_berlaku,
                'type' => $resource->type,
                'accessed_at' => now()->toDateString(),
            ];

            $userKey = 'last_previewed_document_' . Session::get('auth.user');
            $lastDocuments = Cache::get($userKey, []);

            // Ensure no duplicate IDs in cached history
            $lastDocuments = array_filter($lastDocuments, function ($doc) use ($announcementArr) {
                return $doc['id'] !== $announcementArr['id'];
            });

            array_unshift($lastDocuments, $announcementArr);
            $lastDocuments = array_slice($lastDocuments, 0, 2);

            Cache::put($userKey, $lastDocuments, now()->addDays(7));

            return response()->json([
                'success' => true,
                'data' => $announcementArr
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Announcement not found: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Permanently delete the specified resource.
     *
     * @param int|string $document_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function hardDelete($document_id)
    {
        try {
            $this->announcementService->hardDeleteAnnouncement((int) $document_id);

            return response()->json([
                'status' => 'success',
                'message' => 'Announcement permanently deleted'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Internal Server Error'
            ], 500);
        }
    }
}
