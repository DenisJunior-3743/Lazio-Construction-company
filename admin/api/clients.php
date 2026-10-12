
<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') { http_response_code(403); exit; }
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

if ($action === 'list') {
  $stmt = $pdo->query('SELECT c.id, c.first_name, c.last_name, c.phone, u.username, u.role
                       FROM clients c LEFT JOIN users u ON u.id = c.user_id ORDER BY c.id DESC');
  echo json_encode(['items'=>$stmt->fetchAll()]); exit;
}

if ($action === 'options') {
  $stmt = $pdo->query('SELECT id, first_name, last_name FROM clients ORDER BY first_name');
  echo json_encode(['items'=>$stmt->fetchAll()]); exit;
}

if ($action === 'create') {
  require_once __DIR__ . '/../../includes/credentials.php';
  $data = json_decode(file_get_contents('php://input'), true) ?: [];
  $first_name = trim($data['first_name'] ?? '');
  $last_name  = trim($data['last_name'] ?? '');
  $address    = trim($data['address'] ?? '');
  $phone      = trim($data['phone'] ?? '');
  $gender     = trim($data['gender'] ?? '');
  $set_credentials = !empty($data['set_credentials']);
  $username   = trim($data['username'] ?? '');
  $password   = trim($data['password'] ?? '');

  if (!$first_name || !$last_name || !$address || !$phone || !$gender) {
    echo json_encode(['ok'=>false,'message'=>'First name, last name, address, phone and gender are required']); exit;
  }

  $check = $pdo->prepare('SELECT id FROM users WHERE username = ?');

  if ($set_credentials) {
    if (!$username || !$password) {
      echo json_encode(['ok'=>false,'message'=>'Username and password are required to set up credentials']); exit;
    }
    $check->execute([$username]);
    if ($check->fetch()) { echo json_encode(['ok'=>false,'message'=>'Username already taken']); exit; }
  } else {
    // No credentials requested: auto-generate a unique username from the phone number and a random password.
    $digits = preg_replace('/\D/', '', $phone);
    $base = 'client' . $digits;
    $username = $base;
    $suffix = 1;
    do {
      $check->execute([$username]);
      $taken = $check->fetch();
      if ($taken) { $suffix++; $username = $base . $suffix; }
    } while ($taken);
    $password = bin2hex(random_bytes(5));
  }

  $pdo->beginTransaction();
  try {
    $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)');
    $stmt->execute([$username, password_hash($password, PASSWORD_BCRYPT), 'client']);
    $user_id = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare('INSERT INTO clients (user_id, first_name, last_name, address, phone, gender) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$user_id, $first_name, $last_name, $address, $phone, $gender]);
    $client_id = (int)$pdo->lastInsertId();

    $pdo->commit();
    saveUserCredentials($pdo, $user_id, $username, $password);
    $response = ['ok'=>true, 'client_id'=>$client_id, 'username'=>$username];
    if (!$set_credentials) { $response['generated_password'] = $password; }
    echo json_encode($response);
  } catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['ok'=>false,'message'=>'Failed to create client']);
  }
  exit;
}

echo json_encode(['items'=>[]]);
