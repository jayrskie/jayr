# Database Setup Instructions

## Create borrow_requests Table

### Option 1: Using phpMyAdmin (Recommended for XAMPP)

1. Open phpMyAdmin in your browser:
   - Start XAMPP and ensure MySQL is running
   - Go to http://localhost/phpmyadmin
   
2. Select your library database from the left sidebar

3. Click the "SQL" tab at the top

4. Copy and paste the entire content of `sql/create_borrow_requests.sql`

5. Click "Go" to execute

### Option 2: Using MySQL Command Line

1. Open Command Prompt or PowerShell

2. Navigate to XAMPP MySQL directory:
   ```
   cd C:\xampp\mysql\bin
   ```

3. Run the following command (replace `library` with your actual database name):
   ```
   mysql -u root -p library < "C:\xampp\htdocs\library-management-system\sql\create_borrow_requests.sql"
   ```
   - Press Enter when prompted for password (leave blank if no password set)

### Option 3: Using MySQL Workbench (if installed)

1. Open MySQL Workbench and connect to your local MySQL server

2. Open `sql/create_borrow_requests.sql` from File > Open SQL Script

3. Click the "Execute" lightning bolt icon

## Verification

After running the SQL, verify the table was created:

- **In phpMyAdmin:** Refresh the database, you should see `borrow_requests` table in the list
- **In MySQL CLI:** 
  ```
  DESCRIBE borrow_requests;
  ```
  This should show all columns: id, library_id, book_id, book_title, status, created_at, updated_at

## Table Structure

The `borrow_requests` table includes:
- **id**: Primary key, auto-incrementing
- **library_id**: User's library ID (from users table)
- **book_id**: Reference to the book being requested (foreign key)
- **book_title**: Title of the book (denormalized for quick display)
- **status**: ENUM field with values: 'requested', 'approved', 'rejected'
- **created_at**: Timestamp when request was created
- **updated_at**: Timestamp when request was last updated
- **Indexes**: On library_id, book_id, and status for faster queries
