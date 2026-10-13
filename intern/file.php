<?php
// Streams one uploaded intern document to its owner (or an admin).
// The files themselves sit in uploads/interns/, which is closed to direct web access.
session_start();
// config/database.php prints a stray newline; capture it so it cannot corrupt the file bytes.
ob_start();
require_once __DIR__ . '/../config/database.php';
ob_end_clean();
require_once __DIR__ . '/../includes/intern_helpers.php';

if (!isset($_SESSION['user'])) { http_response_code(403); exit('Please log in.'); }

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT d.original_name, d.stored_name, d.intern_id, i.user_id
                       FROM intern_documents d JOIN interns i ON i.id = d.intern_id WHERE d.id = ?');
$stmt->execute([$id]);
$doc = $stmt->fetch();
if (!$doc) { http_response_code(404); exit('File not found.'); }

$role = $_SESSION['user']['role'] ?? '';
$allowed = $role === 'admin' || ($role === 'intern' && (int)$doc['user_id'] === (int)$_SESSION['user']['id']);
if (!$allowed) { http_response_code(403); exit('Forbidden.'); }

$path = intern_doc_path($doc['intern_id'], $doc['stored_name']);
if (!is_file($path)) { http_response_code(404); exit('File is missing from the server.'); }

$ext = strtolower(pathinfo($doc['stored_name'], PATHINFO_EXTENSION));
$inline = in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true);
$name = preg_replace('/[^\w .()\-]/', '_', $doc['original_name']);

while (ob_get_level()) ob_end_clean();
header('Content-Type: ' . intern_serve_mime($ext));
header('Content-Length: ' . filesize($path));
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $name . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($path);
