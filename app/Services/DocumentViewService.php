<?php

namespace App\Services;

use App\Contracts\Repositories\DocumentViewRepositoryInterface;
use App\Contracts\Services\DocumentViewServiceInterface;
use Illuminate\Support\Collection;

class DocumentViewService implements DocumentViewServiceInterface
{
    /**
     * @var DocumentViewRepositoryInterface
     */
    protected $repository;

    /**
     * DocumentViewService Constructor.
     *
     * @param DocumentViewRepositoryInterface $repository
     */
    public function __construct(DocumentViewRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @inheritDoc
     */
    public function recordView(string $username, int $documentId): void
    {
        $this->repository->recordView($username, $documentId);
    }

    /**
     * @inheritDoc
     */
    public function hasViewed(string $username, int $documentId): bool
    {
        return $this->repository->hasViewed($username, $documentId);
    }

    /**
     * @inheritDoc
     */
    public function getUsersWhoHaveNotViewed(int $documentId): Collection
    {
        return $this->repository->getUsersWhoHaveNotViewed($documentId);
    }

    /**
     * @inheritDoc
     */
    public function getUsersWhoHaveViewed(int $documentId): Collection
    {
        return $this->repository->getUsersWhoHaveViewed($documentId);
    }

    /**
     * @inheritDoc
     */
    public function getDocumentViewStats(): Collection
    {
        return $this->repository->getDocumentViewStats();
    }
}
