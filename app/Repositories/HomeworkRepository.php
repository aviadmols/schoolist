<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Services\Database;

class HomeworkRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findByPage(int $pageId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->db->table('homework')} 
             WHERE page_id = ? 
             ORDER BY date ASC, created_at DESC",
            [$pageId]
        );
    }

    public function findByDate(int $pageId, string $date): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->db->table('homework')} 
             WHERE page_id = ? AND date = ?
             ORDER BY created_at DESC",
            [$pageId, $date]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->db->table('homework')} WHERE id = ?",
            [$id]
        );
    }

    public function create(int $pageId, array $data): int
    {
        return $this->db->insert('homework', [
            'page_id' => $pageId,
            'title' => $data['title'] ?? null,
            'html' => $data['html'] ?? '',
            'date' => $data['date'] ?? date('Y-m-d'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function update(int $id, array $data): void
    {
        $updateData = [
            'title' => $data['title'] ?? null,
            'html' => $data['html'] ?? '',
            'date' => $data['date'] ?? date('Y-m-d'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $this->db->update('homework', $updateData, 'id = ?', [$id]);
    }

    public function delete(int $id): void
    {
        $this->db->delete('homework', 'id = ?', [$id]);
    }
}







