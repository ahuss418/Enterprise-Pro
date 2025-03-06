DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `asset_data`;
DROP TABLE IF EXISTS `uploads`;
DROP TABLE IF EXISTS `logs`;
DROP TABLE IF EXISTS `users`;


CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    -- Full Name
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,

    -- Email
    email VARCHAR(255) NOT NULL UNIQUE,

    -- Password (hashed)
    password TEXT,

    -- Address Details
    address_line_one VARCHAR(100) NOT NULL,
    address_line_two VARCHAR(100) DEFAULT NULL,
    town VARCHAR(100) NOT NULL,
    county VARCHAR(100) NOT NULL,
    postcode VARCHAR(20) NOT NULL,

    -- Local Area Status
    status_live_local BOOLEAN DEFAULT false,
    status_work_local BOOLEAN DEFAULT false,
    status_study_local BOOLEAN DEFAULT false,

    -- Role status
    admin BOOLEAN DEFAULT false,
    verified BOOLEAN DEFAULT false,

    -- Account and Security
    email_confirmed BOOLEAN DEFAULT false,
    reset_token_expire TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reset_token CHAR(36) DEFAULT NULL,
    auth_secret CHAR(36) DEFAULT NULL,

    -- Tracking
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);