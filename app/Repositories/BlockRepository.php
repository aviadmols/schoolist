<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Services\Database;

class BlockRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findByPage(int $pageId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->db->table('page_blocks')} 
             WHERE page_id = ? 
             ORDER BY order_index ASC, id ASC",
            [$pageId]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->db->table('page_blocks')} WHERE id = ?",
            [$id]
        );
    }

    public function create(int $pageId, string $type, string $title, array $data, int $orderIndex): int
    {
        return $this->db->insert('page_blocks', [
            'page_id' => $pageId,
            'type' => $type,
            'title' => $title,
            'data_json' => json_encode($data),
            'order_index' => $orderIndex,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function update(int $id, array $data): void
    {
        if (isset($data['data_json']) && is_array($data['data_json'])) {
            $data['data_json'] = json_encode($data['data_json']);
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->update('page_blocks', $data, 'id = ?', [$id]);
    }

    public function delete(int $id): void
    {
        $this->db->delete('page_blocks', 'id = ?', [$id]);
    }

    public function reorder(int $pageId, array $blockIds): void
    {
        $this->db->beginTransaction();
        try {
            foreach ($blockIds as $index => $blockId) {
                $this->db->update('page_blocks', [
                    'order_index' => $index
                ], 'id = ? AND page_id = ?', [$blockId, $pageId]);
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
            "SELECT MAX(order_index) as max_index FROM {$this->db->table('page_blocks')} WHERE page_id = ?",
            [$pageId]
        );
        return (int)($result['max_index'] ?? -1);
    }
}















