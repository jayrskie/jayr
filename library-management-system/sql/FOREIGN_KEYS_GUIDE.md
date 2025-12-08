-- Foreign Key Relationships for Library Management System
-- This file documents all foreign key constraints in the database

-- EXISTING FOREIGN KEYS (already in table creation):
-- 1. books.id <- borrow_requests.book_id (ON DELETE CASCADE)
-- 2. books.id <- borrow_history.book_id (ON DELETE CASCADE)
-- 3. books.id <- return_history.book_id (ON DELETE CASCADE)
-- 4. users.id <- borrow_history.user_id (ON DELETE CASCADE)
-- 5. users.id <- return_history.user_id (ON DELETE CASCADE)

-- FOREIGN KEYS TO ADD (via migration scripts):
-- 1. add_borrow_requests_fk.sql: users.library_id <- borrow_requests.library_id (ON DELETE CASCADE)

-- TABLE RELATIONSHIPS:
-- users (id, library_id, username, password, role)
--   ├─> borrow_requests (library_id) - references users.library_id
--   ├─> borrow_history (user_id) - references users.id
--   └─> return_history (user_id) - references users.id
--
-- books (id, title, author, isbn, quantity, available)
--   ├─> borrow_requests (book_id) - references books.id
--   ├─> borrow_history (book_id) - references books.id
--   └─> return_history (book_id) - references books.id

-- MIGRATION ORDER:
-- 1. create_borrow_requests.sql (creates borrow_requests table with book_id FK)
-- 2. create_borrow_history.sql (creates borrow_history with user_id and book_id FKs)
-- 3. create_return_history.sql (creates return_history with user_id and book_id FKs)
-- 4. add_borrow_requests_fk.sql (adds library_id FK to borrow_requests)
-- 5. update_borrow_requests_status.sql (updates status enum to include 'returned')
