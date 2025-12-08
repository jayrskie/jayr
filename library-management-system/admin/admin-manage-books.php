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
    <title>Manage Books</title>
    <link rel="stylesheet" href="../dashboard-style.css">
    <link rel="stylesheet" href="manage-book-style.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Logo</h2>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="admin-dashboard.php"><img class="nav-icon" src="../images/house.png" alt="Dashboard"> <span class="nav-text">Dashboard</span></a></li>
                <li class="clicked-page"><a href="admin-manage-books.php"><img class="nav-icon" src="../images/books.png" alt="Books"> <span class="nav-text">Manage Books</span></a></li>
                <li><a href="admin-manage-users.php"><img class="nav-icon" src="../images/calendar.png" alt="Users"> <span class="nav-text">Manage Users</span></a></li>
                <li><a href="admin-view-transactions.php"><img class="nav-icon" src="../images/return.png" alt="Return"> <span class="nav-text">View Transactions</span></a></li>
                <li><a href="admin-view-feedback.php"><img class="nav-icon" src="../images/people.png" alt="Users"> <span class="nav-text">View Feedback</span></a></li>
                <li><a href="../logout.php" class="logout-btn"><img class="nav-icon" src="../images/logout.png" alt="Logout"> <span class="nav-text">Logout</span></a></li>
            </ul>
        </nav>
    </div>
    <div class="main-content">
        <div class="header">
            <span class="welcome-text">Welcome, <?php echo $displayName; ?></span>
        </div>
        <div class="middle-area">
            <h2 class="manage-book">Manage Books</h2>
            <button class="add-book-btn"><img src="../images/plus.png" alt="">New Book</button>
        </div>
        <div class="lower-area">
            <div class="search-filter-row">
                <div class="filter-dropdown">
                    <button class="filter-btn" id="filterBtn" type="button"><img src="../images/filter.png" alt="Filter"></button>
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
                <input class="manage-book-search-bar" id="manageBookSearch" name="manageBookSearch" type="text" placeholder="Search book" aria-label="Search books">
            </div>
            <div id="booksContainer" class="books-container">
                <!-- Books will be displayed here -->
            </div>
        </div>
    </div>

    <!-- Modal for adding new book -->
    <div id="addBookModal" class="modal">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <h2>Add New Book</h2>
            <form id="addBookForm">
                <div class="form-group">
                    <label for="bookTitle">Book Title</label>
                    <input type="text" id="bookTitle" name="bookTitle" required>
                </div>
                <div class="form-group">
                    <label for="bookAuthor">Author</label>
                    <input type="text" id="bookAuthor" name="bookAuthor" required>
                </div>
                <div class="form-group">
                    <label for="bookISBN">ISBN</label>
                    <input type="text" id="bookISBN" name="bookISBN" required>
                </div>
                <div class="form-group">
                    <label for="bookQuantity">Quantity</label>
                    <input type="number" id="bookQuantity" name="bookQuantity" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="bookCategory">Category</label>
                        <input type="text" id="bookCategory" name="bookCategory" required>
                    </div>
                    <div class="form-group">
                        <label for="bookAvailable">Available</label>
                        <input type="number" id="bookAvailable" name="bookAvailable" min="0" required>
                    </div>
                </div>
                <button type="submit" class="submit-btn">Add Book</button>
                <button type="button" class="cancel-btn" id="cancelBtn">Cancel</button>
            </form>
        </div>
    </div>

    <script src="add-new-book.js"></script>
</body>
</html>