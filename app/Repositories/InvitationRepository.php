<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Services\Database;

class InvitationRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findByCode(string $code): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->db->table('invitation_codes')} WHERE code = ?",
            [$code]
        );
    }

    public function create(string $code, string $schoolName, string $adminEmail): int
    {
        return $this->db->insert('invitation_codes', [
            'code' => $code,
            'school_name' => $schoolName,
            'admin_email' => $adminEmail,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function markUsed(int $id, int $userId, int $pageId): void
    {
        $this->db->update('invitation_codes', [
            'status' => 'used',
            'used_at' => date('Y-m-d H:i:s'),
            'used_by_user_id' => $userId,
            'used_page_id' => $pageId
        ], 'id = ?', [$id]);
    }

    public function update(int $id, array $data): void
    {
        $this->db->update('invitation_codes', $data, 'id = ?', [$id]);
    }

    public function list(array $filters = []): array
    {
        $sql = "SELECT * FROM {$this->db->table('invitation_codes')} WHERE 1=1";
        $params = [];

        if (!empty($filters['school_name'])) {
            $sql .= " AND school_name LIKE ?";
            $params[] = '%' . $filters['school_name'] . '%';
        }

        if (!empty($filters['admin_email'])) {
            $sql .= " AND admin_email LIKE ?";
            $params[] = '%' . $filters['admin_email'] . '%';
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        $sql .= " ORDER BY created_at DESC";

        return $this->db->fetchAll($sql, $params);
    }

    public function generateCode(): string
    {
        do {
            $code = strtoupper(substr(md5(uniqid((string)random_int(0, PHP_INT_MAX), true)), 0, 8));
            $exists = $this->db->fetch(
                "SELECT 1 FROM {$this->db->table('invitation_codes')} WHERE code = ?",
                [$code]
            );
        } while ($exists);

        return $code;
    }
}















