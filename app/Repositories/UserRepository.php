<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Services\Database;

class UserRepository
{
    // User Statuses
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    // User Roles
    public const ROLE_SYSTEM_ADMIN = 'system_admin';
    public const ROLE_PAGE_ADMIN = 'page_admin';
    public const ROLE_PARENT = 'parent';

    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findByEmail(string $email): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->db->table('users')} WHERE email = ? AND status = ?",
            [$email, self::STATUS_ACTIVE]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->db->table('users')} WHERE id = ?",
            [$id]
        );
    }

    public function create(array $data): int
    {
        return $this->db->insert('users', [
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'] ?? 'page_admin',
            'status' => $data['status'] ?? 'active',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function update(int $id, array $data): void
    {
        $fields = [];
        $params = [];
        foreach (['first_name', 'last_name', 'email', 'phone', 'role', 'status'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($fields)) return;
        
        $params[] = $id;
        $sql = "UPDATE {$this->db->table('users')} SET " . implode(', ', $fields) . " WHERE id = ?";
        $this->db->query($sql, $params);
    }

    public function list(array $filters = []): array
    {
        $sql = "SELECT * FROM {$this->db->table('users')} WHERE 1=1";
        $params = [];

        if (!empty($filters['email'])) {
            $sql .= " AND email LIKE ?";
            $params[] = '%' . $filters['email'] . '%';
        }
        if (!empty($filters['name'])) {
            $sql .= " AND (first_name LIKE ? OR last_name LIKE ?)";
            $params[] = '%' . $filters['name'] . '%';
            $params[] = '%' . $filters['name'] . '%';
        }
        if (!empty($filters['role'])) {
            $sql .= " AND role = ?";
            $params[] = $filters['role'];
        }

        $sql .= " ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, $params);
    }

    public function search(string $term): array
    {
        $sql = "SELECT id, email, first_name, last_name, CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''), ' (', email, ')') as label 
                FROM {$this->db->table('users')} 
                WHERE email LIKE ? OR first_name LIKE ? OR last_name LIKE ? 
                LIMIT 10";
        $term = '%' . $term . '%';
        return $this->db->fetchAll($sql, [$term, $term, $term]);
    }

    public function updateLastLogin(int $id): void
    {
        $this->db->query(
            "UPDATE {$this->db->table('users')} SET last_login_at = ? WHERE id = ?",
            [date('Y-m-d H:i:s'), $id]
        );
    }

    public function updateRole(int $id, string $role): void
    {
        $this->db->query(
            "UPDATE {$this->db->table('users')} SET role = ? WHERE id = ?",
            [$role, $id]
        );
    }

    public function isPageAdmin(int $userId, int $pageId): bool
    {
        $result = $this->db->fetch(
            "SELECT 1 FROM {$this->db->table('page_admins')} WHERE user_id = ? AND page_id = ?",
            [$userId, $pageId]
        );
        return $result !== null;
    }
}
