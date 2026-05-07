.PHONY: up down build restart logs shell db-shell status

## Jalankan semua container
up:
	docker compose up -d
	@echo ""
	@echo "✓ Panel berjalan di http://localhost:3000"
	@echo "✓ Login: admin@jezpro.id / Admin@12345"
	@echo "✓ phpMyAdmin: http://localhost:8081"

## Jalankan dengan output log di terminal
up-log:
	docker compose up

## Hentikan semua container
down:
	docker compose down

## Build ulang image
build:
	docker compose build --no-cache app

## Restart app container saja
restart:
	docker compose restart app

## Lihat log real-time
logs:
	docker compose logs -f app

## Masuk ke shell container app
shell:
	docker compose exec app sh

## Masuk ke MySQL shell
db-shell:
	docker compose exec mysql mysql -u failover -pfailover_secret failover_panel

## Status semua container
status:
	docker compose ps

## Jalankan artisan command (contoh: make artisan CMD="route:list")
artisan:
	docker compose exec app php artisan $(CMD)

## Reset database (hapus semua data, migrate ulang, seed ulang)
db-reset:
	docker compose exec app php artisan migrate:fresh --seed --force

## Buat superadmin baru
make-admin:
	docker compose exec app php artisan db:seed --class=SuperadminSeeder --force

## Clear semua cache
cache-clear:
	docker compose exec app php artisan optimize:clear
