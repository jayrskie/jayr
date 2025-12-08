-- Create borrow_requests table
CREATE TABLE IF NOT EXISTS borrow_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    library_id VARCHAR(100) NOT NULL,
    book_id INT NOT NULL,
    book_title VARCHAR(255) NOT NULL,
    status ENUM('requested', 'approved', 'rejected') DEFAULT 'requested',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_library_id (library_id),
    INDEX idx_book_id (book_id),
    INDEX idx_status (status),
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);
