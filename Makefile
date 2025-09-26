# Muslim Finder Backend - Development Makefile
# ================================================

# Colors for output
RED=\033[0;31m
GREEN=\033[0;32m
YELLOW=\033[1;33m
BLUE=\033[0;34m
PURPLE=\033[0;35m
CYAN=\033[0;36m
WHITE=\033[1;37m
NC=\033[0m # No Color

# Default values
PHP_VERSION=8.2
NODE_VERSION=18
PORT=8000
HOST=127.0.0.1

# Help target
.PHONY: help
help: ## Show this help message
	@echo "$(CYAN)Muslim Finder Backend - Development Commands$(NC)"
	@echo "$(YELLOW)===============================================$(NC)"
	@echo ""
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "$(GREEN)%-20s$(NC) %s\n", $$1, $$2}' $(MAKEFILE_LIST)

# ============================================
# SETUP & INSTALLATION
# ============================================

.PHONY: install
install: ## Complete installation (composer + npm + database setup)
	@echo "$(BLUE)Installing Muslim Finder Backend...$(NC)"
	@make composer-install
	@make npm-install
	@make setup-env
	@make generate-key
	@make migrate
	@make seed
	@make build
	@echo "$(GREEN)Installation completed successfully!$(NC)"

.PHONY: composer-install
composer-install: ## Install PHP dependencies
	@echo "$(BLUE)Installing PHP dependencies...$(NC)"
	composer install --no-interaction --prefer-dist --optimize-autoloader

.PHONY: npm-install
npm-install: ## Install Node.js dependencies
	@echo "$(BLUE)Installing Node.js dependencies...$(NC)"
	npm install

.PHONY: setup-env
setup-env: ## Copy .env.example to .env if it doesn't exist
	@if [ ! -f .env ]; then \
		echo "$(BLUE)Creating .env file...$(NC)"; \
		cp .env.example .env; \
		echo "$(GREEN).env file created!$(NC)"; \
	else \
		echo "$(YELLOW).env file already exists$(NC)"; \
	fi

.PHONY: generate-key
generate-key: ## Generate application key
	@echo "$(BLUE)Generating application key...$(NC)"
	php artisan key:generate

# ============================================
# DATABASE OPERATIONS
# ============================================

.PHONY: migrate
migrate: ## Run database migrations
	@echo "$(BLUE)Running database migrations...$(NC)"
	php artisan migrate --force

.PHONY: migrate-fresh
migrate-fresh: ## Fresh migration with seed
	@echo "$(BLUE)Running fresh migrations...$(NC)"
	php artisan migrate:fresh --seed --force

.PHONY: seed
seed: ## Run database seeders
	@echo "$(BLUE)Running database seeders...$(NC)"
	php artisan db:seed --force

.PHONY: rollback
rollback: ## Rollback last migration batch
	@echo "$(BLUE)Rolling back last migration batch...$(NC)"
	php artisan migrate:rollback

.PHONY: reset-db
reset-db: ## Reset database (drop all tables and re-migrate)
	@echo "$(RED)Resetting database...$(NC)"
	php artisan migrate:fresh --seed --force

# ============================================
# LOGGING SYSTEM
# ============================================

.PHONY: create-logs-table
create-logs-table: ## Create logs table migration
	@echo "$(BLUE)Creating logs table migration...$(NC)"
	php artisan make:migration create_logs_table

.PHONY: create-log-model
create-log-model: ## Create Log model
	@echo "$(BLUE)Creating Log model...$(NC)"
	php artisan make:model Log

.PHONY: create-log-cleanup-command
create-log-cleanup-command: ## Create log cleanup command
	@echo "$(BLUE)Creating log cleanup command...$(NC)"
	php artisan make:command CleanupLogsCommand

.PHONY: setup-logging
setup-logging: create-logs-table create-log-model create-log-cleanup-command ## Setup complete logging system
	@echo "$(GREEN)Logging system setup completed!$(NC)"

.PHONY: cleanup-logs
cleanup-logs: ## Manually run log cleanup
	@echo "$(BLUE)Cleaning up old logs...$(NC)"
	php artisan logs:cleanup

# ============================================
# API DEVELOPMENT
# ============================================

.PHONY: create-v1-controllers
create-v1-controllers: ## Create all V1 API controllers
	@echo "$(BLUE)Creating V1 API controllers...$(NC)"
	@mkdir -p app/Http/Controllers/Api/V1
	@echo "$(GREEN)V1 controllers directory created!$(NC)"

.PHONY: generate-swagger
generate-swagger: ## Generate Swagger documentation
	@echo "$(BLUE)Generating Swagger documentation...$(NC)"
	php artisan l5-swagger:generate

.PHONY: clear-swagger
clear-swagger: ## Clear Swagger cache
	@echo "$(BLUE)Clearing Swagger cache...$(NC)"
	php artisan l5-swagger:generate --force

# ============================================
# DEVELOPMENT SERVER
# ============================================

.PHONY: serve
serve: ## Start development server
	@echo "$(BLUE)Starting development server on http://$(HOST):$(PORT)...$(NC)"
	php artisan serve --host=$(HOST) --port=$(PORT)

.PHONY: start
start: serve-background ## Start server in background (alias for serve-background)
	@echo "$(GREEN)Server started! Access your app at http://$(HOST):$(PORT)$(NC)"

.PHONY: start-network
start-network: ## Start server accessible from network (0.0.0.0)
	@echo "$(BLUE)Starting server accessible from network...$(NC)"
	@make stop-server
	@sleep 2
	php artisan serve --host=0.0.0.0 --port=$(PORT) > /dev/null 2>&1 &
	@echo "$(GREEN)Server started on network! Access from any device at http://0.0.0.0:$(PORT)$(NC)"

.PHONY: serve-background
serve-background: ## Start development server in background
	@echo "$(BLUE)Starting development server in background...$(NC)"
	@make stop-server
	@sleep 2
	php artisan serve --host=$(HOST) --port=$(PORT) > /dev/null 2>&1 &
	@echo "$(GREEN)Server started in background on http://$(HOST):$(PORT)$(NC)"

.PHONY: stop-server
stop-server: ## Stop development server
	@echo "$(BLUE)Stopping development server...$(NC)"
	@pkill -f "php artisan serve" || true
	@sleep 1

# ============================================
# FRONTEND BUILDING
# ============================================

.PHONY: build
build: ## Build frontend assets for production
	@echo "$(BLUE)Building frontend assets...$(NC)"
	npm run build

.PHONY: dev
dev: ## Build frontend assets for development
	@echo "$(BLUE)Building frontend assets for development...$(NC)"
	npm run dev

.PHONY: watch
watch: ## Watch frontend assets for changes
	@echo "$(BLUE)Watching frontend assets...$(NC)"
	npm run dev -- --watch

# ============================================
# TESTING
# ============================================

.PHONY: test
test: ## Run PHP tests
	@echo "$(BLUE)Running PHP tests...$(NC)"
	php artisan test

.PHONY: test-coverage
test-coverage: ## Run tests with coverage
	@echo "$(BLUE)Running tests with coverage...$(NC)"
	php artisan test --coverage

.PHONY: test-api
test-api: ## Test API endpoints
	@echo "$(BLUE)Testing API endpoints...$(NC)"
	@echo "$(YELLOW)Health Check:$(NC)"
	@curl -s http://localhost:$(PORT)/api/health | jq . || echo "Health check failed"
	@echo "$(YELLOW)V1 Health Check:$(NC)"
	@curl -s http://localhost:$(PORT)/api/v1/health | jq . || echo "V1 health check failed"

# ============================================
# CACHE & OPTIMIZATION
# ============================================

.PHONY: clear
clear: ## Clear all caches
	@echo "$(BLUE)Clearing all caches...$(NC)"
	php artisan cache:clear
	php artisan config:clear
	php artisan route:clear
	php artisan view:clear
	php artisan event:clear

.PHONY: optimize
optimize: ## Optimize application for production
	@echo "$(BLUE)Optimizing application...$(NC)"
	composer install --no-dev --optimize-autoloader
	php artisan config:cache
	php artisan route:cache
	php artisan view:cache
	npm run build

.PHONY: quick
quick: ## Quick development setup (build + clear all + start server)
	@echo "$(BLUE)Building frontend assets...$(NC)"
	npm run build
	@echo "$(BLUE)Clearing all caches...$(NC)"
	php artisan optimize:clear
	@echo "$(BLUE)Checking for existing server on port $(PORT)...$(NC)"
	@if lsof -Pi :$(PORT) -sTCP:LISTEN -t >/dev/null 2>&1; then \
		echo "$(YELLOW)Port $(PORT) is in use. Stopping existing server...$(NC)"; \
		pkill -f "php artisan serve" || true; \
		sleep 2; \
	fi
	@echo "$(BLUE)Starting development server...$(NC)"
	@if lsof -Pi :$(PORT) -sTCP:LISTEN -t >/dev/null 2>&1; then \
		echo "$(YELLOW)Port $(PORT) still in use. Finding available port...$(NC)"; \
		for port in $$(seq $(PORT) $$((PORT + 10))); do \
			if ! lsof -Pi :$$port -sTCP:LISTEN -t >/dev/null 2>&1; then \
				echo "$(GREEN)Using port $$port$(NC)"; \
				php artisan serve --host=$(HOST) --port=$$port; \
				exit 0; \
			fi; \
		done; \
		echo "$(RED)No available ports found in range $(PORT)-$$((PORT + 10))$(NC)"; \
		exit 1; \
	else \
		php artisan serve --host=$(HOST) --port=$(PORT); \
	fi
	@echo "$(GREEN)Quick setup completed! Server running at http://$(HOST):$(PORT)$(NC)"

.PHONY: quick-bg
quick-bg: ## Quick development setup in background (build + clear all + start server)
	@echo "$(BLUE)Building frontend assets...$(NC)"
	npm run build
	@echo "$(BLUE)Clearing all caches...$(NC)"
	php artisan optimize:clear
	@echo "$(BLUE)Checking for existing server on port $(PORT)...$(NC)"
	@if lsof -Pi :$(PORT) -sTCP:LISTEN -t >/dev/null 2>&1; then \
		echo "$(YELLOW)Port $(PORT) is in use. Stopping existing server...$(NC)"; \
		pkill -f "php artisan serve" || true; \
		sleep 2; \
	fi
	@echo "$(BLUE)Starting development server in background...$(NC)"
	@if lsof -Pi :$(PORT) -sTCP:LISTEN -t >/dev/null 2>&1; then \
		echo "$(YELLOW)Port $(PORT) still in use. Finding available port...$(NC)"; \
		for port in $$(seq $(PORT) $$((PORT + 10))); do \
			if ! lsof -Pi :$$port -sTCP:LISTEN -t >/dev/null 2>&1; then \
				echo "$(GREEN)Using port $$port$(NC)"; \
				php artisan serve --host=$(HOST) --port=$$port > /dev/null 2>&1 & \
				echo "$(GREEN)Quick setup completed! Server running at http://$(HOST):$$port$(NC)"; \
				exit 0; \
			fi; \
		done; \
		echo "$(RED)No available ports found in range $(PORT)-$$((PORT + 10))$(NC)"; \
		exit 1; \
	else \
		php artisan serve --host=$(HOST) --port=$(PORT) > /dev/null 2>&1 & \
		echo "$(GREEN)Quick setup completed! Server running at http://$(HOST):$(PORT)$(NC)"; \
	fi

# ============================================
# SERVER MANAGEMENT
# ============================================

.PHONY: kill-server
kill-server: ## Kill any running Laravel development servers
	@echo "$(BLUE)Stopping Laravel development servers...$(NC)"
	@pkill -f "php artisan serve" || echo "$(YELLOW)No Laravel servers found$(NC)"
	@echo "$(GREEN)Server cleanup completed$(NC)"

.PHONY: check-port
check-port: ## Check if port is available
	@echo "$(BLUE)Checking port $(PORT)...$(NC)"
	@if lsof -Pi :$(PORT) -sTCP:LISTEN -t >/dev/null 2>&1; then \
		echo "$(RED)Port $(PORT) is in use$(NC)"; \
		lsof -Pi :$(PORT) -sTCP:LISTEN; \
	else \
		echo "$(GREEN)Port $(PORT) is available$(NC)"; \
	fi

# ============================================
# MAINTENANCE
# ============================================

.PHONY: logs
logs: ## Show application logs
	@echo "$(BLUE)Showing application logs...$(NC)"
	tail -f storage/logs/laravel.log

.PHONY: logs-db
logs-db: ## Show database logs
	@echo "$(BLUE)Showing database logs...$(NC)"
	php artisan tinker --execute="App\Models\Log::latest()->take(20)->get()->each(function(\$log) { echo \$log->created_at . ' [' . \$log->level . '] ' . \$log->message . PHP_EOL; });"

.PHONY: status
status: ## Show application status
	@echo "$(CYAN)Muslim Finder Backend Status$(NC)"
	@echo "$(YELLOW)===============================$(NC)"
	@echo "$(GREEN)PHP Version:$(NC) $$(php --version | head -1)"
	@echo "$(GREEN)Node Version:$(NC) $$(node --version)"
	@echo "$(GREEN)NPM Version:$(NC) $$(npm --version)"
	@echo "$(GREEN)Laravel Version:$(NC) $$(php artisan --version)"
	@echo "$(GREEN)Database Status:$(NC)"
	@php artisan migrate:status | tail -5 || echo "Database not connected"
	@echo "$(GREEN)Server Status:$(NC)"
	@if pgrep -f "php artisan serve" > /dev/null; then \
		echo "  Server is running on http://$(HOST):$(PORT)"; \
	else \
		echo "  Server is not running"; \
	fi

# ============================================
# DEPLOYMENT
# ============================================

.PHONY: deploy
deploy: ## Deploy to production
	@echo "$(BLUE)Deploying to production...$(NC)"
	@make optimize
	@make migrate
	@echo "$(GREEN)Deployment completed!$(NC)"

.PHONY: backup-db
backup-db: ## Backup database
	@echo "$(BLUE)Creating database backup...$(NC)"
	@mkdir -p storage/backups
	@php artisan db:backup --destination=local --destinationPath=storage/backups/backup-$$(date +%Y%m%d-%H%M%S).sql
	@echo "$(GREEN)Database backup created!$(NC)"

# ============================================
# CLEANUP
# ============================================

.PHONY: clean
clean: ## Clean temporary files
	@echo "$(BLUE)Cleaning temporary files...$(NC)"
	rm -rf node_modules/.cache
	rm -rf storage/logs/*.log
	rm -rf bootstrap/cache/*.php
	@echo "$(GREEN)Cleanup completed!$(NC)"

.PHONY: clean-all
clean-all: clean ## Clean everything (including node_modules and vendor)
	@echo "$(RED)Cleaning everything...$(NC)"
	rm -rf node_modules
	rm -rf vendor
	rm -rf storage/logs/*
	rm -rf bootstrap/cache/*
	@echo "$(GREEN)Complete cleanup finished!$(NC)"

# ============================================
# DEVELOPMENT WORKFLOW
# ============================================

.PHONY: dev-setup
dev-setup: install serve-background ## Complete development setup
	@echo "$(GREEN)Development environment ready!$(NC)"
	@echo "$(CYAN)Server running at: http://$(HOST):$(PORT)$(NC)"
	@echo "$(CYAN)API Documentation: http://$(HOST):$(PORT)/api/documentation$(NC)"

.PHONY: restart
restart: stop-server serve-background ## Restart development server
	@echo "$(GREEN)Server restarted!$(NC)"

.PHONY: refresh
refresh: clear build restart ## Refresh everything (clear + build + restart)
	@echo "$(GREEN)Application refreshed!$(NC)"

# ============================================
# API TESTING
# ============================================

.PHONY: test-auth
test-auth: ## Test authentication endpoints
	@echo "$(BLUE)Testing authentication endpoints...$(NC)"
	@echo "$(YELLOW)Testing registration...$(NC)"
	@curl -X POST http://localhost:$(PORT)/api/v1/auth/register \
		-H "Content-Type: application/json" \
		-d '{"name":"Test User","username":"testuser","email":"test@example.com","password":"password123","password_confirmation":"password123"}' \
		| jq . || echo "Registration test failed"

.PHONY: test-login
test-login: ## Test login endpoint
	@echo "$(BLUE)Testing login endpoint...$(NC)"
	@curl -X POST http://localhost:$(PORT)/api/v1/auth/login \
		-H "Content-Type: application/json" \
		-d '{"email":"superadmin@pantheon.com","password":"password"}' \
		| jq . || echo "Login test failed"

# ============================================
# CRON JOBS
# ============================================

.PHONY: setup-cron
setup-cron: ## Setup cron jobs for log cleanup
	@echo "$(BLUE)Setting up cron jobs...$(NC)"
	@echo "0 2 * * 0 cd $(PWD) && make cleanup-logs" | crontab -
	@echo "$(GREEN)Cron job for log cleanup set up!$(NC)"

# ============================================
# DEFAULT TARGET
# ============================================

.DEFAULT_GOAL := help
