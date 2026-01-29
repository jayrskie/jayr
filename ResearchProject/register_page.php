<?php session_start(); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Register — ICT 3A Library</title>
  <meta name="description" content="Create a new account at ICT 3A Library" />
  <link rel="stylesheet" href="styles.css" />
  <style>
    .register-container {
      max-width: 400px;
      margin: 60px auto;
      padding: 40px;
      background: #f9f9f9;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    .register-container h1 {
      text-align: center;
      margin-bottom: 30px;
      color: #246;
    }
    .form-group {
      margin-bottom: 20px;
    }
    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: #333;
    }
    .form-group input {
      width: 100%;
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 16px;
      box-sizing: border-box;
    }
    .form-group input:focus {
      outline: none;
      border-color: #246;
      box-shadow: 0 0 4px rgba(34, 70, 102, 0.2);
    }
    .register-btn {
      width: 100%;
      padding: 12px;
      background: #246;
      color: white;
      border: none;
      border-radius: 4px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      margin-top: 10px;
    }
    .register-btn:hover {
      background: #1a4d80;
    }
    .login-link {
      text-align: center;
      margin-top: 20px;
      font-size: 14px;
    }
    .login-link a {
      color: #246;
      text-decoration: none;
    }
    .login-link a:hover {
      text-decoration: underline;
    }
    .alert {
      padding: 12px 16px;
      border-radius: 4px;
      margin-bottom: 20px;
      font-weight: 500;
    }
    .alert-error {
      background: #fee;
      color: #c33;
      border: 1px solid #fcc;
    }
    .alert-success {
      background: #efe;
      color: #3c3;
      border: 1px solid #cfc;
    }
    footer {
      text-align: center;
      padding: 20px 0;
      margin-top: 40px;
      color: #666;
      font-size: 14px;
    }
  </style>
</head>
<body>
  <header class="site-header">
    <div class="container header-inner">
      <a class="brand" href="index.php" aria-label="Riverside Library home">
        <svg class="logo" width="40" height="40" viewBox="0 0 24 24" aria-hidden="true">
          <rect x="3" y="4" width="18" height="14" rx="2" fill="#246" />
          <path d="M6 8h12M6 12h8" stroke="#fff" stroke-width="1.2" stroke-linecap="round" />
        </svg>
        <span class="brand-name">ICT 3A Library</span>
      </a>
    </div>
  </header>

  <main>
    <div class="register-container">
      <h1>Create Account</h1>
      <?php
      if (isset($_SESSION['error'])) {
          echo '<div class="alert alert-error">' . htmlspecialchars($_SESSION['error']) . '</div>';
          unset($_SESSION['error']);
      }
      if (isset($_SESSION['success'])) {
          echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success']) . '</div>';
          unset($_SESSION['success']);
      }
      ?>
      <form method="POST" action="register_process.php">
        <div class="form-group">
          <label for="library_id">Library ID <span style="color: #c33;">*</span></label>
          <input type="text" id="library_id" name="library_id" required placeholder="AU-XXXXXXXXXXXX (12 number)" maxlength="15" />
        </div>

        <div class="form-group">
          <label for="name">Full Name <span style="color: #c33;">*</span></label>
          <input type="text" id="name" name="name" required placeholder="Dela Cruz, Juan" />
        </div>

        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" placeholder="juan.dela.cruz@example.com (optional)" />
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="Enter your password (optional)" />
        </div>

        <button type="submit" class="register-btn">Register</button>
      </form>

      <div class="login-link">
        Already have an account? <a href="login_page.php">Log in here</a>
      </div>
    </div>
  </main>

  <footer>
    <p>&copy; 2026 ICT 3A Library. All rights reserved.</p>
  </footer>
</body>
</html>