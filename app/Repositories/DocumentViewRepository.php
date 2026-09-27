<?php

namespace App\Repositories;

use App\Contracts\Repositories\DocumentViewRepositoryInterface;
use App\Models\DocumentView;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DocumentViewRepository implements DocumentViewRepositoryInterface
{
    /**
     * @var DocumentView
     */
    protected $model;

    /**
     * DocumentViewRepository Constructor.
     *
     * @param DocumentView $model
     */
    public function __construct(DocumentView $model)
    {
        $this->model = $model;
    }

    /**
     * @inheritDoc
     */
    public function recordView(string $username, int $documentId): void
    {
        $now = now();

        // Menggunakan SQL raw untuk efisiensi Upsert MySQL tanpa memicu SELECT query tambahan
        DB::statement("
            INSERT INTO document_views (username, document_id, first_viewed_at, last_viewed_at, view_count, created_at, updated_at)
            VALUES (?, ?, ?, ?, 1, ?, ?)
            ON DUPLICATE KEY UPDATE 
                last_viewed_at = VALUES(last_viewed_at),
                view_count = view_count + 1,
                updated_at = VALUES(updated_at)
        ", [$username, $documentId, $now, $now, $now, $now]);
    }

    /**
     * @inheritDoc
     */
    public function hasViewed(string $username, int $documentId): bool
    {
        return $this->model->where('username', $username)
            ->where('document_id', $documentId)
            ->exists();
    }

    /**
     * @inheritDoc
     */
    public function getUsersWhoHaveNotViewed(int $documentId): Collection
    {
        // 1. Ambil semua user aktif dari database eksternal (db2) dengan cache 30 menit (1800 detik)
        $allUsers = Cache::remember('db2_active_users', 1800, function () {
            return DB::connection('db2')
                ->table('auth.users as a')
                ->select('a.username', 'a.full_name')
                ->where('a.is_active', '=', true)
                ->get();
        });

        // 2. Ambil username yang sudah melihat dokumen Y
        $viewedUsernames = $this->model->where('document_id', $documentId)
            ->pluck('username')
            ->toArray();

        // 3. Filter user yang BELUM melihat dokumen Y
        return $allUsers->reject(function ($user) use ($viewedUsernames) {
            return in_array($user->username, $viewedUsernames);
        })->values();
    }

    /**
     * @inheritDoc
     */
    public function getUsersWhoHaveViewed(int $documentId): Collection
    {
        // 1. Ambil data viewed records untuk document_id ini
        $views = $this->model->where('document_id', $documentId)->get();
        if ($views->isEmpty()) {
            return collect();
        }

        // 2. Gunakan cache mapping username -> full_name dari db2 (30 menit)
        $userMap = Cache::remember('db2_user_fullname_map', 1800, function () {
            return DB::connection('db2')
                ->table('auth.users as a')
                ->pluck('a.full_name', 'a.username');
        });

        // 3. Gabungkan info tracking view (first_viewed_at, last_viewed_at, view_count)
        return $views->map(function ($view) use ($userMap) {
            $user = new \stdClass();
            $user->username = $view->username;
            $user->full_name = $userMap->get($view->username, $view->username);
            $user->first_viewed_at = $view->first_viewed_at ? $view->first_viewed_at->toDateTimeString() : null;
            $user->last_viewed_at = $view->last_viewed_at ? $view->last_viewed_at->toDateTimeString() : null;
            $user->view_count = $view->view_count;
            return $user;
        })->filter()->values();
    }

    /**
     * @inheritDoc
     */
    public function getDocumentViewStats(): Collection
    {
        // Menghitung statistik keterbacaan per dokumen
        return DB::table('documents as d')
            ->leftJoin('document_views as dv', 'd.id', '=', 'dv.document_id')
            ->select(
                'd.id as document_id',
                'd.title as document_title',
                'd.no_surat as document_no_surat',
                'd.created_at',
                DB::raw('COUNT(dv.username) as total_users_read'),
                DB::raw('IFNULL(SUM(dv.view_count), 0) as total_clicks')
            )
            ->whereNull('d.deleted_at')
            ->groupBy('d.id', 'd.title', 'd.no_surat', 'd.created_at')
            ->get();
    }
}
