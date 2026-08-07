-- Task Dashboard — MVP schema
-- Run this once in phpMyAdmin / mysql CLI to set up the database.

CREATE DATABASE IF NOT EXISTS task_dashboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE task_dashboard;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('manager', 'employee') NOT NULL DEFAULT 'employee',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    assigned_to INT NOT NULL,
    assigned_by INT NOT NULL,
    status ENUM('pending', 'in_progress', 'completed') NOT NULL DEFAULT 'pending',
    due_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Seed users. Password for ALL three is: password123
-- (change these after first login — this hash is just for testing)
INSERT INTO users (name, email, password, role) VALUES
('Tahir (Manager)', 'manager@example.com', '$2y$10$jktkPcDUuKsBM5QVBJW1yuuQvH2qsOclS/0DyIPSzEcr6E0KliEtm', 'manager'),
('Employee One', 'employee1@example.com', '$2y$10$jktkPcDUuKsBM5QVBJW1yuuQvH2qsOclS/0DyIPSzEcr6E0KliEtm', 'employee'),
('Employee Two', 'employee2@example.com', '$2y$10$jktkPcDUuKsBM5QVBJW1yuuQvH2qsOclS/0DyIPSzEcr6E0KliEtm', 'employee');
