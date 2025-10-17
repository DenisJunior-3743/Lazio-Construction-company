
<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'client') { http_response_code(403); exit; }
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');
$cid = (int)($_SESSION['user']['client_id'] ?? 0);
$stmt = $pdo->prepare('SELECT id as invoice_id, total, created_at FROM invoices WHERE client_id = ? AND sent_to_client = 1 ORDER BY id DESC');
$stmt->execute([$cid]);
$rows = $stmt->fetchAll();
echo json_encode(['items'=>array_map(function($r){ return [
  'invoice_id'=>(int)$r['invoice_id'],
  'total'=>(float)$r['total'],
  'created_at'=>$r['created_at']
]; }, $rows)]);
