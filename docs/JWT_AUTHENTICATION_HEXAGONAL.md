# JWT Authentication - Hexagonal Architecture

This document explains the JWT authentication system implemented following hexagonal architecture principles.

## 🎯 Overview

Complete JWT authentication with:
- ✅ **100% Hexagonal Architecture** (Domain → Application → Infrastructure → UI)
- ✅ **No Symfony Guard** (uses Symfony Security Authenticator)
- ✅ **Pure Domain** (no framework dependencies)
- ✅ **CQRS pattern** (Commands and Queries)
- ✅ **DDD patterns** (Aggregates, Value Objects, Domain Events)
- ✅ **Event Sourcing** (UserRegistered event)
- ✅ **Ports & Adapters** (easy to swap implementations)

---

## 📁 Complete Structure

```
Security/
├── User/                           # User Bounded Context
│   ├── Domain/
│   │   ├── Model/
│   │   │   └── User.php                    # Aggregate Root
│   │   ├── ValueObject/
│   │   │   ├── UserId.php
│   │   │   ├── Email.php
│   │   │   └── HashedPassword.php
│   │   ├── Event/
│   │   │   └── UserRegistered.php          # Domain Event
│   │   └── Port/
│   │       ├── UserRepositoryInterface.php
│   │       └── PasswordHasherInterface.php
│   │
│   ├── Application/
│   │   ├── Command/
│   │   │   └── RegisterUser/
│   │   │       ├── RegisterUserCommand.php
│   │   │       └── RegisterUserCommandHandler.php
│   │   ├── Query/                          # (can add GetUserById, etc.)
│   │   ├── DTO/
│   │   │   └── UserDTO.php
│   │   └── Exception/
│   │       └── EmailAlreadyExistsException.php
│   │
│   ├── Infrastructure/
│   │   ├── Persistence/Doctrine/
│   │   │   ├── DoctrineUserRepository.php
│   │   │   └── Mapping/User.orm.xml
│   │   ├── Security/
│   │   │   └── SymfonyPasswordHasher.php
│   │   └── EventSubscriber/
│   │       └── UserRegisteredSubscriber.php
│   │
│   └── UI/                                 # (no UI for User BC)
│
└── Authentication/                 # Authentication Bounded Context
    ├── Domain/
    │   └── Port/
    │       └── TokenGeneratorInterface.php
    │
    ├── Application/
    │   ├── Command/
    │   │   └── Login/
    │   │       ├── LoginCommand.php
    │   │       └── LoginCommandHandler.php
    │   ├── Query/
    │   │   └── GetCurrentUser/
    │   │       ├── GetCurrentUserQuery.php
    │   │       └── GetCurrentUserQueryHandler.php
    │   ├── DTO/
    │   │   └── TokenDTO.php
    │   └── Exception/
    │       └── InvalidCredentialsException.php
    │
    ├── Infrastructure/
    │   ├── Jwt/
    │   │   └── FirebaseJwtTokenGenerator.php
    │   └── Security/
    │       ├── JwtAuthenticator.php           # Symfony Security integration
    │       └── SymfonyUserAdapter.php
    │
    └── UI/
        └── Http/Controller/
            └── AuthController.php              # Login, Register, Me
```

---

## 🏗️ Architecture Layers

### 1. Domain Layer (Pure PHP - No Framework)

#### User Aggregate
```php
// Security/User/Domain/Model/User.php
final class User
{
    use AggregateRoot;  // Collects domain events

    public static function register(
        UserId $id,
        Email $email,
        HashedPassword $password,
        array $roles
    ): self {
        $user = new self(...);

        // Record domain event
        $user->recordThat(new UserRegistered(...));

        return $user;
    }

    public function verifyPassword(
        string $plainPassword,
        PasswordHasherInterface $hasher
    ): bool {
        return $hasher->verify(
            $this->password->value(),
            $plainPassword
        );
    }
}
```

**Key points:**
- ✅ Pure PHP (no Symfony, no Doctrine)
- ✅ Business logic only
- ✅ Uses Value Objects (Email, HashedPassword)
- ✅ Records domain events
- ✅ Password verification via Port (interface)

