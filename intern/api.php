<?php
// Intern portal API. Every action works on the signed-in intern only: the
// intern is always taken from the session, never from the request.
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/intern_helpers.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');
intern_json_guard();

$intern = require_intern($pdo);
$internId = (int)$intern['id'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'profile';
$data = json_decode(file_get_contents('php://input'), true) ?: [];

function doc_row(array $d) {
  return [
    'id' => (int)$d['id'],
    'application_id' => $d['application_id'] !== null ? (int)$d['application_id'] : null,
    'doc_type' => $d['doc_type'],
    'original_name' => $d['original_name'],
    'file_size' => (int)$d['file_size'],
    'created_at' => $d['created_at'],
  ];
}

function profile_payload(array $i) {
  return [
    'id' => (int)$i['id'], 'reg_number' => $i['reg_number'], 'full_name' => $i['full_name'],
    'institution' => $i['institution'], 'course' => $i['course'], 'phone' => $i['phone'],
    'created_at' => $i['created_at'],
  ];
}

if ($action === 'profile') {
  echo json_encode(['ok' => true, 'intern' => profile_payload($intern), 'fee' => INTERN_FEE_UGX,
                    'doc_types' => intern_doc_types(), 'max_upload_mb' => INTERN_MAX_UPLOAD_BYTES / 1048576]); exit;
}

if ($action === 'update_profile') {
  $name   = clean_text($data['full_name'] ?? '', 150);
  $inst   = clean_text($data['institution'] ?? '', 200);
  $course = clean_text($data['course'] ?? '', 200);
  $phone  = trim((string)($data['phone'] ?? ''));
  if (str_length($name) < 2 || $inst === '' || $course === '') { echo json_encode(['ok' => false, 'message' => 'Name, institution and course are required.']); exit; }
  if (!valid_phone($phone)) { echo json_encode(['ok' => false, 'message' => 'Phone must be exactly 10 digits.']); exit; }
  // The registration number is the username, so only the office can change it.
  $pdo->prepare('UPDATE interns SET full_name = ?, institution = ?, course = ?, phone = ? WHERE id = ?')
      ->execute([$name, $inst, $course, $phone, $internId]);
  echo json_encode(['ok' => true]); exit;
}

if ($action === 'change_password') {
  $current = (string)($data['current_password'] ?? '');
  $new = trim((string)($data['new_password'] ?? ''));
  $hash = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
  $hash->execute([(int)$_SESSION['user']['id']]);
  if (!password_verify($current, (string)$hash->fetchColumn())) { echo json_encode(['ok' => false, 'message' => 'Your current password is incorrect.']); exit; }
  if (strlen($new) < INTERN_MIN_PASSWORD) { echo json_encode(['ok' => false, 'message' => 'The new password must be at least ' . INTERN_MIN_PASSWORD . ' characters.']); exit; }
  $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_BCRYPT), (int)$_SESSION['user']['id']]);
  echo json_encode(['ok' => true]); exit;
}

/* ---------- Applications ---------- */

