# API Design — Homora CRM

**Base URL:** `/api/v1`  
**Auth:** Laravel Sanctum (SPA session cookie + Bearer tokens)  
**Tenant:** Authenticated user's organization; optional `X-Organization-Slug` for integrations  
**Format:** JSON · ISO-8601 dates · Cursor/page pagination  

## Conventions

| Item | Rule |
|------|------|
| Success | `{ "data": ..., "meta": ... }` |
| Error | `{ "message": "...", "errors": { "field": ["..."] } }` |
| Pagination | `?page=1&per_page=25` or `?cursor=` |
| Filtering | `?filter[status]=new&filter[assigned_to]=3` |
| Sorting | `?sort=-created_at,name` |
| Includes | `?include=assignee,project,tags` |

## Auth

| Method | Path | Description |
|--------|------|-------------|
| POST | `/auth/login` | Login |
| POST | `/auth/logout` | Logout |
| GET | `/auth/me` | Current user + permissions + org |
| POST | `/auth/forgot-password` | Reset link |
| POST | `/auth/reset-password` | Reset password |
| POST | `/auth/tokens` | Create API token |
| DELETE | `/auth/tokens/{id}` | Revoke token |

## Organization & Users

| Method | Path | Description |
|--------|------|-------------|
| GET/PUT | `/organization` | Current org profile |
| GET/POST | `/users` | List/create users |
| GET/PUT/DELETE | `/users/{id}` | User CRUD |
| GET | `/roles` | Roles + permissions matrix |
| PUT | `/users/{id}/roles` | Assign roles |

## Leads

| Method | Path | Description |
|--------|------|-------------|
| GET | `/leads` | Filterable list |
| GET | `/leads/board` | Kanban columns |
| POST | `/leads` | Create |
| GET/PUT/DELETE | `/leads/{id}` | CRUD |
| PATCH | `/leads/{id}/status` | Pipeline move |
| POST | `/leads/{id}/assign` | Assign owner |
| POST | `/leads/{id}/notes` | Add note |
| POST | `/leads/{id}/convert` | Convert → customer |
| POST | `/leads/import` | CSV import |
| GET | `/leads/{id}/timeline` | Activity timeline |

## Customers

| Method | Path |
|--------|------|
| GET/POST | `/customers` |
| GET/PUT/DELETE | `/customers/{id}` |
| GET | `/customers/{id}/timeline` |
| POST | `/customers/{id}/family` |

## Properties

| Method | Path |
|--------|------|
| GET/POST | `/projects` |
| GET/PUT/DELETE | `/projects/{id}` |
| GET/POST | `/projects/{id}/buildings` |
| GET/POST | `/buildings/{id}/floors` |
| GET/POST | `/floors/{id}/units` |
| GET/PUT | `/units/{id}` |
| GET | `/units` (availability filters) |
| POST | `/projects/{id}/media` |

## Visits / Follow-ups / Tasks

| Method | Path |
|--------|------|
| GET/POST | `/visits` |
| PATCH | `/visits/{id}/check-in` |
| POST | `/visits/{id}/report` |
| GET | `/calendar/visits` |
| GET/POST | `/follow-ups` |
| PATCH | `/follow-ups/{id}/complete` |
| GET | `/follow-ups/today` |
| GET/POST | `/tasks` |
| PATCH | `/tasks/{id}/status` |
| POST | `/tasks/{id}/comments` |

## Sales & Payments

| Method | Path |
|--------|------|
| GET/POST | `/bookings` |
| GET/PUT | `/bookings/{id}` |
| GET/POST | `/bookings/{id}/schedules` |
| GET/POST | `/payments` |
| POST | `/payments/{id}/refund` |
| GET | `/payments/outstanding` |

## Documents / Marketing / Reports

| Method | Path |
|--------|------|
| GET/POST | `/documents` |
| GET | `/documents/{id}/versions` |
| POST | `/documents/{id}/versions` |
| GET/POST | `/campaigns` |
| GET | `/campaigns/{id}/roi` |
| GET | `/dashboard/kpis` |
| GET | `/reports/{type}` |
| POST | `/reports/{type}/export` |

## Search / Notifications / Audit / Automation / AI

| Method | Path |
|--------|------|
| GET | `/search?q=` |
| GET | `/notifications` |
| POST | `/notifications/read` |
| GET | `/audit-logs` |
| GET/POST | `/automation/rules` |
| POST | `/ai/leads/{id}/score` |
| POST | `/ai/suggest-follow-up` |
| POST | `/ai/generate-message` |

## Webhooks (inbound stubs)

| Method | Path |
|--------|------|
| POST | `/webhooks/facebook/leads` |
| POST | `/webhooks/whatsapp` |

## Rate Limiting

- Auth: 10/min  
- Write APIs: 120/min per user  
- Search: 60/min  
- Webhooks: 300/min per token  
