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
    <title>Manage Users</title>
    <link rel="stylesheet" href="../dashboard-style.css">
    <link rel="stylesheet" href="manage-users-style.css">
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
                <li class="clicked-page"><a href="admin-manage-users.php"><img class="nav-icon" src="../images/calendar.png" alt="Users"> <span class="nav-text">Manage Users</span></a></li>
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
        <div class="content">
            <div class="content-header">
                <h2>Manage Users</h2>
                <button class="add-user-btn" id="addUserBtn"><img src="../images/plus.png" alt=""> Add User</button>
            </div>
            <input class="user-search-bar" type="text" id="searchInput" placeholder="Search user" aria-label="Search users">
            <table id="usersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Library ID</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- User rows will be populated here by JavaScript -->
                </tbody>
            </table>
        </div>
    </div>                      
    
    <!-- Modal for adding new user -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <h2>Add New User</h2>
            <form id="addUserForm">
                <div class="form-group">
                    <label for="userName">Name</label>
                    <input type="text" id="userName" name="userName" required>
                </div>
                <div class="form-group">
                    <label for="userLibraryId">Library ID</label>
                    <input type="text" id="userLibraryId" name="userLibraryId" required>
                </div>
                <div class="form-group">
                    <label for="userPassword">Password</label>
                    <input type="password" id="userPassword" name="userPassword" placeholder="Set a password (optional)">
                </div>
                <div class="form-group">
                    <label for="userRole">Role</label>
                    <select id="userRole" name="userRole" required>
                        <option value="">Select Role</option>
                        <option value="admin">Admin</option>
                        <option value="user">User</option>
                    </select>
                </div>
                <button type="submit" class="submit-btn">Add User</button>
                <button type="button" class="cancel-btn" id="cancelBtn">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Modal for editing user -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" id="editModalClose">&times;</span>
            <h2>Edit User</h2>
            <form id="editUserForm">
                <input type="hidden" id="editUserId" name="editUserId">
                <div class="form-group">
                    <label for="editUserPassword">Change Password</label>
                    <input type="password" id="editUserPassword" name="editUserPassword" placeholder="Leave blank to keep current password">
                </div>
                <button type="submit" class="submit-btn">Update User</button>
                <button type="button" class="cancel-btn" id="editCancelBtn">Cancel</button>
            </form>
        </div>
    </div>
    
    <script src="users.js"></script>
</body>
</html>