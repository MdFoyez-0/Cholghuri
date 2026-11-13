-- ======================================================================
-- CHOLOGHURI - TRAVEL & TOUR BOOKING MANAGEMENT SYSTEM
-- Upgraded Database Schema & Comprehensive Seed Data
-- ======================================================================

CREATE DATABASE IF NOT EXISTS chologhuri CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE chologhuri;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS admin_logs;
DROP TABLE IF EXISTS cancellation_requests;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS wishlist;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS booking_travelers;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS package_images;
DROP TABLE IF EXISTS tour_packages;
DROP TABLE IF EXISTS destinations;
DROP TABLE IF EXISTS admins;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------
-- 1. USERS TABLE
-- ----------------------------------------------------------------------
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  phone VARCHAR(30) NULL,
  password VARCHAR(255) NOT NULL,
  profile_image VARCHAR(255) DEFAULT 'default-avatar.png',
  status ENUM('active', 'inactive') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_user_email (email),
  INDEX idx_user_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 2. ADMINS TABLE
-- ----------------------------------------------------------------------
CREATE TABLE admins (
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

-- ----------------------------------------------------------------------
-- 3. DESTINATIONS TABLE
-- ----------------------------------------------------------------------
CREATE TABLE destinations (
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

-- ----------------------------------------------------------------------
-- 4. TOUR PACKAGES TABLE
-- ----------------------------------------------------------------------
CREATE TABLE tour_packages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  destination_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  package_name VARCHAR(150) GENERATED ALWAYS AS (title) VIRTUAL,
  destination VARCHAR(100) NOT NULL,
  duration VARCHAR(50) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  available_seats INT NOT NULL DEFAULT 20,
  start_date DATE NULL,
  end_date DATE NULL,
  image VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  itinerary TEXT NULL,
  included_services TEXT NULL,
  excluded_services TEXT NULL,
  important_notes TEXT NULL,
  status ENUM('active', 'inactive') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE,
  INDEX idx_pkg_status (status),
  INDEX idx_pkg_dest (destination_id),
  INDEX idx_pkg_price (price)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 5. PACKAGE IMAGES TABLE
-- ----------------------------------------------------------------------
CREATE TABLE package_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  package_id INT NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  is_featured TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (package_id) REFERENCES tour_packages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 6. BOOKINGS TABLE
-- ----------------------------------------------------------------------
CREATE TABLE bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_code VARCHAR(30) NOT NULL UNIQUE,
  user_id INT NOT NULL,
  package_id INT NOT NULL,
  travel_date DATE NOT NULL,
  number_of_travelers INT NOT NULL DEFAULT 1,
  people INT GENERATED ALWAYS AS (number_of_travelers) VIRTUAL,
  total_amount DECIMAL(10,2) NOT NULL,
  total_price DECIMAL(10,2) GENERATED ALWAYS AS (total_amount) VIRTUAL,
  status ENUM('Pending', 'Confirmed', 'Completed', 'Cancelled', 'Rejected') DEFAULT 'Pending',
  payment_status ENUM('pending', 'verified', 'failed', 'refunded') DEFAULT 'pending',
  special_requests TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (package_id) REFERENCES tour_packages(id) ON DELETE CASCADE,
  INDEX idx_booking_code (booking_code),
  INDEX idx_booking_status (status),
  INDEX idx_booking_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 7. BOOKING TRAVELERS TABLE
-- ----------------------------------------------------------------------
CREATE TABLE booking_travelers (
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

-- ----------------------------------------------------------------------
-- 8. PAYMENTS TABLE
-- ----------------------------------------------------------------------
CREATE TABLE payments (
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

-- ----------------------------------------------------------------------
-- 9. REVIEWS TABLE
-- ----------------------------------------------------------------------
CREATE TABLE reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  package_id INT NOT NULL,
  booking_id INT NULL,
  rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment TEXT NOT NULL,
  review TEXT GENERATED ALWAYS AS (comment) VIRTUAL,
  status ENUM('pending', 'approved', 'hidden') DEFAULT 'approved',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (package_id) REFERENCES tour_packages(id) ON DELETE CASCADE,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL,
  INDEX idx_rev_pkg (package_id),
  INDEX idx_rev_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 10. WISHLIST TABLE
-- ----------------------------------------------------------------------
CREATE TABLE wishlist (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  package_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user_package (user_id, package_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (package_id) REFERENCES tour_packages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 11. NOTIFICATIONS TABLE
-- ----------------------------------------------------------------------
CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  type VARCHAR(50) DEFAULT 'info',
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_notif_user (user_id),
  INDEX idx_notif_unread (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 12. CANCELLATION REQUESTS TABLE
-- ----------------------------------------------------------------------
CREATE TABLE cancellation_requests (
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

-- ----------------------------------------------------------------------
-- 13. ADMIN LOGS TABLE
-- ----------------------------------------------------------------------
CREATE TABLE admin_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NOT NULL,
  action VARCHAR(100) NOT NULL,
  description TEXT NOT NULL,
  ip_address VARCHAR(45) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 14. SETTINGS TABLE
-- ----------------------------------------------------------------------
CREATE TABLE settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(100) NOT NULL UNIQUE,
  setting_value TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================================
-- SEED DATA (REAL PLACES & AUTHENTIC INFORMATION)
-- ======================================================================

-- Disabled sample administrators: provision real admins with unique passwords before use.
INSERT INTO admins (name, email, password, role, status) VALUES
('Sample Super Admin', 'admin@chologhuri.com', '!disabled!', 'Super Admin', 'inactive'),
('Sample Operations Manager', 'foyez@chologhuri.com', '!disabled!', 'Operations Manager', 'inactive');

-- Disabled sample travelers: replace with accounts created through registration.
INSERT INTO users (name, email, phone, password, profile_image, status) VALUES
('Fahim Hossain', 'fahim@example.com', '01711223344', '!disabled!', 'default-avatar.png', 'inactive'),
('Nusrat Jahan', 'nusrat@example.com', '01822334455', '!disabled!', 'default-avatar.png', 'inactive'),
('Tanvir Rahman', 'tanvir@example.com', '01933445566', '!disabled!', 'default-avatar.png', 'inactive'),
('Sadia Islam', 'sadia@example.com', '01644556677', '!disabled!', 'default-avatar.png', 'inactive');

-- Real Destinations in Bangladesh
INSERT INTO destinations (name, slug, division, location, description, image, status) VALUES
('Cox\'s Bazar', 'coxs-bazar', 'Chittagong', 'South-Eastern Coast, Bay of Bengal', 'Home to the world\'s longest unbroken natural sandy sea beach (120 km), featuring Kolatoli, Inani Coral Beach, Himchari waterfalls, and scenic Marine Drive sunset views.', 'images/destinations/coxsbazar.jpg', 'active'),
('Sajek Valley', 'sajek-valley', 'Chittagong', 'Baghaichhari, Rangamati Hill Tracts', 'The Queen of Hills and kingdom of clouds nestled at 1,800 feet above sea level. Experience wooden mountain cottages, floating sea of clouds, Konglak Para summit, and mesmerizing sunrises.', 'images/destinations/sajek.jpg', 'active'),
('Sundarbans', 'sundarbans', 'Khulna', 'Khulna & Bagerhat Coastal Belt', 'The world\'s largest contiguous mangrove wetland forest and UNESCO World Heritage site, famous for the majestic Royal Bengal Tiger, spotted deer, river dolphins, and wilderness waterways.', 'images/destinations/sundarbans.jpg', 'active'),
('Saint Martin\'s Island', 'saint-martin', 'Chittagong', 'Northeast Bay of Bengal, Teknaf', 'The only coral island of Bangladesh, fondly called Narikel Jinjira. Boasts crystalline azure waters, live coral reefs, coconut palm groves, Chera Dwip, and fresh sea crab delights.', 'images/destinations/saintmartin.jpg', 'active'),
('Sreemangal', 'sreemangal', 'Sylhet', 'Moulvibazar, Sylhet Division', 'The Tea Capital of Bangladesh, renowned for miles of undulating terraced green tea estates, seven-layer tea at Nilkantha, ancient Lawachara National Park rainforest, and bird reserves.', 'images/destinations/sreemangal.jpg', 'active'),
('Bandarban', 'bandarban', 'Chittagong', 'Chittagong Hill Tracts', 'Famous for cloud-kissed peaks including Nilgiri, mythical volcanic Boga Lake, Keokradong hiking trails, Golden Buddhist Temple (Buddha Dhatu Jadi), and cascading Amiakhum falls.', 'images/destinations/bandarban.jpg', 'active'),
('Rangamati', 'rangamati', 'Chittagong', 'Kaptai Lake Basin, Hill Tracts', 'Scenic lake district surrounded by lush green hills, featuring picturesque Kaptai Lake boat cruises, iconic red Suspension Bridge, Shuvolong Waterfalls, and vibrant Chakma indigenous crafts.', 'images/destinations/rangamati.jpg', 'active'),
('Kuakata', 'kuakata', 'Barisal', 'Patuakhali, Coastal Southern Coast', 'Affectionately known as Sagor Konna (Daughter of the Sea), famous as one of the few panoramic beaches where both sunrise and sunset can be viewed uninterrupted over the sea.', 'images/destinations/kuakata.jpg', 'active'),
('Tanguar Haor', 'tanguar-haor', 'Sylhet', 'Tahirpur, Sunamganj', 'A Ramsar wetland paradise nestled right beneath the misty Meghalaya hills of India. Renowned for traditional wooden luxury houseboat safaris on pristine turquoise waters and Watch Tower.', 'images/destinations/tanguar.jpg', 'active'),
('Jaflong', 'jaflong', 'Sylhet', 'Gowainghat, Sylhet Border', 'Renowned for picturesque stone collection along the crystal clear Piyain River directly beneath the lush green Meghalaya hills and cascading waterfalls of India.', 'images/destinations/jaflong.jpg', 'active'),
('Bichanakandi', 'bichanakandi', 'Sylhet', 'Rustompur, Gowainghat, Sylhet', 'A magical natural wonderland where rolling layers of the high Khasi mountains meet an expansive bed of smooth river stones and crystal-clear flowing water.', 'images/destinations/bichnakandi.jpg', 'active'),
('Lalbagh Fort', 'lalbagh-fort', 'Dhaka', 'Lalbagh, Old Dhaka', 'An iconic 17th-century Mughal fort complex overlooking Old Dhaka, featuring the marble Tomb of Pari Bibi, the three-domed Lalbagh Mosque, and Diwan-i-Aam historic museum.', 'images/destinations/lalbagh.jpg', 'active'),
('Ahsan Manzil', 'ahsan-manzil', 'Dhaka', 'Kumartoli, Old Dhaka Riverfront', 'The celebrated Pink Palace and former official seat of the Nawab of Dhaka, situated on the banks of the historic Buriganga River with magnificent Indo-Saracenic Revival architecture.', 'images/destinations/ahsanmanzil.jpg', 'active'),
('Somapura Mahavihara', 'somapura-mahavihara', 'Rajshahi', 'Paharpur, Naogaon', 'A majestic UNESCO World Heritage monument and one of the largest Buddhist monasteries south of the Himalayas, dating back to the 8th century Pala Empire with ancient terracotta reliefs.', 'images/destinations/paharpur.jpg', 'active');

-- Tour Packages linked to Destinations
INSERT INTO tour_packages (destination_id, title, destination, duration, price, available_seats, start_date, end_date, image, description, itinerary, included_services, excluded_services, important_notes, status) VALUES
(1, 'Cox\'s Bazar Marine Drive & Beach Escape', 'Cox\'s Bazar', '3 Days / 2 Nights', 6500.00, 16, '2026-10-15', '2026-10-18', 'images/destinations/coxsbazar.jpg', 
'Immerse yourself in the relaxing sound of the Bay of Bengal waves. Explore the world famous 80km Marine Drive by open-hood jeep (Chander Gari), marvel at sunset over Inani Coral Beach, visit Himchari national park hilltops, and taste fresh grilled seafood.',
'Day 1: Arrival at Cox\'s Bazar, Hotel Check-in, Beach leisure, Sunset at Sugandha Beach, Evening barbecue dinner.\nDay 2: Morning trip along Marine Drive to Himchari & Inani coral beach, Parasailing & beach activities, Seafood dinner.\nDay 3: Sunrise walk, visit radiant Burmese market for local souvenirs, departure for Dhaka.',
'AC Bus Transportation (Dhaka - Cox\'s Bazar - Dhaka), 3-Star Deluxe Hotel Accommodation (Twin Share), Daily Buffet Breakfast, Guided Marine Drive Tour, Welcome Coconut Drinks',
'Lunch & Dinner personal orders, Beach water sports (Jet Ski, Quad bike), Personal shopping expenses',
'Please carry a national ID or student card. Sunscreen and comfortable beachwear are highly recommended.', 'active'),

(2, 'Sajek Valley Cloud Kingdom & Konglak Summit', 'Sajek Valley', '2 Days / 1 Night', 5200.00, 12, '2026-10-20', '2026-10-22', 'images/destinations/sajek.jpg',
'Step into paradise above the clouds. Travel through the scenic curves of Khagrachari hills by 4x4 Chander Gari under army escort, stay in cozy hill cottages with balcony views over floating clouds, and hike to the highest Konglak peak.',
'Day 1: Reach Khagrachari early morning, breakfast, army escort ride to Sajek Valley, check-in to hill cottage, sunset at Sajek Helipad, indigenous bamboo chicken dinner.\nDay 2: 5:30 AM sunrise view over sea of clouds, trek to Konglak Para, explore Ruilui village, descent back to Khagrachari, explore Alutila mysterious cave & Tareng hill, return bus.',
'Non-AC / AC Bus to Khagrachari, 4x4 Chander Gari reserved for 2 days, 1 Night hilltop wooden cottage stay, 4 Meals including Authentic Bamboo Chicken Dinner, Entry tickets & local guide fee',
'Personal snacks, hard drinks, laundry, room service tips',
'Electricity is solar-powered in Sajek; carry high-capacity power banks. Sajek is safe and escorted daily by security forces.', 'active'),

(3, 'Sundarbans Mangrove Safari & Bengal Tiger Trail', 'Sundarbans', '3 Days / 2 Nights', 8500.00, 18, '2026-10-25', '2026-10-28', 'images/destinations/sundarbans.jpg',
'Embark on an unforgettable deep-forest river safari cruising through the UNESCO World Heritage mangrove forest. Watch herds of spotted deer, mudskippers, crocodiles, and kingfishers, while experiencing tranquil overnight boat stays.',
'Day 1: Board luxury cruiser ship at Khulna / Mongla Ghat, sail towards forest interior, pass forest station at Harbaria eco-tourism park, forest watchtower walk, sunset river cruise.\nDay 2: Early morning silent wooden rowboat safari in narrow creeks, visit Katka beach & wildlife sanctuary, Jamtola watchtower, evening barbecue on ship deck.\nDay 3: Visit Karamjal crocodile breeding center & monkey trail, sail back to Mongla port, transfer to Dhaka bus.',
'3 Days / 2 Nights cabin stay on board safari ship, All meals (Breakfast, Lunch, Dinner, Evening tea & snacks), Forest department entry permission & armed forest guards, Experienced naturalist eco-guide',
'Any personal medicine expenses, extra soft drinks, tips for boat crew',
'Keep noise levels strictly minimum during forest rides. Plastic littering is legally prohibited in the mangrove biosphere.', 'active'),

(4, 'Saint Martin\'s Coral Island Turquoise Getaway', 'Saint Martin\'s Island', '3 Days / 2 Nights', 7800.00, 14, '2026-11-05', '2026-11-08', 'images/destinations/saintmartin.jpg',
'Escape to the only coral paradise of Bangladesh. Sail across the Bay of Bengal on a sea cruise ship, enjoy coconut water directly from swaying palms, cycle around the island, and take a country boat to uninhabited Chera Dwip.',
'Day 1: Arrival at Teknaf ship terminal, 2.5 hr cruise on Bay of Bengal to Saint Martin, check-in to beachside resort, afternoon swim in transparent turquoise water, sunset coconut sip, live fish BBQ.\nDay 2: Morning speed boat or wooden engine boat tour to Chera Dwip corals, cycling around the western beach, evening musical circle by the bonfire.\nDay 3: Free morning for photography and seashell collection, afternoon cruise back to Teknaf, night coach to Dhaka.',
'Dhaka - Teknaf return bus, Two-way Ship Cruise tickets, 2 Nights beachfront eco-resort accommodation, Daily breakfast, Chera Dwip boat trip, Island guide assistance',
'Water sports, scuba diving trial, personal purchases',
'Respect marine ecology: Do not collect or step harshly on living coral formations.', 'active'),

(5, 'Sreemangal Tea Gardens & Lawachara Forest Expedition', 'Sreemangal', '2 Days / 1 Night', 4500.00, 20, '2026-11-12', '2026-11-14', 'images/destinations/sreemangal.jpg',
'Relax amidst endless vibrant green hills in Bangladesh\'s tea heaven. Walk through century-old tea estates, hike through Lawachara evergreen rainforest to spot Hoolock Gibbons, taste the iconic Seven Layer Tea, and visit tribal Manipuri weaving villages.',
'Day 1: Train / AC bus ride to Sreemangal, check-in to tea garden resort, cycle or tuk-tuk tour of Finlay Tea Estates, taste Nilkantha 7-color tea, visit Madhabpur lake surrounded by tea hills.\nDay 2: Early morning trek inside Lawachara National Park rainforest, visit Khasia tribal village inside forest, shop authentic Manipuri handloom shawls, evening return.',
'Return transport from Dhaka, 1 Night stay at eco-resort amidst tea gardens, All guided sightseeing by private auto/microbus, Entry fees to Lawachara Park, Breakfast & Traditional Khasia dinner',
'Personal beverage, train food expenses',
'Wear comfortable walking shoes for the rainforest trail. Carry a light rain jacket or umbrella.', 'active'),

(6, 'Bandarban Nilgiri Clouds & Golden Temple Tour', 'Bandarban', '3 Days / 2 Nights', 6900.00, 15, '2026-11-18', '2026-11-21', 'images/destinations/bandarban.jpg',
'Witness Bangladesh\'s highest panoramic road as you climb into the clouds at Nilgiri Hill Resort. Visit the sacred Golden Buddhist Temple (Buddha Dhatu Jadi), cross the bamboo bridge at Shoilo Propat waterfall, and explore mysterious Chimbuk Hill.',
'Day 1: Reach Bandarban town, check-in, visit Buddha Dhatu Jadi (Golden Temple) perched atop a high hill, evening sunset at Meghla eco-park.\nDay 2: Chander Gari day-trip ascending to Nilgiri (2,200 ft), touch passing clouds, stop at Chimbuk hill peak and Shoilo Propat natural hill spring, indigenous tribal handicraft market.\nDay 3: Visit Nilachal hill viewpoint for panoramic city vistas, lunch at tribal restaurant, return journey to Dhaka.',
'AC Bus Dhaka - Bandarban return, 4WD Chander Gari for mountain spots, 2 Nights 3-star hotel stay, Entry tickets & army checkpoint clearance, Local tour conductor',
'Meals not specified in package, personal shopping for tribal shawls & hill honey',
'Keep your original National ID card handy for security checkposts in Bandarban.', 'active'),

(7, 'Rangamati Kaptai Lake Cruise & Shuvolong Falls', 'Rangamati', '2 Days / 1 Night', 4800.00, 22, '2026-11-25', '2026-11-27', 'images/destinations/rangamati.jpg',
'Sail across the largest artificial freshwater lake in South Asia. Enjoy long motorized wooden boat cruises surrounded by pine and teak-forested hills, walk across the red Hanging Bridge, and taste fresh Kaptai lake fish curries.',
'Day 1: Arrival at Rangamati, check-in, stroll across the iconic Hanging Suspension Bridge, tribal museum visit, evening lake breeze.\nDay 2: Full day boat safari on Kaptai Lake to Shuvolong Waterfall, visit Tuk Tuk Eco Village, visit traditional Chakma tribal weavers at Rajbon Vihara island, evening departure.',
'Bus transport Dhaka - Rangamati return, Full day private engine boat on Kaptai Lake, 1 Night hotel accommodation, Breakfast & lake island lunch, Tour guide',
'Speedboat upgrades, personal purchases',
'Boat rides are equipped with life jackets. Bring sunglasses and cameras for scenic reflections.', 'active'),

(8, 'Kuakata Sunset & Sunrise Sea Paradise', 'Kuakata', '3 Days / 2 Nights', 5800.00, 18, '2026-12-01', '2026-12-04', 'images/destinations/kuakata.jpg',
'Experience the unique magic of Sagor Konna where you can view both the morning sunrise and evening sunset melting directly into the Bay of Bengal from the exact same shoreline. Visit the century-old Rakhine Buddhist Temple and Jhau forest.',
'Day 1: Direct AC bus via Padma Bridge to Kuakata, hotel check-in, leisure on the wide sandy beach, stunning sunset view at beach front, beach barbecue.\nDay 2: 5:00 AM trip to Gongamoti Char to witness the golden sunrise, visit red crab island, Rakhine women weavers village, ancient Buddhist temple with massive statue, Jhau bon eco-trail.\nDay 3: Morning beach bike ride or sea swim, shopping dried fish & local pickles, afternoon departure via Padma Bridge.',
'AC bus transportation via Padma expressway, 2 Nights hotel stay near beach, Guided sunrise & sunset motorcycle/jeep tour, Daily breakfast, Entry permits',
'Lunch and Dinner personal preference, beach bike rental for personal joyrides',
'The route now takes under 6 hours from Dhaka thanks to Padma Bridge!', 'active'),

(9, 'Tanguar Haor Luxury Houseboat Odyssey', 'Tanguar Haor', '2 Days / 1 Night', 7500.00, 16, '2026-12-10', '2026-12-12', 'images/destinations/tanguar.jpg',
'Experience the ultimate water paradise. Sleep on an authentic wooden luxury houseboat (trawler) cruising through crystal clear waters with the magnificent Indian Meghalaya hills in the backdrop. Swim at Niladri Lake (Shahid Siraj Lake) and Shimul Bagan.',
'Day 1: Arrive Sunamganj early morning, transfer to Tahirpur Ghat, board fully furnished luxury wooden houseboat, sail into Tanguar Haor sanctuary, swim in open turquoise water, visit Watch Tower, sunset tea on boat rooftop, overnight boat stay with starlight.\nDay 2: Sail to Niladri Lake (limestone blue water lake), visit magical Shimul Bagan red flower forest, Jadukata river clean water beach, return to Tahirpur ghat, evening bus to Dhaka.',
'Sunamganj return bus, 2 Days / 1 Night on luxury wooden houseboat with bed cabins & bio-toilet, 6 Delicious traditional Bengali meals (duck roast, fresh haor fish, haor ghee), Local boat pilots & guide',
'Personal snacks, travel to/from boarding points outside itinerary',
'Life jackets must be worn when swimming in open haor water. Solar power available on houseboats.', 'active'),

(10, 'Jaflong Zero Point & Piyain River Stone Stream Tour', 'Jaflong', '2 Days / 1 Night', 4600.00, 15, '2026-12-15', '2026-12-17', 'images/destinations/jaflong.jpg',
'Witness the iconic border spectacle where the crystal clear waters of the Piyain River roll down from the clouds of Meghalaya India over colored river pebbles. Experience a wooden engine boat ride to Zero Point and visit authentic Khasia tribal hamlets.',
'Day 1: Depart Dhaka for Sylhet, travel through lush green highway, visit Hazrat Shah Jalal Mazar, hotel check-in in Sylhet city, evening Sylhet regional cuisine.\nDay 2: Morning scenic drive to Jaflong Zero Point, boat ride on Piyain River beneath Meghalaya waterfalls, visit Sangrampunji waterfall across the border, explore Khasia Rajbari & betel nut gardens, evening return to Dhaka.',
'Return transportation from Dhaka, 1 Night deluxe hotel stay in Sylhet, Boat ride on Piyain River to Zero Point, Guided visit to Khasia tribal village, Breakfast & lunch included',
'Shopping for border stone souvenirs, personal boat upgrades',
'Carry valid photo ID for the border checkpoint area at Tamabil / Jaflong.', 'active'),

(11, 'Bichanakandi Riverbed & Ratargul Freshwater Swamp Safari', 'Bichanakandi', '2 Days / 1 Night', 4900.00, 14, '2026-12-20', '2026-12-22', 'images/destinations/bichnakandi.jpg',
'Experience two of Bangladesh\'s most stunning natural wonders: cruise through the emerald canopy of Ratargul Swamp Forest (the Amazon of Bangladesh) by silent wooden canoe, then immerse in the crystal cold river waters and boulder terraces of Bichanakandi.',
'Day 1: Arrive in Sylhet, proceed to Ratargul Swamp Forest, board traditional wooden dinghy boat into flooded Koroch trees and watchtower, lunch at village restaurant, check-in to Sylhet hotel.\nDay 2: Travel to Gowainghat and boat through Mayabi waterfall creek to Bichanakandi boulder river, swim and relax in clear hill streams, return journey.',
'Dhaka - Sylhet - Dhaka transportation, Silent boat safari inside Ratargul Swamp Forest, Engine boat ride to Bichanakandi, 1 Night hotel accommodation, Breakfast & local fish lunch',
'Personal extra snacks, lifejacket rentals outside standard package',
'Waterproof pouches for mobile phones and cameras are strongly recommended.', 'active'),

(12, 'Historic Old Dhaka Heritage Walk & Mughal Lalbagh Fort', 'Lalbagh Fort', '1 Day Day-Trip', 1800.00, 25, '2026-12-24', '2026-12-24', 'images/destinations/lalbagh.jpg',
'Journey back 400 years into the rich Mughal era of Dhaka. Explore the majestic Lalbagh Fort and Pari Bibi\'s tomb, marvel at the Grand Star Mosque (Tara Masjid), savor authentic Old Dhaka Kacchi Biryani, and wander through historic copper & brass artisan lanes.',
'8:30 AM: Meet at curated pickup point, visit Lalbagh Fort complex & underground tunnels museum.\n11:30 AM: Walk through Shakhari Bazaar heritage craft street and Tara Masjid.\n1:00 PM: Traditional Old Dhaka Kacchi Biryani lunch at famous heritage eatery.\n2:30 PM: Armenian Church & historic Choto Katra monument walk.\n5:00 PM: Sunset boat crossing on Buriganga river, concluding with hot saffron tea.',
'AC Tourist Minibus for the day, All heritage monument entry tickets, Authentic Old Dhaka Kacchi Biryani & Borhani feast, Certified heritage historian guide, Refreshing bottled water & afternoon tea',
'Personal shopping in heritage bazaars',
'Wear modest clothing suitable for religious and historical monuments.', 'active'),

(13, 'Ahsan Manzil Pink Palace & Buriganga River Heritage Cruise', 'Ahsan Manzil', '1 Day Day-Trip', 2100.00, 20, '2026-12-26', '2026-12-26', 'images/destinations/ahsanmanzil.jpg',
'Discover the royal legacy of the Nawabs of Dhaka at the magnificent Pink Palace (Ahsan Manzil). Admire its grand dome, sweeping stairs, and 23 galleries of royal artifacts, followed by a private heritage boat cruise along the bustling Buriganga waterway.',
'9:00 AM: Morning arrival at Ahsan Manzil waterfront entrance.\n9:30 AM - 12:30 PM: Guided tour of 23 museum galleries, royal dining hall, and courtroom.\n1:00 PM: Riverfront lunch with classic Dhaka cuisine.\n2:30 PM: Private roofed wooden boat ride on Buriganga river observing historic river trade.\n4:30 PM: Evening stroll through Sadarghat and heritage French mansion (Ruplal House).',
'Full museum entry clearance and tickets, Professional museum docent & historical storyteller, Gourmet royal lunch & Bakarkhani snacks, 2-hour private Buriganga boat excursion',
'Museum camera permit fees if using DSLR',
'Museum remains closed on Thursdays; tours run Saturday through Wednesday.', 'active'),

(14, 'Somapura Mahavihara & Mahasthangarh Ancient Citadel Odyssey', 'Somapura Mahavihara', '2 Days / 1 Night', 5400.00, 16, '2026-12-28', '2026-12-30', 'images/destinations/paharpur.jpg',
'Travel through 2,500 years of Bengal history. Visit Somapura Mahavihara at Paharpur, one of the greatest Buddhist universities of ancient Asia, and walk the fortified earthen ramparts of Mahasthangarh (Pundranagara), the earliest archaeological city in Bangladesh.',
'Day 1: AC coach departure via Jamuna Bridge to Bogura, check-in, proceed to Mahasthangarh archaeological site, visit Govinda Bhita, Gokul Medh (Behula\'s Lakshmindar basor ghar), and Mahasthan museum, taste Bogura Doi (famous curd).\nDay 2: Morning excursion to Paharpur Somapura Mahavihara, explore the central stupa, terracotta plaques, and archaeological monastery cells, museum visit, evening return to Dhaka.',
'AC Coach transportation round trip, 1 Night 3-Star hotel stay in Bogura, UNESCO site entry tickets & archaeological guide, Breakfast, lunch, and famous Bogura Doi degustation, All monument entry permits',
'Personal souvenirs & additional snacks',
'Comfortable walking shoes are essential for exploring the vast archaeological grounds.', 'active');

-- Sample Bookings
INSERT INTO bookings (booking_code, user_id, package_id, travel_date, number_of_travelers, total_amount, status, payment_status, special_requests) VALUES
('CHG-10021', 1, 1, '2026-10-15', 2, 13000.00, 'Confirmed', 'verified', 'Need quiet sea-facing room with twin beds'),
('CHG-10022', 2, 2, '2026-10-20', 3, 15600.00, 'Confirmed', 'verified', 'Early check-in for mountain cottage if possible'),
('CHG-10023', 3, 3, '2026-10-25', 1, 8500.00, 'Pending', 'pending', 'Single bed cabin on middle deck'),
('CHG-10024', 4, 5, '2026-11-12', 2, 9000.00, 'Completed', 'verified', 'Vegetarian food options for one traveler'),
('CHG-10025', 1, 4, '2026-11-05', 2, 15600.00, 'Cancelled', 'refunded', 'Requested cancellation due to family exam schedule');

-- Booking Travelers
INSERT INTO booking_travelers (booking_id, name, phone, email, age, gender) VALUES
(1, 'Fahim Hossain', '01711223344', 'fahim@example.com', 28, 'Male'),
(1, 'Tanima Hossain', '01711223345', 'tanima@example.com', 25, 'Female'),
(2, 'Nusrat Jahan', '01822334455', 'nusrat@example.com', 26, 'Female'),
(2, 'Sabrina Akter', '01822334456', 'sabrina@example.com', 24, 'Female'),
(2, 'Imran Mahmud', '01822334457', 'imran@example.com', 29, 'Male'),
(3, 'Tanvir Rahman', '01933445566', 'tanvir@example.com', 31, 'Male'),
(4, 'Sadia Islam', '01644556677', 'sadia@example.com', 27, 'Female'),
(4, 'Abrar Islam', '01644556678', 'abrar@example.com', 30, 'Male');

-- Payments
INSERT INTO payments (booking_id, user_id, amount, payment_method, transaction_id, sender_number, status, admin_note) VALUES
(1, 1, 13000.00, 'bKash', 'BK99X40291AZ', '01711223344', 'verified', 'Verified via bKash Merchant Portal'),
(2, 2, 15600.00, 'Nagad', 'NG77T90218LK', '01822334455', 'verified', 'Nagad Trx ID confirmed'),
(3, 3, 8500.00, 'bKash', 'BK12K88492OP', '01933445566', 'pending', 'Awaiting bank clearance'),
(4, 4, 9000.00, 'Card', 'CRD401928374', '01644556677', 'verified', 'Online Card Payment Authorized');

-- Real Customer Reviews
INSERT INTO reviews (user_id, package_id, booking_id, rating, comment, status) VALUES
(4, 5, 4, 5, 'The Sreemangal tea garden tour was mesmerizing! Lawachara forest hike was very peaceful and we even saw the endangered hoolock gibbons. CholoGhuri arranged top tier accommodation.', 'approved'),
(1, 1, 1, 5, 'Cox\'s Bazar Marine Drive trip was worth every taka. Clean transport, friendly guide, and hotel right near the beach. Highly recommended!', 'approved'),
(2, 2, 2, 5, 'Waking up above the clouds in Sajek Valley is an unforgettable memory. The bamboo chicken dinner was super delicious. 10/10 service from CholoGhuri.', 'approved'),
(3, 3, NULL, 5, 'Sundarbans boat safari was surreal. The silence of the mangrove canals and spotting herds of deer at Katka beach made our vacation truly special.', 'approved');

-- Wishlist
INSERT INTO wishlist (user_id, package_id) VALUES
(1, 6),
(1, 9),
(2, 4),
(3, 1);

-- Notifications
INSERT INTO notifications (user_id, title, message, type, is_read) VALUES
(1, 'Booking Confirmed!', 'Your booking CHG-10021 for Cox\'s Bazar Marine Drive has been verified and confirmed. Check your email or download your voucher.', 'booking', 0),
(2, 'Payment Verified', 'We have successfully verified your payment of ৳15,600 for Sajek Valley Adventure (CHG-10022).', 'payment', 1),
(3, 'Booking Received', 'Your booking request CHG-10023 for Sundarbans Safari is received. Please complete your payment verification.', 'booking', 0),
(4, 'Review Invitation', 'Hope you enjoyed your Sreemangal expedition! We would love to hear your review.', 'system', 1);

-- Cancellation Requests
INSERT INTO cancellation_requests (booking_id, user_id, reason, status, admin_note) VALUES
(5, 1, 'My university mid-term exams were rescheduled to the same week.', 'approved', 'Refund of ৳15,600 initiated via original bKash number.');

-- Admin Activity Logs
INSERT INTO admin_logs (admin_id, action, description, ip_address) VALUES
(1, 'Admin Login', 'Super Admin logged in successfully to management console', '127.0.0.1'),
(1, 'Booking Confirmed', 'Booking CHG-10021 status updated to Confirmed', '127.0.0.1'),
(1, 'Payment Verified', 'Payment TrxID BK99X40291AZ verified for ৳13,000', '127.0.0.1'),
(1, 'Cancellation Approved', 'Cancellation request for CHG-10025 approved and marked refunded', '127.0.0.1');

-- System Settings
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'CholoGhuri - Travel Booking & Management System'),
('site_tagline', 'Explore More. Live Better. Across Beautiful Bangladesh.'),
('contact_email', 'support@chologhuri.com'),
('contact_phone', ''),
('emergency_phone', ''),
('office_address', ''),
('bkash_merchant', ''),
('nagad_merchant', ''),
('currency_symbol', '৳'),
('allow_cancellations', '1'),
('cancellation_cutoff_hours', '48');
