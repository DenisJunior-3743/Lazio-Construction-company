<?php
// includes/intern_helpers.php
// Shared helpers for the internship module (registration, validation, uploads).

if (!defined('INTERN_FEE_UGX'))          define('INTERN_FEE_UGX', 20000);
if (!defined('INTERN_MAX_UPLOAD_BYTES')) define('INTERN_MAX_UPLOAD_BYTES', 5 * 1024 * 1024);
if (!defined('INTERN_MAX_DOCS'))         define('INTERN_MAX_DOCS', 25);
if (!defined('INTERN_MIN_PASSWORD'))     define('INTERN_MIN_PASSWORD', 6);

function json_response($data, $status = 200) {
  http_response_code($status);
  header('Content-Type: application/json');
  echo json_encode($data);
  exit;
}

/* ---------- Document types & upload rules ---------- */

function intern_doc_types() {
  return [
    'application_letter' => 'Application letter (from your institution)',
    'student_id'         => 'Student ID',
    'national_id'        => 'National ID / Passport',
    'academic_document'  => 'Academic document (transcript, results, admission)',
    'cv'                 => 'CV / Resume',
    'recommendation'     => 'Recommendation letter',
    'other'              => 'Other',
  ];
}

// extension => acceptable detected MIME types
function intern_allowed_files() {
  return [
    'pdf'  => ['application/pdf'],
    'jpg'  => ['image/jpeg'],
    'jpeg' => ['image/jpeg'],
    'png'  => ['image/png'],
    'doc'  => ['application/msword', 'application/x-ole-storage', 'application/CDFV2', 'application/vnd.ms-office'],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
  ];
}

// MIME type used when serving a stored file back to its owner / the admin.
function intern_serve_mime($ext) {
  $map = [
    'pdf'  => 'application/pdf',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
  ];
  return $map[strtolower($ext)] ?? 'application/octet-stream';
}

function intern_upload_root() {
  return realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'interns';
}

// Creates uploads/interns/ (with a deny-all .htaccess) and the intern's folder.
function intern_ensure_upload_dir($internId) {
  $root = intern_upload_root();
  if (!is_dir($root) && !@mkdir($root, 0755, true)) return false;
  $ht = $root . DIRECTORY_SEPARATOR . '.htaccess';
  if (!file_exists($ht)) {
    @file_put_contents($ht, "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n</IfModule>\n");
  }
  $dir = $root . DIRECTORY_SEPARATOR . (int)$internId;
  if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return false;
  return $dir;
}

function intern_doc_path($internId, $storedName) {
  return intern_upload_root() . DIRECTORY_SEPARATOR . (int)$internId . DIRECTORY_SEPARATOR . basename($storedName);
}

function intern_upload_error_message($code) {
  if ($code === UPLOAD_ERR_INI_SIZE || $code === UPLOAD_ERR_FORM_SIZE) return 'The file is too large (maximum 5 MB).';
  if ($code === UPLOAD_ERR_NO_FILE) return 'Please choose a file to upload.';
  return 'The upload failed. Please try again.';
}

