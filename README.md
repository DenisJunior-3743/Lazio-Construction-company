
# Lazio Construction Company — Website & Admin System

A modern, mobile-first PHP + MySQL project for Lazio Construction Company. Built with Bootstrap 5, role-based access (Admin/Client), invoicing, uploads, and modular header/footer includes.

## Quick Start (InfinityFree or any LAMP)
1. Create a MySQL database and import `sql/schema.sql`.
2. Upload the project files to your hosting (htdocs).
3. Update DB credentials in `config/database.php` if needed.
4. Login as **admin** with:
   - **Username:** `admin`
   - **Password:** `admin123` (change immediately in DB!)
5. Create items & clients, then generate invoices in the Admin dashboard.
6. Clients can log in to see and print their invoices.

## Structure
- `/index.php` — Home
- `/pages/services.php` — Activities (from uploads)
- `/auth/*` — Login, signup, auth check
- `/admin/dashboard.php` + `/admin/api/*` — Admin & JSON endpoints
- `/client/*` — Client portal, print view
- `/uploads/*` — Uploaded images/videos
- `/assets/*` — CSS/JS/images

## Internships
Interns (students on industrial training) register with their registration number, apply, upload documents and print
acceptance letters drafted by the office. Run `sql/internship_module.sql` on the database, then see
[INTERNSHIP_MODULE.md](INTERNSHIP_MODULE.md) for the file list and setup notes.

## Notes
- For PDF, we use the browser's **Print to PDF** from the invoice page.
- Add a server-side PDF tool (e.g., DOMPDF) later if required.
- Basic security included (prepared statements, limited file types). Always harden further for production.
