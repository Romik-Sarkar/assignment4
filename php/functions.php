<?php
/**
 * CS 6314 Assignment #4
 * Common PHP Functions
 */

require_once 'config.php';

// ============================================
// USER AUTHENTICATION FUNCTIONS
// ============================================

/**
 * Register a new user
 */
function registerUser($phone, $password, $firstName, $lastName, $dateOfBirth, $email, $gender = null) {
    $conn = getDBConnection();
    if (!$conn) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }

    // Validate phone format
    if (!validatePhone($phone)) {
        closeDBConnection($conn);
        return ['success' => false, 'error' => 'Invalid phone format. Use ddd-ddd-dddd'];
    }

    // Prevent admin phone registration
    if ($phone === ADMIN_PHONE) {
        closeDBConnection($conn);
        return ['success' => false, 'error' => 'This phone number is reserved'];
    }

    // Check if phone already exists
    $stmt = $conn->prepare("SELECT Phone FROM users WHERE Phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $stmt->close();
        closeDBConnection($conn);
        return ['success' => false, 'error' => 'Phone number already registered'];
    }
    $stmt->close();

    // Validate email
    if (!validateEmail($email)) {
        closeDBConnection($conn);
        return ['success' => false, 'error' => 'Email must contain @ and .com'];
    }

    // Validate date of birth
    if (!validateDateFormat($dateOfBirth)) {
        closeDBConnection($conn);
        return ['success' => false, 'error' => 'Invalid date format. Use MM-DD-YYYY'];
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (Phone, Password, FirstName, LastName, DateOfBirth, Email, Gender) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $phone, $hashedPassword, $firstName, $lastName, $dateOfBirth, $email, $gender);

    if ($stmt->execute()) {
        $stmt->close();
        closeDBConnection($conn);
        return ['success' => true, 'message' => 'Registration successful'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        closeDBConnection($conn);
        return ['success' => false, 'error' => 'Registration failed: ' . $error];
    }
}

/**
 * Login user
 */
function loginUser($phone, $password) {
    $conn = getDBConnection();
    if (!$conn) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }

    $stmt = $conn->prepare("SELECT Phone, Password, FirstName, LastName, DateOfBirth, Email, Gender FROM users WHERE Phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        closeDBConnection($conn);
        return ['success' => false, 'error' => 'Invalid phone number or password'];
    }

    $user = $result->fetch_assoc();
    $stmt->close();
    closeDBConnection($conn);

    // Verify password
    if (password_verify($password, $user['Password'])) {
        // Remove password from user data
        unset($user['Password']);

        // Set session
        $_SESSION['user'] = [
            'phone' => $user['Phone'],
            'firstName' => $user['FirstName'],
            'lastName' => $user['LastName'],
            'dateOfBirth' => $user['DateOfBirth'],
            'email' => $user['Email'],
            'gender' => $user['Gender']
        ];

        return ['success' => true, 'user' => $_SESSION['user']];
    } else {
        return ['success' => false, 'error' => 'Invalid phone number or password'];
    }
}

/**
 * Logout user
 */
function logoutUser() {
    session_unset();
    session_destroy();
    return ['success' => true, 'message' => 'Logged out successfully'];
}

// ============================================
// FLIGHT FUNCTIONS
// ============================================

/**
 * Search flights
 */
function searchFlights($origin, $destination, $departureDate, $numPassengers = 1, $flexDays = 3) {
    $conn = getDBConnection();
    if (!$conn) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }

    // Convert date to MySQL format
    $mysqlDate = convertDateToMySQL($departureDate);

    // Calculate date range for flexible search
    $startDate = date('Y-m-d', strtotime($mysqlDate . " -$flexDays days"));
    $endDate = date('Y-m-d', strtotime($mysqlDate . " +$flexDays days"));

    // Search flights
    $stmt = $conn->prepare("
        SELECT flight_id, origin, destination, departure_date, arrival_date,
               departure_time, arrival_time, available_seats, price
        FROM flights
        WHERE origin = ?
          AND destination = ?
          AND departure_date BETWEEN ? AND ?
          AND available_seats >= ?
        ORDER BY departure_date, departure_time
    ");

    $stmt->bind_param("ssssi", $origin, $destination, $startDate, $endDate, $numPassengers);
    $stmt->execute();
    $result = $stmt->get_result();

    $flights = [];
    while ($row = $result->fetch_assoc()) {
        $flights[] = $row;
    }

    $stmt->close();
    closeDBConnection($conn);

    return ['success' => true, 'flights' => $flights];
}

