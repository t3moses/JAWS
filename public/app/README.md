# JAWS Frontend Application

This directory contains the frontend application for JAWS (Social Day Cruising).

## Directory Structure

```
public/app/
├── index.html       # Main HTML entry point
├── js/             # JavaScript files
│   └── main.js     # Main application logic
├── css/            # Stylesheets
│   └── styles.css  # Main styles
└── assets/         # Static assets (images, fonts, etc.)
```

## Replacing the Sample Frontend

To use your own frontend files:

1. **Replace `index.html`** with your main HTML file
2. **Add your JavaScript files** to the `js/` directory
3. **Add your CSS files** to the `css/` directory
4. **Add images/assets** to the `assets/` directory

## API Integration

The backend REST API is available at `/api`:

- `GET /api/events` - List all events
- `GET /api/events/{id}` - Get specific event
- `GET /api/flotillas` - Get all flotillas
- `POST /api/auth/login` - User login
- `POST /api/auth/register` - User registration
- `GET /api/users/me` - Get user profile
- `PATCH /api/users/me/availability` - Update availability
- `GET /api/assignments` - Get user assignments

See the main project README.md for complete API documentation.

## Authentication

Protected endpoints require a JWT token in the Authorization header:

```javascript
const token = localStorage.getItem('jaws_token');

fetch('/api/users/me', {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
    }
});
```

## Routing

The server is configured to serve `index.html` for all non-API routes, allowing client-side routing:

- `/` → Serves `index.html`
- `/events` → Serves `index.html` (handle routing in JS)
- `/profile` → Serves `index.html` (handle routing in JS)
- `/api/*` → API endpoints (handled by backend)
- `/app/js/*` → Static JS files (served directly)
- `/app/css/*` → Static CSS files (served directly)
- `/app/assets/*` → Static assets (served directly)

## Development

For local development, the PHP built-in server handles everything:

```bash
php -S localhost:8000 -t public
```

Visit http://localhost:8000 to see the frontend application.
