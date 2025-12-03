# CS 6314 Assignment #4 - Travel Deals App - Testing Summary

## Tech Stack
- **Backend:** PHP 8.5 + MySQL
- **Frontend:** HTML5, CSS3, Vanilla JavaScript
- **Data:** JSON (flights), XML (hotels, contacts)
- **Server:** PHP built-in server on port 8000

## Database Schema
```sql
Database: travel_deals

Tables:
- users (Phone PK, Password, FirstName, LastName, DateOfBirth, Email, Gender)
- flights (flight_id PK, origin, destination, departure_date, arrival_date, departure_time, arrival_time, available_seats, price)
- passengers (SSN PK, first_name, last_name, date_of_birth, category)
- flight_booking (flight_booking_id PK, flight_id FK, user_phone FK, total_price)
- tickets (ticket_id PK, flight_booking_id FK, SSN FK, price)
- hotels (hotel_id PK, hotel_name, city, price_per_night)
- hotel_booking (hotel_booking_id PK, hotel_id FK, user_phone FK, check_in_date, check_out_date, num_rooms, price_per_night, total_price)
- guesses (SSN, hotel_booking_id FK, first_name, last_name, date_of_birth, category)
```

## Pages (7 Required)
1. **index.html** - Home
2. **stays.html** - Hotel search/booking
3. **flights.html** - Flight search/booking
4. **contact.html** - Contact form (requires login, stores XML)
5. **cart.html** - Shopping cart
6. **login.html** - User login
7. **register.html** - User registration
8. **account.html** - My Account (bookings + admin queries)

## Key Features to Test

### 1. User Registration (register.html)
- Phone format: `ddd-ddd-dddd` (REQUIRED, UNIQUE)
- Password: min 8 chars, must match confirmation
- FirstName, LastName: REQUIRED
- DOB format: `MM-DD-YYYY` (REQUIRED)
- Email: must contain `@` and `.com` (REQUIRED)
- Gender: OPTIONAL
- Phone `222-222-2222` is RESERVED for admin
- API: POST `/api-router.php/register.php`

### 2. User Login (login.html)
- Admin: Phone `222-222-2222`, Password `admin123`
- Stores user in `sessionStorage.currentUser`
- API: POST `/api-router.php/login.php`
- **Expected:** User name displays in nav after login

### 3. Contact Form (contact.html)
- Requires login
- Comment: min 10 characters
- Stores to `data/contacts.xml`
- API: POST `/api-router.php/contact-submit.php`

### 4. Flights System (flights.html)
**Data:** 62 flights in `data/flights.json` (TX ↔ CA, Sep 1 - Dec 1, 2024)

**Search:**
- Trip type: One-way OR Round-trip
- Origin, Destination: TX or CA cities
- Departure date: Sep 1 - Dec 1, 2024
- Passengers: Adults (0-4), Children (0-4), Infants (0-4)
- ±3 days flexible search if no exact match
- API: POST `/api-router.php/flights-search.php`

**Pricing:**
- Adult: 100% of base price
- Child: 70% of base price
- Infant: 10% of base price

**Booking:**
- Requires: SSN, FirstName, LastName, DOB for each passenger
- Generates: flight-booking-id, ticket-id per passenger
- Updates: decrements available_seats
- API: POST `/api-router.php/flight-booking.php`

### 5. Hotels System (stays.html)
**Data:** 25 hotels in `data/hotels.xml` (TX & CA, unlimited availability)

**Search:**
- City: TX or CA
- Check-in/out: Sep 1 - Dec 1, 2024
- Guests: Adults, Children, Infants
- Room calc: Max 2 guests per room (infants don't count)
- API: POST `/api-router.php/hotels-search.php`

**Booking:**
- Requires: SSN, FirstName, LastName, DOB for each guest
- Total: num_rooms × price_per_night × num_nights
- Generates: hotel-booking-id
- API: POST `/api-router.php/hotel-booking.php`

### 6. My Account - User Queries (account.html)
All users can:
1. View my flight bookings
2. View my hotel bookings
3. Get passengers for flight booking by ID
4. Search flights by passenger SSN
5. View all Sep 2024 bookings (flights + hotels)

**API:** GET `/api-router.php/account-queries.php?query=<query_name>`

### 7. My Account - Admin Queries (account.html)
Admin only (phone `222-222-2222`):
1. Load flights from JSON → database
2. Load hotels from XML → database
3. All booked TX flights (Sep-Oct 2024)
4. All booked TX hotels (Sep-Oct 2024)
5. Most expensive booked hotels
6. Flights with ≥1 infant
7. Flights with ≥1 infant AND ≥5 children
8. Most expensive booked flights
9. TX flights with NO infants
10. COUNT CA-arriving flights (Sep-Oct 2024)

**APIs:**
- POST `/api-router.php/admin-load-flights.php`
- POST `/api-router.php/admin-load-hotels.php`
- GET `/api-router.php/account-queries.php?query=admin_*`

## Known Issues to Test

### Critical
1. ❌ User name not displaying after login
2. ❌ Flight search not working
3. ❌ Hotel search not working
4. ⚠️ Admin load buttons (flights.json, hotels.xml → DB)

### To Verify
- Session persistence across pages
- Cart functionality
- Booking confirmation displays
- Available seats/rooms decrement
- XML contact storage
- Date validation (MM-DD-YYYY, Sep 1 - Dec 1 range)
- Phone uniqueness check
- Password hashing/verification

## Test Data

**Admin Login:**
- Phone: `222-222-2222`
- Password: `admin123`

**Test Cities:**
- Texas: Dallas, Houston, Austin, San Antonio, Fort Worth, El Paso
- California: Los Angeles, San Francisco, San Diego, Sacramento, San Jose

**Date Range:** September 1, 2024 - December 1, 2024

**Sample SSN Format:** `123-45-6789`

## How to Run

```bash
# Start MySQL
mysql.server start

# Start PHP server
cd "/path/to/assignment4"
php -S localhost:8000 -t public

# Access: http://localhost:8000/index.html
```

## File Structure
```
assignment4/
├── public/          # Frontend (HTML/CSS/JS)
├── php/
│   ├── config.php   # DB config
│   ├── functions.php
│   ├── admin-functions.php
│   └── api/         # API endpoints
├── data/
│   ├── flights.json
│   ├── hotels.xml
│   └── contacts.xml
└── database/
    └── schema.sql
```

## API Router
All API calls go through `/api-router.php/<endpoint>.php`
- Changes working directory to `php/api/` for includes
- Returns JSON responses

---

**Generate test cases for:** Registration validation, login flow, flight/hotel search, booking process, admin queries, session management, data persistence, error handling.