/**
 * Get flight by ID
 */
function getFlightById($flightId) {
    $conn = getDBConnection();
    if (!$conn) return null;

    $stmt = $conn->prepare("SELECT * FROM flights WHERE flight_id = ?");
    $stmt->bind_param("s", $flightId);
    $stmt->execute();
    $result = $stmt->get_result();
    $flight = $result->fetch_assoc();

    $stmt->close();
    closeDBConnection($conn);

    return $flight;
}

/**
 * Calculate flight total price
 */
function calculateFlightPrice($basePrice, $adults, $children, $infants) {
    $total = 0;
    $total += $adults * $basePrice;
    $total += $children * ($basePrice * CHILD_MULTIPLIER);
    $total += $infants * ($basePrice * INFANT_MULTIPLIER);
    return round($total, 2);
}

/**
 * Book flight with passengers
 */
function bookFlight($flightId, $userPhone, $passengers, $totalPrice) {
    $conn = getDBConnection();
    if (!$conn) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }

    $conn->begin_transaction();

    try {
        // Generate booking ID
        $bookingId = generateUniqueID('FB');

        // Insert booking
        $stmt = $conn->prepare("INSERT INTO flight_booking (flight_booking_id, flight_id, user_phone, total_price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssd", $bookingId, $flightId, $userPhone, $totalPrice);
        $stmt->execute();
        $stmt->close();

        // Insert passengers and tickets
        $tickets = [];
        foreach ($passengers as $passenger) {
            // Insert or update passenger
            $stmt = $conn->prepare("INSERT INTO passengers (SSN, first_name, last_name, date_of_birth, category) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE first_name=?, last_name=?, date_of_birth=?, category=?");
            $stmt->bind_param("sssssssss",
                $passenger['ssn'], $passenger['firstName'], $passenger['lastName'],
                $passenger['dateOfBirth'], $passenger['category'],
                $passenger['firstName'], $passenger['lastName'],
                $passenger['dateOfBirth'], $passenger['category']
            );
            $stmt->execute();
            $stmt->close();

            // Generate ticket ID
            $ticketId = generateUniqueID('TK');

            // Insert ticket
            $stmt = $conn->prepare("INSERT INTO tickets (ticket_id, flight_booking_id, SSN, price) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssd", $ticketId, $bookingId, $passenger['ssn'], $passenger['price']);
            $stmt->execute();
            $stmt->close();

            $tickets[] = [
                'ticketId' => $ticketId,
                'ssn' => $passenger['ssn'],
                'firstName' => $passenger['firstName'],
                'lastName' => $passenger['lastName'],
                'dateOfBirth' => $passenger['dateOfBirth'],
                'category' => $passenger['category'],
                'price' => $passenger['price']
            ];
        }

        // Update available seats
        $numPassengers = count($passengers);
        $stmt = $conn->prepare("UPDATE flights SET available_seats = available_seats - ? WHERE flight_id = ?");
        $stmt->bind_param("is", $numPassengers, $flightId);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        closeDBConnection($conn);

        return [
            'success' => true,
            'bookingId' => $bookingId,
            'tickets' => $tickets
        ];

    } catch (Exception $e) {
        $conn->rollback();
        closeDBConnection($conn);
        return ['success' => false, 'error' => 'Booking failed: ' . $e->getMessage()];
    }
}

// ============================================
// HOTEL FUNCTIONS
// ============================================

/**
 * Search hotels
 */
