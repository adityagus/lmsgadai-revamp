<?php

namespace App\Contracts;

interface SearchServiceInterface
{
    /**
     * Refresh index for both courses and documents.
     *
     * @return array
     */
    public function refresh(): array;

    /**
     * Update or add a single course to search index.
     *
     * @param mixed $course
     * @return void
     */
    public function updateCourse($course): void;

    /**
     * Update or add a single document (announcement) to search index.
     *
     * @param mixed $document
     * @return void
     */
    public function updateDocument($document): void;

    /**
     * Delete a resource from search index by type and id.
     *
     * @param string $type
     * @param int $id
     * @return void
     */
    public function delete(string $type, int $id): void;
}