#### Value Objects

```php
// Email Value Object
final readonly class Email
{
    public function __construct(private string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email');
        }
    }
}
```

**Benefits:**
- ✅ Type safety
- ✅ Validation at construction
- ✅ Immutable
- ✅ Self-documenting

#### Ports (Interfaces)

```php
// Domain defines WHAT it needs
interface UserRepositoryInterface
{
    public function findByEmail(Email $email): ?User;
    public function save(User $user): void;
}

interface PasswordHasherInterface
{
    public function hash(string $plainPassword): string;
    public function verify(string $hash, string $plain): bool;
}

interface TokenGeneratorInterface
{
    public function generateToken(User $user): string;
    public function parseToken(string $token): ?array;
}
```

**Hexagonal Architecture:**
- Domain defines **interfaces** (ports)
- Infrastructure provides **implementations** (adapters)
- Easy to swap implementations (testing, different JWT libraries, etc.)

---

### 2. Application Layer (Use Cases)

#### Register User Command

```php
// Application/Command/RegisterUser/RegisterUserCommand.php
final readonly class RegisterUserCommand
{
    public function __construct(
        public string $email,
        public string $plainPassword,
        public array $roles = ['ROLE_USER'],
    ) {}
}

// Application/Command/RegisterUser/RegisterUserCommandHandler.php
#[AsMessageHandler]
final readonly class RegisterUserCommandHandler
{
    public function __invoke(RegisterUserCommand $command): UserId
    {
        // Create Value Objects
        $email = new Email($command->email);

        // Business Rule: Email must be unique
        if ($this->userRepository->emailExists($email)) {
            throw new EmailAlreadyExistsException($email->value());
        }

        // Hash password (via port)
        $hashedPassword = new HashedPassword(
            $this->passwordHasher->hash($command->plainPassword)
        );

        // Generate ID
        $userId = new UserId($this->idGenerator->generate());

        // Create User aggregate (Domain logic)
        $user = User::register($userId, $email, $hashedPassword);

        // Persist
        $this->userRepository->save($user);

        // Events automatically published after flush
        return $userId;
    }
}
```

**Flow:**
1. Parse input (Command)
2. Validate business rules
3. Call Domain logic
4. Persist
5. Events published automatically by `DomainEventPublisherListener`

#### Login Command

```php
final readonly class LoginCommandHandler
{
    public function __invoke(LoginCommand $command): TokenDTO
    {
        // Find user
        $user = $this->userRepository->findByEmail(
            new Email($command->email)
        );

        if (!$user) {
            throw new InvalidCredentialsException();
        }

        // Verify password
        if (!$user->verifyPassword($command->plainPassword, $this->passwordHasher)) {
            throw new InvalidCredentialsException();
        }

        // Record login
        $user->recordLogin();
        $this->userRepository->save($user);

        // Generate JWT token
        $token = $this->tokenGenerator->generateToken($user);

        return new TokenDTO($token, $user->id()->value(), ...);
    }
}
```

**Security:**
- ✅ Same error message for "user not found" and "wrong password" (no user enumeration)
- ✅ Password verification via port (abstraction)
- ✅ JWT generation via port (can swap implementation)

---

### 3. Infrastructure Layer (Adapters)

#### JWT Token Generator (Firebase JWT)

```php
// Infrastructure/Jwt/FirebaseJwtTokenGenerator.php
final readonly class FirebaseJwtTokenGenerator implements TokenGeneratorInterface
{
    public function generateToken(User $user): string
    {
        $payload = [
            'iss' => $this->issuer,
            'iat' => $now->getTimestamp(),
            'exp' => $expiresAt->getTimestamp(),
            'sub' => $user->id()->value(),
            'email' => $user->email()->value(),
            'roles' => $user->roles(),
        ];

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    public function parseToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
            return [
                'userId' => $decoded->sub,
                'email' => $decoded->email,
                'roles' => $decoded->roles,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
}
```

**Easy to swap:**
- Can replace with Lexik JWT, custom implementation, etc.
- Domain doesn't care about implementation

#### Symfony Security Integration

