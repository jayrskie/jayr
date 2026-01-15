<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $libraryId = trim($_POST['libraryId']);
    $password = $_POST['password'] ?? '';

    if (empty($libraryId)) {
        header('Location: index.php?error=' . urlencode('Library ID is required.'));
        exit();
    } else {
        // Check if users table has a password-like column
        $passwordCol = null;
        $schema = $conn->real_escape_string($dbname);
        $resCols = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '$schema' AND TABLE_NAME = 'users'");
        if ($resCols) {
            while ($r = $resCols->fetch_assoc()) {
                $col = $r['COLUMN_NAME'];
                $lower = strtolower($col);
                if (in_array($lower, ['password','passwd','pass'])) {
                    $passwordCol = $col;
                    break;
                }
            }
            $resCols->free();
        }

        // Build SELECT depending on whether a password column exists
        if ($passwordCol) {
            $sql = "SELECT username, id, role, `$passwordCol` FROM users WHERE library_id = ? LIMIT 1";
        } else {
            $sql = 'SELECT username, id, role FROM users WHERE library_id = ? LIMIT 1';
        }

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('s', $libraryId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $userRow = $result->fetch_assoc();

                // If password column exists, verify password if one is set
                if ($passwordCol) {
                    $hash = $userRow[$passwordCol] ?? '';
                    
                    // If password is set in database, require and verify it
                    if (!empty($hash)) {
                        if (empty($password)) {
                            header('Location: index.php?error=' . urlencode('Password is required.'));
                            exit();
                        }

                        $passwordOk = false;
                        // If hash looks like a PHP password_hash output, use password_verify
                        if (is_string($hash) && (strpos($hash, '$2y$') === 0 || strpos($hash, '$2a$') === 0 || strpos($hash, '$argon2') === 0)) {
                            if (password_verify($password, $hash)) $passwordOk = true;
                        } else {
                            // Fall back to direct compare for plaintext passwords
                            if ($hash === $password) $passwordOk = true;
                        }

                        if (!$passwordOk) {
                            header('Location: index.php?error=' . urlencode('Invalid credentials.'));
                            exit();
                        }
                    }
                    // If no password is set in database, allow login regardless of password entered
                }

                // Credentials ok — set session
                $username = $userRow['username'] ?? $libraryId;
                $userId = $userRow['id'] ?? 0;
                $role = $userRow['role'] ?? 'user';

                $_SESSION['username'] = $username;
                $_SESSION['library_id'] = $libraryId;
                $_SESSION['user_id'] = $userId;
                $_SESSION['role'] = $role;

                // Determine display name
                $displayName = $libraryId;
                $candidates = ['username','user_name','name','full_name','email'];
                foreach ($candidates as $c) {
                    if (isset($userRow[$c]) && trim($userRow[$c]) !== '') {
                        $displayName = $userRow[$c];
                        break;
                    }
                }

                $_SESSION['display_name'] = $displayName;

                $roleLower = strtolower((string)$_SESSION['role']);
                if ($roleLower === 'admin') {
                    header('Location: ./admin/admin-dashboard.php');
                    exit();
                } else {
                    header('Location: ./user/user-dashboard.php');
                    exit();
                }
            } else {
                header('Location: index.php?error=' . urlencode('Invalid Library ID.'));
                exit();
            }
            $stmt->close();
        } else {
            header('Location: index.php?error=' . urlencode('Database query failed.'));
            exit();
        }
    }

    $conn->close();
}