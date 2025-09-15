.PHONY: build up down restart logs shell migrate fresh install

# Docker commands
build:
	docker-compose build --no-cache

up:
	docker-compose up -d

down:
	docker-compose down

restart:
	docker-compose restart

logs:
	docker-compose logs -f

# Application commands
shell:
	docker-compose exec app bash

migrate:
	docker-compose exec app php artisan migrate

fresh:
	docker-compose exec app php artisan migrate:fresh --seed

install:
	docker-compose exec app composer install
	docker-compose exec app npm install

# Setup commands
setup: build up
	@echo "Waiting for services to start..."
	sleep 10
	docker-compose exec app php artisan key:generate
	docker-compose exec app php artisan config:cache
	docker-compose exec app php artisan route:cache
	docker-compose exec app php artisan view:cache
	docker-compose exec app php artisan migrate --seed
	@echo "Setup complete! Visit http://localhost:8080"

# Development helpers
clear-cache:
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear

optimize:
	docker-compose exec app php artisan config:cache
	docker-compose exec app php artisan route:cache
	docker-compose exec app php artisan view:cache
	docker-compose exec app composer dump-autoload --optimize

# Database helpers
db-fresh:
	docker-compose exec app php artisan migrate:fresh --seed

db-backup:
	docker-compose exec mysql mysqldump -u laravel -psecret laravel > backup_$$(date +%Y%m%d_%H%M%S).sql

# Testing
test:
	docker-compose exec app php artisan test

# Cleanup
clean:
	docker-compose down -v
	docker system prune -f