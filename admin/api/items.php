<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') { http_response_code(403); exit; }
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
  $stmt = $pdo->query('SELECT i.id, c.name AS category, i.name, i.unit_price, i.category_id
                       FROM items i JOIN categories c ON i.category_id = c.id ORDER BY c.name, i.id DESC');
  echo json_encode(['items'=>$stmt->fetchAll()]); exit;
}

if ($action === 'create') {
  $data = json_decode(file_get_contents('php://input'), true) ?: [];
  $category_id = (int)($data['category_id'] ?? 0);
  $name = trim($data['name'] ?? '');
  $unit_price = (int)($data['unit_price'] ?? 0);

  // Check for duplicate item name in same category
  $stmt = $pdo->prepare('SELECT COUNT(*) FROM items WHERE name = ? AND category_id = ?');
  $stmt->execute([$name, $category_id]);
  if ($stmt->fetchColumn() > 0) {
    echo json_encode(['ok'=>false, 'message'=>'Item already exists in this category']); exit;
  }

  $stmt = $pdo->prepare('INSERT INTO items (category_id, name, unit_price) VALUES (?, ?, ?)');
  $stmt->execute([$category_id, $name, $unit_price]);
  echo json_encode(['ok'=>true]); exit;
}

if ($action === 'update') {
  $data = json_decode(file_get_contents('php://input'), true) ?: [];
  $id = (int)($data['id'] ?? 0);
  $name = trim($data['name'] ?? '');
  $unit_price = (int)($data['unit_price'] ?? 0);

  // Check for duplicate name in same category (excluding current item)
  $stmt = $pdo->prepare('SELECT category_id FROM items WHERE id = ?');
  $stmt->execute([$id]);
  $category_id = $stmt->fetchColumn();
  $stmt = $pdo->prepare('SELECT COUNT(*) FROM items WHERE name = ? AND category_id = ? AND id != ?');
  $stmt->execute([$name, $category_id, $id]);
  if ($stmt->fetchColumn() > 0) {
    echo json_encode(['ok'=>false, 'message'=>'Another item with this name exists in this category']); exit;
  }

  $stmt = $pdo->prepare('UPDATE items SET name = ?, unit_price = ? WHERE id = ?');
  $stmt->execute([$name, $unit_price, $id]);
  echo json_encode(['ok'=>true]); exit;
}

if ($action === 'delete') {
  $data = json_decode(file_get_contents('php://input'), true) ?: [];
  $id = (int)($data['id'] ?? 0);
  $stmt = $pdo->prepare('DELETE FROM items WHERE id = ?');
  $stmt->execute([$id]);
  echo json_encode(['ok'=>true]); exit;
}

echo json_encode(['ok'=>false]);
