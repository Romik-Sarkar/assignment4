# CS 6314 Assignment #4 - Travel Deals Web Application

## Quick Start Guide

This README provides immediate steps to get your PHP/MySQL application running.

---

## Prerequisites

- **MySQL** installed and running
- **PHP 7.4+** installed
- **Web browser**

---

## Setup Instructions (5 Minutes)

### Step 1: Start MySQL
```bash
# macOS
brew services start mysql

# Or manually
mysql.server start
```

### Step 2: Create Database
```bash
# Login to MySQL (enter your root password when prompted)
mysql -u root -p

# In MySQL console, run:
source /Users/mir/Library/Mobile\ Documents/com~apple~CloudDocs/School/UTD/Fall\ 2025/CS6314/Homework/Assignment-4/assignment4/database/schema.sql

# Exit MySQL
exit;
```

### Step 3: Configure Database Credentials
Edit the file: `assignment4/php/config.php`

Update these lines with your MySQL credentials:
```php
define('DB_USER', 'root');          // Your MySQL username
define('DB_PASS', 'your_password'); // Your MySQL password
```

### Step 4: Start PHP Server
```bash
cd "/Users/mir/Library/Mobile Documents/com~apple~CloudDocs/School/UTD/Fall 2025/CS6314/Homework/Assignment-4/assignment4"

php -S localhost:8000
```

### Step 5: Open Application
Open your browser to: **http://localhost:8000/public/index.html**

---

## Admin Account

**Phone:** 222-222-2222
**Password:** admin123

Use this account to:
- Load flights from JSON
- Load hotels from XML
- Access admin-only queries

---

## Project Structure

```
assignment4/
├── database/
│   └── schema.sql              ← MySQL database schema
├── php/
│   ├── config.php              ← Database configuration
│   ├── functions.php           ← Core PHP functions
│   ├── admin-functions.php     ← Admin-only functions
│   └── api/                    ← PHP API endpoints
│       ├── register.php
│       ├── login.php
│       ├── logout.php
│       ├── contact-submit.php
│       ├── flights-search.php
│       ├── hotels-search.php
│       ├── flight-booking.php
│       ├── hotel-booking.php
│       ├── admin-load-flights.php
│       ├── admin-load-hotels.php
│       └── account-queries.php
├── data/
│   ├── flights.json            ← 62 flights (TX ↔ CA)
│   ├── hotels.xml              ← 25 hotels (TX & CA)
│   └── contacts.xml            ← Contact submissions
├── public/
│   ├── *.html                  ← HTML pages
│   ├── mystyle.css             ← All styling
│   └── *.js                    ← JavaScript files
└── IMPLEMENTATION_GUIDE.md     ← Detailed implementation guide
```

---

## Key Features Implemented

### ✅ User Authentication
- Registration with validation (phone format, password length, unique phone)
- Login with session management
- Admin account (phone: 222-222-2222) with special privileges

### ✅ Contact System
- Login required to submit
- Minimum 10 character comment
- Stores data in XML format

### ✅ Flights System
- 62 flights between Texas and California cities
- Date range: September 1 - December 1, 2024
- One-way and round-trip search
- Passenger categories: Adult (100%), Child (70%), Infant (10%)
- ±3 days flexible search
- Cart and booking system
- Automatic seat availability updates

### ✅ Hotels System
- 25 hotels in Texas and California
- Unlimited availability
- Room calculation (max 2 guests per room, infants excluded)
- Booking with guest information
- Price calculation (rooms × nights × price_per_night)

### ✅ My Account - User Queries
1. Retrieve hotel bookings by booking ID
2. Retrieve flight bookings by booking ID
3. Retrieve passengers for a flight booking
4. Retrieve all September 2024 bookings (flights + hotels)
5. Retrieve flights by passenger SSN

### ✅ My Account - Admin Queries (phone: 222-222-2222)
1. Load flights.json into database
2. Load hotels.xml into database
3. All booked flights from Texas cities (Sep-Oct 2024)
4. All booked hotels in Texas cities (Sep-Oct 2024)
5. Most expensive booked hotels
6. Flights with at least 1 infant passenger
7. Flights with at least 1 infant AND 5+ children
8. Most expensive booked flights
9. Texas flights with NO infant passengers
10. Count of flights arriving to California (Sep-Oct 2024)

---

## Database Tables

### Users
- `users` - User accounts (phone is primary key)

### Flights
- `flights` - Flight listings
- `passengers` - Passenger information (SSN is primary key)
- `flight_booking` - Flight bookings
- `tickets` - Tickets linking passengers to bookings

### Hotels
- `hotels` - Hotel listings
- `hotel_booking` - Hotel reservations
- `guesses` - Guests for hotel bookings

### Views
- `v_flight_bookings_summary` - Flight bookings with passenger counts
- `v_hotel_bookings_summary` - Hotel bookings with guest counts

---

## API Endpoints

All endpoints are located at: `http://localhost:8000/php/api/`

### Public Endpoints
- `POST /register.php` - Register new user
- `POST /login.php` - Login user
- `GET /logout.php` - Logout user

