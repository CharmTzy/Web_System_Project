<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class HelpCenterRepository implements HelpCenterRepositoryInterface
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function categories(): array
    {
        $statement = $this->connection->query(
            <<<SQL
            SELECT
                id,
                name,
                slug,
                description,
                icon_key,
                sort_order
            FROM help_categories
            ORDER BY sort_order ASC, name ASC
            SQL
        );

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'slug' => (string) $row['slug'],
                'description' => (string) ($row['description'] ?? ''),
                'icon_key' => (string) ($row['icon_key'] ?? 'general'),
                'sort_order' => (int) $row['sort_order'],
            ],
            $statement->fetchAll()
        );
    }

    public function hotQuestions(array $filters): array
    {
        $conditions = ['hq.is_hot = 1'];
        $params = [];

        if (($filters['category'] ?? '') !== '') {
            $conditions[] = 'hc.slug = :category';
            $params['category'] = $filters['category'];
        }

        if (($filters['search'] ?? '') !== '') {
            $conditions[] = '(hq.question LIKE :search OR hq.answer LIKE :search OR hc.name LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $statement = $this->connection->prepare(
            <<<SQL
            SELECT
                hq.id,
                hq.category_id,
                hq.question,
                hq.answer,
                hq.is_hot,
                hq.sort_order,
                hc.name AS category_name,
                hc.slug AS category_slug
            FROM help_questions hq
            INNER JOIN help_categories hc
                ON hc.id = hq.category_id
            WHERE {$this->buildWhereClause($conditions)}
            ORDER BY hq.sort_order ASC, hq.id ASC
            LIMIT 12
            SQL
        );
        $statement->execute($params);

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'category_id' => (int) $row['category_id'],
                'category_name' => (string) $row['category_name'],
                'category_slug' => (string) $row['category_slug'],
                'question' => (string) $row['question'],
                'answer' => (string) $row['answer'],
                'is_hot' => (bool) $row['is_hot'],
                'sort_order' => (int) $row['sort_order'],
            ],
            $statement->fetchAll()
        );
    }

    public function listAllQuestions(array $filters = []): array
    {
        $conditions = [];
        $params = [];

        if (($filters['category_id'] ?? 0) > 0) {
            $conditions[] = 'hc.id = :category_id';
            $params['category_id'] = (int) $filters['category_id'];
        }

        if (($filters['search'] ?? '') !== '') {
            $conditions[] = '(hq.question LIKE :search_question OR hq.answer LIKE :search_answer OR hc.name LIKE :search_category)';
            $searchPattern = '%' . $filters['search'] . '%';
            $params['search_question'] = $searchPattern;
            $params['search_answer'] = $searchPattern;
            $params['search_category'] = $searchPattern;
        }

        $sql = <<<SQL
            SELECT
                hq.id,
                hq.category_id,
                hq.question,
                hq.answer,
                hq.is_hot,
                hq.sort_order,
                hc.name AS category_name,
                hc.slug AS category_slug
            FROM help_questions hq
            INNER JOIN help_categories hc
                ON hc.id = hq.category_id
            SQL;

        if ($conditions !== []) {
            $sql .= ' WHERE ' . $this->buildWhereClause($conditions);
        }

        $sql .= ' ORDER BY hc.sort_order ASC, hq.sort_order ASC, hq.id ASC';

        $statement = $this->connection->prepare($sql);
        $statement->execute($params);

        return array_map([$this, 'normalizeQuestion'], $statement->fetchAll());
    }

    public function findQuestionById(int $id): ?array
    {
        $statement = $this->connection->prepare(
            <<<SQL
            SELECT
                hq.id,
                hq.category_id,
                hq.question,
                hq.answer,
                hq.is_hot,
                hq.sort_order,
                hc.name AS category_name,
                hc.slug AS category_slug
            FROM help_questions hq
            INNER JOIN help_categories hc
                ON hc.id = hq.category_id
            WHERE hq.id = :id
            LIMIT 1
            SQL
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return is_array($row) ? $this->normalizeQuestion($row) : null;
    }

    public function createQuestion(array $data): int
    {
        $statement = $this->connection->prepare(
            <<<SQL
            INSERT INTO help_questions (
                category_id,
                question,
                answer,
                is_hot,
                sort_order
            ) VALUES (
                :category_id,
                :question,
                :answer,
                :is_hot,
                :sort_order
            )
            SQL
        );
        $statement->execute([
            'category_id' => $data['category_id'],
            'question' => $data['question'],
            'answer' => $data['answer'],
            'is_hot' => $data['is_hot'],
            'sort_order' => $data['sort_order'],
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function updateQuestion(int $id, array $data): bool
    {
        $statement = $this->connection->prepare(
            <<<SQL
            UPDATE help_questions
            SET
                category_id = :category_id,
                question = :question,
                answer = :answer,
                is_hot = :is_hot,
                sort_order = :sort_order
            WHERE id = :id
            SQL
        );

        return $statement->execute([
            'id' => $id,
            'category_id' => $data['category_id'],
            'question' => $data['question'],
            'answer' => $data['answer'],
            'is_hot' => $data['is_hot'],
            'sort_order' => $data['sort_order'],
        ]);
    }

    public function deleteQuestion(int $id): bool
    {
        $statement = $this->connection->prepare('DELETE FROM help_questions WHERE id = :id');

        return $statement->execute(['id' => $id]);
    }

    private function normalizeQuestion(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'category_id' => (int) $row['category_id'],
            'category_name' => (string) $row['category_name'],
            'category_slug' => (string) $row['category_slug'],
            'question' => (string) $row['question'],
            'answer' => (string) $row['answer'],
            'is_hot' => (bool) $row['is_hot'],
            'sort_order' => (int) $row['sort_order'],
        ];
    }

    private function buildWhereClause(array $conditions): string
    {
        return implode(' AND ', $conditions);
    }
}
