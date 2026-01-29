<?php session_start(); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Borrow Requests - ICT 3A Library</title>
  <meta name="description" content="Manage borrow requests" />
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

    .request-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem;
      border: 1px solid #cde;
      border-radius: 8px;
      margin-bottom: 1rem;
      background: #f9fafb;
    }

    .request-info {
      flex: 1;
    }

    .request-info h3 {
      margin: 0 0 0.5rem 0;
      color: var(--accent);
    }

    .request-info p {
      margin: 0.25rem 0;
      font-size: 0.95rem;
      color: var(--muted);
    }

    .request-actions {
      display: flex;
      gap: 0.75rem;
    }

    .btn-approve {
      background: #2e7d32;
      color: white;
      padding: 0.5rem 1rem;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 500;
      transition: background 0.3s;
    }

    .btn-approve:hover {
      background: #1b5e20;
    }

    .btn-configure {
      background: #2e7d32;
      color: white;
      padding: 0.5rem 1rem;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 500;
      transition: background 0.3s;
    }

    .btn-configure:hover {
      background: #1b5e20;
    }

    .btn-return {
      background: #1976d2;
      color: white;
      padding: 0.5rem 1rem;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 500;
      transition: background 0.3s;
    }

    .btn-return:hover {
      background: #1565c0;
    }

    .return-success {
      color: #2e7d32;
      font-weight: 500;
    }

    .btn-reject {
      background: #c33;
      color: white;
      padding: 0.5rem 1rem;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 500;
      transition: background 0.3s;
    }

    .btn-reject:hover {
      background: #a00;
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

    .status-approved {
      background: #d4edda;
      color: #155724;
    }

    .status-rejected {
      background: #f8d7da;
      color: #721c24;
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

    .circulation-transaction-box {
      background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
      border: 2px solid var(--accent);
      border-radius: 10px;
      padding: 1.5rem;
      margin-bottom: 2rem;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .circulation-transaction-box h3 {
      margin: 0 0 1rem 0;
      color: var(--accent);
      font-size: 1.1rem;
    }

    .circulation-form {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
    }

    .form-group {
      display: flex;
      flex-direction: column;
    }

    .form-group label {
      font-weight: 500;
      color: var(--text);
      margin-bottom: 0.4rem;
      font-size: 0.9rem;
    }

    .form-group input,
    .form-group select {
      padding: 0.6rem 0.8rem;
      border: 1px solid #cde;
      border-radius: 6px;
      font-size: 0.95rem;
      transition: border-color 0.3s;
    }

    .form-group input:focus,
    .form-group select:focus {
      outline: none;
      border-color: var(--accent);
      box-shadow: 0 0 0 2px rgba(0, 102, 204, 0.1);
    }

    .circulation-form-actions {
      grid-column: 1 / -1;
      display: flex;
      gap: 1rem;
      margin-top: 0.5rem;
    }

    .btn-lend-direct {
      background: #2e7d32;
      color: white;
      padding: 0.65rem 1.5rem;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 500;
      transition: background 0.3s;
      flex: 1;
    }

    .btn-lend-direct:hover {
      background: #1b5e20;
    }

    .btn-cancel-lend {
      background: #f0f0f0;
      color: #333;
      padding: 0.65rem 1.5rem;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 500;
      transition: background 0.3s;
    }

    .btn-cancel-lend:hover {
      background: #e0e0e0;
    }

    @media (max-width: 1024px) {
      .request-item {
        flex-direction: column;
        align-items: flex-start;
      }

      .request-actions {
        width: 100%;
        margin-top: 1rem;
        flex-wrap: wrap;
      }

      .request-actions button {
        flex: 1;
        min-width: 120px;
      }
    }

    @media (max-width: 768px) {
      h1 {
        font-size: 1.75rem;
      }

      h2 {
        font-size: 1.5rem;
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

      .request-info p {
        font-size: 0.85rem;
      }

      .request-info h3 {
        font-size: 0.95rem;
      }

      .status-badge, .return-status-badge {
        font-size: 0.75rem;
        padding: 0.2rem 0.5rem;
      }

      .option-buttons {
        flex-direction: column;
      }

      .option-btn {
        padding: 0.6rem;
        font-size: 0.85rem;
      }

      .modal {
        width: 95% !important;
        padding: 1.25rem !important;
      }

      .days-input {
        flex-direction: column;
        gap: 0.25rem;
      }

      .days-input input {
        width: 100%;
      }

      .search-container {
        flex-direction: column;
        gap: 0.75rem;
      }

      .search-input {
        width: 100%;
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

      h2 {
        font-size: 1rem;
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

      .welcome {
        display: none;
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

      .request-item {
        padding: 0.75rem;
        margin-bottom: 0.75rem;
      }

      .request-info h3 {
        font-size: 0.85rem;
      }

      .request-info p {
        font-size: 0.75rem;
        margin: 0.15rem 0;
      }

      .request-actions {
        gap: 0.5rem;
      }

      .request-actions button {
        padding: 0.35rem 0.75rem;
        font-size: 0.7rem;
      }

      .btn-approve, .btn-reject, .btn-save-config {
        padding: 0.4rem 0.75rem !important;
        font-size: 0.8rem !important;
      }

      .tabs {
        gap: 0.25rem;
        margin-bottom: 1rem;
      }

      .tab-btn {
        padding: 0.5rem 0.6rem;
        font-size: 0.7rem;
      }

      .search-container {
        gap: 0.4rem;
        margin-bottom: 0.75rem;
      }

      .search-input {
        font-size: 0.75rem;
        padding: 0.4rem 0.6rem;
      }

      .search-info {
        font-size: 0.7rem;
        margin-top: 0.3rem;
      }

      .modal {
        width: 95% !important;
        padding: 1rem !important;
      }

      .modal h2 {
        font-size: 0.95rem;
        margin-bottom: 1rem;
      }

      .option-btn {
        padding: 0.5rem;
        font-size: 0.7rem;
      }

      .book-info-card {
        margin-bottom: 1rem;
      }

      .book-info-card h3 {
        font-size: 0.85rem;
      }

      .book-info-card p {
        font-size: 0.7rem;
      }

      .sub-options {
        margin-left: 0.5rem;
        padding: 0.75rem;
      }

      .sub-options label {
        font-size: 0.75rem;
        margin-bottom: 0.5rem;
      }

      .days-input input {
        width: 100%;
        padding: 0.35rem;
        font-size: 0.7rem;
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

      .request-item {
        padding: 0.5rem;
      }

      .request-info h3 {
        font-size: 0.75rem;
      }

      .request-info p {
        font-size: 0.65rem;
      }

      .request-actions button {
        padding: 0.25rem 0.5rem;
        font-size: 0.65rem;
      }

      .tab-btn {
        padding: 0.4rem 0.5rem;
        font-size: 0.65rem;
      }

      .modal {
        padding: 0.75rem !important;
      }

      .option-btn {
        padding: 0.4rem;
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

    /* Modal Styles */
    .modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.5);
      z-index: 9999;
      justify-content: center;
      align-items: center;
    }

    .modal-overlay.active {
      display: flex;
    }

    .modal {
      background: white;
      border-radius: 12px;
      padding: 2rem;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
      animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
      from {
        transform: translateY(-50px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .modal h2 {
      margin: 0 0 1rem 0;
      color: var(--accent);
      font-size: 1.5rem;
    }

    .modal-content {
      margin-bottom: 1.5rem;
    }

    .book-info-card {
      background: #f9fafb;
      padding: 1rem;
      border-radius: 6px;
      margin-bottom: 1.5rem;
      border: 1px solid #cde;
    }

    .book-info-card h3 {
      margin: 0 0 0.5rem 0;
      color: var(--accent);
    }

    .book-info-card p {
      margin: 0.25rem 0;
      font-size: 0.95rem;
      color: var(--muted);
    }

    .option-group {
      margin-bottom: 1.5rem;
    }

    .option-group label {
      display: block;
      font-weight: 600;
      margin-bottom: 0.75rem;
      color: #333;
    }

    .option-buttons {
      display: flex;
      gap: 1rem;
      margin-bottom: 1rem;
    }

    .option-btn {
      flex: 1;
      padding: 0.75rem;
      border: 2px solid #cde;
      border-radius: 6px;
      background: white;
      cursor: pointer;
      font-weight: 500;
      transition: all 0.3s;
    }

    .option-btn.selected {
      border-color: var(--accent);
      background: var(--accent);
      color: white;
    }

    .option-btn:hover {
      border-color: var(--accent);
    }

    .sub-options {
      display: none;
      margin-left: 1rem;
      padding: 1rem;
      background: #f9fafb;
      border-radius: 6px;
      border-left: 3px solid var(--accent);
    }

    .sub-options.visible {
      display: block;
    }

    .sub-options label {
      display: flex;
      align-items: center;
      margin-bottom: 0.75rem;
      font-weight: normal;
    }

    .sub-options input[type="radio"] {
      margin-right: 0.5rem;
      cursor: pointer;
    }

    .days-input {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .days-input input {
      width: 60px;
      padding: 0.5rem;
      border: 1px solid #cde;
      border-radius: 4px;
      font-size: 0.95rem;
    }

    #bookCopySelect {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid #cde;
      border-radius: 4px;
      font-size: 0.95rem;
      background: white;
      cursor: pointer;
    }

    #bookCopySelect:focus {
      outline: none;
      border-color: var(--accent);
      box-shadow: 0 0 4px rgba(34, 70, 102, 0.2);
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
      <h1>Books Circulation</h1>
      <p class="muted">Manage book borrowing, approvals, and returns</p>

      <!-- Tabs for different statuses -->
      <div class="tabs">
        <button class="tab-btn active" onclick="switchTab('pending')">Pending</button>
        <button class="tab-btn" onclick="switchTab('approved')">Approved</button>
        <a href="transaction_history_page.php" style="margin-left: auto; align-self: center; color: var(--accent); text-decoration: none; font-weight: 500;">📋 History →</a>
      </div>

      <!-- Search Bar -->
      <div class="search-container">
        <input type="text" id="searchInput" class="search-input" placeholder="Search by book title, user name, or email..." />
        <div class="search-info" id="searchInfo"></div>
      </div>

      <!-- Collection Circulation Transaction Box -->
      <div class="circulation-transaction-box">
        <h3>📚 Collection Circulation Transaction</h3>
        <p class="muted" style="margin: 0 0 1rem 0; font-size: 0.9rem;">Lend a book directly to a user without requiring a borrow request</p>
        
        <div class="circulation-form">
          <div class="form-group">
            <label>User Type</label>
            <div style="display: flex; gap: 1.5rem;">
              <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: normal; cursor: pointer;">
                <input type="radio" name="circulationUserType" value="registered" onchange="updateUserSelectionDisplay()" style="cursor: pointer;">
                Registered User
              </label>
              <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: normal; cursor: pointer;">
                <input type="radio" name="circulationUserType" value="guest" onchange="updateUserSelectionDisplay()" style="cursor: pointer;">
                Guest
              </label>
            </div>
          </div>

          <div class="form-group" id="registeredUserSearchGroup" style="display: none;">
            <label for="circulationUserInput">User (Library ID)</label>
            <div style="position: relative;">
              <div style="display: flex; gap: 0.5rem;">
                <div style="display: flex; align-items: center; padding: 0.6rem 0.8rem; background: #f0f0f0; border: 1px solid #cde; border-radius: 6px 0 0 6px; font-weight: 500; color: #333;">AU-</div>
                <div style="flex: 1; position: relative;">
                  <input 
                    type="text" 
                    id="circulationUserInput" 
                    placeholder="Enter 12 digits (e.g., 136865140383)" 
                    oninput="searchCirculationUsers()"
                    pattern="[0-9]*"
                    inputmode="numeric"
                    maxlength="12"
                    style="width: 100%; padding: 0.6rem 0.8rem; border: 1px solid #cde; border-radius: 0 6px 0 0; font-size: 0.95rem; transition: border-color 0.3s; box-sizing: border-box;"
                  >
                  <div 
                    id="circulationUserSuggestions" 
                    style="position: absolute; top: 100%; left: 0; right: -56px; background: white; border: 1px solid #cde; border-top: none; border-radius: 0 0 6px 0; max-height: 200px; overflow-y: auto; display: none; z-index: 1000; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"
                  ></div>
                </div>
              </div>
            </div>
          </div>

          <div class="form-group" id="guestNameGroup" style="display: none;">
            <label for="circulationGuestName">Guest Name</label>
            <input type="text" id="circulationGuestName" placeholder="Enter guest name">
          </div>

          <div class="form-group">
            <label for="circulationBookInput">Book (Accession Code)</label>
            <div style="position: relative;">
              <input 
                type="text" 
                id="circulationBookInput" 
                placeholder="Type accession code..." 
                oninput="searchCirculationBooks()"
                style="width: 100%; padding: 0.6rem 0.8rem; border: 1px solid #cde; border-radius: 6px; font-size: 0.95rem; transition: border-color 0.3s;"
              >
              <div 
                id="circulationBookSuggestions" 
                style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #cde; border-top: none; border-radius: 0 0 6px 6px; max-height: 200px; overflow-y: auto; display: none; z-index: 1000; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"
              ></div>
            </div>
          </div>

          <div class="form-group">
            <label>Borrow Type</label>
            <div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
              <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: normal; cursor: pointer;">
                <input type="radio" name="circulationBorrowType" value="takehome" onchange="updateCirculationDurationDisplay()" style="cursor: pointer;">
                📚 Take Home
              </label>
              <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: normal; cursor: pointer;">
                <input type="radio" name="circulationBorrowType" value="classroom" onchange="updateCirculationDurationDisplay()" style="cursor: pointer;">
                🏫 Classroom
              </label>
              <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: normal; cursor: pointer;">
                <input type="radio" name="circulationBorrowType" value="renew" onchange="updateCirculationDurationDisplay()" style="cursor: pointer;">
                🔄 Renew
              </label>
            </div>
          </div>

          <div class="form-group" id="circulationDurationGroup" style="display: none;">
            <label for="circulationDuration">Duration (days)</label>
            <input type="number" id="circulationDuration" placeholder="e.g., 7" min="1">
          </div>

          <div class="form-group" id="circulationScheduleGroup" style="display: none;">
            <label>Schedule</label>
            <div style="display: flex; gap: 1.5rem;">
              <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: normal; cursor: pointer;">
                <input type="radio" name="circulationSchedule" value="am" style="cursor: pointer;">
                AM
              </label>
              <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: normal; cursor: pointer;">
                <input type="radio" name="circulationSchedule" value="pm" style="cursor: pointer;">
                PM
              </label>
            </div>
          </div>

          <div class="circulation-form-actions">
            <button class="btn-lend-direct" onclick="lendBookDirectly()">Lend Book</button>
          </div>
        </div>
      </div>

      <!-- Pending Requests -->
      <div id="pendingSection" class="tab-content">
        <div id="pendingRequests" style="min-height: 200px;">
          <div class="empty-state">
            <p>Loading pending requests...</p>
          </div>
        </div>
      </div>

      <!-- Configure (Borrowed Books) -->
      <div id="approvedSection" class="tab-content" style="display: none;">
        <div id="approvedRequests" style="min-height: 200px;">
          <div class="empty-state">
            <p>Loading borrowed books...</p>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- Configure Borrow Modal -->
  <div class="modal-overlay" id="configureModal">
    <div class="modal" style="max-width: 600px;">
      <h2>Configure Book Borrowing</h2>
      
      <div class="modal-content" id="modalContent">
        <!-- Book info and options will be populated here -->
      </div>

      <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
        <button onclick="closeConfigureModal()" style="background: #f0f0f0; color: #333; padding: 0.65rem 1.5rem; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">Cancel</button>
        <button id="rejectBtnModal" onclick="rejectFromModal()" style="background: #c33; color: white; padding: 0.65rem 1.5rem; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; display: none;">Reject</button>
        <button class="btn-save-config" onclick="saveBorrowConfiguration()" style="background: var(--accent); color: white; padding: 0.65rem 1.5rem; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">Approve</button>
      </div>
    </div>
  </div>

  <!-- Alert Modal -->
  <div id="alertModal" class="modal-overlay">
    <div class="modal" style="max-width: 500px;">
      <h2 id="alertModalTitle">Message</h2>
      <p id="alertModalMessage" style="white-space: pre-line; line-height: 1.6;"></p>
      <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
        <button onclick="closeAlertModal()" style="background: var(--accent); color: white; padding: 0.65rem 1.5rem; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">OK</button>
      </div>
    </div>
  </div>

  <!-- Confirmation Modal -->
  <div id="confirmModal" class="modal-overlay">
    <div class="modal" style="max-width: 500px;">
      <h2 id="confirmModalTitle">Confirm Action</h2>
      <p id="confirmModalMessage" style="white-space: pre-line; line-height: 1.6;"></p>
      <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
        <button onclick="closeConfirmModal()" style="background: #f0f0f0; color: #333; padding: 0.65rem 1.5rem; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">Cancel</button>
        <button id="confirmModalBtn" onclick="handleConfirmAction()" style="background: var(--accent); color: white; padding: 0.65rem 1.5rem; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">Confirm</button>
      </div>
    </div>
  </div>

  <!-- Warning Modal (for no borrow history) -->
  <div id="warningModal" class="modal-overlay">
    <div class="modal" style="max-width: 500px;">
      <h2 id="warningModalTitle" style="color: #ff9800;">⚠️ Warning</h2>
      <p id="warningModalMessage" style="white-space: pre-line; line-height: 1.6;"></p>
      <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
        <button onclick="closeWarningModal()" style="background: #f0f0f0; color: #333; padding: 0.65rem 1.5rem; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">Cancel</button>
        <button id="warningConfirmBtn" onclick="handleWarningConfirm()" style="background: #ff9800; color: white; padding: 0.65rem 1.5rem; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">Continue Anyway</button>
      </div>
    </div>
  </div>

  <footer class="site-footer">
    <div class="container footer-inner">
      <p>© ICT 3A Library • <a href="privacy_page.php">Privacy</a> • <a href="accessibility_page.php">Accessibility</a></p>
    </div>
  </footer>

  <style>
    .modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.5);
      z-index: 9999;
      justify-content: center;
      align-items: center;
    }

    .modal-overlay.active {
      display: flex;
    }

    .modal {
      background: white;
      border-radius: 12px;
      padding: 2rem;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
      animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
      from {
        transform: translateY(-50px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .modal h2 {
      margin: 0 0 1rem 0;
      color: var(--accent);
      font-size: 1.5rem;
    }

    .modal-content {
      margin-bottom: 1.5rem;
    }

    .book-info-card {
      background: #f9fafb;
      padding: 1rem;
      border-radius: 6px;
      margin-bottom: 1.5rem;
      border: 1px solid #cde;
    }

    .book-info-card h3 {
      margin: 0 0 0.5rem 0;
      color: var(--accent);
    }

    .book-info-card p {
      margin: 0.25rem 0;
      font-size: 0.95rem;
      color: var(--muted);
    }

    .option-group {
      margin-bottom: 1.5rem;
    }

    .option-group label {
      display: block;
      font-weight: 600;
      margin-bottom: 0.75rem;
      color: #333;
    }

    .option-buttons {
      display: flex;
      gap: 1rem;
      margin-bottom: 1rem;
    }

    .option-btn {
      flex: 1;
      padding: 0.75rem;
      border: 2px solid #cde;
      border-radius: 6px;
      background: white;
      cursor: pointer;
      font-weight: 500;
      transition: all 0.3s;
    }

    .option-btn.selected {
      border-color: var(--accent);
      background: var(--accent);
      color: white;
    }

    .option-btn:hover {
      border-color: var(--accent);
    }

    .sub-options {
      display: none;
      margin-left: 1rem;
      padding: 1rem;
      background: #f9fafb;
      border-radius: 6px;
      border-left: 3px solid var(--accent);
    }

    .sub-options.visible {
      display: block;
    }

    .sub-options label {
      display: flex;
      align-items: center;
      margin-bottom: 0.75rem;
      font-weight: normal;
    }

    .sub-options input[type="radio"] {
      margin-right: 0.5rem;
      cursor: pointer;
    }

    .days-input {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .days-input input {
      width: 60px;
      padding: 0.5rem;
      border: 1px solid #cde;
      border-radius: 4px;
      font-size: 0.95rem;
    }

    @media (max-width: 600px) {
      .modal {
        padding: 1.5rem;
        width: 95%;
      }

      .option-buttons {
        flex-direction: column;
      }
    }
  </style>

  <script>
    // Alert Modal functions
    function showAlertModal(title, message) {
      document.getElementById('alertModalTitle').textContent = title;
      document.getElementById('alertModalMessage').textContent = message;
      document.getElementById('alertModal').classList.add('show');
    }

    function closeAlertModal() {
      document.getElementById('alertModal').classList.remove('show');
    }

    // Confirmation Modal functions
    let pendingConfirmAction = null;
    function showConfirmModal(title, message, onConfirm) {
      document.getElementById('confirmModalTitle').textContent = title;
      document.getElementById('confirmModalMessage').textContent = message;
      pendingConfirmAction = onConfirm;
      document.getElementById('confirmModal').classList.add('show');
    }

    function closeConfirmModal() {
      document.getElementById('confirmModal').classList.remove('show');
      pendingConfirmAction = null;
    }

    function handleConfirmAction() {
      if (pendingConfirmAction) {
        pendingConfirmAction();
      }
      closeConfirmModal();
    }

    // Warning Modal functions
    let pendingWarningAction = null;
    function showWarningModal(title, message, onContinue) {
      document.getElementById('warningModalTitle').textContent = '⚠️ ' + title;
      document.getElementById('warningModalMessage').textContent = message;
      pendingWarningAction = onContinue;
      document.getElementById('warningModal').classList.add('show');
    }

    function closeWarningModal() {
      document.getElementById('warningModal').classList.remove('show');
      pendingWarningAction = null;
    }

    function handleWarningConfirm() {
      if (pendingWarningAction) {
        pendingWarningAction();
      }
      closeWarningModal();
    }

    function switchTab(tabName) {
      // Hide all tabs
      document.getElementById('pendingSection').style.display = 'none';
      document.getElementById('approvedSection').style.display = 'none';

      // Remove active class from all buttons
      document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));

      // Clear search and show all results
      const searchInput = document.getElementById('searchInput');
      const searchInfo = document.getElementById('searchInfo');
      if (searchInput) searchInput.value = '';
      if (searchInfo) searchInfo.textContent = '';

      // Show selected tab
      if (tabName === 'pending') {
        document.getElementById('pendingSection').style.display = 'block';
        document.querySelectorAll('.tab-btn')[0].classList.add('active');
        renderItems('pending');
      } else if (tabName === 'approved') {
        document.getElementById('approvedSection').style.display = 'block';
        document.querySelectorAll('.tab-btn')[1].classList.add('active');
        renderItems('approved');
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

    // Global variables
    let currentBorrowId = null;
    let currentRequestId = null;
    let isConfigureFromPending = false;
    let allPendingRequests = [];
    let allBorrowedBooks = [];

    function loadRequests(status, elementId) {
      fetch(`get_borrow_requests.php?status=${status}`)
        .then(response => response.json())
        .then(data => {
          if (data.success && data.requests && data.requests.length > 0) {
            let html = '';
            data.requests.forEach(request => {
              html += `
                <div class="request-item">
                  <div class="request-info">
                    <h3>
                      ${escapeHtml(request.book_title)}
                      ${status === 'pending' ? `<span class="status-badge status-${status}">${status.toUpperCase()}</span>` : ''}
                      ${request.is_guest ? '<span style="font-size: 0.75rem; background: #e3f2fd; color: #1976d2; padding: 0.25rem 0.5rem; border-radius: 4px; margin-left: 0.5rem;">GUEST</span>' : ''}
                    </h3>
                    <p><strong>Name:</strong> ${escapeHtml(request.user_name)}</p>
                    <p><strong>Email:</strong> ${request.is_guest ? 'N/A (Guest)' : escapeHtml(request.user_email)}</p>
                    <p><strong>Request Date:</strong> ${new Date(request.request_date).toLocaleString()}</p>
              `;
              
              if (request.approved_date) {
                html += `<p><strong>Approved Date:</strong> ${new Date(request.approved_date).toLocaleString()}</p>`;
              }
              
              html += `
                    </div>
                    <div class="request-actions">
              `;
              
              if (status === 'pending') {
                html += `
                  <button class="btn-approve" onclick="openConfigureModalFromPending(${request.id}, &quot;${escapeHtml(request.book_title)}&quot;, &quot;${escapeHtml(request.user_name)}&quot;, &quot;${escapeHtml(request.author)}&quot;, '${request.is_guest}', ${request.book_id})">Configure</button>
                `;
              }
              
              html += `
                    </div>
                  </div>
                </div>
              `;
            });
            document.getElementById(elementId).innerHTML = html;
          } else {
            document.getElementById(elementId).innerHTML = '<div class="empty-state"><p>No ' + status + ' requests found.</p></div>';
          }
        })
        .catch(error => {
          console.error('Error loading requests:', error);
          document.getElementById(elementId).innerHTML = '<div class="empty-state"><p style="color: #c33;">Error loading requests.</p></div>';
        });
    }

    function openConfigureModalFromPending(requestId, bookTitle, userName, author, isGuest, bookId) {
      console.log('openConfigureModalFromPending called with requestId:', requestId, 'type:', typeof requestId);
      currentRequestId = requestId;
      currentBorrowId = null;
      isConfigureFromPending = true;
      console.log('After assignment - currentRequestId:', currentRequestId, 'isConfigureFromPending:', isConfigureFromPending);
      
      // Show reject button and change save button text
      document.getElementById('rejectBtnModal').style.display = 'inline-block';
      document.querySelector('.btn-save-config').textContent = 'Approve';
      
      // Load available book copies and show modal with initial content
      loadBookCopiesForConfigure(bookId, bookTitle, userName, author);
    }

    function loadBookCopiesForConfigure(bookId, bookTitle, userName, author, currentBorrowType, currentBorrowDays, currentBorrowSchedule) {
      // Prepare pre-selected buttons and show correct sub-options
      let takehomeSelected = currentBorrowType === 'takehome';
      let classroomSelected = currentBorrowType === 'classroom';
      
      const html = `
        <div class="book-info-card">
          <h3>${bookTitle}</h3>
          <p><strong>User:</strong> ${userName}</p>
          <p><strong>Author:</strong> ${author}</p>
        </div>

        <div class="option-group">
          <label>Select Book Copy</label>
          <select id="bookCopySelect" required>
            <option value="">Loading available copies...</option>
          </select>
        </div>

        <div class="option-group">
          <label>Borrowing Type</label>
          <div class="option-buttons">
            <button class="option-btn ${takehomeSelected ? 'selected' : ''}" onclick="selectBorrowType('takehome', this)">📚 Take Home</button>
            <button class="option-btn ${classroomSelected ? 'selected' : ''}" onclick="selectBorrowType('classroom', this)">🏫 Classroom Use</button>
          </div>
        </div>

        <!-- Take Home Options -->
        <div class="sub-options ${takehomeSelected ? 'visible' : ''}" id="takehomeOptions">
          <label>Duration (days)</label>
          <div class="days-input">
            <input type="number" id="borrowDays" min="1" max="7" value="${currentBorrowDays || 7}" />
            <span>days (Max: 7 days)</span>
          </div>
        </div>

        <!-- Classroom Use Options -->
        <div class="sub-options ${classroomSelected ? 'visible' : ''}" id="classroomOptions">
          <label>Schedule</label>
          <label>
            <input type="radio" name="schedule" value="am" ${currentBorrowSchedule === 'am' ? 'checked' : ''} /> AM (7:00 AM - 1:00 PM)
          </label>
          <label>
            <input type="radio" name="schedule" value="pm" ${currentBorrowSchedule === 'pm' ? 'checked' : ''} /> PM (1:00 PM - 7:00 PM)
          </label>
        </div>
      `;

      document.getElementById('modalContent').innerHTML = html;
      document.getElementById('configureModal').classList.add('active');
      
      // Load available copies after displaying modal
      if (bookId) {
        loadAvailableCopies(bookId);
      }
    }

    function loadAvailableCopies(bookId) {
      console.log('loadAvailableCopies called with bookId:', bookId);
      fetch(`get_available_copies.php?book_id=${bookId}`)
        .then(response => {
          console.log('Response status:', response.status);
          return response.json();
        })
        .then(data => {
          console.log('Loaded copies data:', data);
          const selectElement = document.getElementById('bookCopySelect');
          console.log('Select element found:', selectElement);
          if (data.success && data.copies && data.copies.length > 0) {
            selectElement.innerHTML = '<option value="">Select a copy...</option>';
            data.copies.forEach(copy => {
              const option = document.createElement('option');
              option.value = copy.copy_id;
              option.textContent = `Copy #${copy.copy_number} (Accession: ${copy.accession_code})`;
              selectElement.appendChild(option);
              console.log('Added option:', option.textContent);
            });
            console.log('Loaded ' + data.copies.length + ' copies');
          } else {
            console.warn('No copies found or error:', data);
            selectElement.innerHTML = '<option value="">No available copies</option>';
          }
        })
        .catch(error => {
          console.error('Error loading copies:', error);
          document.getElementById('bookCopySelect').innerHTML = '<option value="">Error loading copies</option>';
        });
    }

    function loadBorrowedBooks() {
      fetch('get_borrowed_books.php')
        .then(response => response.json())
        .then(data => {
          if (data.success && data.books && data.books.length > 0) {
            let html = '';
            data.books.forEach(book => {
              const dueDate = new Date(book.due_date);
              const today = new Date();
              const isOverdue = dueDate < today;
              
              html += `
                <div class="request-item" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border: 1px solid #cde; border-radius: 8px; margin-bottom: 1rem; background: #f9fafb;">
                  <div class="request-info" onclick="openConfigureModal(${book.id}, &quot;${escapeHtml(book.book_title)}&quot;, &quot;${escapeHtml(book.user_name)}&quot;, &quot;${escapeHtml(book.author)}&quot;, '${book.is_guest}', ${book.book_id}, ${book.borrow_type ? `'${book.borrow_type}'` : 'null'}, ${book.borrow_duration || 'null'}, '${book.borrow_schedule || 'am'}')" style="cursor: pointer; flex: 1;">
                    <h3 style="margin: 0 0 0.5rem 0; color: var(--accent);">
                      ${escapeHtml(book.book_title)}
                      <span class="status-badge" style="background: #d4edda; color: #155724;">BORROWED</span>
                      ${isOverdue ? '<span style="font-size: 0.75rem; background: #ffcccc; color: #c33; padding: 0.25rem 0.5rem; border-radius: 4px; margin-left: 0.5rem;">OVERDUE</span>' : ''}
                      ${book.is_guest ? '<span style="font-size: 0.75rem; background: #e3f2fd; color: #1976d2; padding: 0.25rem 0.5rem; border-radius: 4px; margin-left: 0.5rem;">GUEST</span>' : ''}
                    </h3>
                    <p style="margin: 0.25rem 0; font-size: 0.95rem; color: var(--muted);"><strong>Name:</strong> ${escapeHtml(book.user_name)}</p>
                    <p style="margin: 0.25rem 0; font-size: 0.95rem; color: var(--muted);"><strong>Email:</strong> ${book.is_guest ? 'N/A (Guest)' : escapeHtml(book.user_email)}</p>
                    <p style="margin: 0.25rem 0; font-size: 0.95rem; color: var(--muted);"><strong>Author:</strong> ${escapeHtml(book.author)}</p>
                    <p style="margin: 0.25rem 0; font-size: 0.95rem; color: var(--muted);"><strong>Request Type:</strong> ${book.request_type === 'CCT' ? '📋 CCT' : '🌐 Online'}</p>
                    <p style="margin: 0.25rem 0; font-size: 0.95rem; color: var(--muted);"><strong>Borrow Type:</strong> ${book.borrow_type ? (book.borrow_type === 'takehome' ? '📚 Take Home (' + (book.borrow_duration || '—') + ' days)' : '🏫 Classroom (' + (book.borrow_schedule === 'am' ? 'AM' : book.borrow_schedule === 'pm' ? 'PM' : '—') + ')') : 'Not configured'}</p>
                    <p><strong>Borrow Date:</strong> ${new Date(book.borrow_date).toLocaleString()}</p>
                    <p><strong>Due Date:</strong> <span style="color: ${isOverdue ? '#c33' : '#2e7d32'}; font-weight: 600;">${dueDate.toLocaleString()}</span></p>
                    <p style="margin-top: 0.75rem; color: var(--accent); font-weight: 500;">Click to configure borrowing options</p>
                  </div>
                  <div class="request-actions">
                    <button class="btn-approve" onclick="markAsReturned(${book.id})">Mark Returned</button>
                  </div>
                </div>
              `;
            });
            document.getElementById('approvedRequests').innerHTML = html;
          } else {
            document.getElementById('approvedRequests').innerHTML = '<div class="empty-state"><p>No borrowed books found.</p></div>';
          }
        })
        .catch(error => {
          console.error('Error loading borrowed books:', error);
          document.getElementById('approvedRequests').innerHTML = '<div class="empty-state"><p style="color: #c33;">Error loading borrowed books.</p></div>';
        });
    }

    function openConfigureModal(borrowId, bookTitle, userName, author, isGuest, bookId, currentBorrowType, currentBorrowDays, currentBorrowSchedule) {
      currentBorrowId = borrowId;
      currentRequestId = null;
      isConfigureFromPending = false;
      
      // Hide reject button and change save button text back
      document.getElementById('rejectBtnModal').style.display = 'none';
      document.querySelector('.btn-save-config').textContent = 'Save Configuration';
      
      // Load available book copies and show modal with initial content
      loadBookCopiesForConfigure(bookId, bookTitle, userName, author, currentBorrowType, currentBorrowDays, currentBorrowSchedule);
    }

    function selectBorrowType(type, btn) {
      // Update button states
      document.querySelectorAll('.option-btn').forEach(button => button.classList.remove('selected'));
      if (btn) {
        btn.classList.add('selected');
      }

      // Show/hide sub-options
      document.getElementById('takehomeOptions').classList.remove('visible');
      document.getElementById('classroomOptions').classList.remove('visible');

      if (type === 'takehome') {
        document.getElementById('takehomeOptions').classList.add('visible');
      } else {
        document.getElementById('classroomOptions').classList.add('visible');
      }
    }

    function closeConfigureModal() {
      document.getElementById('configureModal').classList.remove('active');
      currentBorrowId = null;
    }

    function saveBorrowConfiguration() {
      console.log('saveBorrowConfiguration called');
      console.log('isConfigureFromPending:', isConfigureFromPending);
      console.log('currentRequestId:', currentRequestId);
      console.log('currentBorrowId:', currentBorrowId);
      
      // Validate book copy selection
      const copySelect = document.getElementById('bookCopySelect');
      if (!copySelect.value) {
        showAlertModal('Error', 'Please select a book copy');
        return;
      }
      
      const borrowType = document.querySelector('.option-btn.selected');
      if (!borrowType) {
        showAlertModal('Error', 'Please select a borrowing type');
        return;
      }

      let configData = {
        borrow_type: borrowType.textContent.includes('Take Home') ? 'takehome' : 'classroom',
        book_copy_id: copySelect.value
      };

      if (configData.borrow_type === 'takehome') {
        const days = document.getElementById('borrowDays').value;
        if (!days || days < 1 || days > 7) {
          showAlertModal('Error', 'Please enter a valid number of days (1-7)');
          return;
        }
        configData.borrow_days = parseInt(days);
      } else {
        const scheduleInput = document.querySelector('input[name="schedule"]:checked');
        if (!scheduleInput) {
          showAlertModal('Error', 'Please select a schedule (AM or PM)');
          return;
        }
        configData.borrow_schedule = scheduleInput.value;
      }

      const formData = new FormData();
      
      // If this is a pending request, approve it with configuration
      if (isConfigureFromPending && currentRequestId) {
        formData.append('request_id', currentRequestId);
        formData.append('action', 'approve');
        formData.append('config', JSON.stringify(configData));

        console.log('Sending approval request:', {request_id: currentRequestId, action: 'approve', config: configData});

        fetch('handle_request.php', {
          method: 'POST',
          body: formData
        })
          .then(response => {
            console.log('Response status:', response.status);
            return response.json().then(data => ({
              status: response.status,
              body: data
            }));
          })
          .then(({ status, body: data }) => {
            console.log('Response data:', data);
            if (status === 200 && data.success) {
              showAlertModal('Success', 'Request approved and configured successfully');
              closeConfigureModal();
              loadRequests('pending', 'pendingRequests');
              loadBorrowedBooks();
            } else {
              const errorMsg = data.message || 'Unknown error occurred';
              console.error('Error details:', errorMsg);
              showAlertModal('Error', 'Error: ' + errorMsg);
            }
          })
          .catch(error => {
            console.error('Error:', error);
            showAlertModal('Error', 'Error approving request');
          });
      } else {
        // Otherwise save configuration for already-borrowed book
        formData.append('borrowed_book_id', currentBorrowId);
        formData.append('config', JSON.stringify(configData));

        console.log('Sending config for borrowed book:', {borrowed_book_id: currentBorrowId, config: configData});

        fetch('save_borrow_config.php', {
          method: 'POST',
          body: formData
        })
          .then(response => {
            console.log('Response status:', response.status);
            return response.json();
          })
          .then(data => {
            console.log('Response data:', data);
            if (data.success) {
              showAlertModal('Success', data.message);
              closeConfigureModal();
              loadBorrowedBooks();
            } else {
              showAlertModal('Error', 'Error: ' + data.message);
            }
          })
          .catch(error => {
            console.error('Error:', error);
            showAlertModal('Error', 'Error saving configuration');
          });
      }
    }

    function rejectFromModal() {
      if (!currentRequestId) {
        showAlertModal('Error', 'Error: No request selected');
        return;
      }

      if (confirm('Are you sure you want to reject this borrow request?')) {
        const formData = new FormData();
        formData.append('request_id', currentRequestId);
        formData.append('action', 'reject');

        fetch('handle_request.php', {
          method: 'POST',
          body: formData
        })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              showAlertModal('Success', 'Request rejected successfully');
              closeConfigureModal();
              loadRequests('pending', 'pendingRequests');
              loadRequests('rejected', 'rejectedRequests');
            } else {
              showAlertModal('Error', 'Error: ' + data.message);
            }
          })
          .catch(error => {
            console.error('Error:', error);
            showAlertModal('Error', 'Error rejecting request');
          });
      }
    }

    // Close modal when clicking outside
    document.addEventListener('click', (event) => {
      const modal = document.getElementById('configureModal');
      if (event.target === modal) {
        closeConfigureModal();
      }
    });

    // Close modal on Escape key
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeConfigureModal();
      }
    });

    function handleRequest(requestId, action) {
      const formData = new FormData();
      formData.append('request_id', requestId);
      formData.append('action', action);

      fetch('handle_request.php', {
        method: 'POST',
        body: formData
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            showAlertModal('Success', data.message);
            // Reload requests
            loadRequests('pending', 'pendingRequests');
            loadBorrowedBooks();
            loadRequests('rejected', 'rejectedRequests');
          } else {
            showAlertModal('Error', 'Error: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          showAlertModal('Error', 'Error processing request');
        });
    }

    function markAsReturned(borrowedBookId) {
      if (confirm('Mark this book as returned?')) {
        const formData = new FormData();
        formData.append('borrowed_book_id', borrowedBookId);

        fetch('mark_returned.php', {
          method: 'POST',
          body: formData
        })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              showAlertModal('Success', 'Book marked as returned with ' + (data.return_status || 'unknown') + ' status');
              loadBorrowedBooks();
            } else {
              showAlertModal('Error', 'Error: ' + data.message);
            }
          })
          .catch(error => {
            console.error('Error:', error);
            showAlertModal('Error', 'Error marking book as returned');
          });
      }
    }

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    function filterSearch() {
      const searchTerm = document.getElementById('searchInput').value.toLowerCase();
      const searchInfo = document.getElementById('searchInfo');
      
      const activeTab = document.getElementById('pendingSection').style.display !== 'none' ? 'pending' : 'approved';
      let itemsToSearch = activeTab === 'pending' ? allPendingRequests : allBorrowedBooks;
      
      if (!searchTerm) {
        searchInfo.textContent = '';
        renderItems(activeTab);
        return;
      }

      let filteredItems = itemsToSearch.filter(item => {
        const bookTitle = (item.book_title || '').toLowerCase();
        const userName = (item.user_name || item.guest_name || '').toLowerCase();
        const userEmail = (item.user_email || item.email || '').toLowerCase();
        const author = (item.author || '').toLowerCase();
        
        return bookTitle.includes(searchTerm) || 
               userName.includes(searchTerm) || 
               userEmail.includes(searchTerm) ||
               author.includes(searchTerm);
      });

      if (filteredItems.length === 0) {
        searchInfo.textContent = `No results found for "${searchTerm}"`;
        searchInfo.classList.add('no-results');
        document.getElementById(activeTab === 'pending' ? 'pendingRequests' : 'approvedRequests').innerHTML = 
          '<div class="empty-state"><p>No matching requests found</p></div>';
      } else {
        searchInfo.textContent = `Found ${filteredItems.length} result${filteredItems.length !== 1 ? 's' : ''}`;
        searchInfo.classList.remove('no-results');
        renderFilteredItems(activeTab, filteredItems);
      }
    }

    function renderFilteredItems(type, items) {
      console.log('renderFilteredItems called with type:', type, 'items count:', items.length);
      
      // If no items, show empty state
      if (!items || items.length === 0) {
        const emptyHtml = '<div class="empty-state"><p>' + (type === 'pending' ? 'No pending requests' : 'No borrowed books') + '</p></div>';
        if (type === 'pending') {
          document.getElementById('pendingRequests').innerHTML = emptyHtml;
        } else {
          document.getElementById('approvedRequests').innerHTML = emptyHtml;
        }
        console.log('Empty state rendered for type:', type);
        return;
      }

      if (type === 'pending') {
        let html = '';
        items.forEach(request => {
          const bookTitle = escapeHtml(request.book_title);
          const userName = escapeHtml(request.user_name || request.guest_name);
          const userEmail = request.email ? escapeHtml(request.email) : 'Guest';
          const author = escapeHtml(request.author);

          html += `
            <div class="request-item">
              <div class="request-info">
                <h3>${bookTitle}</h3>
                <p><strong>User:</strong> ${userName}</p>
                <p><strong>Email:</strong> ${userEmail}</p>
                <p><strong>Author:</strong> ${author}</p>
                <p><strong>Request Date:</strong> ${new Date(request.request_date).toLocaleString()}</p>
              </div>
              <div class="request-actions">
                <button class="btn-configure" onclick="openConfigureModalFromPending(${request.id}, &quot;${bookTitle}&quot;, &quot;${userName}&quot;, &quot;${author}&quot;, false, ${request.book_id})">Configure</button>
              </div>
            </div>
          `;
        });
        console.log('Rendering HTML to pendingRequests, html length:', html.length);
        document.getElementById('pendingRequests').innerHTML = html;
        console.log('HTML rendered to pendingRequests');
      } else {
        let html = '';
        items.forEach(book => {
          const bookTitle = escapeHtml(book.book_title);
          const userName = escapeHtml(book.user_name || book.guest_name);
          const userEmail = book.email ? escapeHtml(book.email) : 'Guest';
          const author = escapeHtml(book.author);
          const returnStatus = book.return_status;
          let statusClass = '';
          let statusText = returnStatus ? returnStatus.charAt(0).toUpperCase() + returnStatus.slice(1) : 'Active';
          
          if (returnStatus === 'early') statusClass = 'return-status-early';
          else if (returnStatus === 'on_time') statusClass = 'return-status-on-time';
          else if (returnStatus === 'late') statusClass = 'return-status-late';
          else if (returnStatus === 'overdue') statusClass = 'return-status-overdue';

          html += `
            <div class="request-item">
              <div class="request-info">
                <h3>
                  ${bookTitle}
                  <span class="status-badge status-approved">BORROWED</span>
                  ${returnStatus && returnStatus !== 'pending' ? `<span class="return-status-badge ${statusClass}">${statusText}</span>` : ''}
                </h3>
                <p><strong>User:</strong> ${userName}</p>
                <p><strong>Email:</strong> ${userEmail}</p>
                <p><strong>Borrow Type:</strong> ${book.borrow_type ? (book.borrow_type === 'takehome' ? '📚 Take Home (' + (book.borrow_duration || '—') + ' days)' : '🏫 Classroom (' + (book.borrow_schedule === 'am' ? 'AM' : book.borrow_schedule === 'pm' ? 'PM' : '—') + ')') : 'Not configured'}</p>
                <p><strong>Borrowed:</strong> ${new Date(book.borrow_date).toLocaleString()}</p>
                <p><strong>Due:</strong> ${new Date(book.due_date).toLocaleString()}</p>
                ${book.return_date ? `<p><strong>Returned:</strong> ${new Date(book.return_date).toLocaleString()}</p>` : ''}
              </div>
              <div class="request-actions">
                ${!book.return_date ? `<button class="btn-return" onclick="markReturned(${book.id})">Mark Returned</button>` : '<span class="return-success">✓ Returned</span>'}
              </div>
            </div>
          `;
        });
        console.log('Rendering HTML to approvedRequests, html length:', html.length);
        document.getElementById('approvedRequests').innerHTML = html;
        console.log('HTML rendered to approvedRequests');
      }
    }

    function renderItems(type) {
      if (type === 'pending') {
        renderFilteredItems('pending', allPendingRequests);
      } else {
        renderFilteredItems('approved', allBorrowedBooks);
      }
    }

    // Load requests when page loads
    window.selectedCirculationBookId = null;

    function loadRegisteredUsers() {
      fetch('get_registered_users.php')
        .then(response => response.json())
        .then(data => {
          if (data.success && data.users && data.users.length > 0) {
            const select = document.getElementById('circulationUserSelect');
            select.innerHTML = '<option value="">-- Select User --</option>';
            data.users.forEach(user => {
              const option = document.createElement('option');
              option.value = user.id;
              option.textContent = `${user.name} (${user.email})`;
              select.appendChild(option);
            });
          }
        })
        .catch(error => console.error('Error loading users:', error));
    }

    // TEST FUNCTION - Can be called from browser console
    window.debugCirculationBooks = function() {
      console.log('=== DEBUG CIRCULATION BOOKS ===');
      console.log('Array length:', allAvailableBooksForCirculation.length);
      if (allAvailableBooksForCirculation.length > 0) {
        console.log('First 3 books:', allAvailableBooksForCirculation.slice(0, 3));
      } else {
        console.log('Array is empty. Attempting to reload...');
        loadAvailableBooksForCirculation();
        setTimeout(() => {
          console.log('After reload, array length:', allAvailableBooksForCirculation.length);
          if (allAvailableBooksForCirculation.length > 0) {
            console.log('First book:', allAvailableBooksForCirculation[0]);
          }
        }, 1000);
      }
    };

    let allAvailableBooksForCirculation = [];

    function loadAvailableBooksForCirculation() {
      console.log('loadAvailableBooksForCirculation() called');
      fetch('get_available_book_copies.php')
        .then(response => {
          console.log('✓ Fetch response received. Status:', response.status, response.statusText);
          console.log('  Content-Type:', response.headers.get('content-type'));
          if (!response.ok) {
            return response.text().then(text => {
              console.error('✗ Response not OK. Text:', text);
              throw new Error(`HTTP ${response.status}: ${text}`);
            });
          }
          return response.json();
        })
        .then(data => {
          console.log('✓ JSON parsed successfully. Data:', data);
          if (data.success && data.copies && Array.isArray(data.copies)) {
            allAvailableBooksForCirculation = data.copies;
            console.log('✓ Loaded ' + data.copies.length + ' available book copies');
            if (data.copies.length > 0) {
              console.log('  First copy example:', data.copies[0]);
            }
          } else {
            console.warn('✗ Data structure unexpected. Success:', data.success, 'Copies array:', Array.isArray(data.copies), 'Full data:', data);
            allAvailableBooksForCirculation = [];
          }
        })
        .catch(error => {
          console.error('✗ Fetch error:', error.message);
          console.error('  Full error:', error);
          allAvailableBooksForCirculation = [];
        });
    }

    function searchCirculationBooks() {
      const input = document.getElementById('circulationBookInput').value.trim().toLowerCase();
      const suggestionsDiv = document.getElementById('circulationBookSuggestions');
      
      if (!input) {
        suggestionsDiv.style.display = 'none';
        return;
      }

      console.log('Searching for: "' + input + '" in ' + allAvailableBooksForCirculation.length + ' copies');
      
      // If array is empty, try loading data first
      if (allAvailableBooksForCirculation.length === 0) {
        console.warn('No book copies loaded yet. Array is empty. Loading now...');
        suggestionsDiv.innerHTML = '<div style="padding: 0.75rem 1rem; color: #999;">Loading books... please wait</div>';
        suggestionsDiv.style.display = 'block';
        
        // Load and retry search after a short delay
        loadAvailableBooksForCirculation();
        setTimeout(() => {
          searchCirculationBooks();
        }, 500);
        return;
      }

      // Filter books by accession code or title
      const matches = allAvailableBooksForCirculation.filter(copy => {
        const matchAccession = copy.accession_code && copy.accession_code.toLowerCase().includes(input);
        const matchTitle = copy.title && copy.title.toLowerCase().includes(input);
        return matchAccession || matchTitle;
      });

      console.log('Found ' + matches.length + ' matches');

      if (matches.length === 0) {
        suggestionsDiv.innerHTML = '<div style="padding: 0.75rem 1rem; color: #999;">No books found</div>';
        suggestionsDiv.style.display = 'block';
        return;
      }

      let html = '';
      matches.forEach(book => {
        html += `
          <div 
            style="padding: 0.75rem 1rem; border-bottom: 1px solid #eee; cursor: pointer; transition: background 0.2s;"
            onmouseover="this.style.background='#f0f0f0';"
            onmouseout="this.style.background='white';"
            onclick="selectCirculationBook(${book.copy_id}, '${book.accession_code}', '${escapeHtml(book.title)}', '${escapeHtml(book.author)}', ${book.book_id})"
          >
            <strong>${book.accession_code}</strong> - ${escapeHtml(book.title)}
            <br>
            <small style="color: #999;">${escapeHtml(book.author)} (Copy #${book.copy_number})</small>
          </div>
        `;
      });
      
      suggestionsDiv.innerHTML = html;
      suggestionsDiv.style.display = 'block';
    }

    function selectCirculationBook(copyId, accessionCode, title, author, bookId) {
      document.getElementById('circulationBookInput').value = accessionCode + ' - ' + title;
      window.selectedCirculationBookId = bookId;
      window.selectedCirculationCopyId = copyId;
      window.selectedCirculationBook = { id: bookId, copyId: copyId, accessionCode: accessionCode, title: title, author: author };
      document.getElementById('circulationBookSuggestions').style.display = 'none';
    }

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    function loadCirculationBookCopies(bookId) {
      // This function is no longer needed - book copy is selected via accession code search
    }

    function updateUserSelectionDisplay() {
      const userType = document.querySelector('input[name="circulationUserType"]:checked')?.value || '';
      const guestNameGroup = document.getElementById('guestNameGroup');
      const registeredUserSearchGroup = document.getElementById('registeredUserSearchGroup');
      
      if (userType === 'guest') {
        guestNameGroup.style.display = 'flex';
        registeredUserSearchGroup.style.display = 'none';
      } else if (userType === 'registered') {
        guestNameGroup.style.display = 'none';
        registeredUserSearchGroup.style.display = 'flex';
      } else {
        guestNameGroup.style.display = 'none';
        registeredUserSearchGroup.style.display = 'none';
      }
    }

    let allRegisteredUsersForCirculation = [];

    function loadRegisteredUsersForCirculation() {
      fetch('get_registered_users.php')
        .then(response => response.json())
        .then(data => {
          if (data.success && data.users && data.users.length > 0) {
            allRegisteredUsersForCirculation = data.users;
          }
        })
        .catch(error => console.error('Error loading users:', error));
    }

    function searchCirculationUsers() {
      const input = document.getElementById('circulationUserInput').value.trim();
      const suggestionsDiv = document.getElementById('circulationUserSuggestions');
      
      if (!input) {
        suggestionsDiv.style.display = 'none';
        return;
      }

      // Filter users by library ID (without AU- prefix)
      const matches = allRegisteredUsersForCirculation.filter(user => 
        (user.library_id && user.library_id.replace('AU-', '').toLowerCase().includes(input.toLowerCase()))
      );

      if (matches.length === 0) {
        suggestionsDiv.innerHTML = '<div style="padding: 0.75rem 1rem; color: #999;">No users found</div>';
        suggestionsDiv.style.display = 'block';
        return;
      }

      let html = '';
      matches.forEach(user => {
        html += `
          <div 
            style="padding: 0.75rem 1rem; border-bottom: 1px solid #eee; cursor: pointer; transition: background 0.2s;"
            onmouseover="this.style.background='#f0f0f0';"
            onmouseout="this.style.background='white';"
            onclick="selectCirculationUser(${user.id}, '${user.library_id}', '${escapeHtml(user.name)}')"
          >
            <strong>${user.library_id}</strong> - ${escapeHtml(user.name)}
            <br>
            <small style="color: #999;">${escapeHtml(user.email)}</small>
          </div>
        `;
      });
      
      suggestionsDiv.innerHTML = html;
      suggestionsDiv.style.display = 'block';
    }

    function selectCirculationUser(userId, libraryId, userName) {
      document.getElementById('circulationUserInput').value = libraryId.replace('AU-', '');
      window.selectedCirculationUserId = userId;
      document.getElementById('circulationUserSuggestions').style.display = 'none';
    }

    function updateCirculationDurationDisplay() {
      const borrowType = document.querySelector('input[name="circulationBorrowType"]:checked')?.value || '';
      const durationGroup = document.getElementById('circulationDurationGroup');
      const scheduleGroup = document.getElementById('circulationScheduleGroup');
      
      if (borrowType === 'takehome') {
        durationGroup.style.display = 'flex';
        scheduleGroup.style.display = 'none';
      } else if (borrowType === 'classroom') {
        durationGroup.style.display = 'none';
        scheduleGroup.style.display = 'flex';
      } else if (borrowType === 'renew') {
        // For renew, fetch user's last borrow info
        fetchLastBorrowForRenew();
        durationGroup.style.display = 'none';
        scheduleGroup.style.display = 'none';
      } else {
        durationGroup.style.display = 'none';
        scheduleGroup.style.display = 'none';
      }
    }

    function fetchLastBorrowForRenew() {
      const userType = document.querySelector('input[name="circulationUserType"]:checked')?.value;
      
      if (userType === 'registered') {
        const userId = window.selectedCirculationUserId;
        if (!userId) {
          showAlertModal('Error', 'Please select a registered user first');
          document.querySelector('input[name="circulationBorrowType"][value="renew"]').checked = false;
          return;
        }

        fetch(`get_user_last_borrow.php?user_id=${userId}`)
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              window.lastBorrowData = {
                borrow_type: data.borrow_type,
                borrow_duration: data.borrow_duration,
                borrow_schedule: data.borrow_schedule
              };
              const typeStr = data.borrow_type === 'takehome' ? 'Take Home (' + data.borrow_duration + ' day' + (data.borrow_duration > 1 ? 's' : '') + ')' : 'Classroom (' + data.borrow_schedule.toUpperCase() + ')';
              const accessionStr = data.accession_code ? 'Book Copy: ' + data.accession_code + ' - ' : '';
              showAlertModal('Info', 'Using user\'s last borrow settings:\n' + 
                    accessionStr + data.book_title + '\nType: ' + typeStr);
            } else {
              // Show warning that user has no borrow history
              showWarningModal('No Borrow History', data.message || 'This user does not have any borrowing history.\n\nWould you like to continue with a new borrow instead of renewing?', () => {
                // User clicked "Continue Anyway" - uncheck renew and let them select a different borrow type
                document.querySelector('input[name="circulationBorrowType"][value="renew"]').checked = false;
                updateCirculationDurationDisplay();
              });
            }
          })
          .catch(error => {
            console.error('Error:', error);
            showAlertModal('Error', 'Error fetching last borrow: ' + error.message);
            document.querySelector('input[name="circulationBorrowType"][value="renew"]').checked = false;
          });
      } else if (userType === 'guest') {
        const guestName = document.getElementById('circulationGuestName').value.trim();
        if (!guestName) {
          showAlertModal('Error', 'Please enter guest name first');
          document.querySelector('input[name="circulationBorrowType"][value="renew"]').checked = false;
          return;
        }

        fetch(`get_guest_last_borrow.php?guest_name=${encodeURIComponent(guestName)}`)
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              window.lastBorrowData = {
                borrow_type: data.borrow_type,
                borrow_duration: data.borrow_duration,
                borrow_schedule: data.borrow_schedule
              };
              const typeStr = data.borrow_type === 'takehome' ? 'Take Home (' + data.borrow_duration + ' day' + (data.borrow_duration > 1 ? 's' : '') + ')' : 'Classroom (' + data.borrow_schedule.toUpperCase() + ')';
              const accessionStr = data.accession_code ? 'Book Copy: ' + data.accession_code + ' - ' : '';
              showAlertModal('Info', 'Using guest\'s last borrow settings:\n' + 
                    accessionStr + data.book_title + '\nType: ' + typeStr);
            } else {
              // Show warning that guest has no borrow history
              showWarningModal('No Borrow History', data.message || 'This guest does not have any borrowing history.\n\nWould you like to continue with a new borrow instead of renewing?', () => {
                // User clicked "Continue Anyway" - uncheck renew and let them select a different borrow type
                document.querySelector('input[name="circulationBorrowType"][value="renew"]').checked = false;
                updateCirculationDurationDisplay();
              });
            }
          })
          .catch(error => {
            console.error('Error:', error);
            showAlertModal('Error', 'Error fetching last borrow: ' + error.message);
            document.querySelector('input[name="circulationBorrowType"][value="renew"]').checked = false;
          });
      }
    }

    function lendBookDirectly() {
      const userType = document.querySelector('input[name="circulationUserType"]:checked')?.value;
      const bookId = window.selectedCirculationBookId;
      const bookCopyId = window.selectedCirculationCopyId;
      const borrowType = document.querySelector('input[name="circulationBorrowType"]:checked')?.value;
      
      if (!userType) {
        showAlertModal('Error', 'Please select a user type');
        return;
      }
      
      if (!bookId || !bookCopyId) {
        showAlertModal('Error', 'Please select a book');
        return;
      }
      
      if (!borrowType) {
        showAlertModal('Error', 'Please select a borrow type');
        return;
      }

      let userData = {};
      
      if (userType === 'guest') {
        const guestName = document.getElementById('circulationGuestName').value.trim();
        if (!guestName) {
          showAlertModal('Error', 'Please enter guest name');
          return;
        }
        userData = {
          user_type: 'guest',
          guest_name: guestName
        };
      } else {
        const userId = window.selectedCirculationUserId;
        if (!userId) {
          showAlertModal('Error', 'Please select a registered user');
          return;
        }
        userData = {
          user_type: 'registered',
          user_id: userId
        };
      }

      let borrowData = {
        book_id: bookId,
        borrow_type: borrowType,
        book_copy_id: bookCopyId
      };

      if (borrowType === 'takehome') {
        const duration = document.getElementById('circulationDuration').value;
        if (!duration) {
          showAlertModal('Error', 'Please enter borrow duration');
          return;
        }
        borrowData.borrow_duration = duration;
      } else if (borrowType === 'classroom') {
        const schedule = document.querySelector('input[name="circulationSchedule"]:checked')?.value;
        if (!schedule) {
          showAlertModal('Error', 'Please select a schedule');
          return;
        }
        borrowData.borrow_schedule = schedule;
      } else if (borrowType === 'renew') {
        if (!window.lastBorrowData) {
          showAlertModal('Error', 'Could not fetch last borrow details. Please try again.');
          return;
        }
        borrowData.borrow_type = window.lastBorrowData.borrow_type;
        
        // Only include relevant fields based on borrow type
        if (window.lastBorrowData.borrow_type === 'takehome') {
          borrowData.borrow_duration = window.lastBorrowData.borrow_duration;
        } else if (window.lastBorrowData.borrow_type === 'classroom') {
          borrowData.borrow_schedule = window.lastBorrowData.borrow_schedule;
        }
      }

      // Show confirmation before lending
      const bookTitle = window.selectedCirculationBook ? window.selectedCirculationBook.title : 'Unknown';
      const accessionCode = window.selectedCirculationBook ? window.selectedCirculationBook.accessionCode : 'Unknown';
      const confirmMsg = `Book: ${accessionCode} - ${bookTitle}\n\nBorrow Type: ${borrowData.borrow_type === 'takehome' ? 'Take Home (' + borrowData.borrow_duration + ' day' + (borrowData.borrow_duration > 1 ? 's' : '') + ')' : borrowData.borrow_type === 'renew' ? 'Renew' : 'Classroom (' + borrowData.borrow_schedule.toUpperCase() + ')'}`;
      
      // Store borrowData for use in confirmation callback
      window.pendingBorrowData = borrowData;
      window.pendingUserData = userData;
      
      showConfirmModal('Confirm Book Lending', confirmMsg, () => {
        proceedWithLending(window.pendingUserData, window.pendingBorrowData);
      });
    }

    function proceedWithLending(userData, borrowData) {
      const formData = new FormData();
      formData.append('user_type', userData.user_type);
      if (userData.guest_name) formData.append('guest_name', userData.guest_name);
      if (userData.user_id) formData.append('user_id', userData.user_id);
      formData.append('book_id', borrowData.book_id);
      formData.append('borrow_type', borrowData.borrow_type);
      if (borrowData.book_copy_id) formData.append('book_copy_id', borrowData.book_copy_id);
      if (borrowData.borrow_duration) formData.append('borrow_duration', borrowData.borrow_duration);
      if (borrowData.borrow_schedule) formData.append('borrow_schedule', borrowData.borrow_schedule);

      // Create a new endpoint to handle direct lending
      fetch('lend_book_direct.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const dueDate = new Date(data.due_date);
          const dueDateStr = dueDate.toLocaleString();
          showAlertModal('Success', 'Book lent successfully! User verified.\nDue Date: ' + dueDateStr);
          // Reset form
          document.querySelectorAll('input[name="circulationUserType"]').forEach(r => r.checked = false);
          document.getElementById('circulationUserInput').value = '';
          window.selectedCirculationUserId = null;
          document.getElementById('circulationGuestName').value = '';
          document.getElementById('circulationBookInput').value = '';
          window.selectedCirculationBookId = null;
          document.querySelectorAll('input[name="circulationBorrowType"]').forEach(r => r.checked = false);
          document.querySelectorAll('input[name="circulationSchedule"]').forEach(r => r.checked = false);
          document.getElementById('circulationDuration').value = '';
          window.lastBorrowData = null;
          updateUserSelectionDisplay();
          updateCirculationDurationDisplay();
          // Reload books list
          loadAvailableBooksForCirculation();
          loadBorrowedBooks();
        } else {
          showAlertModal('Error', 'Error: ' + (data.message || 'Failed to lend book'));
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showAlertModal('Error', 'Error lending book: ' + error.message);
      });
    }

    // Load requests when page loads
    window.selectedCirculationBookId = null;
    window.selectedCirculationUserId = null;

    document.addEventListener('DOMContentLoaded', () => {
      console.log('DOMContentLoaded fired');
      
      // Load registered users for circulation form
      loadRegisteredUsersForCirculation();
      
      // Load available books for circulation form
      loadAvailableBooksForCirculation();
      
      // Update badge on page load
      updatePendingBadge();
      
      // Load pending requests
      fetch('get_borrow_requests.php?status=pending')
        .then(response => {
          console.log('Pending requests response status:', response.status);
          return response.json();
        })
        .then(data => {
          console.log('Pending requests data:', data);
          allPendingRequests = data.requests || [];
          console.log('Pending requests loaded:', allPendingRequests.length);
          renderItems('pending');
        })
        .catch(error => {
          console.error('Error loading pending requests:', error);
          document.getElementById('pendingRequests').innerHTML = '<div class="empty-state"><p>Error loading requests</p></div>';
        });

      // Load borrowed books
      loadBorrowedBooks();

      // Add search event listener
      document.getElementById('searchInput').addEventListener('input', filterSearch);

      // Check for new requests every 30 seconds
      setInterval(updatePendingBadge, 30000);
    });

    function markReturned(borrowedBookId) {
      console.log('markReturned called with borrowedBookId:', borrowedBookId);
      
      const formData = new FormData();
      formData.append('borrowed_book_id', borrowedBookId);
      
      fetch('mark_returned.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        console.log('Mark returned response:', data);
        if (data.success) {
          showAlertModal('Success', 'Book marked as returned successfully');
          loadBorrowedBooks(); // Reload the borrowed books list
        } else {
          showAlertModal('Error', 'Error: ' + (data.message || 'Failed to mark book as returned'));
        }
      })
      .catch(error => {
        console.error('Error marking book as returned:', error);
        showAlertModal('Error', 'Error marking book as returned: ' + error.message);
      });
    }

    function loadBorrowedBooks() {
      console.log('Loading borrowed books...');
      fetch('get_borrowed_books.php')
        .then(response => {
          console.log('Get borrowed books response status:', response.status);
          return response.json();
        })
        .then(data => {
          console.log('Borrowed books data:', data);
          allBorrowedBooks = data.books || [];
          console.log('All borrowed books updated:', allBorrowedBooks);
          renderItems('approved');
        })
        .catch(error => {
          console.error('Error loading borrowed books:', error);
        });
    }
  </script>
</body>
</html>
