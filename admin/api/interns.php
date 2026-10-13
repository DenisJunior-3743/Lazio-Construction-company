<?php
// Admin API: interns, their applications and their documents.
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/intern_helpers.php';
intern_json_guard();
require_admin();
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$data = json_decode(file_get_contents('php://input'), true) ?: [];

// All documents an intern has uploaded (newest first).
function admin_documents(PDO $pdo, $internId) {
  $stmt = $pdo->prepare('SELECT id, application_id, doc_type, original_name, mime_type, file_size, created_at
                         FROM intern_documents WHERE intern_id = ? ORDER BY id DESC');
  $stmt->execute([$internId]);
  return array_map(function ($d) {
    return [
      'id' => (int)$d['id'],
      'application_id' => $d['application_id'] !== null ? (int)$d['application_id'] : null,
      'doc_type' => $d['doc_type'],
      'original_name' => $d['original_name'],
      'file_size' => (int)$d['file_size'],
      'created_at' => $d['created_at'],
    ];
  }, $stmt->fetchAll());
}

function application_row(array $a, array $docs) {
  return [
    'id' => (int)$a['id'],
    'intern_id' => (int)$a['intern_id'],
    'full_name' => $a['full_name'] ?? null,
    'reg_number' => $a['reg_number'] ?? null,
    'institution' => $a['institution'] ?? null,
    'services' => decode_services($a['services']),
    'institution_modules' => $a['institution_modules'],
    'start_date' => $a['start_date'],
    'end_date' => $a['end_date'],
    'message' => $a['message'],
    'status' => $a['status'],
    'fee_paid' => (int)$a['fee_paid'],
    'fee_reference' => $a['fee_reference'],
    'admin_note' => $a['admin_note'],
    'created_at' => $a['created_at'],
    'letters' => isset($a['letters']) ? (int)$a['letters'] : 0,
    'documents' => $docs,
  ];
}

if ($action === 'list') {
  $stmt = $pdo->query('
    SELECT i.id, i.reg_number, i.full_name, i.institution, i.course, i.phone, i.registered_by, i.created_at,
           (SELECT COUNT(*) FROM intern_documents d WHERE d.intern_id = i.id) AS documents,
           (SELECT COUNT(*) FROM intern_applications a WHERE a.intern_id = i.id) AS applications,
           (SELECT COUNT(*) FROM acceptance_letters l WHERE l.intern_id = i.id) AS letters
    FROM interns i ORDER BY i.id DESC');
  echo json_encode(['items' => $stmt->fetchAll()]); exit;
}

if ($action === 'options') {
  $stmt = $pdo->query('SELECT id, reg_number, full_name, institution, course FROM interns ORDER BY full_name');
  echo json_encode(['items' => $stmt->fetchAll()]); exit;
}

if ($action === 'get') {
  $id = (int)($_GET['id'] ?? 0);
  $stmt = $pdo->prepare('SELECT id, user_id, reg_number, full_name, institution, course, phone, registered_by, created_at FROM interns WHERE id = ?');
  $stmt->execute([$id]);
  $intern = $stmt->fetch();
  if (!$intern) { echo json_encode(['ok' => false, 'message' => 'Intern not found.']); exit; }

  $docs = admin_documents($pdo, $id);
  $apps = $pdo->prepare('SELECT a.*, (SELECT COUNT(*) FROM acceptance_letters l WHERE l.application_id = a.id) AS letters
                         FROM intern_applications a WHERE a.intern_id = ? ORDER BY a.id DESC');
  $apps->execute([$id]);
  $applications = array_map(function ($a) use ($docs) {
    $own = array_values(array_filter($docs, function ($d) use ($a) { return $d['application_id'] === (int)$a['id']; }));
    return application_row($a, $own);
  }, $apps->fetchAll());

  $lt = $pdo->prepare('SELECT id, letter_date, start_date, end_date, sent_to_intern FROM acceptance_letters WHERE intern_id = ? ORDER BY id DESC');
  $lt->execute([$id]);
  $letters = array_map(function ($l) {
    return ['id' => (int)$l['id'], 'ref' => letter_ref($l['id'], $l['letter_date']), 'start_date' => $l['start_date'],
            'end_date' => $l['end_date'], 'sent_to_intern' => (int)$l['sent_to_intern']];
  }, $lt->fetchAll());

  echo json_encode(['ok' => true, 'intern' => $intern, 'documents' => $docs, 'applications' => $applications, 'letters' => $letters]); exit;
}

if ($action === 'create') {
  $res = intern_register($pdo, $data, 'admin');
  echo json_encode($res); exit;
}

if ($action === 'update') {
  $id = (int)($data['id'] ?? 0);
  $name   = clean_text($data['full_name'] ?? '', 150);
  $reg    = normalize_reg_number($data['reg_number'] ?? '');
  $inst   = clean_text($data['institution'] ?? '', 200);
  $course = clean_text($data['course'] ?? '', 200);
  $phone  = trim((string)($data['phone'] ?? ''));

  $stmt = $pdo->prepare('SELECT id, user_id FROM interns WHERE id = ?');
  $stmt->execute([$id]);
  $intern = $stmt->fetch();
  if (!$intern) { echo json_encode(['ok' => false, 'message' => 'Intern not found.']); exit; }
  if ($name === '' || $reg === '' || $inst === '' || $course === '' || $phone === '') {
    echo json_encode(['ok' => false, 'message' => 'All fields are required.']); exit;
  }
  if (!valid_reg_number($reg)) { echo json_encode(['ok' => false, 'message' => 'Registration number may only contain letters, numbers and / - . _']); exit; }
  if (!valid_phone($phone)) { echo json_encode(['ok' => false, 'message' => 'Phone must be exactly 10 digits.']); exit; }

  $dupe = $pdo->prepare('SELECT 1 FROM users WHERE username = ? AND id <> ?');
  $dupe->execute([$reg, $intern['user_id']]);
  if ($dupe->fetch()) { echo json_encode(['ok' => false, 'message' => 'Another account already uses that registration number.']); exit; }

  $pdo->beginTransaction();
  try {
    $pdo->prepare('UPDATE users SET username = ? WHERE id = ?')->execute([$reg, $intern['user_id']]);
    $pdo->prepare('UPDATE interns SET reg_number = ?, full_name = ?, institution = ?, course = ?, phone = ? WHERE id = ?')
        ->execute([$reg, $name, $inst, $course, $phone, $id]);
    $pdo->commit();
  } catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['ok' => false, 'message' => 'Could not update the intern.']); exit;
  }
  echo json_encode(['ok' => true]); exit;
}

if ($action === 'reset_password') {
  $id = (int)($data['id'] ?? 0);
  $password = trim((string)($data['password'] ?? ''));
  $stmt = $pdo->prepare('SELECT user_id FROM interns WHERE id = ?');
  $stmt->execute([$id]);
  $userId = $stmt->fetchColumn();
  if (!$userId) { echo json_encode(['ok' => false, 'message' => 'Intern not found.']); exit; }
  $generated = null;
  if ($password === '') { $password = $generated = generate_password(8); }
  elseif (strlen($password) < INTERN_MIN_PASSWORD) { echo json_encode(['ok' => false, 'message' => 'Password must be at least ' . INTERN_MIN_PASSWORD . ' characters.']); exit; }
  $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_BCRYPT), $userId]);
  $res = ['ok' => true];
  if ($generated !== null) $res['generated_password'] = $generated;
  echo json_encode($res); exit;
}

