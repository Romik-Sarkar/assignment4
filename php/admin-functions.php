<?php
/**
 * CS 6314 Assignment #4
 * Admin Functions for Loading Data
 */

require_once 'config.php';

/**
 * Load flights from JSON file into database
 */
function loadFlightsFromJSON() {
    requireAdmin();

    $jsonFile = __DIR__ . '/../data/flights.json';
    if (!file_exists($jsonFile)) {
        return ['success' => false, 'error' => 'flights.json file not found at: ' . $jsonFile];
    }

    $jsonContent = file_get_contents($jsonFile);
    $data = json_decode($jsonContent, true);

    if (!isset($data['flights'])) {
        return ['success' => false, 'error' => 'Invalid JSON format'];
    }

    $conn = getDBConnection();
    if (!$conn) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }

    $conn->begin_transaction();
    $inserted = 0;
    $updated = 0;

    try {
        $stmt = $conn->prepare("
            INSERT INTO flights (flight_id, origin, destination, departure_date, arrival_date, departure_time, arrival_time, available_seats, price)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                origin = VALUES(origin),
                destination = VALUES(destination),
                departure_date = VALUES(departure_date),
                arrival_date = VALUES(arrival_date),
                departure_time = VALUES(departure_time),
                arrival_time = VALUES(arrival_time),
                available_seats = VALUES(available_seats),
                price = VALUES(price)
        ");

        foreach ($data['flights'] as $flight) {
            $stmt->bind_param("sssssssid",
                $flight['flightId'],
                $flight['origin'],
                $flight['destination'],
                $flight['departureDate'],
                $flight['arrivalDate'],
                $flight['departureTime'],
                $flight['arrivalTime'],
                $flight['availableSeats'],
                $flight['price']
            );

            if ($stmt->execute()) {
                if ($stmt->affected_rows == 1) {
                    $inserted++;
                } else if ($stmt->affected_rows == 2) {
                    $updated++;
                }
            }
        }

        $stmt->close();
        $conn->commit();
        closeDBConnection($conn);

        return [
            'success' => true,
            'message' => "Flights loaded successfully",
            'inserted' => $inserted,
            'updated' => $updated
        ];

    } catch (Exception $e) {
        $conn->rollback();
        closeDBConnection($conn);
        return ['success' => false, 'error' => 'Failed to load flights: ' . $e->getMessage()];
    }
}

/**
 * Load hotels from XML file into database
 */
function loadHotelsFromXML() {
    requireAdmin();

    $xmlFile = __DIR__ . '/../data/hotels.xml';
    if (!file_exists($xmlFile)) {
        return ['success' => false, 'error' => 'hotels.xml file not found at: ' . $xmlFile];
    }

    $xml = simplexml_load_file($xmlFile);
    if (!$xml) {
        return ['success' => false, 'error' => 'Invalid XML format'];
    }

    $conn = getDBConnection();
    if (!$conn) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }

    $conn->begin_transaction();
    $inserted = 0;
    $updated = 0;

    try {
        $stmt = $conn->prepare("
            INSERT INTO hotels (hotel_id, hotel_name, city, price_per_night)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                hotel_name = VALUES(hotel_name),
                city = VALUES(city),
                price_per_night = VALUES(price_per_night)
        ");

        foreach ($xml->hotel as $hotel) {
            $hotelId = (string)$hotel->{'hotel-id'};
            $hotelName = (string)$hotel->{'hotel-name'};
            $city = (string)$hotel->city;
            $pricePerNight = (float)$hotel->{'price-per-night'};

            $stmt->bind_param("sssd", $hotelId, $hotelName, $city, $pricePerNight);

            if ($stmt->execute()) {
                if ($stmt->affected_rows == 1) {
                    $inserted++;
                } else if ($stmt->affected_rows == 2) {
                    $updated++;
                }
            }
        }

        $stmt->close();
        $conn->commit();
        closeDBConnection($conn);

        return [
            'success' => true,
            'message' => "Hotels loaded successfully",
            'inserted' => $inserted,
            'updated' => $updated
        ];

    } catch (Exception $e) {
        $conn->rollback();
        closeDBConnection($conn);
        return ['success' => false, 'error' => 'Failed to load hotels: ' . $e->getMessage()];
    }
}

/**
 * Get all booked flights departing from Texas cities (Sep-Oct 2024)
 * ADMIN ONLY
 */
