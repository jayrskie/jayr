<?php 
session_start();
require_once 'connect.php';

// Check if user is logged in and verified
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'user') {
    header('Location: index.php');
    exit();
}

// Check if user is verified from database
$verifyQuery = 'SELECT is_verified FROM users WHERE id = ?';
$verifyStmt = $conn->prepare($verifyQuery);
if ($verifyStmt) {
    $verifyStmt->bind_param('i', $_SESSION['user_id']);
    $verifyStmt->execute();
    $userResult = $verifyStmt->get_result()->fetch_assoc();
    
    if (!$userResult || !$userResult['is_verified']) {
        $_SESSION['error'] = 'You must be verified to access borrow history';
        header('Location: index.php');
        exit();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>My Borrow History - ICT 3A Library</title>
  <meta name="description" content="View your borrow history" />
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
      flex-wrap: wrap;
      justify-content: flex-end;
    }
    .user-name {
      font-weight: 500;
      font-size: 1.1rem;
    }
    .logout-btn {
      background: var(--accent);
      color: white;
      padding: 0.45rem 0.7rem;
      border-radius: 8px;
      text-decoration: none;
      font-size: 0.95rem;
      transition: background 0.3s;
      white-space: nowrap;
    }
    .logout-btn:hover {
      background: #1a4d80;
    }

    .nav-link {
      position: relative;
    }

    .history-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem;
      border: 1px solid #cde;
      border-radius: 8px;
      margin-bottom: 1rem;
      background: #f9fafb;
    }

    .history-info {
      flex: 1;
    }

    .history-info h3 {
      margin: 0 0 0.5rem 0;
      color: var(--accent);
    }

    .history-info p {
      margin: 0.25rem 0;
      font-size: 0.95rem;
      color: var(--muted);
    }

    .status-badge {
      display: inline-block;
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 500;
      margin-left: 0.5rem;
    }

    .status-pending {
      background: #fff3cd;
      color: #856404;
    }

    .status-borrowed {
      background: #cfe9f3;
      color: #0c5460;
    }

    .return-status-badge {
      display: inline-block;
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 500;
      margin-left: 0.5rem;
    }

    .return-status-early {
      background: #cfe9f3;
      color: #0c5460;
    }

    .return-status-on-time {
      background: #d4edda;
      color: #155724;
    }

    .return-status-late {
      background: #fff3cd;
      color: #856404;
    }

    .return-status-overdue {
      background: #f8d7da;
      color: #721c24;
    }

    .empty-state {
      text-align: center;
      padding: 2rem;
      color: var(--muted);
    }

    .search-container {
      display: flex;
      gap: 1rem;
      margin-bottom: 1.5rem;
      align-items: center;
    }

    .search-input {
      flex: 1;
      padding: 0.6rem 1rem;
      border: 1px solid #cde;
      border-radius: 6px;
      font-size: 0.95rem;
      transition: border-color 0.3s;
    }

    .search-input:focus {
      outline: none;
      border-color: var(--accent);
      box-shadow: 0 0 0 2px rgba(0, 102, 204, 0.1);
    }

    .search-info {
      color: var(--muted);
      font-size: 0.9rem;
      margin-top: 0.5rem;
    }

    .search-info.no-results {
      color: #d32f2f;
      font-weight: 500;
    }

    @media (max-width: 1024px) {
      .history-item {
        flex-direction: column;
        align-items: flex-start;
      }
    }

    @media (max-width: 768px) {
      h1 {
        font-size: 1.75rem;
      }

      .user-menu {
        gap: 0.5rem;
      }

      .user-name {
        font-size: 0.8rem;
      }

      .logout-btn {
        padding: 0.4rem 0.6rem;
        font-size: 0.75rem;
      }

      .search-container {
        flex-direction: column;
        gap: 0.75rem;
      }

      .search-input {
        width: 100%;
      }

      .history-item {
        padding: 0.75rem;
      }

      .history-info h3 {
        font-size: 0.95rem;
      }

      .history-info p {
        font-size: 0.85rem;
      }

      .status-badge, .return-status-badge {
        font-size: 0.75rem;
        padding: 0.2rem 0.5rem;
      }
    }
  </style>
