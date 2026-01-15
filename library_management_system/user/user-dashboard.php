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
                <li class="clicked-page"><a href="user-dashboard.php"><img class="nav-icon" src="../images/house.png" alt="Overview"> <span class="nav-text">Dashboard</span></a></li>
                <li><a href="user-book-catalog.php"><img class="nav-icon" src="../images/books.png" alt="Books"> <span class="nav-text">View Catalog</span></a></li>
                <li><a href="user-transaction-history.php"><img class="nav-icon" src="../images/calendar.png" alt="Borrow"> <span class="nav-text">Transaction History</span></a></li>
                <li><a href="user-due-dates.php"><img class="nav-icon" src="../images/return.png" alt="Return"> <span class="nav-text">Check Due Dates</a></li>
                <li><a href="user-feedback.php"><img class="nav-icon" src="../images/people.png" alt="Users"> <span class="nav-text">Give Feedback</span></a></li>
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
                <!-- Add more stats here -->
            </div>
            <div id="dueDatesSection" style="margin-top: 30px; display: none;">
                <h3>Books Due Soon</h3>
                <div id="dueDatesList"></div>
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

        .time-left {
            font-weight: 500;
            padding: 2px 6px;
            border-radius: 4px;
            display: inline-block;
            font-size: 0.9em;
        }

        .time-left.safe {
            background: #d4edda;
            color: #155724;
        }

        .time-left.warning {
            background: #fff3cd;
            color: #856404;
        }

        .time-left.danger {
            background: #f8d7da;
            color: #721c24;
        }
    </style>

    <script>
        async function loadStats() {
            try {
                const booksRes = await fetch('../get_total_books.php');
                const booksData = await booksRes.json();
                if (booksData.success) {
                    document.getElementById('totalBooks').textContent = booksData.total_books;
                }

                const availRes = await fetch('../get_available_books.php');
                const availData = await availRes.json();
                if (availData.success) {
                    document.getElementById('availableBooks').textContent = availData.total_available;
                }

                const diffRes = await fetch('../get_total_different_books.php');
                const diffData = await diffRes.json();
                if (diffData.success) {
                    document.getElementById('totalDifferent').textContent = diffData.total_different;
                }

                // Load due dates
                const dueRes = await fetch('get_due_dates.php');
                const dueData = await dueRes.json();
                if (dueData.success) {
                    displayDueDates(dueData.due_dates);
                }
            } catch (err) {
                console.error('Failed to load stats', err);
            }
        }

        // Load on page load
        document.addEventListener('DOMContentLoaded', loadStats);

        function displayDueDates(dueDates) {
            const section = document.getElementById('dueDatesSection');
            const list = document.getElementById('dueDatesList');
            if (dueDates.length === 0) {
                section.style.display = 'none';
                return;
            }
            section.style.display = 'block';
            list.innerHTML = '';
            dueDates.forEach(due => {
                const dueDate = new Date(due.expires_at);
                const now = new Date();
                const diffTime = dueDate - now;
                const days = Math.floor(diffTime / (1000 * 60 * 60 * 24));
                const hours = Math.floor((diffTime % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((diffTime % (1000 * 60 * 60)) / (1000 * 60));
                let timeLeft;
                if (days > 0) {
                    timeLeft = `${days} days ${hours} hours`;
                } else if (hours > 0) {
                    timeLeft = `${hours} hours ${minutes} minutes`;
                } else {
                    timeLeft = `${minutes} minutes`;
                }
                let statusClass = 'safe';
                if (diffTime <= 24 * 60 * 60 * 1000) statusClass = 'danger';
                else if (diffTime <= 3 * 24 * 60 * 60 * 1000) statusClass = 'warning';
                const item = document.createElement('div');
                item.style.marginBottom = '10px';
                item.innerHTML = `<strong>${due.book_title}</strong> - <span class="time-left ${statusClass}">${timeLeft}</span>`;
                list.appendChild(item);
            });
        }
    </script>
</body>
</html>