# UI Wireframes — Homora CRM

Inspired by HubSpot / Salesforce / Linear / Notion / Stripe / Monday — dense but calm enterprise UI with clear hierarchy, dark mode, responsive.

## Design Tokens (CSS variables)

```css
--bg: #0f1419;           /* dark surface — toggleable light */
--bg-elevated: #161b22;
--border: #2a3340;
--text: #e7ecf3;
--muted: #9aa7b8;
--brand: #1f6feb;        /* cool steel blue — not purple */
--success: #3fb950;
--warning: #d29922;
--danger: #f85149;
--font-sans: "IBM Plex Sans", ui-sans-serif, system-ui;
--font-display: "IBM Plex Sans", ui-sans-serif;
```

Light mode flips surfaces to soft slate/white with the same brand accent.

## Shell Layout

```
┌──────────────────────────────────────────────────────────┐
│ Logo Homora   [Global Search________]  🔔  Theme  Avatar │
├───────────┬──────────────────────────────────────────────┤
│ Dashboard │  Page title                    Primary CTA   │
│ Leads     │──────────────────────────────────────────────│
│ Customers │  Filters / tabs                              │
│ Pipeline  │                                              │
│ Projects  │  Main content (table / kanban / calendar)    │
│ Visits    │                                              │
│ Tasks     │                                              │
│ Payments  │                                              │
│ Docs      │                                              │
│ Marketing │                                              │
│ Reports   │                                              │
│ Settings  │                                              │
└───────────┴──────────────────────────────────────────────┘
```

## Screens (MVP)

### 1. Login
Centered brand mark **Homora**, email/password, “Sign in”, subtle gradient atmosphere + abstract city/property silhouette (full-bleed background). No cards-heavy clutter.

### 2. Dashboard
KPI strip (today’s leads, conversion, revenue, outstanding) + chart (leads over time) + “Today’s follow-ups” list + top sources. One purpose: situational awareness.

### 3. Leads Table
Columns: Name, Phone, Source, Status, Assigned, Priority, AI Score, Updated. Row → detail drawer.

### 4. Pipeline Kanban
Columns for each stage; drag cards; card shows name, budget, assignee avatar, score.

### 5. Lead Detail
Header identity + status select; tabs: Timeline | Notes | Tasks | Visits | Documents | AI Assist.

### 6. Projects / Inventory
Project list → project detail with building/floor/unit tree + availability matrix.

### 7. Calendar (Visits)
Week/month toggle; visit chips; click → visit drawer with check-in + report.

### 8. Payments
Outstanding table + booking schedule view; record payment modal.

### 9. Documents
Typed list with version badge; upload dropzone.

### 10. Settings
Org profile, users & roles, automation rules, notification prefs.

## Motion (intentional)

1. Sidebar active indicator slide  
2. Kanban card lift + drop ease  
3. KPI number count-up on dashboard load  

## Mobile

Bottom nav: Home, Leads, Tasks, More. Tables become stacked cards; Kanban horizontal scroll.
