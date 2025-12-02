// Frontend API Helper 

const API_URL = 'http://localhost:3000/api';

// Cart storage key
const CART_KEY = 'travelDealsCart';

// ============================================
// USER AUTHENTICATION API
// ============================================

async function registerUser(userData) {
    try {
        const response = await fetch(`${API_URL}/users/register`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(userData)
        });
        
        const result = await response.json();
        if (result.success) {
            console.log('✓ User registered:', result.user.phone);
            return result;
        } else {
            throw new Error(result.error);
        }
    } catch (error) {
        console.error('Error registering user:', error);
        return { success: false, error: error.message };
    }
}

async function loginUser(phone, password) {
    try {
        const response = await fetch(`${API_URL}/users/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ phone, password })
        });
        
        const result = await response.json();
        if (result.success) {
            console.log('✓ User logged in:', result.user.phone);
            // Store user in session
            sessionStorage.setItem('currentUser', JSON.stringify(result.user));
            return result;
        } else {
            throw new Error(result.error);
        }
    } catch (error) {
        console.error('Error logging in:', error);
        return { success: false, error: error.message };
    }
}

function logoutUser() {
    sessionStorage.removeItem('currentUser');
    console.log('✓ User logged out');
    window.location.href = '/';
}

function getCurrentUser() {
    const userStr = sessionStorage.getItem('currentUser');
    return userStr ? JSON.parse(userStr) : null;
}

function isLoggedIn() {
    return getCurrentUser() !== null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        alert('Please login to access this page');
        window.location.href = '/login';
        return false;
    }
    return true;
}

// ============================================
// CONTACTS API
// ============================================

async function saveContact(contactData) {
    try {
        const response = await fetch(`${API_URL}/contacts`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(contactData)
        });
        
        const result = await response.json();
        if (result.success) {
            console.log('✓ Contact saved:', result.contactId);
            return result;
        } else {
            throw new Error(result.error);
        }
    } catch (error) {
        console.error('Error saving contact:', error);
        alert('Error saving contact. Please try again.');
        return { success: false, error: error.message };
    }
}

async function getAllContacts() {
    try {
        const response = await fetch(`${API_URL}/contacts`);
        const result = await response.json();
        return result.contacts || [];
    } catch (error) {
        console.error('Error fetching contacts:', error);
        return [];
    }
}

// ============================================
// BOOKINGS API
// ============================================

async function saveBooking(bookingData) {
    try {
        const response = await fetch(`${API_URL}/bookings`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(bookingData)
        });
        
        const result = await response.json();
        if (result.success) {
            console.log('✓ Booking saved:', result.booking.bookingId);
            return result;
        } else {
            throw new Error(result.error);
        }
    } catch (error) {
        console.error('Error saving booking:', error);
        alert('Error saving booking. Please try again.');
        return { success: false, error: error.message };
    }
}

async function getAllBookings() {
    try {
        const response = await fetch(`${API_URL}/bookings`);
        const result = await response.json();
        return result.bookings || [];
    } catch (error) {
        console.error('Error fetching bookings:', error);
        return [];
    }
}

// ============================================
// FLIGHTS API
// ============================================

async function loadFlights() {
    try {
        console.log('Loading flights from:', `${API_URL}/flights`);
        
        const response = await fetch(`${API_URL}/flights`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || 'Failed to load flights');
        }
        
        if (!result.data || !result.data.flights) {
            throw new Error('Invalid flights data structure');
        }
        
        console.log('✓ Flights loaded successfully:', result.data.flights.length, 'flights');
        return result.data;
        
    } catch (error) {
        console.error('Error loading flights:', error);
        alert(`Error loading flights: ${error.message}\n\nPlease check:\n1. Server is running (node server.js)\n2. flights.json is in the data folder`);
        return { flights: [] };
    }
}

// ============================================
// HOTELS API
// ============================================

async function loadHotels() {
    try {
        console.log('Loading hotels from:', `${API_URL}/hotels`);
        
        const response = await fetch(`${API_URL}/hotels`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || 'Failed to load hotels');
        }
        
        console.log('✓ Hotels loaded successfully:', result.data.hotels.length, 'hotels');
        return result.data;
        
    } catch (error) {
        console.error('Error loading hotels:', error);
        alert(`Error loading hotels: ${error.message}`);
        return { hotels: [] };
    }
}

// ============================================
// CARS API
// ============================================

async function loadCars() {
    try {
        console.log('Loading cars from:', `${API_URL}/cars`);
        
        const response = await fetch(`${API_URL}/cars`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || 'Failed to load cars');
        }
        
        console.log('✓ Cars loaded successfully:', result.data.cars.length, 'cars');
        return result.data;
        
    } catch (error) {
        console.error('Error loading cars:', error);
        alert(`Error loading cars: ${error.message}`);
        return { cars: [] };
    }
}

// ============================================
// CART API - Uses sessionStorage
// ============================================

function getCart() {
    try {
        const cartData = sessionStorage.getItem(CART_KEY);
        return cartData ? JSON.parse(cartData) : [];
    } catch (error) {
        console.error('Error reading cart:', error);
        return [];
    }
}

function saveCartToStorage(cart) {
    try {
        sessionStorage.setItem(CART_KEY, JSON.stringify(cart));
    } catch (error) {
        console.error('Error saving cart:', error);
    }
}

function addToCart(item) {
    const cart = getCart();
    cart.push(item);
    saveCartToStorage(cart);
    alert('Item added to cart!');
    window.location.href = '/cart';
}

function clearCart() {
    sessionStorage.removeItem(CART_KEY);
}

function removeFromCart(index) {
    const cart = getCart();
    cart.splice(index, 1);
    saveCartToStorage(cart);
}

// ============================================
// CONNECTION TEST
// ============================================

async function testConnection() {
    try {
        console.log('Testing connection to backend...');
        const response = await fetch(`${API_URL}/flights`);
        if (response.ok) {
            console.log('✓ Backend connection successful');
            return true;
        } else {
            console.error('✗ Backend returned error:', response.status);
            return false;
        }
    } catch (error) {
        console.error('✗ Cannot connect to backend:', error.message);
        console.error('Make sure the server is running: node server.js');
        return false;
    }
}

// Run connection test on page load
if (typeof window !== 'undefined') {
    window.addEventListener('load', () => {
        testConnection();
    });
}