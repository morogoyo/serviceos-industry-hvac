# HVAC Plugin — Implementation Plan

_Adopted: 2026-07-09_

---

## 🛠️ PART 1: Operational Architecture & Guardrails

### 1. Database Schema Plan (The Master-Detail Model)

To avoid lazy table creation on first write—which causes severe crash loops on administrative read screens—`class-activator.php` must strictly provision three clean tables upon plugin activation:

**`{$wpdb->prefix}hvac_submissions` (Parent):** Holds context and integration keys.

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT | Primary Key, Auto-Increment |
| ji_contract | VARCHAR(100), nullable | External Contract ID mapping |
| ji_wo | VARCHAR(100), nullable | External Work Order reference mapping |
| technician_id | BIGINT | WordPress/CRM User ID assignment |
| client_id | BIGINT, nullable | Associated native ServiceOS Client record |
| created_at | DATETIME | |

**`{$wpdb->prefix}hvac_unit_items` (Child):** Individual rows corresponding to equipment iterations.

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT | Primary Key, Auto-Increment |
| submission_id | BIGINT | Foreign Key referencing Parent |
| unit_number | INT | Sequence tracking integer |
| equipment_type | VARCHAR(100) | |
| serial_number | VARCHAR(100), Indexed | Vital for the equipment tracking history ledger |
| model_number | VARCHAR(100) | |
| checks_json | LONGTEXT | Serialized or JSON-stringified multi-point validation flags |

**`{$wpdb->prefix}hvac_signoffs` (Signatures):** Physical authentication/authorization details.

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT | Primary Key, Auto-Increment |
| submission_id | BIGINT | Foreign Key referencing Parent |
| signoff_type | VARCHAR(50) | 'technician' or 'customer' |
| printed_name | VARCHAR(255) | |
| signature_data | LONGTEXT | Base64 encoded vector or raster image string |
| signed_at | DATETIME | |

### 2. Git Branching Strategy

The implementation must adhere strictly to isolated feature lifecycles off the `dev` trunk:

| Branch | Scope |
|--------|-------|
| `feature/hvac-db-seeder` | Schema deployment + CRM metadata injection |
| `feature/hvac-public-api` | REST Endpoint ingestion + core CRM Deal creation |
| `feature/hvac-frontend-core` | Headless shortcode orchestration + Elementor settings |
| `feature/hvac-admin-harness` | Dashboard reporting + detail layout compilation |

### 3. Core Framework Guardrails

- **No Hardcoded Strings Rule:** All taxonomy selections, pipeline lookups, and assignment arrays must map directly via `ServiceOSAPI` models. No hardcoded placeholders.
- **Theme Continuity:** Any front-end template or dashboard section must consume standard native CRM CSS variables (e.g., `--primary`, `--surface`, `--card-bg`). Never emit explicit hex colors.
- **SQL Safety Enforcement:** Every database access layer transaction must wrap strings explicitly using `$wpdb->prepare()`, matching placeholder markers perfectly.

---

## 📋 PART 2: Coding Prompts

---

### 🛠️ Prompt 1: Database Provisioning & Seed Infrastructure

**Target Branch:** `feature/hvac-db-seeder`

> Context: You are the ServiceOS HVAC developer agent. Review `class-activator.php` and `class-seeder.php` within the hvac snapshot.

**Task:**

1. Refactor `includes/class-public.php` to define a clean table schema array inside a public method `create_tables()`. Use `$wpdb->get_charset_collate()` and `dbDelta`. Implement three tables explicitly:
   - `wp_hvac_submissions` (id, ji_contract, ji_wo, technician_id, client_id, created_at)
   - `wp_hvac_unit_items` (id, submission_id, unit_number, equipment_type, serial_number, model_number, checks_json)
   - `wp_hvac_signoffs` (id, submission_id, signoff_type, printed_name, signature_data, signed_at)
   Ensure `serial_number` is indexed for rapid historical equipment lookup.

2. Update `includes/class-activator.php` so its `activate()` method explicitly runs `Public_Checklist::create_tables()` immediately on activation to prevent admin harness read path crashes before a write occurs.

