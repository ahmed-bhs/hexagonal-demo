# JWT Authentication - Installation & Setup

Quick guide to install and configure JWT authentication in the project.

---

## 📦 Installation

### 1. Install Firebase JWT library

```bash
composer require firebase/php-jwt
```

### 2. Configure environment variables

Add to `.env`:
```bash
JWT_SECRET=your-jwt-secret-key-change-in-production-min-256-bits-please
JWT_ISSUER=hexagonal-demo-app
```

⚠️ **IMPORTANT:** In production, generate a strong secret (256+ bits):
```bash
# Generate secure random key
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
```

---

## 🗄️ Database Setup

### 1. Create migration for users table

```bash
php bin/console doctrine:migrations:diff
```

This will generate a migration with the users table:
```sql
CREATE TABLE users (
    id VARCHAR(36) PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    roles JSON NOT NULL,
    created_at TIMESTAMP NOT NULL,
    last_login_at TIMESTAMP NULL
);

CREATE INDEX idx_users_email ON users(email);
```

### 2. Run migrations

```bash
php bin/console doctrine:migrations:migrate
```

---

## ⚙️ Configuration Already Done

The following files are already configured in the project:

✅ `config/services.yaml` - Port → Adapter bindings
✅ `config/packages/security.yaml` - Symfony Security with JWT
✅ `src/Security/` - Complete authentication system
✅ `docs/JWT_AUTHENTICATION_HEXAGONAL.md` - Full documentation

---

## 🧪 Testing the API

### 1. Start Symfony server

```bash
symfony server:start
```

### 2. Register a new user

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "secret123"
  }'
```

Expected response (201 Created):
```json
{
  "success": true,
  "message": "User registered successfully",
  "userId": "01JH5X2Y3Z4A5B6C7D8E9F0G1H"
}
```

### 3. Login

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "secret123"
  }'
```

Expected response (200 OK):
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "user": {
      "id": "01JH5X2Y3Z4A5B6C7D8E9F0G1H",
      "email": "test@example.com",
      "roles": ["ROLE_USER"]
    }
  }
}
```

### 4. Access protected endpoint

Copy the token from login response and use it:

```bash
TOKEN="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."

curl -X GET http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer $TOKEN"
```

Expected response (200 OK):
```json
{
  "success": true,
  "data": {
    "id": "01JH5X2Y3Z4A5B6C7D8E9F0G1H",
    "email": "test@example.com",
    "roles": ["ROLE_USER"],
    "createdAt": "2026-01-15T10:30:00+00:00",
    "lastLoginAt": "2026-01-15T14:45:00+00:00"
  }
}
```

### 5. Test protected resource

```bash
curl -X GET http://localhost:8000/api/attributions \
  -H "Authorization: Bearer $TOKEN"
```

This should work if the endpoint exists and requires ROLE_USER.

---

## 🔍 Verify Installation

### Check database

```bash
php bin/console doctrine:schema:validate
```

Should show:
```
[Mapping]  OK - The mapping files are correct.
[Database] OK - The database schema is in sync with the mapping files.
```

### Check routes

```bash
php bin/console debug:router | grep auth
```

Should show:
```
api_auth_register    POST    /api/auth/register
api_auth_login       POST    /api/auth/login
api_auth_me          GET     /api/auth/me
```

### Check services

```bash
php bin/console debug:container | grep Security
```

Should show JWT-related services registered.

---

## 🐛 Troubleshooting

### Error: "Class 'Firebase\JWT\JWT' not found"

**Solution:** Install firebase/php-jwt
```bash
composer require firebase/php-jwt
```

### Error: "Environment variable not found: JWT_SECRET"

**Solution:** Add to `.env`:
```bash
JWT_SECRET=your-secret-here
JWT_ISSUER=hexagonal-demo-app
```

### Error: "Invalid credentials" (but credentials are correct)

**Check:**
1. Is the user in database?
   ```bash
   php bin/console doctrine:query:sql "SELECT * FROM users WHERE email = 'test@example.com'"
   ```

2. Is the password hashed correctly?
   ```bash
   php bin/console security:hash-password
   ```

3. Check logs:
   ```bash
   tail -f var/log/dev.log
   ```

### Error: "No route found for GET /api/auth/me"

**Check routes are loaded:**
```bash
php bin/console debug:router api_auth_me
```

If route is missing, check `src/Security/Authentication/UI/Http/Controller/AuthController.php` has correct Route attributes.

### Error: "Authentication failed" on protected endpoint

**Check token:**
1. Is Authorization header present?
   ```bash
   curl -v http://localhost:8000/api/auth/me \
     -H "Authorization: Bearer YOUR_TOKEN"
   ```

2. Is token valid?
   - Check expiration (default 1 hour)
   - Check JWT_SECRET matches

3. Check logs for JWT parsing errors:
   ```bash
   tail -f var/log/dev.log | grep JWT
   ```

---

## 📚 Next Steps

1. **Read full documentation:** `docs/JWT_AUTHENTICATION_HEXAGONAL.md`
2. **Understand architecture:** How Domain, Application, Infrastructure layers work
3. **Extend functionality:** Add password reset, email verification, 2FA, etc.
4. **Write tests:** Unit tests for Domain, integration tests for Application

---

## 🔐 Security Checklist

Before going to production:

- [ ] Generate strong JWT_SECRET (256+ bits)
- [ ] Store JWT_SECRET in vault (not in .env committed to git)
- [ ] Configure HTTPS only
- [ ] Set appropriate JWT TTL (default 1 hour)
- [ ] Enable rate limiting on login endpoint
- [ ] Add refresh tokens for better UX
- [ ] Log security events (failed logins, etc.)
- [ ] Configure CORS properly
- [ ] Add request validation middleware
- [ ] Enable SQL query logging in production (for debugging)

---

## ✅ Installation Complete!

Your JWT authentication system is ready to use with:
- ✅ Pure hexagonal architecture
- ✅ CQRS pattern
- ✅ DDD patterns
- ✅ Event sourcing
- ✅ Type-safe implementation
- ✅ Production-ready security

Happy coding! 🚀
