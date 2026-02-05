<?php session_start(); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Login — ICT 3A Library</title>
  <meta name="description" content="Login to your ICT 3A Library account" />
  <link rel="stylesheet" href="styles.css" />
  <style>
    .welcome {
      font-weight: 500;
      font-size: 1.1rem;
    }
    .login-container {
      max-width: 400px;
      margin: 60px auto;
      padding: 40px;
      background: #f9f9f9;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    .login-container h1 {
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
    .login-btn {
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
    .login-btn:hover {
      background: #1a4d80;
    }
    .signup-link {
      text-align: center;
      margin-top: 20px;
      font-size: 14px;
    }
    .signup-link a {
      color: #246;
      text-decoration: none;
    }
    .signup-link a:hover {
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
  <?php include 'header_template.php'; ?>

  <main>
    <div class="login-container">
      <h1>Login</h1>
      <?php
      if (isset($_SESSION['error'])) {
          echo '<div class="alert alert-error">' . htmlspecialchars($_SESSION['error']) . '</div>';
          unset($_SESSION['error']);
      }
      ?>
      <form method="POST" action="login_process.php">
        <div class="form-group">
          <label for="library_id">Library ID</label>
          <input type="text" id="library_id" name="library_id" required placeholder="AU-XXXXXXXXXXXX (12 number)" />
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="Leave blank if you didn't set a password" />
        </div>

        <button type="submit" class="login-btn">Login</button>
      </form>

      <div class="signup-link">
        Don't have an account? <a href="register_page.php">Create one here</a>
      </div>
    </div>
  </main>

  <footer>
    <p>&copy; 2026 ICT 3A Library. All rights reserved.</p>
  </footer>
</body>
</html>
