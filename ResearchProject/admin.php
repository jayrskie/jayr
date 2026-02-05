<?php 
session_start();
require 'connect.php';
// Trigger overdue reminders once per day when an admin loads this page
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
  $triggerFile = __DIR__ . DIRECTORY_SEPARATOR . 'trigger_overdue_reminders.php';
  if (file_exists($triggerFile)) {
    // include the trigger (it will run only once per day)
    include $triggerFile;
  }
}

// Fetch statistics
$totalBooks = 0;
$totalBookCopies = 0;
$totalUsers = 0;
$totalBorrowedBooks = 0;

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
    }
    
    @media (max-width: 600px) {
      .user-name {
        display: none;
      }
      .logout-btn {
        padding: 0.4rem 0.6rem;
        font-size: 0.8rem;
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
    document.addEventListener('DOMContentLoaded', updatePendingBadge);

    // Check for new requests every 30 seconds
    setInterval(updatePendingBadge, 30000);
  </script>

  <main>
    <section class="hero">
      <div class="container hero-grid">
        <div class="hero-content">
          <h1>Explore. Learn. Connect.</h1>
          <p class="lede">Discover books!</p>

          <form class="search-form" role="search" aria-label="Search the library catalogue">
            <label for="q" class="sr-only">Search catalogue</label>
            <input id="q" name="q" type="search" placeholder="Search titles, authors, subjects..." />
            <button type="submit" class="search-btn">Search</button>
          </form>

          <!-- Stats Section -->
          <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
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
          <?php endif; ?>
        </div>

        <div class="hero-visual" aria-hidden="true">
          <!-- simple decorative illustration -->
          <svg viewBox="0 0 200 140" width="100%" height="100%" class="illustration">
            <rect x="6" y="10" width="64" height="110" rx="6" fill="#f4f9fb" stroke="#cde" />
            <rect x="78" y="22" width="64" height="98" rx="6" fill="#fff7ea" stroke="#f0d" />
            <rect x="150" y="30" width="40" height="88" rx="6" fill="#eef9f2" stroke="#bfe" />
          </svg>
        </div>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="container footer-inner">
      <p>© ICT 3A Library • <a href="privacy_page.php">Privacy</a> • <a href="accessibility_page.php">Accessibility</a></p>
    </div>
  </footer>
</body>
</html>