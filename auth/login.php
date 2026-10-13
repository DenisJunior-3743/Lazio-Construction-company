<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/credentials.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  header('Content-Type: application/json');
  $data = json_decode(file_get_contents('php://input'), true) ?: [];
  $action = $data['action'] ?? '';
  if ($action === 'reset_password') {
    $username = trim($data['username'] ?? '');
    $password = trim($data['password'] ?? '');
    if (!$username || !$password) { echo json_encode(['ok'=>false,'message'=>'Missing credentials']); exit; }
    $stmt = $pdo->prepare('SELECT id, username, role FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if (!$user) { echo json_encode(['ok'=>false,'message'=>'User not found']); exit; }

    // Interns hold personal documents, so a registration number alone is not enough:
    // the phone number they registered with must match as well.
    if ($user['role'] === 'intern') {
      $phone = preg_replace('/\D/', '', (string)($data['phone'] ?? ''));
      $stmt = $pdo->prepare('SELECT phone FROM interns WHERE user_id = ? LIMIT 1');
      $stmt->execute([$user['id']]);
      $registered = preg_replace('/\D/', '', (string)$stmt->fetchColumn());
      if ($phone === '' || $registered === '' || !hash_equals($registered, $phone)) {
        echo json_encode(['ok'=>false,'message'=>'The phone number does not match the one this registration number was registered with.']); exit;
      }
      if (strlen($password) < 6) { echo json_encode(['ok'=>false,'message'=>'Password must be at least 6 characters.']); exit; }
    }

    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $stmt->execute([password_hash($password, PASSWORD_BCRYPT), $user['id']]);
    // Save credentials to JSON file (not for interns - their passwords are never stored in plain text)
    if ($user['role'] !== 'intern') {
      saveUserCredentials($pdo, $user['id'], $username, $password);
    }
    echo json_encode(['ok'=>true]); exit;
  }

  $username = trim($data['username'] ?? '');
  $password = trim($data['password'] ?? '');

  if (!$username || !$password) { echo json_encode(['ok'=>false,'message'=>'Missing credentials']); exit; }

  $stmt = $pdo->prepare('SELECT u.id, u.username, u.password_hash, u.role, c.id as client_id
                         FROM users u LEFT JOIN clients c ON c.user_id = u.id
                         WHERE username = ? LIMIT 1');
  $stmt->execute([$username]);
  $user = $stmt->fetch();
  if (!$user || !password_verify($password, $user['password_hash'])) {
    echo json_encode(['ok'=>false,'message'=>'Invalid username or password']); exit;
  }
  // Only intern accounts touch the interns table, so admin and client logins never depend on it.
  $user['intern_id'] = null;
  if ($user['role'] === 'intern') {
    $st = $pdo->prepare('SELECT id FROM interns WHERE user_id = ? LIMIT 1');
    $st->execute([$user['id']]);
    $user['intern_id'] = $st->fetchColumn() ?: null;
  }
  session_regenerate_id(true);
  $_SESSION['user'] = [
    'id' => (int)$user['id'],
    'username' => $user['username'],
    'role' => $user['role'],
    'client_id' => $user['client_id'],
    'intern_id' => $user['intern_id']
  ];
  if ($user['role'] === 'admin') $redirect = '/lazio_site/admin/dashboard.php';
  elseif ($user['role'] === 'intern') $redirect = '/lazio_site/intern/index.php';
  else $redirect = '/lazio_site/client/index.php';
  echo json_encode(['ok'=>true,'redirect'=>$redirect]); exit;
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Lazio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/lazio_site/assets/css/root.css" rel="stylesheet">
  </head>
  <body>
    <div id="header-include"></div>
    <main class="container">
      <div class="auth-shell">
        <div class="w-100" style="max-width:460px;">
          <div class="auth-card fade-in">
            <div class="text-center">
              <div class="auth-icon"><i class="bi bi-shield-lock"></i></div>
              <h1 class="h4 fw-bold mb-1">Welcome back</h1>
              <p class="text-muted mb-4">Login to manage your Lazio account.</p>
            </div>
            <div id="loginAlert" class="alert alert-danger d-none"></div>
            <form id="loginForm">
              <div class="mb-3">
                <label class="form-label">Username</label>
                <input name="username" class="form-control" placeholder="Enter your username" required>
                <div class="form-text"><i class="bi bi-mortarboard me-1"></i>Interns: your registration number is your username.</div>
              </div>
              <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="password-toggle">
                  <input name="password" type="password" class="form-control" placeholder="Enter your password" required>
                  <button type="button" class="btn password-toggle-btn" title="Toggle password visibility">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
              </div>
              <button class="btn btn-primary w-100"><i class="bi bi-box-arrow-in-right me-2"></i>Login</button>
            </form>
            <div class="text-end mt-2">
              <button type="button" class="btn btn-link p-0" id="forgotPasswordBtn">Forgot Password?</button>
            </div>
            <form id="resetForm" class="d-none mt-3 pt-3" style="border-top:1px solid var(--border);">
              <p class="small text-muted mb-3"><i class="bi bi-key me-1"></i>Reset your password</p>
              <div class="mb-2">
                <label class="form-label">Username</label>
                <input name="reset_username" class="form-control" required>
              </div>
              <div class="mb-2">
                <label class="form-label">Registered phone number <span class="text-muted fw-normal">(interns only)</span></label>
                <input name="reset_phone" class="form-control" inputmode="numeric" maxlength="10" placeholder="The phone number you registered with">
              </div>
              <div class="mb-2">
                <label class="form-label">New Password</label>
                <div class="password-toggle">
                  <input name="reset_password" type="password" class="form-control" required>
                  <button type="button" class="btn password-toggle-btn" title="Toggle password visibility">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <div class="password-toggle">
                  <input name="reset_confirm" type="password" class="form-control" required>
                  <button type="button" class="btn password-toggle-btn" title="Toggle password visibility">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
              </div>
              <button class="btn btn-success w-100">Update Password</button>
              <button type="button" class="btn btn-link w-100 mt-2" id="backToLoginBtn">Back to Login</button>
            </form>
          </div>
          <p class="text-center text-muted small mt-3 mb-0">
            Don't have an account? <a href="/lazio_site/auth/signup.php" class="fw-semibold">Sign up</a>
            &middot; <a href="/lazio_site/auth/signup.php?role=intern" class="fw-semibold">Register as an intern</a>
          </p>
        </div>
      </div>
    </main>
    <div id="footer-include"></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/lazio_site/assets/js/include.js"></script>
    <script src="/lazio_site/assets/js/auth.js"></script>
  </body>
</html>
