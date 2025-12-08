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
    <title>Transactions/Reports</title>
    <link rel="stylesheet" href="../dashboard-style.css">
    <link rel="stylesheet" href="view-transaction-style.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Logo</h2>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="admin-dashboard.php"><img class="nav-icon" src="../images/house.png" alt="Overview"> <span class="nav-text">Dashboard</span></a></li>
                <li><a href="admin-manage-books.php"><img class="nav-icon" src="../images/books.png" alt="Books"> <span class="nav-text">Manage Books</span></a></li>
                <li><a href="admin-manage-users.php"><img class="nav-icon" src="../images/calendar.png" alt="Users"> <span class="nav-text">Manage Users</span></a></li>
                <li class="clicked-page"><a href="admin-view-transactions.php"><img class="nav-icon" src="../images/return.png" alt="Return"> <span class="nav-text">View Transactions</span></a></li>
                <li><a href="admin-view-feedback.php"><img class="nav-icon" src="../images/people.png" alt="Users"> <span class="nav-text">View Feedback</span></a></li>
                <li><a href="../logout.php" class="logout-btn"><img class="nav-icon" src="../images/logout.png" alt="Logout"> <span class="nav-text">Logout</span></a></li>
            </ul>
        </nav>
    </div>
    <div class="main-content">
        <div class="header">
            <div class="header-actions">
                <a href="admin-view-transactions.php" class="header-btn">Borrow Request</a>
                <a href="admin-borrowed-books.php" class="header-btn">Borrowed Books</a>
                <a href="admin-borrow-history.php" class="header-btn clicked-header-page">Borrow History</a>
                <a href="admin-return-history.php" class="header-btn">Return History</a>
            </div>
            <span class="welcome-text">Welcome, <?php echo $displayName; ?></span>
        </div>
        <div class="content">
            <h2>Borrow History</h2>
            <p style="color: #999; font-size: 0.95em; margin-bottom: 15px;">All borrow records (records expire after 7 days)</p>
            <input class="search-bar" type="text" id="searchInput" placeholder="Search by user or book title" aria-label="Search history">
            <table id="historyTable">
                <thead>
                    <tr>
                        <th>Library ID</th>
                        <th>User</th>
                        <th>Book Title</th>
                        <th>Borrow Date</th>
                        <th>Expires At</th>
                                <th>Days Left</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- History records will be populated here by JavaScript -->
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

        .search-bar {
            width: 100%;
            max-width: 420px;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            margin-bottom: 12px;
            font-size: 0.95em;
        }

        #historyTable {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95em;
        }

        #historyTable thead th {
            text-align: left;
            padding: 10px 12px;
            background: #f8f9fb;
            border-bottom: 1px solid #e6e6e6;
            color: #333;
        }

        #historyTable tbody td {
            padding: 10px 12px;
            border-bottom: 1px solid #f1f1f1;
            color: #555;
        }

        #historyTable tbody tr:hover {
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
        let allRecords = [];

        function loadBorrowHistory() {
            fetch('../user/get_borrow_history.php')
                .then(response => {
                    if (!response.ok) throw new Error('Failed to load history');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        allRecords = data.records;
                        displayHistory(allRecords);
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
                tableBody.innerHTML = '<tr><td colspan="6" class="no-records">No borrow history records</td></tr>';
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
                    <td>${record.library_id}</td>
                    <td>${record.username || 'Unknown'}</td>
                    <td>${record.book_title}</td>
                    <td>${borrowDate.toLocaleDateString()} ${borrowDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</td>
                    <td>${expiresDate.toLocaleDateString()} ${expiresDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</td>
                    <td><span class="days-left ${daysClass}">${daysLeft} days</span></td>
                `;
                tableBody.appendChild(row);
            });
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const filtered = allRecords.filter(record =>
                (record.library_id?.toLowerCase().includes(searchTerm)) ||
                (record.username?.toLowerCase().includes(searchTerm)) ||
                (record.book_title?.toLowerCase().includes(searchTerm))
            );
            displayHistory(filtered);
        });

        // Load history on page load
        document.addEventListener('DOMContentLoaded', loadBorrowHistory);

        // Refresh every 5 minutes
        setInterval(loadBorrowHistory, 5 * 60 * 1000);
    </script>
</body>
</html>