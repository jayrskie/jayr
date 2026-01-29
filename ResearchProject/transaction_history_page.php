<?php session_start(); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Transaction History - ICT 3A Library</title>
  <meta name="description" content="View transaction history" />
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
    .back-btn {
      display: inline-block;
      background: var(--accent);
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 6px;
      text-decoration: none;
      margin-bottom: 1.5rem;
      transition: background 0.3s;
    }
    .back-btn:hover {
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

    .export-btn {
      background: #2e7d32;
      color: white;
      padding: 0.6rem 1rem;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 500;
      transition: background 0.3s;
      white-space: nowrap;
      font-size: 0.95rem;
      text-decoration: none;
      display: inline-block;
    }

    .export-btn:hover {
      background: #1b5e20;
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

    .tabs {
      display: flex;
      gap: 1rem;
      margin-bottom: 1.5rem;
      border-bottom: 2px solid #cde;
    }

    .tab-btn {
      background: none;
      border: none;
      padding: 0.75rem 1rem;
      font-size: 0.95rem;
      color: var(--muted);
      cursor: pointer;
      border-bottom: 3px solid transparent;
      transition: all 0.3s;
    }

    .tab-btn.active {
      color: var(--accent);
      border-bottom-color: var(--accent);
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

      .back-btn {
        padding: 0.4rem 0.8rem;
        font-size: 0.85rem;
        margin-bottom: 1rem;
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

      .tabs {
        gap: 0.5rem;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
      }

      .tab-btn {
        padding: 0.6rem 0.8rem;
        font-size: 0.85rem;
        white-space: nowrap;
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

    @media (max-width: 480px) {
      main {
        padding: 1rem 0;
      }

      .container {
        padding: 0 0.75rem;
      }

      h1 {
        font-size: 1.25rem;
      }

      .brand-name {
        display: none;
      }

      .logo {
        width: 32px;
        height: 32px;
      }

      .user-name {
        display: none !important;
      }

      .user-menu {
        display: flex !important;
        align-items: center;
        gap: 0.5rem;
        justify-content: flex-end;
      }

      .logout-btn {
        padding: 0.35rem 0.5rem;
        font-size: 0.7rem;
        display: inline-block !important;
        visibility: visible !important;
      }

      .search-container {
        flex-direction: column;
        gap: 0.5rem;
        margin-bottom: 1rem;
      }

      .search-input {
        width: 100%;
        font-size: 0.85rem;
        padding: 0.5rem 0.75rem;
      }

      .search-info {
        font-size: 0.8rem;
      }

      .welcome {
        display: none;
      }

      .back-btn {
        display: inline-block;
        padding: 0.35rem 0.6rem;
        font-size: 0.75rem;
        margin-bottom: 0.75rem;
      }

      .muted {
        font-size: 0.8rem;
      }

      .tabs {
        gap: 0.25rem;
        margin-bottom: 1rem;
      }

      .tab-btn {
        padding: 0.5rem 0.6rem;
        font-size: 0.7rem;
      }

      .history-item {
        padding: 0.75rem;
        margin-bottom: 0.75rem;
      }

      .history-info h3 {
        font-size: 0.85rem;
      }

      .history-info p {
        font-size: 0.75rem;
        margin: 0.15rem 0;
      }

      .status-badge, .return-status-badge {
        font-size: 0.7rem;
        padding: 0.15rem 0.4rem;
        margin-left: 0.25rem;
      }

      .empty-state {
        padding: 1.5rem;
      }

      .empty-state p {
        font-size: 0.85rem;
      }

      .site-footer {
        padding: 1rem 0;
      }

      .site-footer p {
        font-size: 0.7rem;
      }
    }

    @media (max-width: 320px) {
      .brand {
        gap: 0.25rem;
      }

      .logo {
        width: 28px;
        height: 28px;
      }

      h1 {
        font-size: 1rem;
      }

      .history-item {
        padding: 0.5rem;
      }

      .history-info h3 {
        font-size: 0.75rem;
      }

      .history-info p {
        font-size: 0.65rem;
        margin: 0.1rem 0;
      }

      .tab-btn {
        padding: 0.4rem 0.5rem;
        font-size: 0.65rem;
      }

      .back-btn {
        padding: 0.3rem 0.5rem;
        font-size: 0.65rem;
      }

      .logout-btn {
        padding: 0.25rem 0.4rem;
        font-size: 0.6rem;
        display: inline-block !important;
        visibility: visible !important;
      }

      .user-menu {
        display: flex !important;
        gap: 0.25rem;
      }
    }
  </style>
</head>
<body>
  <header class="site-header">
    <div class="container header-inner">
      <a class="brand" href="admin.php" aria-label="ICT 3A Library home">
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
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
          </div>
        <?php else: ?>
          <a href="login_page.php" style="color: var(--accent); text-decoration: none; font-weight: 500;">Login</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <main>
    <section class="container" style="padding: 2rem 0;">
      <a href="transaction_page.php" class="back-btn">← Back to Transactions</a>
      
      <h1>Transaction History</h1>
      <p class="muted">View all returned books and borrowing history</p>

      <!-- Tabs for different views -->
      <div class="tabs">
        <button class="tab-btn active" onclick="switchTab('requests')">All Requests</button>
        <a href="export_borrow_history.php" class="export-btn" style="margin-left: auto;">📥 Export as CSV</a>
      </div>

      <!-- Search Bar -->
      <div class="search-container">
        <input type="text" id="searchInput" class="search-input" placeholder="Search by book title, user name, or email..." />
        <div class="search-info" id="searchInfo"></div>
      </div>

      <!-- All Requests Tab (Approved & Rejected) -->
      <div id="requestsSection" class="tab-content">
        <div id="requestsList" style="min-height: 200px;">
          <div class="empty-state">
            <p>Loading requests...</p>
          </div>
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
      // Check for new requests every 30 seconds
      setInterval(updatePendingBadge, 30000);
    });

    function switchTab(tabName) {
      // Only one tab now, so just keep it visible
      loadAllRequests();
    }

    function loadAllRequests() {
      fetch('get_complete_borrow_history.php')
        .then(response => response.json())
        .then(data => {
          if (data.success && data.requests && data.requests.length > 0) {
            let html = '';
            // Sort requests by most recent date first
            const sortedRequests = [...data.requests].sort((a, b) => {
              const dateA = new Date(a.return_date || a.borrow_date || a.request_date || 0);
              const dateB = new Date(b.return_date || b.borrow_date || b.request_date || 0);
              return dateB - dateA; // Descending order (most recent first)
            });
            
            sortedRequests.forEach(req => {
              const requestStatus = req.request_status;
              const returnStatus = req.return_status || 'pending';
              
              const requestStatusLabels = {
                'approved': '✓ Approved',
                'rejected': '✗ Rejected'
              };
              const requestStatusColors = {
                'approved': '#155724',
                'rejected': '#721c24'
              };
              
              const returnStatusLabels = {
                'early': '✓ Early',
                'on_time': '✓ On Time',
                'late': '⚠ Late',
                'overdue': '✗ Overdue',
                'pending': '⏳ Pending'
              };
              const returnStatusColors = {
                'early': '#0c5460',
                'on_time': '#155724',
                'late': '#856404',
                'overdue': '#721c24',
                'pending': '#666'
              };
              
              const requestDate = new Date(req.request_date);
              
              html += `
                <div class="history-item">
                  <div class="history-info">
                    <h3>
                      ${escapeHtml(req.book_title)}
                      <span class="status-badge status-${requestStatus}">${requestStatusLabels[requestStatus]}</span>
                      ${requestStatus === 'approved' ? '<span class="return-status-badge return-status-' + returnStatus + '">' + returnStatusLabels[returnStatus] + '</span>' : ''}
                      ${req.is_guest ? '<span style="font-size: 0.75rem; background: #e3f2fd; color: #1976d2; padding: 0.25rem 0.5rem; border-radius: 4px; margin-left: 0.5rem;">GUEST</span>' : ''}
                    </h3>
                    <p><strong>Name:</strong> ${escapeHtml(req.user_name)}</p>
                    <p><strong>Email:</strong> ${req.is_guest ? 'N/A (Guest)' : escapeHtml(req.user_email)}</p>
                    <p><strong>Author:</strong> ${escapeHtml(req.author)}</p>
                    <p><strong>Request Type:</strong> ${req.request_type === 'CCT' ? '📋 CCT' : '🌐 Online'}</p>
                    <p><strong>Request Date:</strong> ${requestDate.toLocaleString()}</p>
                    ${requestStatus === 'approved' ? `
                      <p><strong>Borrow Type:</strong> ${req.borrow_type === 'takehome' ? '📚 Take Home (' + req.borrow_duration + ' days)' : '🏫 Classroom (' + (req.borrow_schedule === 'am' ? 'AM' : 'PM') + ')'}</p>
                      <p><strong>Borrow Date:</strong> ${req.borrow_date ? new Date(req.borrow_date).toLocaleString() : 'N/A'}</p>
                      <p><strong>Due Date:</strong> ${req.due_date ? new Date(req.due_date).toLocaleString() : 'N/A'}</p>
                      ${req.return_date ? '<p><strong>Return Date:</strong> <span style="color: ' + returnStatusColors[returnStatus] + '; font-weight: 600;">' + new Date(req.return_date).toLocaleString() + '</span></p>' : '<p><strong>Status:</strong> <span style="color: #c33; font-weight: 600;">Active</span></p>'}
                    ` : ''}
                  </div>
                </div>
              `;
            });
            document.getElementById('requestsList').innerHTML = html;
          } else {
            document.getElementById('requestsList').innerHTML = '<div class="empty-state"><p>No requests found.</p></div>';
          }
        })
        .catch(error => {
          console.error('Error loading requests:', error);
          document.getElementById('requestsList').innerHTML = '<div class="empty-state"><p style="color: #c33;">Error loading requests.</p></div>';
        });
    }

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    function formatOverdueDuration(totalHours) {
      // Convert total hours to days, hours, and minutes
      const totalMinutes = Math.round(totalHours * 60);
      const days = Math.floor(totalMinutes / (24 * 60));
      const hours = Math.floor((totalMinutes % (24 * 60)) / 60);
      const minutes = totalMinutes % 60;
      
      if (days > 0) {
        // Format as days:hours:minutes
        return `${days}:${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')} days`;
      } else {
        // Format as hours:minutes
        return `${hours}:${String(minutes).padStart(2, '0')} hours`;
      }
    }

    let allRequests = [];

    function filterSearch() {
      const searchTerm = document.getElementById('searchInput').value.toLowerCase();
      const searchInfo = document.getElementById('searchInfo');
      
      if (!searchTerm) {
        searchInfo.textContent = '';
        renderRequests(allRequests);
        return;
      }

      let filteredRequests = allRequests.filter(req => {
        const bookTitle = (req.title || '').toLowerCase();
        const userName = (req.user_name || req.guest_name || '').toLowerCase();
        const userEmail = (req.email || '').toLowerCase();
        const author = (req.author || '').toLowerCase();
        
        return bookTitle.includes(searchTerm) || 
               userName.includes(searchTerm) || 
               userEmail.includes(searchTerm) ||
               author.includes(searchTerm);
      });

      if (filteredRequests.length === 0) {
        searchInfo.textContent = `No results found for "${searchTerm}"`;
        searchInfo.classList.add('no-results');
        document.getElementById('requestsList').innerHTML = 
          '<div class="empty-state"><p>No matching requests found</p></div>';
      } else {
        searchInfo.textContent = `Found ${filteredRequests.length} result${filteredRequests.length !== 1 ? 's' : ''}`;
        searchInfo.classList.remove('no-results');
        renderRequests(filteredRequests);
      }
    }

    function renderRequests(requests) {
      let html = '';
      if (requests.length === 0) {
        html = '<div class="empty-state"><p>No requests found.</p></div>';
      } else {
        requests.forEach(req => {
          const bookTitle = escapeHtml(req.book_title);
          const userName = escapeHtml(req.user_name || req.guest_name);
          const userEmail = req.email ? escapeHtml(req.email) : 'Guest';
          const author = escapeHtml(req.author);
          const returnStatus = req.return_status || 'pending';
          
          // Determine display status: show "Rejected" if rejected, "Borrowed" if borrowed but not returned, otherwise show return status
          const displayStatus = req.request_status === 'rejected' ? 'rejected' : (req.borrow_date && !req.return_date ? 'borrowed' : returnStatus);
          
          const statusText = {
            'approved': '✓ Approved',
            'rejected': '✗ Rejected',
            'pending': '⏳ Pending',
            'borrowed': '📚 Borrowed',
            'early': '⏱️ Early Return',
            'on_time': '✓ On Time',
            'late': '⚠️ Late Return',
            'overdue': '❌ Overdue'
          }[displayStatus] || displayStatus;

          const statusColor = {
            'approved': '#2e7d32',
            'rejected': '#d32f2f',
            'pending': '#f57c00',
            'borrowed': '#1976d2',
            'early': '#1976d2',
            'on_time': '#2e7d32',
            'late': '#f57c00',
            'overdue': '#d32f2f'
          }[displayStatus] || '#666';

          html += `
            <div class="history-item">
              <div class="history-info">
                <h3>
                  ${bookTitle}
                  <span class="status-badge" style="background-color: ${statusColor}33; color: ${statusColor};">${statusText}</span>
                  ${!req.is_guest ? '<span style="font-size: 0.75rem; background: #c8e6c9; color: #2e7d32; padding: 0.25rem 0.5rem; border-radius: 4px; margin-left: 0.5rem;">USER</span>' : '<span style="font-size: 0.75rem; background: #e3f2fd; color: #1976d2; padding: 0.25rem 0.5rem; border-radius: 4px; margin-left: 0.5rem;">GUEST</span>'}
                </h3>
                <p><strong>User:</strong> ${userName}</p>
                <p><strong>Email:</strong> ${userEmail}</p>
                <p><strong>Author:</strong> ${author}</p>
                <p><strong>Request Type:</strong> ${req.request_type === 'CCT' ? '📋 CCT' : '🌐 Online'}</p>
                <p><strong>Requested:</strong> ${new Date(req.request_date).toLocaleString()}</p>
                ${req.borrow_date ? `<p><strong>Borrowed:</strong> ${new Date(req.borrow_date).toLocaleString()}</p>` : ''}
                ${req.borrow_type ? `<p><strong>Borrow Type:</strong> ${req.borrow_type === 'takehome' ? '📚 Take Home (' + (req.borrow_duration || '—') + ' days)' : '🏫 Classroom (' + (req.borrow_schedule === 'am' ? 'AM' : req.borrow_schedule === 'pm' ? 'PM' : '—') + ')'}</p>` : ''}
                ${req.due_date ? `<p><strong>Due:</strong> ${new Date(req.due_date).toLocaleString()}</p>` : ''}
                ${req.return_date ? `<p><strong>Returned:</strong> ${new Date(req.return_date).toLocaleString()}</p>` : ''}
                ${req.return_date && req.overdue_hours !== null && (req.return_status === 'late' || req.return_status === 'overdue') ? `<p><strong style="color: #d32f2f;">Overdue Duration:</strong> <span style="color: #d32f2f; font-weight: 600;">${formatOverdueDuration(req.overdue_hours)}</span></p>` : ''}
              </div>
            </div>
          `;
        });
      }
      document.getElementById('requestsList').innerHTML = html;
    }

    function switchTab(tabName) {
      // Clear search
      document.getElementById('searchInput').value = '';
      document.getElementById('searchInfo').textContent = '';
      
      if (tabName === 'requests') {
        document.getElementById('requestsSection').style.display = 'block';
        renderRequests(allRequests);
      }
    }

    // Load all requests when page loads
    document.addEventListener('DOMContentLoaded', () => {
      fetch('get_complete_borrow_history.php')
        .then(response => response.json())
        .then(data => {
          if (data.success && data.requests) {
            allRequests = data.requests;
          }
          renderRequests(allRequests);
          
          // Add search event listener
          document.getElementById('searchInput').addEventListener('input', filterSearch);
        })
        .catch(error => {
          console.error('Error loading requests:', error);
          document.getElementById('requestsList').innerHTML = '<div class="empty-state"><p style="color: #c33;">Error loading requests.</p></div>';
        });
    });
  </script>
</body>
</html>
