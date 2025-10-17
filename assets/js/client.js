let deletedInvoices = new Map();

async function loadInvoices() {
  const table = document.getElementById('invoiceTableBody');
  // Fetch invoices from API
  const r = await fetch('/lazio_site/client/invoices.php', {credentials:'include'});
  const data = await r.json();
  if (!data.items || data.items.length === 0) {
    table.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">
      <i class="bi bi-receipt icon-md mb-2 d-block"></i>No invoices found.
    </td></tr>`;
    return;
  }
  table.innerHTML = data.items.map(it => `
    <tr>
      <td class="fw-semibold">#${it.invoice_id}</td>
      <td>${new Date(it.created_at).toLocaleDateString()}</td>
      <td class="fw-semibold text-success">UGX ${Number(it.total).toLocaleString()}</td>
      <td><span class="badge bg-success">Received</span></td>
      <td class="text-end">
        <a href="/lazio_site/client/print_invoice_clean.php?id=${it.invoice_id}" target="_blank"
           class="btn btn-outline-primary btn-sm me-1" title="View/Print Invoice">
          <i class="bi bi-eye-fill"></i>
        </a>
        <button onclick="deleteInvoice(${it.invoice_id})"
                class="btn btn-outline-danger btn-sm" title="Delete Invoice">
          <i class="bi bi-trash-fill"></i>
        </button>
      </td>
    </tr>
    <tr>
      <td colspan="5" class="text-center">
        <button class="btn btn-outline-primary btn-sm" onclick="toggleFullInvoice(${it.invoice_id})" id="toggleBtn-${it.invoice_id}">
          <i class="bi bi-eye-fill"></i> View Full Invoice
        </button>
      </td>
    </tr>
    <tr id="fullInvoiceRow-${it.invoice_id}" style="display:none;">
      <td colspan="5" id="fullInvoiceCell-${it.invoice_id}"></td>
    </tr>
  `).join('');
}

function deleteInvoice(invoiceId) {
  if (confirm('Are you sure you want to delete this invoice? This action cannot be undone.')) {
    // Store for undo functionality
    const row = document.getElementById(`invoice-${invoiceId}`);
    if (row) {
      deletedInvoices.set(invoiceId, {
        id: invoiceId,
        html: row.outerHTML,
        deleted_at: new Date()
      });
      row.remove();
      
      // Show undo notification
      showUndoNotification(invoiceId);
    }
  }
}

function showUndoNotification(invoiceId) {
  // Create a temporary notification
  const notification = document.createElement('div');
  notification.className = 'alert alert-warning alert-dismissible fade show position-fixed';
  notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
  notification.innerHTML = `
    Invoice deleted
    <button type="button" class="btn btn-sm btn-outline-warning ms-2" onclick="undoDelete(${invoiceId})">Undo</button>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  `;
  
  document.body.appendChild(notification);
  
  // Auto-remove after 5 seconds
  setTimeout(() => {
    if (notification.parentNode) {
      notification.remove();
    }
  }, 5000);
}

function undoDelete(invoiceId) {
  const deleted = deletedInvoices.get(invoiceId);
  if (deleted) {
    const table = document.getElementById('invoiceTableBody');
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = deleted.html;
    const row = tempDiv.firstElementChild;
    table.appendChild(row);
    deletedInvoices.delete(invoiceId);
  }
}

function refreshInvoices() {
  loadInvoices();
}

async function toggleFullInvoice(invoiceId) {
  const row = document.getElementById(`fullInvoiceRow-${invoiceId}`);
  const cell = document.getElementById(`fullInvoiceCell-${invoiceId}`);
  const btn = document.getElementById(`toggleBtn-${invoiceId}`);
  if (row.style.display === 'none') {
    // Fetch invoice HTML via AJAX
    cell.innerHTML = '<div class="text-center text-muted py-3">Loading...</div>';
    row.style.display = '';
    btn.innerHTML = '<i class="bi bi-eye-slash-fill"></i> Hide Full Invoice';
    // You can create a dedicated endpoint that returns only the invoice table HTML
    const r = await fetch(`/lazio_site/client/print_invoice_clean.php?id=${invoiceId}&inline=1`, {credentials:'include'});
    const html = await r.text();
    cell.innerHTML = html;
  } else {
    row.style.display = 'none';
    btn.innerHTML = '<i class="bi bi-eye-fill"></i> View Full Invoice';
  }
}

// Load invoices on page load
document.addEventListener('DOMContentLoaded', () => {
  function getGreeting() {
    const hour = new Date().getHours();
    if (hour < 12) return "Good morning";
    if (hour < 17) return "Good afternoon";
    return "Good evening";
  }
  if (window.userSecondName) {
    const greetMsg = `${getGreeting()}, ${window.userSecondName}! Welcome to Lazio Construction.`;
    // Display in a greeting div or alert
    const greetingDiv = document.createElement('div');
    greetingDiv.className = 'alert alert-success my-3';
    greetingDiv.textContent = greetMsg;
    document.body.prepend(greetingDiv);
  }
  loadInvoices();
});