</head>
<body>
  <header class="site-header">
    <div class="container header-inner">
      <a class="brand" href="index.php" aria-label="ICT 3A Library home">
        <svg class="logo" width="40" height="40" viewBox="0 0 24 24" aria-hidden="true">
          <rect x="3" y="4" width="18" height="14" rx="2" fill="#246" />
          <path d="M6 8h12M6 12h8" stroke="#fff" stroke-width="1.2" stroke-linecap="round" />
        </svg>
        <span class="brand-name">ICT 3A Library</span>
      </a>

      <?php if (isset($_SESSION['user_id'])): ?>
        <nav class="center-nav" aria-label="User">
          <a class="nav-link" href="index.php">Catalog</a>
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
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <main>
    <section class="container" style="padding: 2rem 0;">
      <h1>My Borrow History</h1>
      <p class="muted">View all your book borrowing records</p>

      <!-- Search Bar -->
      <div class="search-container">
        <input type="text" id="searchInput" class="search-input" placeholder="Search by book title or author..." />
        <div class="search-info" id="searchInfo"></div>
      </div>

      <div id="historyList" style="min-height: 200px;">
        <div class="empty-state">
          <p>Loading your borrow history...</p>
        </div>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="container footer-inner">
      <p>© ICT 3A Library • <a href="privacy_page.php">Privacy</a> • <a href="accessibility_page.php">Accessibility</a></p>
    </div>
  </footer>

  <script>
    let allBorrowHistory = [];

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    function formatOverdueDuration(totalHours) {
      const totalMinutes = Math.round(totalHours * 60);
      const days = Math.floor(totalMinutes / (24 * 60));
      const hours = Math.floor((totalMinutes % (24 * 60)) / 60);
      const minutes = totalMinutes % 60;
      
      if (days > 0) {
        return `${days}:${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')} days`;
      } else {
        return `${hours}:${String(minutes).padStart(2, '0')} hours`;
      }
    }

    function filterSearch() {
      const searchTerm = document.getElementById('searchInput').value.toLowerCase();
      const searchInfo = document.getElementById('searchInfo');
      
      if (!searchTerm) {
        searchInfo.textContent = '';
        renderHistory(allBorrowHistory);
        return;
      }

      let filteredItems = allBorrowHistory.filter(item => {
        const bookTitle = (item.book_title || '').toLowerCase();
        const author = (item.author || '').toLowerCase();
        
        return bookTitle.includes(searchTerm) || author.includes(searchTerm);
      });

      if (filteredItems.length === 0) {
        searchInfo.textContent = `No results found for "${searchTerm}"`;
        searchInfo.classList.add('no-results');
        document.getElementById('historyList').innerHTML = 
          '<div class="empty-state"><p>No matching records found</p></div>';
      } else {
        searchInfo.textContent = `Found ${filteredItems.length} result${filteredItems.length !== 1 ? 's' : ''}`;
        searchInfo.classList.remove('no-results');
        renderHistory(filteredItems);
      }
    }

    function renderHistory(items) {
      let html = '';
      if (items.length === 0) {
        html = '<div class="empty-state"><p>No borrow history found.</p></div>';
      } else {
        // Sort items by most recent date first
        const sortedItems = [...items].sort((a, b) => {
          const dateA = new Date(a.return_date || a.borrow_date || a.request_date || 0);
          const dateB = new Date(b.return_date || b.borrow_date || b.request_date || 0);
          return dateB - dateA; // Descending order (most recent first)
        });
        
        sortedItems.forEach(item => {
          const bookTitle = escapeHtml(item.book_title);
          const author = escapeHtml(item.author);
          const returnStatus = item.return_status || 'pending';
          
          const statusText = {
            'pending': '⏳ Pending',
            'early': '✓ Early Return',
            'on_time': '✓ On Time',
            'late': '⚠️ Late Return',
            'overdue': '❌ Overdue'
          }[returnStatus] || returnStatus;

          const statusColor = {
            'pending': '#f57c00',
            'early': '#1976d2',
            'on_time': '#2e7d32',
            'late': '#f57c00',
            'overdue': '#d32f2f'
          }[returnStatus] || '#666';

          html += `
            <div class="history-item">
              <div class="history-info">
                <h3>
                  ${bookTitle}
                  <span class="status-badge" style="background-color: ${statusColor}33; color: ${statusColor};">${statusText}</span>
                </h3>
                <p><strong>Author:</strong> ${author}</p>
                <p><strong>Borrow Type:</strong> ${item.borrow_type === 'takehome' ? '📚 Take Home (' + (item.borrow_duration || '—') + ' days)' : '🏫 Classroom (' + (item.borrow_schedule === 'am' ? 'AM' : 'PM') + ')'}</p>
                <p><strong>Borrowed:</strong> ${item.borrow_date ? new Date(item.borrow_date).toLocaleString() : 'N/A'}</p>
                <p><strong>Due:</strong> ${item.due_date ? new Date(item.due_date).toLocaleString() : 'N/A'}</p>
                ${item.return_date ? `<p><strong>Returned:</strong> ${new Date(item.return_date).toLocaleString()}</p>` : '<p><strong>Status:</strong> <span style="color: #f57c00; font-weight: 600;">Still Borrowed</span></p>'}
                ${item.return_date && item.overdue_hours !== null && (item.return_status === 'late' || item.return_status === 'overdue') ? `<p><strong style="color: #d32f2f;">Overdue Duration:</strong> <span style="color: #d32f2f; font-weight: 600;">${formatOverdueDuration(item.overdue_hours)}</span></p>` : ''}
              </div>
            </div>
          `;
        });
      }
      document.getElementById('historyList').innerHTML = html;
    }

    // Load borrow history when page loads
    document.addEventListener('DOMContentLoaded', () => {
      fetch('get_user_borrow_history.php')
        .then(response => response.json())
        .then(data => {
          if (data.success && data.history) {
            allBorrowHistory = data.history;
          }
          renderHistory(allBorrowHistory);
          
          // Add search event listener
          document.getElementById('searchInput').addEventListener('input', filterSearch);
        })
        .catch(error => {
          console.error('Error loading history:', error);
          document.getElementById('historyList').innerHTML = '<div class="empty-state"><p style="color: #c33;">Error loading your borrow history.</p></div>';
        });
    });
  </script>
</body>
</html>
