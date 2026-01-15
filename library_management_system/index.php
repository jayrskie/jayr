<?php
$error = '';

if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICT 3A LMS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <h1>LIBRARY MANAGEMENT SYSTEM</h1>
    </div>

    <div class="form-box">
        <form method="POST" action="login.php">
            <h2>Login</h2>
            <?php if (!empty($error)): ?>
                <p class="error-message"> <?= $error ?> </p>
            <?php endif; ?>
            <label for="libraryId">Library ID <img src="images/key.png" alt="key" class="label-icon"></label>
            <input type="text" id="libraryId" name="libraryId" placeholder="Enter the provided library ID" required>
            <label for="password">Password <img src="images/key.png" alt="lock" class="label-icon"></label>
            <input type="password" id="password" name="password" placeholder="Enter your password">
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>