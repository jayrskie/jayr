<?php
session_start();

if (!isset($_SESSION['library_id'])) {
    header('Location: ../login.php');
    exit();
}

    $displayName = htmlspecialchars($_SESSION['display_name'] ?? $_SESSION['library_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../dashboard-style.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Logo</h2>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="user-dashboard.php"><img class="nav-icon" src="../images/house.png" alt="Overview"> <span class="nav-text">Dashboard</span></a></li>
                <li><a href="user-book-catalog.php"><img class="nav-icon" src="../images/books.png" alt="Books"> <span class="nav-text">View Catalog</span></a></li>
                <li class="clicked-page"><a href="user-transaction-history.php"><img class="nav-icon" src="../images/calendar.png" alt="Borrow"> <span class="nav-text">Transaction History</span></a></li>
                <li><a href="user-due-dates.php"><img class="nav-icon" src="../images/return.png" alt="Return"> <span class="nav-text">Check Due Dates</span></a></li>
                <li><a href="user-feedback.php"><img class="nav-icon" src="../images/people.png" alt="Users"> <span class="nav-text">Give Feedback</span></a></li>
                <li><a href="../logout.php" class="logout-btn"><img class="nav-icon" src="../images/logout.png" alt="Logout"> <span class="nav-text">Logout</span></a></li>
            </ul>
        </nav>
    </div>
    <div class="main-content">
        <div class="header">
            <span class="welcome-text">Welcome, <?php echo $displayName; ?></span>
        </div>
        <div class="content">
            <h2>Borrow History</h2>
            <p style="color: #999; font-size: 0.95em; margin-bottom: 15px;">Records are automatically removed after 7 days</p>
            <table id="historyTable">
                <thead>
                    <tr>
                        <th>Book Title</th>
                        <th>Borrow Date</th>
                        <th>Expires At</th>
                        <th>Days Left</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Borrow history records will be populated here by JavaScript -->
                </tbody>
            </table>

            <hr style="margin: 24px 0; border: none; border-top: 1px solid #eee;">

            <h2>Return History</h2>
            <p style="color: #999; font-size: 0.95em; margin-bottom: 15px;">Records of books you have returned</p>
            <table id="returnTable">
                <thead>
                    <tr>
                        <th>Book Title</th>
                        <th>Borrow Date</th>
                        <th>Return Date</th>
                        <th>Days Borrowed</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Return history records will be populated here by JavaScript -->
                </tbody>
            </table>
        </div>
    </div>

    <style>
        .content {
            width: calc(100% - 40px);
            margin: 18px 20px;
            background: #fff;
            border: 1px solid #e9e9e9;
            border-radius: 8px;
            padding: 18px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.04);
            box-sizing: border-box;
        }

        .content h2 {
            margin: 0 0 12px 0;
            font-size: 1.4em;
            color: #222;
        }

        #historyTable, #returnTable {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95em;
        }

        #historyTable thead th, #returnTable thead th {
            text-align: left;
            padding: 10px 12px;
            background: #f8f9fb;
            border-bottom: 1px solid #e6e6e6;
            color: #333;
        }

        #historyTable tbody td, #returnTable tbody td {
            padding: 10px 12px;
            border-bottom: 1px solid #f1f1f1;
            color: #555;
        }

        #historyTable tbody tr:hover, #returnTable tbody tr:hover {
            background: #fbfbff;
        }

        .no-records {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .days-left {
            font-weight: 500;
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-block;
        }

        .days-left.warning {
            background: #fff3cd;
            color: #856404;
        }

        .days-left.normal {
            background: #d4edda;
            color: #155724;
        }
    </style>

    <script>
        function loadBorrowHistory() {
            fetch('get_borrow_history.php')
                .then(response => {
                    if (!response.ok) throw new Error('Failed to load history');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        displayHistory(data.records);
                    } else {
                        console.error('Error:', data.error);
                    }
                })
                .catch(error => console.error('Fetch error:', error));
        }

        function displayHistory(records) {
            const tableBody = document.querySelector('#historyTable tbody');
            tableBody.innerHTML = '';

            if (records.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="4" class="no-records">No borrow history records</td></tr>';
                return;
            }

            records.forEach(record => {
                const borrowDate = new Date(record.borrow_date);
                const expiresDate = new Date(record.expires_at);
                const now = new Date();
                const daysLeft = Math.ceil((expiresDate - now) / (1000 * 60 * 60 * 24));

                const row = document.createElement('tr');
                const daysClass = daysLeft <= 2 ? 'warning' : 'normal';
                
                row.innerHTML = `
                    <td>${record.book_title}</td>
                    <td>${borrowDate.toLocaleDateString()} ${borrowDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</td>
                    <td>${expiresDate.toLocaleDateString()} ${expiresDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</td>
                    <td><span class="days-left ${daysClass}">${daysLeft} days</span></td>
                `;
                tableBody.appendChild(row);
            });
        }

        function loadReturnHistory() {
            fetch('get_return_history.php')
                .then(response => {
                    if (!response.ok) throw new Error('Failed to load return history');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        displayReturnHistory(data.records);
                    } else {
                        console.error('Error:', data.error);
                    }
                })
                .catch(error => console.error('Fetch error:', error));
        }

        function displayReturnHistory(records) {
            const tableBody = document.querySelector('#returnTable tbody');
            tableBody.innerHTML = '';

            if (records.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="4" class="no-records">No return history records</td></tr>';
                return;
            }

            records.forEach(record => {
                const borrowDate = record.borrow_date ? new Date(record.borrow_date) : null;
                const returnDate = record.return_date ? new Date(record.return_date) : null;

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${record.book_title}</td>
                    <td>${borrowDate ? (borrowDate.toLocaleDateString() + ' ' + borrowDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })) : '-'}</td>
                    <td>${returnDate ? (returnDate.toLocaleDateString() + ' ' + returnDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })) : '-'}</td>
                    <td>${record.days_borrowed !== null && record.days_borrowed !== undefined ? `<span class="days-left normal">${record.days_borrowed} days</span>` : '-'}</td>
                `;
                tableBody.appendChild(row);
            });
        }

        // Load histories on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadBorrowHistory();
            loadReturnHistory();
        });

        // Refresh every 5 minutes
        setInterval(() => {
            loadBorrowHistory();
            loadReturnHistory();
        }, 5 * 60 * 1000);
    </script>
</body>
</html>