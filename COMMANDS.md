# Useful Commands - Hexagonal Demo

Quick reference for all commands related to the project.

---

## 📦 Installation

```bash
# Install dependencies
composer install

# Install JWT library
composer require firebase/php-jwt

# Generate APP_SECRET if needed
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
```

---

## 🗄️ Database

```bash
# Create database
php bin/console doctrine:database:create

# Generate migration
php bin/console doctrine:migrations:diff

# Run migrations
php bin/console doctrine:migrations:migrate

# Validate schema
php bin/console doctrine:schema:validate

# Drop database (⚠️ DANGER)
php bin/console doctrine:database:drop --force
```

### SQL Queries
```bash
# Check users table
php bin/console doctrine:query:sql "SELECT * FROM users"

# Count users
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM users"

# Delete test user
php bin/console doctrine:query:sql "DELETE FROM users WHERE email = 'test@example.com'"
```

---

## 🚀 Server

```bash
# Start Symfony server
symfony server:start

# Start in background
symfony server:start -d

# Stop server
symfony server:stop

# Check server status
symfony server:status

# View server logs
symfony server:log
```

---

## 🧪 Testing

### Unit Tests
```bash
# Run all tests
vendor/bin/phpunit

# Run specific test
vendor/bin/phpunit tests/Unit/Security/User/Domain/Model/UserTest.php

# Run with coverage
vendor/bin/phpunit --coverage-html coverage
```

### Functional Tests
```bash
# Run API tests
vendor/bin/phpunit tests/Functional/Security/AuthenticationTest.php
```

### Manual API Testing
```bash
# Register user
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com", "password": "secret123"}'

# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com", "password": "secret123"}'

# Get current user (replace TOKEN)
curl -X GET http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Test with jq for pretty output
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com", "password": "secret123"}' | jq .
```

---

## 🔍 Architecture Validation

### Deptrac
```bash
# Analyze dependencies
./vendor/bin/deptrac analyze

# Generate dependency graph
./vendor/bin/deptrac analyze --formatter=graphviz-dot --output=docs/architecture.dot

# Convert to PNG (requires graphviz)
dot -Tpng docs/architecture.dot -o docs/architecture.png
```

### PHPStan
```bash
# Static analysis
vendor/bin/phpstan analyse

# Specific level
vendor/bin/phpstan analyse --level=8
```

### PHP CS Fixer
```bash
# Check code style
vendor/bin/php-cs-fixer fix --dry-run

# Fix code style
vendor/bin/php-cs-fixer fix
```

---

## 🔧 Symfony Console

### Debug
```bash
# List all routes
php bin/console debug:router

# Show specific route
php bin/console debug:router api_auth_login

# List services
php bin/console debug:container

# Show specific service
php bin/console debug:container App\Security\User\Domain\Port\UserRepositoryInterface

# Show autowired services
php bin/console debug:autowiring

# Show event listeners
php bin/console debug:event-dispatcher

# Show configuration
php bin/console debug:config security
```

### Cache
```bash
# Clear cache
php bin/console cache:clear

# Warm up cache
php bin/console cache:warmup

# Clear specific cache pool
php bin/console cache:pool:clear cache.app
```

### Assets
```bash
# Install assets
php bin/console assets:install

# Import asset map
php bin/console importmap:install
```

---

## 🔐 Security

### Password Hashing
```bash
# Hash a password
php bin/console security:hash-password

# Hash specific password
php bin/console security:hash-password secret123
```

### Generate JWT Secret
```bash
# Generate 256-bit secret
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"

# Generate 512-bit secret
php -r "echo bin2hex(random_bytes(64)) . PHP_EOL;"
```

---

## 📊 Monitoring

### Logs
```bash
# Tail logs
tail -f var/log/dev.log

# Filter logs
tail -f var/log/dev.log | grep ERROR

# Search in logs
grep "Authentication failed" var/log/dev.log

# Clear logs
rm var/log/*.log
```

### Profiler
```bash
# View profiler (browser)
# http://localhost:8000/_profiler

# Clear profiler data
php bin/console cache:pool:clear cache.app_clearer
```

---

## 🐳 Docker (if used)

```bash
# Start containers
docker-compose up -d

# Stop containers
docker-compose down

# View logs
docker-compose logs -f

# Exec into container
docker-compose exec php bash

# Database shell
docker-compose exec db psql -U app hexagonal_demo
```

