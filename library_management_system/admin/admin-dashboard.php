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
                <li class="clicked-page"><a href=""><img class="nav-icon" src="../images/house.png" alt="Overview"> <span class="nav-text">Dashboard</span></a></li>
                <li><a href="admin-manage-books.php"><img class="nav-icon" src="../images/books.png" alt="Books"> <span class="nav-text">Manage Books</span></a></li>
                <li><a href="admin-manage-users.php"><img class="nav-icon" src="../images/calendar.png" alt="Users"> <span class="nav-text">User Management</span></a></li>
                <li><a href="admin-view-transactions.php"><img class="nav-icon" src="../images/return.png" alt="Return"> <span class="nav-text">View Transactions</span></a></li>
                <li><a href="admin-view-feedback.php"><img class="nav-icon" src="../images/people.png" alt="Users"> <span class="nav-text">View Feedback</span></a></li>
                <li><a href="../logout.php" class="logout-btn"><img class="nav-icon" src="../images/logout.png" alt="Logout"> <span class="nav-text">Logout</span></a></li>
            </ul>
        </nav>
    </div>
    <div class="main-content">
        <div class="header">
                <span class="welcome-text">Welcome! <?php echo $displayName; ?></span>
        </div>
        <div class="content">
            <h2>Dashboard Overview</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Book Copies</h3>
                    <div class="stat-number" id="totalBooks">0</div>
                </div>
                <div class="stat-card">
                    <h3>Available Books</h3>
                    <div class="stat-number" id="availableBooks">0</div>
                </div>
                <div class="stat-card">
                    <h3>Total Different Books</h3>
                    <div class="stat-number" id="totalDifferent">0</div>
                </div>
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <div class="stat-number" id="totalUsers">0</div>
                </div>
                <div class="stat-card">
                    <h3>Pending Requests</h3>
                    <div class="stat-number" id="pendingRequests">0</div>
                </div>
                <div class="stat-card">
                    <h3>Borrowed Books</h3>
                    <div class="stat-number" id="borrowedBooks">0</div>
                </div>
                <!-- Add more stats here -->
            </div>
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
            margin: 0 0 20px 0;
            font-size: 1.4em;
            color: #222;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .stat-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }

        .stat-card h3 {
            margin: 0 0 10px 0;
            font-size: 1em;
            color: #495057;
        }

        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #007bff;
        }
    </style>

    <script>
        async function loadStats() {
            try {
                // Total books
                const booksRes = await fetch('../get_total_books.php');
                const booksData = await booksRes.json();
                if (booksData.success) {
                    document.getElementById('totalBooks').textContent = booksData.total_books;
                }

                // Available books
                const availRes = await fetch('../get_available_books.php');
                const availData = await availRes.json();
                if (availData.success) {
                    document.getElementById('availableBooks').textContent = availData.total_available;
                }

                // Total different books
                const diffRes = await fetch('../get_total_different_books.php');
                const diffData = await diffRes.json();
                if (diffData.success) {
                    document.getElementById('totalDifferent').textContent = diffData.total_different;
                }

                // Total users
                const usersRes = await fetch('get_total_users.php');
                const usersData = await usersRes.json();
                if (usersData.success) {
                    document.getElementById('totalUsers').textContent = usersData.total_users;
                }

                // Pending requests
                const pendingRes = await fetch('get_pending_count.php');
                const pendingData = await pendingRes.json();
                if (pendingData.success) {
                    document.getElementById('pendingRequests').textContent = pendingData.count;
                }

                // Borrowed books
                const borrowedRes = await fetch('../get_borrowed_books.php');
                const borrowedData = await borrowedRes.json();
                if (borrowedData.success) {
                    document.getElementById('borrowedBooks').textContent = borrowedData.total_borrowed;
                }
            } catch (err) {
                console.error('Failed to load stats', err);
            }
        }

        // Load on page load
        document.addEventListener('DOMContentLoaded', loadStats);
    </script>
</body>
</html>