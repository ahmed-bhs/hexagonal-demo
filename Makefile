.PHONY: help install start stop restart db-create db-migrate db-reset db-fixtures test lint clean

# Colors for output
BLUE := \033[0;34m
GREEN := \033[0;32m
YELLOW := \033[1;33m
RED := \033[0;31m
NC := \033[0m # No Color

# Console command - use symfony CLI if available, otherwise $(CONSOLE)
CONSOLE := $(shell command -v symfony > /dev/null && echo "symfony console" || echo "$(CONSOLE)")

help: ## Show this help message
	@echo "$(BLUE)Available commands:$(NC)"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-20s$(NC) %s\n", $$1, $$2}'

install: ## Install dependencies
	@echo "$(BLUE)Installing dependencies...$(NC)"
	composer install
	@echo "$(GREEN)Dependencies installed!$(NC)"

start: ## Start Docker containers
	@echo "$(BLUE)Starting Docker containers...$(NC)"
	docker compose up -d
	@echo "$(GREEN)Containers started!$(NC)"

stop: ## Stop Docker containers
	@echo "$(BLUE)Stopping Docker containers...$(NC)"
	docker compose down
	@echo "$(GREEN)Containers stopped!$(NC)"

restart: stop start ## Restart Docker containers

setup: install start db-setup ## Complete project setup (install, start, database)
	@echo "$(GREEN)Project setup complete!$(NC)"
	@echo "$(YELLOW)You can now access the application.$(NC)"

db-create: ## Create database if it doesn't exist
	@echo "$(BLUE)Creating database...$(NC)"
	@$(CONSOLE) doctrine:database:create --if-not-exists || true
	@echo "$(GREEN)Database ready!$(NC)"

db-migrate: ## Run database migrations or create schema
	@echo "$(BLUE)Setting up database schema...$(NC)"
	@if $(CONSOLE) doctrine:migrations:status 2>/dev/null | grep -q "Available Migrations.*0"; then \
		echo "$(YELLOW)No migrations found, creating schema directly...$(NC)"; \
		$(CONSOLE) doctrine:schema:create --no-interaction 2>/dev/null || true; \
	else \
		echo "$(BLUE)Running migrations...$(NC)"; \
		$(CONSOLE) doctrine:migrations:migrate --no-interaction; \
	fi
	@echo "$(GREEN)Database schema ready!$(NC)"

db-fixtures: ## Load database fixtures
	@echo "$(BLUE)Loading fixtures...$(NC)"
	$(CONSOLE) doctrine:fixtures:load --no-interaction
	@echo "$(GREEN)Fixtures loaded!$(NC)"

db-setup: db-create db-migrate db-fixtures ## Setup database (create, migrate, fixtures)
	@echo "$(GREEN)Database setup complete!$(NC)"

db-reset: ## Reset database (drop, create, migrate, fixtures)
	@echo "$(YELLOW)Resetting database...$(NC)"
	$(CONSOLE) doctrine:database:drop --force --if-exists
	@$(MAKE) db-setup
	@echo "$(GREEN)Database reset complete!$(NC)"

db-validate: ## Validate database schema mapping
	@echo "$(BLUE)Validating schema...$(NC)"
	$(CONSOLE) doctrine:schema:validate

test: ## Run tests
	@echo "$(BLUE)Running tests...$(NC)"
	php bin/phpunit

test-coverage: ## Run tests with coverage
	@echo "$(BLUE)Running tests with coverage...$(NC)"
	XDEBUG_MODE=coverage php bin/phpunit --coverage-html var/coverage

lint: ## Run code quality checks
	@echo "$(BLUE)Running linter...$(NC)"
	@if [ -f "vendor/bin/php-cs-fixer" ]; then \
		vendor/bin/php-cs-fixer fix --dry-run --diff; \
	else \
		echo "$(YELLOW)php-cs-fixer not installed$(NC)"; \
	fi

deptrac: ## Run architecture layer validation
	@echo "$(BLUE)Running deptrac...$(NC)"
	@if [ -f "vendor/bin/deptrac" ]; then \
		vendor/bin/deptrac analyse; \
	else \
		echo "$(YELLOW)deptrac not installed. Run: composer require --dev qossmic/deptrac$(NC)"; \
	fi

clean: ## Clean cache and logs
	@echo "$(BLUE)Cleaning cache and logs...$(NC)"
	rm -rf var/cache/* var/log/*
	@echo "$(GREEN)Cache and logs cleaned!$(NC)"

cache-clear: ## Clear Symfony cache
	@echo "$(BLUE)Clearing Symfony cache...$(NC)"
	$(CONSOLE) cache:clear
	@echo "$(GREEN)Cache cleared!$(NC)"

console: ## Access Symfony console
	$(CONSOLE)

logs: ## Show Docker logs
	docker compose logs -f

status: ## Show project status
	@echo "$(BLUE)Docker Containers:$(NC)"
	@docker compose ps
	@echo ""
	@echo "$(BLUE)Database Status:$(NC)"
	@$(CONSOLE) doctrine:migrations:status 2>/dev/null || echo "$(YELLOW)Cannot connect to database$(NC)"
