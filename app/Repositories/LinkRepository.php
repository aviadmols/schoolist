<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Services\Database;

class LinkRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findByNumber(int $number): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->db->table('links')} WHERE link_number = ? AND deleted_at IS NULL",
            [$number]
        );
    }

    public function create(int $linkNumber, int $qNumber = 0): int
    {
        return $this->db->insert('links', [
            'link_number' => $linkNumber,
            'q_number' => $qNumber,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function delete(int $linkNumber): void
    {
        $this->db->update('links', [
            'deleted_at' => date('Y-m-d H:i:s')
        ], 'link_number = ?', [$linkNumber]);
    }

    public function activate(int $linkNumber, int $pageUniqueId): void
    {
        $this->db->update('links', [
            'page_unique_numeric_id' => $pageUniqueId,
            'last_used_at' => date('Y-m-d H:i:s')
        ], 'link_number = ?', [$linkNumber]);
    }

    public function updateLastUsed(int $linkNumber): void
    {
        $this->db->update('links', [
            'last_used_at' => date('Y-m-d H:i:s')
        ], 'link_number = ?', [$linkNumber]);
    }

    public function list(array $filters = []): array
    {
        $sql = "SELECT l.*, p.school_name, p.class_title 
                FROM {$this->db->table('links')} l
                LEFT JOIN {$this->db->table('pages')} p ON l.page_unique_numeric_id = p.unique_numeric_id
                WHERE l.deleted_at IS NULL";
        $params = [];

        if (!empty($filters['link_number'])) {
            $sql .= " AND l.link_number = ?";
            $params[] = $filters['link_number'];
        }

        if (!empty($filters['q_number'])) {
            $sql .= " AND l.q_number = ?";
            $params[] = $filters['q_number'];
        }

        $sql .= " ORDER BY l.created_at DESC";

        return $this->db->fetchAll($sql, $params);
    }
}