function getTexasFlightsSepOct() {
    requireAdmin();

    $conn = getDBConnection();
    if (!$conn) return ['success' => false, 'error' => 'Database connection failed'];

    $query = "
        SELECT DISTINCT
            f.flight_id,
            f.origin,
            f.destination,
            f.departure_date,
            f.departure_time,
            f.arrival_time,
            fb.flight_booking_id,
            fb.total_price,
            fb.booking_date
        FROM flight_booking fb
        JOIN flights f ON fb.flight_id = f.flight_id
        WHERE f.origin LIKE '%TX%' OR f.origin IN ('Dallas', 'Houston', 'Austin', 'San Antonio', 'Fort Worth', 'El Paso', 'Corpus Christi', 'Lubbock', 'Plano', 'Irving', 'Laredo')
        AND f.departure_date BETWEEN '2024-09-01' AND '2024-10-31'
        ORDER BY f.departure_date, f.departure_time
    ";

    $result = $conn->query($query);
    $flights = [];

    while ($row = $result->fetch_assoc()) {
        $flights[] = $row;
    }

    closeDBConnection($conn);
    return ['success' => true, 'data' => $flights];
}

/**
 * Get all booked hotels in Texas cities (Sep-Oct 2024)
 * ADMIN ONLY
 */
function getTexasHotelsSepOct() {
    requireAdmin();

    $conn = getDBConnection();
    if (!$conn) return ['success' => false, 'error' => 'Database connection failed'];

    $query = "
        SELECT
            h.hotel_id,
            h.hotel_name,
            h.city,
            hb.hotel_booking_id,
            hb.check_in_date,
            hb.check_out_date,
            hb.num_rooms,
            hb.total_price,
            hb.booking_date
        FROM hotel_booking hb
        JOIN hotels h ON hb.hotel_id = h.hotel_id
        WHERE (h.city LIKE '%TX%' OR h.city IN ('Dallas', 'Houston', 'Austin', 'San Antonio', 'Fort Worth', 'El Paso', 'Corpus Christi', 'Lubbock', 'Plano', 'Irving', 'Laredo'))
        AND hb.check_in_date BETWEEN '2024-09-01' AND '2024-10-31'
        ORDER BY hb.check_in_date
    ";

    $result = $conn->query($query);
    $hotels = [];

    while ($row = $result->fetch_assoc()) {
        $hotels[] = $row;
    }

    closeDBConnection($conn);
    return ['success' => true, 'data' => $hotels];
}

/**
 * Get most expensive booked hotels
 * ADMIN ONLY
 */
function getMostExpensiveHotels() {
    requireAdmin();

    $conn = getDBConnection();
    if (!$conn) return ['success' => false, 'error' => 'Database connection failed'];

    $query = "
        SELECT
            h.hotel_id,
            h.hotel_name,
            h.city,
            hb.hotel_booking_id,
            hb.check_in_date,
            hb.check_out_date,
            hb.num_rooms,
            hb.total_price,
            hb.booking_date
        FROM hotel_booking hb
        JOIN hotels h ON hb.hotel_id = h.hotel_id
        ORDER BY hb.total_price DESC
        LIMIT 10
    ";

    $result = $conn->query($query);
    $hotels = [];

    while ($row = $result->fetch_assoc()) {
        $hotels[] = $row;
    }

    closeDBConnection($conn);
    return ['success' => true, 'data' => $hotels];
}

/**
 * Get all booked flights with at least one infant passenger
 * ADMIN ONLY
 */
function getFlightsWithInfants() {
    requireAdmin();

    $conn = getDBConnection();
    if (!$conn) return ['success' => false, 'error' => 'Database connection failed'];

    $query = "
        SELECT DISTINCT
            f.flight_id,
            f.origin,
            f.destination,
            f.departure_date,
            fb.flight_booking_id,
            fb.total_price,
            COUNT(CASE WHEN p.category = 'infant' THEN 1 END) as infant_count
        FROM flight_booking fb
        JOIN flights f ON fb.flight_id = f.flight_id
        JOIN tickets t ON fb.flight_booking_id = t.flight_booking_id
        JOIN passengers p ON t.SSN = p.SSN
        WHERE p.category = 'infant'
        GROUP BY fb.flight_booking_id
        ORDER BY f.departure_date
    ";

    $result = $conn->query($query);
    $flights = [];

    while ($row = $result->fetch_assoc()) {
        $flights[] = $row;
    }

    closeDBConnection($conn);
    return ['success' => true, 'data' => $flights];
}

/**
 * Get all booked flights with at least one infant AND at least 5 children
 * ADMIN ONLY
 */
