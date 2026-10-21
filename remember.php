<?php
/**
 * Student Portal - Remember-Me Token Handler 
 * Uses selector/validator split-token pattern to resist timing attacks.
 */

require_once __DIR__ . '/db.php';

function setRememberMeCookie($userId, $conn) {
    // Generate 12 bytes = 24 hex chars selector, 32 bytes validator
    $selector = bin2hex(random_bytes(12));
    $validator = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $validator);
    $expiresAt = date('Y-m-d H:i:s', time() + (86400 * 30)); // 30 days

    // Clean old tokens for this user
    $del = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
    if ($del) {
        $del->bind_param("i", $userId);
        $del->execute();
        $del->close();
    }

    // Insert new token
    $stmt = $conn->prepare("INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isss", $userId, $selector, $tokenHash, $expiresAt);
        $stmt->execute();
        $stmt->close();

        // Set secure cookie
        $cookieValue = $selector . ':' . $validator;
        setcookie('remember_me', $cookieValue, [
            'expires'  => time() + (86400 * 30),
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
}

function restoreRememberLogin($conn) {
    if (isset($_SESSION['user_id']) || empty($_COOKIE['remember_me'])) {
        return;
    }

    $parts = explode(':', $_COOKIE['remember_me']);
    if (count($parts) !== 2) {
        return;
    }

    [$selector, $validator] = $parts;

    $stmt = $conn->prepare("
        SELECT rt.user_id, rt.token_hash, rt.expires_at, u.full_name, u.email, COALESCE(ur.role, 'student') as role
        FROM remember_tokens rt
        JOIN users u ON u.user_id = rt.user_id
        LEFT JOIN user_roles ur ON ur.user_id = u.user_id
        WHERE rt.selector = ? AND rt.expires_at > NOW()
        LIMIT 1
    ");

    if ($stmt) {
        $stmt->bind_param("s", $selector);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $expectedHash = hash('sha256', $validator);
            if (hash_equals($row['token_hash'], $expectedHash)) {
                // Regenerate session ID and restore session
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$row['user_id'];
                $_SESSION['full_name'] = $row['full_name'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['last_activity'] = time();

                logAudit($conn, $row['user_id'], $row['full_name'], 'REMEMBER_LOGIN', 'Restored session via remember-me token.');
            }
        }
        $stmt->close();
    }
}

function clearRememberMeCookie($userId, $conn) {
    if ($userId > 0) {
        $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();
        }
    }

    if (isset($_COOKIE['remember_me'])) {
        setcookie('remember_me', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        unset($_COOKIE['remember_me']);
    }
}
?>