if ($action === 'delete') {
  $id = (int)($data['id'] ?? 0);
  $stmt = $pdo->prepare('SELECT user_id FROM interns WHERE id = ?');
  $stmt->execute([$id]);
  $userId = $stmt->fetchColumn();
  if (!$userId) { echo json_encode(['ok' => false, 'message' => 'Intern not found.']); exit; }
  intern_delete_all_files($pdo, $id);
  // Deleting the login cascades to the intern, applications, documents and letters.
  $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
  echo json_encode(['ok' => true]); exit;
}

/* ---------- Applications ---------- */

if ($action === 'applications') {
  $where = []; $params = [];
  $status = $_GET['status'] ?? '';
  if (in_array($status, ['pending', 'accepted', 'rejected'], true)) { $where[] = 'a.status = ?'; $params[] = $status; }
  $sql = 'SELECT a.*, i.full_name, i.reg_number, i.institution,
                 (SELECT COUNT(*) FROM acceptance_letters l WHERE l.application_id = a.id) AS letters
          FROM intern_applications a JOIN interns i ON i.id = a.intern_id'
       . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY a.id DESC';
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll();

  // Documents attached to these applications (e.g. the uploaded application letter).
  $docsByApp = [];
  if ($rows) {
    $ids = array_map(function ($r) { return (int)$r['id']; }, $rows);
    $in = implode(',', array_fill(0, count($ids), '?'));
    $d = $pdo->prepare("SELECT id, application_id, doc_type, original_name, file_size, created_at FROM intern_documents WHERE application_id IN ($in)");
    $d->execute($ids);
    foreach ($d->fetchAll() as $doc) {
      $docsByApp[(int)$doc['application_id']][] = [
        'id' => (int)$doc['id'], 'application_id' => (int)$doc['application_id'], 'doc_type' => $doc['doc_type'],
        'original_name' => $doc['original_name'], 'file_size' => (int)$doc['file_size'], 'created_at' => $doc['created_at'],
      ];
    }
  }
  $items = array_map(function ($r) use ($docsByApp) { return application_row($r, $docsByApp[(int)$r['id']] ?? []); }, $rows);
  echo json_encode(['items' => $items]); exit;
}

