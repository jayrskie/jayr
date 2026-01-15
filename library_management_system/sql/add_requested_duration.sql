-- Add requested_duration column to borrow_requests table
ALTER TABLE borrow_requests ADD COLUMN requested_duration INT DEFAULT 7;