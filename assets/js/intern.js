/* Intern portal (intern/index.php) */
(function () {
  'use strict';

  var API = '/lazio_site/intern/api.php';
  var SERVICES_API = '/lazio_site/admin/api/internship_services.php?action=list_public';

  var state = { profile: null, docTypes: {}, services: [], fee: 20000, maxMb: 5, applications: [] };

  function $(id) { return document.getElementById(id); }
  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
    });
  }
  function fmtDate(s) { return (window.LazioLetter && LazioLetter.formatDate(s)) || s || ''; }
  function fmtStamp(s) { return s ? fmtDate(String(s).slice(0, 10)) : ''; }
  function fmtSize(b) {
    b = Number(b) || 0;
    return b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB';
  }
  function joinList(a) { return window.LazioLetter ? LazioLetter.joinList(a) : (a || []).join(', '); }

  var alertTimer = null;
  function notify(message, type) {
    var box = $('portalAlert');
    box.className = 'alert alert-' + (type || 'success');
    box.textContent = message;
    box.classList.remove('d-none');
    box.scrollIntoView({behavior: 'smooth', block: 'nearest'});
    clearTimeout(alertTimer);
    alertTimer = setTimeout(function () { box.classList.add('d-none'); }, 9000);
  }

  // One place for every request: handles an expired session and network errors.
  async function call(action, opts) {
    opts = opts || {};
    var init = {credentials: 'include'};
    if (opts.json) {
      init.method = 'POST';
      init.headers = {'Content-Type': 'application/json'};
      init.body = JSON.stringify(opts.json);
    } else if (opts.form) {
      init.method = 'POST';
      init.body = opts.form;
    }
    var res;
    try {
      res = await fetch(API + '?action=' + action, init);
    } catch (e) {
      return {ok: false, message: 'Network error. Please check your connection and try again.'};
    }
    if (res.status === 403) { window.location.href = '/lazio_site/auth/login.php'; return {ok: false, message: 'Please log in again.'}; }
    try { return await res.json(); } catch (e) { return {ok: false, message: 'Unexpected server response.'}; }
  }

  /* ---------- Profile ---------- */

  function renderProfile() {
    var p = state.profile;
    if (!p) return;
    $('welcomeName').textContent = p.full_name;
    $('pReg').textContent = p.reg_number;
    $('pInst').textContent = p.institution;
    $('pCourse').textContent = p.course;
    $('pPhone').textContent = p.phone;
  }

  async function loadProfile() {
    var r = await call('profile');
    if (!r.ok) { notify(r.message || 'Could not load your profile.', 'danger'); return; }
    state.profile = r.intern;
    state.docTypes = r.doc_types || {};
    state.fee = r.fee || 20000;
    state.maxMb = r.max_upload_mb || 5;
    renderProfile();
    document.querySelectorAll('.fee-amount').forEach(function (el) { el.textContent = Number(state.fee).toLocaleString(); });
    document.querySelectorAll('.max-mb').forEach(function (el) { el.textContent = state.maxMb; });
    $('docTypeSelect').innerHTML = Object.keys(state.docTypes).map(function (k) {
      return '<option value="' + esc(k) + '">' + esc(state.docTypes[k]) + '</option>';
    }).join('');
  }

  $('editProfileBtn').addEventListener('click', function () {
    var f = $('profileForm'), p = state.profile;
    if (!p) return;
    $('pfReg').value = p.reg_number;
    f.full_name.value = p.full_name;
    f.institution.value = p.institution;
    f.course.value = p.course;
    f.phone.value = p.phone;
    $('profileAlert').classList.add('d-none');
    bootstrap.Modal.getOrCreateInstance($('profileModal')).show();
  });

  $('profileForm').phone.addEventListener('input', function (e) {
    e.target.value = e.target.value.replace(/\D/g, '').slice(0, 10);
  });

  $('profileForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    var f = e.target, box = $('profileAlert');
    var payload = {full_name: f.full_name.value, institution: f.institution.value, course: f.course.value, phone: f.phone.value};
    var r = await call('update_profile', {json: payload});
    if (!r.ok) { box.textContent = r.message || 'Could not save.'; box.classList.remove('d-none'); return; }
    bootstrap.Modal.getInstance($('profileModal')).hide();
    await loadProfile();
    notify('Your profile was updated.');
  });

  $('changePasswordBtn').addEventListener('click', function () {
    $('passwordForm').reset();
    $('passwordAlert').classList.add('d-none');
    bootstrap.Modal.getOrCreateInstance($('passwordModal')).show();
  });

  $('passwordForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    var f = e.target, box = $('passwordAlert');
    if (f.new_password.value !== f.confirm_password.value) { box.textContent = 'The new passwords do not match.'; box.classList.remove('d-none'); return; }
    var r = await call('change_password', {json: {current_password: f.current_password.value, new_password: f.new_password.value}});
    if (!r.ok) { box.textContent = r.message || 'Could not change the password.'; box.classList.remove('d-none'); return; }
    bootstrap.Modal.getInstance($('passwordModal')).hide();
    notify('Your password was changed.');
  });

  /* ---------- Letters ---------- */

  async function loadLetters() {
    var body = $('lettersBody');
    var r = await call('letters');
    var items = r.items || [];
    $('lettersCount').textContent = items.length;
    if (!items.length) {
      body.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4"><i class="bi bi-envelope-paper icon-md mb-2 d-block"></i>' +
        'No acceptance letters yet. Once your application is approved, your letter will appear here.</td></tr>';
      return;
    }
    body.innerHTML = items.map(function (l) {
      return '<tr>' +
        '<td class="fw-semibold">' + esc(l.ref) + '</td>' +
        '<td>' + esc(fmtDate(l.letter_date)) + '</td>' +
        '<td>' + esc(fmtDate(l.start_date)) + ' &ndash; ' + esc(fmtDate(l.end_date)) + '</td>' +
        '<td class="small">' + esc(joinList(l.services)) + '</td>' +
        '<td class="text-end"><a class="btn btn-outline-primary btn-sm" target="_blank" href="/lazio_site/intern/print_letter.php?id=' + l.id + '">' +
        '<i class="bi bi-printer me-1"></i>View / Print</a></td></tr>';
    }).join('');
  }

  /* ---------- Applications ---------- */

  var STATUS = {
    pending:  {cls: 'text-bg-warning', label: 'Pending review'},
    accepted: {cls: 'text-bg-success', label: 'Accepted'},
    rejected: {cls: 'text-bg-danger',  label: 'Not accepted'}
  };

  async function loadServices() {
    var box = $('servicesChecklist');
    try {
      var res = await fetch(SERVICES_API);
      var r = await res.json();
      state.services = r.items || [];
    } catch (e) { state.services = []; }
    if (!state.services.length) { box.innerHTML = '<div class="col-12 text-muted small">No training areas are available right now.</div>'; return; }
    box.innerHTML = state.services.map(function (s, i) {
      return '<div class="col-12 col-md-6 col-lg-4"><div class="form-check">' +
        '<input class="form-check-input" type="checkbox" name="services[]" value="' + esc(s.name) + '" id="svc' + i + '">' +
        '<label class="form-check-label" for="svc' + i + '">' + esc(s.name) + '</label></div></div>';
    }).join('');
  }

  function docLinks(docs) {
    if (!docs || !docs.length) return '';
    return docs.map(function (d) {
      return '<a class="badge text-bg-light border me-1" target="_blank" href="/lazio_site/intern/file.php?id=' + d.id + '"><i class="bi bi-paperclip me-1"></i>' + esc(d.original_name) + '</a>';
    }).join('');
  }

  function renderApplications() {
    var box = $('applicationsList');
    if (!state.applications.length) {
      box.innerHTML = '<div class="empty-state"><i class="bi bi-send"></i><p class="mb-0">You have not applied yet. Click "New application" to start.</p></div>';
      return;
    }
    box.innerHTML = state.applications.map(function (a) {
      var st = STATUS[a.status] || STATUS.pending;
      var fee = a.fee_paid
        ? '<span class="badge text-bg-success"><i class="bi bi-check-circle me-1"></i>Fee received</span>'
        : '<span class="badge text-bg-secondary">Fee UGX ' + Number(state.fee).toLocaleString() + ' awaiting payment</span>';
      var canEditRef = a.status === 'pending' && !a.fee_paid;
      return '<div class="card mb-3"><div class="card-body">' +
        '<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">' +
          '<div><strong>Application #' + a.id + '</strong> <span class="text-muted small">&middot; submitted ' + esc(fmtStamp(a.created_at)) + '</span></div>' +
          '<div class="d-flex gap-2 flex-wrap">' + fee + '<span class="badge ' + st.cls + '">' + st.label + '</span></div>' +
        '</div>' +
        '<div class="small mb-1"><i class="bi bi-calendar-event me-1"></i>' + esc(fmtDate(a.start_date)) + ' &ndash; ' + esc(fmtDate(a.end_date)) + '</div>' +
        '<div class="small mb-1"><i class="bi bi-tools me-1"></i>' + esc(joinList(a.services)) + '</div>' +
        (a.institution_modules ? '<div class="small mb-1 text-muted"><i class="bi bi-journal-text me-1"></i>Institution modules: ' + esc(a.institution_modules) + '</div>' : '') +
        '<div class="small mb-1 text-muted"><i class="bi bi-receipt me-1"></i>Payment reference: ' + (a.fee_reference ? esc(a.fee_reference) : '<em>not provided</em>') + '</div>' +
        (a.admin_note ? '<div class="alert alert-info py-2 small mt-2 mb-2"><i class="bi bi-chat-left-text me-1"></i>' + esc(a.admin_note) + '</div>' : '') +
        (a.documents.length ? '<div class="mt-2">' + docLinks(a.documents) + '</div>' : '') +
        '<div class="d-flex gap-2 flex-wrap mt-3">' +
          (canEditRef ? '<button class="btn btn-outline-primary btn-sm" data-act="ref" data-id="' + a.id + '"><i class="bi bi-pencil me-1"></i>Payment reference</button>' : '') +
          (a.status === 'pending' ? '<button class="btn btn-outline-danger btn-sm" data-act="withdraw" data-id="' + a.id + '"><i class="bi bi-x-circle me-1"></i>Withdraw</button>' : '') +
          (a.letters ? '<button class="btn btn-success btn-sm" data-act="letters"><i class="bi bi-envelope-paper me-1"></i>View my letter</button>' : '') +
        '</div>' +
      '</div></div>';
    }).join('');
  }

  async function loadApplications() {
    var r = await call('applications');
    state.applications = r.items || [];
    renderApplications();
  }

  $('applicationsList').addEventListener('click', async function (e) {
    var btn = e.target.closest('button[data-act]');
    if (!btn) return;
    var id = Number(btn.dataset.id);
    if (btn.dataset.act === 'letters') {
      bootstrap.Tab.getOrCreateInstance(document.querySelector('[data-bs-target="#tabLetters"]')).show();
    } else if (btn.dataset.act === 'withdraw') {
      if (!confirm('Withdraw this application? You can submit a new one afterwards.')) return;
      var r = await call('application_delete', {json: {id: id}});
      if (!r.ok) { notify(r.message || 'Could not withdraw the application.', 'danger'); return; }
      await loadApplications();
      notify('Application withdrawn.');
    } else if (btn.dataset.act === 'ref') {
      var app = state.applications.find(function (a) { return a.id === id; });
      var ref = prompt('Enter your payment reference / transaction ID:', app && app.fee_reference ? app.fee_reference : '');
      if (ref === null) return;
      var r2 = await call('application_reference', {json: {id: id, fee_reference: ref}});
      if (!r2.ok) { notify(r2.message || 'Could not save the reference.', 'danger'); return; }
      await loadApplications();
      notify('Payment reference saved. The office will confirm your payment.');
    }
  });

  function setApplicationFormVisible(show) {
    $('applicationFormCard').classList.toggle('d-none', !show);
    if (show) $('applicationFormCard').scrollIntoView({behavior: 'smooth', block: 'start'});
  }
  $('newApplicationBtn').addEventListener('click', function () { setApplicationFormVisible(true); });
  $('cancelApplicationBtn').addEventListener('click', function () { setApplicationFormVisible(false); });

  $('applicationForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    var f = e.target;
    if (!f.querySelector('input[name="services[]"]:checked')) { notify('Please select at least one training area.', 'danger'); return; }
    if (!f.start_date.value || !f.end_date.value) { notify('Please choose the start and end dates.', 'danger'); return; }
    if (f.end_date.value < f.start_date.value) { notify('The end date cannot be before the start date.', 'danger'); return; }
    var file = f.application_letter.files[0];
    if (file && file.size > state.maxMb * 1048576) { notify('The application letter is too large (maximum ' + state.maxMb + ' MB).', 'danger'); return; }

    var btn = $('applicationSubmit');
    btn.disabled = true;
    var r = await call('application_create', {form: new FormData(f)});
    btn.disabled = false;
    if (!r.ok) { notify(r.message || 'Could not submit your application.', 'danger'); return; }
    f.reset();
    setApplicationFormVisible(false);
    await Promise.all([loadApplications(), loadDocuments()]);
    notify(r.warning || 'Your application was submitted. The office will review it and contact you.', r.warning ? 'warning' : 'success');
  });

  /* ---------- Documents ---------- */

  async function loadDocuments() {
    var body = $('documentsBody');
    var r = await call('documents');
    var items = r.items || [];
    if (!items.length) {
      body.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4"><i class="bi bi-folder2-open icon-md mb-2 d-block"></i>No documents uploaded yet.</td></tr>';
      return;
    }
    body.innerHTML = items.map(function (d) {
      return '<tr>' +
        '<td>' + esc(state.docTypes[d.doc_type] || d.doc_type) + '</td>' +
        '<td class="fw-semibold">' + esc(d.original_name) + '</td>' +
        '<td>' + fmtSize(d.file_size) + '</td>' +
        '<td>' + esc(fmtStamp(d.created_at)) + '</td>' +
        '<td class="text-end"><div class="btn-group btn-group-sm">' +
          '<a class="btn btn-outline-primary" target="_blank" href="/lazio_site/intern/file.php?id=' + d.id + '" title="View"><i class="bi bi-eye"></i></a>' +
          '<button class="btn btn-outline-danger" data-del="' + d.id + '" title="Delete"><i class="bi bi-trash"></i></button>' +
        '</div></td></tr>';
    }).join('');
  }

  $('documentsBody').addEventListener('click', async function (e) {
    var btn = e.target.closest('button[data-del]');
    if (!btn) return;
    if (!confirm('Delete this document?')) return;
    await call('document_delete', {json: {id: Number(btn.dataset.del)}});
    await Promise.all([loadDocuments(), loadApplications()]);
  });

  $('documentForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    var f = e.target;
    var file = f.file.files[0];
    if (!file) { notify('Please choose a file to upload.', 'danger'); return; }
    if (file.size > state.maxMb * 1048576) { notify('The file is too large (maximum ' + state.maxMb + ' MB).', 'danger'); return; }
    var btn = $('documentSubmit');
    btn.disabled = true;
    var r = await call('document_upload', {form: new FormData(f)});
    btn.disabled = false;
    if (!r.ok) { notify(r.message || 'Upload failed.', 'danger'); return; }
    f.reset();
    await loadDocuments();
    notify('Document uploaded.');
  });

  /* ---------- Start ---------- */

  (async function init() {
    await loadProfile();
    await Promise.all([loadLetters(), loadApplications(), loadDocuments(), loadServices()]);
  })();
})();
