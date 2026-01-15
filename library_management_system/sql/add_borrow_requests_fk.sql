-- Add foreign key constraint for user_id in borrow_requests table
-- This ensures user_id values reference users.id
ALTER TABLE borrow_requests 
ADD CONSTRAINT fk_borrow_requests_user_id 
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE;
