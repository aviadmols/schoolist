<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Services\Database;

class QActivationRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findByNumber(int $number): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->db->table('q_activations')} WHERE q_number = ? AND status = 'active'",
            [$number]
        );
    }

    public function create(int $number, int $pageUniqueId, string $ip): int
    {
        return $this->db->insert('q_activations', [
            'q_number' => $number,
            'page_unique_numeric_id' => $pageUniqueId,
            'status' => 'active',
            'activated_by_ip' => $ip,
            'created_at' => date('Y-m-d H:i:s'),
            'last_used_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function updateLastUsed(int $id): void
    {
        $this->db->update('q_activations', [
            'last_used_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$id]);
    }

    public function list(array $filters = []): array
    {
        $sql = "SELECT q.*, p.school_name, p.class_title 
                FROM {$this->db->table('q_activations')} q
                LEFT JOIN {$this->db->table('pages')} p ON q.page_unique_numeric_id = p.unique_numeric_id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['q_number'])) {
            $sql .= " AND q.q_number = ?";
            $params[] = $filters['q_number'];
        }

        if (!empty($filters['page_unique_id'])) {
            $sql .= " AND q.page_unique_numeric_id = ?";
            $params[] = $filters['page_unique_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND q.status = ?";
            $params[] = $filters['status'];
        }

        $sql .= " ORDER BY q.created_at DESC";

        return $this->db->fetchAll($sql, $params);
    }
}















