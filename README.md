# Memo Notepad

A modern, secure note-taking application built with PHP and SQLite3, inspired by memonotepad.com.

## Features

- **Modern UI**: Dark theme with FontAwesome icons
- **Secure Authentication**: 32-digit hexadecimal secret key system
- **SQLite3 Database**: Lightweight, file-based database
- **Responsive Design**: Works on desktop and mobile devices
- **Toast Notifications**: Modern, non-intrusive notifications
- **Floating Sidebar**: Collapsible sidebar with memo list
- **View/Edit Modes**: Seamless switching between viewing and editing
- **Auto-save**: Automatic saving while editing
- **Search Functionality**: Search through memo titles and content
- **Database Export**: Download SQLite database backup
- **Pretty URLs**: Clean URLs without .php extensions
- **Keyboard Shortcuts**: Ctrl+S to save, Ctrl+N for new memo, Escape to cancel

## Requirements

- PHP 7.4 or higher
- SQLite3 extension enabled
- Apache web server with mod_rewrite
- Modern web browser

## Installation

1. Clone or download the project to your web server directory
2. Ensure Apache mod_rewrite is enabled
3. Make sure the `data/` directory is writable by the web server
4. Access the application in your web browser

## First Time Setup

When you first visit the application:

1. The system will automatically create a new user
2. A 32-character secret key will be generated and displayed
3. **IMPORTANT**: Save this secret key securely - you'll need it to access your memos
4. The secret key will be pre-filled in the login form for your first login

## Usage

### Authentication
- Enter your 32-character secret key to login
- The key must be exactly 32 hexadecimal characters

### Creating Memos
- Click "New Memo" button or use Ctrl+N
- Enter a title and content
- Click "Save" or use Ctrl+S

### Managing Memos
- Click on any memo in the sidebar to view it
- Use the "Edit" button to modify existing memos
- Use the "Delete" button to remove memos (with confirmation)
- Use the search box to find specific memos

### Keyboard Shortcuts
- `Ctrl+S` or `Cmd+S`: Save current memo
- `Ctrl+N` or `Cmd+N`: Create new memo
- `Escape`: Cancel edit mode or close modals

## File Structure

```
memo-notepad/
├── api/
│   ├── api.php          # REST API endpoints
│   └── download.php     # Database download
├── assets/
│   ├── css/
│   │   └── style.css    # Main stylesheet
│   └── js/
│       └── app.js       # Frontend JavaScript
├── config/
│   └── Database.php     # Database configuration
├── models/
│   ├── User.php         # User model
│   └── Memo.php         # Memo model
├── data/                # SQLite database storage
├── auth.php             # Authentication handler
├── index.php            # Main application
├── login.php            # Login page
├── logout.php           # Logout handler
├── .htaccess            # Apache configuration
└── README.md            # This file
```

## API Endpoints

- `GET /api/memos` - Get all user memos
- `GET /api/memo/{id}` - Get specific memo
- `POST /api/memo` - Create new memo
- `PUT /api/memo/{id}` - Update existing memo
- `DELETE /api/memo/{id}` - Delete memo
- `GET /api/search?q={query}` - Search memos

## Security Features

- Session-based authentication
- SQL injection protection with prepared statements
- XSS protection with HTML escaping
- CSRF protection through session validation
- Secure headers via .htaccess
- Database file protection
- Input validation and sanitization

## Browser Support

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

## License

This project is open source and available under the MIT License.
