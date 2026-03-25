<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class PaymentCardRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function countByUser(int $userId): int
    {
        $statement = $this->connection->prepare(
            'SELECT COUNT(*) FROM payment_cards WHERE user_id = :user_id'
        );
        $statement->execute(['user_id' => $userId]);

        return (int) $statement->fetchColumn();
    }

    public function listByUser(int $userId): array
    {
        $statement = $this->connection->prepare(
            'SELECT * FROM payment_cards WHERE user_id = :user_id ORDER BY is_default DESC, created_at DESC, id DESC'
        );
        $statement->execute(['user_id' => $userId]);

        return array_map(fn (array $row): array => $this->normalize($row), $statement->fetchAll());
    }

    public function findById(int $id): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT * FROM payment_cards WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return is_array($row) ? $this->normalize($row) : null;
    }

    public function findDefaultByUser(int $userId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT * FROM payment_cards WHERE user_id = :user_id ORDER BY is_default DESC, created_at ASC, id ASC LIMIT 1'
        );
        $statement->execute(['user_id' => $userId]);
        $row = $statement->fetch();

        return is_array($row) ? $this->normalize($row) : null;
    }

    public function create(array $data): int
    {
        $statement = $this->connection->prepare(
            <<<SQL
            INSERT INTO payment_cards (
                user_id,
                label,
                cardholder_name,
                card_last_four,
                card_brand,
                expiry_month,
                expiry_year,
                is_default
            ) VALUES (
                :user_id,
                :label,
                :cardholder_name,
                :card_last_four,
                :card_brand,
                :expiry_month,
                :expiry_year,
                :is_default
            )
            SQL
        );
        $statement->execute([
            'user_id' => $data['user_id'],
            'label' => $data['label'],
            'cardholder_name' => $data['cardholder_name'],
            'card_last_four' => $data['card_last_four'],
            'card_brand' => $data['card_brand'],
            'expiry_month' => $data['expiry_month'],
            'expiry_year' => $data['expiry_year'],
            'is_default' => $data['is_default'] ?? 0,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function delete(int $id): bool
    {
        $statement = $this->connection->prepare(
            'DELETE FROM payment_cards WHERE id = :id'
        );

        return $statement->execute(['id' => $id]);
    }

    public function clearDefault(int $userId): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE payment_cards SET is_default = 0 WHERE user_id = :user_id'
        );

        return $statement->execute(['user_id' => $userId]);
    }

    public function setDefault(int $id, int $userId): bool
    {
        $ownsTransaction = !$this->connection->inTransaction();

        if ($ownsTransaction) {
            $this->connection->beginTransaction();
        }

        try {
            $this->clearDefault($userId);

            $statement = $this->connection->prepare(
                'UPDATE payment_cards SET is_default = 1 WHERE id = :id AND user_id = :user_id'
            );
            $result = $statement->execute([
                'id' => $id,
                'user_id' => $userId,
            ]);

            if ($ownsTransaction) {
                $this->connection->commit();
            }

            return $result;
        } catch (\Throwable $exception) {
            if ($ownsTransaction && $this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $exception;
        }
    }

    private function normalize(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'user_id' => (int) $row['user_id'],
            'label' => (string) $row['label'],
            'cardholder_name' => (string) $row['cardholder_name'],
            'card_last_four' => (string) $row['card_last_four'],
            'card_brand' => (string) $row['card_brand'],
            'expiry_month' => (int) $row['expiry_month'],
            'expiry_year' => (int) $row['expiry_year'],
            'is_default' => (bool) $row['is_default'],
            'created_at' => (string) $row['created_at'],
            'updated_at' => (string) $row['updated_at'],
        ];
    }
}
