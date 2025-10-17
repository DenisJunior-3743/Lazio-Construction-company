<?php

session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') { http_response_code(403); exit; }
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

if ($action === 'list') {
  $stmt = $pdo->query('SELECT id, name FROM categories ORDER BY name');
  echo json_encode(['items'=>$stmt->fetchAll()]); exit;
}

if ($action === 'create') {
  $data = json_decode(file_get_contents('php://input'), true) ?: [];
  $stmt = $pdo->prepare('INSERT INTO categories (name) VALUES (?)');
  $stmt->execute([$data['name'] ?? '']);
  echo json_encode(['ok'=>true]); exit;
}

if ($action === 'update') {
  $data = json_decode(file_get_contents('php://input'), true) ?: [];
  $stmt = $pdo->prepare('UPDATE categories SET name=? WHERE id=?');
  $stmt->execute([$data['name'] ?? '', (int)($data['id'] ?? 0)]);
  echo json_encode(['ok'=>true]); exit;
}

if ($action === 'delete') {
  $data = json_decode(file_get_contents('php://input'), true) ?: [];
  $stmt = $pdo->prepare('DELETE FROM categories WHERE id=?');
  $stmt->execute([(int)($data['id'] ?? 0)]);
  echo json_encode(['ok'=>true]); exit;
}

echo json_encode(['ok'=>false]);