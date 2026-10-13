
-- MySQL schema for Lazio Construction Company CMS
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','client','intern') NOT NULL DEFAULT 'client',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS clients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  first_name VARCHAR(50) NOT NULL,
  last_name VARCHAR(50) NOT NULL,
  address VARCHAR(255) NOT NULL,
  phone VARCHAR(50) NOT NULL,
  gender ENUM('Male','Female') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category VARCHAR(100) NOT NULL,
  name VARCHAR(150) NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS invoices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT NOT NULL,
  total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  sent_to_client BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS invoice_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  invoice_id INT NOT NULL,
  item_id INT NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
  FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS uploads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type ENUM('image','video') NOT NULL,
  path VARCHAR(255) NOT NULL,
  category VARCHAR(100) NOT NULL,
  description VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS agreements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT NOT NULL,
  ceo_name VARCHAR(150) NOT NULL DEFAULT 'ENG: BENARD MATHEW O.',
  labour_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  commitment_fee DECIMAL(14,2) NOT NULL DEFAULT 200000,
  deposit_percent DECIMAL(5,2) NOT NULL DEFAULT 20.00,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS agreement_phases (
  id INT AUTO_INCREMENT PRIMARY KEY,
  agreement_id INT NOT NULL,
  phase_number INT NOT NULL,
  content TEXT NOT NULL,
  FOREIGN KEY (agreement_id) REFERENCES agreements(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS agreement_representatives (
  id INT AUTO_INCREMENT PRIMARY KEY,
  agreement_id INT NOT NULL,
  side ENUM('company','client') NOT NULL,
  name VARCHAR(150) NOT NULL,
  position VARCHAR(150) NOT NULL,
  contact VARCHAR(100) NULL,
  sort_order INT NOT NULL DEFAULT 1,
  FOREIGN KEY (agreement_id) REFERENCES agreements(id) ON DELETE CASCADE
);

-- Internship module (same definitions as sql/internship_module.sql)
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
);

CREATE TABLE IF NOT EXISTS internship_services (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_internship_services_name (name)
);

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
);

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
);

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
);

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

-- Seed admin (username: admin, password: admin123) - change after first login!
INSERT INTO users (username, password_hash, role)
VALUES ('admin', '$2y$10$S6sG16XpM0yR9f3tUz3OhuOYG7M6wH6N.HZ0cB1g1eJ32jQH5jKvm', 'admin')
ON DUPLICATE KEY UPDATE username=username;
