<?php 
session_start();
require_once 'connect.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Privacy Policy - ICT 3A Library</title>
  <meta name="description" content="Privacy Policy for ICT 3A Library." />
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

    .privacy-content {
      max-width: 900px;
      margin: 0 auto;
      padding: 2rem 1rem;
      line-height: 1.8;
    }

    .privacy-content h1 {
      color: var(--accent);
      margin-bottom: 1.5rem;
      font-size: 2rem;
    }

    .privacy-content h2 {
      color: var(--accent);
      margin-top: 2rem;
      margin-bottom: 1rem;
      font-size: 1.3rem;
    }

    .privacy-content p {
      color: #333;
      margin-bottom: 1rem;
    }

    .privacy-content ul {
      margin: 1rem 0 1rem 2rem;
      color: #333;
    }

    .privacy-content ul li {
      margin-bottom: 0.5rem;
    }

    .last-updated {
      color: var(--muted);
      font-style: italic;
      margin-bottom: 2rem;
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
      .privacy-content {
        padding: 1.5rem 0.75rem;
      }
    }
  </style>
</head>
<body>
  <header class="site-header">
    <div class="container header-inner">
      <a class="brand" href="<?php echo isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin' ? 'admin.php' : 'index.php'; ?>" aria-label="ICT 3A Library home">
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

  <main>
    <div class="privacy-content">
      <h1>Privacy Policy</h1>
      <p class="last-updated">Last updated: January 2026</p>

      <p>We value your privacy. This Privacy Policy explains how our Online Library Borrowing System collects, uses, and protects your information when you use our website.</p>

      <h2>1. Information We Collect</h2>
      <p>We may collect the following information when you use the system:</p>
      <ul>
        <li><strong>Personal information such as:</strong>
          <ul>
            <li>Name</li>
            <li>Email address</li>
            <li>Student or user ID (if applicable)</li>
          </ul>
        </li>
        <li><strong>Account and borrowing information such as:</strong>
          <ul>
            <li>Borrowed books</li>
            <li>Borrow and return dates</li>
            <li>Request and approval status</li>
          </ul>
        </li>
        <li><strong>Technical information such as:</strong>
          <ul>
            <li>Browser type</li>
            <li>Date and time of access (for system logs)</li>
          </ul>
        </li>
      </ul>

      <h2>2. How We Use Your Information</h2>
      <p>The information we collect is used to:</p>
      <ul>
        <li>Manage user accounts</li>
        <li>Process book borrowing and returning requests</li>
        <li>Track borrowing history and due dates</li>
        <li>Send notifications related to your requests (if email is enabled)</li>
        <li>Improve system performance and user experience</li>
      </ul>

      <h2>3. Data Protection</h2>
      <p>We take reasonable measures to protect your information, including:</p>
      <ul>
        <li>Restricting access to authorized administrators only</li>
        <li>Using secure database storage</li>
        <li>Preventing unauthorized access, modification, or disclosure of data</li>
      </ul>
      <p>However, no system is 100% secure, and we cannot guarantee absolute security.</p>

      <h2>4. Data Sharing</h2>
      <p>We do not sell, trade, or share your personal information with third parties.</p>
      <p>Your data is only accessible to:</p>
      <ul>
        <li>System administrators</li>
        <li>Authorized library staff (if applicable)</li>
      </ul>

      <h2>5. Cookies</h2>
      <p>This website may use cookies or session storage to:</p>
      <ul>
        <li>Keep you logged in</li>
        <li>Maintain system functionality</li>
      </ul>
      <p>We do not use cookies for advertising or tracking across other websites.</p>

      <h2>6. User Rights</h2>
      <p>You have the right to:</p>
      <ul>
        <li>View your personal and borrowing information</li>
        <li>Request correction of incorrect data</li>
        <li>Request account deletion (subject to administrative approval)</li>
      </ul>

      <h2>7. Third-Party Services</h2>
      <p>If third-party services are used (such as email services), they will only be used to support system functionality and not for marketing purposes.</p>

      <h2>8. Changes to This Policy</h2>
      <p>This Privacy Policy may be updated from time to time. Any changes will be posted on this page.</p>

      <h2>9. Contact Information</h2>
      <p>If you have questions or concerns about this Privacy Policy, please contact the system administrator.</p>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container footer-inner">
      <p>© ICT 3A Library • <a href="privacy_page.php">Privacy</a> • <a href="accessibility_page.php">Accessibility</a></p>
      <p class="muted small">Follow us:
        <a href="#" aria-label="Facebook">FB</a> · <a href="#" aria-label="Twitter">X</a> · <a href="#" aria-label="Instagram">IG</a>
      </p>
    </div>
  </footer>

  <script>
    // Fetch and update pending requests count (for admin only)
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
    updatePendingBadge();

    // Update badge every 30 seconds
    setInterval(updatePendingBadge, 30000);
  </script>
</body>
</html>