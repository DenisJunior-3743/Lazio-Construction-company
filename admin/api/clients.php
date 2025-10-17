
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

echo json_encode(['items'=>[]]);
