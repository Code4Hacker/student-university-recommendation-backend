-- Add to your existing UniversityCourses database
USE UniversityCourses;

CREATE TABLE IF NOT EXISTS Students (
    student_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(255) DEFAULT 'USER',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS StudentSubjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    subject VARCHAR(255) NOT NULL,
    grade VARCHAR(2) NOT NULL,
    FOREIGN KEY (student_id) REFERENCES Students(student_id)
);
-- Password: 123456
INSERT INTO Students (username, email, full_name, password, created_at) VALUES
('T22-03-2344', 'john.doe@example.com', 'John Doe', 
 '$2y$10$00IhwRYDsTifudPSkn6YBeQskLbU0pXMQnqNvHvJu.urb8cj7TEii', 
 '2025-04-01 10:00:00'), 
('T22-03-23443', 'mary.smith@example.com', 'Mary Smith', 
 '$2y$10$00IhwRYDsTifudPSkn6YBeQskLbU0pXMQnqNvHvJu.urb8cj7TEii', 
 '2025-04-02 14:30:00'),
('T22-03-24344', 'peter.jones@example.com', 'Peter Jones', 
 '$2y$10$00IhwRYDsTifudPSkn6YBeQskLbU0pXMQnqNvHvJu.urb8cj7TEii', 
 '2025-04-03 09:15:00'), 
('T22-03-2349', 'anna.brown@example.com', 'Anna Brown', 
 '$2y$10$00IhwRYDsTifudPSkn6YBeQskLbU0pXMQnqNvHvJu.urb8cj7TEii', 
 '2025-04-04 11:45:00'), 
('T22-03-2378', 'david.wilson@example.com', 'David Wilson', 
 '$2y$10$00IhwRYDsTifudPSkn6YBeQskLbU0pXMQnqNvHvJu.urb8cj7TEii', 
 '2025-04-05 16:20:00'); 

INSERT INTO StudentSubjects (student_id, subject, grade) VALUES
(1, 'Physics', 'B'),
(1, 'Chemistry', 'C'),
(1, 'Mathematics', 'A'),
(2, 'Economics', 'B'),
(2, 'Commerce', 'B'),
(2, 'Accountancy', 'C'),
(3, 'Physics', 'C'),
(3, 'Chemistry', 'B'),
(3, 'Biology', 'A'),
(4, 'History', 'B'),
(4, 'Geography', 'A'),
(4, 'Literature', 'C'),
(5, 'Chemistry', 'A'),
(5, 'Biology', 'B'),
(5, 'Geography', 'C');