function getFlightsWithInfantsAnd5Children() {
    requireAdmin();

    $conn = getDBConnection();
    if (!$conn) return ['success' => false, 'error' => 'Database connection failed'];

    $query = "
        SELECT
            f.flight_id,
            f.origin,
            f.destination,
            f.departure_date,
            fb.flight_booking_id,
            fb.total_price,
            SUM(CASE WHEN p.category = 'infant' THEN 1 ELSE 0 END) as infant_count,
            SUM(CASE WHEN p.category = 'child' THEN 1 ELSE 0 END) as child_count
        FROM flight_booking fb
        JOIN flights f ON fb.flight_id = f.flight_id
        JOIN tickets t ON fb.flight_booking_id = t.flight_booking_id
        JOIN passengers p ON t.SSN = p.SSN
        GROUP BY fb.flight_booking_id
        HAVING infant_count >= 1 AND child_count >= 5
        ORDER BY f.departure_date
    ";

    $result = $conn->query($query);
    $flights = [];

    while ($row = $result->fetch_assoc()) {
        $flights[] = $row;
    }

    closeDBConnection($conn);
    return ['success' => true, 'data' => $flights];
}

/**
 * Get most expensive booked flights
 * ADMIN ONLY
 */
function getMostExpensiveFlights() {
    requireAdmin();

    $conn = getDBConnection();
    if (!$conn) return ['success' => false, 'error' => 'Database connection failed'];

    $query = "
        SELECT
            f.flight_id,
            f.origin,
            f.destination,
            f.departure_date,
            fb.flight_booking_id,
            fb.total_price,
            fb.booking_date
        FROM flight_booking fb
        JOIN flights f ON fb.flight_id = f.flight_id
        ORDER BY fb.total_price DESC
        LIMIT 10
    ";

    $result = $conn->query($query);
    $flights = [];

    while ($row = $result->fetch_assoc()) {
        $flights[] = $row;
    }

    closeDBConnection($conn);
    return ['success' => true, 'data' => $flights];
}

/**
 * Get flights departing from Texas with NO infant passengers
 * ADMIN ONLY
 */
function getTexasFlightsNoInfants() {
    requireAdmin();

    $conn = getDBConnection();
    if (!$conn) return ['success' => false, 'error' => 'Database connection failed'];

    $query = "
        SELECT DISTINCT
            f.flight_id,
            f.origin,
            f.destination,
            f.departure_date,
            fb.flight_booking_id,
            fb.total_price
        FROM flight_booking fb
        JOIN flights f ON fb.flight_id = f.flight_id
        WHERE (f.origin LIKE '%TX%' OR f.origin IN ('Dallas', 'Houston', 'Austin', 'San Antonio', 'Fort Worth', 'El Paso', 'Corpus Christi', 'Lubbock', 'Plano', 'Irving', 'Laredo'))
        AND fb.flight_booking_id NOT IN (
            SELECT DISTINCT fb2.flight_booking_id
            FROM flight_booking fb2
            JOIN tickets t ON fb2.flight_booking_id = t.flight_booking_id
            JOIN passengers p ON t.SSN = p.SSN
            WHERE p.category = 'infant'
        )
        ORDER BY f.departure_date
    ";

    $result = $conn->query($query);
    $flights = [];

    while ($row = $result->fetch_assoc()) {
        $flights[] = $row;
    }

    closeDBConnection($conn);
    return ['success' => true, 'data' => $flights];
}

/**
 * Count of booked flights arriving to California cities in Sep OR Oct 2024
 * ADMIN ONLY
 */
function getCaliforniaFlightCountSepOct() {
    requireAdmin();

    $conn = getDBConnection();
    if (!$conn) return ['success' => false, 'error' => 'Database connection failed'];

    $query = "
        SELECT COUNT(DISTINCT fb.flight_booking_id) as count
        FROM flight_booking fb
        JOIN flights f ON fb.flight_id = f.flight_id
        WHERE (f.destination LIKE '%CA%' OR f.destination IN ('Los Angeles', 'San Francisco', 'San Diego', 'Sacramento', 'San Jose', 'Fresno', 'Oakland', 'Long Beach', 'Anaheim', 'Riverside', 'Irvine', 'Santa Ana', 'Stockton', 'Bakersfield'))
        AND f.arrival_date BETWEEN '2024-09-01' AND '2024-10-31'
    ";

    $result = $conn->query($query);
    $row = $result->fetch_assoc();

    closeDBConnection($conn);
    return ['success' => true, 'count' => $row['count']];
}

?>
