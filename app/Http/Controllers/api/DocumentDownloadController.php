<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Services\RoleAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DocumentDownloadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'document_id' => ['required', 'integer', 'min:1'],
        ]);

        $username = (string) $request->session()->get('auth.user', '');
        if ($username === '') {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }
        if (!Schema::hasTable('document_downloads')) {
            return response()->json([
                'success' => false,
                'message' => 'Tabel document_downloads belum tersedia di database. Jalankan SQL alter terlebih dahulu.',
            ], 503);
        }

        $documentId = (int) $data['document_id'];
        $now = now();

        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                INSERT INTO document_downloads (username, document_id, first_downloaded_at, last_downloaded_at, download_count, created_at, updated_at)
                VALUES (?, ?, ?, ?, 1, ?, ?)
                ON DUPLICATE KEY UPDATE
                    last_downloaded_at = VALUES(last_downloaded_at),
                    download_count = download_count + 1,
                    updated_at = VALUES(updated_at)
            ", [$username, $documentId, $now, $now, $now, $now]);
        } else {
            $existing = DB::table('document_downloads')
                ->where('username', $username)
                ->where('document_id', $documentId)
                ->first();

            if ($existing) {
                DB::table('document_downloads')
                    ->where('id', $existing->id)
                    ->update([
                        'last_downloaded_at' => $now,
                        'download_count' => (int) $existing->download_count + 1,
                        'updated_at' => $now,
                    ]);
            } else {
                DB::table('document_downloads')->insert([
                    'username' => $username,
                    'document_id' => $documentId,
                    'first_downloaded_at' => $now,
                    'last_downloaded_at' => $now,
                    'download_count' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Document download recorded successfully.',
        ], 201);
    }

    public function downloadedUsers(Request $request, int $document_id): JsonResponse
    {
        abort_unless(
            RoleAccess::canManageLearning($request)
                || RoleAccess::canManageDocuments($request)
                || RoleAccess::isAudit($request),
            403
        );

        if (!Schema::hasTable('document_downloads')) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $downloads = DB::table('document_downloads')
            ->where('document_id', $document_id)
            ->orderByDesc('last_downloaded_at')
            ->get();

        $userMap = [];
        try {
            $usernames = $downloads->pluck('username')->unique()->filter()->values()->all();
            if (!empty($usernames)) {
                $userMap = DB::connection('db2')
                    ->table('auth.users')
                    ->whereIn('username', $usernames)
                    ->pluck('full_name', 'username')
                    ->all();
            }
        } catch (Throwable $e) {
            // fallback when db2 is unavailable
        }

        $data = $downloads->map(function ($row) use ($userMap) {
            return [
                'username' => $row->username,
                'full_name' => $userMap[$row->username] ?? $row->username,
                'first_downloaded_at' => $row->first_downloaded_at,
                'last_downloaded_at' => $row->last_downloaded_at,
                'download_count' => (int) $row->download_count,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
