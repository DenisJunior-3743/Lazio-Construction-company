<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') { http_response_code(403); exit; }
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

if ($action === 'list') {
  $stmt = $pdo->query('
    SELECT a.id, a.ceo_name, a.created_at,
           CONCAT(c.first_name, " ", c.last_name) as client_name,
           (SELECT COUNT(*) FROM agreement_phases p WHERE p.agreement_id = a.id) as phase_count
    FROM agreements a
    JOIN clients c ON c.id = a.client_id
    ORDER BY a.id DESC
  ');
  echo json_encode(['agreements' => $stmt->fetchAll()]); exit;
}

if ($action === 'create') {
  $data = json_decode(file_get_contents('php://input'), true) ?: [];
  $client_id = (int)($data['client_id'] ?? 0);
  $ceo_name = trim($data['ceo_name'] ?? '');
  $phases = $data['phases'] ?? [];
  $labour_amount = (float)($data['labour_amount'] ?? 0);
  $commitment_fee = (float)($data['commitment_fee'] ?? 200000);
  $deposit_percent = (float)($data['deposit_percent'] ?? 20);
  $representatives = $data['representatives'] ?? [];

  if (!$client_id || !$ceo_name || !$phases) {
    echo json_encode(['ok'=>false,'message'=>'Client, CEO name and at least one phase are required']); exit;
  }
  if ($labour_amount <= 0) {
    echo json_encode(['ok'=>false,'message'=>'Labour amount is required']); exit;
  }

  $pdo->beginTransaction();
  try {
    $stmt = $pdo->prepare('INSERT INTO agreements (client_id, ceo_name, labour_amount, commitment_fee, deposit_percent) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$client_id, $ceo_name, $labour_amount, $commitment_fee, $deposit_percent]);
    $agreement_id = (int)$pdo->lastInsertId();

    $stmt2 = $pdo->prepare('INSERT INTO agreement_phases (agreement_id, phase_number, content) VALUES (?, ?, ?)');
    $saved = 0;
    foreach (array_values($phases) as $content) {
      $content = trim((string)$content);
      if ($content === '') continue;
      $saved++;
      $stmt2->execute([$agreement_id, $saved, $content]);
    }

    if (!$saved) {
      $pdo->rollBack();
      echo json_encode(['ok'=>false,'message'=>'At least one phase must have content']); exit;
    }

    $stmt3 = $pdo->prepare('INSERT INTO agreement_representatives (agreement_id, side, name, position, contact, sort_order) VALUES (?, ?, ?, ?, ?, ?)');
    foreach (['company', 'client'] as $side) {
      $order = 0;
      foreach ((array)($representatives[$side] ?? []) as $rep) {
        $name = trim((string)($rep['name'] ?? ''));
        $position = trim((string)($rep['position'] ?? ''));
        $contact = trim((string)($rep['contact'] ?? ''));
        if ($name === '' || $position === '') continue;
        $order++;
        $stmt3->execute([$agreement_id, $side, $name, $position, $contact !== '' ? $contact : null, $order]);
      }
    }

    $pdo->commit();
    echo json_encode(['ok'=>true, 'agreement_id'=>$agreement_id]);
  } catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['ok'=>false,'message'=>'Failed to save agreement']);
  }
  exit;
}

if ($action === 'delete') {
  $data = json_decode(file_get_contents('php://input'), true) ?: [];
  $id = (int)($data['id'] ?? 0);
  if (!$id) { echo json_encode(['ok'=>false,'message'=>'Invalid agreement ID']); exit; }

  $pdo->prepare('DELETE FROM agreements WHERE id = ?')->execute([$id]);
  echo json_encode(['ok'=>true]); exit;
}

echo json_encode(['ok'=>false]);
