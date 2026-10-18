# HVAC Plugin — Implementation Plan

_Adopted: 2026-07-09 | Completed: 2026-07-27_

> **Status: ALL PROMPTS COMPLETE.** See session learnings in AGENTS.md for implementation details, bug fixes, and architectural decisions made during development.

## Completion Summary

| Prompt | Branch | Merged | Key Deliverables |
|--------|--------|:------:|------------------|
| #1: DB Provisioning & Seeding | `feature/hvac-db-seeder` | ✅ | 3 tables with FK CASCADE, 10 categories, 2 pipelines, 38 services |
| #2: REST Controller & CRM Deals | `feature/hvac-public-api` | ✅ | 4 REST endpoints, column-aware saves, auto-create deals, equipment tracking |
| #3: Shortcode & Elementor | `feature/hvac-frontend-core` | ✅ | `[hvac_checklist]` with dynamic units, Elementor REPEATER items, client search |
| #4: Admin Harness | `feature/hvac-admin-harness` | ✅ | 4 pages (dashboard, detail, submissions list/detail), collapsible sections, delete handler |

### Post-Plan Additions
- **Dynamic Elementor checklist** — REPEATER items replace hardcoded JS array (`feature/elementor-dynamic-checklist`, merged 2026-07-27)
- **Submission delete handler** — nonce-verified deletion with redirect (`fix/delete-submission-handler`, merged 2026-07-27)
- **FK constraints** — ON DELETE CASCADE on unit_items and signoffs tables
- **Pipeline constant** — `Seeder::SERVICE_PIPELINE_NAME` replaces hardcoded string

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

### 🛠️ Prompt 1: Database Provisioning & Seed Infrastructure ✅ COMPLETE

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

### 🛠️ Prompt 2: REST Controller & CRM Deal Linkage ✅ COMPLETE

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

### 🛠️ Prompt 3: Shortcode Engine & Elementor Dynamic Cockpit ✅ COMPLETE

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

### 🛠️ Prompt 4: Administrative Reporting & Harness Visualizer ✅ COMPLETE

**Target Branch:** `feature/hvac-admin-harness`

> Context: Review `class-harness.php` from the HVAC snapshot and the shared page partial requirements from the CRM core snapshot.

**Task:**

1. Complete the implementation of the 4 dedicated admin interfaces inside `includes/class-harness.php`:
   - **Dashboard:** Aggregate metrics from `wp_hvac_submissions` (e.g., total installations, open leads generated in the pipeline).
   - **Submissions List:** Render a tabular layout showing ji_contract, ji_wo, technician names, and timestamps.
   - **Detail View:** Build an advanced inspection record interface layout utilizing section components like info_table, unit_overview, expandable_units, and signoffs.

2. Secure data handling: Use explicit SQL pagination on the list screen and guarantee that the detail layout accurately maps base64 signatures directly out of the `wp_hvac_signoffs` data rows.

**Constraints:** Every page template file must tightly execute the shared template layout mechanism: require `layout-start.php` at the header and `layout-end.php` at the footer. Never break the unified CRM admin shell hierarchy.

---

## 🔌 PART 3: Harness Connectivity Guide (For New Industry Plugins)

_Last updated: 2026-07-14 — to be merged into the skeleton `serviceos-industry-plugin` repo._

This section documents everything a new industry plugin needs to connect to the CRM harness, based on the HVAC plugin implementation and all debugging sessions.

---

### 1. Plugin Bootstrap (`serviceos-industry-{industry}.php`)

```php
<?php
// Plugin Name: ServiceOS {Industry}
// Requires Plugins: service-os-crm

define('SERVICEOS_IP_VERSION', '1.0.0');
define('SERVICEOS_IP_PATH', plugin_dir_path(__FILE__));
define('SERVICEOS_IP_URL', plugin_dir_url(__FILE__));

require_once SERVICEOS_IP_PATH . 'includes/class-activator.php';
require_once SERVICEOS_IP_PATH . 'includes/class-seeder.php';
require_once SERVICEOS_IP_PATH . 'includes/class-harness.php';
require_once SERVICEOS_IP_PATH . 'includes/class-assets.php';

// Register hooks — public routes and migrations register immediately
ServiceOS_Industry_Plugin\Public_Checklist::register();

// CRM-dependent hooks wait for plugins_loaded
add_action('plugins_loaded', function () {
    if (!class_exists('Service_OS_CRM\\Harness\\Service_OS_CRM_Harness')) return;
    ServiceOS_Industry_Plugin\Seeder::register();
    ServiceOS_Industry_Plugin\Assets::register();
    add_action('init', function () {
        (new ServiceOS_Industry_Plugin\Harness())->register_with_crm();
    }, 99);
});
```

