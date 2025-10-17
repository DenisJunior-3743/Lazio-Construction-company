<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') { http_response_code(403); exit; }
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? 'create';

if ($action === 'create') {
  $data = json_decode(file_get_contents('php://input'), true) ?: [];
  $client_id = (int)($data['client_id'] ?? 0);
  $items = $data['items'] ?? [];

  if (!$client_id || !$items) { echo json_encode(['ok'=>false]); exit; }

  $pdo->beginTransaction();
  try {
    $stmt = $pdo->prepare('INSERT INTO invoices (client_id, total) VALUES (?, 0)');
    $stmt->execute([$client_id]);
    $invoice_id = (int)$pdo->lastInsertId();

    $total = 0;
    foreach ($items as $it) {
      $item_id = (int)$it['item_id'];
      $qty = (int)$it['quantity'];
      $unit = (int)$it['unit_price'];
      $subtotal = $qty * $unit;
      $total += $subtotal;

      $stmt2 = $pdo->prepare('INSERT INTO invoice_items (invoice_id, item_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)');
      $stmt2->execute([$invoice_id, $item_id, $qty, $unit, $subtotal]);
    }

    $upd = $pdo->prepare('UPDATE invoices SET total = ? WHERE id = ?');
    $upd->execute([$total, $invoice_id]);

    $pdo->commit();
    echo json_encode(['ok'=>true,'invoice_id'=>$invoice_id]);
  } catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['ok'=>false]);
  }
  exit;
}

if ($action === 'update_quantity') {
  $data = json_decode(file_get_contents('php://input'), true) ?: [];
  $id = (int)($data['id'] ?? 0);
  $qty = (int)($data['quantity'] ?? 1);

  $stmt = $pdo->prepare('UPDATE invoice_items SET quantity = ?, subtotal = quantity * unit_price WHERE id = ?');
  $stmt->execute([$qty, $id]);
  echo json_encode(['ok'=>true]); exit;
}

if ($action === 'delete_item') {
  $data = json_decode(file_get_contents('php://input'), true) ?: [];
  $id = (int)($data['id'] ?? 0);
  $stmt = $pdo->prepare('DELETE FROM invoice_items WHERE id = ?');
  $stmt->execute([$id]);
  echo json_encode(['ok'=>true]); exit;
}

if ($action === 'list') {
  $stmt = $pdo->query('
    SELECT i.id, i.total, i.created_at, i.sent_to_client,
           CONCAT(c.first_name, " ", c.last_name) as client_name
    FROM invoices i 
    JOIN clients c ON c.id = i.client_id 
    ORDER BY i.id DESC
  ');
  echo json_encode(['invoices' => $stmt->fetchAll()]); exit;
}

if ($action === 'send_to_client') {
  $data = json_decode(file_get_contents('php://input'), true) ?: [];
  $invoice_id = (int)($data['invoice_id'] ?? 0);
  
  if (!$invoice_id) { echo json_encode(['ok'=>false,'message'=>'Invalid invoice ID']); exit; }
  
  $stmt = $pdo->prepare('UPDATE invoices SET sent_to_client = 1 WHERE id = ?');
  $stmt->execute([$invoice_id]);
  echo json_encode(['ok'=>true]); exit;
}

if ($action === 'delete') {
  $data = json_decode(file_get_contents('php://input'), true) ?: [];
  $invoice_id = (int)($data['invoice_id'] ?? 0);
  
  if (!$invoice_id) { echo json_encode(['ok'=>false,'message'=>'Invalid invoice ID']); exit; }
  
  $pdo->beginTransaction();
  try {
    // Delete invoice items first
    $pdo->prepare('DELETE FROM invoice_items WHERE invoice_id = ?')->execute([$invoice_id]);
    // Delete invoice
    $pdo->prepare('DELETE FROM invoices WHERE id = ?')->execute([$invoice_id]);
    $pdo->commit();
    echo json_encode(['ok'=>true]); exit;
  } catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['ok'=>false,'message'=>'Failed to delete invoice']); exit;
  }
}

if ($action === 'undo_delete') {
  $data = json_decode(file_get_contents('php://input'), true) ?: [];
  $id = (int)($data['id'] ?? 0);
  $pdo->prepare('UPDATE invoices SET deleted=0 WHERE id=?')->execute([$id]);
  echo json_encode(['ok'=>true]); exit;
}

echo json_encode(['ok'=>false]);
