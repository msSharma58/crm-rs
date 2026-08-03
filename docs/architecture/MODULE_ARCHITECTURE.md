# Module Architecture — Homora CRM

## 1. Overview

Homora CRM uses a **modular monolith**: one Laravel application, domain modules under `app/Modules/*` and `modules/*` config, shared kernel (Auth, Tenancy, Audit), Vue SPA frontend.

```
┌─────────────────────────────────────────────────────────────┐
│                     Vue 3 SPA (Vite)                        │
│  Pinia · Vue Router · Tailwind · Shadcn Vue · Charts        │
└──────────────────────────┬──────────────────────────────────┘
                           │ Sanctum (cookie / token)
┌──────────────────────────▼──────────────────────────────────┐
│                   Laravel 12 API Layer                      │
│  Controllers · FormRequests · API Resources · Policies      │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│                 Application / Domain Services               │
│  DTOs · Services · Actions · Domain Events                  │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│              Repositories · Eloquent Models                 │
│         Global Tenant Scope · Soft Deletes · Audit          │
└──────────────────────────┬──────────────────────────────────┘
         MySQL · Redis (cache/queue) · Horizon · S3/local disk
```

## 2. Modules

| Module | Responsibility |
|--------|----------------|
| **Core / Tenancy** | Organization resolution, tenant scope, settings |
| **Authentication** | Login, logout, password reset, Sanctum tokens, 2FA-ready |
| **Users** | User profiles, invites, status |
| **Roles & Permissions** | Spatie-style RBAC (custom lean implementation) |
| **Leads** | Capture, pipeline, assignment, tags, scoring |
| **Customers** | Profiles, family, interests, timeline |
| **Projects** | Projects, buildings, floors, units, media, amenities |
| **Sales** | Bookings, negotiations, deal lifecycle |
| **Visits** | Scheduling, check-in, reports, calendar |
| **FollowUps** | Reminders, channels, daily dashboard |
| **Tasks** | Assignments, comments, attachments |
| **Documents** | Typed files, versions |
| **Payments** | Installments, receipts, outstanding, refunds, commissions |
| **Marketing** | Campaigns, costs, ROI |
| **Automation** | Rules engine + queued actions |
| **AI** | Provider contracts + jobs (mock/default) |
| **Notifications** | In-app, email, channel stubs |
| **Reports** | Aggregations, PDF/Excel export jobs |
| **Search** | Global search aggregator |
| **Audit** | Mutation trail |
| **Settings** | Org + user preferences |

## 3. Backend Package Layout (per module)

```
app/Modules/Leads/
  Domain/
    Models/
    Enums/
    Events/
    DTOs/
  Application/
    Services/
    Actions/
    Queries/
  Infrastructure/
    Repositories/
    Jobs/
  Http/
    Controllers/
    Requests/
    Resources/
    Policies/
  Routes/
    api.php
  Providers/
    LeadsServiceProvider.php
  Tests/
    Feature/
```

Shared cross-cutting concerns live in `app/Core/` (Tenancy, Audit, Support).

## 4. Frontend Layout

```
resources/js/
  app.ts
  router/
  stores/
  layouts/
  components/ui/          # Shadcn Vue primitives
  components/shared/
  modules/
    leads/
    customers/
    properties/
    sales/
    visits/
    tasks/
    payments/
    documents/
    marketing/
    reports/
    settings/
    dashboard/
  lib/
  types/
```

## 5. Cross-Cutting Patterns

- **Repository Pattern** — data access behind interfaces (bound in providers)
- **Service Layer** — orchestration, transactions, events
- **DTOs** — typed transfer objects for create/update
- **Form Requests** — validation at HTTP boundary
- **API Resources** — stable JSON contracts
- **Policies** — authorization per model
- **TenantScope** — automatic `organization_id` filtering
- **AuditObserver** — log create/update/delete on audited models
- **Queues** — notifications, AI, exports, automation via Redis + Horizon

## 6. Tenancy Resolution Order

1. Authenticated user's `current_organization_id`
2. `X-Organization-Slug` header (API integrations)
3. Subdomain (production): `{slug}.homora.app`

## 7. Integration Boundaries

| Integration | Interface | MVP |
|-------------|-----------|-----|
| WhatsApp | `MessagingChannel` | Log/stub |
| SMS | `MessagingChannel` | Stub |
| Facebook Lead Ads | `LeadIngestor` | CSV + webhook stub |
| AI | `AiProvider` | Heuristic mock |
| Storage | Laravel Filesystem | Local / S3-ready |
| Payments gateway | N/A | Manual recording |

## 8. Deployment Topology

- `app` (PHP-FPM) + `nginx`
- `queue` (Horizon)
- `scheduler` (`schedule:work`)
- `mysql`, `redis`
- Optional `meilisearch` later for search
