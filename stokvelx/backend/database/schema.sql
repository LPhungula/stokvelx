-- ============================================================
-- StokvelX – Database Schema + Demo Seed Data
-- ============================================================
-- HOW TO USE (XAMPP / WAMP / MAMP):
-- 1. Open phpMyAdmin (http://localhost/phpmyadmin)
-- 2. Click "Import" (or "New" > SQL tab)
-- 3. Select / paste this entire file and run it
-- This will create the `stokvelx` database, all tables, and
-- demo data so the app works immediately after import.
-- ============================================================

CREATE DATABASE IF NOT EXISTS stokvelx CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE stokvelx;

-- ------------------------------------------------------------
-- TABLE: users
-- ------------------------------------------------------------
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(50) NOT NULL,
  last_name VARCHAR(50) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  phone VARCHAR(20),
  password_hash VARCHAR(255) NOT NULL,
  university VARCHAR(120),
  student_number VARCHAR(40),
  id_number VARCHAR(20),
  role ENUM('student','admin') DEFAULT 'student',
  referral_code VARCHAR(20) UNIQUE,
  referred_by INT NULL,
  wallet_balance DECIMAL(10,2) DEFAULT 0.00,
  kyc_verified TINYINT(1) DEFAULT 1,
  biometric_enabled TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (referred_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLE: bank_details
-- ------------------------------------------------------------
CREATE TABLE bank_details (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  bank_name VARCHAR(60),
  account_number VARCHAR(40),
  account_type VARCHAR(30),
  verified TINYINT(1) DEFAULT 0,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLE: circles
-- ------------------------------------------------------------
CREATE TABLE circles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  type ENUM('standard','premium','investment') DEFAULT 'standard',
  weekly_contribution DECIMAL(10,2) NOT NULL,
  max_members INT NOT NULL,
  admin_fee DECIMAL(10,2) DEFAULT 10.00,
  extra_fee DECIMAL(10,2) DEFAULT 0.00,
  cycle_weeks INT DEFAULT 10,
  status ENUM('active','completed') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLE: circle_members
-- ------------------------------------------------------------
CREATE TABLE circle_members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  circle_id INT NOT NULL,
  user_id INT NOT NULL,
  position INT NOT NULL,
  status ENUM('active','completed','removed') DEFAULT 'active',
  joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (circle_id) REFERENCES circles(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_member (circle_id, user_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLE: contributions
-- ------------------------------------------------------------
CREATE TABLE contributions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  circle_id INT NOT NULL,
  user_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  week_number INT NOT NULL,
  due_date DATE NULL,
  status ENUM('paid','pending','late') DEFAULT 'pending',
  payment_method VARCHAR(40),
  paid_at TIMESTAMP NULL,
  FOREIGN KEY (circle_id) REFERENCES circles(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLE: payouts
-- ------------------------------------------------------------
CREATE TABLE payouts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  circle_id INT NOT NULL,
  user_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  payout_date DATE,
  status ENUM('paid','upcoming','pending') DEFAULT 'pending',
  FOREIGN KEY (circle_id) REFERENCES circles(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLE: wallet_transactions
-- ------------------------------------------------------------
CREATE TABLE wallet_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type ENUM('credit','debit') NOT NULL,
  category VARCHAR(40) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  description VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLE: referrals
-- ------------------------------------------------------------
CREATE TABLE referrals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  referrer_id INT NOT NULL,
  referred_id INT NOT NULL,
  bonus_amount DECIMAL(10,2) DEFAULT 10.00,
  status ENUM('pending','paid') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (referred_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLE: chat_messages
-- ------------------------------------------------------------
CREATE TABLE chat_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  circle_id INT NOT NULL,
  user_id INT NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (circle_id) REFERENCES circles(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;


-- ============================================================
-- DEMO SEED DATA
-- ============================================================

-- Passwords:
--   admin@stokvelx.com      / admin123
--   sipho@student.uct.ac.za / password123   (all other demo students also use password123)

INSERT INTO users (id, first_name, last_name, email, phone, password_hash, university, student_number, id_number, role, referral_code, referred_by, wallet_balance) VALUES
(1,  'Admin',   'Founder',     'admin@stokvelx.com',        '+27810000000', '$2y$10$Z5ByrA41u2XLj9iW4hRaMOAmCdIHPLR/GGc/KdeW.57ulmerzcZsC', 'StokvelX HQ', 'ADMIN001',  '0000000000000', 'admin',   'ADMIN01', NULL, 0.00),
(2,  'Sipho',   'Dlamini',     'sipho@student.uct.ac.za',   '+27812345678', '$2y$10$8cUmi0stezK8DAdf.IUgTeK3tIwGgPaXRFZFM5rB7Z6AgWA7aTnEC', 'University of Cape Town', 'STUD2024001', '9001015800082', 'student', 'SIPHO10', NULL, 980.00),
(3,  'Lerato',  'Mokoena',     'lerato@wits.ac.za',         '+27823456789', '$2y$10$8cUmi0stezK8DAdf.IUgTeK3tIwGgPaXRFZFM5rB7Z6AgWA7aTnEC', 'Wits University', 'STUD2024002', '9802025800083', 'student', 'LERATO22', NULL, 320.00),
(4,  'Thabo',   'Khumalo',     'thabo@up.ac.za',            '+27834567890', '$2y$10$8cUmi0stezK8DAdf.IUgTeK3tIwGgPaXRFZFM5rB7Z6AgWA7aTnEC', 'University of Pretoria', 'STUD2024003', '9703035800084', 'student', 'THABO33', NULL, 150.00),
(5,  'Naledi',  'Sithole',     'naledi@ukzn.ac.za',         '+27845678901', '$2y$10$8cUmi0stezK8DAdf.IUgTeK3tIwGgPaXRFZFM5rB7Z6AgWA7aTnEC', 'UKZN', 'STUD2024004', '9904045800085', 'student', 'NALEDI44', NULL, 600.00),
(6,  'Kabelo',  'Dube',        'kabelo@uwc.ac.za',          '+27856789012', '$2y$10$8cUmi0stezK8DAdf.IUgTeK3tIwGgPaXRFZFM5rB7Z6AgWA7aTnEC', 'UWC', 'STUD2024005', '9605055800086', 'student', 'KABELO55', NULL, 75.00),
(7,  'Zanele',  'Mthembu',     'zanelemthembu@uct.ac.za',   '+27867890123', '$2y$10$8cUmi0stezK8DAdf.IUgTeK3tIwGgPaXRFZFM5rB7Z6AgWA7aTnEC', 'University of Cape Town', 'STUD2024006', '9906065800087', 'student', 'ZANELEM66', NULL, 40.00),
(8,  'Lukhanyo','Gumede',      'lukhanyo@uct.ac.za',        '+27878901234', '$2y$10$8cUmi0stezK8DAdf.IUgTeK3tIwGgPaXRFZFM5rB7Z6AgWA7aTnEC', 'University of Cape Town', 'STUD2024007', '0007075800088', 'student', 'LUKHANYO77', 2, 20.00),
(9,  'Amahle',  'Ndlovu',      'amahle@wits.ac.za',         '+27889012345', '$2y$10$8cUmi0stezK8DAdf.IUgTeK3tIwGgPaXRFZFM5rB7Z6AgWA7aTnEC', 'Wits University', 'STUD2024008', '0108085800089', 'student', 'AMAHLE88', 2, 10.00),
(10, 'Bongani', 'Tshabalala',  'bongani@up.ac.za',          '+27890123456', '$2y$10$8cUmi0stezK8DAdf.IUgTeK3tIwGgPaXRFZFM5rB7Z6AgWA7aTnEC', 'University of Pretoria', 'STUD2024009', '0209095800090', 'student', 'BONGANI99', 2, 5.00),
(11, 'Zanele',  'Pillay',      'zanelepillay@gmail.com',    '+27801234567', '$2y$10$8cUmi0stezK8DAdf.IUgTeK3tIwGgPaXRFZFM5rB7Z6AgWA7aTnEC', 'Durban University of Technology', 'STUD2024010', '0310105800091', 'student', 'ZANELEP10', 2, 0.00),
(12, 'Mpho',    'Khoza',       'mpho@gmail.com',            '+27812340000', '$2y$10$8cUmi0stezK8DAdf.IUgTeK3tIwGgPaXRFZFM5rB7Z6AgWA7aTnEC', 'CPUT', 'STUD2024011', '0411115800092', 'student', 'MPHOK11', 2, 0.00),
(13, 'Sive',    'Mahlangu',    'sive@uct.ac.za',            '+27823450000', '$2y$10$8cUmi0stezK8DAdf.IUgTeK3tIwGgPaXRFZFM5rB7Z6AgWA7aTnEC', 'University of Cape Town', 'STUD2024012', '0512125800093', 'student', 'SIVE12', NULL, 0.00);

-- Circles
INSERT INTO circles (id, name, type, weekly_contribution, max_members, admin_fee, extra_fee, cycle_weeks, status) VALUES
(1, 'Campus Builders Stokvel', 'standard',   250.00, 10, 10.00, 0.00,  10, 'active'),
(2, 'Future Leaders Stokvel',  'standard',   300.00, 8,  10.00, 0.00,  8,  'active'),
(3, 'Vision 2025 Stokvel',     'premium',    400.00, 10, 10.00, 50.00, 10, 'active'),
(4, 'Invest & Grow Stokvel',   'investment', 500.00, 15, 15.00, 0.00,  24, 'active'),
(5, 'Campus Connect Stokvel',  'standard',   150.00, 10, 0.00,  0.00,  10, 'completed'),
(6, 'Graduation Goals Circle', 'standard',   200.00, 12, 10.00, 0.00,  12, 'active');

-- Circle members: Campus Builders (circle 1)
INSERT INTO circle_members (circle_id, user_id, position, status) VALUES
(1, 3, 1, 'active'),
(1, 4, 2, 'active'),
(1, 5, 3, 'active'),
(1, 2, 4, 'active'),
(1, 6, 5, 'active'),
(1, 7, 6, 'active'),
(1, 8, 7, 'active'),
(1, 9, 8, 'active'),
(1, 10, 9, 'active'),
(1, 13, 10, 'active');

-- Circle members: Future Leaders (circle 2)
INSERT INTO circle_members (circle_id, user_id, position, status) VALUES
(2, 5, 1, 'active'),
(2, 4, 2, 'active'),
(2, 2, 3, 'active'),
(2, 3, 4, 'active'),
(2, 6, 5, 'active'),
(2, 7, 6, 'active'),
(2, 8, 7, 'active'),
(2, 9, 8, 'active');

-- Circle members: Vision 2025 Premium (circle 3) - a few early members
INSERT INTO circle_members (circle_id, user_id, position, status) VALUES
(3, 5, 1, 'active'),
(3, 6, 2, 'active'),
(3, 3, 3, 'active'),
(3, 4, 4, 'active');

-- Circle members: Invest & Grow (circle 4)
INSERT INTO circle_members (circle_id, user_id, position, status) VALUES
(4, 10, 1, 'active'),
(4, 9, 2, 'active'),
(4, 8, 3, 'active');

-- Circle members: Campus Connect (circle 5, completed) - Sipho already cycled through
INSERT INTO circle_members (circle_id, user_id, position, status) VALUES
(5, 3, 1, 'completed'),
(5, 4, 2, 'completed'),
(5, 5, 3, 'completed'),
(5, 6, 4, 'completed'),
(5, 7, 5, 'completed'),
(5, 8, 6, 'completed'),
(5, 2, 7, 'completed'),
(5, 9, 8, 'completed'),
(5, 10, 9, 'completed'),
(5, 13, 10, 'completed');

-- Circle members: Graduation Goals (circle 6) - 7 of 12 spots filled
INSERT INTO circle_members (circle_id, user_id, position, status) VALUES
(6, 3, 1, 'active'),
(6, 4, 2, 'active'),
(6, 5, 3, 'active'),
(6, 6, 4, 'active'),
(6, 7, 5, 'active'),
(6, 8, 6, 'active'),
(6, 9, 7, 'active');

-- Contributions: Campus Builders, week 4 (current) - 7 of 10 paid
INSERT INTO contributions (circle_id, user_id, amount, week_number, due_date, status, payment_method, paid_at) VALUES
(1, 3, 250.00, 4, '2024-05-24', 'paid', 'eft', '2024-05-15 09:15:00'),
(1, 4, 250.00, 4, '2024-05-24', 'paid', 'card', '2024-05-15 09:32:00'),
(1, 5, 250.00, 4, '2024-05-24', 'paid', 'wallet', '2024-05-16 11:00:00'),
(1, 2, 250.00, 4, '2024-05-24', 'paid', 'wallet', '2024-05-17 10:02:00'),
(1, 6, 250.00, 4, '2024-05-24', 'paid', 'eft', '2024-05-17 12:00:00'),
(1, 7, 250.00, 4, '2024-05-24', 'paid', 'card', '2024-05-17 13:10:00'),
(1, 8, 250.00, 4, '2024-05-24', 'paid', 'wallet', '2024-05-17 14:00:00'),
(1, 9, 250.00, 4, '2024-05-24', 'pending', NULL, NULL),
(1, 10, 250.00, 4, '2024-05-24', 'pending', NULL, NULL),
(1, 13, 250.00, 4, '2024-05-24', 'pending', NULL, NULL);

-- Contributions: Future Leaders, week 3 (current) - 6 of 8 paid
INSERT INTO contributions (circle_id, user_id, amount, week_number, due_date, status, payment_method, paid_at) VALUES
(2, 5, 300.00, 3, '2024-05-10', 'paid', 'eft', '2024-05-09 10:00:00'),
(2, 4, 300.00, 3, '2024-05-10', 'paid', 'card', '2024-05-09 11:00:00'),
(2, 2, 300.00, 3, '2024-05-10', 'paid', 'wallet', '2024-05-10 09:00:00'),
(2, 3, 300.00, 3, '2024-05-10', 'paid', 'eft', '2024-05-10 12:00:00'),
(2, 6, 300.00, 3, '2024-05-10', 'paid', 'card', '2024-05-10 13:00:00'),
(2, 7, 300.00, 3, '2024-05-10', 'paid', 'wallet', '2024-05-10 14:00:00'),
(2, 8, 300.00, 3, '2024-05-17', 'pending', NULL, NULL),
(2, 9, 300.00, 3, '2024-05-17', 'pending', NULL, NULL);

-- Payouts: Campus Builders (net pot = (250-10)*10 = 2400)
INSERT INTO payouts (circle_id, user_id, amount, payout_date, status) VALUES
(1, 3, 2400.00, '2024-05-08', 'paid'),
(1, 4, 2400.00, '2024-05-15', 'paid'),
(1, 5, 2400.00, '2024-05-22', 'paid'),
(1, 2, 2400.00, '2024-05-29', 'upcoming'),
(1, 6, 2400.00, '2024-06-05', 'pending'),
(1, 7, 2400.00, '2024-06-12', 'pending'),
(1, 8, 2400.00, '2024-06-19', 'pending'),
(1, 9, 2400.00, '2024-06-26', 'pending'),
(1, 10, 2400.00, '2024-07-03', 'pending'),
(1, 13, 2400.00, '2024-07-10', 'pending');

-- Payouts: Future Leaders (net pot = (300-10)*8 = 2320)
INSERT INTO payouts (circle_id, user_id, amount, payout_date, status) VALUES
(2, 5, 2320.00, '2024-05-22', 'paid'),
(2, 4, 2320.00, '2024-05-29', 'paid'),
(2, 2, 2320.00, '2024-06-05', 'upcoming'),
(2, 3, 2320.00, '2024-06-12', 'pending'),
(2, 6, 2320.00, '2024-06-19', 'pending'),
(2, 7, 2320.00, '2024-06-26', 'pending'),
(2, 8, 2320.00, '2024-07-03', 'pending'),
(2, 9, 2320.00, '2024-07-10', 'pending');

-- Payout already received by Sipho: Campus Connect (completed)
INSERT INTO payouts (circle_id, user_id, amount, payout_date, status) VALUES
(5, 2, 1500.00, '2024-05-10', 'paid');

-- Wallet transactions for Sipho (user_id = 2), in chronological order
INSERT INTO wallet_transactions (user_id, type, category, amount, description, created_at) VALUES
(2, 'credit', 'deposit',        500.00, 'Wallet top-up via EFT',                              '2024-04-28 08:00:00'),
(2, 'debit',  'withdrawal',     500.00, 'Withdrawal to FNB ****4521',                         '2024-05-01 10:00:00'),
(2, 'credit', 'referral_bonus', 10.00,  'Referral bonus - Bongani Tshabalala joined',         '2024-05-08 09:00:00'),
(2, 'credit', 'payout',         1500.00,'Payout received - Campus Connect Stokvel',           '2024-05-10 08:30:00'),
(2, 'debit',  'contribution',   300.00, 'Contribution payment - Future Leaders Stokvel (Week 3)', '2024-05-10 09:00:00'),
(2, 'credit', 'referral_bonus', 10.00,  'Referral bonus - Amahle Ndlovu joined',              '2024-05-12 09:00:00'),
(2, 'credit', 'referral_bonus', 10.00,  'Referral bonus - Lukhanyo Gumede joined',            '2024-05-15 09:00:00'),
(2, 'debit',  'contribution',   250.00, 'Contribution payment - Campus Builders Stokvel (Week 4)', '2024-05-17 10:02:00');

-- Referrals (Sipho referred 5 people, 3 paid + 2 pending)
INSERT INTO referrals (referrer_id, referred_id, bonus_amount, status, created_at) VALUES
(2, 10, 10.00, 'paid',    '2024-05-08 09:00:00'),
(2, 9,  10.00, 'paid',    '2024-05-12 09:00:00'),
(2, 8,  10.00, 'paid',    '2024-05-15 09:00:00'),
(2, 11, 10.00, 'pending', '2024-05-20 09:00:00'),
(2, 12, 10.00, 'pending', '2024-05-21 09:00:00');

-- Bank details (demo, unverified by default except Sipho's)
INSERT INTO bank_details (user_id, bank_name, account_number, account_type, verified) VALUES
(2, 'FNB', '62812345210', 'Cheque / Current', 1);

-- Chat messages: Campus Builders Stokvel (circle 1)
INSERT INTO chat_messages (circle_id, user_id, message, created_at) VALUES
(1, 1, 'Good morning everyone! This week''s contribution deadline is today at 5pm. Please make sure you''ve paid. 💪', '2024-05-17 09:00:00'),
(1, 3, 'I''ve paid mine! ✅ Reference: SB2024001', '2024-05-17 09:15:00'),
(1, 4, 'Done! Paid via Capitec 🏦', '2024-05-17 09:32:00'),
(1, 2, 'Just paid mine now via wallet! 🙌', '2024-05-17 10:02:00'),
(1, 1, '7/10 members have paid so far. Still waiting on 3 members — please pay before 5pm to avoid being locked out.', '2024-05-17 14:00:00'),
(1, 5, 'Reminder: Sipho''s payout is on 29 May. He''ll receive R2,400 🎊', '2024-05-17 14:15:00'),
(1, 2, 'Can''t wait! 😄 This stokvel is the best decision I''ve made this year.', '2024-05-17 14:20:00');

-- Chat messages: Future Leaders Stokvel (circle 2)
INSERT INTO chat_messages (circle_id, user_id, message, created_at) VALUES
(2, 1, 'Welcome to Future Leaders Stokvel! Contributions are due every Friday. 🚀', '2024-05-10 08:00:00'),
(2, 5, 'Excited to be part of this circle!', '2024-05-10 08:30:00'),
(2, 2, 'Paid my contribution for this week ✅', '2024-05-10 09:00:00');

-- Chat messages: Invest & Grow Stokvel (circle 4)
INSERT INTO chat_messages (circle_id, user_id, message, created_at) VALUES
(4, 1, 'Monthly returns statement has been posted. Check your dashboard for details on shared profits. 📈', '2024-05-15 09:00:00');
