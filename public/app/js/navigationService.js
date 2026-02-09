/**
 * Navigation Service
 * Provides common navigation update logic for authenticated users
 *
 * This module encapsulates the repetitive pattern of updating navigation
 * based on authentication state across multiple page modules.
 */

/**
 * Updates navigation UI when user is authenticated
 *
 * NOTE: As of the anti-FOUC refactor, the navigation structure is pre-rendered
 * in HTML with both authenticated and unauthenticated items. CSS classes
 * (.nav-authenticated, .nav-unauthenticated) control visibility based on the
 * auth state set by auth-init.js.
 *
 * This function now ONLY:
 * - Fills in the user's first name (replaces "..." placeholder)
 * - Attaches the sign-out event handler
 *
 * @param {Object} user - User object from AuthService.getCurrentUser()
 * @param {Function} signOut - Sign out function from AuthService
 * @returns {boolean} true if navigation was updated, false if user not provided
 *
 * @example
 * import { isSignedIn, getCurrentUser, signOut } from '../authService.js';
 * import { updateAuthenticatedNavigation } from '../navigationService.js';
 *
 * if (isSignedIn()) {
 *     const user = getCurrentUser();
 *     updateAuthenticatedNavigation(user, signOut);
 * }
 */
export function updateAuthenticatedNavigation(user, signOut) {
    if (!user) {
        console.warn('NavigationService: No user provided');
        return false;
    }

    // Update user's first name in the greeting (nav structure already exists in HTML)
    const userNameSpan = document.querySelector('.user-name');
    if (userNameSpan) {
        userNameSpan.textContent = user.profile.firstName;
    } else {
        console.warn('NavigationService: .user-name element not found');
    }

    // Attach sign-out event handler
    const signOutBtn = document.querySelector('.sign-out-btn');
    if (signOutBtn) {
        signOutBtn.addEventListener('click', (e) => {
            e.preventDefault();
            signOut();
        });
    } else {
        console.warn('NavigationService: .sign-out-btn element not found');
    }

    return true;
}
