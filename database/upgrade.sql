-- ======================================================================
-- CHOLOGHURI MIGRATION / UPGRADE SCRIPT
-- Safely upgrades existing database without data loss
-- ======================================================================

USE chologhuri;

-- 1. Upgrade users table
ALTER TABLE users 
  ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255) DEFAULT 'default-avatar.png' AFTER password,
  ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive') DEFAULT 'active' AFTER profile_image,
  ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- Add indexes if not present
CREATE INDEX IF NOT EXISTS idx_user_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_user_status ON users(status);

-- Disable documented sample travelers and invalidate their shared-password hashes.
UPDATE users SET status = 'inactive', password = '!disabled!'
  WHERE email IN ('fahim@example.com', 'nusrat@example.com', 'tanvir@example.com', 'sadia@example.com');

-- 2. Create admins table
CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(50) DEFAULT 'Super Admin',
  status ENUM('active', 'inactive') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_admin_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Disable documented sample administrators and invalidate their shared-password hashes.
UPDATE admins SET status = 'inactive', password = '!disabled!'
  WHERE email IN ('admin@chologhuri.com', 'foyez@chologhuri.com');

-- 3. Create destinations table
CREATE TABLE IF NOT EXISTS destinations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  slug VARCHAR(120) NOT NULL UNIQUE,
  division VARCHAR(80) NOT NULL,
  location VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  image VARCHAR(255) NOT NULL,
  status ENUM('active', 'inactive') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_dest_slug (slug),
  INDEX idx_dest_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Upgrade tour_packages table
ALTER TABLE tour_packages 
  ADD COLUMN IF NOT EXISTS destination_id INT NULL AFTER id,
  ADD COLUMN IF NOT EXISTS available_seats INT NOT NULL DEFAULT 20 AFTER price,
  ADD COLUMN IF NOT EXISTS start_date DATE NULL AFTER available_seats,
  ADD COLUMN IF NOT EXISTS end_date DATE NULL AFTER start_date,
  ADD COLUMN IF NOT EXISTS itinerary TEXT NULL AFTER description,
  ADD COLUMN IF NOT EXISTS included_services TEXT NULL AFTER itinerary,
  ADD COLUMN IF NOT EXISTS excluded_services TEXT NULL AFTER included_services,
  ADD COLUMN IF NOT EXISTS important_notes TEXT NULL AFTER excluded_services,
  ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- 5. Create package_images table
CREATE TABLE IF NOT EXISTS package_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  package_id INT NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  is_featured TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (package_id) REFERENCES tour_packages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Upgrade bookings table
ALTER TABLE bookings 
  ADD COLUMN IF NOT EXISTS booking_code VARCHAR(30) NULL AFTER id,
  ADD COLUMN IF NOT EXISTS number_of_travelers INT NOT NULL DEFAULT 1 AFTER travel_date,
  ADD COLUMN IF NOT EXISTS total_amount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER number_of_travelers,
  ADD COLUMN IF NOT EXISTS payment_status ENUM('pending', 'verified', 'failed', 'refunded') DEFAULT 'pending' AFTER status,
  ADD COLUMN IF NOT EXISTS special_requests TEXT NULL AFTER payment_status,
  ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- Fill booking_code for any existing records
UPDATE bookings SET booking_code = CONCAT('CHG-', LPAD(id + 10000, 5, '0')) WHERE booking_code IS NULL OR booking_code = '';
UPDATE bookings SET number_of_travelers = people WHERE number_of_travelers = 1 AND people > 1;
UPDATE bookings SET total_amount = total_price WHERE total_amount = 0 AND total_price > 0;

-- 7. Create booking_travelers table
CREATE TABLE IF NOT EXISTS booking_travelers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  phone VARCHAR(30) NULL,
  email VARCHAR(150) NULL,
  age INT NOT NULL DEFAULT 25,
  gender ENUM('Male', 'Female', 'Other') DEFAULT 'Male',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Create payments table
CREATE TABLE IF NOT EXISTS payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL,
  user_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  payment_method ENUM('Cash', 'bKash', 'Nagad', 'Card') NOT NULL,
  transaction_id VARCHAR(100) NOT NULL,
  sender_number VARCHAR(30) NULL,
  receipt_image VARCHAR(255) NULL,
  status ENUM('pending', 'verified', 'failed', 'refunded') DEFAULT 'pending',
  admin_note TEXT NULL,
  paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_pay_trx (transaction_id),
  INDEX idx_pay_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Upgrade reviews table
ALTER TABLE reviews 
  ADD COLUMN IF NOT EXISTS booking_id INT NULL AFTER package_id,
  ADD COLUMN IF NOT EXISTS status ENUM('pending', 'approved', 'hidden') DEFAULT 'approved' AFTER comment;

-- 10. Create wishlist table
CREATE TABLE IF NOT EXISTS wishlist (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  package_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user_package (user_id, package_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (package_id) REFERENCES tour_packages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  type VARCHAR(50) DEFAULT 'info',
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_notif_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Create cancellation_requests table
CREATE TABLE IF NOT EXISTS cancellation_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL,
  user_id INT NOT NULL,
  reason TEXT NOT NULL,
  status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  admin_note TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Create admin_logs table
CREATE TABLE IF NOT EXISTS admin_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NOT NULL,
  action VARCHAR(100) NOT NULL,
  description TEXT NOT NULL,
  ip_address VARCHAR(45) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Create settings table
CREATE TABLE IF NOT EXISTS settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(100) NOT NULL UNIQUE,
  setting_value TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

UPDATE settings SET setting_value = ''
  WHERE setting_key IN ('bkash_merchant', 'nagad_merchant')
    AND setting_value IN ('01712345678', '01812987654');
