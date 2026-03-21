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

    private function buildWhereClause(array $conditions): string
    {
        return implode(' AND ', $conditions);
    }
}
