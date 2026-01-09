# Quick Start

Run the Hexagonal Demo in 5 minutes.

---

## Prerequisites

- PHP 8.1+
- Composer
- Symfony CLI (recommended)
- MySQL/PostgreSQL/SQLite

---

## Installation

```bash
# 1. Clone repository
git clone https://github.com/ahmed-bhs/hexagonal-demo
cd hexagonal-demo

# 2. Install dependencies
composer install

# 3. Configure database
# Edit .env and set DATABASE_URL
cp .env.example .env

# 4. Create database
php bin/console doctrine:database:create

# 5. Create schema
php bin/console doctrine:schema:create

# 6. Load demo data
php bin/console doctrine:fixtures:load

# 7. Start server
symfony server:start
# OR
php -S localhost:8000 -t public/
```

---

## Access

Open: **http://localhost:8000**

---

## What You'll See

- **Dashboard** (/) - Statistics overview
- **Residents** (/habitants) - List of all residents
- **Gifts** (/cadeaux) - Catalog of available gifts

---

## Next Steps

- [Explore the architecture](../architecture/overview.md)
- [See code examples](../examples/entities.md)
- [Run tests](../guides/testing.md)