3. Update `includes/class-seeder.php` to fully seed the 10 service categories, 2 pipelines (12-stage sales and 6-stage service), and 36 services via the core `ServiceOSAPI_Base_Model` framework.

**Constraints:** Ensure every single `$wpdb->prepare` call maps to appropriate string (`%s`) or integer (`%d`) tokens correctly. Do not write hardcoded placeholders.

---

### 🛠️ Prompt 2: REST Controller & CRM Deal Linkage

**Target Branch:** `feature/hvac-public-api`

> Context: Review `class-public.php` and the core CRM's REST controller design.

**Task:**

1. Build out the complete REST registration logic inside `includes/class-public.php` for `POST /wp-json/crm/v1/hvac/checklist-submit`.

2. Write the submission handler method to execute the following atomic transactional workflow:
   - Accept the nested JSON object containing global meta (ji_contract, ji_wo, technician_id) and an array of individual inspection items.
   - Insert the parent record into `wp_hvac_submissions`. Retrieve the insert ID.
   - Loop through the equipment units array and unpack serial numbers, model variations, and serialized validation checkboxes into `wp_hvac_unit_items`.
   - If an asset with that `serial_number` does not exist in the CRM, register it; if it does, append this entry to its history.
   - Initialize a new core CRM deal using `new ServiceOSAPI_Deal()`. Set its tracking target pipeline id to the newly seeded HVAC service pipeline and default its status/stage flag to 'lead'. Save the relationship.
   - Dispatch the HTML summary email via an internal trigger to `includes/class-email.php`.

**Constraints:** Return a structured `WP_REST_Response` with the internal Parent Submission ID and newly generated Lead ID on success. Sanitize every incoming parameter meticulously.

---

### 🛠️ Prompt 3: Shortcode Engine & Elementor Dynamic Cockpit

**Target Branch:** `feature/hvac-frontend-core`

> Context: Review `checklist-core.js`, `checklist-step-1.js`, `checklist-step-2.js`, and `class-hvac-checklist-widget.php`.

**Task:**

1. Program the shortcode handler `[hvac_checklist]` inside `includes/class-public.php` to serve as an intelligent headless wrapper. It must sniff out active dynamic runtime contexts:
   - Check if an explicit shortcode attribute exists: `[hvac_checklist ji_wo="1024"]`.
   - If empty, check for global query-string variables: `$_GET['wo_id']` or `$_GET['contract_id']`.
   - If found, auto-fill the field locks in step-1 of the form template.

2. Refactor `widgets/class-hvac-checklist-widget.php` to properly expose Elementor control fields. Add toggle controls for 'Allow Work Order Override' and 'Enforce Assignment Lock'.

3. Map Elementor's render function to directly return `do_shortcode('[hvac_checklist ...]')`, piping the configured elementor control settings seamlessly into the shortcode attributes array.

**Constraints:** Ensure front-end JavaScript scripts are cleanly localized with WP nonces, active REST API base URLs, and contextual operational tags using `wp_localize_script` inside `class-assets.php`. Use the standard CRM CSS variables exclusively.

---

### 🛠️ Prompt 4: Administrative Reporting & Harness Visualizer

**Target Branch:** `feature/hvac-admin-harness`

> Context: Review `class-harness.php` from the HVAC snapshot and the shared page partial requirements from the CRM core snapshot.

**Task:**

1. Complete the implementation of the 4 dedicated admin interfaces inside `includes/class-harness.php`:
   - **Dashboard:** Aggregate metrics from `wp_hvac_submissions` (e.g., total installations, open leads generated in the pipeline).
   - **Submissions List:** Render a tabular layout showing ji_contract, ji_wo, technician names, and timestamps.
   - **Detail View:** Build an advanced inspection record interface layout utilizing section components like info_table, unit_overview, expandable_units, and signoffs.

2. Secure data handling: Use explicit SQL pagination on the list screen and guarantee that the detail layout accurately maps base64 signatures directly out of the `wp_hvac_signoffs` data rows.

**Constraints:** Every page template file must tightly execute the shared template layout mechanism: require `layout-start.php` at the header and `layout-end.php` at the footer. Never break the unified CRM admin shell hierarchy.