// Validates and stores one uploaded file, then records it. Returns [ok, message, id].
function intern_store_upload(PDO $pdo, $internId, $applicationId, $docType, $file) {
  if (!is_array($file) || !isset($file['error'])) return [false, 'Please choose a file to upload.', 0];
  if ($file['error'] !== UPLOAD_ERR_OK) return [false, intern_upload_error_message($file['error']), 0];
  if (!is_uploaded_file($file['tmp_name'])) return [false, 'The upload failed. Please try again.', 0];
  if ($file['size'] > INTERN_MAX_UPLOAD_BYTES) return [false, 'The file is too large (maximum 5 MB).', 0];
  if ($file['size'] <= 0) return [false, 'The file is empty.', 0];

  $types = intern_doc_types();
  if (!isset($types[$docType])) $docType = 'other';

  $allowed = intern_allowed_files();
  $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
  if (!isset($allowed[$ext])) return [false, 'Only PDF, JPG, PNG, DOC and DOCX files are accepted.', 0];

  // Check the real content type, not the one the browser claims.
  if (function_exists('finfo_open')) {
    $fi = finfo_open(FILEINFO_MIME_TYPE);
    $detected = $fi ? finfo_file($fi, $file['tmp_name']) : '';
    if ($fi) finfo_close($fi);
    if ($detected && !in_array($detected, $allowed[$ext], true)) {
      return [false, 'The file content does not match its type. Please upload a genuine ' . strtoupper($ext) . ' file.', 0];
    }
  }

  $count = $pdo->prepare('SELECT COUNT(*) FROM intern_documents WHERE intern_id = ?');
  $count->execute([$internId]);
  if ((int)$count->fetchColumn() >= INTERN_MAX_DOCS) return [false, 'You have reached the maximum number of documents (' . INTERN_MAX_DOCS . '). Delete one to upload another.', 0];

  $dir = intern_ensure_upload_dir($internId);
  if (!$dir) return [false, 'The server could not prepare the upload folder.', 0];

  $stored = bin2hex(random_bytes(16)) . '.' . $ext;
  if (!move_uploaded_file($file['tmp_name'], $dir . DIRECTORY_SEPARATOR . $stored)) {
    return [false, 'The server could not save the file.', 0];
  }

  $original = preg_replace('/[^\w .()\-]/u', '_', basename($file['name']));
  $original = substr($original, 0, 200);
  $stmt = $pdo->prepare('INSERT INTO intern_documents (intern_id, application_id, doc_type, original_name, stored_name, mime_type, file_size) VALUES (?, ?, ?, ?, ?, ?, ?)');
  $stmt->execute([$internId, $applicationId ?: null, $docType, $original, $stored, intern_serve_mime($ext), (int)$file['size']]);
  return [true, 'Uploaded.', (int)$pdo->lastInsertId()];
}

// Removes every stored file for an intern (call before deleting the intern row).
function intern_delete_all_files(PDO $pdo, $internId) {
  $stmt = $pdo->prepare('SELECT stored_name FROM intern_documents WHERE intern_id = ?');
  $stmt->execute([$internId]);
  foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $name) {
    @unlink(intern_doc_path($internId, $name));
  }
  $dir = intern_upload_root() . DIRECTORY_SEPARATOR . (int)$internId;
  if (is_dir($dir)) @rmdir($dir);
}

/* ---------- Validation helpers ---------- */

function str_length($s) {
  return function_exists('mb_strlen') ? mb_strlen($s) : strlen($s);
}

// "uvt088 / u/26/m/ei/0137" -> "UVT088/U/26/M/EI/0137"
function normalize_reg_number($raw) {
  return strtoupper(preg_replace('/\s+/', '', trim((string)$raw)));
}

function valid_reg_number($reg) {
  return (bool)preg_match('/^[A-Z0-9][A-Z0-9\/\-._]{2,48}$/', $reg);
}

function clean_text($raw, $max = 200) {
  $s = trim(preg_replace('/\s+/', ' ', (string)$raw));
  return str_length($s) > $max ? (function_exists('mb_substr') ? mb_substr($s, 0, $max) : substr($s, 0, $max)) : $s;
}

function valid_phone($p) {
  return (bool)preg_match('/^\d{10}$/', $p);
}

function valid_date($s) {
  if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', (string)$s, $m)) return false;
  return checkdate((int)$m[2], (int)$m[3], (int)$m[1]);
}

// Returns an error message, or '' when the period is acceptable.
function check_period($start, $end) {
  if (!valid_date($start) || !valid_date($end)) return 'Please provide valid start and end dates.';
  if ($end < $start) return 'The end date cannot be before the start date.';
  $days = (strtotime($end) - strtotime($start)) / 86400;
  if ($days > 366) return 'An internship period cannot be longer than one year.';
  return '';
}

