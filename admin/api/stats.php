
<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') { http_response_code(403); exit; }
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

$counts = ['clients'=>0,'invoices'=>0,'items'=>0,'uploads'=>0];
$counts['clients'] = (int)$pdo->query('SELECT COUNT(*) FROM clients')->fetchColumn();
$counts['invoices'] = (int)$pdo->query('SELECT COUNT(*) FROM invoices')->fetchColumn();
$counts['items']    = (int)$pdo->query('SELECT COUNT(*) FROM items')->fetchColumn();
$counts['uploads']  = (int)$pdo->query('SELECT COUNT(*) FROM uploads')->fetchColumn();

echo json_encode($counts);
