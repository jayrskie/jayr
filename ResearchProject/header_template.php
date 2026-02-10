<?php
/**
 * Header template for all pages
 * Include this file at the top of the <header> section
 */
?>
<header class="site-header">
  <div class="container header-inner">
    <a class="brand" href="<?php echo (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') ? 'admin.php' : 'index.php'; ?>" aria-label="AU JRC Library home">
      <img class="logo" src="images/au_logo.jpg" alt="AU JRC Library Logo" />
      <span class="brand-name">AU JRC Library</span>
    </a>

    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
      <nav class="center-nav" aria-label="Admin">
        <a class="nav-link" href="catalog_page.php">Catalog</a>
      </nav>
      <nav class="center-nav" aria-label="Admin">
        <a class="nav-link" href="transaction_page.php">Transaction <span class="notification-badge hidden" id="pendingBadge" onclick="event.stopPropagation(); event.preventDefault();">0</span></a>
      </nav>
      <nav class="center-nav" aria-label="Admin">
        <a class="nav-link" href="transaction_history_page.php">History</a>
      </nav>
    <?php elseif (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'user'): ?>
      <nav class="center-nav" aria-label="User">
        <a class="nav-link" href="index.php">Catalog</a>
      </nav>
      <nav class="center-nav" aria-label="User">
        <a class="nav-link" href="user_borrow_history.php">Borrow History</a>
      </nav>
    <?php endif; ?>

    <nav class="main-nav" aria-label="Primary">
      <p class="welcome">Welcome!</p>
      <?php if (isset($_SESSION['user_id'])): ?>
        <div class="user-menu">
          <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
          <a class="btn" href="logout.php">Logout</a>
        </div>
      <?php else: ?>
        <a class="btn" href="login_page.php">Login</a>
        <a class="btn" href="register_page.php">Register</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
