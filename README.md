# ServiceOS HVAC

HVAC-specific module for [ServiceOS CRM](https://github.com/morogoyo/wp_crm_general) — service categories, pipeline stages, field checklists, and an Elementor-integrated public-facing checklist.

## Features

- **10 service categories**: AC & Cooling, Heating, Repair & Diagnostics, Air Quality, Ductwork, Thermostats & Controls, Maintenance Plans, Commercial HVAC, New Construction, General
- **2 pipelines**: 12-stage "HVAC Sales Pipeline" + 6-stage "Service & Repair Pipeline"
- **38 seed services** across all categories with realistic pricing
- **Dashboard & detail pages** for the service catalog (CRM admin)
- **Field checklist system**: public-facing `[hvac_checklist]` shortcode with configurable checklist items, dynamic unit count (1-99), client search dropdown, add/remove units in the field
- **Elementor widget**: `HVAC_Checklist_Widget` with REPEATER for configurable checklist items, brand colors, and operational mode controls
- **REST API**: `POST /wp-json/crm/v1/hvac/checklist-submit` accepts JSON, persists to DB, auto-creates CRM deal, sends email. Additional endpoints for submissions list, submission detail, and client search
- **Email notifications**: HTML email reports sent on checklist submission
- **CRM integration**: sidebar navigation, page rendering, CSS/JS inheritance, API access, submission deletion with nonce verification
- **Database**: FOREIGN KEY constraints with ON DELETE CASCADE, schema migration system with column-aware writes

## Requirements

- WordPress 6.0+
- [ServiceOS CRM](https://github.com/morogoyo/wp_crm_general) plugin installed and active
- PHP 7.0+

## File Structure

```
├── serviceos-industry-hvac.php        # Main plugin file
├── includes/
│   ├── class-harness.php              # CRM harness (4 pages: dashboard, detail, submissions, submission-detail)
│   ├── class-activator.php            # Activation/deactivation hooks + table creation
│   ├── class-seeder.php               # Seeds 10 categories, 2 pipelines, 38 services
│   ├── class-assets.php               # Admin CSS/JS enqueuing + localized config
│   ├── class-email.php                # Checklist submission email notifications
│   ├── class-public.php               # Shortcode [hvac_checklist], REST routes, DB schema, save logic, migrations
│   └── widgets/
│       └── class-hvac-checklist-widget.php  # Elementor widget with REPEATER controls
├── templates/
│   └── email-report.php               # HTML email template for checklist reports
├── assets/
│   ├── css/
│   │   ├── module.css                 # CRM admin styles (CRM CSS variables)
│   │   └── checklist.css              # Front-end checklist styles
│   └── js/
│       ├── module.js                  # CRM admin JS (new service modal, create deal, API calls)
│       ├── checklist-core.js          # Shared checklist logic (dynamic items, add/remove units, REST submit)
│       └── checklist-init.js          # DOM-driven initializer (reads data-* attributes, replaces per-count scripts)
├── QUESTIONNAIRE.md                   # Client requirements (filled in)
├── AGENTS.md                          # Agent guidelines
└── README.md                          # This file
```

## Custom Database Tables

| Table | Purpose |
|-------|---------|
| `{prefix}hvac_submissions` | Checklist submission headers (property, tech, date, etc.) |
| `{prefix}hvac_unit_items` | Per-unit checklist items (checked status, temps, notes, initials) |
| `{prefix}hvac_signoffs` | Final sign-off items per submission |

Created automatically on plugin activation and verified on each REST submission.

## Shortcode

```
[hvac_checklist]
[hvac_checklist units="10"]
[hvac_checklist units="52"]
[hvac_checklist units="10" max_units="99" allow_unit_add_remove="1"]
```

Dynamic unit count (1-99). Configure via Elementor widget or shortcode attrs. Checklist items are configurable through the Elementor REPEATER control or passed via `items` JSON attribute. Supports `client_source`, `ji_wo`, `ji_contract`, `allow_wo_override`, and `enforce_assignment_lock` attributes.

## REST API

```
POST /wp-json/crm/v1/hvac/checklist-submit
Content-Type: application/json
X-WP-Nonce: {wp_rest_nonce}

{
  "ji_property": "...",
  "ji_date": "2026-01-01",
  "ji_tech": "...",
  "ji_wo": "...",
  "ji_contract": "...",
  "ji_visit": "Quarterly",
  "unit_count": 10,
  "units": [
    {
      "num": 1,
      "checks": [true, true, true, true, true, false, false, false, false, false],
      "sup": "55",
      "ret": "70",
      "dt": "15",
      "fs": "20x20x1",
      "notes": "...",
      "init": "RT"
    }
  ],
  "signoff": [
    {"label": "All units serviced...", "checked": true}
  ]
}
```

Response: `{"success":true, "message":"Report submitted successfully.", "submission_id":1, "deal_id":5}`

Additional endpoints:
- `GET /wp-json/crm/v1/hvac/submissions` — paginated submissions list
- `GET /wp-json/crm/v1/hvac/submissions/{id}` — single submission with units and signoffs
- `GET /wp-json/crm/v1/hvac/client-search?q=...` — public client search

## Seeding Flow

```
Plugin activates
    → Creates hvac_* tables via Activator
    → CRM syncs module via serviceos_crm_available_modules filter
    → CRM fires serviceos_crm_module_seed filter
    → Seeder::seed() returns 10 categories, 2 pipelines, 38 services
    → CRM creates them in DB
    → CRM sets seed_applied = 1
```

## CSS Variables Available

All CRM CSS custom properties are available in `module.css` (admin pages) and custom properties are defined in `checklist.css` (front-end).

## Branching

- `main` — production-ready
- `dev` — integration/staging
- `feature/*` / `fix/*` — work branches

See `AGENTS.md` for full workflow and agent guidelines.
