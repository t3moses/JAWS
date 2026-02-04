/**
 * User Service Module
 * Handles user profile operations via API
 */

import { patch } from './apiService.js';
import { API_CONFIG } from './config.js';
import { isDeadlinePassed } from './eventService.js';

/**
 * Update user profile
 * @param {string} userId - User ID (ignored - backend uses JWT token from request)
 * @param {Object} updates - Object with fields to update
 * @returns {Promise<Object>} Result object with success status
 */
export async function updateUser(userId, updates) {
    try {
        console.log('Updating user profile via API:', updates);

        // Call PATCH /api/users/me endpoint
        // Backend uses JWT token to identify user, so userId is ignored
        const response = await patch(API_CONFIG.ENDPOINTS.USER_ME, updates);

        if (response?.success === false) {
            console.error('Profile update failed:', response.error);
            return { success: false, error: response.error || 'Failed to update profile' };
        }

        console.log('Profile updated successfully');
        return { success: true, data: response?.data };
    } catch (error) {
        console.error('Error updating profile:', error);
        return { success: false, error: error.message || 'Failed to update profile' };
    }
}

/**
 * Update event availability for current user
 * @param {string} userId - User ID (ignored - backend uses JWT token from request)
 * @param {string} eventDate - Event date (YYYY-MM-DD)
 * @param {boolean} isAvailable - Availability status
 * @returns {Promise<Object>} Result object with success status
 */
export async function updateEventAvailability(userId, eventDate, isAvailable) {
    try {
        // Check deadline (10 AM on event day)
        if (isDeadlinePassed(eventDate)) {
            return { success: false, error: 'Registration deadline has passed' };
        }

        console.log(`Updating availability for ${eventDate}: ${isAvailable}`);

        // Call PATCH /api/users/me/availability endpoint
        // Backend uses JWT token to identify user, so userId is ignored
        const response = await patch(API_CONFIG.ENDPOINTS.USER_AVAILABILITY, {
            availabilities: [
                {
                    eventId: eventDate,
                    isAvailable: isAvailable
                }
            ]
        });

        if (response?.success === false) {
            console.error('Availability update failed:', response.error);
            return { success: false, error: response.error || 'Failed to update availability' };
        }

        console.log('Availability updated successfully');
        return { success: true, data: response?.data };
    } catch (error) {
        console.error('Error updating availability:', error);
        return { success: false, error: error.message || 'Failed to update availability' };
    }
}
