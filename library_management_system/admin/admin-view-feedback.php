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
    <title>Feedback</title>
    <link rel="stylesheet" href="../dashboard-style.css">
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
                <li><a href="admin-manage-users.php"><img class="nav-icon" src="../images/calendar.png" alt="Users"> <span class="nav-text">User Management</span></a></li>
                <li><a href="admin-view-transactions.php"><img class="nav-icon" src="../images/return.png" alt="Return"> <span class="nav-text">View Transactions</span></a></li>
                <li class="clicked-page"><a href="admin-view-feedback.php"><img class="nav-icon" src="../images/people.png" alt="Users"> <span class="nav-text">View Feedback</span></a></li>
                <li><a href="../logout.php" class="logout-btn"><img class="nav-icon" src="../images/logout.png" alt="Logout"> <span class="nav-text">Logout</span></a></li>
            </ul>
        </nav>
    </div>
    <div class="main-content">
        <div class="header">
            <span class="welcome-text">Welcome! <?php echo $displayName; ?></span>
        </div>
        <div class="content">
            <h2>User Feedback</h2>
            <p style="color: #666; margin-bottom: 20px;">View feedback submitted by users</p>
            <input class="search-bar" type="text" id="searchInput" placeholder="Search by user or feedback content" aria-label="Search feedback">
            <div id="feedbackContainer">
                <!-- Feedback items will be loaded here -->
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
            margin-bottom: 20px;
            font-size: 0.95em;
        }

        .feedback-item {
            border: 1px solid #e9e9e9;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background: #fafafa;
        }

        .feedback-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.9em;
            color: #666;
        }

        .feedback-user {
            font-weight: 500;
        }

        .feedback-date {
            color: #999;
        }

        .feedback-text {
            color: #333;
            line-height: 1.5;
        }

        .no-feedback {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>

    <script>
        let allFeedbacks = [];

        function loadFeedbacks() {
            fetch('../user/get_feedbacks.php')
                .then(response => {
                    if (!response.ok) throw new Error('Failed to load feedbacks');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        allFeedbacks = data.feedbacks;
                        displayFeedbacks(allFeedbacks);
                    } else {
                        console.error('Error:', data.error);
                        document.getElementById('feedbackContainer').innerHTML = '<p class="no-feedback">Failed to load feedbacks</p>';
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    document.getElementById('feedbackContainer').innerHTML = '<p class="no-feedback">Error loading feedbacks</p>';
                });
        }

        function displayFeedbacks(feedbacks) {
            const container = document.getElementById('feedbackContainer');
            container.innerHTML = '';

            if (feedbacks.length === 0) {
                container.innerHTML = '<p class="no-feedback">No feedback submitted yet</p>';
                return;
            }

            feedbacks.forEach(feedback => {
                const date = new Date(feedback.created_at);
                const formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                const item = document.createElement('div');
                item.className = 'feedback-item';
                item.innerHTML = `
                    <div class="feedback-header">
                        <span class="feedback-user">${feedback.library_id} (${feedback.username})</span>
                        <span class="feedback-date">${formattedDate}</span>
                    </div>
                    <div class="feedback-text">${feedback.feedback_text}</div>
                `;
                container.appendChild(item);
            });
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const filtered = allFeedbacks.filter(feedback =>
                (feedback.library_id?.toLowerCase().includes(searchTerm)) ||
                (feedback.username?.toLowerCase().includes(searchTerm)) ||
                (feedback.feedback_text?.toLowerCase().includes(searchTerm))
            );
            displayFeedbacks(filtered);
        });

        // Load feedbacks on page load
        document.addEventListener('DOMContentLoaded', loadFeedbacks);

        // Refresh every 5 minutes
        setInterval(loadFeedbacks, 5 * 60 * 1000);
    </script>
</body>
</html>