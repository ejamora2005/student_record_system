CREATE DATABASE IF NOT EXISTS school_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE school_db;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(30) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS students (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_number VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    course VARCHAR(120) NOT NULL,
    year_level VARCHAR(20) NOT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

SET @student_column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'school_db'
      AND TABLE_NAME = 'students'
      AND COLUMN_NAME = 'profile_image'
);

SET @student_alter_sql = IF(
    @student_column_exists = 0,
    'ALTER TABLE students ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL AFTER year_level',
    'SELECT 1'
);

PREPARE stmt_student FROM @student_alter_sql;
EXECUTE stmt_student;
DEALLOCATE PREPARE stmt_student;
