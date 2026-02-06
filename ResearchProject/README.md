# Library Book Borrowing System

A web-based library management system that allows users to request books online and librarians to approve or reject those requests. Once approved, the system automatically sets a due date and generates a printable borrow receipt.

This project was built as a learning-focused system to practice backend logic, database relationships, and real-world workflows.

---

## ✨ Features

### User
- View available books
- Request to borrow books
- View borrow history and status (pending, approved, rejected)
- See due dates for approved requests

### Librarian / Admin
- Approve or reject borrow requests
- Automatically assign due dates
- View all borrow transactions
- Print borrow receipts after approval

---

## 🧾 Receipt Generation
- Receipt is generated when a borrow request is approved
- Shows borrower details, borrowed books, due date, and transaction ID
- Uses browser print functionality (can be saved as PDF if no printer is available)

---

## 🛠️ Tech Stack

- **Frontend:** HTML, CSS, JavaScript  
- **Backend:** PHP  
- **Database:** MySQL  
- **Other:** Browser print API (`window.print()`)

---

## 🗂️ Database Structure (Simplified)

- `users` – stores user and librarian accounts  
- `books` – stores book information  
- `borrow_requests` – handles borrow approvals and status  
- `borrowed_books` – links transactions to books  

The system uses relational database concepts such as foreign keys and joins.

---

## 🚀 How It Works (High-Level Flow)

1. User submits a borrow request
2. Request is saved with **pending** status
3. Librarian reviews and approves the request
4. System:
   - Updates status to **approved**
   - Sets approved date and due date
   - Generates a printable receipt
5. User can view borrow history and due dates

---

## 🧠 What I Learned

- Designing relational databases
- Handling approval-based workflows
- Working with dates, due dates, and timezones
- Separating user and admin logic
- Generating printable documents using web technologies
- Debugging and understanding AI-assisted code

---

## 🔮 Possible Improvements

- Email notifications for approvals and due dates
- Overdue fine calculation
- PDF receipt download
- Search and filter for borrow history
- Role-based access control improvements

---

## 📌 Notes

This project was developed as a learning and portfolio project.  
Some parts were AI-assisted, but the logic, structure, and debugging were fully understood and refined during development.
