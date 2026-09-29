<?php
require_once __DIR__ . '/bootstrap.php';

/** Creates the admin tables if they don't exist yet. Safe to run repeatedly. */
function install_schema(PDO $pdo): void
{
    $mysql = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql';
    $id = $mysql ? 'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $tail = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';

    $tables = [
        "CREATE TABLE IF NOT EXISTS vf_content (
            ckey VARCHAR(100) NOT NULL PRIMARY KEY,
            cvalue MEDIUMTEXT NOT NULL,
            updated_at DATETIME NOT NULL
        )",
        "CREATE TABLE IF NOT EXISTS vf_positions (
            id $id,
            title VARCHAR(150) NOT NULL,
            location VARCHAR(150) NOT NULL DEFAULT '',
            employment_type VARCHAR(100) NOT NULL DEFAULT '',
            description TEXT NOT NULL,
            is_open TINYINT NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )",
        "CREATE TABLE IF NOT EXISTS vf_applications (
            id $id,
            first_name VARCHAR(120) NOT NULL,
            last_name VARCHAR(120) NOT NULL,
            city VARCHAR(120) NOT NULL,
            position VARCHAR(150) NOT NULL,
            phone VARCHAR(40) NOT NULL,
            email VARCHAR(190) NOT NULL,
            cv_file VARCHAR(190) NOT NULL DEFAULT '',
            cv_name VARCHAR(190) NOT NULL DEFAULT '',
            mail_sent TINYINT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL
        )",
        "CREATE TABLE IF NOT EXISTS vf_admins (
            id $id,
            username VARCHAR(60) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            last_login_at DATETIME NULL,
            created_at DATETIME NOT NULL
        )",
        "CREATE TABLE IF NOT EXISTS vf_login_attempts (
            id $id,
            ip VARCHAR(45) NOT NULL,
            attempted_at DATETIME NOT NULL
        )",
    ];

    foreach ($tables as $sql) {
        $pdo->exec($sql . $tail);
    }
}
