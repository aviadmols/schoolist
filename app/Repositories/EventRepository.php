<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Services\Database;

class EventRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findByPage(int $pageId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->db->table('events')} 
             WHERE page_id = ? 
             ORDER BY date ASC, time ASC",
            [$pageId]
        );
    }

    public function findPublishedByPage(int $pageId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->db->table('events')} 
             WHERE page_id = ? AND published = 1
             ORDER BY date ASC, time ASC",
            [$pageId]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->db->table('events')} WHERE id = ?",
            [$id]
        );
    }

    public function create(int $pageId, array $data): int
    {
        return $this->db->insert('events', [
            'page_id' => $pageId,
            'name' => $data['name'] ?? '',
            'date' => $data['date'] ?? '',
            'time' => $data['time'] ?? null,
            'location' => $data['location'] ?? null,
            'description' => $data['description'] ?? null,
            'published' => isset($data['published']) && $data['published'] ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function update(int $id, array $data): void
    {
        $updateData = [
            'name' => $data['name'] ?? '',
            'date' => $data['date'] ?? '',
            'time' => $data['time'] ?? null,
            'location' => $data['location'] ?? null,
            'description' => $data['description'] ?? null,
            'published' => isset($data['published']) && $data['published'] ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $this->db->update('events', $updateData, 'id = ?', [$id]);
    }

    public function delete(int $id): void
    {
        $this->db->delete('events', 'id = ?', [$id]);
    }
}







