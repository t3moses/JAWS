/**
 * System Status Page Module
 * Displays API connection status and authentication state
 */

import { hasToken } from '../tokenService.js';
import { getCurrentEnvironment, API_CONFIG } from '../config.js';
import * as ApiService from '../apiService.js';

// Make functions available globally
window.testApiConnection = testApiConnection;
window.clearToken = clearSessionToken;

// Display environment info
function displayEnvironmentInfo() {
    const envName = getCurrentEnvironment();
    document.getElementById('environment-name').textContent = envName.toUpperCase();
    document.getElementById('api-base-url').textContent = API_CONFIG.BASE_URL;
}

// Display authentication status
function displayAuthStatus() {
    const tokenPresent = hasToken();
    const tokenStatusElement = document.getElementById('token-status');

    if (tokenPresent) {
        tokenStatusElement.textContent = '✅ Yes';
        tokenStatusElement.className = 'status-value success';
    } else {
        tokenStatusElement.textContent = '❌ No';
        tokenStatusElement.className = 'status-value error';
    }
}

// Test API connection
async function testApiConnection() {
    const statusElement = document.getElementById('connection-status');
    const button = document.getElementById('test-connection-btn');

    // Update UI to show testing state
    statusElement.textContent = '⏳ Testing...';
    statusElement.className = 'status-value';
    button.disabled = true;

    try {
        // Try to fetch events (public endpoint)
        const events = await ApiService.getAllEvents();

        if (events && Array.isArray(events)) {
            statusElement.textContent = `✅ Connected (${events.length} events found)`;
            statusElement.className = 'status-value success';
        } else {
            statusElement.textContent = '⚠️ Connected but unexpected response';
            statusElement.className = 'status-value warning';
        }
    } catch (error) {
        statusElement.textContent = `❌ Failed: ${error.message}`;
        statusElement.className = 'status-value error';
    } finally {
        button.disabled = false;
    }
}

// Clear session token (for testing)
function clearSessionToken() {
    if (confirm('Are you sure you want to clear your session token? You will need to sign in again.')) {
        sessionStorage.removeItem('nsc_auth_token');
        alert('Session token cleared!');
        displayAuthStatus();
    }
}

// Initial load
displayEnvironmentInfo();
displayAuthStatus();

// Auto-test connection on load
testApiConnection();
