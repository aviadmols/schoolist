<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Services\Database;

class OtpRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create(string $email, string $codeHash, string $ip): int
    {
        // Invalidate previous OTPs for this email
        $this->db->update('otp_codes', [
            'expires_at' => date('Y-m-d H:i:s', time() - 1)
        ], 'email = ? AND expires_at > NOW()', [$email]);

        return $this->db->insert('otp_codes', [
            'email' => $email,
            'code_hash' => $codeHash,
            'expires_at' => date('Y-m-d H:i:s', time() + 600), // 10 minutes
            'attempts' => 0,
            'ip' => $ip,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function findValid(string $email): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->db->table('otp_codes')} 
             WHERE email = ? AND expires_at > NOW() AND attempts < 5
             ORDER BY created_at DESC LIMIT 1",
            [$email]
        );
    }

    public function incrementAttempts(int $id): void
    {
        $this->db->query(
            "UPDATE {$this->db->table('otp_codes')} SET attempts = attempts + 1 WHERE id = ?",
            [$id]
        );
    }

    public function markUsed(int $id): void
    {
        $this->db->update('otp_codes', [
            'expires_at' => date('Y-m-d H:i:s', time() - 1)
        ], 'id = ?', [$id]);
    }
}















