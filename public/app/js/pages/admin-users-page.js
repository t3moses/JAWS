/**
 * Admin Users Page
 * Manage admin privileges for registered users
 */

import { requireAuth, getCurrentUser, signOut } from '../authService.js';
import { updateAuthenticatedNavigation, addAdminLink } from '../navigationService.js';
import { initHamburgerMenu } from '../hamburger.js';
import * as adminService from '../adminService.js';
import { showToast } from '../toast.js';

let currentUser = null;

// Initialize page
document.addEventListener('DOMContentLoaded', async () => {
    initHamburgerMenu();

    requireAuth();

    currentUser = await getCurrentUser();
    if (!currentUser) {
        window.location.href = 'signin.html';
        return;
    }

    if (!currentUser.isAdmin) {
        console.warn('Access denied: User is not an admin');
        window.location.href = 'dashboard.html';
        return;
    }

    updateAuthenticatedNavigation(currentUser, signOut);
    addAdminLink(currentUser);

    await loadUsers();
});

/**
 * Load all users and render the table
 */
async function loadUsers() {
    const container = document.getElementById('users-container');

    try {
        const users = await adminService.getAllUsers();
        renderUsersTable(container, users);
    } catch (error) {
        console.error('Failed to load users:', error);
        container.innerHTML = '<p class="error-message">Failed to load users. Please try again.</p>';
    }
}

/**
 * Render the users table
 * @param {HTMLElement} container
 * @param {Object[]} users
 */
function renderUsersTable(container, users) {
    if (users.length === 0) {
        container.innerHTML = '<p>No registered users found.</p>';
        return;
    }

    const rows = users.map(user => renderUserRow(user)).join('');

    container.innerHTML = `
        <table class="data-table">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Account Type</th>
                    <th>Admin</th>
                    <th>Edit</th>
                </tr>
            </thead>
            <tbody>
                ${rows}
            </tbody>
        </table>
    `;

    // Attach button listeners
    container.querySelectorAll('[data-user-id]').forEach(btn => {
        btn.addEventListener('click', () => handleAdminToggle(btn));
    });
}

/**
 * Render a single user table row
 * @param {Object} user
 * @returns {string} HTML string
 */
function renderUserRow(user) {
    const isSelf = user.id === currentUser.id;
    const accountLabel = user.account_type === 'boat_owner' ? 'Boat Owner' : 'Crew';

    let adminCell;
    if (isSelf) {
        adminCell = '<span class="text-muted">You</span>';
    } else if (user.is_admin) {
        adminCell = `<button class="btn btn-sm btn-danger" data-user-id="${user.id}" data-is-admin="true">Revoke Admin</button>`;
    } else {
        adminCell = `<button class="btn btn-sm btn-primary" data-user-id="${user.id}" data-is-admin="false">Grant Admin</button>`;
    }

    const editCell = `<a href="admin-user-edit.html?userId=${user.id}" class="btn btn-sm btn-secondary">Edit</a>`;

    return `
        <tr id="user-row-${user.id}">
            <td>${escapeHtml(user.email)}</td>
            <td>${accountLabel}</td>
            <td>${adminCell}</td>
            <td>${editCell}</td>
        </tr>
    `;
}

/**
 * Handle admin toggle button click
 * @param {HTMLButtonElement} btn
 */
async function handleAdminToggle(btn) {
    const userId = parseInt(btn.dataset.userId, 10);
    const currentIsAdmin = btn.dataset.isAdmin === 'true';
    const newIsAdmin = !currentIsAdmin;

    const action = newIsAdmin ? 'grant admin privileges to' : 'revoke admin privileges from';
    if (!confirm(`Are you sure you want to ${action} this user?`)) {
        return;
    }

    btn.disabled = true;
    btn.classList.add('loading');

    try {
        await adminService.setUserAdmin(userId, newIsAdmin);
        showToast(`Admin status updated successfully. Changes take effect on the user's next login.`, 'success');
        await loadUsers();
    } catch (error) {
        console.error('Failed to update admin status:', error);
        showToast(error.message || 'Failed to update admin status', 'error');
        btn.disabled = false;
        btn.classList.remove('loading');
    }
}

/**
 * Escape HTML special characters
 * @param {string} str
 * @returns {string}
 */
function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
