/**
 * Crew Registration Page Module
 * Handles crew member registration form
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

    // Create user data object
    const userData = {
        accountType: 'crew',
        email: document.getElementById('email').value,
        password: password,
        profile: {
            firstName: document.getElementById('first_name').value,
            lastName: document.getElementById('last_name').value,
            membershipNumber: document.getElementById('membership_number').value,
            experience: document.getElementById('experience').value,
            socialPreference: document.getElementById('whatsapp_group').checked
        }
    };

    // Register user (creates account and signs in automatically)
    const result = await register(userData);

    if (result.success) {
        alert('Welcome to the crew! Check out the events page to register for your first sail!');
        window.location.href = 'dashboard.html';
    } else {
        alert('Error: ' + result.error);
    }
});
