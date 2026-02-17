/**
 * Admin Service
 * Handles all admin-related API calls
 */

import * as apiService from './apiService.js';
import { API_CONFIG } from './config.js';

/**
 * Get matching data for an event (capacity analysis)
 * @param {string} eventId - Event identifier
 * @returns {Promise<Object>} Matching data with available boats, crews, and capacity summary
 */
export async function getMatchingData(eventId) {
    try {
        const response = await apiService.get(API_CONFIG.ENDPOINTS.ADMIN_MATCHING, { eventId });

        if (!response.success) {
            throw new Error(response.message || 'Failed to load matching data');
        }

        return response.data;
    } catch (error) {
        console.error('AdminService: Failed to get matching data:', error);
        throw error;
    }
}

/**
 * Send email notifications for an event
 * @param {string} eventId - Event identifier
 * @param {boolean} includeCalendar - Whether to include calendar invites
 * @returns {Promise<Object>} Result with count of emails sent
 */
export async function sendNotifications(eventId, includeCalendar = true) {
    try {
        const response = await apiService.post(API_CONFIG.ENDPOINTS.ADMIN_NOTIFICATIONS, {
            include_calendar: includeCalendar
        }, { eventId });

        if (!response.success) {
            throw new Error(response.message || 'Failed to send notifications');
        }

        return response.data;
    } catch (error) {
        console.error('AdminService: Failed to send notifications:', error);
        throw error;
    }
}

/**
 * Get current season configuration
 * @returns {Promise<Object>} Season configuration data
 */
export async function getSeasonConfig() {
    try {
        const response = await apiService.get(API_CONFIG.ENDPOINTS.ADMIN_CONFIG);

        if (!response.success) {
            throw new Error(response.message || 'Failed to load season configuration');
        }

        return response.data;
    } catch (error) {
        console.error('AdminService: Failed to get season config:', error);
        throw error;
    }
}

/**
 * Update season configuration
 * @param {Object} configData - Configuration data to update
 * @returns {Promise<Object>} Updated configuration
 */
export async function updateSeasonConfig(configData) {
    try {
        const response = await apiService.patch(API_CONFIG.ENDPOINTS.ADMIN_CONFIG, configData);

        if (!response.success) {
            throw new Error(response.message || 'Failed to update season configuration');
        }

        return response.data;
    } catch (error) {
        console.error('AdminService: Failed to update season config:', error);
        throw error;
    }
}

/**
 * Get all registered users
 * @returns {Promise<Object[]>} Array of user summaries
 */
export async function getAllUsers() {
    try {
        const response = await apiService.get(API_CONFIG.ENDPOINTS.ADMIN_USERS);

        if (!response.success) {
            throw new Error(response.message || 'Failed to load users');
        }

        return response.data;
    } catch (error) {
        console.error('AdminService: Failed to get users:', error);
        throw error;
    }
}

/**
 * Grant or revoke admin privileges for a user
 * @param {number} userId - Target user ID
 * @param {boolean} isAdmin - Whether to grant (true) or revoke (false) admin
 * @returns {Promise<Object>} Updated user summary
 */
export async function setUserAdmin(userId, isAdmin) {
    try {
        const response = await apiService.patch(API_CONFIG.ENDPOINTS.ADMIN_USER_ADMIN, { is_admin: isAdmin }, { id: userId });

        if (!response.success) {
            throw new Error(response.message || 'Failed to update admin status');
        }

        return response.data;
    } catch (error) {
        console.error('AdminService: Failed to set user admin:', error);
        throw error;
    }
}
