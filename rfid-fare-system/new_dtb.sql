-- =====================================================
-- DATABASE: rfid_fare_system
-- Automated Fare Collection System with RFID
-- Vince Gabriel Liners
-- SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA; (RUN SQL PARA MA ACCESS ANG DTB)
-- =====================================================

USE rfid_fare_system;

-- =====================================================
-- TABLE 1: admin (Management Accounts)
-- =====================================================
CREATE TABLE admin (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLE 2: discount_types (Senior, PWD, Student, Regular)
-- =====================================================
CREATE TABLE discount_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    percentage DECIMAL(5,2) DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLE 3: destinations (Boat routes and fares)
-- =====================================================
CREATE TABLE destinations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,
    base_fare DECIMAL(10,2) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLE 4: collectors (Collector accounts)
-- =====================================================
CREATE TABLE collectors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_number VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    contact VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLE 5: passengers (Passenger accounts with RFID)
-- =====================================================
CREATE TABLE passengers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    rfid_uid VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    contact VARCHAR(50),
    balance DECIMAL(10,2) DEFAULT 0,
    loyalty_points INT DEFAULT 0,
    discount_type_id INT DEFAULT 4,
    status ENUM('active', 'lost', 'blocked') DEFAULT 'active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLE 6: transactions (Fare payment records)
-- =====================================================
CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    receipt_no VARCHAR(50) UNIQUE NOT NULL,
    passenger_id INT NOT NULL,
    collector_id INT NOT NULL,
    destination_id INT NOT NULL,
    base_fare DECIMAL(10,2) NOT NULL,
    discount_percentage DECIMAL(5,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    net_payment DECIMAL(10,2) NOT NULL,
    balance_before DECIMAL(10,2) NOT NULL,
    balance_after DECIMAL(10,2) NOT NULL,
    loyalty_points_earned INT DEFAULT 0,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLE 7: balance_loads (Track balance reloads)
-- =====================================================
CREATE TABLE balance_loads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    passenger_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    reference_no VARCHAR(50),
    loaded_by INT,
    load_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLE 8: loyalty_settings (Configure loyalty points)
-- =====================================================
CREATE TABLE loyalty_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    points_per_peso DECIMAL(5,2) DEFAULT 0.10,
    min_fare_for_points DECIMAL(10,2) DEFAULT 10.00,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLE 9: rfid_replacements (Track lost card replacements)
-- =====================================================
CREATE TABLE rfid_replacements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    passenger_id INT NOT NULL,
    old_rfid_uid VARCHAR(50) NOT NULL,
    new_rfid_uid VARCHAR(50) NOT NULL,
    reason VARCHAR(255) DEFAULT 'lost',
    replaced_by INT,
    replacement_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================

-- Insert discount types
INSERT INTO discount_types (name, percentage) VALUES
('senior', 20.00),
('pwd', 20.00),
('student', 10.00),
('regular', 0.00);

-- Insert loyalty settings
INSERT INTO loyalty_settings (points_per_peso, min_fare_for_points) VALUES
(0.10, 10.00);

-- Insert sample destinations
INSERT INTO destinations (name, base_fare) VALUES
('Dinagat Island', 50.00),
('San Jose', 35.00),
('Loreto', 65.00),
('Cagdianao', 45.00),
('Tubajon', 55.00);

-- Insert default admin account (username: admin, password: admin123)
INSERT INTO admin (username, password, full_name) VALUES
('admin', 'admin123', 'System Administrator');

-- =====================================================
-- ADD FOREIGN KEY CONSTRAINTS
-- =====================================================

ALTER TABLE collectors ADD FOREIGN KEY (created_by) REFERENCES admin(id) ON DELETE SET NULL;
ALTER TABLE passengers ADD FOREIGN KEY (discount_type_id) REFERENCES discount_types(id);
ALTER TABLE passengers ADD FOREIGN KEY (created_by) REFERENCES admin(id) ON DELETE SET NULL;
ALTER TABLE transactions ADD FOREIGN KEY (passenger_id) REFERENCES passengers(id);
ALTER TABLE transactions ADD FOREIGN KEY (collector_id) REFERENCES collectors(id);
ALTER TABLE transactions ADD FOREIGN KEY (destination_id) REFERENCES destinations(id);
ALTER TABLE balance_loads ADD FOREIGN KEY (passenger_id) REFERENCES passengers(id);
ALTER TABLE balance_loads ADD FOREIGN KEY (loaded_by) REFERENCES admin(id) ON DELETE SET NULL;
ALTER TABLE loyalty_settings ADD FOREIGN KEY (updated_by) REFERENCES admin(id) ON DELETE SET NULL;
ALTER TABLE rfid_replacements ADD FOREIGN KEY (passenger_id) REFERENCES passengers(id);
ALTER TABLE rfid_replacements ADD FOREIGN KEY (replaced_by) REFERENCES admin(id) ON DELETE SET NULL;

-- =====================================================
-- VERIFICATION (Run to check all tables exist)
-- =====================================================
SHOW TABLES;