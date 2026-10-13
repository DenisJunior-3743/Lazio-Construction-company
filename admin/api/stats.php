
<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') { http_response_code(403); exit; }
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

$counts = ['clients'=>0,'invoices'=>0,'items'=>0,'uploads'=>0,'interns'=>0,'pending_applications'=>0];
$counts['clients'] = (int)$pdo->query('SELECT COUNT(*) FROM clients')->fetchColumn();
// Internship tables only exist once sql/internship_module.sql has been run.
try {
  $counts['interns'] = (int)$pdo->query('SELECT COUNT(*) FROM interns')->fetchColumn();
  $counts['pending_applications'] = (int)$pdo->query("SELECT COUNT(*) FROM intern_applications WHERE status = 'pending'")->fetchColumn();
} catch (PDOException $e) {}
$counts['invoices'] = (int)$pdo->query('SELECT COUNT(*) FROM invoices')->fetchColumn();
$counts['items']    = (int)$pdo->query('SELECT COUNT(*) FROM items')->fetchColumn();
$counts['uploads']  = (int)$pdo->query('SELECT COUNT(*) FROM uploads')->fetchColumn();

echo json_encode($counts);
