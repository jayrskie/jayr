<?php 
session_start();
require 'connect.php';

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
    .logout-btn {
      background: var(--accent);
      color: #ffffff;
      padding: 0.45rem 0.7rem;
      border-radius: 8px;
      text-decoration: none;
      font-size: 0.95rem;
      transition: background 0.3s;
    }
    .logout-btn:hover {
      background: #1a4d80;
    }

    .nav-link {
      position: relative;
    }

    .notification-badge {
      position: absolute;
      top: -6px;
      right: -6px;
      background: #d32f2f;
      color: white;
      border-radius: 50%;
      width: 18px;
      height: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.65rem;
      font-weight: bold;
      border: 1px solid white;
      animation: pulse 2s infinite;
    }

    .notification-badge.hidden {
      display: none;
    }

    @keyframes pulse {
      0%, 100% {
        transform: scale(1);
      }
      50% {
        transform: scale(1.1);
      }
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
  <header class="site-header">
    <div class="container header-inner">
      <a class="brand" href="#" aria-label="Riverside Library home">
        <svg class="logo" width="40" height="40" viewBox="0 0 24 24" aria-hidden="true">
          <rect x="3" y="4" width="18" height="14" rx="2" fill="#246" />
          <path d="M6 8h12M6 12h8" stroke="#fff" stroke-width="1.2" stroke-linecap="round" />
        </svg>
        <span class="brand-name">ICT 3A Library</span>
      </a>

      <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
        <nav class="center-nav" aria-label="Admin">
          <a class="nav-link" href="catalog_page.php">Catalog</a>
        </nav>
        <nav class="center-nav" aria-label="Admin">
          <a class="nav-link" href="transaction_page.php">Transaction <span class="notification-badge hidden" id="pendingBadge">0</span></a>
        </nav>
        <nav class="center-nav" aria-label="Admin">
          <a class="nav-link" href="transaction_history_page.php">History</a>
        </nav>
      <?php endif; ?>

      <nav class="main-nav" aria-label="Primary">
        <p class="welcome">Welcome!</p>
        <?php if (isset($_SESSION['user_id'])): ?>
          <div class="user-menu">
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <a class="logout-btn" href="logout.php">Logout</a>
          </div>
        <?php else: ?>
          <a class="btn" href="login_page.php">Login</a>
          <a class="btn" href="register_page.php">Register</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>

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