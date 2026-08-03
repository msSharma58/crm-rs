# Development Roadmap — Homora CRM

## Phase 0 — Foundation (current)
- Planning docs (PRD, ERD, API, architecture, flows, wireframes)
- Laravel 12 + Vue 3 + Docker + CI skeleton
- Multi-tenancy, Sanctum auth, RBAC
- Audit logs, base UI shell, Tilottama seed

## Phase 1 — Sales Core (MVP)
- Leads + Kanban + CSV import
- Customers + conversion
- Projects / units inventory
- Visits + calendar
- Follow-ups + tasks
- Bookings + payment schedules + payments
- Documents + versions
- Dashboard KPIs
- Feature tests (tenant isolation, pipeline, booking)

## Phase 2 — Growth Ops
- Marketing campaigns + ROI
- Automation rules engine (assign, remind, escalate)
- Notifications (email + in-app production; WhatsApp/SMS providers)
- Reports + PDF/Excel export
- Global search (DB first; Meilisearch optional)

## Phase 3 — Intelligence
- AI lead scoring & prioritization (real provider)
- Follow-up / WhatsApp / email generators
- Conversation summary & sentiment
- Property recommendation
- Sales forecast

## Phase 4 — Scale
- Facebook Lead Ads live sync
- WhatsApp Business Cloud
- Multi-branch advanced
- Billing / subscriptions for SaaS
- Mobile apps (optional)
- Advanced inventory (parking, facing, view premiums)

## Definition of Done (per module)
- Migrations + models + factories
- Repository + service + policy + API resource
- Vue screens wired to API
- Pest feature tests
- Seed coverage for Tilottama Homes demo