### 2. Harness Class (`includes/class-harness.php`)

Must extend `Service_OS_CRM_Harness` with these methods:

```php
class Harness extends Service_OS_CRM_Harness {
    protected $module_slug = 'my-industry';
    protected $module_name = 'My Industry';
    protected $module_icon = 'build';          // Material icon slug
    protected $industry = 'General';
}
```

**Required methods:**
- `get_module_info()` — returns `name, slug, industry, description, menu_label, menu_icon, plugin_file, plugin_class, version`
- `get_pages()` — returns array of `['slug' => '...', 'title' => '...', 'icon' => '...']`
- `get_page_data($page_slug, $params)` — dispatches to per-page methods, returns schema arrays

**Harness REST helper** (use instead of `$wpdb` for all page data):

```php
private function call_rest($route, $params = []) {
    $request = new \WP_REST_Request('GET', '/crm/v1/' . $route);
    foreach ($params as $key => $value) {
        $request->set_param($key, $value);
    }
    $response = rest_do_request($request);
    if (is_wp_error($response)) {
        error_log('[Harness] REST error: ' . $route . ' — ' . $response->get_error_message());
        return null;
    }
    if ($response instanceof \WP_REST_Response) {
        if ($response->is_error()) return null;
        $data = $response->get_data();
    } else {
        $data = $response; // raw array (WP 6.x+)
    }
    return json_decode(wp_json_encode($data), true);
}
```

**CRM REST endpoints available:**
| Endpoint | Params | Returns |
|----------|--------|---------|
| `services` | `business_id`, `module_slug` | Service list |
| `services/{id}` | — | Single service |
| `deals` | `business_id`, `status` | Deal list |
| `categories` | `business_id` | Category list |
| `pipelines` | `business_id` | Pipeline list |

**Custom REST endpoints** for plugin-specific data should be registered in `includes/class-public.php` via `register_rest_route('crm/v1', '/{slug}/...', [...])`.

### 3. Section Types (for `get_page_data()`)

Submitted via `$data['sections'][] = [...]`. Available types:

| Type | Section Keys | Description |
|------|-------------|-------------|
| `info_table` | `label, columns[], rows[][]` | Label-value table (pairs of `<th>` + `<td>`) |
| `unit_overview` | `label, units[] (num, completion, status, status_color, notes, initials)` | Per-unit summary table |
| `expandable_units` | `label, units[] (num, checks[], check_labels[], sup_temp, ret_temp, delta_t, filter_size, notes, initials)` | Per-unit collapsible cards with checklist tables |
| `signoffs` | `label, items[] (label, checked)` | Sign-off checklist table |
| `data_table` | `label, cols[], rows[][]` | Generic data table |
| `html` | `content` | Raw HTML injected into page |

**Rule: All sections on detail/submission-detail pages MUST be collapsible.** Every section card must follow the same collapsible pattern (icon, label, badge, `expand_less` arrow with localStorage persistence). This provides a consistent UX where users can collapse any section to reduce scrolling on data-heavy pages. The `info_table`, `unit_overview`, `expandable_units`, `signoffs`, and `data_table` section types all support the collapsible wrapper — flag them with `collapsible => true` and provide a unique `collapse_key`.

**Collapsible wrapper** — any section can become collapsible by adding:
```php
'collapsible'     => true,
'collapse_key'    => 'unique-key',
'collapse_icon'   => 'material_icon_name',
'collapse_badge'  => '<span class="crm-unit-badge">3 items</span>',
```

