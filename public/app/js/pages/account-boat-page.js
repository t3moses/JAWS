/**
 * Boat Owner Registration Page Module
 * Handles boat owner registration form
 */

import { isSignedIn, register } from '../authService.js';

// Check if already signed in
if (await isSignedIn()) {
    window.location.href = 'dashboard.html';
}

document.querySelector('form').addEventListener('submit', async function(e) {
    e.preventDefault();

    // Get password values
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;

    // Validate passwords match
    if (password !== confirmPassword) {
        alert('Passwords do not match! Please try again.');
        return;
    }

    // Validate password length
    if (password.length < 8) {
        alert('Password must be at least 8 characters long.');
        return;
    }

    // Validate password requirements
    if (!/[A-Z]/.test(password)) {
        alert('Password must contain at least one uppercase letter (A-Z).');
        return;
    }

    if (!/[a-z]/.test(password)) {
        alert('Password must contain at least one lowercase letter (a-z).');
        return;
    }

    if (!/[0-9]/.test(password)) {
        alert('Password must contain at least one number (0-9).');
        return;
    }

    // Create user data object
    const userData = {
        accountType: 'boat_owner',
        email: document.getElementById('email').value,
        password: password,
        profile: {
            firstName: document.getElementById('first_name').value,
            lastName: document.getElementById('last_name').value,
            phone: document.getElementById('phone').value,
            boatName: document.getElementById('boat_name').value,
            minCrew: document.getElementById('min_crew').value,
            maxCrew: document.getElementById('max_crew').value,
            requestFirstMate: document.getElementById('request_first_mate').checked,
            whatsappGroup: document.getElementById('whatsapp_group').checked
        }
    };

    // Register user (creates account and signs in automatically)
    const result = await register(userData);

    if (result.success) {
        alert('Boat registered! Now set your availability for events and meet your crew!');
        window.location.href = 'dashboard.html';
    } else {
        alert('Error: ' + result.error);
    }
});
