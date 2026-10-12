<?php
function saveUserCredentials($pdo, $user_id, $username, $password) {
  // Get full name from clients table if exists
  $stmt = $pdo->prepare('SELECT first_name, last_name FROM clients WHERE user_id = ?');
  $stmt->execute([$user_id]);
  $client = $stmt->fetch();
  $full_name = $client ? ($client['first_name'] . ' ' . $client['last_name']) : $username;
  $data = [
    'user_id' => $user_id,
    'username' => $username,
    'full_name' => $full_name,
    'password' => $password,
    'updated_at' => date('Y-m-d H:i:s')
  ];
  $jsonFile = __DIR__ . '/../data/user_credentials.json';
  $all = [];
  if (file_exists($jsonFile)) {
    $all = json_decode(file_get_contents($jsonFile), true) ?: [];
  }
  $all[$user_id] = $data;
  file_put_contents($jsonFile, json_encode($all, JSON_PRETTY_PRINT));
}