The page renderer wraps collapsible sections in `<details class="crm-section-card crm-collapsible-section">` with localStorage persistence (same pattern as contact-detail page).

### 4. Database Tables

**Activation:** `class-activator.php` calls `create_tables()` on plugin activation.

**Migration:** `maybe_migrate_tables()` hooks to `plugins_loaded` and runs on every page load. It checks `hvac_schema_version` option and applies idempotent `ALTER TABLE` statements only if current version < target. Always use `SHOW COLUMNS` checks before each `ALTER TABLE`.

```php
$cols = $wpdb->get_col("SHOW COLUMNS FROM {$table}");
if (!in_array('new_column', $cols, true)) {
    $wpdb->query("ALTER TABLE {$table} ADD COLUMN new_column ...");
}
update_option('hvac_schema_version', SCHEMA_VERSION);
```

**Key rules:**
- Never set schema version in `create_tables()` — only `maybe_migrate_tables()` controls it
- Always `$wpdb->suppress_errors(true)` before DB operations in REST handlers
- Use column-aware INSERTs: query `SHOW COLUMNS`, only insert into columns that exist
- Handle merged schema scenarios (original questionnaire columns + migration columns coexisting)

### 5. REST Endpoints

**Registration** in `class-public.php`:
```php
add_action('rest_api_init', [__CLASS__, 'register_routes']);

register_rest_route('crm/v1', '/{slug}/endpoint', [
    'methods'             => 'GET',      // or POST
    'callback'            => [$this, 'handler'],
    'permission_callback'  => '__return_true',  // for internal use only
]);
```

**POST endpoints** (form submissions) — use `__return_true` for permission (nonce validated separately).

**GET endpoints** (harness data) — use `__return_true` when called only internally via `rest_do_request()`. The `current_user_can()` check can fail in internal dispatch context.

**`$wpdb->print_error()`** — defaults to `true`, echoes HTML on query failure, corrupts REST JSON responses. Always call `$wpdb->suppress_errors(true)` before DB operations in REST handlers.

### 6. Assets (`includes/class-assets.php`)

```php
// Admin: only on CRM pages
wp_enqueue_style('serviceos-ip-module', ..., ['service-os-crm-dashboard']);
wp_enqueue_script('serviceos-ip-module', ..., ['service-os-crm-api']);
wp_localize_script('serviceos-ip-module', 'ServiceOSHVACConfig', [
    'businessId' => (int) get_option('service_os_crm_business_id', 1),
    'moduleSlug' => $module_slug,
]);

// Public: on specific pages/shortcodes
wp_localize_script('{slug}-checklist-core', '{Slug}ChecklistConfig', [
    'restUrl'   => rest_url('crm/v1/{slug}/checklist-submit'),
    'restNonce' => wp_create_nonce('wp_rest'),
]);
```

**JS dependencies:** `service-os-crm-api` provides `ServiceOSAPI`, `ServiceOSModal`, `ServiceOSToast`. Use `ServiceOSAPI.{resource}.{action}(businessId)` for CRM data — `ServiceOSAPI.services.create(data)`, `ServiceOSAPI.categories.list(businessId)`, etc.

**CSS dependencies:** `service-os-crm-dashboard` provides all CRM variables (`--primary`, `--surface`, `--card-bg`, etc.) and component styles (cards, tables, modals, collapsible sections).

### 7. Seeder (`includes/class-seeder.php`)

Implements `Service_OS_CRM_Module_Seeder` interface. Must return categories, pipelines (with stages), and services. Hooked via `serviceos_crm_module_seed` filter. CRM handles DB insertion — just return the data arrays.

### 8. Testing Checklist

Before deploying a new industry plugin:
1. Activate plugin → verify `hvac_*` tables created in DB
2. Verify sidebar nav item appears with correct icon
3. Verify list/detail/submissions pages render without errors
4. Verify collapsible sections open/close and persist state across reloads
5. Submit a public-facing form → verify data saves and appears in admin submissions list
6. Check PHP error log for `[HVAC]` prefixed diagnostic messages
7. In Docker: `docker exec {container} mysql -u {user} -p{pass} {db} -e "SHOW COLUMNS FROM wp_{slug}_submissions;"`
