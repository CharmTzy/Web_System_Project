<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\HelpCenterRepository;
use InvalidArgumentException;
use RuntimeException;

final class AdminHelpCenterService
{
    public function __construct(private readonly HelpCenterRepository $repository)
    {
    }

    public function categories(): array
    {
        return $this->repository->categories();
    }

    public function listQuestions(array $filters = []): array
    {
        return $this->repository->listAllQuestions([
            'search' => trim((string) ($filters['search'] ?? '')),
            'category_id' => (int) ($filters['category_id'] ?? 0),
        ]);
    }

    public function getQuestion(int $id): ?array
    {
        return $this->repository->findQuestionById($id);
    }

    public function create(array $input): array
    {
        $data = $this->validate($input);
        $id = $this->repository->createQuestion($data);
        $question = $this->repository->findQuestionById($id);

        if ($question === null) {
            throw new RuntimeException('Question could not be created.');
        }

        return $question;
    }

    public function update(int $id, array $input): array
    {
        $existing = $this->repository->findQuestionById($id);

        if ($existing === null) {
            throw new RuntimeException('Question not found.');
        }

        $this->repository->updateQuestion($id, $this->validate($input));

        return $this->repository->findQuestionById($id) ?? $existing;
    }

    public function delete(int $id): void
    {
        $existing = $this->repository->findQuestionById($id);

        if ($existing === null) {
            throw new RuntimeException('Question not found.');
        }

        $this->repository->deleteQuestion($id);
    }

    private function validate(array $input): array
    {
        $categoryId = filter_var($input['category_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $question = trim((string) ($input['question'] ?? ''));
        $answer = trim((string) ($input['answer'] ?? ''));
        $sortOrder = filter_var($input['sort_order'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($categoryId === false) {
            throw new InvalidArgumentException('Please select a category.');
        }

        if ($question === '') {
            throw new InvalidArgumentException('Question is required.');
        }

        if ($answer === '') {
            throw new InvalidArgumentException('Answer is required.');
        }

        if ($sortOrder === false) {
            throw new InvalidArgumentException('Sort order must be 1 or greater.');
        }

        return [
            'category_id' => $categoryId,
            'question' => $question,
            'answer' => $answer,
            'sort_order' => $sortOrder,
            'is_hot' => bool_from_input($input['is_hot'] ?? false) ? 1 : 0,
        ];
    }
}
