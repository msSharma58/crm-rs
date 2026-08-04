# Homora CRM

Multi-tenant Enterprise Real Estate CRM — the operating system for lead-to-close sales.

**First customer:** Tilottama Homes  
**Stack:** Laravel 12 · PHP 8.4 · MySQL · Redis · Horizon · Sanctum · Vue 3 · TypeScript · Vite · Pinia · Tailwind CSS

---

## Documentation

| Doc | Path |
|-----|------|
| Product Requirements | [docs/product/PRD.md](docs/product/PRD.md) |
| User Flows | [docs/product/USER_FLOWS.md](docs/product/USER_FLOWS.md) |
| Module Architecture | [docs/architecture/MODULE_ARCHITECTURE.md](docs/architecture/MODULE_ARCHITECTURE.md) |
| ER Diagram | [docs/architecture/ER_DIAGRAM.md](docs/architecture/ER_DIAGRAM.md) |
| Folder Structure | [docs/architecture/FOLDER_STRUCTURE.md](docs/architecture/FOLDER_STRUCTURE.md) |
| API Design | [docs/api/API_DESIGN.md](docs/api/API_DESIGN.md) |
| UI Wireframes | [docs/ui/WIREFRAMES.md](docs/ui/WIREFRAMES.md) |
| Roadmap | [docs/roadmap/DEVELOPMENT_ROADMAP.md](docs/roadmap/DEVELOPMENT_ROADMAP.md) |

---

## Quick start (local)

### Requirements
- PHP 8.4+, Composer, Node 22+, MySQL 8
- Redis (optional, recommended for production)

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate

# Configure MySQL in .env (Redis is optional), then:
php artisan migrate --seed
npm run build
php artisan serve
```

SPA: `http://localhost:8000`  
API: `http://localhost:8000/api/v1`

#### Optional: Enable Redis for Production

For production environments, Redis is recommended for caching, sessions, and queues:

```bash
# Install phpredis extension
sudo apt-get install php-redis  # Ubuntu/Debian
# or
brew install php-redis          # macOS

# Update .env
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Docker

```bash
docker compose up -d --build
docker compose exec app php artisan migrate --seed
docker compose exec app npm run build
```

App via Nginx: `http://localhost:8080`

---

## Demo credentials (Tilottama Homes)

| Role | Email | Password |
|------|-------|----------|
| Company Owner / Org Admin | `admin@tilottamahomes.com.np` | `password` |
| Sales Manager | `sales.manager@tilottamahomes.com.np` | `password` |
| Sales Executive | `anil@tilottamahomes.com.np` | `password` |
| Marketing | `marketing@tilottamahomes.com.np` | `password` |
| Accountant | `accounts@tilottamahomes.com.np` | `password` |
| Super Admin | `super@homora.test` | `password` |

Super admins can scope a tenant with header `X-Organization-Id: 1`.

---

## Architecture highlights

- **Multi-tenant SaaS** — shared DB, `organization_id` global scope + Spatie teams RBAC
- **Modular monolith** — domain modules under `app/Modules/*` with services, repositories, DTOs
- **API-first Vue SPA** — Sanctum tokens + CSRF-ready cookie auth
- **Queues** — Redis + Laravel Horizon
- **Audit logs** — mutation trail on critical models
- **AI stubs** — heuristic lead scoring + provider-ready interfaces

### Modules shipped (MVP)

Auth · Organization · Users · Roles/Permissions · Leads (table + Kanban) · Customers · Projects/Units · Visits · Follow-ups · Tasks · Bookings · Payments · Documents · Campaigns · Dashboard · Automation stubs · AI scoring · Search · Audit · Notifications · Reports UI

---

## API examples

```bash
# Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"email":"admin@tilottamahomes.com.np","password":"password"}'

# Leads board
curl http://localhost:8000/api/v1/leads/board \
  -H "Authorization: Bearer <token>" -H 'Accept: application/json'

# Dashboard KPIs
curl http://localhost:8000/api/v1/dashboard \
  -H "Authorization: Bearer <token>" -H 'Accept: application/json'
```

---

## Testing

```bash
./vendor/bin/pest
npm run typecheck
npm run build
```

CI runs on GitHub Actions (`.github/workflows/ci.yml`).

---

## License

Proprietary — Homora CRM. All rights reserved.
