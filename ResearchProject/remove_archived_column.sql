-- Remove archived column from books table
-- Archive functionality is now handled at the copy level only
-- Individual book copies can be marked as "archived" status

-- Drop the archived column from books table
ALTER TABLE books DROP COLUMN IF EXISTS archived;

-- Verify the books table structure after removal
DESCRIBE books;

-- Note: Book copies are now archived individually by setting their status to "archived"
-- Available status options for book_copies are:
-- - available (default when adding new books)
-- - damaged (temporarily unavailable)
-- - lost (temporarily unavailable)
-- - archived (permanently unavailable)