### Protected Endpoints (Login Required)
- `POST /contact-submit.php` - Submit contact form
- `POST /flights-search.php` - Search flights
- `POST /hotels-search.php` - Search hotels
- `POST /flight-booking.php` - Book flight
- `POST /hotel-booking.php` - Book hotel
- `GET /account-queries.php?query={query_name}` - Account queries

### Admin Endpoints (Admin Login Required)
- `POST /admin-load-flights.php` - Load flights from JSON
- `POST /admin-load-hotels.php` - Load hotels from XML

---

## Testing Workflow

### 1. First-Time Setup
1. Login as admin (222-222-2222 / admin123)
2. Load flights from JSON
3. Load hotels from XML
4. Logout

### 2. User Registration
1. Go to Register page
2. Fill form with valid data
3. Phone format: XXX-XXX-XXXX
4. Password: minimum 8 characters
5. DOB format: MM-DD-YYYY
6. Email: must contain @ and .com

### 3. Flight Booking
1. Login
2. Go to Flights page
3. Select trip type (one-way or round-trip)
4. Choose origin and destination (Texas ↔ California)
5. Select departure date (Sep 1 - Dec 1, 2024)
6. Add passengers (adults, children, infants)
7. Search flights
8. Select flight
9. Add to cart
10. Fill passenger information (SSN required)
11. Book

### 4. Hotel Booking
1. Login
2. Go to Stays page
3. Select city
4. Choose check-in and check-out dates
5. Add guests (adults, children, infants)
6. System calculates rooms needed (max 2 guests per room)
7. Search hotels
8. Select hotel
9. Add to cart
10. Fill guest information (SSN required)
11. Book

### 5. View Bookings
1. Login
2. Go to My Account page
3. View flight bookings
4. View hotel bookings
5. Search by booking ID or SSN

### 6. Admin Queries
1. Login as admin
2. Go to My Account page
3. Access admin-only section
4. Run various admin queries

---

## Validation Rules

### Phone Number
- Format: `ddd-ddd-dddd`
- Must be unique
- 222-222-2222 reserved for admin

### Password
- Minimum 8 characters
- Must match confirmation

### Date of Birth
- Format: `MM-DD-YYYY`
- Must be valid date

### Email
- Must contain `@` and `.com`

### Dates (Bookings)
- Between September 1, 2024 and December 1, 2024

### Passengers
- Max 4 per category (adults, children, infants)

### Hotel Rooms
- Max 2 guests per room (infants don't count)

---

## Styling

All styling is preserved in `public/mystyle.css`:
- Color scheme: Blue gradients, teal accents, dark navy
- Responsive design (desktop, tablet, mobile)
- Animations: fadeIn, slideIn, pulse
- Interactive elements: hover effects, active states
- Form styling: validation messages, success/error displays

**DO NOT modify mystyle.css** - all existing styling is maintained.

---

## Troubleshooting

### "Database connection failed"
```bash
# Check MySQL is running
mysql.server status

# Verify credentials in php/config.php
# Test connection
mysql -u root -p travel_deals
```

### "Table doesn't exist"
```bash
# Re-run schema
mysql -u root -p
source /path/to/assignment4/database/schema.sql
```

### "Permission denied" for XML files
```bash
chmod 666 assignment4/data/contacts.xml
chmod 755 assignment4/data
```

### PHP errors
```bash
# Check PHP version (need 7.4+)
php -v

# Check extensions
php -m | grep -i mysqli
php -m | grep -i xml
```

---

## File Locations

- **Database Schema:** `assignment4/database/schema.sql`
- **PHP Config:** `assignment4/php/config.php`
- **API Endpoints:** `assignment4/php/api/*.php`
- **Data Files:** `assignment4/data/`
- **Frontend:** `assignment4/public/`
- **Documentation:** `IMPLEMENTATION_GUIDE.md`

---

## Assignment Requirements

This implementation meets **ALL** CS 6314 Assignment #4 requirements:

✅ 7 pages (stays, flights, contact-us, register, login, cart, my-account)
✅ External CSS (mystyle.css)
✅ Header, nav, side panel, main content, footer on all pages
✅ Register page with validation
✅ Login with session management
✅ Admin hardcoded (222-222-2222)
✅ Date/time display on all pages
✅ Contact form (login required, min 10 chars, XML storage)
✅ UI customization (font size, background color)
✅ Flights system (JSON data, search, booking, cart)
✅ Hotels system (XML data, search, booking, cart)
✅ Admin load features (JSON & XML)
✅ User queries (5 types)
✅ Admin queries (10 types)
✅ Database tables (users, flights, passengers, flight_booking, tickets, hotels, hotel_booking, guesses)
✅ Validation (client & server side)
✅ Security (prepared statements, password hashing, input sanitization)

---

## Support

For detailed implementation information, see: **IMPLEMENTATION_GUIDE.md**

For assignment-specific questions, refer to your CS 6314 course materials.

---

## License

This is a course assignment for CS 6314. All rights reserved.
