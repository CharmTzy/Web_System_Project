<?php

declare(strict_types=1);

namespace App\Support;

use PDO;
use PDOException;

final class Database
{
    private ?PDO $connection = null;

    public function __construct(private readonly array $config)
    {
    }

    public function connection(): ?PDO
    {
        if ($this->connection instanceof PDO) {
            return $this->connection;
        }

        if (!$this->isConfigured()) {
            return null;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $this->config['host'],
            $this->config['port'],
            $this->config['database'],
            $this->config['charset']
        );

        try {
            $this->connection = new PDO(
                $dsn,
                (string) $this->config['username'],
                (string) $this->config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException) {
            return null;
        }

        return $this->connection;
    }

    private function isConfigured(): bool
    {
        return (string) $this->config['host'] !== ''
            && (string) $this->config['database'] !== '';
    }
}

