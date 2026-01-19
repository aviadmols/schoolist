-- Fix: Create sl_links table if it doesn't exist
-- Run this SQL script on your production database

CREATE TABLE IF NOT EXISTS sl_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    link_number INT NOT NULL UNIQUE,
    q_number INT NOT NULL,
    page_unique_numeric_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_used_at DATETIME NULL,
    deleted_at DATETIME NULL,
    INDEX idx_link_number (link_number),
    INDEX idx_q_number (q_number),
    INDEX idx_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


