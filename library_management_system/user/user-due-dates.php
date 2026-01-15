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
                <li><a href="user-transaction-history.php"><img class="nav-icon" src="../images/calendar.png" alt="Borrow"> <span class="nav-text">Transaction History</span></a></li>
                <li class="clicked-page"><a href="user-due-dates.php"><img class="nav-icon" src="../images/return.png" alt="Return"> <span class="nav-text">Check Due Dates</span></a></li>
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
            <h2>Check Due Dates</h2>
            <p style="color: #666; margin-bottom: 20px;">Books you have borrowed that are due within 2 days</p>
            <table id="dueTable">
                <thead>
                    <tr>
                        <th>Book Title</th>
                        <th>Due Date</th>
                        <th>Time Left</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Due dates will be populated here -->
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

        #dueTable {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95em;
        }

        #dueTable thead th {
            text-align: left;
            padding: 10px 12px;
            background: #f8f9fb;
            border-bottom: 1px solid #e6e6e6;
            color: #333;
        }

        #dueTable tbody td {
            padding: 10px 12px;
            border-bottom: 1px solid #f1f1f1;
            color: #555;
        }

        #dueTable tbody tr:hover {
            background: #fbfbff;
        }

        .no-due {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .time-left {
            font-weight: 500;
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-block;
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
        function loadDueDates() {
            fetch('get_due_dates.php')
                .then(response => {
                    if (!response.ok) throw new Error('Failed to load due dates');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        displayDueDates(data.due_dates);
                    } else {
                        console.error('Error:', data.error);
                        document.querySelector('#dueTable tbody').innerHTML = '<tr><td colspan="3" class="no-due">Failed to load due dates</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    document.querySelector('#dueTable tbody').innerHTML = '<tr><td colspan="3" class="no-due">Error loading due dates</td></tr>';
                });
        }

        function displayDueDates(dueDates) {
            const tableBody = document.querySelector('#dueTable tbody');
            tableBody.innerHTML = '';

            if (dueDates.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="3" class="no-due">No books due within 2 days</td></tr>';
                return;
            }

            dueDates.forEach(due => {
                const dueDate = new Date(due.expires_at);
                const now = new Date();
                const diffTime = dueDate - now;

                let statusClass = 'safe';
                if (diffTime <= 24 * 60 * 60 * 1000) statusClass = 'danger'; // <= 1 day
                else if (diffTime <= 3 * 24 * 60 * 60 * 1000) statusClass = 'warning'; // <= 3 days

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

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${due.book_title}</td>
                    <td>${dueDate.toLocaleDateString()} ${dueDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</td>
                    <td><span class="time-left ${statusClass}">${timeLeft}</span></td>
                `;
                tableBody.appendChild(row);
            });
        }

        // Load on page load
        document.addEventListener('DOMContentLoaded', loadDueDates);
    </script>
</body>
</html>