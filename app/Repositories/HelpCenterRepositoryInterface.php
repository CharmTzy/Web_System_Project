<?php

declare(strict_types=1);

namespace App\Repositories;

interface HelpCenterRepositoryInterface
{
    public function categories(): array;

    public function hotQuestions(array $filters): array;
}
