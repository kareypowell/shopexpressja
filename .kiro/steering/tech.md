# Technology Stack

## Backend Framework
- **Laravel 8.x**: PHP framework with Eloquent ORM
- **PHP 7.4**: Server-side language (always design code for PHP 7 compatibility)
- **MySQL/MariaDB**: Primary database with comprehensive indexing strategy

## Frontend Stack
- **Livewire 2.x**: Full-stack framework for dynamic interfaces without JavaScript complexity
- **Alpine.js 3.x**: Lightweight JavaScript framework for interactive components
- **Tailwind CSS 2.x**: Utility-first CSS framework with custom color palette
- **Blade Templates**: Laravel's templating engine

## Build System
- **Laravel Mix**: Asset compilation and bundling
- **Webpack**: Module bundler (via Laravel Mix)
- **Sass**: CSS preprocessing
- **NPM**: Package management for frontend dependencies

## Key Libraries & Packages
- **Laravel Sanctum**: API authentication
- **Laravel Livewire Tables**: Advanced data tables with sorting/filtering
- **DomPDF**: PDF generation for receipts and reports
- **MailerSend**: Email service integration
- **Doctrine DBAL**: Database abstraction layer for migrations

## Development Tools
- **PHPUnit**: Testing framework with Feature and Unit tests
- **Laravel Dusk**: Browser testing (some tests use Browser namespace)
- **Faker**: Test data generation
- **Laravel Tinker**: REPL for debugging

## Common Commands

### Development
```bash
# Install dependencies
composer install
npm install

# Build assets
npm run dev          # Development build
npm run watch        # Watch for changes
npm run prod         # Production build

# Database
php artisan migrate
php artisan db:seed
php artisan migrate:fresh --seed

# Cache management
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

### Testing
```bash
# Run all tests
php artisan test

# Run specific test types
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage
php artisan test --coverage
```

### Performance & Optimization
```bash
# Query optimization tool
php artisan customer:optimize-queries

# Cache optimization
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Queue processing
php artisan queue:work
```

## Performance Features
- **Multi-level caching**: Customer statistics, financial summaries, package metrics
- **Database optimization**: Strategic indexes for complex queries
- **Query optimization service**: Bulk operations and eager loading
- **Automatic cache invalidation**: Model observers for data consistency