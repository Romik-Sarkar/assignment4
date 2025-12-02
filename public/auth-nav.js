// Authentication and navigation management


// UPDATE NAVIGATION BASED ON LOGIN STATUS

function updateNavigation() {
    const currentUser = sessionStorage.getItem('currentUser');
    
    const loginNav = document.getElementById('loginNav');
    const registerNav = document.getElementById('registerNav');
    const logoutNav = document.getElementById('logoutNav');
    const accountNav = document.getElementById('accountNav');
    const userGreeting = document.getElementById('userGreeting');
    const userNameDisplay = document.getElementById('userNameDisplay');
    
    if (currentUser) {
        // User is logged in
        const user = JSON.parse(currentUser);
        
        // Hide login and register, show logout and account
        if (loginNav) loginNav.style.display = 'none';
        if (registerNav) registerNav.style.display = 'none';
        if (logoutNav) logoutNav.style.display = 'block';
        if (accountNav) accountNav.style.display = 'block';
        
        // Show user greeting in header (requirement #3)
        if (userGreeting && userNameDisplay) {
            userNameDisplay.textContent = user.firstName + ' ' + user.lastName;
            userGreeting.style.display = 'block';
        }
        
        console.log('✓ User logged in:', user.firstName, user.lastName);
    } else {
        // User is NOT logged in
        
        // Show login and register, hide logout and account
        if (loginNav) loginNav.style.display = 'block';
        if (registerNav) registerNav.style.display = 'block';
        if (logoutNav) logoutNav.style.display = 'none';
        if (accountNav) accountNav.style.display = 'none';
        
        // Hide user greeting
        if (userGreeting) userGreeting.style.display = 'none';
        
        console.log('User not logged in');
    }
}


// LOGOUT FUNCTION

function logout(event) {
    if (event) event.preventDefault();
    
    // Confirm logout
    if (!confirm('Are you sure you want to logout?')) {
        return;
    }
    
    // Clear session
    sessionStorage.removeItem('currentUser');
    
    // Update navigation
    updateNavigation();
    
    // Redirect to home page
    window.location.href = '/';
}


// CHECK IF USER IS LOGGED IN

function isLoggedIn() {
    return sessionStorage.getItem('currentUser') !== null;
}


// GET CURRENT USER

function getCurrentUser() {
    const userStr = sessionStorage.getItem('currentUser');
    return userStr ? JSON.parse(userStr) : null;
}


// REQUIRE LOGIN (for protected pages)

function requireLogin() {
    if (!isLoggedIn()) {
        alert('Please login to access this page');
        window.location.href = '/login';
        return false;
    }
    return true;
}


// INIT - Run when navigation is loaded

// This will be called by navLoader.js after navigation HTML is loaded
window.addEventListener('load', function() {
    // Small delay to ensure navigation is loaded
    setTimeout(updateNavigation, 100);
});

// Optional: Listen for storage changes (if user logs in/out in another tab)
window.addEventListener('storage', function(e) {
    if (e.key === 'currentUser') {
        updateNavigation();
    }
});