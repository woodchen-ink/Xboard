# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Xboard is a Laravel-based proxy protocol management panel built on top of V2board with significant performance enhancements. It's designed to manage VPN/proxy services including V2Ray, Shadowsocks, Trojan, Hysteria2, and VLESS protocols. The application supports both traditional PHP-FPM and high-performance runtime options like Webman.

## Development Commands

### Core Commands
```bash
# Run Artisan commands
php artisan <command>

# Install dependencies
composer install

# Run database migrations
php artisan migrate

# Generate application key
php artisan key:generate

# Run tests
./vendor/bin/phpunit

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Custom Xboard Commands
```bash
# Install Xboard
php artisan xboard:install

# Update Xboard
php artisan xboard:update

# Rollback to previous version
php artisan xboard:rollback

# Generate statistics
php artisan xboard:statistics

# Reset user traffic
php artisan reset:traffic

# Backup database
php artisan backup:database

# Check orders, commissions, tickets
php artisan check:order
php artisan check:commission
php artisan check:ticket
```

### Docker Commands
```bash
# Start services
docker compose up -d

# Restart services (required after admin path changes)
docker compose restart

# View logs
docker compose logs -f

# Execute commands in container
docker compose exec xboard php artisan <command>
```

### Queue Management
```bash
# Start Horizon (queue worker)
php artisan horizon

# Publish Horizon assets
php artisan horizon:publish
```

## Architecture Overview

### Core Components

**Application Layer (`app/`)**
- **Controllers**: RESTful API controllers organized by version (V1, V2) and user type (Admin, User, Guest, Staff)
- **Services**: Business logic layer handling core operations (AuthService, OrderService, PaymentService, etc.)
- **Models**: Eloquent models representing database entities (User, Plan, Server*, Order, etc.)
- **Jobs**: Asynchronous task handlers for email, Telegram notifications, and traffic processing
- **Middleware**: Request filtering and authentication layers

**Protocol Support (`app/Protocols/`)**
- Multi-protocol client configuration generators (Clash, V2rayN, Shadowrocket, SingBox, etc.)
- Automatic protocol distribution based on client detection

**Server Types (`app/Models/Server*`)**
- ServerShadowsocks, ServerTrojan, ServerVmess, ServerVless, ServerHysteria
- Unified server management with protocol-specific configurations

**Payment Integration (`app/Payments/`)**
- Multiple payment gateway support (Alipay, Stripe, Coinbase, etc.)
- Extensible payment provider system

### Database Design

The application uses Laravel migrations with a `v2_*` prefix for all tables. Key entities:
- Users with subscription plans and traffic tracking
- Multiple server types with protocol-specific configurations
- Order processing and commission tracking
- Ticket system for customer support
- Statistics and logging tables

### Performance Enhancements

- **Webman Integration**: ~50% performance improvement over traditional PHP-FPM
- **LaravelS Support**: 10+ times concurrent request improvement
- **Database Optimization**: Configuration retrieval optimized for high performance
- **Distributed Architecture**: Docker-based deployment with Redis caching

### Frontend Architecture

- **Admin Panel**: Vue.js-based administrative interface (`public/assets/admin/`)
- **User Frontend**: Vue3 + TypeScript + NaiveUI + Unocss + Pinia (`public/theme/`)
- **Multi-theme Support**: Configurable themes in `public/theme/` directories

## Development Guidelines

### Code Organization
- Follow Laravel conventions and directory structure
- Controllers are organized by API version and user type
- Services contain business logic, Models handle data access
- Use proper namespacing: `App\` for application code, `Library\` for external libraries

### Database Operations
- Use Eloquent models for database interactions
- All custom tables use `v2_` prefix
- Migrations are timestamped and located in `database/migrations/`
- Use database seeders for initial data setup

### API Design
- RESTful API structure with proper HTTP methods
- API versioning through URL paths (`/api/v1/`, `/api/v2/`)
- JSON responses using consistent format via ApiResponse helper
- Proper error handling with custom exceptions

### Security Considerations
- Middleware-based authentication and authorization
- CSRF protection enabled
- Input validation through Form Request classes
- Environment-based configuration management

### Testing
- PHPUnit configuration in `phpunit.xml`
- Test files in `tests/` directory (Feature and Unit tests)
- Use `php artisan test` or `./vendor/bin/phpunit` to run tests

### Deployment Options
1. **Docker Compose** (Recommended): Use `docker-compose.sample.yaml` as template
2. **Traditional LAMP**: Standard Laravel deployment with PHP-FPM
3. **Webman**: High-performance runtime option for production

## Configuration Management

### Environment Variables
- Copy `.env.example` to `.env` and configure
- Database, Redis, and application settings
- Payment gateway API keys and settings
- Queue and cache driver configuration

### Theme Configuration
- Themes located in `public/theme/`
- Configuration files: `config.json` in each theme directory
- Blade templates for server-side rendering

### Server Rules and Routing
- Protocol-specific rules in `resources/rules/`
- Clash, Sing-box, Surfboard, and Surge configurations
- Customizable routing rules for different clients

## Migration and Updates

The application includes comprehensive migration tools for upgrading from V2board and other panel systems. Migration commands handle database schema updates and data transformation automatically.

## Scheduled Tasks

Cron jobs are defined in `app/Console/Kernel.php`:
- Daily statistics generation
- Order processing and commission calculations
- Traffic reset and log cleanup  
- Email reminders and notifications
- Database backups (if enabled)