-- ============================================================================
-- Lazio Construction Company — Internship module migration
--
-- Run this ONCE in phpMyAdmin (SQL tab) on every database that already exists
-- (local XAMPP and the live host). It is safe to run more than once.
--
-- It:
--   1. adds the 'intern' role to users.role
--   2. creates interns, internship_services, intern_applications,
--      intern_documents and acceptance_letters
--   3. seeds the training areas shown on the internship poster
-- ============================================================================

-- 1. New role ---------------------------------------------------------------
ALTER TABLE users
  MODIFY role ENUM('admin','client','intern') NOT NULL DEFAULT 'client';

-- 2. Tables -----------------------------------------------------------------

-- One row per intern. The registration number is also the login username
-- (users.username), the password lives in users.password_hash.
CREATE TABLE IF NOT EXISTS interns (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  reg_number VARCHAR(50) NOT NULL,
  full_name VARCHAR(150) NOT NULL,
  institution VARCHAR(200) NOT NULL,
  course VARCHAR(200) NOT NULL,
  phone VARCHAR(50) NOT NULL,
  registered_by ENUM('self','admin') NOT NULL DEFAULT 'self',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_interns_user (user_id),
  UNIQUE KEY uq_interns_reg (reg_number),
  CONSTRAINT fk_interns_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Training areas offered to interns (admin can add / rename / hide them).
CREATE TABLE IF NOT EXISTS internship_services (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_internship_services_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- An intern's application. `services` is a JSON array of training-area names.
CREATE TABLE IF NOT EXISTS intern_applications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  intern_id INT NOT NULL,
  services TEXT NOT NULL,
  institution_modules TEXT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  message TEXT NULL,
  status ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  fee_paid TINYINT(1) NOT NULL DEFAULT 0,
  fee_reference VARCHAR(100) NULL,
  admin_note VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_intern_applications_intern (intern_id),
  CONSTRAINT fk_intern_applications_intern FOREIGN KEY (intern_id) REFERENCES interns(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Files an intern uploads (application letter, IDs, academic documents ...).
-- The files themselves live in uploads/interns/<intern_id>/.
CREATE TABLE IF NOT EXISTS intern_documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  intern_id INT NOT NULL,
  application_id INT NULL,
  doc_type ENUM('application_letter','student_id','national_id','academic_document','cv','recommendation','other') NOT NULL DEFAULT 'other',
  original_name VARCHAR(255) NOT NULL,
  stored_name VARCHAR(255) NOT NULL,
  mime_type VARCHAR(100) NOT NULL,
  file_size INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_intern_documents_intern (intern_id),
  KEY idx_intern_documents_application (application_id),
  CONSTRAINT fk_intern_documents_intern FOREIGN KEY (intern_id) REFERENCES interns(id) ON DELETE CASCADE,
  CONSTRAINT fk_intern_documents_application FOREIGN KEY (application_id) REFERENCES intern_applications(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Acceptance letters. The intern's name / reg number / institution / course are
-- copied into the letter when it is saved, so an issued letter never changes
-- if the intern later edits their profile.
CREATE TABLE IF NOT EXISTS acceptance_letters (
  id INT AUTO_INCREMENT PRIMARY KEY,
  intern_id INT NOT NULL,
  application_id INT NULL,
  recipient_name VARCHAR(150) NOT NULL,
  reg_number VARCHAR(50) NOT NULL,
  institution VARCHAR(200) NOT NULL,
  course VARCHAR(200) NOT NULL,
  letter_date DATE NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  duration_text VARCHAR(100) NULL,
  services TEXT NOT NULL,
  working_hours VARCHAR(150) NOT NULL,
  conditions TEXT NOT NULL,
  signatory_name VARCHAR(150) NOT NULL,
  signatory_title VARCHAR(150) NOT NULL,
  sent_to_intern TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_acceptance_letters_intern (intern_id),
  CONSTRAINT fk_acceptance_letters_intern FOREIGN KEY (intern_id) REFERENCES interns(id) ON DELETE CASCADE,
  CONSTRAINT fk_acceptance_letters_application FOREIGN KEY (application_id) REFERENCES intern_applications(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Seed the training areas (from the internship poster) ---------------------
-- Each name is kept free of commas so lists read correctly in letters:
-- "A, B, C, and D".
INSERT IGNORE INTO internship_services (name, sort_order) VALUES
  ('Brick Laying', 1),
  ('Construction Setting-Out & Plan Reading', 2),
  ('Construction Drawings', 3),
  ('Plastering & Finishing', 4),
  ('Ground Screeding & Levelling', 5),
  ('Tiling', 6),
  ('Painting', 7),
  ('Plumbing', 8),
  ('Electrical Pipe Works & Wiring', 9),
  ('Generator Installation & Repair', 10),
  ('Solar Installation & Repair', 11),
  ('Motor Installation & Repair', 12),
  ('Roofing & Formwork', 13),
  ('Steel Bending', 14);
