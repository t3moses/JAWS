/**
 * Dashboard Page Module
 * Handles dashboard page display and event availability management
 */

import { requireAuth, getCurrentUser, signOut } from '../authService.js';
import { getAllEvents, isDeadlinePassed } from '../eventService.js';
import { updateEventAvailability } from '../userService.js';

// Make signOut available globally for onclick handlers
window.signOut = signOut;

// Require authentication
if (!requireAuth()) {
    // requireAuth redirects to signin.html if not authenticated
}

// Get current user
const user = await getCurrentUser();
if (!user) {
    console.error('No user found, redirecting to sign in');
    alert('Session error. Please sign in again.');
    window.location.href = 'signin.html';
    throw new Error('No user found'); // Stop execution
}

console.log('User loaded successfully:', user.email);

// Populate username in hero and nav
document.getElementById('hero-username').textContent = user.profile.firstName;
document.getElementById('nav-username').textContent = user.profile.firstName;

// Populate account badge
const badge = document.getElementById('account-badge');
if (user.accountType === 'crew') {
    badge.textContent = '🌊 Crew Member';
    badge.classList.add('crew-member');
} else {
    badge.textContent = '⛵ Boat Owner';
    badge.classList.add('boat-owner');
}

// Populate profile details
const profileDetails = document.getElementById('profile-details');
let profileHTML = '';
console.log(user);
if (user.accountType === 'crew') {
    profileHTML = `
        <div class="profile-item">
            <span class="profile-label">Name:</span>
            <span class="profile-value">${user.profile.firstName} ${user.profile.lastName}</span>
        </div>
        <div class="profile-item">
            <span class="profile-label">Email:</span>
            <span class="profile-value">${user.email}</span>
        </div>
        ${user.membershipNumber ? `
        <div class="profile-item">
            <span class="profile-label">Membership Number:</span>
            <span class="profile-value">${user.profile.membershipNumber}</span>
        </div>
        ` : ''}
        <div class="profile-item">
            <span class="profile-label">Experience:</span>
            <span class="profile-value">${formatExperience(user.profile.experience)}</span>
        </div>
        <div class="profile-item">
            <span class="profile-label">WhatsApp Group:</span>
            <span class="profile-value">${user.profile.whatsappGroup ? 'Yes, enrolled' : 'Not enrolled'}</span>
        </div>
    `;
} else {
    profileHTML = `
        <div class="profile-item">
            <span class="profile-label">Name:</span>
            <span class="profile-value">${user.profile.firstName} ${user.profile.lastName}</span>
        </div>
        <div class="profile-item">
            <span class="profile-label">Email:</span>
            <span class="profile-value">${user.email}</span>
        </div>
        <div class="profile-item">
            <span class="profile-label">Phone:</span>
            <span class="profile-value">${user.profile.phone}</span>
        </div>
        <div class="profile-item">
            <span class="profile-label">Boat Name:</span>
            <span class="profile-value">${user.profile.boatName}</span>
        </div>
        <div class="profile-item">
            <span class="profile-label">Crew Capacity:</span>
            <span class="profile-value">${user.profile.minCrew || 1} - ${user.profile.maxCrew} crew members</span>
        </div>
        <div class="profile-item">
            <span class="profile-label">First Mate Requested:</span>
            <span class="profile-value">${user.profile.requestFirstMate ? 'Yes' : 'No'}</span>
        </div>
        <div class="profile-item">
            <span class="profile-label">WhatsApp Group:</span>
            <span class="profile-value">${user.profile.whatsappGroup ? 'Yes, enrolled' : 'Not enrolled'}</span>
        </div>
    `;
}

profileDetails.innerHTML = profileHTML;

// Helper function for formatting
function formatExperience(value) {
    const labels = {
        'none': 'None',
        'competent_crew': 'Competent Crew',
        'competent_first_mate': 'Competent First Mate'
    };
    return labels[value] || value;
}

// Populate event availability checkboxes
async function populateEventCheckboxes() {
    const availabilityList = document.getElementById('availability-list');

    try {
        const events = await getAllEvents();

        events.forEach(event => {
            // Use event.eventId (display name format like "Fri Jun 12") to match availabilities
            const isAvailable = user.eventAvailability[event.eventId] || false;
            const deadlinePassed = isDeadlinePassed(event.date);

            const itemDiv = document.createElement('div');
            itemDiv.className = 'availability-item' + (deadlinePassed ? ' disabled' : '');

            itemDiv.innerHTML = `
                <input type="checkbox"
                       id="event-${event.eventId}"
                       data-event-date="${event.eventId}"
                       data-original="${isAvailable}"
                       ${isAvailable ? 'checked' : ''}
                       ${deadlinePassed ? 'disabled' : ''}>
                <label for="event-${event.eventId}" class="availability-date">
                    ${event.displayDate || event.eventId}
                </label>
                ${deadlinePassed ? '<span class="deadline-warning">Deadline Passed</span>' : ''}
            `;

            availabilityList.appendChild(itemDiv);
        });
    } catch (error) {
        console.error('Failed to load events:', error);
        availabilityList.innerHTML = '<div class="alert alert-error">Failed to load events. Please refresh the page.</div>';
    }
}

// Call the async function
populateEventCheckboxes();

// Handle save availability button
document.getElementById('save-availability').addEventListener('click', async function() {
    const successMessage = document.getElementById('success-message');
    const errorMessage = document.getElementById('error-message');

    // Hide messages
    successMessage.style.display = 'none';
    errorMessage.style.display = 'none';

    // Get all checkboxes
    const checkboxes = document.querySelectorAll('.availability-item input[type="checkbox"]');
    let hasError = false;
    let hasChanges = false;
    const failedEvents = [];

    const saveButton = this;
    const originalLabel = saveButton.textContent;
    saveButton.disabled = true;
    saveButton.textContent = 'Saving...';

    // Use for...of to properly handle async operations
    for (const checkbox of checkboxes) {
        if (checkbox.disabled) {
            continue;
        }

        const eventDate = checkbox.getAttribute('data-event-date');
        const isAvailable = checkbox.checked;
        const originalValue = checkbox.dataset.original === 'true';

        if (originalValue === isAvailable) {
            continue;
        }

        hasChanges = true;

        const result = await updateEventAvailability(user.userId, eventDate, isAvailable);

        if (!result.success) {
            errorMessage.textContent = result.error || 'Failed to update availability';
            errorMessage.style.display = 'block';
            hasError = true;
            failedEvents.push(eventDate);
            checkbox.checked = originalValue; // Revert checkbox on error
        } else {
            checkbox.dataset.original = String(isAvailable);
            user.eventAvailability[eventDate] = isAvailable;
        }
    }

    saveButton.disabled = false;
    saveButton.textContent = originalLabel;

    if (!hasChanges) {
        successMessage.textContent = 'No changes to save.';
        successMessage.style.display = 'block';
        setTimeout(() => {
            successMessage.style.display = 'none';
        }, 2000);
        return;
    }

    if (hasError) {
        if (failedEvents.length > 1) {
            errorMessage.textContent = 'Some availability updates failed. Please try again.';
        }
        return;
    }

    successMessage.textContent = 'Availability updated successfully!';
    successMessage.style.display = 'block';

    // Hide success message after 3 seconds
    setTimeout(() => {
        successMessage.style.display = 'none';
    }, 3000);
});
