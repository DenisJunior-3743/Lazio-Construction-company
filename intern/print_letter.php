<?php
// Print view for an acceptance letter.
// Admins can open any letter; an intern can open only their own letters that were sent to them.
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/intern_helpers.php';

if (!isset($_SESSION['user'])) { header('Location: /lazio_site/auth/login.php'); exit; }

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM acceptance_letters WHERE id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) { http_response_code(404); exit('Letter not found.'); }

$role = $_SESSION['user']['role'] ?? '';
if ($role === 'intern') {
  $own = $pdo->prepare('SELECT 1 FROM interns WHERE user_id = ? AND id = ?');
  $own->execute([(int)$_SESSION['user']['id'], (int)$row['intern_id']]);
  if (!$own->fetch() || !(int)$row['sent_to_intern']) { http_response_code(403); exit('Forbidden.'); }
} elseif ($role !== 'admin') {
  http_response_code(403); exit('Forbidden.');
}

$letter = letter_payload($row);
$backUrl = $role === 'admin' ? '/lazio_site/admin/dashboard.php' : '/lazio_site/intern/index.php';
header('Cache-Control: private, no-store');
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceptance Letter - <?php echo htmlspecialchars($letter['recipient_name']); ?></title>
    <link href="/lazio_site/assets/css/letter.css" rel="stylesheet">
  </head>
  <body class="letter-page-body">
    <div class="letter-toolbar no-print">
      <a href="<?php echo $backUrl; ?>">&larr; Back</a>
      <button class="btn-print" onclick="window.print()">Print / Save as PDF</button>
    </div>
    <div id="letterRoot"></div>
    <script src="/lazio_site/assets/js/acceptance-letter.js"></script>
    <script>
      const LETTER = <?php echo json_encode($letter, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>;
      document.getElementById('letterRoot').innerHTML = LazioLetter.build(LETTER);
    </script>
  </body>
</html>