```php
// Infrastructure/Security/JwtAuthenticator.php
final class JwtAuthenticator extends AbstractAuthenticator
{
    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization');
    }

    public function authenticate(Request $request): Passport
    {
        // Extract token from Authorization header
        $token = substr($request->headers->get('Authorization'), 7);

        // Parse token (via port)
        $payload = $this->tokenGenerator->parseToken($token);

        if (!$payload) {
            throw new AuthenticationException('Invalid token');
        }

        // Load user from repository (via port)
        $user = $this->userRepository->findById(
            new UserId($payload['userId'])
        );

        // Return Symfony UserInterface adapter
        return new SelfValidatingPassport(
            new UserBadge($payload['userId'], fn() =>
                new SymfonyUserAdapter($user)
            )
        );
    }
}
```

**Key points:**
- ✅ Uses ports (TokenGeneratorInterface, UserRepositoryInterface)
- ✅ Adapts Domain User to Symfony UserInterface
- ✅ No business logic (just infrastructure concern)

#### User Adapter for Symfony

```php
// Infrastructure/Security/SymfonyUserAdapter.php
final readonly class SymfonyUserAdapter implements UserInterface
{
    public function __construct(private User $user) {}

    public function getRoles(): array
    {
        return $this->user->roles();
    }

    public function getUserIdentifier(): string
    {
        return $this->user->id()->value();
    }

    public function getDomainUser(): User
    {
        return $this->user;
    }
}
```

**Adapter Pattern:**
- Wraps Domain User
- Implements Symfony UserInterface
- Allows Symfony Security to work with our Domain

---

### 4. UI Layer (Controllers)

```php
// UI/Http/Controller/AuthController.php
#[Route('/api/auth')]
final class AuthController extends AbstractController
{
    #[Route('/register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Dispatch Command
        $command = new RegisterUserCommand(
            email: $data['email'],
            plainPassword: $data['password']
        );

        $userId = $this->commandBus->dispatch($command);

        return new JsonResponse([
            'success' => true,
            'userId' => $userId->value(),
        ], 201);
    }

    #[Route('/login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $command = new LoginCommand(...);
        $tokenDTO = $this->commandBus->dispatch($command);

        return new JsonResponse($tokenDTO->toArray());
    }

    #[Route('/me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser(); // Symfony Security

        $query = new GetCurrentUserQuery($user->getUserIdentifier());
        $userDTO = $this->queryBus->dispatch($query);

        return new JsonResponse($userDTO->toArray());
    }
}
```

**Thin controller:**
- ✅ Parse request
- ✅ Dispatch Command/Query
- ✅ Format response
- ✅ No business logic

---

## 🔄 Complete Authentication Flow

### Registration Flow

```
1. POST /api/auth/register {"email": "...", "password": "..."}
   ↓
2. AuthController::register()
   - Parse JSON
   - Create RegisterUserCommand
   ↓
3. RegisterUserCommandHandler (Application)
   - Validate email format (Value Object)
   - Check email uniqueness (Business Rule)
   - Hash password (via PasswordHasherInterface port)
   - Generate ID (via IdGeneratorInterface port)
   ↓
4. User::register() (Domain)
   - Create User aggregate
   - recordThat(UserRegistered) ← Event collected
   ↓
5. UserRepository->save() (Infrastructure)
   - persist()
   - flush() ✅ DB commit
   ↓
6. DomainEventPublisherListener (Infrastructure)
   - pullDomainEvents() ← Get UserRegistered
   - eventPublisher->publish()
   ↓
7. EventStore + Symfony EventDispatcher
   - EventStore->append() (audit trail)
   - Dispatch to UserRegisteredSubscriber
   ↓
8. UserRegisteredSubscriber
   - Log registration
   - Send welcome email (TODO)
   ↓
9. Response: 201 Created with userId
```

### Login Flow

```
1. POST /api/auth/login {"email": "...", "password": "..."}
   ↓
2. AuthController::login()
   - Parse JSON
   - Create LoginCommand
   ↓
3. LoginCommandHandler (Application)
   - Find user by email (via repository port)
   - Verify password (Domain method + hasher port)
   - Record login (Domain method)
   ↓
4. TokenGenerator->generateToken() (Infrastructure)
   - Create JWT payload
   - Sign with secret
   ↓
5. Response: 200 OK with JWT token
   {
     "token": "eyJ...",
     "user": {
       "id": "...",
       "email": "...",
       "roles": [...]
     }
   }
```

