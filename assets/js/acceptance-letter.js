/*
 * Acceptance letter template (Lazio Construction Company).
 *
 * ONE template used everywhere a letter is shown:
 *   - the live preview in the admin dashboard
 *   - the print page opened by the admin
 *   - the print page opened by the intern
 * so what the admin previews is exactly what is printed.
 *
 * Usage:  container.innerHTML = LazioLetter.build(data);
 * `data` fields: recipient_name, reg_number, institution, course, letter_date,
 * start_date, end_date, duration_text, services[], working_hours, conditions,
 * signatory_name, signatory_title, ref (optional).
 *
 * Styles live in assets/css/letter.css.
 */
(function (global) {
  'use strict';

  // ---- Company details printed on the letterhead (edit here) ----
  var COMPANY = {
    name: 'Lazio Construction Company Limited',
    tagline: 'Building with integrity, innovation & impact',
    address: 'Bbunga, Uganda',
    phone: '+256 744190183',
    logo: '/lazio_site/assets/images/logo.png'
  };

  // ---- Defaults offered when drafting a new letter ----
  var DEFAULTS = {
    working_hours: 'Monday – Saturday, 8:00 a.m. to 5:00 p.m.',
    signatory_name: 'ENG: BENARD MATHEW O.',
    signatory_title: 'Chief Executive Officer',
    // One condition per line. {working_hours} is replaced by the working hours field.
    conditions: [
      'You are expected to observe the official working hours ({working_hours}) and to adhere to the company\'s dress code and code of conduct.',
      'You are required to follow all site safety instructions and to wear the appropriate protective gear while on site.',
      'You are required to present your academic documents and a copy of this letter on your reporting day.',
      'All work, data, designs, and information accessed or created during your internship shall remain strictly confidential and the property of Lazio Construction Company Limited.',
      'An internship completion certificate will be issued upon successful completion of the internship period and submission of any required reports.'
    ].join('\n')
  };

  var MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July',
                'August', 'September', 'October', 'November', 'December'];
  var ONES = ['zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten',
              'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];
  var TENS = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
    });
  }

  // 'YYYY-MM-DD' -> local Date (no timezone shifting), or null.
  function parseDate(s) {
    var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(s || ''));
    if (!m) return null;
    var d = new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]));
    return (d.getFullYear() === Number(m[1]) && d.getMonth() === Number(m[2]) - 1 && d.getDate() === Number(m[3])) ? d : null;
  }

  function ordinal(n) {
    var v = n % 100;
    if (v >= 11 && v <= 13) return n + 'th';
    switch (n % 10) {
      case 1: return n + 'st';
      case 2: return n + 'nd';
      case 3: return n + 'rd';
      default: return n + 'th';
    }
  }

  // '2026-12-07' -> '7th December 2026'
  function formatDate(s) {
    var d = parseDate(s);
    return d ? ordinal(d.getDate()) + ' ' + MONTHS[d.getMonth()] + ' ' + d.getFullYear() : '';
  }

  function numberWords(n) {
    n = Math.floor(Number(n));
    if (!isFinite(n) || n < 0 || n > 99) return String(n);
    if (n < 20) return ONES[n];
    return TENS[Math.floor(n / 10)] + (n % 10 ? '-' + ONES[n % 10] : '');
  }

  /*
   * Joins items into a sentence list:
   *   1 item  -> "A"
   *   2 items -> "A and B"
   *   3+      -> "A, B, C, and D"   (a comma between every item, then "and" before the last)
   */
  function joinList(items) {
    var list = (items || []).map(function (s) { return String(s).trim(); }).filter(Boolean);
    if (list.length === 0) return '';
    if (list.length === 1) return list[0];
    if (list.length === 2) return list[0] + ' and ' + list[1];
    return list.slice(0, -1).join(', ') + ', and ' + list[list.length - 1];
  }

  // Describes the period between two dates, e.g. "approximately ten (10) weeks".
  function describeDuration(startStr, endStr) {
    var s = parseDate(startStr), e = parseDate(endStr);
    if (!s || !e || e < s) return '';
    var days = Math.round((e - s) / 86400000) + 1; // both days count
    if (days < 14) return numberWords(days) + ' (' + days + ') ' + (days === 1 ? 'day' : 'days');
    var weeks = Math.round(days / 7);
    if (weeks <= 12) {
      return (days % 7 === 0 ? '' : 'approximately ') + numberWords(weeks) + ' (' + weeks + ') weeks';
    }
    var months = Math.max(1, Math.round(days / 30.44));
    return 'approximately ' + numberWords(months) + ' (' + months + ') ' + (months === 1 ? 'month' : 'months');
  }

  // A value, or a highlighted placeholder while the form is still being filled in.
  function field(value, placeholder, bold) {
    var v = String(value == null ? '' : value).trim();
    if (!v) return '<span class="lt-missing">[' + esc(placeholder) + ']</span>';
    return bold ? '<strong>' + esc(v) + '</strong>' : esc(v);
  }

  function build(d) {
    d = d || {};
    var name = field(d.recipient_name, 'Intern name', true);
    var reg = field(d.reg_number, 'Registration number', true);
    var start = formatDate(d.start_date);
    var end = formatDate(d.end_date);
    var letterDate = formatDate(d.letter_date);
    var services = joinList(d.services);
    var period = String(d.duration_text || '').trim() || describeDuration(d.start_date, d.end_date);
    var hours = String(d.working_hours || '').trim();

    var conditions = String(d.conditions == null ? '' : d.conditions)
      .split(/\r\n|\r|\n/)
      .map(function (l) { return l.trim(); })
      .filter(Boolean)
      .map(function (line, i) {
        var text = esc(line).replace(/\{working_hours\}/gi, esc(hours || '[working hours]'));
        return '<div class="lt-cond"><strong class="lt-cond-n">' + (i + 1) + '.</strong><div>' + text + '</div></div>';
      }).join('');

    var company = esc(COMPANY.name);

    return '' +
    '<div class="letter-sheet">' +
      '<table class="lt-table">' +
        '<thead><tr><td>' +
          '<div class="lt-band"><span></span><span></span><span></span></div>' +
          '<div class="lt-head">' +
            '<div class="lt-brand">' +
              '<img class="lt-logo" src="' + esc(COMPANY.logo) + '" alt="">' +
              '<div><div class="lt-company">' + company + '</div><div class="lt-tagline">' + esc(COMPANY.tagline) + '</div></div>' +
            '</div>' +
            '<div class="lt-contact"><div>' + esc(COMPANY.address) + '</div><div><strong>Tel: ' + esc(COMPANY.phone) + '</strong></div></div>' +
          '</div>' +
          '<div class="lt-rule"></div>' +
        '</td></tr></thead>' +
        '<tbody><tr><td class="lt-body">' +
          '<div class="lt-date">' + (letterDate ? esc(letterDate) : '<span class="lt-missing">[Letter date]</span>') + '</div>' +
          (d.ref ? '<div class="lt-ref">Ref: ' + esc(d.ref) + '</div>' : '') +
          '<div class="lt-address">' +
            '<div>' + name + '</div>' +
            '<div>Reg. No.: ' + reg + '</div>' +
            '<div>' + field(d.course, 'Course') + '</div>' +
            '<div>' + field(d.institution, 'Institution') + '</div>' +
          '</div>' +
          '<p>Dear ' + name + ',</p>' +
          '<p class="lt-re">RE: ACCEPTANCE OF INTERNSHIP APPLICATION</p>' +
          '<p>We are pleased to inform you that your application for an internship position at ' + company + ' has been reviewed and accepted. ' +
            'On behalf of the Management and Staff of ' + company + ', we warmly welcome you to our organisation.</p>' +
          '<p>Your internship will commence on ' + (start ? '<strong>' + esc(start) + '</strong>' : '<span class="lt-missing">[start date]</span>') +
            ' and conclude on ' + (end ? '<strong>' + esc(end) + '</strong>' : '<span class="lt-missing">[end date]</span>') + ', giving you a period of ' +
            (period ? esc(period) : '<span class="lt-missing">[period]</span>') +
            ' of practical training and hands-on experience in ' +
            (services ? '<strong>' + esc(services) + '</strong>' : '<span class="lt-missing">[training areas]</span>') + '.</p>' +
          '<p>During this period, you will work alongside our experienced team of construction professionals and technicians. ' +
            'You will be expected to contribute meaningfully to ongoing projects, demonstrate creativity and initiative, ' +
            'and uphold the highest standards of professionalism, safety, and conduct.</p>' +
          (conditions ? '<p class="lt-keep">Please take note of the following conditions governing your internship:</p><div class="lt-conds">' + conditions + '</div>' : '') +
          '<div class="lt-closing">' +
            '<p>We trust that this opportunity will add significant value to your academic and professional growth. ' +
              'We look forward to a mutually beneficial engagement and hope that your time with us will be both productive and enriching.</p>' +
            '<p class="lt-yours">Yours faithfully,</p>' +
            '<div class="lt-sign-space"></div>' +
            '<div class="lt-signatory">' +
              '<div class="lt-sign-line"></div>' +
              '<div><strong>' + field(d.signatory_name, 'Signatory name') + '</strong></div>' +
              '<div>' + field(d.signatory_title, 'Signatory title') + '</div>' +
              '<div>' + company + '</div>' +
            '</div>' +
            '<p class="lt-date-line">Date: ' + (letterDate ? esc(letterDate) : '<span class="lt-missing">[Letter date]</span>') + '</p>' +
          '</div>' +
        '</td></tr></tbody>' +
        '<tfoot><tr><td>' +
          '<div class="lt-foot-spacer"></div>' +
          '<div class="lt-foot">' + company + ' | ' + esc(COMPANY.tagline) + '</div>' +
        '</td></tr></tfoot>' +
      '</table>' +
    '</div>';
  }

  // Scales a rendered sheet down to fit its container (preview only) and
  // returns how many A4 pages the letter currently needs.
  function fit(wrap) {
    var sheet = wrap && wrap.querySelector('.letter-sheet');
    if (!sheet) return 1;
    sheet.style.transform = 'none';
    var scale = Math.min(1, wrap.clientWidth / sheet.offsetWidth);
    sheet.style.transformOrigin = 'top left';
    sheet.style.transform = scale < 1 ? 'scale(' + scale + ')' : 'none';
    wrap.style.height = Math.ceil(sheet.offsetHeight * scale) + 'px';
    var pagePx = 297 * 96 / 25.4;
    return Math.max(1, Math.ceil((sheet.offsetHeight - 4) / pagePx));
  }

  global.LazioLetter = {
    COMPANY: COMPANY,
    DEFAULTS: DEFAULTS,
    build: build,
    fit: fit,
    formatDate: formatDate,
    joinList: joinList,
    describeDuration: describeDuration,
    numberWords: numberWords,
    ordinal: ordinal
  };
})(typeof window !== 'undefined' ? window : globalThis);
