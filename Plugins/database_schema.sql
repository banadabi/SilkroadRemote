-- Silkroad Remote Database Schema
-- Run this SQL to create the required database tables

-- Create database
CREATE DATABASE IF NOT EXISTS silkroad_remote CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE silkroad_remote;

-- Characters table - stores character data from the bot
CREATE TABLE IF NOT EXISTS characters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    qr_id VARCHAR(255) UNIQUE NOT NULL,
    account_id BIGINT NOT NULL,
    player_id BIGINT NOT NULL,
    server VARCHAR(100) NOT NULL,
    name VARCHAR(100) NOT NULL,
    level INT DEFAULT 0,
    max_level INT DEFAULT 0,
    exp BIGINT DEFAULT 0,
    max_exp BIGINT DEFAULT 0,
    sp BIGINT DEFAULT 0,
    gold BIGINT DEFAULT 0,
    gold_stored BIGINT DEFAULT 0,
    hp INT DEFAULT 0,
    max_hp INT DEFAULT 0,
    mp INT DEFAULT 0,
    max_mp INT DEFAULT 0,
    region INT DEFAULT 0,
    region_palace VARCHAR(200) DEFAULT '',
    x FLOAT DEFAULT 0,
    y FLOAT DEFAULT 0,
    z FLOAT DEFAULT 0,
    dead_counter INT DEFAULT 0,
    actual_training_area_x FLOAT DEFAULT 0,
    actual_training_area_y FLOAT DEFAULT 0,
    actual_training_radius FLOAT DEFAULT 0,
    pc_start_bot INT DEFAULT 0,
    pc_stop_bot INT DEFAULT 0,
    connected_state TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_account_id (account_id),
    INDEX idx_server_name (server, name)
);

-- Botting commands table - stores commands from mobile app to PC
CREATE TABLE IF NOT EXISTS botting_commands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    qr_id VARCHAR(255) NOT NULL,
    account_id BIGINT NOT NULL,
    training_radius FLOAT DEFAULT 0,
    training_area_x FLOAT DEFAULT 0,
    training_area_y FLOAT DEFAULT 0,
    start_bot VARCHAR(1) DEFAULT '0',
    stop_bot VARCHAR(1) DEFAULT '0',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_qr (qr_id),
    INDEX idx_account_id (account_id)
);

-- Chat messages table
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    qr_id VARCHAR(255) NOT NULL,
    account_id BIGINT NOT NULL,
    party_message TEXT,
    guild_message TEXT,
    send_message TEXT,
    which_chat VARCHAR(1) DEFAULT '0',
    send_message_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_qr (qr_id),
    INDEX idx_account_id (account_id)
);

-- Notifications table
-- notification_type values:
--   0 = Character death notification
--   1 = Rare item drop notification
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    qr_id VARCHAR(255) NOT NULL,
    account_id BIGINT NOT NULL,
    notification_type INT DEFAULT 0 COMMENT '0=death, 1=rare_item',
    notification_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_qr_id (qr_id),
    INDEX idx_account_id (account_id)
);

-- Party information table
CREATE TABLE IF NOT EXISTS party_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    qr_id VARCHAR(255) NOT NULL,
    account_id BIGINT NOT NULL,
    party_users TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_qr (qr_id),
    INDEX idx_account_id (account_id)
);

-- Item/Inventory information table
CREATE TABLE IF NOT EXISTS item_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    qr_id VARCHAR(255) NOT NULL,
    account_id BIGINT NOT NULL,
    item_data MEDIUMTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_qr (qr_id),
    INDEX idx_account_id (account_id)
);

-- QR Code registration table
CREATE TABLE IF NOT EXISTS qr_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    qr_id VARCHAR(255) UNIQUE NOT NULL,
    old_qr_id VARCHAR(255),
    account_id BIGINT NOT NULL,
    player_id BIGINT NOT NULL,
    server VARCHAR(100) NOT NULL,
    name VARCHAR(100) NOT NULL,
    is_active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_account_id (account_id),
    INDEX idx_old_qr_id (old_qr_id)
);
