<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'intern') {
  header('Location: /lazio_site/auth/login.php'); exit;
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Intern Portal - Lazio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/lazio_site/assets/css/root.css" rel="stylesheet">
  </head>
  <body>
    <div id="header-include"></div>
    <main class="container my-4">
      <div class="admin-shell-header mb-3">
        <div>
          <span class="section-eyebrow"><i class="bi bi-mortarboard"></i>Intern Portal</span>
          <h1 class="h3 fw-bold mb-1">Welcome, <span id="welcomeName">intern</span></h1>
          <p class="text-muted mb-0">Apply for your internship, upload your documents and print your acceptance letter.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <button class="btn btn-outline-primary btn-sm" id="editProfileBtn"><i class="bi bi-pencil-square me-1"></i>Edit profile</button>
          <button class="btn btn-outline-secondary btn-sm" id="changePasswordBtn"><i class="bi bi-key me-1"></i>Change password</button>
        </div>
      </div>

      <div id="portalAlert" class="alert d-none" role="alert"></div>

      <div class="row g-3 mb-4" id="profileTiles">
        <div class="col-6 col-lg-3"><div class="stat"><span class="stat-icon"><i class="bi bi-card-text"></i></span><div class="small-muted">Registration No.</div><div class="fw-bold" id="pReg">—</div></div></div>
        <div class="col-6 col-lg-3"><div class="stat"><span class="stat-icon"><i class="bi bi-bank"></i></span><div class="small-muted">Institution</div><div class="fw-bold" id="pInst">—</div></div></div>
        <div class="col-6 col-lg-3"><div class="stat"><span class="stat-icon"><i class="bi bi-journal-text"></i></span><div class="small-muted">Course</div><div class="fw-bold" id="pCourse">—</div></div></div>
        <div class="col-6 col-lg-3"><div class="stat"><span class="stat-icon"><i class="bi bi-telephone"></i></span><div class="small-muted">Phone</div><div class="fw-bold" id="pPhone">—</div></div></div>
      </div>

      <ul class="nav nav-tabs mb-3" id="portalTabs" role="tablist">
        <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabLetters" type="button"><i class="bi bi-envelope-paper me-1"></i>My Letters <span class="badge text-bg-secondary ms-1" id="lettersCount">0</span></button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabApplications" type="button"><i class="bi bi-send me-1"></i>My Applications</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabDocuments" type="button"><i class="bi bi-folder2-open me-1"></i>My Documents</button></li>
      </ul>

      <div class="tab-content">
        <!-- Letters -->
        <div class="tab-pane fade show active" id="tabLetters">
          <div class="card"><div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead><tr><th class="border-0">Reference</th><th class="border-0">Letter date</th><th class="border-0">Internship period</th><th class="border-0">Training areas</th><th class="border-0 text-end">Actions</th></tr></thead>
                <tbody id="lettersBody"><tr><td colspan="5" class="text-center text-muted py-4">Loading letters...</td></tr></tbody>
              </table>
            </div>
          </div></div>
          <p class="small text-muted mt-2 mb-0"><i class="bi bi-info-circle me-1"></i>You can print your letters as many times as you need. Use "Print / Save as PDF" on the letter page.</p>
        </div>

        <!-- Applications -->
        <div class="tab-pane fade" id="tabApplications">
          <div class="alert alert-info">
            <i class="bi bi-cash-coin me-2"></i><strong>Organisation fee:</strong> before your application is approved, interns pay
            <strong>UGX <span class="fee-amount">20,000</span></strong>, which helps us organise your internship better. Pay through the company bank account or a verified mobile money number
            (ask the office for the details), then add your payment reference to your application. Any further internship fee is discussed in person with the Directors/Instructors.
          </div>
          <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h5 class="fw-bold mb-0">Your applications</h5>
            <button class="btn btn-primary btn-sm" id="newApplicationBtn"><i class="bi bi-plus-lg me-1"></i>New application</button>
          </div>

          <div class="card mb-3 d-none" id="applicationFormCard">
            <div class="card-body">
              <h6 class="fw-bold mb-3">Apply for an internship</h6>
              <form id="applicationForm" novalidate>
                <label class="form-label">Training areas you want to join <span class="text-danger">*</span></label>
                <div class="row g-2 mb-3" id="servicesChecklist"><div class="col-12 text-muted small">Loading training areas...</div></div>
                <div class="row g-3 mb-3">
                  <div class="col-12 col-md-6"><label class="form-label">Start date <span class="text-danger">*</span></label><input type="date" name="start_date" class="form-control" required></div>
                  <div class="col-12 col-md-6"><label class="form-label">End date <span class="text-danger">*</span></label><input type="date" name="end_date" class="form-control" required>
                    <div class="form-text">Use the period given by your institution (see your application letter).</div></div>
                </div>
                <div class="mb-3">
                  <label class="form-label">Modules your institution requires you to cover <span class="text-muted fw-normal">(optional)</span></label>
                  <textarea name="institution_modules" class="form-control" rows="2" maxlength="1000" placeholder="e.g. Installation, maintenance and repair of electrical conduits and sockets"></textarea>
                </div>
                <div class="mb-3">
                  <label class="form-label">Message <span class="text-muted fw-normal">(optional)</span></label>
                  <textarea name="message" class="form-control" rows="2" maxlength="1000"></textarea>
                </div>
                <div class="row g-3 mb-3">
                  <div class="col-12 col-md-6">
                    <label class="form-label">Application letter from your institution <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="file" name="application_letter" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                    <div class="form-text">PDF, JPG, PNG, DOC or DOCX, up to <span class="max-mb">5</span> MB.</div>
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label">Payment reference <span class="text-muted fw-normal">(optional, add it after paying)</span></label>
                    <input name="fee_reference" class="form-control" maxlength="100" placeholder="Mobile money / bank transaction ID">
                  </div>
                </div>
                <div class="d-flex gap-2">
                  <button class="btn btn-success" id="applicationSubmit" type="submit"><i class="bi bi-send-check me-1"></i>Submit application</button>
                  <button class="btn btn-outline-secondary" type="button" id="cancelApplicationBtn">Cancel</button>
                </div>
              </form>
            </div>
          </div>

          <div id="applicationsList"><p class="text-muted small">Loading applications...</p></div>
        </div>

        <!-- Documents -->
        <div class="tab-pane fade" id="tabDocuments">
          <div class="card mb-3"><div class="card-body">
            <h6 class="fw-bold mb-3">Upload a document</h6>
            <form id="documentForm" class="row g-3 align-items-end" novalidate>
              <div class="col-12 col-md-4"><label class="form-label">Document type</label><select name="doc_type" id="docTypeSelect" class="form-select"></select></div>
              <div class="col-12 col-md-5"><label class="form-label">File</label><input type="file" name="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                <div class="form-text">PDF, JPG, PNG, DOC or DOCX, up to <span class="max-mb">5</span> MB.</div></div>
              <div class="col-12 col-md-3"><button class="btn btn-primary w-100" id="documentSubmit" type="submit"><i class="bi bi-upload me-1"></i>Upload</button></div>
            </form>
          </div></div>
          <div class="card"><div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead><tr><th class="border-0">Type</th><th class="border-0">File</th><th class="border-0">Size</th><th class="border-0">Uploaded</th><th class="border-0 text-end">Actions</th></tr></thead>
                <tbody id="documentsBody"><tr><td colspan="5" class="text-center text-muted py-4">Loading documents...</td></tr></tbody>
              </table>
            </div>
          </div></div>
          <p class="small text-muted mt-2 mb-0"><i class="bi bi-shield-lock me-1"></i>Only you and the Lazio office can see your documents. Remember to bring your original academic documents on your reporting day.</p>
        </div>
      </div>
    </main>

    <!-- Edit profile -->
    <div class="modal fade" id="profileModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
      <form id="profileForm">
        <div class="modal-header"><h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit profile</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div id="profileAlert" class="alert alert-danger d-none"></div>
          <div class="mb-3"><label class="form-label">Registration number</label><input id="pfReg" class="form-control" disabled><div class="form-text">Your registration number is your username. Contact the office to change it.</div></div>
          <div class="mb-3"><label class="form-label">Full names</label><input name="full_name" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Institution</label><input name="institution" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Course / Programme</label><input name="course" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Phone</label><input name="phone" class="form-control" inputmode="numeric" maxlength="10" required></div>
          <p class="small text-muted mb-0">Letters that have already been issued keep the details they were issued with.</p>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save changes</button></div>
      </form>
    </div></div></div>

    <!-- Change password -->
    <div class="modal fade" id="passwordModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
      <form id="passwordForm">
        <div class="modal-header"><h5 class="modal-title"><i class="bi bi-key me-2"></i>Change password</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div id="passwordAlert" class="alert alert-danger d-none"></div>
          <div class="mb-3"><label class="form-label">Current password</label><input type="password" name="current_password" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">New password</label><input type="password" name="new_password" class="form-control" minlength="6" required><div class="form-text">At least 6 characters.</div></div>
          <div class="mb-3"><label class="form-label">Confirm new password</label><input type="password" name="confirm_password" class="form-control" required></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Update password</button></div>
      </form>
    </div></div></div>

    <div id="footer-include"></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/lazio_site/assets/js/include.js"></script>
    <script src="/lazio_site/assets/js/acceptance-letter.js"></script>
    <script src="/lazio_site/assets/js/intern.js"></script>
  </body>
</html>