function generate_password($len = 8) {
  $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  $out = '';
  for ($i = 0; $i < $len; $i++) $out .= $chars[random_int(0, strlen($chars) - 1)];
  return $out;
}

/* ---------- Training areas ---------- */

// The trainings from the internship poster (assets/internship/intern.jpeg).
// sql/internship_module.sql seeds the same list; this copy is what the public pages
// show if the database cannot be reached or the internship tables have not been created yet.
function internship_default_services() {
  return [
    'Brick Laying',
    'Construction Setting-Out & Plan Reading',
    'Construction Drawings',
    'Plastering & Finishing',
    'Ground Screeding & Levelling',
    'Tiling',
    'Painting',
    'Plumbing',
    'Electrical Pipe Works & Wiring',
    'Generator Installation & Repair',
    'Solar Installation & Repair',
    'Motor Installation & Repair',
    'Roofing & Formwork',
    'Steel Bending',
  ];
}

// Makes an endpoint answer with JSON (and a clear message) instead of a PHP fatal-error
// page when something goes wrong, e.g. sql/internship_module.sql has not been run yet.
function intern_json_guard() {
  set_exception_handler(function ($e) {
    error_log('Internship module: ' . $e->getMessage());
    $missing = $e instanceof PDOException && (string)$e->getCode() === '42S02';
    if (!headers_sent()) { http_response_code(500); header('Content-Type: application/json'); }
    echo json_encode(['ok' => false, 'message' => $missing
      ? 'The internship tables are missing. Run sql/internship_module.sql in phpMyAdmin on this database first.'
      : 'Something went wrong on the server. Please try again.']);
    exit;
  });
}

function decode_services($json) {
  $arr = json_decode((string)$json, true);
  return is_array($arr) ? array_values(array_map('strval', $arr)) : [];
}

// Keeps only names that exist (and are active) in internship_services, in the
// table's own order. $extraAllowed lists names that may stay even though they are no
// longer in the table (e.g. a training area renamed after the application was made).
// Returns the cleaned list.
function clean_service_selection(PDO $pdo, $selected, $activeOnly = true, array $extraAllowed = []) {
  if (!is_array($selected)) return [];
  $wanted = [];
  foreach ($selected as $s) $wanted[strtolower(trim((string)$s))] = trim((string)$s);
  $sql = 'SELECT name FROM internship_services' . ($activeOnly ? ' WHERE is_active = 1' : '') . ' ORDER BY sort_order, name';
  $out = [];
  foreach ($pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN) as $name) {
    if (isset($wanted[strtolower($name)])) { $out[] = $name; unset($wanted[strtolower($name)]); }
  }
  $extra = [];
  foreach ($extraAllowed as $name) $extra[strtolower(trim((string)$name))] = true;
  foreach ($wanted as $key => $original) {
    if ($original !== '' && isset($extra[$key])) $out[] = $original;
  }
  return $out;
}

/* ---------- Registration (self sign-up and admin) ---------- */