if ($action === 'application_update') {
  $id = (int)($data['id'] ?? 0);
  $stmt = $pdo->prepare('SELECT id FROM intern_applications WHERE id = ?');
  $stmt->execute([$id]);
  if (!$stmt->fetch()) { echo json_encode(['ok' => false, 'message' => 'Application not found.']); exit; }

  $sets = []; $params = [];
  if (isset($data['status'])) {
    if (!in_array($data['status'], ['pending', 'accepted', 'rejected'], true)) { echo json_encode(['ok' => false, 'message' => 'Invalid status.']); exit; }
    $sets[] = 'status = ?'; $params[] = $data['status'];
  }
  if (isset($data['fee_paid'])) { $sets[] = 'fee_paid = ?'; $params[] = !empty($data['fee_paid']) ? 1 : 0; }
  if (array_key_exists('fee_reference', $data)) { $sets[] = 'fee_reference = ?'; $params[] = clean_text($data['fee_reference'], 100) ?: null; }
  if (array_key_exists('admin_note', $data)) { $sets[] = 'admin_note = ?'; $params[] = clean_text($data['admin_note'], 255) ?: null; }
  if (!$sets) { echo json_encode(['ok' => false, 'message' => 'Nothing to update.']); exit; }
  $params[] = $id;
  $pdo->prepare('UPDATE intern_applications SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);
  echo json_encode(['ok' => true]); exit;
}

if ($action === 'application_delete') {
  $id = (int)($data['id'] ?? 0);
  // Files attached to the application stay in the intern's documents (application_id becomes NULL).
  $pdo->prepare('DELETE FROM intern_applications WHERE id = ?')->execute([$id]);
  echo json_encode(['ok' => true]); exit;
}

/* ---------- Documents ---------- */

if ($action === 'document_delete') {
  $id = (int)($data['id'] ?? 0);
  $stmt = $pdo->prepare('SELECT intern_id, stored_name FROM intern_documents WHERE id = ?');
  $stmt->execute([$id]);
  $doc = $stmt->fetch();
  if ($doc) {
    @unlink(intern_doc_path($doc['intern_id'], $doc['stored_name']));
    $pdo->prepare('DELETE FROM intern_documents WHERE id = ?')->execute([$id]);
  }
  echo json_encode(['ok' => true]); exit;
}

echo json_encode(['ok' => false]);
