---
layout: default
title: Quick Start
nav_order: 2
description: "Get the Hexagonal Demo running in 5 minutes with step-by-step installation instructions."
---

# Quick Start Guide
{: .no_toc }

Get the Hexagonal Demo up and running in 5 minutes.
{: .fs-6 .fw-300 }

## Table of Contents
{: .no_toc .text-delta }

1. TOC
{:toc}

---

## Prerequisites

Before you begin, ensure you have:

- **PHP 8.1 or higher**
- **Composer** (dependency manager)
- **Symfony CLI** (optional but recommended)
- **Database** (MySQL, PostgreSQL, or SQLite)

{: .note }
Don't have Symfony CLI? You can use `php -S localhost:8000 -t public` instead.

---

## Installation

### Step 1: Clone the Repository

```bash
git clone https://github.com/ahmed-bhs/hexagonal-demo.git
cd hexagonal-demo
```

### Step 2: Install Dependencies

```bash
composer install
```

This will install:
- Symfony 6.4+
- Doctrine ORM
- Symfony Messenger
- hexagonal-maker-bundle
- All other dependencies

### Step 3: Configure Database

#### Option A: Using SQLite (Simplest)

Edit `.env` file:

```bash
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
```

#### Option B: Using MySQL

```bash
DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/hexagonal_demo?serverVersion=8.0"
```

#### Option C: Using PostgreSQL

```bash
DATABASE_URL="postgresql://db_user:db_password@127.0.0.1:5432/hexagonal_demo?serverVersion=15&charset=utf8"
```

{: .warning }
For production, use `.env.local` instead of editing `.env` directly.

### Step 4: Create Database

```bash
php bin/console doctrine:database:create
```

### Step 5: Create Schema

```bash
php bin/console doctrine:schema:create
```

This creates the tables for:
- `habitant` (residents)
- `cadeau` (gifts)
- `attribution` (gift assignments)

### Step 6: Load Sample Data

```bash
php bin/console doctrine:fixtures:load
```

Type `yes` when prompted. This loads:
- 10 residents (children, adults, seniors)
- 10 gifts (various items)
- 7 gift attributions

### Step 7: Start the Server

#### With Symfony CLI:

```bash
symfony server:start
```

#### Without Symfony CLI:

```bash
php -S localhost:8000 -t public
```

### Step 8: Open in Browser

Navigate to: **http://localhost:8000**

---

## What You'll See

### Home Page (Dashboard)

The home page shows:

- **Total Residents:** Count of all residents
- **Total Gifts:** Count of all gifts in catalog
- **Attributions Made:** Number of gifts assigned
- **Age Distribution:** Breakdown by category

### Navigation

Use the top menu to navigate:

- **Home** - Dashboard with statistics
- **Habitants** - List of all residents
- **Cadeaux** - Catalog of gifts

---

## Exploring the Application

### Residents List (`/habitants`)

Displays all residents with:

- Full name (first name + last name)
- Age with category badge:
  - **Child** (0-17 years) - Blue badge
  - **Adult** (18-64 years) - Green badge
  - **Senior** (65+ years) - Yellow badge
- Email address
- Bootstrap-styled table

### Gifts Catalog (`/cadeaux`)

Shows all gifts with:

- Gift name
- Description
- Stock quantity
- Availability status:
  - **Available** (green) - In stock
  - **Out of Stock** (red) - No stock

### Database Content

After loading fixtures, you'll have:

#### 10 Residents:
- Sophie Martin (8 years old, child)
- Lucas Dubois (45 years old, adult)
- Marie Lefebvre (72 years old, senior)
- Thomas Bernard (12 years old, child)
- Emma Petit (35 years old, adult)
- And 5 more...

#### 10 Gifts:
- Puzzle 3D (10 in stock)
- Livre de recettes (5 in stock)
- Jeu de société (3 in stock)
- And 7 more...

#### 7 Attributions:
- Sophie → Puzzle 3D
- Lucas → Livre de recettes
- And 5 more...

---

## Verifying the Installation

### Check the Database

```bash
php bin/console doctrine:query:sql "SELECT COUNT(*) as total FROM habitant"
```

Expected output: `total: 10`

### Check Doctrine Mappings

```bash
php bin/console doctrine:mapping:info
```

You should see:
- `App\Cadeau\Attribution\Domain\Model\Habitant`
- `App\Cadeau\Attribution\Domain\Model\Cadeau`
- `App\Cadeau\Attribution\Domain\Model\Attribution`

### Run Tests (Optional)

```bash
php bin/phpunit
```

---

## Common Issues

### Port Already in Use

If port 8000 is busy:

```bash
# Symfony CLI
symfony server:start --port=8080

# PHP built-in server
php -S localhost:8080 -t public
```

### Database Connection Failed

Check your database credentials in `.env`:

```bash
# Verify database is running
# MySQL:
mysql -u db_user -p

# PostgreSQL:
psql -U db_user
```

### Composer Install Fails

Try clearing the cache:

```bash
composer clear-cache
composer install
```

### Fixtures Already Loaded

To reload fixtures:

```bash
php bin/console doctrine:schema:drop --force
php bin/console doctrine:schema:create
php bin/console doctrine:fixtures:load
```

---

## Next Steps

Now that you have the app running:

1. **[Explore the Architecture](architecture)** - Understand the hexagonal structure
2. **[Learn the Features](features)** - See what's implemented
3. **[Take the Code Tour](code-tour)** - Compare generated vs manual code
4. **[Check the API](api)** - Learn about endpoints and CQRS

---

## Development Mode

### Enable Debug Toolbar

The Symfony debug toolbar is enabled by default in dev mode. You'll see it at the bottom of each page with:

- Request/Response info
- Database queries
- Performance metrics
- Messenger messages

### Clear Cache

If you make configuration changes:

```bash
php bin/console cache:clear
```

### Watch for Changes

For Twig template changes, no cache clear needed. For PHP changes, cache is auto-refreshed in dev mode.

---

## Production Deployment

For production deployment:

1. **Set environment:**
   ```bash
   APP_ENV=prod
   APP_DEBUG=0
   ```

2. **Install production dependencies:**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

3. **Clear and warm cache:**
   ```bash
   php bin/console cache:clear --env=prod
   php bin/console cache:warmup --env=prod
   ```

4. **Create real database** (don't use SQLite in production)

5. **Run migrations** instead of schema:create:
   ```bash
   php bin/console doctrine:migrations:migrate --no-interaction
   ```

---

## Alternative: Docker Setup

### Using Docker Compose

```bash
# Start services
docker-compose up -d

# Install dependencies
docker-compose exec php composer install

# Create database
docker-compose exec php php bin/console doctrine:database:create
docker-compose exec php php bin/console doctrine:schema:create
docker-compose exec php php bin/console doctrine:fixtures:load

# Access at http://localhost:8080
```

---

## Getting Help

If you encounter issues:

1. **Check the logs:**
   ```bash
   tail -f var/log/dev.log
   ```

2. **Verify requirements:**
   ```bash
   symfony check:requirements
   ```

3. **Ask for help:**
   - [GitHub Issues](https://github.com/ahmed-bhs/hexagonal-demo/issues)
   - [Symfony Community](https://symfony.com/community)

---

{: .highlight }
**You're all set!** The Hexagonal Demo is now running. Explore the code, experiment with features, and learn hexagonal architecture in practice.
