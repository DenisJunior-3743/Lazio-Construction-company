<?php
// Training areas offered to interns.
// list_public is open to everyone (home page, internship page, intern portal);
// every other action is admin only.
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/intern_helpers.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? 'list_public';

if ($action === 'list_public') {
  try {
    $stmt = $pdo->query('SELECT id, name FROM internship_services WHERE is_active = 1 ORDER BY sort_order, name');
    echo json_encode(['items' => $stmt->fetchAll(), 'fee' => INTERN_FEE_UGX]); exit;
  } catch (PDOException $e) {
    // Table missing (migration not run yet): show the poster's list so the public page still works.
    $items = [];
    foreach (internship_default_services() as $name) $items[] = ['id' => 0, 'name' => $name];
    echo json_encode(['items' => $items, 'fee' => INTERN_FEE_UGX, 'fallback' => true]); exit;
  }
}

intern_json_guard();
require_admin();

if ($action === 'list') {
  $stmt = $pdo->query('SELECT id, name, sort_order, is_active FROM internship_services ORDER BY sort_order, name');
  echo json_encode(['items' => $stmt->fetchAll()]); exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];

// Names are joined with commas in letters ("A, B, and C"), so they must not contain any.
function clean_service_name($raw) {
  $name = clean_text($raw, 150);
  if (str_length($name) < 2) return [null, 'Please enter a training area name.'];
  if (strpos($name, ',') !== false) return [null, 'Names cannot contain commas - they are joined with commas in letters. Use "&" or "and" instead.'];
  return [$name, ''];
}

if ($action === 'create') {
  list($name, $err) = clean_service_name($data['name'] ?? '');
  if ($err) { echo json_encode(['ok' => false, 'message' => $err]); exit; }
  $dupe = $pdo->prepare('SELECT 1 FROM internship_services WHERE name = ?');
  $dupe->execute([$name]);
  if ($dupe->fetch()) { echo json_encode(['ok' => false, 'message' => 'That training area already exists.']); exit; }
  $next = (int)$pdo->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM internship_services')->fetchColumn();
  $pdo->prepare('INSERT INTO internship_services (name, sort_order) VALUES (?, ?)')->execute([$name, $next]);
  echo json_encode(['ok' => true, 'id' => (int)$pdo->lastInsertId()]); exit;
}

if ($action === 'update') {
  $id = (int)($data['id'] ?? 0);
  if (!$id) { echo json_encode(['ok' => false, 'message' => 'Invalid training area.']); exit; }
  if (isset($data['name'])) {
    list($name, $err) = clean_service_name($data['name']);
    if ($err) { echo json_encode(['ok' => false, 'message' => $err]); exit; }
    $dupe = $pdo->prepare('SELECT 1 FROM internship_services WHERE name = ? AND id <> ?');
    $dupe->execute([$name, $id]);
    if ($dupe->fetch()) { echo json_encode(['ok' => false, 'message' => 'That training area already exists.']); exit; }
    $pdo->prepare('UPDATE internship_services SET name = ? WHERE id = ?')->execute([$name, $id]);
  }
  if (isset($data['is_active'])) {
    $pdo->prepare('UPDATE internship_services SET is_active = ? WHERE id = ?')->execute([!empty($data['is_active']) ? 1 : 0, $id]);
  }
  echo json_encode(['ok' => true]); exit;
}

if ($action === 'delete') {
  $id = (int)($data['id'] ?? 0);
  if (!$id) { echo json_encode(['ok' => false, 'message' => 'Invalid training area.']); exit; }
  // Applications and letters keep their own copy of the names, so this is safe.
  $pdo->prepare('DELETE FROM internship_services WHERE id = ?')->execute([$id]);
  echo json_encode(['ok' => true]); exit;
}

echo json_encode(['ok' => false]);
