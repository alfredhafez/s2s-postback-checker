# S2S Postback Checker

A comprehensive Server-to-Server (S2S) Postback Testing Tool built in PHP for affiliate marketing and lead generation tracking. This tool provides a complete solution for testing, monitoring, and managing postback integrations with full analytics dashboard.

![S2S Postback Checker](https://img.shields.io/badge/PHP-8.0%2B-blue) ![Database](https://img.shields.io/badge/Database-MySQL-orange) ![License](https://img.shields.io/badge/License-MIT-green)

## 🚀 Features

### Core Functionality
- **🎯 Offer Management**: Create and manage offers with custom postback templates
- **📊 Analytics Dashboard**: Real-time charts and KPIs using Chart.js
- **🔗 Click Tracking**: Advanced click tracking with sub-parameters (sub1-sub5)
- **💰 Conversion Tracking**: Lead submission forms with automatic postback firing
- **🧪 Manual Testing Tool**: Test postback URLs manually with detailed response logging
- **⚙️ Global Settings**: Configurable postback templates with token replacement

### Advanced Features
- **🛡️ CSRF Protection**: Secure forms with CSRF token validation
- **📋 Copy Click URLs**: One-click copy functionality for tracking URLs
- **📈 Recent Activity**: Display recent clicks and conversions
- **🎨 Modern UI**: Responsive dark mode with glass effect design
- **🔄 Token Replacement**: Comprehensive token system for dynamic URLs
- **📝 Detailed Logging**: Complete postback response logging and error tracking

### Pre-built Components
- **🏗️ Web-based Installer**: Easy setup with database configuration
- **📦 Sample Data**: Pre-built offers for immediate testing
- **🎯 Offer Templates**: Ready-to-use offer configurations
- **📊 Response Analytics**: Success rates and performance metrics

## 📋 Requirements

- **PHP**: 8.0 or higher
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Web Server**: Apache, Nginx, or similar
- **Extensions**: PDO, PDO_MySQL

## 🛠️ Installation

### Quick Start

1. **Clone or download** the repository to your web server directory
2. **Navigate** to your domain: `http://yourdomain.com/install/install.php`
3. **Follow the installer** steps:
   - Configure database connection
   - Create database tables
   - Set up initial settings
4. **Complete setup** and start tracking!

### Manual Installation

1. **Database Setup**:
   ```sql
   CREATE DATABASE s2s_postback CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **Import Schema**:
   ```bash
   mysql -u username -p s2s_postback < install/schema.sql
   ```

3. **Configuration**:
   Create `includes/db_config.php`:
   ```php
   <?php
   $config = [
       'db_host' => 'localhost',
       'db_name' => 's2s_postback',
       'db_user' => 'your_username',
       'db_pass' => 'your_password',
       'installed' => true
   ];
   ?>
   ```

## 📖 Usage Guide

### Creating Offers

1. Navigate to **Offers** page
2. Click **"Add New Offer"**
3. Fill in offer details:
   - **Name**: Offer display name
   - **Description**: Brief description
   - **Goal Name**: Conversion goal (e.g., "lead", "signup")
   - **Postback Template**: Custom template (optional)

### Click Tracking URLs

Generated click URLs follow this format:
```
https://yourdomain.com/click.php?offer=1&sub1={transaction_id}&sub2=optional&sub3=optional
```

**Parameters:**
- `offer`: Required offer ID
- `sub1-sub5`: Optional tracking parameters

### Postback Templates

Use tokens in your postback templates for dynamic replacement:

```
https://partner.com/postback?tid={transaction_id}&goal={goal}&name={name}&email={email}&offer={offer_id}
```

**Available Tokens:**
- `{transaction_id}` - Unique transaction identifier
- `{goal}` - Conversion goal name
- `{name}` - User's name (URL encoded)
- `{email}` - User's email (URL encoded)
- `{phone}` - User's phone number (URL encoded)
- `{offer_id}` - Offer ID
- `{offer_name}` - Offer name (URL encoded)
- `{payout}` - Payout amount
- `{revenue}` - Revenue amount
- `{sub1-sub5}` - Sub tracking parameters (URL encoded)
- `{ip}` - User's IP address
- `{timestamp}` - Unix timestamp
- `{date}` - Date (Y-m-d format)
- `{datetime}` - Full datetime (Y-m-d H:i:s)

### Testing Postbacks

1. Go to **Postback Test** page
2. Enter your complete postback URL
3. Click **"Test Postback"**
4. Review response details and status

## 🗄️ Database Schema

The system uses 6 main tables:

- **`offers`**: Store offer configurations
- **`clicks`**: Track click events with parameters
- **`conversions`**: Record lead submissions
- **`postback_logs`**: Log all postback responses
- **`manual_tests`**: Store manual test results
- **`settings`**: Global configuration options

## 🔧 Configuration

### Global Settings

Access via **Settings** page:

- **Site Name**: Display name for the application
- **Timezone**: System timezone for reporting
- **Postback Template**: Default template for all offers

### Offer-Level Configuration

Each offer can have:
- Custom postback template (overrides global)
- Specific goal name
- Active/inactive status

## 🎨 UI Design

The interface features:
- **Dark Theme**: Professional dark color scheme
- **Glass Effect**: Modern glassmorphism design
- **Responsive Layout**: Works on desktop and mobile
- **Chart.js Integration**: Interactive analytics charts
- **Copy Functionality**: Easy URL copying
- **Real-time Updates**: Live data display

## 🔍 Analytics & Reporting

### Dashboard Metrics
- Total clicks and conversions
- Conversion rate calculation
- Active offers count
- 7-day activity chart

### Activity Monitoring
- Recent clicks table
- Recent conversions table
- Postback success rates
- Response time tracking

### Detailed Logging
- HTTP status codes
- Response bodies
- Error messages
- Response times

## 🛡️ Security Features

- **CSRF Protection**: All forms protected with CSRF tokens
- **Input Validation**: Comprehensive input sanitization
- **SQL Injection Protection**: Prepared statements throughout
- **XSS Prevention**: Output escaping and validation

## 🔄 API Integration

### Click Tracking Endpoint
```
GET /click.php?offer={offer_id}&sub1={transaction_id}[&sub2-5=optional]
```

### Manual Postback Testing
The system can test any postback URL and provides detailed response analysis.

## 🚀 Advanced Usage

### Custom Token Implementation

Add custom tokens by modifying `includes/postback.php`:

```php
$tokens['{custom_token}'] = $custom_value;
```

### Webhook Integration

The system can be extended to receive webhooks for external conversion tracking.

### Multi-Offer Campaigns

Use sub-parameters to track:
- Traffic sources (`sub1`)
- Campaign IDs (`sub2`)
- Creative variations (`sub3`)
- Landing page versions (`sub4`)
- Custom tracking (`sub5`)

## 📁 File Structure

```
s2s-postback-checker/
├── css/
│   └── style.css              # Main stylesheet with glass effect
├── includes/
│   ├── config.php             # Database configuration
│   ├── header.php             # Navigation header
│   └── postback.php           # Postback firing system
├── install/
│   ├── install.php            # Web-based installer
│   └── schema.sql             # Database schema
├── index.php                  # Dashboard with analytics
├── offers.php                 # Offer management interface
├── click.php                  # Click tracking endpoint
├── offer.php                  # Lead submission forms
├── postback-test.php          # Manual testing tool
├── settings.php               # Configuration interface
└── README.md                  # This documentation
```

## 🤝 Contributing

Contributions are welcome! Areas for enhancement:

- Additional chart types and analytics
- Advanced filtering and date ranges
- Export functionality for reports
- Additional token types
- Webhook receivers
- Multi-user support
- API documentation
- Performance optimizations

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support, issues, or feature requests:

1. Check existing documentation
2. Review the installer for setup issues
3. Check database connectivity
4. Verify file permissions
5. Review error logs

## 🎯 Use Cases

Perfect for:
- **Affiliate Networks**: Track partner conversions
- **Lead Generation**: Monitor form submissions
- **Performance Marketing**: Analyze campaign effectiveness
- **Conversion Testing**: Validate tracking implementations
- **Development Teams**: Test integration before production

---

Built with ❤️ for the affiliate marketing community. Happy tracking! 🚀
