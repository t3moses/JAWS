/**
 * Sign In Page Module
 * Handles user authentication on the sign-in page
 */

import { isSignedIn, signIn } from '../authService.js';
import { showError } from '../toastService.js';

// Check if already signed in
if (await isSignedIn()) {
    window.location.href = 'dashboard.html';
}

const form = document.getElementById('signin-form');

form.addEventListener('submit', async function(e) {
    e.preventDefault();

    // Get form values
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;

    // Attempt sign in
    const result = await signIn(email, password);

    if (result.success) {
        // Redirect to dashboard
        window.location.href = 'dashboard.html';
    } else {
        // Show error message as toast
        showError(result.error);

        // Clear password field
        document.getElementById('password').value = '';
    }
});
