# Schoolist - Class Page Manager

A production-ready PHP 8.3 + MySQL web application for managing class pages (Linktree-style) for schools. Built for shared hosting environments (Apache + phpMyAdmin + MySQL).

## Features

- **Public Class Pages**: Beautiful, mobile-first Hebrew (RTL) pages with blocks and announcements
- **Passwordless Authentication**: OTP-based login via email
- **Multi-tenant**: Each school/class has its own page
- **Invitation System**: Admin-controlled invitation codes for user registration
- **Rich Content Editor**: WYSIWYG editor with drag-and-drop block ordering
- **AI Integration**: OpenAI Vision API for extracting schedules and contacts from images
- **Short Link Activation**: `/q/{number}` mechanism for permanent redirects
- **Admin Panel**: System admin interface for managing invitations, pages, and activations
- **i18n Support**: Hebrew (default) and English language support

## Requirements

- PHP 8.3 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Apache with mod_rewrite enabled
- PHP extensions: PDO, PDO_MySQL, curl, mbstring, json
- OpenAI API key (optional, for AI features)

## Installation

### 1. Upload Files

Upload all files to your web server (subdomain or subdirectory).

### 2. Set Permissions

```bash
chmod 755 config/
chmod 755 public/uploads/
chmod 755 logs/
chmod 755 storage/
```

### 3. Run Setup Wizard

Navigate to `/setup` in your browser and follow the installation wizard:

1. **Check Requirements**: Verifies PHP version and extensions
2. **Database Configuration**: Enter MySQL credentials and table prefix
3. **Base URL**: Set your application's base URL (e.g., `https://app.schoolist.co.il`)
4. **Email Configuration**: Configure SMTP or use PHP mail()
5. **Finalize**: Enter OpenAI API key (optional) and create initial admin user

### 4. Database Setup

The installer will automatically create all required tables. Manual setup is also possible:

```bash
mysql -u username -p database_name < database/schema.sql
```

Remember to replace `sl_` prefix in the SQL file if using a different prefix.

### 5. Configuration

After installation, configuration is stored in `config/config.php`. To re-run setup, delete `setup.lock`.

## Configuration

### Database

Edit `config/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_PREFIX', 'sl_');
```

### Base URL

```php
define('BASE_URL', 'https://app.schoolist.co.il/');
```

### Email (SMTP)

```php
define('SMTP_ENABLED', true);
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your_smtp_user');
define('SMTP_PASS', 'your_smtp_password');
define('SMTP_FROM', 'noreply@schoolist.co.il');
define('SMTP_FROM_NAME', 'Schoolist');
```

### OpenAI API

```php
define('OPENAI_API_KEY', 'sk-...');
```

## Usage

### System Admin

1. Login at `/login` (use the email configured during setup)
2. Access admin panel at `/admin`
3. Create invitation codes for schools/classes
4. Manage pages and Q activations

### Page Admin

1. Receive invitation code from system admin
2. Login at `/login` with your email
3. Enter invitation code when prompted
4. Access editor at `/editor/{pageId}`
5. Add announcements and blocks
6. Configure page settings

### Public Pages

- View class pages at `/p/{uniquePageId}`
- Share pages using the share button
- Activate short links at `/q/{number}`

## Block Types

1. **Schedule (מערכת שעות)**: Upload image, AI extracts structured schedule
2. **Contacts (אנשי קשר חשובים)**: Upload image or paste text, AI extracts contacts
3. **WhatsApp Groups (קבוצות וואטסאפ)**: List of WhatsApp group links
4. **Useful Links (קישורים שימושיים)**: List of useful links
5. **Calendar (לוח חופשות)**: Rich text for holidays and special days

## Security Features

- **CSRF Protection**: All forms protected with CSRF tokens
- **XSS Prevention**: HTML sanitization on input and output
- **SQL Injection Prevention**: Prepared statements everywhere
- **Rate Limiting**: OTP requests and verification attempts are rate-limited
- **File Upload Security**: MIME type validation, safe file naming
- **Session Security**: HttpOnly cookies + localStorage tokens
- **Input Validation**: Server-side validation for all inputs

## File Structure

```
/
├── app/
│   ├── Controllers/      # MVC Controllers
│   ├── Core/             # Core framework (Router, Request, Response)
│   ├── Middleware/      # Authentication and authorization
│   ├── Repositories/     # Data access layer
│   ├── Services/         # Business logic
│   └── Views/           # PHP templates
├── config/
│   ├── config.php        # Main configuration
│   └── lang/            # Translation files
├── database/
│   └── schema.sql       # Database schema
├── public/
│   ├── assets/          # CSS, JS, images
│   └── uploads/         # User-uploaded files
├── storage/             # Rate limit storage
├── logs/                # Application logs
├── index.php            # Entry point
├── .htaccess           # Apache rewrite rules
└── README.md
```

## API Endpoints

### Authentication
- `POST /api/auth/request-otp` - Request OTP code
- `POST /api/auth/verify-otp` - Verify OTP and login
- `POST /api/auth/refresh` - Refresh auth token
- `POST /api/auth/logout` - Logout
- `GET /api/me` - Get current user

### Pages & Blocks
- `GET /api/pages/{pageId}` - Get page data
- `POST /api/pages/{pageId}/blocks` - Create block
- `PUT /api/pages/{pageId}/blocks/{blockId}` - Update block
- `DELETE /api/pages/{pageId}/blocks/{blockId}` - Delete block
- `POST /api/pages/{pageId}/blocks/reorder` - Reorder blocks
- `POST /api/pages/{pageId}/announcements` - Create announcement
- `PUT /api/pages/{pageId}/announcements/{announcementId}` - Update announcement
- `DELETE /api/pages/{pageId}/announcements/{announcementId}` - Delete announcement
- `POST /api/pages/{pageId}/announcements/reorder` - Reorder announcements
- `POST /api/pages/{pageId}/settings` - Update page settings

### AI
- `POST /api/ai/extract-schedule` - Extract schedule from image
- `POST /api/ai/extract-contacts` - Extract contacts from image or text

### Q Activation
- `POST /api/q/activate` - Activate Q number

### Admin
- `GET /api/admin/invitations` - List invitations
- `POST /api/admin/invitations` - Create invitation
- `PUT /api/admin/invitations/{id}` - Update invitation
- `GET /api/admin/pages` - List pages
- `GET /api/admin/q-activations` - List Q activations

## Troubleshooting

### 500 Internal Server Error

- Check PHP error logs: `logs/app.log`
- Verify file permissions
- Check `.htaccess` is working
- Verify database connection

### Database Connection Failed

- Verify credentials in `config/config.php`
- Check MySQL user has proper permissions
- Ensure database exists

### OTP Emails Not Sending

- Check SMTP configuration
- Verify SMTP credentials
- Test with PHP `mail()` function
- Check spam folder

### AI Extraction Not Working

- Verify OpenAI API key is set
- Check API key has sufficient credits
- Verify image format is supported (JPEG, PNG, GIF, WebP)

## Development

### Adding New Block Types

1. Add block type to editor UI (`app/Views/editor/index.php`)
2. Add rendering logic in `public/assets/js/public.js`
3. Add editor form in `public/assets/js/editor.js`
4. Update translations in `config/lang/he.php` and `config/lang/en.php`

### Adding New Languages

1. Create new file: `config/lang/{lang}.php`
2. Copy structure from `he.php` or `en.php`
3. Translate all strings
4. Update `I18n` service to support language switching

## License

This project is proprietary software. All rights reserved.

## Support

For issues and questions, contact the development team.















