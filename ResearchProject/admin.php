<?php 
session_start();
require 'connect.php';
// Note: Overdue reminders should be triggered via CLI/cron job only, not during web requests

// Fetch statistics
$totalBooks = 0;
$totalBookCopies = 0;
$totalUsers = 0;
$totalBorrowedBooks = 0;
$latestBooks = [];
$recentLogins = [];
$recentlyBorrowedBooks = [];

try {
  // Total Books
  $stmt = $conn->prepare("SELECT COUNT(*) as count FROM books");
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  $totalBooks = $row['count'];

  // Total Book Copies
  $stmt = $conn->prepare("SELECT COUNT(*) as count FROM book_copies");
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  $totalBookCopies = $row['count'];

  // Total Users (including admins)
  $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users");
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  $totalUsers = $row['count'];

  // Total Borrowed Books (currently borrowed, not returned)
  $stmt = $conn->prepare("SELECT COUNT(*) as count FROM borrowed_books WHERE return_date IS NULL");
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  $totalBorrowedBooks = $row['count'];

  // Latest Added Books (last 5)
  $stmt = $conn->prepare("SELECT id, title, author, created_at FROM books ORDER BY created_at DESC LIMIT 5");
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $latestBooks[] = $row;
  }

  // Add last_login and last_logout columns if they don't exist
  $altTableSql = "ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL, ADD COLUMN IF NOT EXISTS last_logout TIMESTAMP NULL";
  @$conn->query($altTableSql);

  // Recent User Logins (last 10 users - ordered by last_login)
  $stmt = $conn->prepare("SELECT id, name, email, last_login, last_logout FROM users WHERE last_login IS NOT NULL ORDER BY last_login DESC LIMIT 10");
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $recentLogins[] = $row;
  }

  // Recently Borrowed Books (last 5)
  $stmt = $conn->prepare("SELECT bb.id, bb.borrow_date, b.title, b.author, u.name as user_name FROM borrowed_books bb JOIN books b ON bb.book_id = b.id JOIN users u ON bb.user_id = u.id ORDER BY bb.borrow_date DESC LIMIT 5");
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $recentlyBorrowedBooks[] = $row;
  }
} catch (Exception $e) {
  error_log("Error fetching statistics: " . $e->getMessage());
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>ICT 3A Library</title>
  <meta name="description" content="Riverside Library — free resources, events, and community programs. Search the catalogue, join events, and become a member." />
  <link rel="stylesheet" href="styles.css" />
  <style>
    .welcome {
      font-weight: 500;
      font-size: 1.1rem;
    }
    
    .user-menu {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      margin-left: auto;
    }

    .user-name {
      font-weight: 500;
      font-size: 1.1rem;
    }

    /* Notification badge styles moved to styles.css */
    
    /* Toast Notification Styles */
    .toast-notification {
      position: fixed;
      top: 2rem;
      right: 2rem;
      background: white;
      padding: 1.5rem;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      max-width: 400px;
      z-index: 9999;
      animation: slideIn 0.3s ease-out;
      border-left: 4px solid #4caf50;
    }

    .toast-notification.error {
      border-left-color: #f44336;
    }

    .toast-notification.warning {
      border-left-color: #ff9800;
    }

    @keyframes slideIn {
      from {
        transform: translateX(450px);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }

    @keyframes slideOut {
      from {
        transform: translateX(0);
        opacity: 1;
      }
      to {
        transform: translateX(450px);
        opacity: 0;
      }
    }

    .toast-notification.removing {
      animation: slideOut 0.3s ease-out forwards;
    }

    .toast-title {
      font-weight: 600;
      margin-bottom: 0.5rem;
      font-size: 1rem;
    }

    .toast-message {
      font-size: 0.95rem;
      color: #666;
      margin: 0;
    }

    .toast-close {
      position: absolute;
      top: 1rem;
      right: 1rem;
      background: none;
      border: none;
      font-size: 1.5rem;
      cursor: pointer;
      color: #999;
      padding: 0;
      width: 24px;
      height: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .toast-close:hover {
      color: #333;
    }
    
    @media (max-width: 880px) {
      .user-menu {
        gap: 0.5rem;
      }
      .user-name {
        font-size: 0.85rem;
      }
      .logout-btn {
        padding: 0.4rem 0.6rem;
        font-size: 0.85rem;
      }
      .toast-notification {
        max-width: 300px;
        right: 1rem;
        top: 1rem;
      }
    }
    
    @media (max-width: 600px) {
      .user-name {
        display: none;
      }
      .logout-btn {
        padding: 0.4rem 0.6rem;
        font-size: 0.8rem;
      }
      .toast-notification {
        max-width: calc(100vw - 2rem);
        right: 1rem;
        left: 1rem;
      }
    }

    .stats-section {
      margin-top: 2rem;
      padding: 0 1rem;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.5rem;
      margin-top: 1.5rem;
    }

    .dashboard-section {
      margin-top: 2rem;
      padding: 0 1rem;
    }

    .section-title {
      color: var(--text);
      font-size: 1.3rem;
      font-weight: 600;
      margin-bottom: 1rem;
    }

    .table-container {
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      max-width: 100%;
    }

    .dashboard-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.95rem;
    }

    .dashboard-table thead {
      background: #f5f5f5;
      border-bottom: 2px solid #ddd;
    }

    .dashboard-table th {
      padding: 1rem;
      text-align: left;
      font-weight: 600;
      color: var(--text);
    }

    .dashboard-table td {
      padding: 0.75rem 1rem;
      border-bottom: 1px solid #eee;
    }

    .dashboard-table tbody tr:hover {
      background: #f9f9f9;
    }

    .dashboard-table tbody tr:last-child td {
      border-bottom: none;
    }

    .timestamp {
      color: #999;
      font-size: 0.85rem;
    }

    .two-column-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 2rem;
      margin-top: 2rem;
    }

    @media (max-width: 1024px) {
      .two-column-grid {
        grid-template-columns: 1fr;
      }
    }

    .stat-card {
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      padding: 1.5rem;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      text-align: center;
      transition: transform 0.3s, box-shadow 0.3s;
    }

    .stat-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
    }

    .stat-card.books {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .stat-card.copies {
      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .stat-card.users {
      background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    .stat-card.borrowed {
      background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    }

    .stat-value {
      font-size: 2.5rem;
      font-weight: bold;
      color: white;
      margin: 0.5rem 0;
    }

    .stat-label {
      font-size: 0.95rem;
      color: rgba(255, 255, 255, 0.9);
      font-weight: 500;
    }

    @media (max-width: 768px) {
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
      }

      .stat-card {
        padding: 1rem;
      }

      .stat-value {
        font-size: 2rem;
      }

      .stat-label {
        font-size: 0.9rem;
      }
    }
  </style>
</head>
<body>
  <?php include 'header_template.php'; ?>

  <script>
    // Show toast notification
    function showToast(title, message, type = 'success', duration = 5000) {
      const container = document.querySelector('.toast-container') || (() => {
        const div = document.createElement('div');
        div.className = 'toast-container';
        document.body.appendChild(div);
        return div;
      })();

      const toast = document.createElement('div');
      toast.className = `toast-notification ${type}`;
      toast.innerHTML = `
        <div class="toast-title">${title}</div>
        <p class="toast-message">${message}</p>
        <button class="toast-close" aria-label="Close notification">×</button>
      `;

      container.appendChild(toast);

      const closeBtn = toast.querySelector('.toast-close');
      const removeToast = () => {
        toast.classList.add('removing');
        setTimeout(() => toast.remove(), 300);
      };

      closeBtn.addEventListener('click', removeToast);
      
      if (duration > 0) {
        setTimeout(removeToast, duration);
      }
    }

    // Fetch and update pending requests count
    function updatePendingBadge() {
      fetch('get_pending_count.php')
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const badge = document.getElementById('pendingBadge');
            const count = data.pending_count;
            
            if (count > 0) {
              badge.textContent = count;
              badge.classList.remove('hidden');
            } else {
              badge.classList.add('hidden');
            }
          }
        })
        .catch(error => console.error('Error fetching pending count:', error));
    }

    // Update badge on page load
    document.addEventListener('DOMContentLoaded', () => {
      updatePendingBadge();
    });

    // Check for new requests every 30 seconds
    setInterval(updatePendingBadge, 30000);
  </script>

  <main>
    <!-- Admin Dashboard -->
    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
      <section class="dashboard">
        <div class="container">
          <!-- Dashboard Header -->
          <div style="margin-top: 2rem; padding: 0 1rem;">
            <h1 style="color: var(--text); margin-bottom: 0.5rem;">Admin Dashboard</h1>
            <p style="color: var(--muted); margin-bottom: 1.5rem;">Welcome! Here's an overview of your library.</p>
          </div>

          <!-- Statistics Section -->
          <div class="stats-section">
            <h2 style="color: var(--text); margin-bottom: 0.5rem;">Dashboard Statistics</h2>
            <div class="stats-grid">
              <div class="stat-card books">
                <div class="stat-label">Total Books</div>
                <div class="stat-value"><?php echo $totalBooks; ?></div>
              </div>
              <div class="stat-card copies">
                <div class="stat-label">Total Book Copies</div>
                <div class="stat-value"><?php echo $totalBookCopies; ?></div>
              </div>
              <div class="stat-card users">
                <div class="stat-label">Total Users</div>
                <div class="stat-value"><?php echo $totalUsers; ?></div>
              </div>
              <div class="stat-card borrowed">
                <div class="stat-label">Currently Borrowed</div>
                <div class="stat-value"><?php echo $totalBorrowedBooks; ?></div>
              </div>
            </div>
          </div>
          <!-- Recently Borrowed Books Section -->
          <div class="dashboard-section">
            <h2 class="section-title">Recently Borrowed Books</h2>
            <div class="table-container">
              <table class="dashboard-table">
                <thead>
                  <tr>
                    <th>Book</th>
                    <th>Date Borrowed</th>
                    <th>Borrowed By</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($recentlyBorrowedBooks)): ?>
                    <?php foreach ($recentlyBorrowedBooks as $borrow): ?>
                      <tr>
                        <td><strong><?php echo htmlspecialchars($borrow['title']); ?></strong> by <?php echo htmlspecialchars($borrow['author']); ?></td>
                        <td><span class="timestamp"><?php echo date('M d, Y H:i', strtotime($borrow['borrow_date'])); ?></span></td>
                        <td><?php echo htmlspecialchars($borrow['user_name']); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="3" style="text-align: center; color: var(--muted);">No borrowed books yet</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
          <!-- Two Column Layout for Latest Books and Recent Logins -->
          <div class="two-column-grid">
            <!-- Latest Added Books Section -->
            <div class="dashboard-section">
              <h2 class="section-title">Latest Added Books</h2>
              <div class="table-container">
                <table class="dashboard-table">
                  <thead>
                    <tr>
                      <th>Book</th>
                      <th>Date Added</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!empty($latestBooks)): ?>
                      <?php foreach ($latestBooks as $book): ?>
                        <tr>
                          <td><strong><?php echo htmlspecialchars($book['title']); ?></strong>  by <?php echo htmlspecialchars($book['author']); ?></td>
                          <td><span class="timestamp"><?php echo date('M d, Y H:i', strtotime($book['created_at'])); ?></span></td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="3" style="text-align: center; color: var(--muted);">No books added yet</td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Recent User Logins Section -->
            <div class="dashboard-section">
              <h2 class="section-title">Recent User Activity</h2>
              <div class="table-container">
                <table class="dashboard-table">
                  <thead>
                    <tr>
                      <th>Name</th>
                      <th>Last Login</th>
                      <th>Last Logout</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!empty($recentLogins)): ?>
                      <?php foreach ($recentLogins as $user): ?>
                        <tr>
                          <td><strong><?php echo htmlspecialchars($user['name']); ?></strong></td>
                          <td><span class="timestamp"><?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'N/A'; ?></span></td>
                          <td><span class="timestamp"><?php echo $user['last_logout'] ? date('M d, Y H:i', strtotime($user['last_logout'])) : 'Still logged in'; ?></span></td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="3" style="text-align: center; color: var(--muted);">No user activity yet</td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </section>
    <?php else: ?>
      <!-- Non-Admin Hero Section -->
      <section class="hero">
        <div class="container hero-grid">
          <div class="hero-content">
            <h1>Explore. Learn. Connect.</h1>
            <p class="lede">Discover books and resources at ICT 3A Library</p>
          </div>
          <div class="hero-visual" aria-hidden="true">
            <svg viewBox="0 0 200 140" width="100%" height="100%" class="illustration">
              <rect x="6" y="10" width="64" height="110" rx="6" fill="#f4f9fb" stroke="#cde" />
              <rect x="78" y="22" width="64" height="98" rx="6" fill="#fff7ea" stroke="#f0d" />
              <rect x="150" y="30" width="40" height="88" rx="6" fill="#eef9f2" stroke="#bfe" />
            </svg>
          </div>
        </div>
      </section>
    <?php endif; ?>
  </main>

  <footer class="site-footer">
    <div class="container footer-inner">
      <p>© ICT 3A Library • <a href="privacy_page.php">Privacy</a> • <a href="accessibility_page.php">Accessibility</a></p>
    </div>
  </footer>
</body>
</html>