// Creates the login (users) and the intern profile in one transaction.
// $in: full_name, reg_number, institution, course, phone, password (optional for admin).
// Returns ['ok'=>bool, 'message'=>string, + on success: intern_id, user_id, username, generated_password?].
function intern_register(PDO $pdo, array $in, $registeredBy) {
  $name   = clean_text($in['full_name'] ?? '', 150);
  $reg    = normalize_reg_number($in['reg_number'] ?? '');
  $inst   = clean_text($in['institution'] ?? '', 200);
  $course = clean_text($in['course'] ?? '', 200);
  $phone  = trim((string)($in['phone'] ?? ''));
  $password = trim((string)($in['password'] ?? ''));

  if ($name === '' || $reg === '' || $inst === '' || $course === '' || $phone === '') {
    return ['ok' => false, 'message' => 'Full name, registration number, institution, course and phone are required.'];
  }
  if (str_length($name) < 2) return ['ok' => false, 'message' => 'Please enter the intern\'s full name.'];
  if (!valid_reg_number($reg)) {
    return ['ok' => false, 'message' => 'Registration number may only contain letters, numbers and / - . _ (3 to 49 characters).'];
  }
  if (!valid_phone($phone)) return ['ok' => false, 'message' => 'Phone must be exactly 10 digits.'];

  $generated = null;
  if ($password === '') {
    if ($registeredBy !== 'admin') return ['ok' => false, 'message' => 'Please choose a password.'];
    $password = $generated = generate_password(8);
  } elseif (strlen($password) < INTERN_MIN_PASSWORD) {
    return ['ok' => false, 'message' => 'Password must be at least ' . INTERN_MIN_PASSWORD . ' characters.'];
  }

  $check = $pdo->prepare('SELECT 1 FROM users WHERE username = ? UNION SELECT 1 FROM interns WHERE reg_number = ?');
  $check->execute([$reg, $reg]);
  if ($check->fetch()) {
    return ['ok' => false, 'message' => 'This registration number is already registered. If you were registered by the office, log in or use "Forgot Password".'];
  }

  $pdo->beginTransaction();
  try {
    $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)');
    $stmt->execute([$reg, password_hash($password, PASSWORD_BCRYPT), 'intern']);
    $userId = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare('INSERT INTO interns (user_id, reg_number, full_name, institution, course, phone, registered_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $reg, $name, $inst, $course, $phone, $registeredBy === 'admin' ? 'admin' : 'self']);
    $internId = (int)$pdo->lastInsertId();
    $pdo->commit();
  } catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($e instanceof PDOException && (string)$e->getCode() === '42S02') {
      return ['ok' => false, 'message' => 'Registration is not available yet: the internship tables are missing. Please tell the office.'];
    }
    return ['ok' => false, 'message' => 'Registration failed. The registration number may already be in use.'];
  }

  $res = ['ok' => true, 'message' => 'Registered.', 'intern_id' => $internId, 'user_id' => $userId, 'username' => $reg];
  if ($generated !== null) $res['generated_password'] = $generated;
  return $res;
}

/* ---------- Session helpers ---------- */

// Loads the signed-in intern (or answers 403). Never trusts an id from the request.
function require_intern(PDO $pdo) {
  if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'intern') {
    json_response(['ok' => false, 'message' => 'Please log in as an intern.'], 403);
  }
  $stmt = $pdo->prepare('SELECT * FROM interns WHERE user_id = ? LIMIT 1');
  $stmt->execute([(int)$_SESSION['user']['id']]);
  $intern = $stmt->fetch();
  if (!$intern) json_response(['ok' => false, 'message' => 'Intern profile not found.'], 403);
  return $intern;
}

function require_admin() {
  if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    json_response(['ok' => false, 'message' => 'Forbidden'], 403);
  }
}

/* ---------- Letters ---------- */

function letter_ref($id, $letterDate) {
  return 'LCC/INT/' . date('Y', strtotime($letterDate)) . '/' . str_pad((string)(int)$id, 4, '0', STR_PAD_LEFT);
}

// Row from acceptance_letters -> the array the letter template (JS) expects.
function letter_payload(array $row) {
  return [
    'id'             => (int)$row['id'],
    'ref'            => letter_ref($row['id'], $row['letter_date']),
    'intern_id'      => (int)$row['intern_id'],
    'application_id' => $row['application_id'] !== null ? (int)$row['application_id'] : null,
    'recipient_name' => $row['recipient_name'],
    'reg_number'     => $row['reg_number'],
    'institution'    => $row['institution'],
    'course'         => $row['course'],
    'letter_date'    => $row['letter_date'],
    'start_date'     => $row['start_date'],
    'end_date'       => $row['end_date'],
    'duration_text'  => $row['duration_text'] ?? '',
    'services'       => decode_services($row['services']),
    'working_hours'  => $row['working_hours'],
    'conditions'     => $row['conditions'],
    'signatory_name' => $row['signatory_name'],
    'signatory_title'=> $row['signatory_title'],
    'sent_to_intern' => (int)$row['sent_to_intern'],
  ];
}
