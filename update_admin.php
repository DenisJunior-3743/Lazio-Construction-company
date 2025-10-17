<?php
// update_admin.php
// Upload this to your project root and open it once in the browser.
// After success, DELETE this file for security.

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain');

try {
    // Adjust path if your config file is elsewhere
    require_once __DIR__ . '/config/database.php'; // expects $pdo from this file

    if (!isset($pdo) || !$pdo instanceof PDO) {
        throw new Exception("Database connection not found. Check config/database.php");
    }

    $pdo->beginTransaction();

    // find a single admin account (first by id)
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE role = 'admin' ORDER BY id LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        $pdo->rollBack();
        echo "No admin user found (no rows with role='admin').\n";
        exit;
    }

    $adminId = (int)$admin['id'];
    $newUsername = 'Admin';       // desired username
    $newPasswordPlain = 'Denis Jr'; // desired password (plain text)
    $newPasswordHash = password_hash($newPasswordPlain, PASSWORD_BCRYPT);

    // Update password (always)
    $updPwd = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $updPwd->execute([$newPasswordHash, $adminId]);

    // Check for username conflict (another account already using 'Admin')
    $chk = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $chk->execute([$newUsername, $adminId]);
    $conflict = $chk->fetch(PDO::FETCH_ASSOC);

    if ($conflict) {
        // Commit password change and report conflict for username
        $pdo->commit();
        echo "Password updated for admin (id={$adminId}).\n";
        echo "Could NOT change username to '{$newUsername}' because another user (id={$conflict['id']}) already has that username.\n";
        echo "If you want the username changed, rename or remove the conflicting account first, then re-run.\n";
        exit;
    }

    // No conflict — update username as well
    $updName = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
    $updName->execute([$newUsername, $adminId]);

    $pdo->commit();

    echo "SUCCESS: Admin (id={$adminId}) username set to '{$newUsername}' and password changed to '{$newPasswordPlain}'.\n";
    echo "IMPORTANT: Delete this file now (update_admin.php) to prevent misuse.\n";
} catch (Exception $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Check your DB credentials in config/database.php and ensure the web server can access it.\n";
}