---

## 📝 Code Generation

### Maker Bundle
```bash
# Create entity
php bin/console make:entity

# Create controller
php bin/console make:controller

# Create command
php bin/console make:command

# Create event subscriber
php bin/console make:subscriber

# Create test
php bin/console make:test
```

---

## 🔄 Git

```bash
# Status
git status

# Add files
git add .

# Commit
git commit -m "feat: add JWT authentication"

# Push
git push origin main

# Create branch
git checkout -b feature/jwt-auth

# View history
git log --oneline --graph
```

---

## 📈 Performance

### Profiling
```bash
# Run Blackfire profiler
blackfire run php bin/console app:some-command

# Profile API endpoint
blackfire curl http://localhost:8000/api/auth/me
```

### Benchmarking
```bash
# Apache Bench
ab -n 1000 -c 10 http://localhost:8000/api/auth/login

# wrk
wrk -t4 -c100 -d30s http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer TOKEN"
```

---

## 🧹 Cleanup

```bash
# Remove vendor
rm -rf vendor/

# Remove cache
rm -rf var/cache/*

# Remove logs
rm -rf var/log/*

# Reinstall
composer install
```

---

## 🎯 Quick Workflows

### New Feature Development
```bash
# 1. Create branch
git checkout -b feature/new-feature

# 2. Run tests
vendor/bin/phpunit

# 3. Validate architecture
./vendor/bin/deptrac analyze

# 4. Check code style
vendor/bin/php-cs-fixer fix

# 5. Static analysis
vendor/bin/phpstan analyse

# 6. Commit
git add .
git commit -m "feat: add new feature"
git push origin feature/new-feature
```

### Deployment Checklist
```bash
# 1. Update dependencies
composer install --no-dev --optimize-autoloader

# 2. Clear cache
php bin/console cache:clear --env=prod

# 3. Warm up cache
php bin/console cache:warmup --env=prod

# 4. Run migrations
php bin/console doctrine:migrations:migrate --no-interaction

# 5. Check logs
tail -f var/log/prod.log
```

### Debug Authentication Issues
```bash
# 1. Check routes
php bin/console debug:router | grep auth

# 2. Check services
php bin/console debug:container | grep Security

# 3. Check user in DB
php bin/console doctrine:query:sql "SELECT * FROM users WHERE email = 'test@example.com'"

# 4. Test password hash
php bin/console security:hash-password secret123

# 5. View logs
tail -f var/log/dev.log | grep -i "auth\|jwt\|token"

# 6. Test JWT manually
# Decode JWT at https://jwt.io
```

---

## 🆘 Emergency Commands

```bash
# Reset everything
rm -rf var/cache/* var/log/*
php bin/console cache:clear
composer install

# Reset database
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction

# Fix permissions
chmod -R 777 var/
```

---

## 📚 Documentation

```bash
# Generate API documentation
php bin/console nelmio:apidoc:dump > docs/api.json

# View routes as markdown
php bin/console debug:router --format=md > docs/ROUTES.md
```

---

## 🎯 Common Tasks

### Create New User Manually
```bash
# Hash password
HASH=$(php bin/console security:hash-password secret123 | tail -n 1)

# Insert user
php bin/console doctrine:query:sql "INSERT INTO users (id, email, password, roles, created_at) VALUES ('01JH5X2Y3Z4A5B6C7D8E9F0G1H', 'admin@example.com', '$HASH', '[\"ROLE_ADMIN\"]', NOW())"
```

### Check JWT Token
```bash
# Decode JWT payload (without verification)
TOKEN="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
echo $TOKEN | cut -d. -f2 | base64 -d | jq .
```

### Export Database
```bash
# PostgreSQL
pg_dump -U app hexagonal_demo > backup.sql

# Import
psql -U app hexagonal_demo < backup.sql
```

---

**Pro Tip:** Add these commands to a Makefile for easier access!

```makefile
.PHONY: install test validate serve

install:
	composer install
	php bin/console doctrine:migrations:migrate

test:
	vendor/bin/phpunit
	./vendor/bin/deptrac analyze

validate:
	vendor/bin/phpstan analyse
	vendor/bin/php-cs-fixer fix --dry-run

serve:
	symfony server:start
```

Then run: `make install`, `make test`, etc.
