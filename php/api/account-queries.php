<?php
require_once '../config.php';
require_once '../functions.php';
require_once '../admin-functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Invalid request method', 405);
}

$query = $_GET['query'] ?? '';

switch ($query) {
    case 'my_flight_bookings':
        $user = getCurrentUser();
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT * FROM v_flight_bookings_summary WHERE user_phone = ? ORDER BY departure_date DESC");
        $stmt->bind_param("s", $user['phone']);
        $stmt->execute();
        $result = $stmt->get_result();
        $bookings = [];
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
        $stmt->close();
        closeDBConnection($conn);
        sendSuccess($bookings);
        break;

    case 'my_hotel_bookings':
        $user = getCurrentUser();
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT * FROM v_hotel_bookings_summary WHERE user_phone = ? ORDER BY check_in_date DESC");
        $stmt->bind_param("s", $user['phone']);
        $stmt->execute();
        $result = $stmt->get_result();
        $bookings = [];
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
        $stmt->close();
        closeDBConnection($conn);
        sendSuccess($bookings);
        break;

    case 'flight_passengers':
        $bookingId = $_GET['booking_id'] ?? '';
        if (empty($bookingId)) {
            sendError('Booking ID is required');
        }
        $conn = getDBConnection();
        $stmt = $conn->prepare("
            SELECT p.*, t.ticket_id, t.price
            FROM tickets t
            JOIN passengers p ON t.SSN = p.SSN
            WHERE t.flight_booking_id = ?
        ");
        $stmt->bind_param("s", $bookingId);
        $stmt->execute();
        $result = $stmt->get_result();
        $passengers = [];
        while ($row = $result->fetch_assoc()) {
            $passengers[] = $row;
        }
        $stmt->close();
        closeDBConnection($conn);
        sendSuccess($passengers);
        break;

    case 'september_bookings':
        $user = getCurrentUser();
        $conn = getDBConnection();
        $query = "
            SELECT 'flight' as type, f.flight_id, f.origin, f.destination, f.departure_date, fb.flight_booking_id as booking_id, fb.total_price, fb.booking_date
            FROM flight_booking fb
            JOIN flights f ON fb.flight_id = f.flight_id
            WHERE fb.user_phone = ? AND f.departure_date BETWEEN '2024-09-01' AND '2024-09-30'
            UNION ALL
            SELECT 'hotel' as type, h.hotel_id, h.hotel_name as origin, h.city as destination, hb.check_in_date as departure_date, hb.hotel_booking_id as booking_id, hb.total_price, hb.booking_date
            FROM hotel_booking hb
            JOIN hotels h ON hb.hotel_id = h.hotel_id
            WHERE hb.user_phone = ? AND hb.check_in_date BETWEEN '2024-09-01' AND '2024-09-30'
            ORDER BY departure_date DESC
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $user['phone'], $user['phone']);
        $stmt->execute();
        $result = $stmt->get_result();
        $bookings = [];
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
        $stmt->close();
        closeDBConnection($conn);
        sendSuccess($bookings);
        break;

    case 'flights_by_ssn':
        $ssn = $_GET['ssn'] ?? '';
        if (empty($ssn)) {
            sendError('SSN is required');
        }
        $conn = getDBConnection();
        $stmt = $conn->prepare("
            SELECT DISTINCT f.*, fb.flight_booking_id, fb.total_price, t.ticket_id
            FROM tickets t
            JOIN flight_booking fb ON t.flight_booking_id = fb.flight_booking_id
            JOIN flights f ON fb.flight_id = f.flight_id
            WHERE t.SSN = ?
            ORDER BY f.departure_date DESC
        ");
        $stmt->bind_param("s", $ssn);
        $stmt->execute();
        $result = $stmt->get_result();
        $flights = [];
        while ($row = $result->fetch_assoc()) {
            $flights[] = $row;
        }
        $stmt->close();
        closeDBConnection($conn);
        sendSuccess($flights);
        break;

    // ADMIN QUERIES
    case 'admin_texas_flights':
        $result = getTexasFlightsSepOct();
        sendSuccess($result['data']);
        break;

    case 'admin_texas_hotels':
        $result = getTexasHotelsSepOct();
        sendSuccess($result['data']);
        break;

    case 'admin_expensive_hotels':
        $result = getMostExpensiveHotels();
        sendSuccess($result['data']);
        break;

    case 'admin_flights_infants':
        $result = getFlightsWithInfants();
        sendSuccess($result['data']);
        break;

    case 'admin_flights_infants_children':
        $result = getFlightsWithInfantsAnd5Children();
        sendSuccess($result['data']);
        break;

    case 'admin_expensive_flights':
        $result = getMostExpensiveFlights();
        sendSuccess($result['data']);
        break;

    case 'admin_texas_flights_no_infants':
        $result = getTexasFlightsNoInfants();
        sendSuccess($result['data']);
        break;

    case 'admin_california_count':
        $result = getCaliforniaFlightCountSepOct();
        sendSuccess(['count' => $result['count']]);
        break;

    default:
        sendError('Invalid query parameter');
}
?>
