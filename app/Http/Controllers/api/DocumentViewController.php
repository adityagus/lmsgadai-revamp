<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentViewRequest;
use App\Contracts\Services\DocumentViewServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Session;

class DocumentViewController extends Controller
{
    /**
     * @var DocumentViewServiceInterface
     */
    protected $service;

    /**
     * DocumentViewController Constructor.
     *
     * @param DocumentViewServiceInterface $service
     */
    public function __construct(DocumentViewServiceInterface $service)
    {
        $this->service = $service;
    }

    /**
     * Record a new view log.
     * POST /api/v1/document-views
     *
     * @param StoreDocumentViewRequest $request
     * @return JsonResponse
     */
    public function store(StoreDocumentViewRequest $request): JsonResponse
    {
        $username = Session::get('auth.user');
        $documentId = (int) $request->validated()['document_id'];
        try {
            $this->service->recordView($username, $documentId);
            return response()->json([
                'success' => true,
                'message' => 'Document view recorded successfully.'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to record document view: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if current user has viewed a document.
     * GET /api/v1/document-views/document/{document_id}/check
     *
     * @param int $document_id
     * @return JsonResponse
     */
    public function check(int $document_id): JsonResponse
    {
        if (!Session::has('auth')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $username = Session::get('auth.user');
        $hasViewed = $this->service->hasViewed($username, $document_id);

        return response()->json([
            'success' => true,
            'data' => [
                'has_viewed' => $hasViewed
            ]
        ]);
    }

    /**
     * Get list of users who have not viewed a specific document.
     * GET /api/v1/document-views/document/{document_id}/unviewed
     *
     * @param int $document_id
     * @return JsonResponse
     */
    public function unviewedUsers(int $document_id): JsonResponse
    {
        try {
            $users = $this->service->getUsersWhoHaveNotViewed($document_id);
            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get unviewed users: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get list of users who have viewed a specific document.
     * GET /api/v1/document-views/document/{document_id}/viewed
     *
     * @param int $document_id
     * @return JsonResponse
     */
    public function viewedUsers(int $document_id): JsonResponse
    {
        try {
            $users = $this->service->getUsersWhoHaveViewed($document_id);
            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get viewed users: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get aggregate statistics of document views.
     * GET /api/v1/document-views/stats
     *
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->service->getDocumentViewStats();
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}
