<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Services\Database;

class AuthTokenRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create(int $userId, string $tokenHash, string $ip, string $userAgent): int
    {
        // Clean old tokens
        $this->db->query(
            "DELETE FROM {$this->db->table('auth_tokens')} 
             WHERE user_id = ? AND expires_at < NOW()",
            [$userId]
        );

        return $this->db->insert('auth_tokens', [
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => date('Y-m-d H:i:s', time() + 31536000), // 1 year
            'last_used_at' => date('Y-m-d H:i:s'),
            'ip' => $ip,
            'user_agent' => $userAgent
        ]);
    }

    public function findValid(string $tokenHash): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->db->table('auth_tokens')} 
             WHERE token_hash = ? AND expires_at > NOW()",
            [$tokenHash]
        );
    }

    public function updateLastUsed(int $id): void
    {
        $this->db->update('auth_tokens', [
            'last_used_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$id]);
    }

    public function revoke(int $userId): void
    {
        $this->db->delete('auth_tokens', 'user_id = ?', [$userId]);
    }
}















