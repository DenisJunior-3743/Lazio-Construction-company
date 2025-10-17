<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

$action = $_GET['action'] ?? 'list_public';

if ($action === 'upload') {
  if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') { http_response_code(403); exit; }
  header('Content-Type: application/json');

  $category = $_POST['category'] ?? 'General';
  $description = $_POST['description'] ?? '';
  if (!isset($_FILES['file'])) { echo json_encode(['ok'=>false,'message'=>'No file']); exit; }

  $file = $_FILES['file'];
  $type = str_starts_with($file['type'], 'video') ? 'video' : 'image';
  $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
  $safeName = uniqid('upl_') . '.' . $ext;
  $dir = $type === 'video' ? 'uploads/videos/' : 'uploads/images/';
  $dest = $dir . $safeName;

  // Absolute path for saving
  $savePath = __DIR__ . '/../../' . $dest;

  // Make sure directory exists
  @mkdir(dirname($savePath), 0777, true);

  if (!move_uploaded_file($file['tmp_name'], $savePath)) {
    echo json_encode(['ok'=>false,'message'=>'Failed to save']); exit;
  }

  // Save path as URL for browser
  $urlPath = '/lazio_site/' . $dest;

  $stmt = $pdo->prepare('INSERT INTO uploads (type, path, category, description) VALUES (?, ?, ?, ?)');
  $stmt->execute([$type, $urlPath, $category, $description]);

  echo json_encode(['ok'=>true]); exit;
}

if ($action === 'list') {
  if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') { http_response_code(403); exit; }
  header('Content-Type: application/json');
  $stmt = $pdo->query('SELECT id, type, path, category, description, created_at, show_home FROM uploads ORDER BY id DESC');
  echo json_encode(['items' => $stmt->fetchAll(PDO::FETCH_ASSOC)]); exit;
}

if ($action === 'delete') {
  if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') { http_response_code(403); exit; }
  header('Content-Type: application/json');
  $data = json_decode(file_get_contents('php://input'), true) ?: [];
  $id = (int)($data['id'] ?? 0);
  $stmt = $pdo->prepare('SELECT path FROM uploads WHERE id = ?'); $stmt->execute([$id]);
  $path = $stmt->fetchColumn();
  if ($path) {
    @unlink($_SERVER['DOCUMENT_ROOT'] . $path);
    @unlink(__DIR__ . '/../../' . $path);
  }
  $pdo->prepare('DELETE FROM uploads WHERE id = ?')->execute([$id]);
  echo json_encode(['ok'=>true]); exit;
}

if ($action === 'list_public') {
  header('Content-Type: application/json');
  $stmt = $pdo->query('SELECT id, type, path, category, description, created_at, show_home FROM uploads WHERE deleted = 0 ORDER BY id DESC');
  echo json_encode(['items' => $stmt->fetchAll(PDO::FETCH_ASSOC)]); exit;
}

if ($action === 'toggle_home') {
  if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') { http_response_code(403); exit; }
  header('Content-Type: application/json');
  $data = json_decode(file_get_contents('php://input'), true) ?: [];
  $id = (int)($data['id'] ?? 0);
  $show = !empty($data['show']) ? 1 : 0;
  $pdo->prepare('UPDATE uploads SET show_home = ? WHERE id = ?')->execute([$show, $id]);
  echo json_encode(['ok'=>true]); exit;
}

if ($action === 'edit_desc') {
  if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') { http_response_code(403); exit; }
  header('Content-Type: application/json');
  $data = json_decode(file_get_contents('php://input'), true) ?: [];
  $id = (int)($data['id'] ?? 0);
  $desc = $data['desc'] ?? '';
  $pdo->prepare('UPDATE uploads SET description = ? WHERE id = ?')->execute([$desc, $id]);
  echo json_encode(['ok'=>true]); exit;
}
