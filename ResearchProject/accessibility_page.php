<?php 
session_start();
require_once 'connect.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Accessibility Statement - ICT 3A Library</title>
  <meta name="description" content="Accessibility Statement for ICT 3A Library." />
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

    /* Notification badge styling moved to styles.css; keep pulse only if needed */
    @keyframes pulse {
      0%, 100% {
        transform: scale(1);
      }
      50% {
        transform: scale(1.1);
      }
    }

    .accessibility-content {
      max-width: 900px;
      margin: 0 auto;
      padding: 2rem 1rem;
      line-height: 1.8;
    }

    .accessibility-content h1 {
      color: var(--accent);
      margin-bottom: 1.5rem;
      font-size: 2rem;
    }

    .accessibility-content h2 {
      color: var(--accent);
      margin-top: 2rem;
      margin-bottom: 1rem;
      font-size: 1.3rem;
    }

    .accessibility-content p {
      color: #333;
      margin-bottom: 1rem;
    }

    .accessibility-content ul {
      margin: 1rem 0 1rem 2rem;
      color: #333;
    }

    .accessibility-content ul li {
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
      .accessibility-content {
        padding: 1.5rem 0.75rem;
      }
    }
  </style>
</head>
<body>
  <?php include 'header_template.php'; ?>

  <main>
    <div class="accessibility-content">
      <h1>Accessibility Statement</h1>
      <p class="last-updated">Last updated: January 2026</p>

      <p>We are committed to making our Online Library Borrowing System accessible to all users, including people with disabilities.</p>

      <h2>Our Commitment</h2>
      <p>We aim to ensure that everyone can access and use this website with ease, regardless of ability, device, or assistive technology.</p>

      <h2>Accessibility Features</h2>
      <p>We strive to support accessibility by:</p>
      <ul>
        <li>Using clear and readable text</li>
        <li>Providing proper labels for forms and input fields</li>
        <li>Ensuring buttons and links are easy to identify</li>
        <li>Designing pages to be usable with keyboard navigation</li>
        <li>Maintaining sufficient color contrast where possible</li>
        <li>Structuring content with proper headings for screen readers</li>
      </ul>

      <h2>Assistive Technologies</h2>
      <p>This website is designed to be compatible with common assistive technologies, including:</p>
      <ul>
        <li>Screen readers</li>
        <li>Keyboard-only navigation</li>
        <li>Modern web browsers on desktop and mobile devices</li>
      </ul>

      <h2>Limitations</h2>
      <p>While we aim to make all areas of the website accessible, some features may not yet fully meet all accessibility standards. We are continuously working to improve accessibility across the system.</p>

      <h2>Feedback and Support</h2>
      <p>If you experience any difficulty accessing content or using features on this website, please contact the system administrator. Your feedback will help us improve accessibility for all users.</p>

      <h2>Ongoing Improvements</h2>
      <p>Accessibility is an ongoing effort. We regularly review and update the system to improve usability and accessibility based on user feedback and best practices.</p>
    </div>
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
    document.addEventListener('DOMContentLoaded', updatePendingBadge);

    // Check for new requests every 30 seconds
    setInterval(updatePendingBadge, 30000);
  </script>
</body>
</html>