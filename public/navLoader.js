// navLoader.js - Updated to show user name on all pages

// Global logout function
function logout(event) {
    event.preventDefault();
    sessionStorage.removeItem('currentUser');
    alert('You have been logged out.');
    window.location.href = '/';
}

document.addEventListener('DOMContentLoaded', function() {
    // Load the navigation HTML
    const navPlaceholder = document.getElementById('nav-placeholder');
    if (navPlaceholder) {
        fetch('/topNav.html')
            .then(response => response.text())
            .then(html => {
                navPlaceholder.innerHTML = html;
                updateNavigation();
                initializeDateTime(); // Initialize date/time after nav is loaded
            })
            .catch(error => {
                console.error('Error loading navigation:', error);
            });
    } else {
        // If nav already loaded, just update it
        updateNavigation();
        initializeDateTime();
    }
});

// Display current date and time
function updateDateTime() {
    const datetimeElement = document.getElementById('datetime');
    if (datetimeElement) {
        const now = new Date();
        const options = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        };
        datetimeElement.textContent = now.toLocaleDateString('en-US', options);
    }
}

function initializeDateTime() {
    updateDateTime();
    setInterval(updateDateTime, 1000);
}

function updateNavigation() {
    // Check if user is logged in
    const currentUser = JSON.parse(sessionStorage.getItem('currentUser') || 'null');
    
    if (currentUser) {
        // User is logged in - show account, logout, and greeting; hide login and register
        const userGreetingNav = document.getElementById('userGreetingNav');
        const userGreetingText = document.getElementById('userGreetingText');
        const accountNav = document.getElementById('accountNav');
        const logoutNav = document.getElementById('logoutNav');
        const loginNav = document.getElementById('loginNav');
        const registerNav = document.getElementById('registerNav');
        
        if (userGreetingNav) {
            userGreetingNav.style.display = 'block';
            if (userGreetingText) {
                userGreetingText.textContent = `Welcome, ${currentUser.firstName} ${currentUser.lastName}`;
            }
        }
        if (accountNav) accountNav.style.display = 'block';
        if (logoutNav) logoutNav.style.display = 'block';
        if (loginNav) loginNav.style.display = 'none';
        if (registerNav) registerNav.style.display = 'none';
    } else {
        // User is not logged in - show login and register, hide account, logout, and greeting
        const userGreetingNav = document.getElementById('userGreetingNav');
        const accountNav = document.getElementById('accountNav');
        const logoutNav = document.getElementById('logoutNav');
        const loginNav = document.getElementById('loginNav');
        const registerNav = document.getElementById('registerNav');
        
        if (userGreetingNav) userGreetingNav.style.display = 'none';
        if (accountNav) accountNav.style.display = 'none';
        if (logoutNav) logoutNav.style.display = 'none';
        if (loginNav) loginNav.style.display = 'block';
        if (registerNav) registerNav.style.display = 'block';
    }
}