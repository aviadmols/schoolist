<?php
declare(strict_types=1);

namespace App\Services;

class RateLimiter
{
    private string $storageDir;

    public function __construct()
    {
        $this->storageDir = STORAGE_PATH . '/ratelimit/';
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }

    public function check(string $key, int $maxAttempts, int $windowSeconds): bool
    {
        $file = $this->storageDir . md5($key) . '.json';
        $now = time();

        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && $data['expires'] > $now) {
                if ($data['count'] >= $maxAttempts) {
                    return false;
                }
                $data['count']++;
            } else {
                $data = ['count' => 1, 'expires' => $now + $windowSeconds];
            }
        } else {
            $data = ['count' => 1, 'expires' => $now + $windowSeconds];
        }

        file_put_contents($file, json_encode($data));
        return true;
    }

    public function reset(string $key): void
    {
        $file = $this->storageDir . md5($key) . '.json';
        if (file_exists($file)) {
            unlink($file);
        }
    }
}















