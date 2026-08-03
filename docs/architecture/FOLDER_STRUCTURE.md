# Folder Structure — Homora CRM

```
homora-crm/
├── .github/workflows/
│   ├── ci.yml
│   └── deploy.yml
├── docker/
│   ├── nginx/default.conf
│   ├── php/Dockerfile
│   └── supervisor/horizon.conf
├── docs/
│   ├── product/PRD.md
│   ├── architecture/
│   ├── api/
│   ├── ui/
│   └── roadmap/
├── app/
│   ├── Core/
│   │   ├── Tenancy/
│   │   ├── Audit/
│   │   ├── Support/
│   │   └── Http/Middleware/
│   ├── Modules/
│   │   ├── Auth/
│   │   ├── Organization/
│   │   ├── Users/
│   │   ├── Roles/
│   │   ├── Leads/
│   │   ├── Customers/
│   │   ├── Projects/
│   │   ├── Sales/
│   │   ├── Visits/
│   │   ├── FollowUps/
│   │   ├── Tasks/
│   │   ├── Documents/
│   │   ├── Payments/
│   │   ├── Marketing/
│   │   ├── Automation/
│   │   ├── Ai/
│   │   ├── Notifications/
│   │   ├── Reports/
│   │   ├── Search/
│   │   └── Settings/
│   ├── Providers/
│   └── ...
├── bootstrap/
├── config/
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── resources/
│   ├── js/
│   │   ├── app.ts
│   │   ├── router/
│   │   ├── stores/
│   │   ├── layouts/
│   │   ├── components/
│   │   ├── modules/
│   │   ├── lib/
│   │   └── types/
│   ├── css/
│   └── views/
├── routes/
│   ├── web.php
│   ├── api.php
│   └── console.php
├── tests/
│   ├── Feature/
│   └── Unit/
├── docker-compose.yml
├── composer.json
├── package.json
├── vite.config.ts
├── phpunit.xml / Pest.php
└── README.md
```