if ($action === 'applications') {
  $stmt = $pdo->prepare('SELECT a.*, (SELECT COUNT(*) FROM acceptance_letters l WHERE l.application_id = a.id AND l.sent_to_intern = 1) AS letters
                         FROM intern_applications a WHERE a.intern_id = ? ORDER BY a.id DESC');
  $stmt->execute([$internId]);
  $rows = $stmt->fetchAll();
  $docs = $pdo->prepare('SELECT id, application_id, doc_type, original_name, file_size, created_at FROM intern_documents WHERE intern_id = ? AND application_id IS NOT NULL');
  $docs->execute([$internId]);
  $byApp = [];
  foreach ($docs->fetchAll() as $d) $byApp[(int)$d['application_id']][] = doc_row($d);
  $items = array_map(function ($a) use ($byApp) {
    return [
      'id' => (int)$a['id'],
      'services' => decode_services($a['services']),
      'institution_modules' => $a['institution_modules'],
      'start_date' => $a['start_date'],
      'end_date' => $a['end_date'],
      'message' => $a['message'],
      'status' => $a['status'],
      'fee_paid' => (int)$a['fee_paid'],
      'fee_reference' => $a['fee_reference'],
      'admin_note' => $a['admin_note'],
      'letters' => (int)$a['letters'],
      'created_at' => $a['created_at'],
      'documents' => $byApp[(int)$a['id']] ?? [],
    ];
  }, $rows);
  echo json_encode(['items' => $items]); exit;
}

if ($action === 'application_create') {
  // multipart/form-data: fields + optional application letter file
  $start = (string)($_POST['start_date'] ?? '');
  $end   = (string)($_POST['end_date'] ?? '');
  $err = check_period($start, $end);
  if ($err) { echo json_encode(['ok' => false, 'message' => $err]); exit; }

  $services = clean_service_selection($pdo, $_POST['services'] ?? [], true);
  if (!$services) { echo json_encode(['ok' => false, 'message' => 'Select at least one training area.']); exit; }

  $pending = $pdo->prepare("SELECT COUNT(*) FROM intern_applications WHERE intern_id = ? AND status = 'pending'");
  $pending->execute([$internId]);
  if ((int)$pending->fetchColumn() > 0) {
    echo json_encode(['ok' => false, 'message' => 'You already have an application waiting for a decision. You can withdraw it first if you need to change it.']); exit;
  }

  $modules = clean_text($_POST['institution_modules'] ?? '', 1000);
  $message = clean_text($_POST['message'] ?? '', 1000);
  $ref     = clean_text($_POST['fee_reference'] ?? '', 100);

  $pdo->prepare('INSERT INTO intern_applications (intern_id, services, institution_modules, start_date, end_date, message, fee_reference) VALUES (?, ?, ?, ?, ?, ?, ?)')
      ->execute([$internId, json_encode($services, JSON_UNESCAPED_UNICODE), $modules !== '' ? $modules : null, $start, $end, $message !== '' ? $message : null, $ref !== '' ? $ref : null]);
  $appId = (int)$pdo->lastInsertId();

  $warning = '';
  if (isset($_FILES['application_letter']) && $_FILES['application_letter']['error'] !== UPLOAD_ERR_NO_FILE) {
    list($ok, $msg) = intern_store_upload($pdo, $internId, $appId, 'application_letter', $_FILES['application_letter']);
    if (!$ok) $warning = 'Your application was submitted, but the application letter was not uploaded: ' . $msg . ' You can upload it from the Documents tab.';
  }
  echo json_encode(['ok' => true, 'application_id' => $appId, 'warning' => $warning]); exit;
}

if ($action === 'application_reference') {
  $id = (int)($data['id'] ?? 0);
  $ref = clean_text($data['fee_reference'] ?? '', 100);
  $stmt = $pdo->prepare("UPDATE intern_applications SET fee_reference = ? WHERE id = ? AND intern_id = ? AND status = 'pending' AND fee_paid = 0");
  $stmt->execute([$ref !== '' ? $ref : null, $id, $internId]);
  echo json_encode(['ok' => true]); exit;
}

if ($action === 'application_delete') {
  // Only an application that has not been decided yet can be withdrawn.
  $id = (int)($data['id'] ?? 0);
  $stmt = $pdo->prepare("DELETE FROM intern_applications WHERE id = ? AND intern_id = ? AND status = 'pending'");
  $stmt->execute([$id, $internId]);
  echo json_encode(['ok' => $stmt->rowCount() > 0, 'message' => $stmt->rowCount() ? '' : 'Only applications that are still pending can be withdrawn.']); exit;
}

/* ---------- Documents ---------- */

if ($action === 'documents') {
  $stmt = $pdo->prepare('SELECT id, application_id, doc_type, original_name, file_size, created_at FROM intern_documents WHERE intern_id = ? ORDER BY id DESC');
  $stmt->execute([$internId]);
  echo json_encode(['items' => array_map('doc_row', $stmt->fetchAll())]); exit;
}

if ($action === 'document_upload') {
  $type = (string)($_POST['doc_type'] ?? 'other');
  $appId = (int)($_POST['application_id'] ?? 0);
  if ($appId) {
    $own = $pdo->prepare('SELECT 1 FROM intern_applications WHERE id = ? AND intern_id = ?');
    $own->execute([$appId, $internId]);
    if (!$own->fetch()) $appId = 0;
  }
  list($ok, $msg, $docId) = intern_store_upload($pdo, $internId, $appId ?: null, $type, $_FILES['file'] ?? null);
  echo json_encode(['ok' => $ok, 'message' => $msg, 'id' => $docId]); exit;
}

if ($action === 'document_delete') {
  $id = (int)($data['id'] ?? 0);
  $stmt = $pdo->prepare('SELECT stored_name FROM intern_documents WHERE id = ? AND intern_id = ?');
  $stmt->execute([$id, $internId]);
  $stored = $stmt->fetchColumn();
  if ($stored) {
    @unlink(intern_doc_path($internId, $stored));
    $pdo->prepare('DELETE FROM intern_documents WHERE id = ?')->execute([$id]);
  }
  echo json_encode(['ok' => true]); exit;
}

/* ---------- Letters ---------- */

if ($action === 'letters') {
  $stmt = $pdo->prepare('SELECT * FROM acceptance_letters WHERE intern_id = ? AND sent_to_intern = 1 ORDER BY id DESC');
  $stmt->execute([$internId]);
  $items = array_map(function ($row) {
    $p = letter_payload($row);
    return ['id' => $p['id'], 'ref' => $p['ref'], 'letter_date' => $p['letter_date'], 'start_date' => $p['start_date'],
            'end_date' => $p['end_date'], 'services' => $p['services']];
  }, $stmt->fetchAll());
  echo json_encode(['items' => $items]); exit;
}

echo json_encode(['ok' => false, 'message' => 'Unknown action.']);
