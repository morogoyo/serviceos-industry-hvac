# Industry Plugin — Client Questionnaire

_Answers for the HVAC industry module — implemented._

---

## 1. Industry Basics

- **Industry name:** HVAC
- **Industry slug:** hvac
- **Primary icon:** ac_unit
- **Brief description:** HVAC field service management with checklists, service reports, pipeline tracking, and an Elementor-integrated field checklist shortcode for public-facing technician submissions.

---

## 2. Service Categories

| # | Category Name            | Singular Label    | Icon         | Color    |
|---|--------------------------|-------------------|--------------|----------|
| 1 | AC & Cooling             | AC Job            | ac_unit      | #1565c0  |
| 2 | Heating                  | Heating Job       | whatshot     | #d84315  |
| 3 | Repair & Diagnostics     | Repair            | handyman     | #e65100  |
| 4 | Air Quality              | IAQ Job           | air          | #2e7d32  |
| 5 | Ductwork                 | Duct Job          | layers       | #795548  |
| 6 | Thermostats & Controls   | Controls Job      | sensors      | #00695c  |
| 7 | Maintenance Plans        | Maintenance       | event_repeat | #283593  |
| 8 | Commercial HVAC          | Commercial Job    | business     | #37474f  |
| 9 | New Construction         | New Build         | construction | #bf360c  |
|10 | General                  | General           | folder       | #0073aa  |

---

## 3. Pipeline Stages

### Pipeline 1: HVAC Sales Pipeline

| Order | Stage Name       |
|-------|------------------|
| 0     | Lead             |
| 1     | Qualified        |
| 2     | Site Survey      |
| 3     | Quote Sent       |
| 4     | Negotiation      |
| 5     | Contract Signed  |
| 6     | Permitting       |
| 7     | Equipment Ordered|
| 8     | Installation     |
| 9     | Inspection       |
|10     | Final Walkthrough|
|11     | Completed        |

### Pipeline 2: Service & Repair Pipeline

| Order | Stage Name    |
|-------|---------------|
| 0     | Scheduled     |
| 1     | Dispatched    |
| 2     | Diagnosed     |
| 3     | Repair Complete|
| 4     | Invoiced      |
| 5     | Paid          |

---

## 4. Deal Milestones (optional)

Uses CRM defaults: 25% = "Kickoff", 50% = "Midpoint", 75% = "Final Review", 100% = "Complete".

---

## 5. Seed Services

### AC & Cooling (pipeline: HVAC Sales Pipeline, stage: Quote Sent unless noted)
| Service Title                    | Value ($) |
|----------------------------------|-----------|
| Central AC Install (3-ton)       | 6,500     |
| Central AC Install (5-ton)       | 9,000     |
| Heat Pump Install                | 8,500     |
| Mini-Split Single Zone           | 4,000     |
| Mini-Split Multi-Zone            | 10,000    |
| Condenser Replacement            | 3,500     |
| Evaporator Coil Replacement      | 2,200     |

### Heating
| Service Title                    | Value ($) |
|----------------------------------|-----------|
| Gas Furnace Install (80% AFUE)   | 4,000     |
| Gas Furnace Install (95%+ AFUE)  | 6,500     |
| Electric Furnace Install         | 3,000     |
| Boiler Install                   | 8,000     |
| Heat Exchanger Replacement       | 2,500     |

### Repair & Diagnostics
| Service Title                    | Value ($) |
|----------------------------------|-----------|
| Diagnostic Service Call          | 99        |
| Capacitor Replacement            | 300       |
| Blower Motor Replacement         | 1,000     |
| Compressor Replacement           | 2,500     |
| Refrigerant Leak Repair          | 800       |
| Emergency After-Hours Service    | 249       |

### Air Quality
| Service Title                    | Value ($) |
|----------------------------------|-----------|
| Whole-Home Air Purifier          | 1,500     |
| Humidifier Install               | 800       |
| ERV/HRV Install                  | 3,000     |
| Dehumidifier Install             | 1,800     |

### Ductwork
| Service Title                    | Value ($) |
|----------------------------------|-----------|
| Duct Replacement (Full Home)     | 5,500     |
| Duct Cleaning                    | 800       |
| Duct Sealing                     | 1,200     |
| Duct Insulation                  | 2,000     |

