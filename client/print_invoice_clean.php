<?php
session_start();
require_once __DIR__ . '/../config/database.php';
$id = (int)($_GET['id'] ?? 0);
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

$type = strtolower($_GET['type'] ?? 'invoice');
$isQuote = $type === 'quote';
$inline = isset($_GET['inline']) && $_GET['inline'] == '1';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $isQuote ? 'Quotation' : 'Invoice'; ?> #<?php echo $invoice['id']; ?> - Lazio</title>
    <style>
      @media print {
        .no-print { display: none; }
        body { margin: 0; padding: 15px; background: white !important; }
        .container { box-shadow: none !important; }
      }
      body {
        font-family: 'Segoe UI', Arial, sans-serif;
        font-size: 12px;
        color: #1f2937;
        line-height: 1.4;
        margin: 0;
        padding: 30px 15px;
        background: #eef1f8;
      }
      .container {
        max-width: 800px;
        margin: 0 auto;
        background: #fff;
        padding: 32px 36px;
        border-radius: 14px;
        box-shadow: 0 12px 32px -12px rgba(15, 23, 42, 0.18);
      }
      .header {
        border-bottom: 2px solid #2563eb;
        padding-bottom: 15px;
        margin-bottom: 20px;
      }
      .company-info {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 20px;
      }
      .company-name { 
        font-size: 20px; 
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
      .invoice-title { 
        font-size: 18px; 
        font-weight: bold; 
        color: #2563eb; 
        margin: 0 0 8px 0;
      }
      .invoice-details p {
        margin: 2px 0;
        font-size: 11px;
      }
      .bill-to {
        background: #f3f6fd;
        padding: 12px 14px;
        margin: 15px 0;
        border-radius: 8px;
        border-left: 3px solid #2563eb;
      }
      .bill-to h3 {
        margin: 0 0 5px 0;
        font-size: 11px;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #667085;
      }
      .table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin: 20px 0;
        border: 1px solid #e5e9f2;
        border-radius: 8px;
        overflow: hidden;
      }
      .table th, .table td {
        border-bottom: 1px solid #e5e9f2;
        padding: 8px 10px;
        text-align: left;
      }
      .table th {
        background: #f3f6fd;
        font-weight: bold;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #475467;
      }
      .table td {
        font-size: 11px;
      }
      .table tbody tr:last-child td { border-bottom: none; }
      .text-right { text-align: right; }
      .text-center { text-align: center; }
      .total-row {
        font-size: 13px;
        font-weight: bold;
        background: #f3f6fd;
      }
      .footer {
        margin-top: 30px;
        padding-top: 15px;
        border-top: 1px solid #ddd;
        font-size: 10px;
        color: #666;
      }
      .signature-section {
        margin-top: 30px;
        display: flex;
        justify-content: space-between;
      }
      .signature-box {
        width: 150px;
        text-align: center;
      }
      .signature-line {
        border-bottom: 1px solid #000;
        height: 30px;
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
        font-size: 60px; 
        color: rgba(37,99,235,0.1); 
        font-weight: bold;
      }
    </style>
  </head>
  <body>
    <div class="watermark"><span>QUOTATION</span></div>
    <div class="container">
      <div style="text-align:center; margin-bottom:20px;">
        <img src="/lazio_site/assets/images/logo.png" alt="Lazio Logo" style="max-width:300px; width:80%; height:auto;">
      </div>
      <div class="header">
        <div class="company-info">
          <div>
            <div class="company-name">LAZIO CONSTRUCTION COMPANY</div>
            <p>Bbunga, Uganda</p>
            <p>Phone: +256 744190183</p>
            <p>Email: info@lazioconstruction.example</p>
          </div>
          <div class="invoice-info">
            <div class="invoice-title"><?php echo $isQuote ? 'QUOTATION' : 'INVOICE'; ?></div>
            <p><strong>No:</strong> #<?php echo $invoice['id']; ?></p>
            <p><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($invoice['created_at'])); ?></p>
          </div>
        </div>
      </div>
      
      <div class="bill-to">
        <h3><?php echo $isQuote ? 'Prepared For' : 'Bill To'; ?></h3>
        <p><strong><?php echo htmlspecialchars($invoice['first_name'].' '.$invoice['last_name']); ?></strong></p>
      </div>
      
      <?php if ($inline): ?>
        <table class="table">
          <thead>
            <tr>
              <th>Category</th>
              <th>Description</th>
              <th class="text-center">Qty</th>
              <th class="text-right">Unit Price</th>
              <th class="text-right">Amount</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $grand = 0;
            foreach ($grouped as $cat => $items): 
              $cat_total = 0;
              echo "<tr>
                <td colspan='5' style='font-weight:bold; background:#eef2ff; color:#2563eb; font-size:13px;'>"
                .htmlspecialchars(strtoupper($cat))."</td>
              </tr>";
              foreach ($items as $r) {
                $cat_total += $r['subtotal'];
                $grand += $r['subtotal'];
                echo "<tr>
                  <td></td>
                  <td>".htmlspecialchars($r['name'])."</td>
                  <td class='text-center'>".(int)$r['quantity']."</td>
                  <td class='text-right'>UGX ".number_format((float)$r['unit_price'])."</td>
                  <td class='text-right'>UGX ".number_format((float)$r['subtotal'])."</td>
                </tr>";
              }
              echo "<tr class='table-light'>
                <td colspan='4' class='text-end fw-bold'>Subtotal for ".htmlspecialchars($cat)."</td>
                <td class='text-right fw-bold'>UGX ".number_format($cat_total)."</td>
              </tr>";
            endforeach;
            ?>
          </tbody>
          <tfoot>
            <tr class="total-row">
              <td colspan="4" class="text-end"><strong>GRAND TOTAL</strong></td>
              <td class="text-right"><strong>UGX <?php echo number_format($grand); ?></strong></td>
            </tr>
          </tfoot>
        </table>
      <?php else: ?>
        <table class="table">
          <thead>
            <tr>
              <th>Category</th>
              <th>Description</th>
              <th class="text-center">Qty</th>
              <th class="text-right">Unit Price</th>
              <th class="text-right">Amount</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $grand = 0;
            foreach ($grouped as $cat => $items): 
              $cat_total = 0;
              // Category header row
              echo "<tr>
                <td colspan='5' style='font-weight:bold; background:#eef2ff; color:#2563eb; font-size:13px;'>"
                .htmlspecialchars(strtoupper($cat))."</td>
              </tr>";
              // Items under this category
              foreach ($items as $r) {
                $cat_total += $r['subtotal'];
                $grand += $r['subtotal'];
                echo "<tr>
                  <td></td>
                  <td>".htmlspecialchars($r['name'])."</td>
                  <td class='text-center'>".(int)$r['quantity']."</td>
                  <td class='text-right'>UGX ".number_format((float)$r['unit_price'])."</td>
                  <td class='text-right'>UGX ".number_format((float)$r['subtotal'])."</td>
                </tr>";
              }
              // Subtotal for category
              echo "<tr class='table-light'>
                <td colspan='4' class='text-end fw-bold'>Subtotal for ".htmlspecialchars($cat)."</td>
                <td class='text-right fw-bold'>UGX ".number_format($cat_total)."</td>
              </tr>";
            endforeach;
            ?>
          </tbody>
          <tfoot>
            <tr class="total-row">
              <td colspan="4" class="text-end"><strong>GRAND TOTAL</strong></td>
              <td class="text-right"><strong>UGX <?php echo number_format($grand); ?></strong></td>
            </tr>
          </tfoot>
        </table>
      <?php endif; ?>
      
      
      
      <div class="signature-section">
        <div class="signature-box">
          <div class="signature-line"></div>
          <p>Authorized Signature</p>
        </div>
        <div class="signature-box">
          <div class="signature-line"></div>
          <p>Date</p>
        </div>
      </div>
      
      <div class="no-print" style="margin-top: 24px; text-align: center;">
        <button onclick="window.print()" style="padding: 11px 24px; background: linear-gradient(135deg,#2563eb 0%,#3b82f6 100%); color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; box-shadow: 0 8px 20px -8px rgba(37,99,235,0.5);">🖨️ Print / Save as PDF</button>
      </div>
    </div>
  </body>
</html>
