/**
 * Admin Notifications Page
 * Send email notifications and calendar invites to participants
 */

import { requireAuth, getCurrentUser, signOut } from '../authService.js';
import { updateAuthenticatedNavigation, addAdminLink } from '../navigationService.js';
import { initHamburgerMenu } from '../hamburger.js';
import * as eventService from '../eventService.js';
import * as apiService from '../apiService.js';
import * as adminService from '../adminService.js';
import { showToast } from '../toast.js';

let allEvents = [];
let currentEventData = null;

// Initialize page
document.addEventListener('DOMContentLoaded', async () => {
    // Initialize hamburger menu
    initHamburgerMenu();

    // Require authentication
    requireAuth();

    // Get current user
    const user = await getCurrentUser();
    if (!user) {
        window.location.href = 'signin.html';
        return;
    }

    // Check admin privileges
    if (!user.isAdmin) {
        console.warn('Access denied: User is not an admin');
        window.location.href = 'dashboard.html';
        return;
    }

    // Update navigation
    updateAuthenticatedNavigation(user, signOut);
    addAdminLink(user);

    // Load events
    await loadEvents();

    // Setup event listeners
    setupEventListeners();
});

/**
 * Load all events
 */
async function loadEvents() {
    try {
        allEvents = await eventService.getAllEvents();
        populateEventSelect();
    } catch (error) {
        console.error('Failed to load events:', error);
        showToast('Failed to load events', 'error');
    }
}

/**
 * Populate event dropdown
 */
function populateEventSelect() {
    const select = document.getElementById('event-select');
    const previewBtn = document.getElementById('preview-btn');

    // Clear existing options (keep the first placeholder)
    select.innerHTML = '<option value="">-- Select an event --</option>';

    // Add event options
    allEvents.forEach(event => {
        const option = document.createElement('option');
        option.value = event.eventId;
        // Parse date as local date by appending time component
        const localDate = new Date(event.date + 'T12:00:00');
        option.textContent = `${event.eventId} (${localDate.toLocaleDateString()})`;
        select.appendChild(option);
    });

    // Enable preview button when event selected
    select.addEventListener('change', () => {
        previewBtn.disabled = !select.value;
    });
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    const previewBtn = document.getElementById('preview-btn');
    const sendBtn = document.getElementById('send-btn');
    const eventSelect = document.getElementById('event-select');
    const confirmModal = document.getElementById('confirm-modal');
    const cancelBtn = document.getElementById('cancel-btn');
    const confirmBtn = document.getElementById('confirm-btn');

    // Load preview
    previewBtn.addEventListener('click', async () => {
        const eventId = eventSelect.value;
        if (!eventId) return;

        await loadPreview(eventId);
    });

    // Send notifications (show confirmation modal)
    sendBtn.addEventListener('click', () => {
        showConfirmationModal();
    });

    // Cancel modal
    cancelBtn.addEventListener('click', () => {
        hideConfirmationModal();
    });

    // Confirm send
    confirmBtn.addEventListener('click', async () => {
        hideConfirmationModal();
        await sendNotifications();
    });

    // Close modal on backdrop click
    confirmModal.addEventListener('click', (e) => {
        if (e.target === confirmModal) {
            hideConfirmationModal();
        }
    });
}

/**
 * Load preview for selected event
 */
async function loadPreview(eventId) {
    const previewBtn = document.getElementById('preview-btn');
    const emptyState = document.getElementById('empty-state');

    try {
        // Show loading state
        previewBtn.classList.add('loading');
        previewBtn.disabled = true;

        // Fetch event with flotilla
        currentEventData = await apiService.getEventById(eventId);

        // Hide empty state
        emptyState.style.display = 'none';

        // Render preview
        renderPreview(currentEventData);

        showToast('Preview loaded successfully', 'success');
    } catch (error) {
        console.error('Failed to load preview:', error);
        showToast(error.message || 'Failed to load preview', 'error');
    } finally {
        // Remove loading state
        previewBtn.classList.remove('loading');
        previewBtn.disabled = false;
    }
}

/**
 * Render preview
 */
function renderPreview(eventData) {
    const previewSection = document.getElementById('preview-section');
    const optionsSection = document.getElementById('options-section');
    const eventDetails = document.getElementById('event-details');
    const participantCount = document.getElementById('participant-count');
    const participantList = document.getElementById('participant-list');

    // Show sections
    previewSection.classList.remove('hidden');
    optionsSection.style.display = 'block';

    // Extract event and flotilla data
    const event = eventData.event;
    const flotilla = eventData.flotilla;

    // Render event details
    // Parse date as local date by appending time component
    const eventDate = new Date(event.date + 'T12:00:00');
    eventDetails.innerHTML = `
        <strong>${event.eventId}</strong><br>
        ${eventDate.toLocaleDateString()} at ${event.startTime}
    `;

    // Get boats and count actual email recipients
    const boats = [];
    let totalRecipients = 0;

    if (flotilla && flotilla.crewedBoats) {
        flotilla.crewedBoats.forEach(crewedBoat => {
            const boatName = crewedBoat.boat?.displayName || 'Unknown Boat';
            const crewCount = crewedBoat.crews ? crewedBoat.crews.length : 0;

            // Count boat owner + all crew members
            totalRecipients += 1 + crewCount;

            boats.push(`${boatName} (1 boat owner + ${crewCount} crew = ${1 + crewCount} emails)`);
        });
    }

    // Update participant count (total people receiving emails)
    participantCount.textContent = totalRecipients;

    // Render boat list
    if (boats.length > 0) {
        participantList.innerHTML = boats.map(b => `<li>${b}</li>`).join('');
    } else {
        participantList.innerHTML = '<li>No participants assigned yet</li>';
    }
}

/**
 * Show confirmation modal
 */
function showConfirmationModal() {
    const modal = document.getElementById('confirm-modal');
    const confirmMessage = document.getElementById('confirm-message');
    const participantCount = document.getElementById('participant-count');

    const count = parseInt(participantCount.textContent) || 0;
    confirmMessage.textContent = `Are you sure you want to send notifications to ${count} people (boat owners + crew)?`;

    modal.classList.remove('hidden');
}

/**
 * Hide confirmation modal
 */
function hideConfirmationModal() {
    const modal = document.getElementById('confirm-modal');
    modal.classList.add('hidden');
}

/**
 * Send notifications
 */
async function sendNotifications() {
    const sendBtn = document.getElementById('send-btn');
    const eventSelect = document.getElementById('event-select');
    const includeCalendar = document.getElementById('include-calendar').checked;

    const eventId = eventSelect.value;
    if (!eventId) return;

    try {
        // Show loading state
        sendBtn.classList.add('loading');
        sendBtn.disabled = true;

        // Send notifications
        const result = await adminService.sendNotifications(eventId, includeCalendar);

        showToast(`Successfully sent ${result.emails_sent || 0} email notifications`, 'success');
    } catch (error) {
        console.error('Failed to send notifications:', error);
        showToast(error.message || 'Failed to send notifications', 'error');
    } finally {
        // Remove loading state
        sendBtn.classList.remove('loading');
        sendBtn.disabled = false;
    }
}
