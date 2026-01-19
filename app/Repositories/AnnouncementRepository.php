<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Services\Database;

class AnnouncementRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findByPage(int $pageId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->db->table('announcements')} 
             WHERE page_id = ? 
             ORDER BY order_index ASC, created_at DESC",
            [$pageId]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->db->table('announcements')} WHERE id = ?",
            [$id]
        );
    }

    public function create(int $pageId, string $html, int $orderIndex, ?string $title = null, ?string $date = null): int
    {
        return $this->db->insert('announcements', [
            'page_id' => $pageId,
            'html' => $html,
            'title' => $title,
            'date' => $date,
            'order_index' => $orderIndex,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function update(int $id, array $data): void
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->update('announcements', $data, 'id = ?', [$id]);
    }

    public function delete(int $id): void
    {
        $this->db->delete('announcements', 'id = ?', [$id]);
    }

    public function reorder(int $pageId, array $announcementIds): void
    {
        $this->db->beginTransaction();
        try {
            foreach ($announcementIds as $index => $announcementId) {
                $this->db->update('announcements', [
                    'order_index' => $index
                ], 'id = ? AND page_id = ?', [$announcementId, $pageId]);
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function getMaxOrderIndex(int $pageId): int
    {
        $result = $this->db->fetch(
            "SELECT MAX(order_index) as max_index FROM {$this->db->table('announcements')} WHERE page_id = ?",
            [$pageId]
        );
        return (int)($result['max_index'] ?? -1);
    }
}















