
<?php
session_start();
header('Content-Type: application/json');
echo json_encode([
  'auth' => isset($_SESSION['user']),
  'role' => $_SESSION['user']['role'] ?? null,
  'username' => $_SESSION['user']['username'] ?? null
]);
