// JAWS Frontend Application

// API Base URL
const API_BASE = '/api';

// Initialize application
document.addEventListener('DOMContentLoaded', async () => {
    console.log('JAWS Application Loaded');

    try {
        // Test API connection
        await testApiConnection();

        // Load events
        await loadEvents();

    } catch (error) {
        console.error('Initialization error:', error);
        showError('Failed to initialize application');
    }
});

// Test API connection
async function testApiConnection() {
    try {
        const response = await fetch(`${API_BASE}/events`);

        if (!response.ok) {
            throw new Error(`API returned status ${response.status}`);
        }

        console.log('API connection successful');
        return true;

    } catch (error) {
        console.error('API connection failed:', error);
        throw error;
    }
}

// Load and display events
async function loadEvents() {
    try {
        const response = await fetch(`${API_BASE}/events`);

        if (!response.ok) {
            throw new Error(`Failed to load events: ${response.status}`);
        }

        const data = await response.json();

        if (data.success && data.data && data.data.events) {
            displayEvents(data.data.events);
        } else {
            throw new Error('Invalid response format');
        }

    } catch (error) {
        console.error('Failed to load events:', error);
        showError('Failed to load events');
    }
}

// Display events in the UI
function displayEvents(events) {
    const content = document.getElementById('content');

    if (!events || events.length === 0) {
        content.innerHTML = `
            <h2>No Events Found</h2>
            <p>There are no events scheduled at this time.</p>
        `;
        return;
    }

    const eventsHtml = events.map(event => `
        <div class="event-card">
            <h3>${event.eventId}</h3>
            <p><strong>Date:</strong> ${event.date}</p>
            <p><strong>Time:</strong> ${event.startTime} - ${event.finishTime}</p>
            <p><strong>Status:</strong> ${event.status}</p>
        </div>
    `).join('');

    content.innerHTML = `
        <h2>Upcoming Events</h2>
        <div class="events-list">
            ${eventsHtml}
        </div>
    `;
}

// Show error message
function showError(message) {
    const content = document.getElementById('content');
    content.innerHTML = `
        <div class="error">
            <h2>Error</h2>
            <p>${message}</p>
        </div>
    `;
}
