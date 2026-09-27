<?php

namespace App\Contracts\Services;

use Illuminate\Support\Collection;

interface DocumentViewServiceInterface
{
    /**
     * Record or update user document view.
     *
     * @param string $username
     * @param int $documentId
     * @return void
     */
    public function recordView(string $username, int $documentId): void;

    /**
     * Check if a user has viewed a document.
     *
     * @param string $username
     * @param int $documentId
     * @return bool
     */
    public function hasViewed(string $username, int $documentId): bool;

    /**
     * Get users who have not viewed a specific document.
     *
     * @param int $documentId
     * @return \Illuminate\Support\Collection
     */
    public function getUsersWhoHaveNotViewed(int $documentId): Collection;

    /**
     * Get users who have viewed a specific document with their read timestamps and counts.
     *
     * @param int $documentId
     * @return \Illuminate\Support\Collection
     */
    public function getUsersWhoHaveViewed(int $documentId): Collection;

    /**
     * Get aggregate view statistics for documents.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getDocumentViewStats(): Collection;
}
