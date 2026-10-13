# Internship module — deployment notes

Interns can register (or be registered by the office), apply, upload documents and print
their acceptance letters. The office drafts letters from a single template with a live preview.

## 1. Database (do this first)

Run **`sql/internship_module.sql`** once in phpMyAdmin (SQL tab) on every database
(local XAMPP and the live host). It is safe to run twice. It:

- adds the `intern` role to `users.role`
- creates `interns`, `internship_services`, `intern_applications`, `intern_documents`, `acceptance_letters`
- seeds the 14 training areas

`sql/schema.sql` was updated with the same tables for fresh installs (not needed on the host).

**If the code is uploaded before the SQL has been run** nothing existing breaks: admin and client logins,
invoices and the rest work as before, and the public Internships page and the home page show the trainings
from the poster (built into `includes/intern_helpers.php`). The intern sign-up and the admin Interns /
Acceptance Letters screens show "The internship tables are missing. Run sql/internship_module.sql…" until the SQL is run.
Once the tables exist, the public pages switch to the live training list that the office edits in the dashboard.

## 2. Files to upload (25)

**New (15)**

| File | Purpose |
|---|---|
| `includes/intern_helpers.php` | shared registration / validation / upload code |
| `intern/index.php` | intern portal |
| `intern/api.php` | intern portal API |
| `intern/file.php` | secure download of uploaded documents |
| `intern/print_letter.php` | letter print page (admin + owning intern) |
| `pages/internship.php` | public "view all trainings" page |
| `admin/api/interns.php` | admin: interns, applications, documents |
| `admin/api/internship_services.php` | training areas (public list + admin edit) |
| `admin/api/acceptance_letters.php` | letters: save / list / edit / delete |
| `admin/partials/interns.html` | admin screen: Interns, Applications, Training areas |
| `admin/partials/acceptance_letters.html` | admin screen: draft + preview + saved letters |
| `assets/js/acceptance-letter.js` | the letter template (used by preview AND print) |
| `assets/css/letter.css` | A4 letter styling |
| `assets/js/intern.js` | intern portal script |
| `uploads/interns/.htaccess` | blocks direct web access to uploaded documents |

**Changed (10)**

| File | What changed |
|---|---|
| `auth/login.php` | intern login (reg number = username), portal redirect, phone check on intern password reset |
| `auth/signup.php` | Client / Intern tabs, intern self-registration |
| `assets/js/auth.js` | tabs + intern sign-up form, phone in reset form |
| `assets/js/include.js` | "My Internship" nav link for interns |
| `includes/header.html` | "Internships" + "My Internship" links |
| `includes/footer.html` | "Internships" quick link |
| `index.php` | "Join as an Intern" button + Internships section |
| `admin/dashboard.php` | sidebar: Interns, Acceptance Letters |
| `admin/api/stats.php` | intern counts |
| `admin/partials/stats.html` | intern tiles |

Make sure `uploads/interns/` is writable on the host (the folder is created on first upload if missing).

## 3. Things to confirm (defaults I had to choose)

All of these are editable per letter in the dashboard; the *defaults* live in `assets/js/acceptance-letter.js`.

- Working hours: `Monday – Saturday, 8:00 a.m. to 5:00 p.m.`
- The five standard conditions (adapted from the sample letter; added a site-safety line)
- Signatory: `ENG: BENARD MATHEW O.`, Chief Executive Officer (same as the agreements)
- Letterhead: `Bbunga, Uganda` and `Tel: +256 744190183` (taken from the site footer); company name `Lazio Construction Company Limited`
- Organisation fee `UGX 20,000` (`INTERN_FEE_UGX` in `includes/intern_helpers.php`). Bank / mobile money numbers are **not** in the system; interns are told to ask the office.
- Lists read "A, B, C, and D" (comma before the final "and").

## 4. How it works

- **Intern**: signs up at *Sign Up → I'm an Intern* (reg number = username, own password), applies, uploads documents, prints letters from *My Letters*.
- **Office**: *Interns* → register interns, review applications (mark fee received, accept / reject, add a note), manage training areas.
  *Acceptance Letters* → pick an intern (name, reg number, institution, course load from the database), tick training areas, check the preview, Save / Save & print.
- A letter's intern details are copied into it when saved, so an issued letter never changes if the intern edits their profile.
- Letters can be hidden from the intern (Draft) and shown again at any time.
