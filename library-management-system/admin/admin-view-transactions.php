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
                <a href="admin-view-transactions.php" class="header-btn clicked-header-page">Borrow Request</a>
                <a href="admin-borrowed-books.php" class="header-btn">Borrowed Books</a>
                <a href="admin-borrow-history.php" class="header-btn">Borrow History</a>
                <a href="admin-return-history.php" class="header-btn">Return History</a>
            </div>
            <span class="welcome-text">Welcome, <?php echo $displayName; ?></span>
        </div>
            <!-- Transactions main area -->
            <div class="transactions-main">
                <div class="transactions-search">
                    <input id="borrowSearch" class="transactions-search-bar" type="text" placeholder="Search borrow requests by user, book, or id..." aria-label="Search borrow requests">
                </div>

                <div id="requestsContainer" class="requests-container">
                    <p class="no-requests">No borrow request yet</p>
                </div>
            </div>
    </div>

    <script>
    // Poll borrow requests and render into #requestsContainer
    async function fetchRequests(){
        try{
            const res = await fetch('../user/get_borrow_requests.php');
            if (!res.ok) return;
            const data = await res.json();
            if (!data || !data.success) return;
            const list = Array.isArray(data.requests) ? data.requests : [];
            
            // Only show requested items
            const requestedList = list.filter(r => r.status === 'requested');
            
            // Render requested items
            const container = document.getElementById('requestsContainer');
            if (!container) return;
            container.innerHTML = '';
            if (requestedList.length === 0){
                container.innerHTML = '<p class="no-requests">No borrow request yet</p>';
            } else {
                requestedList.reverse().forEach(r => {
                    const displayUser = r.library_id || r.username || ('#' + (r.user_id || r.userId || r.id));
                    const html = `<div class="request-card" data-request-id="${r.id}">
                        <div class="request-info">
                            <div><strong>${displayUser}</strong> is requesting to borrow the book (<strong>${r.book_title}</strong>)</div>
                            <div class="request-meta">${new Date(r.created_at).toLocaleString()}</div>
                        </div>
                        <div class="request-actions">
                            <button class="approve-btn" data-request-id="${r.id}" aria-label="Approve request">Approve</button>
                            <button class="reject-btn" data-request-id="${r.id}" aria-label="Reject request">Reject</button>
                        </div>
                    </div>`;
                    container.insertAdjacentHTML('beforeend', html);
                });
            }
            
            // Attach approve/reject handlers
            attachRequestHandlers();
        } catch (err){
            console.error('fetchRequests', err);
        }
    }
    
    // Approve/Reject handlers
    function attachRequestHandlers(){
        document.querySelectorAll('.approve-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const requestId = btn.getAttribute('data-request-id');
                await handleRequestAction(requestId, 'approve');
            });
        });
        
        document.querySelectorAll('.reject-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const requestId = btn.getAttribute('data-request-id');
                await handleRequestAction(requestId, 'reject');
            });
        });
    }
    
    async function handleRequestAction(requestId, action){
        const endpoint = action === 'approve' ? './approve_request.php' : './reject_request.php';
        try{
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ request_id: requestId })
            });
            
            if (!res.ok) {
                alert('Failed to ' + action + ' request');
                return;
            }
            
            const data = await res.json();
            if (data.success) {
                // Refresh the list
                fetchRequests();
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
            }
        } catch (err){
            console.error('handleRequestAction', err);
            alert('An error occurred');
        }
    }
    
    // initial load and poll
    fetchRequests();
    setInterval(fetchRequests, 3000);
    </script>

</body>
</html>
