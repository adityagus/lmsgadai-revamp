<?php

namespace App\Services;

use App\Contracts\SearchServiceInterface;
use App\Models\Course;
use App\Models\Announcement;
use Algolia\AlgoliaSearch\SearchClient;
use Illuminate\Support\Facades\Log;

class AlgoliaService implements SearchServiceInterface
{
    /**
     * @var SearchClient
     */
    private $client;

    /**
     * AlgoliaService Constructor.
     *
     * @param SearchClient $client
     */
    public function __construct(SearchClient $client)
    {
        $this->client = $client;
    }

    /**
     * @inheritDoc
     */
    public function refresh(): array
    {
        $index = $this->client->initIndex('course_gadai_mulia');

        // Courses
        $courses = Course::with('category:id,name')->get();
        $algoliaCourses = $courses->map(function ($course) {
            return [
                'objectID' => 'course_' . $course->id,
                'id' => $course->id,
                'type' => 'course',
                'title' => $course->name,
                'tagline' => $course->tagline,
                'description' => $course->description,
                'category' => $course->category ? $course->category->name : null,
                'thumbnail_url' => config('services.mix.img_url') . $course->thumbnail,
            ];
        })->toArray();

        // Documents (Announcements)
        $documents = Announcement::all();
        $algoliaDocuments = $documents->map(function ($doc) {
            return [
                'objectID' => 'document_' . $doc->id,
                'id' => $doc->id,
                'type' => 'document',
                'title' => $doc->title,
                'tagline' => $doc->no_surat ?? '',
                'description' => $doc->content ?? '',
                'category' => 'Document',
                'thumbnail_url' => config('services.mix.img_url') . ($doc->url ?? ''),
            ];
        })->toArray();

        // Merge and push to Algolia
        $allObjects = array_merge($algoliaCourses, $algoliaDocuments);
        $index->clearObjects();
        $index->saveObjects($allObjects);

        return [
            'success' => true,
            'courses_uploaded' => count($algoliaCourses),
            'documents_uploaded' => count($algoliaDocuments),
            'total_uploaded' => count($allObjects),
            'message' => 'Algolia index refreshed with latest courses and documents.'
        ];
    }

    /**
     * @inheritDoc
     */
    public function updateCourse($course): void
    {
        try {
            $course->load('category');
            $index = $this->client->initIndex('course_gadai_mulia');
            $algoliaData = [
                'objectID' => 'course_' . $course->id,
                'id' => $course->id,
                'type' => 'course',
                'title' => $course->name,
                'tagline' => $course->tagline,
                'description' => $course->description,
                'category' => $course->category ? $course->category->name : null,
                'thumbnail_url' => config('services.mix.img_url') . $course->thumbnail,
            ];
            $index->saveObject($algoliaData);
            Log::info('Algolia index updated for course: ' . $course->id);
        } catch (\Exception $e) {
            Log::error('Failed to update Algolia index for course: ' . $e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function updateDocument($document): void
    {
        try {
            $index = $this->client->initIndex('course_gadai_mulia');
            $algoliaData = [
                'objectID' => 'document_' . $document->id,
                'id' => $document->id,
                'type' => 'document',
                'title' => $document->title,
                'tagline' => $document->no_surat ?? '',
                'description' => $document->content ?? '',
                'category' => 'Document',
                'thumbnail_url' => config('services.mix.img_url') . ($document->url ?? ''),
            ];
            $index->saveObject($algoliaData);
            Log::info('Algolia index updated for document: ' . $document->id);
        } catch (\Exception $e) {
            Log::error('Failed to update Algolia index for document: ' . $e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function delete(string $type, int $id): void
    {
        try {
            $index = $this->client->initIndex('course_gadai_mulia');
            $objectID = $type . '_' . $id;
            $index->deleteObject($objectID);
            Log::info('Deleted from Algolia: ' . $objectID);
        } catch (\Exception $e) {
            Log::error('Failed to delete from Algolia: ' . $e->getMessage());
        }
    }
}
