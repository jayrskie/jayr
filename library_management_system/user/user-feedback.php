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
                <li><a href="user-due-dates.php"><img class="nav-icon" src="../images/return.png" alt="Return"> <span class="nav-text">Check Due Dates</span></a></li>
                <li class="clicked-page"><a href="user-feedback.php"><img class="nav-icon" src="../images/people.png" alt="Users"> <span class="nav-text">Give Feedback</span></a></li>
                <li><a href="../logout.php" class="logout-btn"><img class="nav-icon" src="../images/logout.png" alt="Logout"> <span class="nav-text">Logout</span></a></li>
            </ul>
        </nav>
    </div>
    <div class="main-content">
        <div class="header">   
            <span class="welcome-text">Welcome! <?php echo $displayName; ?></span>
        </div>
        <div class="content">
            <h2>Give Feedback</h2>
            <p style="color: #666; margin-bottom: 20px;">Share your thoughts about the library system</p>
            <form id="feedbackForm">
                <textarea id="feedbackText" name="feedback" rows="6" placeholder="Write your feedback here..." required></textarea>
                <button type="submit" class="submit-btn">Submit Feedback</button>
            </form>
            <div id="message" style="margin-top: 15px;"></div>
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

        #feedbackForm {
            display: flex;
            flex-direction: column;
        }

        #feedbackText {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1em;
            resize: vertical;
            margin-bottom: 15px;
        }

        .submit-btn {
            align-self: flex-start;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1em;
        }

        .submit-btn:hover {
            background: #0056b3;
        }

        .submit-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        #message {
            font-weight: 500;
        }

        .success {
            color: #28a745;
        }

        .error {
            color: #dc3545;
        }
    </style>

    <script>
        document.getElementById('feedbackForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const feedback = document.getElementById('feedbackText').value.trim();
            const submitBtn = document.querySelector('.submit-btn');
            const messageDiv = document.getElementById('message');

            if (!feedback) return;

            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';

            try {
                const res = await fetch('submit_feedback.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ feedback })
                });

                const data = await res.json();
                if (data.success) {
                    messageDiv.innerHTML = '<span class="success">Feedback submitted successfully!</span>';
                    document.getElementById('feedbackText').value = '';
                } else {
                    messageDiv.innerHTML = '<span class="error">Error: ' + (data.error || 'Failed to submit feedback') + '</span>';
                }
            } catch (err) {
                messageDiv.innerHTML = '<span class="error">An error occurred: ' + err.message + '</span>';
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Feedback';
            }
        });
    </script>
</body>
</html>