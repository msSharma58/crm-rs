# Database ER Diagram — Homora CRM

## Entity Relationship (Mermaid)

```mermaid
erDiagram
  ORGANIZATIONS ||--o{ USERS : employs
  ORGANIZATIONS ||--o{ ROLES : defines
  ORGANIZATIONS ||--o{ LEADS : owns
  ORGANIZATIONS ||--o{ CUSTOMERS : owns
  ORGANIZATIONS ||--o{ PROJECTS : owns
  ORGANIZATIONS ||--o{ CAMPAIGNS : runs
  ORGANIZATIONS ||--o{ AUTOMATION_RULES : configures

  USERS ||--o{ MODEL_HAS_ROLES : has
  ROLES ||--o{ MODEL_HAS_ROLES : assigned
  ROLES ||--o{ ROLE_HAS_PERMISSIONS : grants
  PERMISSIONS ||--o{ ROLE_HAS_PERMISSIONS : granted

  USERS ||--o{ LEADS : assigned
  CAMPAIGNS ||--o{ LEADS : generates
  LEADS ||--o| CUSTOMERS : converts_to
  LEADS ||--o{ LEAD_NOTES : has
  LEADS ||--o{ LEAD_ACTIVITIES : timeline
  LEADS }o--o{ TAGS : tagged

  CUSTOMERS ||--o{ CUSTOMER_FAMILY_MEMBERS : has
  CUSTOMERS ||--o{ DOCUMENTS : owns
  CUSTOMERS ||--o{ BOOKINGS : books
  CUSTOMERS ||--o{ PAYMENTS : pays
  CUSTOMERS ||--o{ VISITS : attends
  CUSTOMERS ||--o{ FOLLOW_UPS : receives
  CUSTOMERS ||--o{ TASKS : related

  PROJECTS ||--o{ BUILDINGS : contains
  BUILDINGS ||--o{ FLOORS : contains
  FLOORS ||--o{ UNITS : contains
  PROJECTS ||--o{ PROJECT_MEDIA : media
  PROJECTS }o--o{ AMENITIES : offers

  UNITS ||--o{ BOOKINGS : sold_as
  BOOKINGS ||--o{ PAYMENT_SCHEDULES : has
  PAYMENT_SCHEDULES ||--o{ PAYMENTS : collects
  BOOKINGS ||--o{ DOCUMENTS : contracts
  BOOKINGS ||--o{ COMMISSIONS : earns

  VISITS ||--o{ VISIT_REPORTS : produces
  USERS ||--o{ TASKS : assigned
  TASKS ||--o{ TASK_COMMENTS : has
  DOCUMENTS ||--o{ DOCUMENT_VERSIONS : versions
  USERS ||--o{ AUDIT_LOGS : performs
  USERS ||--o{ NOTIFICATIONS : receives

  ORGANIZATIONS {
    bigint id PK
    string name
    string slug UK
    string timezone
    string currency
    json branding
    string status
    timestamps timestamps
  }

  USERS {
    bigint id PK
    bigint organization_id FK
    string name
    string email
    string phone
    string password
    boolean is_active
    json two_factor
    timestamps timestamps
  }

  LEADS {
    bigint id PK
    bigint organization_id FK
    string name
    string phone
    string email
    string location
    decimal budget
    string preferred_property
    bigint project_id FK
    string source
    bigint campaign_id FK
    string status
    bigint assigned_to FK
    string priority
    int ai_score
    timestamps timestamps
    soft_delete deleted_at
  }

  CUSTOMERS {
    bigint id PK
    bigint organization_id FK
    bigint lead_id FK
    string name
    string phone
    string email
    string occupation
    json meta
    timestamps timestamps
  }

  PROJECTS {
    bigint id PK
    bigint organization_id FK
    string name
    string code
    string location
    decimal lat
    decimal lng
    string status
  }

  UNITS {
    bigint id PK
    bigint floor_id FK
    string code
    string type
    decimal area
    decimal price
    string status
  }

  BOOKINGS {
    bigint id PK
    bigint organization_id FK
    bigint customer_id FK
    bigint unit_id FK
    bigint sales_executive_id FK
    string status
    decimal booking_amount
    decimal total_amount
    date booked_at
  }

  PAYMENTS {
    bigint id PK
    bigint organization_id FK
    bigint booking_id FK
    bigint schedule_id FK
    decimal amount
    string method
    string status
    date paid_at
    string receipt_no
  }
```

## Core Tables (MVP)

| Table | Purpose |
|-------|---------|
| `organizations` | Tenants |
| `users` | People (scoped + super admins) |
| `roles`, `permissions`, pivots | RBAC |
| `leads`, `lead_notes`, `lead_activities`, `tags`, `taggables` | Lead CRM |
| `customers`, `customer_family_members` | Customer 360 |
| `projects`, `buildings`, `floors`, `units`, `amenities`, `amenity_project`, `media` | Inventory |
| `campaigns` | Marketing |
| `visits`, `visit_reports` | Site visits |
| `follow_ups` | Reminders |
| `tasks`, `task_comments` | Task mgmt |
| `documents`, `document_versions` | DMS |
| `bookings`, `payment_schedules`, `payments`, `refunds`, `commissions` | Sales & money |
| `automation_rules`, `automation_runs` | Automation |
| `notifications` | In-app |
| `audit_logs` | Security trail |
| `personal_access_tokens` | Sanctum |
| `jobs`, `failed_jobs`, `job_batches` | Queues |

## Indexing Strategy

- `(organization_id, status)` on leads, bookings, payments
- `(organization_id, phone)`, `(organization_id, email)` on leads/customers
- `(assigned_to, status, due_at)` on follow_ups/tasks
- `(organization_id, created_at)` for dashboard ranges
