# Meta Facebook + WhatsApp Sync

Homora CRM ingests Facebook Lead Ads and WhatsApp Cloud messages per organization.

## Endpoints

| Purpose | URL |
|---------|-----|
| Facebook verify + events | `GET/POST /api/v1/webhooks/facebook` |
| WhatsApp verify + events | `GET/POST /api/v1/webhooks/whatsapp` |
| Org settings | `GET/PUT /api/v1/integrations/{facebook\|whatsapp}` |
| Form backfill | `POST /api/v1/integrations/facebook/sync-form` |
| Lead thread / send | `GET/POST /api/v1/leads/{id}/messages` · `/whatsapp` |

## Setup (Meta Developer App)

1. Create a Meta app with **Webhooks**, **Lead Ads**, and **WhatsApp** products.
2. In CRM **Settings**, activate Facebook and paste:
   - Page ID
   - Page Access Token (leads_retrieval)
   - Webhook verify token (must match Meta callback verify token)
3. Point Meta Facebook webhook callback to the Facebook webhook URL shown in Settings.
   Subscribe to `leadgen`.
4. Activate WhatsApp in Settings with:
   - Phone Number ID
   - Permanent / system user Access Token
   - Same or dedicated verify token
5. Point Meta WhatsApp webhook to the WhatsApp webhook URL; subscribe to `messages`.
6. Run a queue worker (`php artisan queue:work`) — webhook handlers are queued.
7. Optional: enter a Lead Form ID and click **Sync Form** to backfill existing leads.

## Env defaults

```env
META_APP_ID=
META_APP_SECRET=
META_WEBHOOK_VERIFY_TOKEN=homora_meta_verify
META_GRAPH_VERSION=v21.0
```

Per-org tokens are stored encrypted in `integration_settings.credentials`.

## Behavior

- Facebook `leadgen` → Graph fetch field_data → create lead (`source=facebook`, `external_id=leadgen_id`).
- WhatsApp inbound text → find/create lead by phone → store `integration_messages` + activity.
- Outbound WhatsApp from lead detail uses the org WhatsApp Cloud token.
