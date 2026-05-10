-- ==============================
-- Campus2Career Database Schema
-- ==============================
CREATE DATABASE IF NOT EXISTS campus2career;
USE campus2career;

-- ==============================
-- USERS TABLE
-- ==============================
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student','company','admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==============================
-- STUDENTS TABLE
-- ==============================
CREATE TABLE IF NOT EXISTS students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    university VARCHAR(100),
    course VARCHAR(100),
    skills TEXT,
    cv_file VARCHAR(255) NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ==============================
-- COMPANIES TABLE
-- ==============================
CREATE TABLE IF NOT EXISTS companies (
    company_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    company_name VARCHAR(100),
    location VARCHAR(100),
    description LONGTEXT NULL,
    website VARCHAR(255) NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ==============================
-- ADMINS TABLE
-- ==============================
CREATE TABLE IF NOT EXISTS admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ==============================
-- INTERNSHIPS TABLE
-- ==============================
CREATE TABLE IF NOT EXISTS internships (
    internship_id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT,
    title VARCHAR(150) NOT NULL,
    description LONGTEXT,
    responsibilities LONGTEXT,
    expectations LONGTEXT,
    internship_location VARCHAR(160),
    requirements LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE
);

-- ==============================
-- APPLICATIONS TABLE
-- ==============================
CREATE TABLE IF NOT EXISTS applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    internship_id INT,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    interview_date DATETIME NULL,
    interview_location VARCHAR(255) NULL,
    interview_notes TEXT NULL,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (internship_id) REFERENCES internships(internship_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS student_certificates (
    certificate_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);

-- ==============================
-- SAMPLE DATA
-- ==============================

-- USERS (passwords are hashed via PHP - use setup.php to insert hashed versions)
-- The raw passwords below are for reference only; run setup.php to insert proper hashed data

-- Admin: admin@c2c.com / admin123
-- Student: john@student.com / 123456
-- Student: alice@student.com / 123456
-- Company: mtn@company.com / 123456
-- Company: irembo@company.com / 123456
