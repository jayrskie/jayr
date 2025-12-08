-- Update borrow_requests table to include 'returned' status in enum
-- This allows tracking books that have been returned
ALTER TABLE borrow_requests 
MODIFY COLUMN status ENUM('requested', 'approved', 'rejected', 'returned') DEFAULT 'requested';
