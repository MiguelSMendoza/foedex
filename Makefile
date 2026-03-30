up:
	docker compose up -d --build

down:
	docker compose down

console:
	docker compose exec app php bin/console $(cmd)

test:
	APP_ENV=test php bin/phpunit

migrate:
	docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
