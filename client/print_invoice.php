<?php
session_start();
require_once __DIR__ . '/../config/database.php';
$id = (int)($_GET['id'] ?? 0);
$type = strtolower($_GET['type'] ?? 'invoice');
$isQuote = $type === 'quote';
if (!$id) { die('Invalid'); }

// Allow client to print only their own invoices; admin can print any
if (!isset($_SESSION['user'])) { header('Location: /lazio_site/auth/login.php'); exit; }
$role = $_SESSION['user']['role'];
if ($role !== 'admin') {
  $cid = (int)($_SESSION['user']['client_id'] ?? 0);
  $own = $pdo->prepare('SELECT COUNT(*) FROM invoices WHERE id = ? AND client_id = ?');
  $own->execute([$id, $cid]);
  if (!$own->fetchColumn()) { die('Forbidden'); }
}

$inv = $pdo->prepare('SELECT i.id, i.total, i.created_at, c.first_name, c.last_name FROM invoices i JOIN clients c ON c.id = i.client_id WHERE i.id = ?');
$inv->execute([$id]);
$invoice = $inv->fetch();
$items = $pdo->prepare('SELECT ii.quantity, ii.unit_price, ii.subtotal, it.name, c.name AS category
  FROM invoice_items ii 
  JOIN items it ON it.id = ii.item_id 
  JOIN categories c ON it.category_id = c.id
  WHERE ii.invoice_id = ?');
$items->execute([$id]);
$rows = $items->fetchAll();

$grouped = [];
foreach ($rows as $r) {
  $grouped[$r['category']][] = $r;
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $isQuote ? 'Quotation' : 'Invoice'; ?> #<?php echo $invoice['id']; ?> - Lazio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
      @media print {
        .no-print { display: none; }
        body { margin: 0; padding: 20px; }
        .page-break { page-break-before: always; }
      }
      body { 
        font-family: 'Arial', sans-serif; 
        font-size: 12px; 
        color: #000; 
        line-height: 1.4;
        margin: 0;
        padding: 20px;
      }
      .doc-wrapper { 
        max-width: 800px; 
        margin: 0 auto; 
        background: white;
      }
      .header { 
        border-bottom: 3px solid #2563eb; 
        padding-bottom: 20px; 
        margin-bottom: 30px;
      }
      .company-info {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
      }
      .company-details h1 { 
        font-size: 24px; 
        font-weight: bold; 
        color: #2563eb; 
        margin: 0 0 5px 0;
      }
      .company-details p { 
        margin: 2px 0; 
        color: #666; 
        font-size: 11px;
      }
      .invoice-info {
        text-align: right;
      }
      .invoice-info h2 { 
        font-size: 20px; 
        font-weight: bold; 
        color: #2563eb; 
        margin: 0 0 10px 0;
      }
      .invoice-details {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin: 20px 0;
      }
      .invoice-details h3 {
        font-size: 14px;
        font-weight: bold;
        margin: 0 0 10px 0;
        color: #333;
      }
      .table { 
        width: 100%; 
        border-collapse: collapse; 
        margin: 20px 0;
      }
      .table th, .table td { 
        border: 1px solid #ddd; 
        padding: 8px; 
        text-align: left;
      }
      .table th { 
        background: #f8f9fa; 
        font-weight: bold; 
        font-size: 11px;
        text-transform: uppercase;
      }
      .table td { 
        font-size: 11px;
      }
      .text-right { text-align: right; }
      .text-center { text-align: center; }
      .totals { 
        font-size: 14px; 
        font-weight: bold;
        background: #f8f9fa;
      }
      .footer {
        margin-top: 40px;
        padding-top: 20px;
        border-top: 1px solid #ddd;
        font-size: 10px;
        color: #666;
      }
      .signature-section {
        margin-top: 40px;
        display: flex;
        justify-content: space-between;
      }
      .signature-box {
        width: 200px;
        text-align: center;
      }
      .signature-line {
        border-bottom: 1px solid #000;
        height: 40px;
        margin-bottom: 5px;
      }
      .watermark {
        position: fixed; 
        top: 0; 
        left: 0; 
        right: 0; 
        bottom: 0; 
        display: <?php echo $isQuote ? 'block' : 'none'; ?>; 
        pointer-events: none;
        z-index: -1;
      }
      .watermark span { 
        position: absolute; 
        top: 50%; 
        left: 50%; 
        transform: translate(-50%, -50%) rotate(-45deg); 
        font-size: 72px; 
        color: rgba(37,99,235,0.1); 
        font-weight: bold;
      }
    </style>
  </head>
  <body>
    <div class="watermark"><span>QUOTATION</span></div>
    <div class="doc-wrapper">
      <div class="header">
        <div class="company-info">
          <div class="company-details">
            <h1>LAZIO CONSTRUCTION COMPANY</h1>
            <p>Bbunga, Uganda</p>
            <p>Phone: +256 700 000000</p>
            <p>Email: info@lazioconstruction.example</p>
          </div>
          <div class="invoice-info">
            <h2><?php echo $isQuote ? 'QUOTATION' : 'INVOICE'; ?></h2>
            <p><strong>No:</strong> #<?php echo $invoice['id']; ?></p>
            <p><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($invoice['created_at'])); ?></p>
          </div>
        </div>
      </div>
      
      <div class="invoice-details">
        <h3><?php echo $isQuote ? 'Prepared For' : 'Bill To'; ?></h3>
        <p><strong><?php echo htmlspecialchars($invoice['first_name'].' '.$invoice['last_name']); ?></strong></p>
      </div>
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead>
          <tr><th>Category</th><th>Description</th><th class="text-end">Qty</th><th class="text-end">Unit Price</th><th class="text-end">Amount</th></tr>
        </thead>
        <tbody>
          <?php 
          $grand = 0;
          foreach ($grouped as $cat => $items): 
            $cat_total = 0;
            foreach ($items as $r) {
              $cat_total += $r['subtotal'];
              $grand += $r['subtotal'];
              echo "<tr>
                <td>".htmlspecialchars($cat)."</td>
                <td>".htmlspecialchars($r['name'])."</td>
                <td class='text-end'>".(int)$r['quantity']."</td>
                <td class='text-end'>".(int)$r['unit_price']."</td>
                <td class='text-end'>".(int)$r['subtotal']."</td>
              </tr>";
            }
            echo "<tr class='table-light'><td colspan='4' class='text-end fw-bold'>Subtotal for $cat</td><td class='text-end fw-bold'>$cat_total</td></tr>";
          endforeach;
          ?>
        </tbody>
        <tfoot>
          <tr><th colspan="4" class="text-end totals">GRAND TOTAL</th><th class="text-end totals"><?php echo $grand; ?></th></tr>
        </tfoot>
      </table>
    </div>
    <div class="row mt-4">
      <div class="col-6">
        <div class="small muted mb-2"><strong>Terms & Notes</strong></div>
        <div class="small muted">
          <?php if ($isQuote): ?>
          Prices are valid for 14 days from the date of this quotation.
          Work commences upon approval and initial deposit as per agreement.
          <?php else: ?>
          Payment due within 14 days. Late payments may attract a service charge.
          <?php endif; ?>
        </div>
      </div>
      <div class="col-6">
        <div class="small muted mb-2 text-end"><strong>Authorization</strong></div>
        <div class="sign-line"></div>
        <div class="small muted text-end">Signature & Date</div>
      </div>
    </div>
    <div class="no-print mt-3 d-flex gap-2">
      <button class="btn btn-primary" onclick="window.print()">Print</button>
    </div>
    </div>
  </body>
</html>
