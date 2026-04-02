<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeImmutable;
use DateTimeZone;
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

            $this->configureSessionTimezone($this->connection);
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

    private function configureSessionTimezone(PDO $connection): void
    {
        $timezoneName = trim((string) ($this->config['timezone'] ?? ''));

        if ($timezoneName === '') {
            return;
        }

        try {
            $timezone = new DateTimeZone($timezoneName);
        } catch (\Throwable) {
            return;
        }

        $offsetSeconds = $timezone->getOffset(new DateTimeImmutable('now', $timezone));
        $sign = $offsetSeconds < 0 ? '-' : '+';
        $absoluteSeconds = abs($offsetSeconds);
        $hours = intdiv($absoluteSeconds, 3600);
        $minutes = intdiv($absoluteSeconds % 3600, 60);
        $offset = sprintf('%s%02d:%02d', $sign, $hours, $minutes);

        $connection->exec('SET time_zone = ' . $connection->quote($offset));
    }
}
