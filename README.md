# CoruNest - NGO Donation & Volunteer Portal

> **Organise. Fund. Mobilise.**

CoruNest is a comprehensive donation and volunteer management platform designed specifically for small NGOs in Cape Town. It provides a secure, transparent, and efficient way to manage fundraising campaigns, coordinate volunteer activities, and engage with donors and supporters.

## 🌟 Features

### Public Features
- **Campaign Browsing**: Discover active fundraising campaigns with detailed information
- **Secure Donations**: Support campaigns via Yoco (cards) or Ozow (bank transfers)
- **Volunteer Opportunities**: Find and sign up for volunteer events
- **Donor Dashboard**: Track donation history and download receipts
- **Mobile PWA**: Install as a mobile app for quick access

### Admin Features
- **Campaign Management**: Create, edit, and manage fundraising campaigns
- **Donation Tracking**: Monitor donations, process refunds, and generate reports
- **Volunteer Management**: Coordinate volunteer signups and track participation
- **Analytics Dashboard**: Comprehensive insights with charts and metrics
- **Bulk Email System**: Communicate with donors and volunteers
- **Audit Logging**: Complete activity tracking for transparency

### Technical Features
- **Hybrid Frontend**: Laravel Blade + Alpine.js for public pages, React.js for admin
- **Payment Integration**: Yoco and Ozow payment gateways
- **PWA Support**: Offline functionality and mobile app installation
- **Role-based Access**: Admin, staff, donor, and volunteer roles
- **Email Automation**: Automated receipts and notifications
- **Database Optimization**: Indexed queries and efficient relationships

## 🛠 Tech Stack

- **Backend**: PHP 8.2+ with Laravel 11
- **Database**: MySQL 8.x
- **Cache/Queue**: Redis + Laravel Horizon
- **Frontend**: 
  - Public: Laravel Blade + Alpine.js + Tailwind CSS
  - Admin: React.js + Inertia.js + Tailwind CSS
- **Payments**: Yoco + Ozow
- **Email**: Mailgun/SendGrid
- **Mobile**: PWA + Capacitor
- **DevOps**: Docker + Docker Compose

## 🚀 Quick Start

### Prerequisites
- Docker and Docker Compose
- Git
- Node.js 20+ (for building assets)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/your-org/corunest.git
   cd corunest
   ```

2. **Set up environment**
   ```bash
   cp .env.example .env
   # Edit .env with your configuration
   ```

3. **Start with Docker**
   ```bash
   make setup
   ```
   This command will:
   - Build Docker containers
   - Install PHP dependencies
   - Install Node.js dependencies
   - Generate application key
   - Run database migrations and seeders
   - Start all services

4. **Access the application**
   - **Main App**: http://localhost:8088
   - **Admin Panel**: http://localhost:8088/admin
   - **phpMyAdmin**: http://localhost:8080
   - **Mailpit**: http://localhost:8025

### Default Login Credentials

After seeding, you can use these accounts:

- **Admin**: admin@corunest.org / password
- **Staff**: staff@corunest.org / password  
- **Donor**: donor1@example.com / password
- **Volunteer**: volunteer1@example.com / password

## 📖 Usage

### Making Donations
1. Browse campaigns at `/campaigns`
2. Click "Donate Now" on any campaign
3. Enter donation amount and payment details
4. Complete payment via Yoco or Ozow
5. Receive email receipt automatically

### Volunteering
1. View events at `/events`
2. Click "Volunteer" on an event
3. Fill out volunteer form with skills and availability
4. Wait for confirmation from event organizers
5. Receive reminders before the event

### Admin Management
1. Access admin panel at `/admin`
2. Create campaigns with rich content and images
3. Monitor donations and volunteer signups
4. Send bulk emails to donors/volunteers
5. View analytics and generate reports

## 🏗 Development

### Available Make Commands

```bash
# Development
make up           # Start all services
make down         # Stop all services  
make restart      # Restart all services
make logs         # View application logs
make shell        # Access application container

# Database
make migrate      # Run migrations
make fresh        # Fresh migration with seeders
make db-backup    # Backup database

# Cache & Optimization
make clear-cache  # Clear all caches
make optimize     # Optimize for production

# Testing
make test         # Run test suite

