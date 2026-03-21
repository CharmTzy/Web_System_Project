<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\SampleHelpCenter;

final class SampleHelpCenterRepository implements HelpCenterRepositoryInterface
{
    public function categories(): array
    {
        return SampleHelpCenter::categories();
    }

    public function hotQuestions(array $filters): array
    {
        $search = mb_strtolower((string) ($filters['search'] ?? ''));
        $category = (string) ($filters['category'] ?? '');

        return array_values(array_filter(
            SampleHelpCenter::hotQuestions(),
            static function (array $question) use ($search, $category): bool {
                if ($category !== '' && $question['category_slug'] !== $category) {
                    return false;
                }

                if ($search === '') {
                    return true;
                }

                $haystack = mb_strtolower(implode(' ', [
                    $question['category_name'],
                    $question['question'],
                    $question['answer'],
                ]));

                return str_contains($haystack, $search);
            }
        ));
    }
}
