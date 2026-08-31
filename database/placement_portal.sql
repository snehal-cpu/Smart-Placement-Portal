CREATE DATABASE IF NOT EXISTS smart_placement;

USE smart_placement;

-- ==========================================
-- USERS TABLE
-- ==========================================

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'recruiter', 'admin') NOT NULL DEFAULT 'student',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- ==========================================
-- STUDENTS TABLE
-- ==========================================

CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    enrollment_no VARCHAR(50) UNIQUE,
    phone VARCHAR(20),
    department VARCHAR(100),
    course VARCHAR(100),
    year INT,
    cgpa DECIMAL(4,2),
    graduation_year YEAR,
    resume VARCHAR(255),
    profile_photo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);


-- ==========================================
-- COMPANIES TABLE
-- ==========================================

CREATE TABLE companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    company_name VARCHAR(150) NOT NULL,
    company_email VARCHAR(150),
    phone VARCHAR(20),
    website VARCHAR(255),
    location VARCHAR(150),
    industry VARCHAR(100),
    description TEXT,
    logo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);


-- ==========================================
-- JOBS TABLE
-- ==========================================

CREATE TABLE jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    job_title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    job_type ENUM(
        'Full Time',
        'Part Time',
        'Internship'
    ) DEFAULT 'Full Time',
    location VARCHAR(150),
    salary VARCHAR(100),
    min_cgpa DECIMAL(4,2) DEFAULT 0.00,
    eligible_department VARCHAR(255),
    eligible_year VARCHAR(100),
    application_deadline DATE,
    vacancies INT DEFAULT 1,
    status ENUM(
        'Open',
        'Closed',
        'Draft'
    ) DEFAULT 'Open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (company_id)
        REFERENCES companies(id)
        ON DELETE CASCADE
);


-- ==========================================
-- SKILLS TABLE
-- ==========================================

CREATE TABLE skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    skill_name VARCHAR(100) NOT NULL UNIQUE
);


-- ==========================================
-- STUDENT SKILLS
-- ==========================================

CREATE TABLE student_skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    skill_id INT NOT NULL,

    UNIQUE(student_id, skill_id),

    FOREIGN KEY (student_id)
        REFERENCES students(id)
        ON DELETE CASCADE,

    FOREIGN KEY (skill_id)
        REFERENCES skills(id)
        ON DELETE CASCADE
);


-- ==========================================
-- JOB SKILLS
-- ==========================================

CREATE TABLE job_skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    skill_id INT NOT NULL,

    UNIQUE(job_id, skill_id),

    FOREIGN KEY (job_id)
        REFERENCES jobs(id)
        ON DELETE CASCADE,

    FOREIGN KEY (skill_id)
        REFERENCES skills(id)
        ON DELETE CASCADE
);


-- ==========================================
-- APPLICATIONS TABLE
-- ==========================================

CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    job_id INT NOT NULL,

    status ENUM(
        'Applied',
        'Under Review',
        'Shortlisted',
        'Rejected',
        'Selected'
    ) DEFAULT 'Applied',

    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE(student_id, job_id),

    FOREIGN KEY (student_id)
        REFERENCES students(id)
        ON DELETE CASCADE,

    FOREIGN KEY (job_id)
        REFERENCES jobs(id)
        ON DELETE CASCADE
);


-- ==========================================
-- NOTIFICATIONS TABLE
-- ==========================================

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'general',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);


-- ==========================================
-- PLACEMENT RECORDS
-- ==========================================

CREATE TABLE placements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    company_id INT NOT NULL,
    job_id INT NOT NULL,
    package VARCHAR(100),
    placement_date DATE,
    status ENUM(
        'Placed',
        'Joined',
        'Completed'
    ) DEFAULT 'Placed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (student_id)
        REFERENCES students(id)
        ON DELETE CASCADE,

    FOREIGN KEY (company_id)
        REFERENCES companies(id)
        ON DELETE CASCADE,

    FOREIGN KEY (job_id)
        REFERENCES jobs(id)
        ON DELETE CASCADE
);


-- ==========================================
-- DEFAULT SKILLS
-- ==========================================

INSERT INTO skills (skill_name) VALUES
('C'),
('C++'),
('Java'),
('JavaScript'),
('PHP'),
('Python'),
('HTML'),
('CSS'),
('Bootstrap'),
('MySQL'),
('SQL'),
('Git'),
('GitHub'),
('React'),
('Node.js'),
('Communication'),
('Problem Solving'),
('Data Structures'),
('OOP'),
('Machine Learning');


-- ==========================================
-- DEFAULT ADMIN
-- ==========================================

INSERT INTO users
(full_name, email, password, role)
VALUES
(
    'System Administrator',
    'admin@smartplacement.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llCj3a8y5N4Z7kY8h6e',
    'admin'
);