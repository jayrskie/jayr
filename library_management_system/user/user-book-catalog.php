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
    <title>Book Catalog</title>
    <link rel="stylesheet" href="../dashboard-style.css">
    <link rel="stylesheet" href="book-catalog-style.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Logo</h2>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="user-dashboard.php"><img class="nav-icon" src="../images/house.png" alt="Overview"> <span class="nav-text">Dashboard</span></a></li>
                <li class="clicked-page"><a href="user-book-catalog.php"><img class="nav-icon" src="../images/books.png" alt="Books"> <span class="nav-text">View Catalog</span></a></li>
                <li><a href="user-transaction-history.php"><img class="nav-icon" src="../images/calendar.png" alt="Borrow"> <span class="nav-text">Transaction History</span></a></li>
                <li><a href="user-due-dates.php"><img class="nav-icon" src="../images/return.png" alt="Return"> <span class="nav-text">Check Due Dates</span></a></li>
                <li><a href="user-feedback.php"><img class="nav-icon" src="../images/people.png" alt="Users"> <span class="nav-text">Give Feedback</span></a></li>
                <li><a href="../logout.php" class="logout-btn"><img class="nav-icon" src="../images/logout.png" alt="Logout"> <span class="nav-text">Logout</span></a></li>
            </ul>
        </nav>
    </div>
    <div class="main-content">
        <div class="header">
            <span class="welcome-text">Welcome! <?php echo $displayName; ?></span>
        </div>
        
        <!-- Borrow Notification -->
        <div id="borrowNotification" class="borrow-notification" style="display: none;">
            Go to school library and ask librarian your borrow request in order to borrow the book.
        </div>
        
        <main id="mainCatalog">
            <!-- Interaction header box -->
            <div class="catalog-header">
                <div class="search-filter-row">
                    <div class="filter-dropdown">
                        <button class="filter-btn" id="filterBtn" type="button">
                            <img src="../images/filter.png" alt="Filter">
                        </button>
                        <div class="dropdown-menu" id="filterDropdown">
                            <a href="#" class="dropdown-item" data-filter="available">Show Available Books</a>
                            <a href="#" class="dropdown-item" data-filter="all">Show All Books</a>
                        </div>
                    </div>
                    <div class="search-field-dropdown">
                        <button class="search-field-btn" id="searchFieldBtn" type="button">All Fields</button>
                        <div class="search-field-menu" id="searchFieldMenu">
                            <a href="#" class="search-field-item" data-field="all">All Fields</a>
                            <a href="#" class="search-field-item" data-field="title">Title</a>
                            <a href="#" class="search-field-item" data-field="author">Author</a>
                            <a href="#" class="search-field-item" data-field="isbn">ISBN</a>
                            <a href="#" class="search-field-item" data-field="category">Category</a>
                        </div>
                    </div>
                    <input class="book-search-bar" id="bookSearch" name="bookSearch" type="text" placeholder="Search book" aria-label="Search books">
                </div>
            </div>
            
            <section class="book-catalog">
                <div id="booksContainer"></div>
            </section>
        </main>

        <!-- Borrow Duration Modal -->
        <div id="borrowModal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="close" id="closeModal">&times;</span>
                <h2>Borrow Book</h2>
                <p id="modalBookTitle"></p>
                <form id="borrowForm">
                    <label for="borrowDays">Borrow Duration (days):</label>
                    <input type="number" id="borrowDays" name="borrowDays" min="1" max="30" value="7" required>
                    <button type="submit" class="borrow-submit-btn">Request Borrow</button>
                </form>
            </div>
        </div>

        <script src="book-catalog.js"></script>
    </div>
</body>
</html>