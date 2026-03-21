<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\HelpCenterRepositoryInterface;

final class HelpCenterService
{
    public function __construct(
        private readonly HelpCenterRepositoryInterface $repository,
        private readonly string $source
    ) {
    }

    public function browse(array $input): array
    {
        $filters = $this->sanitizeFilters($input);

        return [
            'filters' => $filters,
            'categories' => $this->repository->categories(),
            'hot_questions' => $this->repository->hotQuestions($filters),
            'source' => $this->source,
        ];
    }

    private function sanitizeFilters(array $input): array
    {
        $search = trim((string) ($input['search'] ?? ''));
        $search = mb_substr($search, 0, 80);

        $category = trim((string) ($input['category'] ?? ''));
        $category = preg_replace('/[^a-z0-9-]/i', '', $category) ?? '';

        return [
            'search' => $search,
            'category' => $category,
        ];
    }
}
