# TindaWatch

Sari-sari / tindahan monitoring app — stock, sales, utang, e-wallet float, and more.

## Stack

- Laravel 13 + Vue 3 + Inertia
- MySQL 8, Redis, Mailpit via Laravel Sail

## Setup

```bash
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate   # if APP_KEY is empty
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm ci                 # install inside Sail (Linux), not on the host
./vendor/bin/sail npm run build          # or: ./vendor/bin/sail npm run dev
```

App: [http://localhost:8088](http://localhost:8088)  
Mailpit: [http://localhost:18025](http://localhost:18025)

Host ports are offset (`8088`, `33088`, `6388`, `18025`, …) so they don’t clash with your local Docker services already existing.

Current plan allows up to **3 stores per owner** (`TINDAWATCH_MAX_STORES`).

Tip: `alias sail='./vendor/bin/sail'`
