-- Alter the status enum to include 'returned' status
ALTER TABLE borrow_requests MODIFY status ENUM('requested', 'approved', 'rejected', 'returned') DEFAULT 'requested';
