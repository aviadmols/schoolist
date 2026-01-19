-- Update sl_users table
ALTER TABLE sl_users 
ADD COLUMN first_name VARCHAR(100) NULL AFTER id,
ADD COLUMN last_name VARCHAR(100) NULL AFTER first_name,
ADD COLUMN phone VARCHAR(20) NULL AFTER email;

-- Update sl_pages table
ALTER TABLE sl_pages
ADD COLUMN city VARCHAR(100) NULL AFTER school_name,
ADD COLUMN class_type VARCHAR(50) NULL AFTER city,
ADD COLUMN class_number INT NULL AFTER class_type;

-- Update sl_invitation_codes to include child info (requested previously but may be missing columns)
ALTER TABLE sl_invitation_codes
ADD COLUMN child_name VARCHAR(255) NULL AFTER school_name,
ADD COLUMN parent1_name VARCHAR(255) NULL,
ADD COLUMN parent1_role VARCHAR(100) NULL,
ADD COLUMN parent1_phone VARCHAR(20) NULL,
ADD COLUMN parent2_name VARCHAR(255) NULL,
ADD COLUMN parent2_role VARCHAR(100) NULL,
ADD COLUMN parent2_phone VARCHAR(20) NULL,
ADD COLUMN child_birth_date DATE NULL;
