/**
 * Password Utility Module
 * Handles password hashing
 * WARNING: This is a prototype using Base64 encoding - NOT secure for production!
 */

/**
 * Simple password hashing (Base64 for prototype only)
 * WARNING: This is NOT secure! Use bcrypt/argon2 in production
 * @param {string} password - Plain text password
 * @returns {string} Hashed password
 */
export function hashPassword(password) {
    // TODO: Replace with proper hashing (bcrypt/argon2) in production
    return btoa(password);
}