# Cleanup
make clean        # Remove containers and volumes
```

### Local Development Setup

If you prefer running without Docker:

1. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

2. **Set up database**
   ```bash
   php artisan key:generate
   php artisan migrate --seed
   ```

3. **Build assets**
   ```bash
   npm run dev
   ```

4. **Start services**
   ```bash
   php artisan serve
   npm run dev # In separate terminal
   ```

### File Structure

```
corunest/
├── app/
│   ├── Http/Controllers/     # Application controllers
│   ├── Models/              # Eloquent models
│   ├── Services/            # Business logic services
│   └── ...
├── database/
│   ├── migrations/          # Database migrations
│   ├── seeders/            # Data seeders
│   └── factories/          # Model factories
├── resources/
│   ├── views/              # Blade templates (public pages)
│   ├── js/                 # React components (admin)
│   └── css/                # Stylesheets
├── public/
│   ├── manifest.json       # PWA manifest
│   ├── sw.js              # Service worker
│   └── icons/             # PWA icons
├── docker/                # Docker configuration
├── tests/                 # Test files
└── ...
```

## 🔐 Security

CoruNest implements multiple security measures:

- **Authentication**: Laravel Breeze with session-based auth
- **Authorization**: Role-based access control (RBAC)
- **Payment Security**: PCI-compliant payment processing
- **Data Protection**: POPIA-compliant data handling
- **CSRF Protection**: Built-in Laravel CSRF protection
- **Rate Limiting**: API and form submission rate limiting
- **Audit Logging**: Complete activity tracking
- **Secure Headers**: Security headers for XSS/CSRF protection

## 💳 Payment Configuration

### Yoco Setup
1. Create account at https://www.yoco.com
2. Get API keys from dashboard
3. Add to `.env`:
   ```bash
   YOCO_SECRET_KEY=sk_test_your_key
   YOCO_PUBLIC_KEY=pk_test_your_key
   ```

### Ozow Setup  
1. Contact Ozow for merchant account
2. Get integration credentials
3. Add to `.env`:
   ```bash
   OZOW_SITE_CODE=your_site_code
   OZOW_PRIVATE_KEY=your_private_key
   ```

## 📱 PWA Installation

CoruNest is a Progressive Web App that can be installed on mobile devices:

1. **Mobile Installation**:
   - Visit site on mobile browser
   - Tap "Add to Home Screen" prompt
   - App installs like native app

2. **Desktop Installation**:
   - Visit site in Chrome/Edge
   - Click install icon in address bar
   - App installs as desktop application

## 🧪 Testing

CoruNest includes comprehensive tests:

```bash
# Run all tests
make test

# Run specific test types
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage
php artisan test --coverage
```

### Test Coverage
- **Unit Tests**: Model logic, services, utilities
- **Feature Tests**: HTTP endpoints, workflows
- **Browser Tests**: End-to-end user journeys (Playwright)

## 📊 Monitoring & Analytics

### Built-in Analytics
- Donation trends and metrics
- Campaign performance tracking  
- Volunteer participation rates
- User engagement analytics

### External Monitoring
- **Sentry**: Error tracking and performance monitoring
- **Laravel Telescope**: Local development debugging
- **Google Analytics**: Website traffic analysis

## 🚢 Deployment

### Production Deployment

1. **Server Requirements**:
   - Ubuntu 20.04+ or similar
   - Docker & Docker Compose
   - SSL certificate
   - Domain name

2. **Deploy with Docker**:
   ```bash
   # Set production environment
   cp .env.example .env.production
   # Configure production settings

   # Deploy
   docker-compose -f docker-compose.prod.yml up -d
   ```

3. **Configure SSL**:
   ```bash
   # Using Let's Encrypt with Nginx
   certbot --nginx -d your-domain.com
   ```

### Environment-Specific Configuration

#### Staging
```bash
APP_ENV=staging
APP_DEBUG=false
YOCO_MODE=test
OZOW_IS_TEST=true
```

#### Production
```bash
APP_ENV=production  
APP_DEBUG=false
YOCO_MODE=live
OZOW_IS_TEST=false
QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
```

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

### Development Workflow
1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Make changes and add tests
4. Run test suite (`make test`)
5. Commit changes (`git commit -m 'Add amazing feature'`)
6. Push to branch (`git push origin feature/amazing-feature`)
7. Open Pull Request

### Code Standards
- Follow PSR-12 coding standards
- Write comprehensive tests for new features
- Update documentation for API changes
- Use conventional commit messages

## 📝 Documentation

- [API Documentation](docs/api.md)
- [Admin Guide](docs/admin-guide.md)
- [Deployment Guide](docs/deployment.md)
- [Developer Guide](docs/developer-guide.md)

## 🔄 Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

## 📄 License

CoruNest is open-source software licensed under the [MIT License](LICENSE).

## 🆘 Support

- **Documentation**: Check the `/docs` folder
- **Issues**: Create an issue on GitHub
- **Email**: support@corunest.org
- **Community**: Join our Discord server

## 🙏 Acknowledgments

- Built with [Laravel](https://laravel.com)
- UI powered by [Tailwind CSS](https://tailwindcss.com)
- Icons from [Heroicons](https://heroicons.com)
- Payment processing by [Yoco](https://yoco.com) and [Ozow](https://ozow.com)
- Inspired by the amazing NGO community in Cape Town

---

**CoruNest** - *Empowering NGOs to create meaningful change through technology.*