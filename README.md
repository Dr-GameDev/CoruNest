# CoruNest - NGO Donation & Volunteer Management Platform

**"Organise. Fund. Mobilise."**

A secure, installable, production-ready donation & volunteer management platform for small NGOs in Cape Town, built with Laravel 11 + Alpine.js for public UX and React.js for admin dashboards.

## Table of Contents

1. [Project Overview](#project-overview)
2. [Business Model](#business-model)
3. [Technology Stack](#technology-stack)
4. [Development Setup](#development-setup)
5. [Architecture](#architecture)
6. [Feature Implementation](#feature-implementation)
7. [Testing](#testing)
8. [Deployment](#deployment)
9. [Maintenance](#maintenance)
10. [Development Timeline](#development-timeline)

---

## Project Overview

### Mission Statement
CoruNest is a comprehensive, secure, and affordable donation and volunteer management platform specifically designed for small to medium NGOs in South Africa, reducing administrative overhead by 60% while increasing donation conversion by 40%.

### Target Market
- **Primary**: Small to medium NGOs (5-100 employees) in Cape Town and South Africa
- **Secondary**: International NGOs operating in Africa
- **Tertiary**: Community organizations, religious groups, schools

### Key Features
- Hybrid architecture (Alpine.js public + React.js admin)
- South African payment integration (Yoco + Ozow)
- Mobile-first PWA with native app capabilities
- POPIA/GDPR compliance ready
- Real-time analytics and reporting

---

## Business Model

### Revenue Streams

**SaaS Subscription Tiers:**
- **Starter**: R299/month - 3 campaigns, 100 donors, 2.9% transaction fee
- **Growth**: R699/month - 10 campaigns, 500 donors, 2.5% transaction fee  
- **Pro**: R1,299/month - Unlimited, advanced analytics, 2.2% transaction fee
- **Enterprise**: Custom pricing with white-label and API access

**Additional Revenue:**
- Setup & migration services: R2,500-R15,000
- Custom development: R850/hour
- Training & support: R650/hour

### 5-Year Financial Projection

| Year | Customers | Annual Revenue | Profit Margin |
|------|-----------|---------------|---------------|
| Y1   | 50        | R372,000      | -410%         |
| Y2   | 200       | R2,080,000    | 15%           |
| Y3   | 500       | R5,200,000    | 30%           |
| Y4   | 1,000     | R10,500,000   | 35%           |
| Y5   | 1,800     | R18,900,000   | 40%           |

**Break-even**: Month 20 (Q4 Year 2)

---

## Technology Stack

### Backend
- **Framework**: PHP 8.2+ with Laravel 11
- **Database**: MySQL 8.0 with Redis 7.x for caching/queues
- **Payments**: Yoco + Ozow integration
- **Email**: Mailgun/SendGrid
- **Search**: Elasticsearch 8.x
- **Monitoring**: Sentry + Laravel Telescope

### Frontend
- **Public Pages**: Laravel Blade + Alpine.js 3.x + Tailwind CSS 3.x
- **Admin Dashboard**: React 18 + Inertia.js + Recharts
- **Mobile**: PWA + Capacitor wrapper for native apps

### Infrastructure
- **Containerization**: Docker + Docker Compose
- **CI/CD**: GitHub Actions
- **Cloud**: AWS (ALB, EC2, RDS, ElastiCache, S3, CloudFront)
- **IaC**: Terraform for infrastructure management

---

## Development Setup

### Prerequisites
- Docker Desktop 4.0+
- Node.js 18+ with npm
- Git 2.30+

### Quick Start

```bash
# Clone and setup
git clone https://github.com/your-org/corunest.git
cd corunest
cp .env.example .env
cp .env.testing.example .env.testing

# Start development environment
docker-compose up -d
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate --seed

# Install frontend dependencies and build
npm install
npm run dev

# Start queue workers
docker-compose exec app php artisan horizon
```

### Environment Configuration

```env
# .env
APP_NAME=CoruNest
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=mysql
DB_DATABASE=corunest
DB_USERNAME=corunest
DB_PASSWORD=password

REDIS_HOST=redis
REDIS_PORT=6379

# Payment Providers
YOCO_SECRET_KEY=your_yoco_secret_key
YOCO_WEBHOOK_SECRET=your_webhook_secret
OZOW_API_KEY=your_ozow_key

# Email
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=your-domain.com
MAILGUN_SECRET=your-secret

# Monitoring
SENTRY_LARAVEL_DSN=your_sentry_dsn
```

---

## Architecture

### System Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Mobile App    │    │   Web Browser   │    │   Admin Panel   │
│   (Capacitor)   │    │  (Alpine.js)    │    │   (React.js)    │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         └───────────────────────┼───────────────────────┘
                                 │
         ┌─────────────────────────────────────────────────────┐
         │              Load Balancer (Nginx)                  │
         └─────────────────────────────────────────────────────┘
                                 │
         ┌─────────────────────────────────────────────────────┐
         │            Laravel Application (PHP-FPM)            │
         └─────────────────────────────────────────────────────┘
                                 │
    ┌────────────┬───────────────┼───────────────┬─────────────┐
    │            │               │               │             │
┌───▼───┐  ┌────▼────┐  ┌───────▼──────┐  ┌────▼────┐  ┌────▼────┐
│ MySQL │  │  Redis  │  │   File       │  │  Queue  │  │ Elastic │
│   DB  │  │ Cache   │  │  Storage     │  │Workers  │  │ Search  │
└───────┘  └─────────┘  └──────────────┘  └─────────┘  └─────────┘
```

### Database Schema

**Core Tables:**
```sql
-- Users with role-based access
users (id, name, email, password, role, phone, profile, timestamps)

-- Organizations/NGOs
organizations (id, name, slug, description, contact_info, settings, status, timestamps)

-- Campaigns
campaigns (id, organization_id, title, slug, description, target_amount, current_amount, 
          status, dates, featured, image_path, metadata, timestamps)

-- Donations
donations (id, user_id, campaign_id, amount, payment_provider, transaction_id, 
          status, donor_info, receipt_data, timestamps)

-- Events & Volunteers
events (id, organization_id, title, description, location, capacity, dates, timestamps)
volunteer_signups (id, user_id, event_id, volunteer_info, status, timestamps)

-- Audit logging
audit_logs (id, user_id, action, model, model_id, changes, ip_address, timestamps)
```

---

## Feature Implementation

### Payment Integration

```php
// app/Services/PaymentServiceInterface.php
interface PaymentServiceInterface
{
    public function initializePayment(Donation $donation): array;
    public function handleWebhook(Request $request): array;
    public function processRefund(Donation $donation, float $amount = null): array;
}

// app/Services/YocoPaymentService.php
class YocoPaymentService implements PaymentServiceInterface
{
    public function initializePayment(Donation $donation): array
    {
        try {
            $response = $this->client->post('checkouts', [
                'json' => [
                    'amount' => intval($donation->amount * 100),
                    'currency' => $donation->currency,
                    'cancelUrl' => route('donations.cancelled', $donation->id),
                    'successUrl' => route('donations.success', $donation->id),
                    'metadata' => [
                        'donation_id' => $donation->id,
                        'campaign_title' => $donation->campaign->title
                    ]
                ]
            ]);
            
            $data = json_decode($response->getBody(), true);
            
            $donation->update([
                'transaction_id' => $data['id'],
                'status' => 'processing'
            ]);
            
            return [
                'success' => true,
                'redirect_url' => $data['redirectUrl']
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Payment initialization failed'];
        }
    }
}
```

### Campaign Management (React Component)

```jsx
// resources/js/Pages/Admin/Campaigns/Index.jsx
import React, { useState } from 'react';
import { useForm, router } from '@inertiajs/react';

export default function CampaignsIndex({ campaigns, filters }) {
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const { data, setData, get } = useForm({
        search: filters.search || '',
        status: filters.status || ''
    });

    const handleSearch = (e) => {
        e.preventDefault();
        get(route('admin.campaigns.index'), { preserveState: true });
    };

    return (
        <div className="p-6">
            {/* Search & Filters */}
            <form onSubmit={handleSearch} className="mb-6">
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <input
                        type="text"
                        value={data.search}
                        onChange={(e) => setData('search', e.target.value)}
                        placeholder="Search campaigns..."
                        className="px-3 py-2 border rounded-md"
                    />
                    <select
                        value={data.status}
                        onChange={(e) => setData('status', e.target.value)}
                        className="px-3 py-2 border rounded-md"
                    >
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                    </select>
                    <button type="submit" className="bg-blue-600 text-white px-4 py-2 rounded-md">
                        Search
                    </button>
                </div>
            </form>

            {/* Campaigns Table */}
            <div className="bg-white rounded-lg shadow overflow-hidden">
                <table className="min-w-full">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left">Campaign</th>
                            <th className="px-6 py-3 text-left">Status</th>
                            <th className="px-6 py-3 text-left">Progress</th>
                            <th className="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {campaigns.data.map((campaign) => (
                            <tr key={campaign.id} className="border-t">
                                <td className="px-6 py-4">
                                    <div className="flex items-center">
                                        <img 
                                            className="h-10 w-10 rounded object-cover" 
                                            src={campaign.image_path || '/default-campaign.jpg'} 
                                            alt={campaign.title} 
                                        />
                                        <div className="ml-4">
                                            <div className="text-sm font-medium">{campaign.title}</div>
                                        </div>
                                    </div>
                                </td>
                                <td className="px-6 py-4">
                                    <span className={`px-2 py-1 text-xs rounded-full ${
                                        campaign.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100'
                                    }`}>
                                        {campaign.status}
                                    </span>
                                </td>
                                <td className="px-6 py-4">
                                    <div className="flex items-center">
                                        <div className="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                            <div 
                                                className="bg-blue-600 h-2 rounded-full" 
                                                style={{ width: `${Math.min(100, (campaign.current_amount / campaign.target_amount) * 100)}%` }}
                                            />
                                        </div>
                                        <span className="text-sm">
                                            R{campaign.current_amount.toLocaleString()}/R{campaign.target_amount.toLocaleString()}
                                        </span>
                                    </div>
                                </td>
                                <td className="px-6 py-4 text-right">
                                    <button className="text-blue-600 hover:text-blue-900 mr-3">
                                        Edit
                                    </button>
                                    <button className="text-red-600 hover:text-red-900">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
```

### PWA Service Worker

```javascript
// public/sw.js
const CACHE_NAME = 'corunest-v1.0.0';
const STATIC_CACHE = `corunest-static-${CACHE_NAME}`;
const DYNAMIC_CACHE = `corunest-dynamic-${CACHE_NAME}`;

const STATIC_ASSETS = [
    '/',
    '/campaigns',
    '/events',
    '/manifest.json',
    '/css/app.css',
    '/js/app.js',
    '/images/logo-192.png',
    '/images/logo-512.png',
    '/offline.html'
];

// Install - cache static assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then(cache => cache.addAll(STATIC_ASSETS))
            .then(() => self.skipWaiting())
    );
});

// Activate - clean old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then(names => Promise.all(
                names
                    .filter(name => name.startsWith('corunest-') && name !== STATIC_CACHE)
                    .map(name => caches.delete(name))
            ))
            .then(() => clients.claim())
    );
});

// Fetch - cache-first for assets, network-first for pages
self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') return;
    if (!event.request.url.startsWith(self.location.origin)) return;

    if (isAssetRequest(event.request)) {
        event.respondWith(cacheFirst(event.request));
    } else {
        event.respondWith(networkFirst(event.request));
    }
});

function isAssetRequest(request) {
    return /\.(css|js|png|jpg|jpeg|gif|svg|woff2?)$/i.test(request.url);
}

async function cacheFirst(request) {
    const cached = await caches.match(request);
    return cached || fetch(request);
}

async function networkFirst(request) {
    try {
        const response = await fetch(request);
        const cache = await caches.open(DYNAMIC_CACHE);
        cache.put(request, response.clone());
        return response;
    } catch (error) {
        const cached = await caches.match(request);
        return cached || caches.match('/offline.html');
    }
}

// Push notifications
self.addEventListener('push', (event) => {
    if (!event.data) return;
    
    const data = event.data.json();
    const options = {
        body: data.body,
        icon: '/images/logo-192.png',
        badge: '/images/badge-72.png',
        data: { url: data.url }
    };
    
    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// Background sync for offline donations
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-donations') {
        event.waitUntil(syncOfflineDonations());
    }
});

async function syncOfflineDonations() {
    // Implementation for syncing offline donation data
    console.log('Syncing offline donations...');
}
```

---

## Testing

### Test Structure
- **Unit Tests**: PHPUnit/Pest for backend (target: 80%+ coverage)
- **Integration Tests**: API endpoints and payment flows
- **Frontend Tests**: React Testing Library + Jest
- **E2E Tests**: Playwright for critical user journeys

### Running Tests

```bash
# Backend tests
php artisan test --coverage

# Frontend tests  
npm run test

# E2E tests
npx playwright test

# All tests with coverage
npm run test:all
```

### Example Test

```php
// tests/Feature/DonationFlowTest.php
class DonationFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_complete_donation_flow()
    {
        $campaign = Campaign::factory()->create(['target_amount' => 10000]);
        
        $response = $this->post('/donate', [
            'campaign_id' => $campaign->id,
            'amount' => 100,
            'donor_name' => 'John Doe',
            'donor_email' => 'john@example.com',
            'payment_method' => 'yoco'
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('donations', [
            'campaign_id' => $campaign->id,
            'amount' => 100,
            'status' => 'pending'
        ]);
    }
}
```

---

## Deployment

### Docker Production Setup

```dockerfile
# Dockerfile
FROM php:8.2-fpm-alpine

RUN apk add --no-cache nginx supervisor curl mysql-client nodejs npm

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql bcmath opcache

# Copy application
WORKDIR /var/www/html
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader
RUN npm ci && npm run build

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

EXPOSE 80
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf"]
```

### AWS Infrastructure (Terraform)

```hcl
# infrastructure/main.tf
resource "aws_lb" "main" {
  name               = "corunest-alb"
  internal           = false
  load_balancer_type = "application"
  security_groups    = [aws_security_group.alb.id]
  subnets            = aws_subnet.public[*].id
}

resource "aws_db_instance" "main" {
  identifier = "corunest-db"
  engine     = "mysql"
  engine_version = "8.0"
  instance_class = "db.t3.medium"
  
  allocated_storage = 100
  storage_encrypted = true
  
  db_name  = var.db_name
  username = var.db_username
  password = var.db_password
  
  backup_retention_period = 7
  skip_final_snapshot = false
}
```

### CI/CD Pipeline

```yaml
# .github/workflows/deploy.yml
name: Deploy to Production

on:
  push:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
      - run: composer install
      - run: php artisan test --coverage

  deploy:
    needs: test
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Deploy to AWS
        run: |
          # Build and push Docker image
          docker build -t corunest:${{ github.sha }} .
          docker push $ECR_REGISTRY/corunest:${{ github.sha }}
          
          # Update ECS service
          aws ecs update-service \
            --cluster corunest-cluster \
            --service corunest-service \
            --force-new-deployment
```

---

## Maintenance

### Health Monitoring

```php
// app/Http/Controllers/HealthController.php
class HealthController extends Controller
{
    public function check()
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue()
        ];

        $allHealthy = collect($checks)->every(fn($check) => $check['status'] === 'ok');

        return response()->json([
            'status' => $allHealthy ? 'healthy' : 'unhealthy',
            'checks' => $checks,
            'timestamp' => now()
        ], $allHealthy ? 200 : 503);
    }

    private function checkDatabase(): array
    {
        try {
            DB::select('SELECT 1');
            return ['status' => 'ok'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
```

### Performance Commands

```bash
# Database optimization
php artisan db:optimize --analyze

# Performance analysis  
php artisan performance:analyze --days=7

# Clear caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Development Timeline

### Phase 1: Foundation (Weeks 1-4)
- **Week 1**: Project setup, Docker environment, database design
- **Week 2**: Core models, API endpoints, payment service interfaces
- **Week 3**: Public frontend (Alpine.js + Blade), payment integration
- **Week 4**: Admin dashboard foundation (React.js), basic analytics

### Phase 2: Advanced Features (Weeks 5-8)  
- **Week 5**: Event management, volunteer system, email marketing
- **Week 6**: Payment processing, refunds, tax receipts, financial reporting
- **Week 7**: PWA implementation, offline functionality, push notifications
- **Week 8**: Performance optimization, caching, monitoring setup

### Phase 3: Production Ready (Weeks 9-12)
- **Week 9**: Comprehensive testing, security hardening
- **Week 10**: POPIA/GDPR compliance, penetration testing
- **Week 11**: Production deployment, infrastructure setup
- **Week 12**: Go-live, documentation, support processes

**Total Development Time**: 12 weeks
**Team Size**: 1 developers, 1 designer, 1 project manager
**Budget**: ~R1.4M for development phase

---

## Key Success Metrics

### Technical KPIs
- **Uptime**: >99.9%
- **Response Time**: <300ms average
- **Test Coverage**: >80%
- **Security**: Zero critical vulnerabilities

### Business KPIs  
- **Customers**: 50 NGOs by Year 1 end
- **Revenue**: R372K in Year 1, break-even by Month 20
- **Churn Rate**: <5% annually
- **User Satisfaction**: >8.5/10

### Impact Metrics
- **Donations Processed**: R5M+ by Year 2
- **Administrative Time Saved**: 60% reduction for NGOs
- **Donor Conversion**: 40% improvement vs traditional methods

---

**CoruNest: Building the future of NGO management in South Africa, one donation at a time.**