function searchHotels($city) {
    $conn = getDBConnection();
    if (!$conn) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }

    $stmt = $conn->prepare("SELECT hotel_id, hotel_name, city, price_per_night FROM hotels WHERE city = ?");
    $stmt->bind_param("s", $city);
    $stmt->execute();
    $result = $stmt->get_result();

    $hotels = [];
    while ($row = $result->fetch_assoc()) {
        $hotels[] = $row;
    }

    $stmt->close();
    closeDBConnection($conn);

    return ['success' => true, 'hotels' => $hotels];
}

/**
 * Get hotel by ID
 */
function getHotelById($hotelId) {
    $conn = getDBConnection();
    if (!$conn) return null;

    $stmt = $conn->prepare("SELECT * FROM hotels WHERE hotel_id = ?");
    $stmt->bind_param("s", $hotelId);
    $stmt->execute();
    $result = $stmt->get_result();
    $hotel = $result->fetch_assoc();

    $stmt->close();
    closeDBConnection($conn);

    return $hotel;
}

/**
 * Calculate number of rooms needed
 */
function calculateRoomsNeeded($adults, $children) {
    $totalGuests = $adults + $children; // Infants don't count
    return ceil($totalGuests / 2); // Max 2 guests per room
}

/**
 * Book hotel with guests
 */
function bookHotel($hotelId, $userPhone, $checkInDate, $checkOutDate, $numRooms, $pricePerNight, $totalPrice, $guests) {
    $conn = getDBConnection();
    if (!$conn) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }

    $conn->begin_transaction();

    try {
        // Generate booking ID
        $bookingId = generateUniqueID('HB');

        // Convert dates to MySQL format
        $checkIn = convertDateToMySQL($checkInDate);
        $checkOut = convertDateToMySQL($checkOutDate);

        // Insert booking
        $stmt = $conn->prepare("INSERT INTO hotel_booking (hotel_booking_id, hotel_id, user_phone, check_in_date, check_out_date, num_rooms, price_per_night, total_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssidd", $bookingId, $hotelId, $userPhone, $checkIn, $checkOut, $numRooms, $pricePerNight, $totalPrice);
        $stmt->execute();
        $stmt->close();

        // Insert guests
        foreach ($guests as $guest) {
            $stmt = $conn->prepare("INSERT INTO guesses (SSN, hotel_booking_id, first_name, last_name, date_of_birth, category) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss",
                $guest['ssn'], $bookingId, $guest['firstName'],
                $guest['lastName'], $guest['dateOfBirth'], $guest['category']
            );
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();
        closeDBConnection($conn);

        return [
            'success' => true,
            'bookingId' => $bookingId
        ];

    } catch (Exception $e) {
        $conn->rollback();
        closeDBConnection($conn);
        return ['success' => false, 'error' => 'Booking failed: ' . $e->getMessage()];
    }
}

// ============================================
// CONTACT FUNCTIONS (XML Storage)
// ============================================

/**
 * Save contact to XML file
 */
function saveContact($phone, $firstName, $lastName, $dateOfBirth, $email, $gender, $comment) {
    $xmlFile = '../data/contacts.xml';

    // Create or load XML
    if (file_exists($xmlFile)) {
        $xml = simplexml_load_file($xmlFile);
    } else {
        $xml = new SimpleXMLElement('<contacts></contacts>');
    }

    // Generate contact ID
    $contactId = generateUniqueID('CNT');

    // Add contact
    $contact = $xml->addChild('contact');
    $contact->addChild('contact-id', $contactId);
    $contact->addChild('phone', $phone);
    $contact->addChild('firstName', $firstName);
    $contact->addChild('lastName', $lastName);
    $contact->addChild('dateOfBirth', $dateOfBirth);
    $contact->addChild('email', $email);
    $contact->addChild('gender', $gender);
    $contact->addChild('comment', $comment);
    $contact->addChild('timestamp', date('Y-m-d H:i:s'));

    // Save XML
    $dom = new DOMDocument('1.0');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($xml->asXML());
    $dom->save($xmlFile);

    return ['success' => true, 'contactId' => $contactId];
}

?>
