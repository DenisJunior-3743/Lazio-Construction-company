<?php
session_start();
require_once __DIR__ . '/../config/database.php';
$id = (int)($_GET['id'] ?? 0);
if (!$id) { die('Invalid'); }

// Allow client to print only their own agreements; admin can print any
if (!isset($_SESSION['user'])) { header('Location: /lazio_site/auth/login.php'); exit; }
$role = $_SESSION['user']['role'];
if ($role !== 'admin') {
  $cid = (int)($_SESSION['user']['client_id'] ?? 0);
  $own = $pdo->prepare('SELECT COUNT(*) FROM agreements WHERE id = ? AND client_id = ?');
  $own->execute([$id, $cid]);
  if (!$own->fetchColumn()) { die('Forbidden'); }
}

$agr = $pdo->prepare('SELECT a.id, a.ceo_name, a.labour_amount, a.commitment_fee, a.deposit_percent, a.created_at, c.first_name, c.last_name
  FROM agreements a JOIN clients c ON c.id = a.client_id WHERE a.id = ?');
$agr->execute([$id]);
$agreement = $agr->fetch();
if (!$agreement) { die('Not found'); }

$phasesStmt = $pdo->prepare('SELECT phase_number, content FROM agreement_phases WHERE agreement_id = ? ORDER BY phase_number ASC');
$phasesStmt->execute([$id]);
$phases = $phasesStmt->fetchAll();

$repsStmt = $pdo->prepare('SELECT side, name, position, contact FROM agreement_representatives WHERE agreement_id = ? ORDER BY side, sort_order ASC');
$repsStmt->execute([$id]);
$reps = ['company' => [], 'client' => []];
foreach ($repsStmt->fetchAll() as $r) {
  $reps[$r['side']][] = $r;
}

$clientName = trim($agreement['first_name'].' '.$agreement['last_name']);
$depositAmount = (float)$agreement['labour_amount'] * (float)$agreement['deposit_percent'] / 100;

function money($n) {
  return 'UGX ' . number_format((float)$n);
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contract Agreement #<?php echo $agreement['id']; ?> - Lazio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Lora:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    <style>
      @media print {
        .no-print { display: none; }
        body { margin: 0; padding: 0; background: white !important; }
        .print-page { box-shadow: none !important; border-radius: 0 !important; margin: 0 !important; min-height: auto !important; padding: 26px 30px !important; }
      }
      body {
        font-family: 'Lora', Georgia, 'Times New Roman', serif;
        font-size: 13.5px;
        color: #232a3b;
        line-height: 1.65;
        margin: 0;
        padding: 30px 15px;
        background: #eef1f8;
      }
      .cover-title {
        font-family: 'Playfair Display', Georgia, serif;
        font-weight: 700;
        font-size: 32px;
        letter-spacing: 0.02em;
        color: #1e3a8a;
        margin-top: 56px;
      }
      .cover-sub { font-size: 15px; font-style: italic; margin-top: 22px; }
      .cover-client { font-size: 20px; color: #1e3a8a; font-weight: 600; margin-top: 8px; }
      .cover-meta {
        font-family: system-ui, -apple-system, 'Segoe UI', Arial, sans-serif;
        color: #667085;
        font-size: 12px;
        letter-spacing: 0.03em;
        margin-top: 56px;
      }
      .print-page {
        max-width: 800px;
        min-height: 900px;
        margin: 0 auto 24px auto;
        background: #fff;
        padding: 52px 60px;
        border-radius: 14px;
        box-shadow: 0 12px 32px -12px rgba(15, 23, 42, 0.18);
        position: relative;
        page-break-after: always;
        break-after: page;
      }
      .print-page:last-child { page-break-after: auto; break-after: auto; margin-bottom: 0; }
      .page-footer {
        font-family: system-ui, -apple-system, 'Segoe UI', Arial, sans-serif;
        position: absolute;
        bottom: 20px;
        left: 60px;
        right: 60px;
        text-align: center;
        font-size: 10px;
        color: #9ca3af;
        letter-spacing: 0.05em;
      }
      .title {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 27px;
        font-weight: 700;
        color: #1e3a8a;
        margin: 0 0 28px 0;
        padding-bottom: 12px;
        border-bottom: 2px solid #2563eb;
        display: inline-block;
      }
      .intro {
        font-size: 14px;
        line-height: 1.8;
        margin-bottom: 22px;
      }
      .ceo-name { color: #dc2626; font-weight: 600; }
      .client-name { color: #1e3a8a; font-weight: 600; }
      .phase {
        margin: 18px 0;
        padding: 12px 16px;
        background: #f3f6fd;
        border-left: 3px solid #2563eb;
        border-radius: 6px;
      }
      .phase-label {
        font-family: system-ui, -apple-system, 'Segoe UI', Arial, sans-serif;
        font-weight: 700;
        color: #1e3a8a;
        font-size: 11px;
        letter-spacing: 0.06em;
        text-transform: uppercase;
      }
      .phase-content {
        margin-top: 7px;
        white-space: pre-wrap;
        font-size: 13.5px;
      }
      .profile-block { margin-top: 20px; }
      .profile-block h4 {
        font-family: system-ui, -apple-system, 'Segoe UI', Arial, sans-serif;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #1e3a8a;
        margin: 0 0 5px 0;
      }
      .profile-block p { margin: 0; line-height: 1.7; }
      .terms-list { margin-top: 16px; margin-left: 4px; font-size: 14px; }
      .terms-list li { margin-bottom: 16px; padding-left: 6px; line-height: 1.75; }
      .terms-list li::marker { color: #1e3a8a; font-weight: 700; }
      .conclusion {
        margin-top: 14px;
        font-weight: 600;
        font-style: italic;
      }
      .signature-section {
        margin-top: 34px;
        display: flex;
        justify-content: space-between;
        gap: 30px;
      }
      .signature-col { flex: 1; }
      .signature-col h4 {
        font-family: system-ui, -apple-system, 'Segoe UI', Arial, sans-serif;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #667085;
        margin: 0 0 16px 0;
      }
      .signature-line {
        margin-bottom: 20px;
      }
      .signature-line label {
        font-family: system-ui, -apple-system, 'Segoe UI', Arial, sans-serif;
        display: block;
        font-size: 11.5px;
        color: #475467;
        margin-bottom: 5px;
      }
      .signature-line .line {
        border-bottom: 1px solid #000;
        height: 24px;
      }
      .note {
        margin-top: 34px;
        padding-top: 16px;
        border-top: 1px solid #ddd;
        font-size: 12px;
        font-style: italic;
        color: #666;
      }
      .closing {
        margin-top: 22px;
        font-size: 13.5px;
      }
    </style>
  </head>
  <body>

    <!-- Page 1: Cover -->
    <div class="print-page" style="text-align:center;">
      <div style="margin-top:70px;">
        <img src="/lazio_site/assets/images/logo.png" alt="Lazio Logo" style="max-width:320px; width:80%; height:auto;">
      </div>
      <h1 class="cover-title">Contract Agreement</h1>
      <p class="cover-sub">Between <strong>Lazio Construction Company</strong> and</p>
      <p class="cover-client"><?php echo htmlspecialchars($clientName); ?></p>
      <p class="cover-meta">Agreement No. #<?php echo $agreement['id']; ?> &nbsp;|&nbsp; Dated <?php echo date('d/m/Y', strtotime($agreement['created_at'])); ?></p>
      <div class="page-footer">Page 1 of 5 — Lazio Construction Company</div>
    </div>

    <!-- Page 2: Company Profile -->
    <div class="print-page">
      <div class="title">Company Profile</div>
      <p class="intro">Lazio Construction Company is committed to building with integrity, innovation &amp; impact — from plan drawing to full-scale projects, we bring structures to life.</p>
      <div class="profile-block">
        <h4>Our Goal</h4>
        <p>Deliver innovative, sustainable, high-quality construction solutions that exceed expectations.</p>
      </div>
      <div class="profile-block">
        <h4>Our Vision</h4>
        <p>Be the region's leading construction company renowned for excellence and innovation.</p>
      </div>
      <div class="profile-block">
        <h4>Our Mission</h4>
        <p>Provide reliable services and practical training built on integrity and innovation.</p>
      </div>
      <div class="page-footer">Page 2 of 5 — Lazio Construction Company</div>
    </div>

    <!-- Page 3: Scope of Work -->
    <div class="print-page">
      <div class="title">Scope of Work</div>
      <div class="intro">
        This agreement is entered into by Lazio Construction Company, represented by its Chief Executive Officer,
        <span class="ceo-name"><?php echo htmlspecialchars($agreement['ceo_name']); ?></span>, and the client,
        <span class="client-name"><?php echo htmlspecialchars($clientName); ?></span>,
        both of whom agree to be bound by the terms and conditions set out below.
      </div>

      <?php if ($phases): foreach ($phases as $p): ?>
        <div class="phase">
          <div class="phase-label">Phase <?php echo (int)$p['phase_number']; ?></div>
          <div class="phase-content"><?php echo nl2br(htmlspecialchars($p['content'])); ?></div>
        </div>
      <?php endforeach; else: ?>
        <p style="color:#888;">No phases were added to this agreement.</p>
      <?php endif; ?>
      <div class="page-footer">Page 3 of 5 — Lazio Construction Company</div>
    </div>

    <!-- Page 4: Terms & Conditions -->
    <div class="print-page">
      <div class="title">Terms &amp; Conditions</div>
      <ol class="terms-list">
        <li>The client shall pay a commitment fee of <strong><?php echo money($agreement['commitment_fee']); ?></strong> upon signing this agreement.</li>
        <li>A deposit of <strong><?php echo (float)$agreement['deposit_percent']; ?>%</strong> of the agreed labour amount (<?php echo money($agreement['labour_amount']); ?>) shall be paid before work commences, amounting to <strong><?php echo money($depositAmount); ?></strong>.</li>
        <li>Both parties shall agree on and adhere to payment dates to avoid inconveniencing workers.</li>
        <li>Both parties shall review the work phases together to avoid miscommunication.</li>
        <li>This agreement shall be signed by both parties and their respective witnesses/representatives.</li>
      </ol>
      <div class="page-footer">Page 4 of 5 — Lazio Construction Company</div>
    </div>

    <!-- Page 5: Signing -->
    <div class="print-page">
      <div class="title">Signatures</div>
      <p class="conclusion">Both parties acknowledge and accept the terms of this agreement as outlined above.</p>

      <div class="signature-section">
        <div class="signature-col">
          <h4>Company</h4>
          <div class="signature-line"><label>C.E.O. Signature</label><div class="line"></div></div>
        </div>
        <div class="signature-col">
          <h4>Client</h4>
          <div class="signature-line"><label>Client Signature</label><div class="line"></div></div>
        </div>
      </div>

      <div class="signature-section" style="margin-top:30px;">
        <div class="signature-col">
          <h4>Company Witnesses</h4>
          <?php if ($reps['company']): foreach ($reps['company'] as $r): ?>
            <div class="signature-line">
              <label><?php echo htmlspecialchars($r['name']); ?> — <?php echo htmlspecialchars($r['position']); ?><?php if ($r['contact']) echo ' ('.htmlspecialchars($r['contact']).')'; ?></label>
              <div class="line"></div>
            </div>
          <?php endforeach; else: ?>
            <p style="color:#888;font-size:11px;">No representatives registered.</p>
          <?php endif; ?>
        </div>
        <div class="signature-col">
          <h4>Client Witnesses</h4>
          <?php if ($reps['client']): foreach ($reps['client'] as $r): ?>
            <div class="signature-line">
              <label><?php echo htmlspecialchars($r['name']); ?> — <?php echo htmlspecialchars($r['position']); ?><?php if ($r['contact']) echo ' ('.htmlspecialchars($r['contact']).')'; ?></label>
              <div class="line"></div>
            </div>
          <?php endforeach; else: ?>
            <p style="color:#888;font-size:11px;">No representatives registered.</p>
          <?php endif; ?>
        </div>
      </div>

      <div class="note">
        <strong>Note:</strong><br>
        We sincerely thank all our clients for their continued trust and support. We are committed to serving you with the highest level of professionalism and expertise, and we look forward to continuing our partnership.
      </div>

      <div class="closing">
        Yours sincerely,<br>
        <?php echo htmlspecialchars($agreement['ceo_name']); ?><br>
        Chief Executive Officer
      </div>
      <div class="page-footer">Page 5 of 5 — Lazio Construction Company</div>
    </div>

    <div class="no-print" style="margin-top: 24px; text-align: center;">
      <button onclick="window.print()" style="padding: 11px 24px; background: linear-gradient(135deg,#2563eb 0%,#3b82f6 100%); color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; box-shadow: 0 8px 20px -8px rgba(37,99,235,0.5);">🖨️ Print / Save as PDF</button>
    </div>
  </body>
</html>
