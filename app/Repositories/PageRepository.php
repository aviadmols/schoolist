<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Services\Database;

class PageRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findByUniqueId(int $uniqueId): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->db->table('pages')} WHERE unique_numeric_id = ?",
            [$uniqueId]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->db->table('pages')} WHERE id = ?",
            [$id]
        );
    }

    public function create(array $data): int
    {
        if (!isset($data['settings_json']) || is_array($data['settings_json'])) {
            $data['settings_json'] = json_encode($data['settings_json'] ?? []);
        }
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        return $this->db->insert('pages', $data);
    }

    public function update(int $id, array $data): void
    {
        if (isset($data['settings_json']) && is_array($data['settings_json'])) {
            $data['settings_json'] = json_encode($data['settings_json']);
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->update('pages', $data, 'id = ?', [$id]);
    }

    public function generateUniqueId(): int
    {
        do {
            $id = random_int(100000, 999999);
            $exists = $this->db->fetch(
                "SELECT 1 FROM {$this->db->table('pages')} WHERE unique_numeric_id = ?",
                [$id]
            );
        } while ($exists);

        return $id;
    }

    public function delete(int $id): void
    {
        $this->db->delete('pages', 'id = ?', [$id]);
    }

    public function list(array $filters = []): array
    {
        $sql = "SELECT p.*, 
                GROUP_CONCAT(DISTINCT u.email SEPARATOR ', ') as admin_emails,
                GROUP_CONCAT(DISTINCT CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) SEPARATOR ', ') as admin_names
                FROM {$this->db->table('pages')} p
                LEFT JOIN {$this->db->table('page_admins')} pa ON p.id = pa.page_id
                LEFT JOIN {$this->db->table('users')} u ON pa.user_id = u.id
                WHERE 1=1";

        $params = [];

        if (!empty($filters['school_name'])) {
            $sql .= " AND p.school_name LIKE ?";
            $params[] = '%' . $filters['school_name'] . '%';
        }

        if (!empty($filters['class_title'])) {
            $sql .= " AND p.class_title LIKE ?";
            $params[] = '%' . $filters['class_title'] . '%';
        }

        if (!empty($filters['unique_id'])) {
            $sql .= " AND p.unique_numeric_id = ?";
            $params[] = $filters['unique_id'];
        }

        if (!empty($filters['city'])) {
            $sql .= " AND p.city LIKE ?";
            $params[] = '%' . $filters['city'] . '%';
        }

        $sql .= " GROUP BY p.id ORDER BY p.created_at DESC";

        return $this->db->fetchAll($sql, $params);
    }
}
