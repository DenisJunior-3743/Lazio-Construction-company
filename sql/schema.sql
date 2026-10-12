
-- MySQL schema for Lazio Construction Company CMS
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','client') NOT NULL DEFAULT 'client',
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

-- Seed admin (username: admin, password: admin123) - change after first login!
INSERT INTO users (username, password_hash, role)
VALUES ('admin', '$2y$10$S6sG16XpM0yR9f3tUz3OhuOYG7M6wH6N.HZ0cB1g1eJ32jQH5jKvm', 'admin')
ON DUPLICATE KEY UPDATE username=username;
