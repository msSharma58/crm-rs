# Product Requirements Document
# Homora CRM — Enterprise Real Estate CRM (Multi-Tenant SaaS)

**Version:** 1.0  
**First Customer:** Tilottama Homes  
**Product Codename:** Homora CRM  
**Status:** Approved for MVP + Platform Foundation  

---

## 1. Vision

Homora CRM is the central operating system for real estate businesses. It manages the full customer journey from lead capture through booking, payments, documents, and after-sales support — designed as multi-tenant SaaS so it can be sold to many companies after Tilottama Homes.

## 2. Goals

| Goal | Success Metric |
|------|----------------|
| Unify sales operations | 100% of leads tracked in CRM within 30 days of go-live |
| Increase conversion | +15% lead→booking conversion vs. spreadsheet baseline |
| Reduce follow-up leakage | <5% overdue follow-ups without escalation |
| Multi-tenant readiness | New company onboardable via admin without code changes |
| Auditability | All critical mutations logged with actor + timestamp |

## 3. Non-Goals (MVP)

- Native mobile apps (responsive web first)
- Full accounting / ERP replacement
- Public property marketplace
- Multi-currency FX engine (single currency per organization)

## 4. Personas & Roles

| Role | Primary Jobs |
|------|----------------|
| Super Admin | Platform ops, tenant provisioning, global settings |
| Company Owner | Org KPIs, users, billing of seats, approvals |
| Sales Manager | Pipeline, assignments, coaching, forecasts |
| Sales Executive | Daily lead work, visits, follow-ups, bookings |
| Marketing | Campaigns, sources, ROI, lead intake quality |
| Reception | Walk-ins, call logging, visit scheduling |
| Accountant | Payments, receipts, outstanding, refunds, commissions |
| Document Officer | KYC, agreements, versioned document packs |
| Viewer | Read-only dashboards and reports |

## 5. Multi-Tenancy Model

- **Shared database, `organization_id` on every tenant row**
- Subdomain or header-based tenant resolution (`X-Organization-Slug` / Sanctum session)
- Row-level isolation enforced in Eloquent global scopes + policies
- Super Admin operates outside tenant scope

## 6. Customer Journey (Core Flows)

```
Lead Generation → Qualification → Property Matching → Follow-up
→ Site Visit → Negotiation → Booking → Payment Tracking
→ Document Management → Deal Closed → After Sales Support
```

### 6.1 Lead Capture Sources
Facebook Lead Ads, Website, Landing Pages, WhatsApp, Phone, Manual, Referral, Walk-in, CSV Import

### 6.2 Pipeline Stages
`new` → `contacted` → `interested` → `qualified` → `site_visit_scheduled` → `visited` → `negotiation` → `booking` → `payment_pending` → `sold` | `lost` | `cancelled`

Kanban drag-and-drop with audit trail on stage changes.

## 7. Functional Requirements by Module

### 7.1 Authentication & Users
- Email/password login via Laravel Sanctum (SPA cookie + API tokens)
- Invite users, deactivate, password reset
- 2FA-ready (TOTP fields + recovery codes stub)
- Role-based access control (RBAC) with permission matrix

### 7.2 Organization
- Company profile, branding, timezone, currency, locale
- Branches / offices (optional)
- Subscription status stub for future billing

### 7.3 Leads
- CRUD, bulk import, assignment, tags, priority, notes, timeline
- AI lead score field (computed async)
- Duplicate detection by phone/email within org

### 7.4 Customers
- Converted from leads or created directly
- Family members, occupation, interests, communication history

### 7.5 Properties / Projects
- Hierarchy: Project → Building → Floor → Unit
- Availability, price, media, amenities, map location, brochures

### 7.6 Sales / Bookings
- Link customer + unit + commercial terms
- Negotiation notes, booking status lifecycle

### 7.7 Visits
- Schedule, calendar views, assigned executive, check-in, outcome report

### 7.8 Follow-ups & Tasks
- Automatic reminders (queue jobs)
- Daily follow-up dashboard
- Tasks with due date, priority, comments, attachments

### 7.9 Documents
- Typed documents (citizenship, passport, PAN, agreements, receipts, contracts, property files)
- Version history, soft delete

### 7.10 Payments
- Booking amount, installments, due dates, receipts, outstanding, refunds, commissions

### 7.11 Marketing
- Campaigns linked to sources; cost, leads, conversion, ROI

### 7.12 Automation
- Rules: assign lead, welcome WhatsApp/email stub, create follow-up, notify, escalate inactive/overdue

### 7.13 AI (Phase 2 stubs in MVP)
- Lead score, follow-up suggestions, conversation summary, message generators, sentiment, property recommend, forecast — interfaces + queued jobs with mock providers

### 7.14 Dashboard & Reports
- KPIs: today’s/monthly leads, conversion, revenue, bookings, lost, pending payments, performance, sources, ROI
- Exports: PDF / Excel (queued)

### 7.15 Notifications & Search
- In-app + email; WhatsApp/SMS provider interfaces
- Global search across customers, phone, email, property, booking, invoice, document

### 7.16 Security & Audit
- Policies, rate limiting, soft deletes, encrypted sensitive fields where needed, audit logs

## 8. Non-Functional Requirements

| Area | Requirement |
|------|-------------|
| Performance | List APIs p95 < 400ms for 10k leads/org with indexes |
| Availability | Stateless app; Redis queue; horizontal workers |
| Security | RBAC, Sanctum, CSRF for SPA, audit log |
| Extensibility | Modular domains, repository + service layers |
| Observability | Structured logs, Horizon for queues |
| i18n | English first; Nepali-ready locale keys |

## 9. MVP Scope (Phase 1)

Must ship:
1. Multi-tenant org + users + RBAC
2. Leads + Kanban pipeline
3. Customers
4. Projects / Units
5. Visits + Follow-ups + Tasks
6. Bookings + Payments basics
7. Documents
8. Dashboard KPIs
9. Audit logs
10. Docker + CI skeleton
11. Tilottama Homes seed data

Deferred polish: deep AI, live Facebook sync, WhatsApp Business Cloud production wiring (interfaces + stubs only).

## 10. Acceptance Criteria (MVP)

- Company Owner can invite Sales Executive and restrict permissions
- Lead can move through all pipeline stages with history
- Site visit can be scheduled and completed with outcome
- Booking creates payment schedule; outstanding updates correctly
- Documents attach to customer/booking with version bump
- Tenant A cannot read Tenant B data (feature tests)
- Dashboard reflects seeded Tilottama Homes metrics

## 11. Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| Scope explosion | Strict Phase 1 cut; stubs for AI/channels |
| Tenant leak | Global scopes + policy tests |
| Channel integrations | Provider interfaces; mock in MVP |
| Data model churn | Document ERD; migration-first |

## 12. Success Definition for Tilottama Homes

Homora CRM becomes the single source of truth for leads, inventory, site visits, bookings, and collections — replacing spreadsheets for the sales floor within the first operating month.
