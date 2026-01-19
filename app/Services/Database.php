<?php
declare(strict_types=1);

namespace App\Services;

use PDO;
use PDOException;

class Database
{
    private PDO $pdo;
    private string $prefix;

    public function __construct(string $host, string $dbname, string $user, string $pass, string $prefix = 'sl_', int $port = 3306)
    {
        $this->prefix = $prefix;
        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 5, // 5 seconds timeout for connection
        ];

        // 1002 is the constant value for MYSQL_ATTR_INIT_COMMAND
        // We use the integer directly to avoid Deprecation notices in PHP 8.4+
        $options[1002] = "SET SESSION wait_timeout=30, interactive_timeout=30";

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            throw new \RuntimeException("Database connection failed: " . $e->getMessage());
        }
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function prefix(): string
    {
        return $this->prefix;
    }

    public function table(string $name): string
    {
        return $this->prefix . $name;
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database query error: " . $e->getMessage() . " SQL: " . substr($sql, 0, 200));
            throw $e;
        }
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function insert(string $table, array $data): int
    {
        $table = $this->table($table);
        $fields = array_keys($data);
        $placeholders = array_map(fn($f) => ":$f", $fields);
        
        $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $this->query($sql, $data);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $table = $this->table($table);
        $set = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            $set[] = "$field = ?";
            $params[] = $value;
        }
        
        $sql = "UPDATE {$table} SET " . implode(', ', $set) . " WHERE $where";
        $params = array_merge($params, $whereParams);
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    public function delete(string $table, string $where, array $params = []): int
    {
        $table = $this->table($table);
        $sql = "DELETE FROM {$table} WHERE $where";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }
}
