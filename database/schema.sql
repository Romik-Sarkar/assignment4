-- CS 6314 Assignment #4 Database Schema
-- Travel Deals Web Application

-- Drop existing database if it exists and create new one
DROP DATABASE IF EXISTS travel_deals;
CREATE DATABASE travel_deals;
USE travel_deals;

-- ============================================
-- USER AUTHENTICATION TABLE
-- ============================================
CREATE TABLE users (
    Phone VARCHAR(12) PRIMARY KEY,  -- Format: ddd-ddd-dddd
    Password VARCHAR(255) NOT NULL,  -- Hashed password
    FirstName VARCHAR(50) NOT NULL,
    LastName VARCHAR(50) NOT NULL,
    DateOfBirth VARCHAR(10) NOT NULL,  -- Format: MM-DD-YYYY
    Email VARCHAR(100) NOT NULL,
    Gender VARCHAR(10) DEFAULT NULL,  -- Optional field
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_phone (Phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert admin user (hardcoded phone: 222-222-2222)
INSERT INTO users (Phone, Password, FirstName, LastName, DateOfBirth, Email, Gender)
VALUES ('222-222-2222', MD5('admin123'), 'Admin', 'User', '01-01-1980', 'admin@traveldeals.com', 'Other');

-- ============================================
-- FLIGHTS SYSTEM TABLES
-- ============================================

-- Flights table
CREATE TABLE flights (
    flight_id VARCHAR(20) PRIMARY KEY,
    origin VARCHAR(100) NOT NULL,
    destination VARCHAR(100) NOT NULL,
    departure_date DATE NOT NULL,
    arrival_date DATE NOT NULL,
    departure_time TIME NOT NULL,
    arrival_time TIME NOT NULL,
    available_seats INT NOT NULL DEFAULT 150,
    price DECIMAL(10, 2) NOT NULL,
    INDEX idx_origin (origin),
    INDEX idx_destination (destination),
    INDEX idx_departure_date (departure_date),
    INDEX idx_seats (available_seats)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Passengers table
CREATE TABLE passengers (
    SSN VARCHAR(11) PRIMARY KEY,  -- Format: XXX-XX-XXXX
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    date_of_birth VARCHAR(10) NOT NULL,  -- Format: MM-DD-YYYY
    category ENUM('adult', 'child', 'infant') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Flight bookings table
CREATE TABLE flight_booking (
    flight_booking_id VARCHAR(20) PRIMARY KEY,
    flight_id VARCHAR(20) NOT NULL,
    user_phone VARCHAR(12) NOT NULL,  -- Who made the booking
    total_price DECIMAL(10, 2) NOT NULL,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (flight_id) REFERENCES flights(flight_id),
    FOREIGN KEY (user_phone) REFERENCES users(Phone),
    INDEX idx_user (user_phone),
    INDEX idx_flight (flight_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tickets table (many-to-many between flight_booking and passengers)
CREATE TABLE tickets (
    ticket_id VARCHAR(20) PRIMARY KEY,
    flight_booking_id VARCHAR(20) NOT NULL,
    SSN VARCHAR(11) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (flight_booking_id) REFERENCES flight_booking(flight_booking_id),
    FOREIGN KEY (SSN) REFERENCES passengers(SSN),
    INDEX idx_booking (flight_booking_id),
    INDEX idx_passenger (SSN)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- HOTELS SYSTEM TABLES
-- ============================================

-- Hotels table
CREATE TABLE hotels (
    hotel_id VARCHAR(20) PRIMARY KEY,
    hotel_name VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    price_per_night DECIMAL(10, 2) NOT NULL,
    INDEX idx_city (city)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Hotel bookings table
CREATE TABLE hotel_booking (
    hotel_booking_id VARCHAR(20) PRIMARY KEY,
    hotel_id VARCHAR(20) NOT NULL,
    user_phone VARCHAR(12) NOT NULL,  -- Who made the booking
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    num_rooms INT NOT NULL,
    price_per_night DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hotel_id) REFERENCES hotels(hotel_id),
    FOREIGN KEY (user_phone) REFERENCES users(Phone),
    INDEX idx_user (user_phone),
    INDEX idx_hotel (hotel_id),
    INDEX idx_checkin (check_in_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Guests table (many-to-many between hotel_booking and guests)
CREATE TABLE guesses (
    SSN VARCHAR(11) NOT NULL,
    hotel_booking_id VARCHAR(20) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    date_of_birth VARCHAR(10) NOT NULL,  -- Format: MM-DD-YYYY
    category ENUM('adult', 'child', 'infant') NOT NULL,
    PRIMARY KEY (SSN, hotel_booking_id),
    FOREIGN KEY (hotel_booking_id) REFERENCES hotel_booking(hotel_booking_id),
    INDEX idx_booking (hotel_booking_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- VIEWS FOR COMMON QUERIES
-- ============================================

-- View for all flight bookings with passenger counts
CREATE VIEW v_flight_bookings_summary AS
SELECT
    fb.flight_booking_id,
    fb.user_phone,
    f.flight_id,
    f.origin,
    f.destination,
    f.departure_date,
    f.arrival_date,
    f.departure_time,
    f.arrival_time,
    fb.total_price,
    fb.booking_date,
    COUNT(t.ticket_id) as passenger_count,
    SUM(CASE WHEN p.category = 'infant' THEN 1 ELSE 0 END) as infant_count,
    SUM(CASE WHEN p.category = 'child' THEN 1 ELSE 0 END) as child_count
FROM flight_booking fb
JOIN flights f ON fb.flight_id = f.flight_id
LEFT JOIN tickets t ON fb.flight_booking_id = t.flight_booking_id
LEFT JOIN passengers p ON t.SSN = p.SSN
GROUP BY fb.flight_booking_id;

-- View for all hotel bookings with guest counts
CREATE VIEW v_hotel_bookings_summary AS
SELECT
    hb.hotel_booking_id,
    hb.user_phone,
    h.hotel_id,
    h.hotel_name,
    h.city,
    hb.check_in_date,
    hb.check_out_date,
    hb.num_rooms,
    hb.price_per_night,
    hb.total_price,
    hb.booking_date,
    COUNT(g.SSN) as guest_count
FROM hotel_booking hb
JOIN hotels h ON hb.hotel_id = h.hotel_id
LEFT JOIN guesses g ON hb.hotel_booking_id = g.hotel_booking_id
GROUP BY hb.hotel_booking_id;

-- ============================================
-- STORED PROCEDURES FOR COMMON OPERATIONS
-- ============================================

DELIMITER //

-- Procedure to create flight booking with passengers
CREATE PROCEDURE create_flight_booking(
    IN p_flight_booking_id VARCHAR(20),
    IN p_flight_id VARCHAR(20),
    IN p_user_phone VARCHAR(12),
    IN p_total_price DECIMAL(10,2),
    IN p_num_passengers INT
)
BEGIN
    DECLARE v_available_seats INT;

    -- Check available seats
    SELECT available_seats INTO v_available_seats
    FROM flights
    WHERE flight_id = p_flight_id;

    IF v_available_seats >= p_num_passengers THEN
        -- Create booking
        INSERT INTO flight_booking (flight_booking_id, flight_id, user_phone, total_price)
        VALUES (p_flight_booking_id, p_flight_id, p_user_phone, p_total_price);

        -- Update available seats
        UPDATE flights
        SET available_seats = available_seats - p_num_passengers
        WHERE flight_id = p_flight_id;

        SELECT 'SUCCESS' as result;
    ELSE
        SELECT 'INSUFFICIENT_SEATS' as result;
    END IF;
END//

-- Procedure to generate unique IDs
CREATE PROCEDURE generate_booking_id(
    IN p_prefix VARCHAR(5),
    OUT p_new_id VARCHAR(20)
)
BEGIN
    DECLARE v_counter INT;
    SET v_counter = FLOOR(RAND() * 999999) + 1;
    SET p_new_id = CONCAT(p_prefix, LPAD(v_counter, 6, '0'));
END//

DELIMITER ;

-- ============================================
-- INDEXES FOR PERFORMANCE
-- ============================================

-- Additional composite indexes for complex queries
CREATE INDEX idx_flights_search ON flights(origin, destination, departure_date, available_seats);
CREATE INDEX idx_flight_booking_date ON flight_booking(booking_date);
CREATE INDEX idx_hotel_booking_date ON hotel_booking(booking_date, check_in_date);

-- ============================================
-- SAMPLE DATA (For testing - will be replaced by admin load)
-- ============================================

-- Sample flights (These will be replaced when admin loads flights.json)
INSERT INTO flights VALUES
('FL001', 'Dallas, TX', 'Los Angeles, CA', '2024-09-15', '2024-09-15', '08:00:00', '10:30:00', 150, 299.99),
('FL002', 'Houston, TX', 'San Francisco, CA', '2024-09-20', '2024-09-20', '09:00:00', '11:45:00', 150, 349.99),
('FL003', 'Austin, TX', 'San Diego, CA', '2024-10-05', '2024-10-05', '10:30:00', '13:00:00', 150, 279.99);

-- Sample hotels (These will be replaced when admin loads hotels.xml)
INSERT INTO hotels VALUES
('HTL001', 'Grand Texas Hotel', 'Dallas, TX', 150.00),
('HTL002', 'California Beach Resort', 'Los Angeles, CA', 250.00),
('HTL003', 'Downtown Austin Inn', 'Austin, TX', 120.00);

-- ============================================
-- GRANT PRIVILEGES (Adjust username/password as needed)
-- ============================================

-- Create database user for PHP application
-- GRANT ALL PRIVILEGES ON travel_deals.* TO 'travel_user'@'localhost' IDENTIFIED BY 'travel_pass123';
-- FLUSH PRIVILEGES;

-- ============================================
-- DATABASE STATISTICS
-- ============================================

SELECT
    'Database created successfully!' as Status,
    (SELECT COUNT(*) FROM users) as Total_Users,
    (SELECT COUNT(*) FROM flights) as Total_Flights,
    (SELECT COUNT(*) FROM hotels) as Total_Hotels;
