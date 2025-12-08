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
    <title>Borrowed Books</title>
    <link rel="stylesheet" href="../dashboard-style.css">
    <link rel="stylesheet" href="borrowed-books-style.css">
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
                <a href="admin-borrowed-books.php" class="header-btn clicked-header-page">Borrowed Books</a>
                <a href="admin-borrow-history.php" class="header-btn">Borrow History</a>
                <a href="admin-return-history.php" class="header-btn">Return History</a>
            </div>
            <span class="welcome-text">Welcome, <?php echo $displayName; ?></span>
        </div>
        <!-- Borrowed Books main area -->
        <div class="borrowed-main">
            <div class="borrowed-search">
                <input id="borrowedSearch" class="borrowed-search-bar" type="text" placeholder="Search borrowed books by user, book, or id..." aria-label="Search borrowed books">
            </div>

            <div id="borrowedBooksContainer" class="borrowed-books-container">
                <p class="no-borrowed">No borrowed books</p>
            </div>
        </div>
    </div>

    <script>
    // Poll borrowed books and render into #borrowedBooksContainer
    async function fetchBorrowedBooks(){
        try{
            const res = await fetch('../user/get_borrow_requests.php');
            if (!res.ok) return;
            const data = await res.json();
            if (!data || !data.success) return;
            const list = Array.isArray(data.requests) ? data.requests : [];
            
            // Only show approved items (currently borrowed)
            const borrowedList = list.filter(r => r.status === 'approved');
            
            // Render borrowed books
            const container = document.getElementById('borrowedBooksContainer');
            if (!container) return;
            container.innerHTML = '';
            if (borrowedList.length === 0){
                container.innerHTML = '<p class="no-borrowed">No borrowed books</p>';
            } else {
                borrowedList.reverse().forEach(r => {
                    const html = `<div class="borrowed-book-card" data-request-id="${r.id}">
                        <div class="borrowed-info">
                            <div><strong>${r.library_id}</strong> borrowed the book (<strong>${r.book_title}</strong>)</div>
                            <div class="borrowed-meta">Borrowed on: ${new Date(r.created_at).toLocaleString()}</div>
                        </div>
                        <div class="borrowed-actions">
                            <button class="return-btn" data-request-id="${r.id}" aria-label="Return book">Return Book</button>
                        </div>
                    </div>`;
                    container.insertAdjacentHTML('beforeend', html);
                });
            }
            
            // Attach return handlers
            attachReturnHandlers();
        } catch (err){
            console.error('fetchBorrowedBooks', err);
        }
    }
    
    // Return handler
    function attachReturnHandlers(){
        document.querySelectorAll('.return-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const requestId = btn.getAttribute('data-request-id');
                await handleReturnBook(requestId);
            });
        });
    }
    
    async function handleReturnBook(requestId){
        try{
            const res = await fetch('./return_book.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ request_id: requestId })
            });
            
            if (!res.ok) {
                alert('Failed to return book');
                return;
            }
            
            const data = await res.json();
            if (data.success) {
                // Refresh the list
                fetchBorrowedBooks();
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
            }
        } catch (err){
            console.error('handleReturnBook', err);
            alert('An error occurred');
        }
    }
    
    // Search functionality
    document.getElementById('borrowedSearch').addEventListener('input', async (e) => {
        const searchTerm = e.target.value.toLowerCase();
        const cards = document.querySelectorAll('.borrowed-book-card');
        
        cards.forEach(card => {
            const text = card.textContent.toLowerCase();
            card.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
    
    // initial load and poll
    fetchBorrowedBooks();
    setInterval(fetchBorrowedBooks, 3000);
    </script>

</body>
</html>
