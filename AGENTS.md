# ServiceOS Industry Plugin — Agent Guidelines

## What This Repo Is

A skeleton WordPress plugin template for creating industry-specific modules that integrate with the [ServiceOS CRM](https://github.com/morogoyo/wp_crm_general). The `main` branch is the pristine skeleton. Each industry gets its own repo created from this template.

## Why Templates (Not Forks)

**GitHub does not allow the same account to fork its own repos.** We use template repos:
- **Fork:** GitHub tracks parent relationship, offers a "Sync fork" button — but blocked for same-account owner
- **Template:** Copies the repo into a new standalone repo. Skeleton updates via `git fetch upstream && git merge upstream/main`
- **Result is identical:** each industry has its own independent repo with `main`/`dev`/`feature/*` branches

### Repo Structure

```
serviceos-industry-plugin        ← THIS REPO — skeleton template (no industry code)
    │
    ├── serviceos-industry-hvac        ← template copy for HVAC industry
    ├── serviceos-industry-plumbing    ← template copy for Plumbing industry
    └── serviceos-industry-electrical  ← template copy for Electrical industry
```

### Creating a New Industry Plugin

```bash
gh repo create morogoyo/serviceos-industry-{industry} --public --template morogoyo/serviceos-industry-plugin --clone
cd serviceos-industry-{industry}
git remote add upstream https://github.com/morogoyo/serviceos-industry-plugin.git
```

Then customize:
1. **`serviceos-industry-plugin.php`** — rename file, update Plugin Name header
2. **`includes/class-harness.php`** — set `$module_slug`, `$module_name`, `$module_icon`, `$industry`
3. **`includes/class-seeder.php`** — define categories, pipeline stages, seed services
4. **`assets/css/module.css`** — industry-specific styles
5. **`assets/js/module.js`** — industry-specific JavaScript

```bash
git add -A && git commit -m "Customize for {industry} industry"
git push -u origin main
```

### Branch Naming Per Fork

Each fork uses standard naming:
- `main` — production-ready industry plugin
- `dev` — integration/staging branch
- `feature/<name>` — new features
- `fix/<name>` — bug fixes

### Merging to Main — MANUAL ONLY

**Never auto-merge any PR to `main`. Never create a PR targeting `main` without explicit user authorization.** Merges to `main` are a manual process controlled by the user. The agent may only merge to `dev`. `dev` → `main` syncs happen only when explicitly requested and approved.

### Syncing Skeleton Updates

When this skeleton repo receives updates:

```bash
cd serviceos-industry-{industry}
git fetch upstream
git checkout dev
git merge upstream/main --no-ff
# Resolve conflicts, run tests, push
# PR dev → main when ready
```

## How the Harness Works

The plugin extends `Service_OS_CRM_Harness` to integrate with the CRM:

```php
class Harness extends Service_OS_CRM_Harness {
    protected $module_slug = 'my-industry';
    protected $module_name = 'My Industry';
    protected $module_icon = 'build';
    protected $industry = 'general';
}
```

### What the CRM Provides Automatically

| Feature | How |
|---------|-----|
| CSS (dashboard, cards, tables, modals) | Automatically loaded on `admin.php?page=service-os-crm-*` pages |
| JS (`ServiceOSAPI`, `ServiceOSModal`, `ServiceOSToast`, `ServiceOSTheme`) | Automatically loaded via `api.js` |
| Sidebar navigation | Module pages auto-appear between CRM nav and Settings |
| Page rendering | `page-renderer.php` converts schema arrays to HTML |
| Admin shell (full-screen) | WordPress chrome (toolbar, sidebar, footer) stripped automatically |

### What the Plugin Must Provide

| Requirement | Where |
|-------------|-------|
| Module metadata (slug, name, icon, industry) | `class-harness.php` → `get_module_info()` |
| Page definitions (list, detail, etc.) | `class-harness.php` → `get_pages()` |
| Page data (tables, cards, forms) | `class-harness.php` → `get_page_data()` |
| Default categories, stages, services | `class-seeder.php` → `seed()` |
| Custom CSS | `assets/css/module.css` (uses CRM CSS variables) |
| Custom JS | `assets/js/module.js` |

### Seeding Flow

```
Plugin activates
    → CRM syncs module via serviceos_crm_available_modules filter
    → CRM sees seed_applied = 0 in crm_modules
    → CRM fires serviceos_crm_module_seed filter
    → Plugin's Seeder::seed() returns categories, pipeline, stages, services
    → CRM creates them in DB
    → CRM sets seed_applied = 1
```

## No Hardcoded Data Rule

**All data displayed in module pages MUST come from internal REST API calls.** No hardcoded fallback data, mock arrays, or static placeholder content. This includes categories, services, deals, pipeline stages — everything must be fetched from the CRM REST API.

Use `ServiceOSAPI` for all data operations:

```javascript
ServiceOSAPI.deals.list(businessId).then(data => { /* populate table */ });
ServiceOSAPI.categories.list(businessId).then(data => { /* populate filters */ });
```

## CSS Variables Available

All CRM CSS custom properties are available in module stylesheets:

| Variable | Light Theme | Dark Theme |
|----------|-------------|------------|
| `--primary` | `#0058be` | `#6eb4ff` |
| `--surface` | `#f9f9ff` | `#0f1923` |
| `--on-surface` | `#111c2d` | `#e8edf4` |
| `--card-bg` | `#ffffff` | `#1a2535` |
| `--sidebar-bg` | `#263143` | — |
| `--border-light` | `#e7eeff` | `#2a3545` |
| `--error` | `#ba1a1a` | `#ffb4ab` |

Use variables — never hardcode hex values.

## Modal Standard

Any modal `<div>` MUST have `style="display: none;"` inline. Use `ServiceOSModal.open(id)` / `ServiceOSModal.close(id)`.

## Testing

Before pushing an industry fork:
1. Activate the plugin in the WordPress admin
2. Verify sidebar nav item appears
3. Verify list and detail pages render
4. Verify seeded categories appear in Category dropdown
5. Verify seeded pipeline stages appear in Pipeline view

## Session Learnings — 2026-07-13 (HVAC Field Checklist Fixes)

### DB Schema Compatibility
**Always verify the actual DB schema before writing queries.** The production DB may have a different schema than what the code assumes — merged old/new columns, NOT NULL constraints without defaults, or columns from a prior questionnaire schema. Before any INSERT/UPDATE, run `SHOW COLUMNS FROM {table}` and dynamically build the query to only include columns that exist.

```php
$existing_cols = $wpdb->get_col("SHOW COLUMNS FROM {$table}");
foreach ($all_fields as $col => $val) {
    if (in_array($col, $existing_cols, true)) {
        $data[$col] = $val;
    }
}
$wpdb->insert($table, $data, $formats);
```

### `rest_do_request()` Return Type Handling
`rest_do_request()` can return three different types depending on WordPress version:
- `WP_Error` — on route match or permission failure
- `WP_REST_Response` — older WordPress (4.4-5.x)
- Raw array — newer WordPress (6.x+, after `response_to_data()`)

**Always check with `is_wp_error()` first**, then check `instanceof WP_REST_Response` before calling response methods:

```php
$response = rest_do_request($request);
if (is_wp_error($response)) { /* handle error */ }
if ($response instanceof \WP_REST_Response) {
    $data = $response->get_data();
} else {
    $data = $response; // already raw array
}
```

### `$wpdb->print_error()` Corrupts REST JSON
`$wpdb->show_errors` defaults to `true`. When a MySQL query fails, `print_error()` echoes HTML directly to the output buffer, corrupting REST API JSON responses. **Always call `$wpdb->suppress_errors(true)` before DB operations in REST handlers** and restore with `suppress_errors(false)` after.

### Migration Versioning — Single Authority
Never set the schema version in `create_tables()` — `dbDelta` is notoriously unreliable for ALTER TABLE operations on existing installs. Let `maybe_migrate_tables()` (hooked to `plugins_loaded`) be the sole authority on schema versioning. Use idempotent `SHOW COLUMNS` checks before each ALTER TABLE.

```php
// WRONG: create_tables() sets version → migration skipped
dbDelta($tables);
update_option('hvac_schema_version', 2);

// RIGHT: only maybe_migrate_tables() sets version
dbDelta($tables);
// schema version set only after successful migration
```

### Local Docker Verification
When debugging in Docker, verify the actual DB state:
```bash
docker exec {container} mysql -u {user} -p{pass} {db} -e "SHOW TABLES LIKE '%hvac%';"
docker exec {container} mysql -u {user} -p{pass} {db} -e "SHOW COLUMNS FROM wp_hvac_submissions;"
```

### PR-Only Flow to Dev
**Never push directly to `dev`.** All work must go through:
1. Create `fix/` or `feature/` branch off `dev`
2. Commit and push branch
3. Create PR targeting `dev` via `gh pr create`
4. Request user permission before `gh pr merge`
5. Only user merges `dev` → `main`

### Never `git add -A` in the CRM repo
The CRM repo root contains Docker volume mounts (themes, uploads, other plugins) owned by Docker. `git add -A` at the repo root stages thousands of unrelated files. Always `git add {specific_file_paths}` relative to the repo root.

## Session Learnings — 2026-07-14 (Collapsible Cards & Display Fixes)

- **Original Schema Coexistence** — the production DB merged the original QUESTIONNAIRE.md schema with our migration schema; both column sets coexist with NOT NULL constraints on old columns. Column-aware INSERTs (`SHOW COLUMNS` → dynamic fields) are the only safe write pattern.
- **`render_info_table()` Label Bug** — the CRM renderer only output the first column's label as `<th>`; all others were bare `<td>` values. Fix renders every `<th>` label + `<td>` value pair.
- **Collapsible Section Pattern** — use `<details class="crm-section-card crm-collapsible-section" data-collapse-key="..." open>` with `<summary class="crm-collapsible-header">` (Material icon, label, badge, `expand_less` icon). Add a single page-level `<script>` for localStorage persistence. Flag sections with `collapsible => true`.
- **Cross-Repo Workflow** — CRM repo changes first (renderer support), HVAC repo changes depend on CRM deployment. Two separate PRs, merge CRM first then HVAC. Keep branch names consistent across repos.
- **Docker git Permission Issues** — Docker-owned files prevent `git reset --hard`, `git checkout`, `git stash`. Use specific file paths for `git add` and rely on `gh pr merge` for server-side merges.
