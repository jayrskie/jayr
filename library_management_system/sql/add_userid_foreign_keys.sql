-- Migration: Add foreign key constraints for user_id columns
-- IMPORTANT: Backup your database before running this migration.
-- This script assumes the tables and 'user_id' columns exist. If your columns are named differently
-- or contain non-integer data, review and adapt the ALTER statements first.

START TRANSACTION;

-- Ensure user_id columns are integer (adjust if your schema differs)
ALTER TABLE borrow_requests MODIFY COLUMN user_id INT NOT NULL;
ALTER TABLE borrow_history MODIFY COLUMN user_id INT NOT NULL;
ALTER TABLE return_history MODIFY COLUMN user_id INT NOT NULL;

-- Add foreign keys referencing users(id)
ALTER TABLE borrow_requests
  ADD CONSTRAINT fk_borrow_requests_user_id
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE borrow_history
  ADD CONSTRAINT fk_borrow_history_user_id
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE return_history
  ADD CONSTRAINT fk_return_history_user_id
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE;

COMMIT;

-- Notes:
-- 1) If MySQL returns "ERROR 1215: Cannot add foreign key constraint", inspect types of referenced columns.
--    `users.id` must be INT (same signed/unsigned as your user_id columns).
-- 2) If constraint names already exist, either DROP them first or give different names.
-- 3) If your tables contain values in user_id that do not exist in users.id, the ALTER will fail.
--    You may need to fix or remove those rows first (or run a cleanup / mapping step).
