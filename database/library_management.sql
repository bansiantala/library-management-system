CREATE DATABASE IF NOT EXISTS library_management;

USE library_management;


-- =========================================
-- USERS TABLE
-- =========================================

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(100) NOT NULL,

    email VARCHAR(100) NOT NULL UNIQUE,

    password VARCHAR(255) NOT NULL,

    phone VARCHAR(20),

    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- =========================================
-- CATEGORIES TABLE
-- =========================================

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,

    category_name VARCHAR(100) NOT NULL UNIQUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- =========================================
-- BOOKS TABLE
-- =========================================

CREATE TABLE books (
    id INT AUTO_INCREMENT PRIMARY KEY,

    title VARCHAR(200) NOT NULL,

    author VARCHAR(150) NOT NULL,

    category_id INT NOT NULL,

    isbn VARCHAR(50),

    quantity INT NOT NULL DEFAULT 1,

    available_quantity INT NOT NULL DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (category_id)
        REFERENCES categories(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);


-- =========================================
-- ISSUED BOOKS TABLE
-- =========================================

CREATE TABLE issued_books (
    id INT AUTO_INCREMENT PRIMARY KEY,

    book_id INT NOT NULL,

    user_id INT NOT NULL,

    issue_date DATE NOT NULL,

    return_date DATE NOT NULL,

    actual_return_date DATE DEFAULT NULL,

    fine DECIMAL(10,2) DEFAULT 0.00,

    status ENUM('Issued', 'Returned') DEFAULT 'Issued',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (book_id)
        REFERENCES books(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);


-- =========================================
-- SAMPLE CATEGORIES
-- =========================================

INSERT INTO categories (category_name)
VALUES
('Programming'),
('Database'),
('Web Development'),
('Computer Science'),
('Fiction'),
('History'),
('Science'),
('Biography');


-- =========================================
-- SAMPLE BOOKS
-- =========================================

INSERT INTO books
(
    title,
    author,
    category_id,
    isbn,
    quantity,
    available_quantity
)
VALUES
(
    'PHP and MySQL',
    'Luke Welling',
    1,
    'ISBN001',
    5,
    5
),
(
    'HTML and CSS',
    'Jon Duckett',
    3,
    'ISBN002',
    4,
    4
),
(
    'Database System Concepts',
    'Abraham Silberschatz',
    2,
    'ISBN003',
    3,
    3
),
(
    'Introduction to Computer Science',
    'John Smith',
    4,
    'ISBN004',
    5,
    5
),
(
    'The Great Gatsby',
    'F. Scott Fitzgerald',
    5,
    'ISBN005',
    2,
    2
);