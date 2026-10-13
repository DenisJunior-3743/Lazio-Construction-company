<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/credentials.php';
require_once __DIR__ . '/../includes/intern_helpers.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  header('Content-Type: application/json');
  $data = json_decode(file_get_contents('php://input'), true) ?: [];

  // Intern self-registration: the registration number is the username.
  if (($data['role'] ?? '') === 'intern') {
    intern_json_guard();
    $password = trim($data['password'] ?? '');
    if (isset($data['confirm_password']) && $data['confirm_password'] !== $password) {
      echo json_encode(['ok'=>false,'message'=>'Passwords do not match.']); exit;
    }
    $res = intern_register($pdo, $data, 'self');
    if (!$res['ok']) { echo json_encode($res); exit; }
    // Sign the new intern straight in.
    session_regenerate_id(true);
    $_SESSION['user'] = [
      'id' => $res['user_id'],
      'username' => $res['username'],
      'role' => 'intern',
      'client_id' => null,
      'intern_id' => $res['intern_id']
    ];
    echo json_encode(['ok'=>true,'redirect'=>'/lazio_site/intern/index.php']); exit;
  }

  $first_name = trim($data['first_name'] ?? '');
  $last_name  = trim($data['last_name'] ?? '');
  $address    = trim($data['address'] ?? '');
  $phone      = trim($data['phone'] ?? '');
  $gender     = trim($data['gender'] ?? '');
  $username   = trim($data['username'] ?? '');
  $password   = trim($data['password'] ?? '');
  $role       = 'client'; // Only client for now

  if (!$first_name || !$last_name || !$address || !$phone || !$gender || !$username || !$password) {
    echo json_encode(['ok'=>false,'message'=>'All fields are required']); exit;
  }

  // Ensure unique username
  $check = $pdo->prepare('SELECT id FROM users WHERE username = ?');
  $check->execute([$username]);
  if ($check->fetch()) { echo json_encode(['ok'=>false,'message'=>'Username already taken']); exit; }

  $pdo->beginTransaction();
  try {
    $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)');
    $stmt->execute([$username, password_hash($password, PASSWORD_BCRYPT), $role]);
    $user_id = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare('INSERT INTO clients (user_id, first_name, last_name, address, phone, gender) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$user_id, $first_name, $last_name, $address, $phone, $gender]);

    $pdo->commit();
    saveUserCredentials($pdo, $user_id, $username, $password);
    echo json_encode(['ok'=>true]);
  } catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['ok'=>false,'message'=>'Signup failed']); 
  }
  exit;
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign Up - Lazio</title>
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
        <div class="w-100" style="max-width:640px;">
          <div class="auth-card auth-card-wide fade-in">
            <div class="text-center">
              <div class="auth-icon"><i class="bi bi-person-plus"></i></div>
              <h1 class="h4 fw-bold mb-1">Create your account</h1>
              <p class="text-muted mb-3" id="signupIntro" data-client="Sign up as a client to receive and track your invoices." data-intern="Register as an intern to apply for industrial training and get your acceptance letter.">Sign up as a client to receive and track your invoices.</p>
            </div>
            <ul class="nav nav-pills nav-fill mb-4 gap-2" id="roleTabs" role="tablist">
              <li class="nav-item" role="presentation">
                <button type="button" class="nav-link active" data-role="client"><i class="bi bi-person me-1"></i>I'm a Client</button>
              </li>
              <li class="nav-item" role="presentation">
                <button type="button" class="nav-link" data-role="intern"><i class="bi bi-mortarboard me-1"></i>I'm an Intern</button>
              </li>
            </ul>
            <div id="signupAlert" class="alert alert-danger d-none"></div>
            <form id="signupForm">
              <div class="row g-3">
                <div class="col-12 col-md-6">
                  <label class="form-label">First Name</label>
                  <input name="first_name" class="form-control" required>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label">Last Name</label>
                  <input name="last_name" class="form-control" required>
                </div>
                <div class="col-12">
                  <label class="form-label">Address</label>
                  <input name="address" class="form-control" required>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label">Phone</label>
                  <input name="phone" class="form-control" required>
                  <div id="phoneFeedback" class="invalid-feedback"></div>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label">Gender</label>
                  <select name="gender" class="form-select" required>
                    <option value="">Select…</option>
                    <option>Male</option>
                    <option>Female</option>
                  </select>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label">Username</label>
                  <input name="username" class="form-control" required>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label">Password</label>
                  <div class="password-toggle">
                    <input name="password" type="password" class="form-control" required>
                    <button type="button" class="btn password-toggle-btn" title="Toggle password visibility">
                      <i class="bi bi-eye"></i>
                    </button>
                  </div>
                </div>
              </div>
              <button id="signupSubmit" class="btn btn-primary w-100 mt-4" disabled><i class="bi bi-check2-circle me-2"></i>Create Account</button>
            </form>

            <form id="internSignupForm" class="d-none" autocomplete="off">
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label">Full Names</label>
                  <input name="full_name" class="form-control" placeholder="As they appear on your institution records" required>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label">Registration Number</label>
                  <input name="reg_number" class="form-control" placeholder="e.g. UVT088/U/26/M/EI/0137" required>
                  <div class="form-text">This will be your username.</div>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label">Phone</label>
                  <input name="phone" class="form-control" inputmode="numeric" maxlength="10" required>
                </div>
                <div class="col-12">
                  <label class="form-label">Institution</label>
                  <input name="institution" class="form-control" placeholder="e.g. Nile Vocational Institute" required>
                </div>
                <div class="col-12">
                  <label class="form-label">Course / Programme</label>
                  <input name="course" class="form-control" placeholder="e.g. Certificate in Electrical Installation Practice" required>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label">Password</label>
                  <div class="password-toggle">
                    <input name="password" type="password" class="form-control" minlength="6" required>
                    <button type="button" class="btn password-toggle-btn" title="Toggle password visibility">
                      <i class="bi bi-eye"></i>
                    </button>
                  </div>
                  <div class="form-text">At least 6 characters.</div>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label">Confirm Password</label>
                  <div class="password-toggle">
                    <input name="confirm_password" type="password" class="form-control" required>
                    <button type="button" class="btn password-toggle-btn" title="Toggle password visibility">
                      <i class="bi bi-eye"></i>
                    </button>
                  </div>
                </div>
              </div>
              <button id="internSignupSubmit" class="btn btn-primary w-100 mt-4"><i class="bi bi-mortarboard me-2"></i>Register as Intern</button>
              <p class="small text-muted text-center mt-3 mb-0">Before your application is approved, interns pay a UGX 20,000 organisation fee. <a href="/lazio_site/pages/internship.php">See the available trainings</a>.</p>
            </form>
          </div>
          <p class="text-center text-muted small mt-3 mb-0">
            Already have an account? <a href="/lazio_site/auth/login.php" class="fw-semibold">Login</a>
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