### Authenticated Request Flow

```
1. GET /api/auth/me
   Headers: Authorization: Bearer eyJ...
   ↓
2. JwtAuthenticator::supports() (Infrastructure)
   - Check Authorization header exists
   ↓
3. JwtAuthenticator::authenticate()
   - Extract token from header
   - Parse token (via TokenGeneratorInterface)
   - Load user from DB (via UserRepositoryInterface)
   - Create SymfonyUserAdapter
   - Return Passport
   ↓
4. Symfony Security
   - User authenticated ✅
   - Request continues
   ↓
5. AuthController::me()
   - $this->getUser() returns SymfonyUserAdapter
   - Dispatch GetCurrentUserQuery
   ↓
6. GetCurrentUserQueryHandler
   - Load user from repository
   - Convert to UserDTO
   ↓
7. Response: 200 OK with user data
```

---

## ⚙️ Configuration

### services.yaml

```yaml
# User Repository Port → Adapter binding
App\Security\User\Domain\Port\UserRepositoryInterface:
    class: App\Security\User\Infrastructure\Persistence\Doctrine\DoctrineUserRepository

# Password Hasher Port → Adapter binding
App\Security\User\Domain\Port\PasswordHasherInterface:
    class: App\Security\User\Infrastructure\Security\SymfonyPasswordHasher
    arguments:
        $hasherFactory: '@security.password_hasher_factory'

# JWT Token Generator Port → Adapter binding
App\Security\Authentication\Domain\Port\TokenGeneratorInterface:
    class: App\Security\Authentication\Infrastructure\Jwt\FirebaseJwtTokenGenerator
    arguments:
        $secret: '%env(JWT_SECRET)%'
        $issuer: '%env(JWT_ISSUER)%'
        $ttl: 3600

# JWT Authenticator (Symfony Security)
App\Security\Authentication\Infrastructure\Security\JwtAuthenticator:
    arguments:
        $tokenGenerator: '@App\Security\Authentication\Domain\Port\TokenGeneratorInterface'
        $userRepository: '@App\Security\User\Domain\Port\UserRepositoryInterface'
```

### security.yaml

```yaml
security:
    password_hashers:
        common:
            algorithm: auto
            cost: 12

    firewalls:
        # Public endpoints
        public:
            pattern: ^/api/auth/(login|register)
            security: false

        # Protected API (JWT required)
        api:
            pattern: ^/api
            stateless: true
            custom_authenticators:
                - App\Security\Authentication\Infrastructure\Security\JwtAuthenticator

    access_control:
        - { path: ^/api/auth/login, roles: PUBLIC_ACCESS }
        - { path: ^/api/auth/register, roles: PUBLIC_ACCESS }
        - { path: ^/api, roles: ROLE_USER }
```

### .env

```bash
JWT_SECRET=your-jwt-secret-key-change-in-production-min-256-bits-please
JWT_ISSUER=hexagonal-demo-app
```

---

## 🧪 API Usage Examples

### 1. Register User

```bash
curl -X POST http://localhost/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "secret123"
  }'
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "User registered successfully",
  "userId": "01JH5X2Y3Z4A5B6C7D8E9F0G1H"
}
```

### 2. Login

```bash
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "secret123"
  }'
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "user": {
      "id": "01JH5X2Y3Z4A5B6C7D8E9F0G1H",
      "email": "user@example.com",
      "roles": ["ROLE_USER"]
    }
  }
}
```

### 3. Get Current User (Protected)

```bash
curl -X GET http://localhost/api/auth/me \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": "01JH5X2Y3Z4A5B6C7D8E9F0G1H",
    "email": "user@example.com",
    "roles": ["ROLE_USER"],
    "createdAt": "2026-01-15T10:30:00+00:00",
    "lastLoginAt": "2026-01-15T14:45:00+00:00"
  }
}
```

