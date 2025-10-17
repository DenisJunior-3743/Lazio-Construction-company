<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../config/database.php';
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
    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $stmt->execute([password_hash($password, PASSWORD_BCRYPT), $user['id']]);
    // Save credentials to JSON file
    saveUserCredentials($pdo, $user['id'], $username, $password);
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
  $_SESSION['user'] = [
    'id' => (int)$user['id'],
    'username' => $user['username'],
    'role' => $user['role'],
    'client_id' => $user['client_id']
  ];
  $redirect = $user['role']==='admin' ? '/lazio_site/admin/dashboard.php' : '/lazio_site/client/index.php';
  echo json_encode(['ok'=>true,'redirect'=>$redirect]); exit;
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Lazio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/lazio_site/assets/css/root.css" rel="stylesheet">
  </head>
  <body>
    <div id="header-include"></div>
    <main class="container my-5" style="max-width:520px;">
      <h1 class="h4 mb-3">Login</h1>
      <div id="loginAlert" class="alert alert-danger d-none"></div>
      <form id="loginForm" class="card p-3">
        <div class="mb-2">
          <label class="form-label">Username</label>
          <input name="username" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <div class="password-toggle">
            <input name="password" type="password" class="form-control" required>
            <button type="button" class="btn password-toggle-btn" title="Toggle password visibility">
              <i class="bi bi-eye"></i>
            </button>
          </div>
        </div>
        <button class="btn btn-primary w-100">Login</button>
      </form>
      <div class="text-end mt-2">
        <button type="button" class="btn btn-link p-0" id="forgotPasswordBtn">Forgot Password?</button>
      </div>
      <form id="resetForm" class="card p-3 mt-3 d-none">
        <div class="mb-2">
          <label class="form-label">Username</label>
          <input name="reset_username" class="form-control" required>
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
        <div class="mb-2">
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
    </main>
    <div id="footer-include"></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/lazio_site/assets/js/include.js"></script>
    <script src="/lazio_site/assets/js/auth.js"></script>
  </body>
</html>
<?php
// Add this function at the bottom of login.php
function saveUserCredentials($pdo, $user_id, $username, $password) {
  // Get full name from clients table if exists
  $stmt = $pdo->prepare('SELECT first_name, last_name FROM clients WHERE user_id = ?');
  $stmt->execute([$user_id]);
  $client = $stmt->fetch();
  $full_name = $client ? ($client['first_name'] . ' ' . $client['last_name']) : $username;
  $data = [
    'user_id' => $user_id,
    'username' => $username,
    'full_name' => $full_name,
    'password' => $password,
    'updated_at' => date('Y-m-d H:i:s')
  ];
  $jsonFile = __DIR__ . '/../data/user_credentials.json';
  $all = [];
  if (file_exists($jsonFile)) {
    $all = json_decode(file_get_contents($jsonFile), true) ?: [];
  }
  $all[$user_id] = $data;
  file_put_contents($jsonFile, json_encode($all, JSON_PRETTY_PRINT));
}
