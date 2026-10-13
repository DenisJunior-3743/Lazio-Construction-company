<?php
// Admin API: acceptance letters for interns.
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/intern_helpers.php';
intern_json_guard();
require_admin();
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

if ($action === 'list') {
  $stmt = $pdo->query('SELECT id, intern_id, recipient_name, reg_number, institution, letter_date, start_date, end_date, services, sent_to_intern, created_at
                       FROM acceptance_letters ORDER BY id DESC');
  $items = array_map(function ($l) {
    return [
      'id' => (int)$l['id'],
      'ref' => letter_ref($l['id'], $l['letter_date']),
      'intern_id' => (int)$l['intern_id'],
      'recipient_name' => $l['recipient_name'],
      'reg_number' => $l['reg_number'],
      'institution' => $l['institution'],
      'letter_date' => $l['letter_date'],
      'start_date' => $l['start_date'],
      'end_date' => $l['end_date'],
      'service_count' => count(decode_services($l['services'])),
      'sent_to_intern' => (int)$l['sent_to_intern'],
    ];
  }, $stmt->fetchAll());
  echo json_encode(['items' => $items]); exit;
}

if ($action === 'get') {
  $stmt = $pdo->prepare('SELECT * FROM acceptance_letters WHERE id = ?');
  $stmt->execute([(int)($_GET['id'] ?? 0)]);
  $row = $stmt->fetch();
  if (!$row) { echo json_encode(['ok' => false, 'message' => 'Letter not found.']); exit; }
  echo json_encode(['ok' => true, 'letter' => letter_payload($row)]); exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];

if ($action === 'save') {
  $id        = (int)($data['id'] ?? 0);
  $internId  = (int)($data['intern_id'] ?? 0);
  $appId     = (int)($data['application_id'] ?? 0);
  $letterDate = (string)($data['letter_date'] ?? '');
  $start     = (string)($data['start_date'] ?? '');
  $end       = (string)($data['end_date'] ?? '');
  $duration  = clean_text($data['duration_text'] ?? '', 100);
  $hours     = clean_text($data['working_hours'] ?? '', 150);
  $sigName   = clean_text($data['signatory_name'] ?? '', 150);
  $sigTitle  = clean_text($data['signatory_title'] ?? '', 150);
  $sent      = !empty($data['sent_to_intern']) ? 1 : 0;

  // Conditions: one per line, blank lines dropped.
  $lines = [];
  foreach (preg_split('/\r\n|\r|\n/', (string)($data['conditions'] ?? '')) as $line) {
    $line = clean_text($line, 500);
    if ($line !== '') $lines[] = $line;
  }
  if (count($lines) > 12) { echo json_encode(['ok' => false, 'message' => 'Please keep the conditions to 12 lines or fewer.']); exit; }
  $conditions = implode("\n", $lines);

  $stmt = $pdo->prepare('SELECT id, full_name, reg_number, institution, course FROM interns WHERE id = ?');
  $stmt->execute([$internId]);
  $intern = $stmt->fetch();
  if (!$intern) { echo json_encode(['ok' => false, 'message' => 'Please select an intern.']); exit; }

  if (!valid_date($letterDate)) { echo json_encode(['ok' => false, 'message' => 'Please provide a valid letter date.']); exit; }
  $err = check_period($start, $end);
  if ($err) { echo json_encode(['ok' => false, 'message' => $err]); exit; }

  // The application (if any) must belong to the selected intern.
  $application = null;
  if ($appId) {
    $a = $pdo->prepare('SELECT id, status, services FROM intern_applications WHERE id = ? AND intern_id = ?');
    $a->execute([$appId, $internId]);
    $application = $a->fetch() ?: null;
  }

  // Names that were renamed or removed since the application / letter was made may stay.
  $extraAllowed = $application ? decode_services($application['services']) : [];
  if ($id) {
    $cur = $pdo->prepare('SELECT services FROM acceptance_letters WHERE id = ?');
    $cur->execute([$id]);
    $extraAllowed = array_merge($extraAllowed, decode_services((string)$cur->fetchColumn()));
  }
  $services = clean_service_selection($pdo, $data['services'] ?? [], false, $extraAllowed);
  if (!$services) { echo json_encode(['ok' => false, 'message' => 'Select at least one training area.']); exit; }
  if ($hours === '' || $sigName === '' || $sigTitle === '' || $conditions === '') {
    echo json_encode(['ok' => false, 'message' => 'Working hours, conditions and the signatory name and title are required.']); exit;
  }

  $params = [
    $internId, $application ? $application['id'] : null,
    $intern['full_name'], $intern['reg_number'], $intern['institution'], $intern['course'],
    $letterDate, $start, $end, $duration !== '' ? $duration : null,
    json_encode($services, JSON_UNESCAPED_UNICODE), $hours, $conditions, $sigName, $sigTitle, $sent,
  ];

  if ($id) {
    $exists = $pdo->prepare('SELECT 1 FROM acceptance_letters WHERE id = ?');
    $exists->execute([$id]);
    if (!$exists->fetch()) { echo json_encode(['ok' => false, 'message' => 'Letter not found.']); exit; }
    $params[] = $id;
    $pdo->prepare('UPDATE acceptance_letters SET intern_id = ?, application_id = ?, recipient_name = ?, reg_number = ?, institution = ?, course = ?,
                   letter_date = ?, start_date = ?, end_date = ?, duration_text = ?, services = ?, working_hours = ?, conditions = ?,
                   signatory_name = ?, signatory_title = ?, sent_to_intern = ? WHERE id = ?')->execute($params);
    $letterId = $id;
  } else {
    $pdo->prepare('INSERT INTO acceptance_letters (intern_id, application_id, recipient_name, reg_number, institution, course,
                   letter_date, start_date, end_date, duration_text, services, working_hours, conditions, signatory_name, signatory_title, sent_to_intern)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute($params);
    $letterId = (int)$pdo->lastInsertId();
  }

  // Issuing a letter for an application that was still waiting marks it accepted.
  if ($application && $application['status'] === 'pending') {
    $pdo->prepare("UPDATE intern_applications SET status = 'accepted' WHERE id = ?")->execute([$application['id']]);
  }

  echo json_encode(['ok' => true, 'letter_id' => $letterId, 'ref' => letter_ref($letterId, $letterDate)]); exit;
}

if ($action === 'toggle_sent') {
  $id = (int)($data['id'] ?? 0);
  $pdo->prepare('UPDATE acceptance_letters SET sent_to_intern = ? WHERE id = ?')->execute([!empty($data['sent']) ? 1 : 0, $id]);
  echo json_encode(['ok' => true]); exit;
}

if ($action === 'delete') {
  $id = (int)($data['id'] ?? 0);
  $pdo->prepare('DELETE FROM acceptance_letters WHERE id = ?')->execute([$id]);
  echo json_encode(['ok' => true]); exit;
}

echo json_encode(['ok' => false]);
