# S2S Postback Checker

A comprehensive Server-to-Server (S2S) postback testing tool for affiliate marketing and lead tracking. This application provides a complete solution for testing postback integrations with auto-click creation, manual testing tools, and support for both modern and legacy URL patterns.

## Features

- **Postback Testing**: Manual postback URL testing with detailed response analysis
- **Auto-Click Creation**: Automatically creates clicks when none exist for testing
- **Dual URL Support**: Supports both modern click.php flow and legacy offer.php?id&tid pattern
- **Placeholder Protection**: Validates and rejects literal {transaction_id} placeholders
- **Token Replacement**: 19+ dynamic tokens for comprehensive postback customization
- **Error Prevention**: Fixed SQLSTATE[HY093] errors with proper PDO parameter binding
- **Dark Glass UI**: Modern responsive interface with glassmorphism design

## Quick Start

### Installation

1. **Web Installer**: Visit `install/install.php` for guided database setup
2. **Manual Setup**: Import `install/schema.sql` and configure `includes/config.php`

### Supported URL Patterns

#### 1. Click.php Flow (Recommended)
```
https://yourdomain.com/click.php?offer=1&sub1={transaction_id}&sub2=campaign
```
- Creates click record and redirects to offer page
- Handles placeholder validation and UUID generation
- Best for tracking campaign performance

#### 2. Direct Offer Access (Legacy Support)
```
https://yourdomain.com/offer.php?id=1&tid={transaction_id}
```
- Direct access to offer page with transaction ID
- Auto-creates click if none exists (source=direct-offer)
- Maintains compatibility with existing integrations

### Manual Postback Testing

1. Go to **Manual Postback Test Tool**
2. Enter a real transaction ID (NOT {transaction_id})
3. Paste your postback URL
4. Click "Fire Test Postback"
5. View detailed results including HTTP status, response time, and response body

## Key Fixes Implemented

### 1. SQLSTATE[HY093] Resolution
- **Problem**: Invalid parameter number errors in ClickModel INSERT operations
- **Solution**: Switched to positional placeholders (?) with exact parameter count matching
- **Impact**: Eliminates PDO binding errors during click creation

### 2. Manual Postback Tool Enhancement
- **Problem**: Tool failed when no prior click existed for transaction ID
- **Solution**: Auto-creates synthetic click with source='manual' when needed
- **Impact**: Tool works independently without requiring pre-existing clicks

### 3. Legacy URL Pattern Support
- **Problem**: Older integrations used /offer.php?id=&tid= pattern
- **Solution**: Added support with auto-click creation (source='direct-offer')
- **Impact**: Maintains backward compatibility while preserving new features

### 4. Placeholder Validation
- **Problem**: Users pasted literal {transaction_id} causing issues
- **Solution**: Validates input and prompts for real values with helpful error messages
- **Impact**: Prevents confusion and ensures proper testing

## Available Tokens

The postback system supports these dynamic tokens:

```
{transaction_id}  - Unique transaction identifier
{goal}           - Conversion goal (e.g., "lead", "sale")
{name}           - User's name from form submission
{email}          - User's email from form submission
{offer_id}       - Offer identifier
{sub1} to {sub5} - Custom tracking parameters
{timestamp}      - Unix timestamp
{click_id}       - Internal click record ID
{date}           - Current date (Y-m-d)
{datetime}       - Current datetime (Y-m-d H:i:s)
{ip}             - User's IP address
{user_agent}     - User's browser user agent
{referer}        - HTTP referer header
{random}         - Random 6-digit number
```

## Usage Examples

### Basic Click Flow
1. **User clicks**: `click.php?offer=1&sub1=campaign123&sub2=source`
2. **System creates**: Click record with transaction ID
3. **User redirected**: To offer.php with click ID
4. **User submits**: Lead form (name/email)
5. **System fires**: Postback with all tokens replaced

### Manual Testing
1. **Access**: `/postback-test.php`
2. **Enter**: Transaction ID (e.g., `test_12345`)
3. **Paste**: Postback URL with tokens
4. **Fire**: Test request and view response analysis

### Direct Offer Access
1. **User visits**: `offer.php?id=1&tid=existing_transaction`
2. **System finds**: Existing click OR auto-creates one
3. **User submits**: Lead form
4. **System fires**: Postback normally

## Troubleshooting

### HY093 Invalid Parameter Number
- **Cause**: Mismatch between SQL placeholders and execute() parameters
- **Fix**: All ClickModel methods now use positional placeholders with exact parameter counts
- **Prevention**: Never directly interpolate variables into SQL; always use prepared statements

### Manual Test Tool Not Working
- **Cause**: Missing click record for transaction ID
- **Fix**: Tool now auto-creates synthetic clicks as needed
- **Note**: Look for "Auto-created synthetic click" messages in error logs

### Literal {transaction_id} Issues
- **Cause**: Copy-pasting template URLs without replacing placeholders
- **Fix**: Input validation rejects literal placeholders with helpful error messages
- **Solution**: Use real values like `test_12345` instead of `{transaction_id}`

## Technical Architecture

- **Backend**: PHP 8.0+ with PDO for database operations
- **Database**: MySQL 5.7+ / MariaDB 10.2+ with normalized schema
- **Frontend**: Modern CSS with CSS Grid/Flexbox and vanilla JavaScript
- **Security**: CSRF protection, prepared statements, input validation
- **Logging**: Comprehensive error logging with context information

## File Structure

```
├── click.php              # Click tracking endpoint
├── offer.php              # Offer page with legacy support
├── postback-test.php       # Manual postback testing tool
├── index.php              # Dashboard and analytics
├── includes/
│   ├── config.php         # Database configuration
│   └── postback.php       # Postback firing engine
├── lib/
│   └── ClickModel.php     # Click data model (HY093 fixed)
├── install/
│   ├── install.php        # Web-based installer
│   └── schema.sql         # Database schema
└── css/
    └── style.css          # Dark glass theme styles
```

## Security Features

- **CSRF Protection**: All forms protected with token validation
- **SQL Injection Prevention**: Prepared statements throughout
- **Input Validation**: Comprehensive sanitization and validation
- **XSS Protection**: Proper output escaping
- **Error Handling**: Graceful error handling with logging

## License

MIT License - see LICENSE file for details.

## Support

For issues related to:
- **HY093 Errors**: Check error logs for parameter count mismatches
- **Placeholder Issues**: Ensure real values are used, not literal placeholders
- **Manual Testing**: Tool auto-creates clicks; check for success messages
- **Legacy URLs**: Both click.php and offer.php?id&tid patterns are supported