### 4. Access Protected Resource

```bash
curl -X GET http://localhost/api/attributions \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
```

---

## ✅ Benefits of This Architecture

### 1. **Pure Domain**
- Domain has zero framework dependencies
- Can be tested without Symfony
- Can be reused in other contexts (CLI, GraphQL, etc.)

### 2. **Easy Testing**
```php
// Test with fake implementations
$fakeRepository = new InMemoryUserRepository();
$fakeHasher = new FakePasswordHasher();
$fakeTokenGenerator = new FakeTokenGenerator();

$handler = new RegisterUserCommandHandler(
    $fakeRepository,
    $fakeHasher,
    $fakeIdGenerator
);

$userId = $handler(new RegisterUserCommand(...));
```

### 3. **Easy to Swap Implementations**
- Want to use Lexik JWT instead of Firebase JWT?
  - Create new adapter implementing `TokenGeneratorInterface`
  - Update `services.yaml`
  - Domain and Application unchanged ✅

- Want to use Redis for user storage?
  - Create `RedisUserRepository` implementing `UserRepositoryInterface`
  - Update `services.yaml`
  - Domain and Application unchanged ✅

### 4. **Clear Separation of Concerns**
| Layer | Responsibility |
|-------|---------------|
| **Domain** | Business logic, rules, entities |
| **Application** | Use cases, orchestration |
| **Infrastructure** | Technical details (JWT, Doctrine, Symfony) |
| **UI** | HTTP handling, request/response |

### 5. **CQRS Pattern**
- Clear separation: Commands (write) vs Queries (read)
- Easy to optimize separately
- Easy to scale separately

### 6. **Event-Driven**
- Domain events (UserRegistered)
- Automatic publishing after successful transaction
- Easy to add new reactions (send email, analytics, etc.)

---

## 🔒 Security Best Practices

### 1. **Password Hashing**
- ✅ Uses Symfony's password hasher (bcrypt/argon2)
- ✅ Cost factor 12 (configurable)
- ✅ Never stores plain passwords

### 2. **JWT Token Security**
- ✅ Short TTL (1 hour default)
- ✅ Stateless (no session storage)
- ✅ Signed with secret (HS256)
- ✅ Includes user ID, email, roles in payload

### 3. **Error Messages**
- ✅ Same error for "user not found" and "wrong password"
- ✅ Prevents user enumeration
- ✅ Generic messages to frontend

### 4. **Input Validation**
- ✅ Value Objects validate at construction
- ✅ Email format validation
- ✅ Business rules in Application layer

---

## 📊 Database Schema

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

---

## 🚀 Next Steps

### Optional enhancements:

1. **Refresh Tokens**
   - Add `RefreshToken` entity
   - Add `RefreshTokenCommand`
   - Store in database

2. **Email Verification**
   - Add `EmailVerificationToken` entity
   - Add `VerifyEmailCommand`
   - Send verification email

3. **Password Reset**
   - Add `PasswordResetToken` entity
   - Add `RequestPasswordResetCommand`
   - Add `ResetPasswordCommand`

4. **Two-Factor Authentication**
   - Add `TwoFactorSecret` to User
   - Add `EnableTwoFactorCommand`
   - Add `VerifyTwoFactorCommand`

5. **Rate Limiting**
   - Add rate limiter for login attempts
   - Prevent brute force attacks

---

## 🎯 Summary

This JWT authentication system demonstrates:

✅ **100% Hexagonal Architecture**
- Domain is pure PHP (no framework)
- Infrastructure adapters implement domain ports
- Easy to test, swap, and maintain

✅ **100% CQRS**
- Commands for write operations (Register, Login)
- Queries for read operations (GetCurrentUser)

✅ **100% DDD**
- Aggregates (User)
- Value Objects (Email, UserId, HashedPassword)
- Domain Events (UserRegistered)
- Repositories (UserRepositoryInterface)

✅ **Production-Ready**
- Secure password hashing
- JWT token generation
- Symfony Security integration
- Event sourcing (audit trail)
- Error handling
- Type safety

The architecture is **clean, testable, and maintainable**. Each layer has a single responsibility and can evolve independently.