### Thermostats & Controls
| Service Title                    | Value ($) |
|----------------------------------|-----------|
| Smart Thermostat Install         | 500       |
| Zoning System Install            | 3,500     |
| Wi-Fi Thermostat Install         | 350       |

### Maintenance Plans
| Service Title                    | Value ($) |
|----------------------------------|-----------|
| AC Tune-Up                       | 159       |
| Furnace Tune-Up                  | 159       |
| Annual Plan (1 Visit)            | 299       |
| Annual Plan (2 Visits)           | 449       |

### Commercial HVAC
| Service Title                    | Value ($) |
|----------------------------------|-----------|
| Rooftop Unit Install (5-ton)     | 25,000    |
| VRF System Install               | 40,000    |
| Rooftop Unit Repair              | 3,500     |

### New Construction
| Service Title                    | Value ($) |
|----------------------------------|-----------|
| New Build HVAC Rough-In          | 8,000     |
| New Build Full HVAC System       | 12,000    |

Total: 38 seed services across 9 categories.

---

## 6. Custom Requirements

- **Need custom database tables?** [x] Yes [ ] No
  - **hvac_submissions** — stores checklist submission metadata
    - id (BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY)
    - ji_contract, ji_wo, ji_property, ji_date, ji_tech, ji_visit
    - uuid (VARCHAR 36 UNIQUE), property_address, date_of_service, technician_name, work_order, contract_number, visit_type, raw_json
    - technician_id (BIGINT), client_id (BIGINT), created_at (DATETIME)
    - Indexes: date_of_service, technician_name, property_address
  - **hvac_unit_items** — per-unit checklist item rows
    - id (BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY)
    - submission_id (BIGINT, FK → hvac_submissions ON DELETE CASCADE), unit_number (INT)
    - equipment_type (VARCHAR), serial_number (VARCHAR, INDEXED), model_number (VARCHAR)
    - checks_json (LONGTEXT), sup, ret, dt, fs, notes, init, status
    - Indexes: submission_id, serial_number
  - **hvac_signoffs** — final sign-off checklist items
    - id (BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY)
    - submission_id (BIGINT, FK → hvac_submissions ON DELETE CASCADE)
    - signoff_type (VARCHAR), printed_name (VARCHAR), signature_data (LONGTEXT), signed_at (DATETIME)
    - Index: submission_id

- **Need custom REST API endpoints?** [x] Yes [ ] No
  - `POST /wp-json/crm/v1/hvac/checklist-submit` — accepts JSON checklist payload, saves to DB, auto-creates CRM deal, sends email notification. Returns `{success, message, submission_id, deal_id}`. Public (no auth required, nonce-verified).
  - `GET /wp-json/crm/v1/hvac/submissions` — paginated list of submissions (supports `page` and `per_page` params).
  - `GET /wp-json/crm/v1/hvac/submissions/{id}` — single submission with nested units and signoffs.
  - `GET /wp-json/crm/v1/hvac/client-search?q=...` — public client search with debounced LIKE query.

- **Need custom page sections beyond the standard schema?** [ ] Yes [x] No
  - Uses standard sections: info_table, unit_overview, expandable_units, signoffs, data_table, html.

---

## 7. Standalone Plugin Redirect (optional)

- **Standalone page slug:** N/A (checklist is rendered via `[hvac_checklist]` shortcode / Elementor widget on public-facing pages)

---

## 8. Additional Notes

- Checklist supports dynamic unit count (1-99, configurable via shortcode `units` attribute or Elementor widget).
- An Elementor widget (`HVAC_Checklist_Widget`) wraps the shortcode for drag-and-drop page builder use, with REPEATER controls for configurable checklist items.
- Email notifications on checklist submission are sent via `wp_mail` to a configurable recipient (stored in `hvac_settings` option).
- Default checklist items: Evaporator coil inspect, condensate drain flush, control systems inspect, contactors check, air filter replace, condenser fan inspect, refrigerant check, leak inspection, system performance, service report — all configurable via Elementor REPEATER.
- Submission delete handler with nonce verification available from admin detail view.
- Client search dropdown powered by public REST endpoint with 250ms debounce.
- Auto-create CRM deal on checklist submission in "Service & Repair Pipeline" at first stage.
- Technician name auto-filled from current WP user when logged in.
