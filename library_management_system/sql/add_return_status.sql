-- Add return_status column to return_history table
ALTER TABLE return_history ADD COLUMN return_status ENUM('Early', 'On Time', 'Late') DEFAULT 'On Time';