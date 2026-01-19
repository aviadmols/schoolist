-- Schoolist Database Schema

CREATE TABLE IF NOT EXISTS sl_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    role ENUM('system_admin', 'page_admin') NOT NULL DEFAULT 'page_admin',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    last_login_at DATETIME NULL,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sl_pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unique_numeric_id INT NOT NULL UNIQUE,
    school_name VARCHAR(255) NOT NULL,
    class_title VARCHAR(255) NOT NULL,
    settings_json TEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_unique_id (unique_numeric_id),
    INDEX idx_school (school_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sl_page_admins (
    page_id INT NOT NULL,
    user_id INT NOT NULL,
    PRIMARY KEY (page_id, user_id),
    FOREIGN KEY (page_id) REFERENCES sl_pages(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES sl_users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sl_invitation_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(32) NOT NULL UNIQUE,
    school_name VARCHAR(255) NOT NULL,
    admin_email VARCHAR(255) NOT NULL,
    status ENUM('active', 'used', 'disabled') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    used_by_user_id INT NULL,
    used_page_id INT NULL,
    FOREIGN KEY (used_by_user_id) REFERENCES sl_users(id) ON DELETE SET NULL,
    FOREIGN KEY (used_page_id) REFERENCES sl_pages(id) ON DELETE SET NULL,
    INDEX idx_code (code),
    INDEX idx_status (status),
    INDEX idx_school (school_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sl_otp_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    code_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    attempts INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    ip VARCHAR(45) NOT NULL,
    INDEX idx_email (email),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sl_auth_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    last_used_at DATETIME NOT NULL,
    user_agent TEXT,
    ip VARCHAR(45) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES sl_users(id) ON DELETE CASCADE,
    INDEX idx_token (token_hash),
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sl_page_blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    order_index INT NOT NULL DEFAULT 0,
    data_json TEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (page_id) REFERENCES sl_pages(id) ON DELETE CASCADE,
    INDEX idx_page (page_id),
    INDEX idx_order (page_id, order_index)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sl_announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_id INT NOT NULL,
    html TEXT NOT NULL,
    title VARCHAR(255) NULL,
    date DATE NULL,
    order_index INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (page_id) REFERENCES sl_pages(id) ON DELETE CASCADE,
    INDEX idx_page (page_id),
    INDEX idx_order (page_id, order_index),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sl_q_activations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    q_number INT NOT NULL UNIQUE,
    page_unique_numeric_id INT NOT NULL,
    status ENUM('active', 'disabled') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    activated_by_ip VARCHAR(45) NOT NULL,
    last_used_at DATETIME NOT NULL,
    INDEX idx_q_number (q_number),
    INDEX idx_page (page_unique_numeric_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;












