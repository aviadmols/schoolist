<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Service for handling database migrations and updates.
 */
class Migration
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Run all pending migrations.
     */
    public function migrateAll(): void
    {
        // Use a more reliable absolute path for the lock file
        $lockFile = dirname(__DIR__, 2) . '/storage/migration.lock';
        
        try {
            // Check if migrations ran recently (last 1 hour)
            if (file_exists($lockFile)) {
                $lastRun = filemtime($lockFile);
                if ($lastRun !== false && (time() - $lastRun < 3600)) {
                    return;
                }
            }

            // Create/Update lock file immediately
            @file_put_contents($lockFile, date('Y-m-d H:i:s'));

            Logger::info("Migration: Starting updates...");
            $this->migrateUsersAndPages();
            $this->createCitiesTable();
            $this->createEventsTable();
            $this->createHomeworkTable();
            $this->addAnnouncementFields();
            $this->addInvitationFields();
            Logger::info("Migration: Updates completed.");
            
        } catch (\Throwable $e) {
            Logger::error("Migration: Critical failure", ['msg' => $e->getMessage()]);
        }
    }

    /**
     * Create events table if not exists.
     */
    public function createEventsTable(): bool
    {
        try {
            $table = $this->db->table('events');
            $pagesTable = $this->db->table('pages');
            
            $sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                page_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                date DATE NOT NULL,
                time TIME NULL,
                location VARCHAR(255) NULL,
                description TEXT NULL,
                published BOOLEAN NOT NULL DEFAULT FALSE,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_page (page_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $this->db->query($sql);
            return true;
        } catch (\Exception $e) {
            Logger::error("Migration: Failed to create events table", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Create homework table if not exists.
     */
    public function createHomeworkTable(): bool
    {
        try {
            $table = $this->db->table('homework');
            
            $sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                page_id INT NOT NULL,
                title VARCHAR(255) NULL,
                html TEXT NOT NULL,
                date DATE NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_page (page_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $this->db->query($sql);
            Logger::info("Migration: Homework table ensured", ['table' => $table]);
            return true;
        } catch (\Exception $e) {
            Logger::error("Migration: Failed to create homework table", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Migrates users and pages table structure.
     */
    public function migrateUsersAndPages(): bool
    {
        try {
            $usersTable = $this->db->table('users');
            $pagesTable = $this->db->table('pages');

            // Users
            $columns = array_column($this->db->fetchAll("SHOW COLUMNS FROM `{$usersTable}`"), 'Field');
            if (!in_array('first_name', $columns)) $this->db->query("ALTER TABLE `{$usersTable}` ADD COLUMN first_name VARCHAR(100) NULL AFTER id");
            if (!in_array('last_name', $columns)) $this->db->query("ALTER TABLE `{$usersTable}` ADD COLUMN last_name VARCHAR(100) NULL AFTER first_name");
            if (!in_array('phone', $columns)) $this->db->query("ALTER TABLE `{$usersTable}` ADD COLUMN phone VARCHAR(20) NULL AFTER email");
            
            // Update role enum to include 'parent'
            $this->db->query("ALTER TABLE `{$usersTable}` MODIFY COLUMN role ENUM('system_admin', 'page_admin', 'parent') NOT NULL DEFAULT 'page_admin'");

            // Pages
            $columns = array_column($this->db->fetchAll("SHOW COLUMNS FROM `{$pagesTable}`"), 'Field');
            if (!in_array('city', $columns)) $this->db->query("ALTER TABLE `{$pagesTable}` ADD COLUMN city VARCHAR(100) NULL AFTER school_name");
            if (!in_array('class_type', $columns)) $this->db->query("ALTER TABLE `{$pagesTable}` ADD COLUMN class_type VARCHAR(50) NULL AFTER city");
            if (!in_array('class_number', $columns)) $this->db->query("ALTER TABLE `{$pagesTable}` ADD COLUMN class_number INT NULL AFTER class_type");

            return true;
        } catch (\Exception $e) {
            Logger::error("Migration: Users/Pages migration failed", ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function createCitiesTable(): bool
    {
        try {
            $tableName = $this->db->table('cities');
            $this->db->query("CREATE TABLE IF NOT EXISTS `{$tableName}` (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) NOT NULL UNIQUE, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            return true;
        } catch (\Exception $e) { return false; }
    }

    public function addAnnouncementFields(): bool
    {
        try {
            $table = $this->db->table('announcements');
            $columns = array_column($this->db->fetchAll("SHOW COLUMNS FROM `{$table}`"), 'Field');
            if (!in_array('html', $columns)) $this->db->query("ALTER TABLE `{$table}` ADD COLUMN html TEXT NOT NULL DEFAULT ''");
            return true;
        } catch (\Exception $e) { return false; }
    }

    public function addInvitationFields(): bool
    {
        try {
            $table = $this->db->table('invitation_codes');
            $columns = array_column($this->db->fetchAll("SHOW COLUMNS FROM `{$table}`"), 'Field');
            if (!in_array('child_name', $columns)) $this->db->query("ALTER TABLE `{$table}` ADD COLUMN child_name VARCHAR(255) NULL AFTER admin_email");
            return true;
        } catch (\Exception $e) { return false; }
    }
}
