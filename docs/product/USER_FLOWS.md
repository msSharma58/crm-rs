# User Flows — Homora CRM

## 1. Lead Capture → Assignment

```mermaid
flowchart TD
  A[Lead arrives: Web/FB/WhatsApp/Call/Walk-in/CSV] --> B{Duplicate phone/email?}
  B -->|Yes| C[Merge suggestion / attach activity]
  B -->|No| D[Create Lead status=new]
  D --> E[Automation: round-robin or rule assign]
  E --> F[Notify Sales Executive]
  F --> G[Create welcome follow-up task]
```

## 2. Qualification → Site Visit

```mermaid
flowchart TD
  A[Executive contacts lead] --> B[Update status Contacted/Interested]
  B --> C{Budget + project fit?}
  C -->|No| D[Mark Lost + reason]
  C -->|Yes| E[Qualified]
  E --> F[Match units]
  F --> G[Schedule Site Visit]
  G --> H[Calendar + reminders]
  H --> I[Check-in + Visit Report]
  I --> J[Visited]
```

## 3. Negotiation → Booking → Payment

```mermaid
flowchart TD
  A[Visited] --> B[Negotiation]
  B --> C[Agree commercial terms]
  C --> D[Create Booking + reserve Unit]
  D --> E[Generate payment schedule]
  E --> F[Collect booking amount]
  F --> G[Payment Pending / receipts]
  G --> H{All dues clear?}
  H -->|No| I[Outstanding dashboard]
  H -->|Yes| J[Sold + after-sales tasks]
```

## 4. Document Pack

```mermaid
flowchart TD
  A[Booking created] --> B[Document Officer checklist]
  B --> C[Upload Citizenship/PAN/Agreement]
  C --> D[Version if revised]
  D --> E[Link to customer + booking]
```

## 5. Manager Daily Loop

```mermaid
flowchart TD
  A[Open Dashboard] --> B[Review today's leads + overdue follow-ups]
  B --> C[Reassign / escalate]
  C --> D[Inspect pipeline Kanban]
  D --> E[Coach executives on stuck deals]
```

## 6. Role Entry Points

| Role | Home Screen |
|------|-------------|
| Company Owner | Executive dashboard |
| Sales Manager | Pipeline + team performance |
| Sales Executive | My leads + today's follow-ups |
| Marketing | Campaigns + ROI |
| Reception | Quick lead / visit create |
| Accountant | Outstanding payments |
| Document Officer | Pending document checklist |
| Viewer | Read-only reports |
