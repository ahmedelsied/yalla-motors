# YallaMotor - Car Marketplace API

A Laravel-based car marketplace API with advanced filtering, lead management, and caching capabilities.

## Project Structure

```
YallaMotor/
├── app/
│   ├── Enums/                    # Application enums
│   │   ├── CarStatus.php         # Car status definitions
│   │   └── LeadStatus.php        # Lead status definitions
│   ├── Filters/                  # Query filtering system
│   │   ├── CarFilter.php         # Car-specific filters
│   │   └── QueryFilter.php       # Base filter class
│   ├── Http/
│   │   ├── Controllers/Api/      # API controllers
│   │   │   ├── BaseApiController.php
│   │   │   ├── CarController.php
│   │   │   ├── LeadController.php
│   │   │   └── CacheController.php
│   │   ├── Middleware/           # Custom middleware
│   │   │   ├── APIKeyMiddleware.php
│   │   │   └── LeadRateLimitMiddleware.php
│   │   ├── Requests/Api/         # Form request validation
│   │   │   ├── CreateLeadRequest.php
│   │   │   └── ListCarRequest.php
│   │   └── Resources/            # API resource transformers
│   │       ├── CarResource.php
│   │       ├── CarCollection.php
│   │       ├── LeadResource.php
│   │       └── DealerResource.php
│   ├── Jobs/                     # Background jobs
│   │   └── LeadScoringJob.php    # Asynchronous lead scoring
│   ├── Models/                   # Eloquent models
│   │   ├── Car.php
│   │   ├── Dealer.php
│   │   └── Lead.php
│   ├── Queries/                  # Query objects
│   │   └── ListCarsQuery.php     # Car listing query logic
│   ├── Services/                 # Business logic services
│   │   ├── CarService.php        # Car-related business logic
│   │   └── LeadService.php       # Lead-related business logic
│   ├── Traits/                   # Reusable traits
│   │   └── Filterable.php        # Model filtering trait
│   └── Utils/                    # Utility functions
│       ├── helpers.php           # Global helper functions
│       └── ModelFilters.php      # Model filtering utilities
├── config/                       # Configuration files
├── database/
│   ├── factories/                # Model factories for testing
│   ├── migrations/               # Database migrations
│   └── seeders/                  # Database seeders
├── docs/                         # Project documentation
│   └── CACHE_STAMPEDE_PREVENTION.md
├── public/                       # Web server document root
├── resources/
│   ├── css/                      # Stylesheets
│   ├── js/                       # JavaScript files
│   └── views/                    # Blade templates
├── routes/
│   ├── api.php                   # API routes
│   ├── web.php                   # Web routes
│   └── console.php               # Console routes
├── storage/                      # File storage
├── tests/                        # Test suites
│   ├── Feature/                  # Feature tests
│   │   ├── CarsEndpointTest.php
│   │   ├── LeadsEndpointTest.php
│   │   ├── CacheInvalidationTest.php
│   │   └── LeadCreationTest.php
│   └── Unit/                     # Unit tests
│       └── LeadScoringJobTest.php
└── vendor/                       # Composer dependencies
```

## Prerequisites

- **PHP**: 8.2 or higher
- **Composer**: Latest version
- **MySQL**: 5.7 or higher (or MariaDB 10.2+)
- **Laravel Valet** (macOS) or **Laragon** (Windows)

## 🛠️ Installation & Setup

### 1. Clone the Repository
```bash
git clone https://github.com/ahmedelsied/yalla-motors.git
cd YallaMotor
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Environment Configuration
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Database Setup
```bash
# Create database
mysql -u root -p -e "CREATE DATABASE yalla_motor;"

# Update .env with database credentials
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=yalla_motor
DB_USERNAME=root
DB_PASSWORD=your_password

# Run migrations
php artisan migrate

# Seed sample data (optional)
php artisan db:seed
```

### 5. Testing Database Setup
```bash
# Create testing database
mysql -u root -p -e "CREATE DATABASE yalla_motor_testing;"

# Run migrations for testing
php artisan migrate --database=testing
```

### 6. Cache Configuration (Optional)
For production with Redis:
```bash
# Update .env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 7. Queue Configuration
```bash
# For development (sync)
QUEUE_CONNECTION=sync

# For production (database or Redis)
QUEUE_CONNECTION=database
# or
QUEUE_CONNECTION=redis
```

## Starting the Project

### Development Server
```bash
# Start Laravel development server
php artisan serve

# In another terminal, start queue worker (if using async queues)
php artisan queue:work

```

### Production Deployment
```bash
# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start queue workers
php artisan queue:work --daemon
```

## Running Tests

```bash
# Run all tests
php artisan test

```

## 📚 API Documentation

### Base URL
```
http://localhost:8000/api/v1
```

### Authentication
- API Key middleware for admin endpoints
- Rate limiting for lead submissions

### Endpoints

#### Cars
- `GET /cars` - List cars with filtering and pagination
- `GET /cars/{id}` - Get specific car details

#### Leads
- `POST /leads` - Create new lead (rate limited)

#### Admin
- `POST /admin/cache/purge` - Purge application cache

## Configuration

### Environment Variables
```env
# Application
APP_NAME=YallaMotor
APP_ENV=local
APP_DEBUG=true

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=yalla_motor

# Cache
CACHE_DRIVER=array  # or redis for production

# Queue
QUEUE_CONNECTION=sync  # or database/redis for production

# Testing
DB_TESTING_DATABASE=yalla_motor_testing
```

## Features

- **Advanced Car Filtering**: Make, model, year, price, mileage, location
- **Pagination**: Configurable page sizes with metadata
- **Faceted Search**: Dynamic facets for make and year
- **Lead Management**: Lead creation with validation and scoring
- **Rate Limiting**: 5 leads per hour per IP/email
- **Caching**: Intelligent cache invalidation
- **Background Jobs**: Asynchronous lead scoring
- **Comprehensive Testing**: 46+ test methods covering all functionality

## Architecture

This project follows a layered architecture pattern:

- **Controllers**: Handle HTTP requests and responses
- **Services**: Contain business logic and orchestration
- **Queries**: Encapsulate complex database queries
- **Filters**: Reusable filtering logic
- **Jobs**: Background processing
- **Resources**: API response formatting
- **Models**: Data access and relationships