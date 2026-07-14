<?php
namespace ServiceOS_Industry_Plugin;

if (!defined('ABSPATH')) {
    exit;
}

class Public_Checklist {

    public static function register() {
        add_shortcode('hvac_checklist', [__CLASS__, 'render_checklist']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
        add_action('plugins_loaded', [__CLASS__, 'maybe_migrate_tables']);
    }

    public static function register_routes() {
        register_rest_route('crm/v1', '/hvac/checklist-submit', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'handle_submission'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('crm/v1', '/hvac/submissions', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'handle_get_submissions'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('crm/v1', '/hvac/submissions/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'handle_get_submission_detail'],
            'permission_callback' => '__return_true',
        ]);
    }

    public static function render_checklist($atts) {
        $atts = shortcode_atts([
            'units'                   => '10',
            'ji_wo'                   => '',
            'ji_contract'             => '',
            'allow_wo_override'       => '1',
            'enforce_assignment_lock' => '0',
            'auto_track'              => '1',
            'technician_lock'         => '0',
            'navy_color'              => '#001C32',
            'orange_color'            => '#E07820',
        ], $atts);

        if ('1' === $atts['technician_lock'] && !current_user_can('edit_posts')) {
            return '<div class="hvac-error" style="padding:20px;background:var(--card-bg,#fff);border:1px solid var(--error,#ba1a1a);border-radius:8px;color:var(--error,#ba1a1a);font-family:Arial,sans-serif;">'
                . __('Access Restricted: You are not assigned to this operational ticket.', 'serviceos-industry-hvac')
                . '</div>';
        }

        $unit_count = absint($atts['units']);
        if (!in_array($unit_count, [10, 52])) {
            $unit_count = 10;
        }

        $wo_value = sanitize_text_field($atts['ji_wo']);
        if (empty($wo_value) && !empty($_GET['wo_id'])) {
            $wo_value = sanitize_text_field(wp_unslash($_GET['wo_id']));
        }
        $contract_value = sanitize_text_field($atts['ji_contract']);
        if (empty($contract_value) && !empty($_GET['contract_id'])) {
            $contract_value = sanitize_text_field(wp_unslash($_GET['contract_id']));
        }
        $wo_readonly = $wo_value && !$atts['allow_wo_override'] ? ' readonly' : '';
        $assignment_lock = (bool) $atts['enforce_assignment_lock'];
        $tech_readonly = $assignment_lock ? ' readonly' : '';

        $wid = 'hvac' . $unit_count . '_' . uniqid();
        $storage_key = 'hvac_' . $unit_count . 'unit_v1';

        $settings = get_option('hvac_settings', []);
        $company = $settings['company'] ?? 'ServicePro Field';
        $recipient_name = $settings['recipient_name'] ?? 'the administrator';
        $rest_url = rest_url('crm/v1/hvac/checklist-submit');
        $nonce = wp_create_nonce('wp_rest');

        $navy = esc_attr($atts['navy_color']);
        $orange = esc_attr($atts['orange_color']);
        $total_items = $unit_count * 5;

        ob_start();
        ?>
<style id="hvac-elementor-style-<?php echo esc_attr($wid); ?>">
#<?php echo esc_attr($wid); ?> { --navy: <?php echo $navy; ?>; --orange: <?php echo $orange; ?>; }
</style>
<div class="hvac-wrap hvac-wrap-<?php echo $unit_count; ?>unit" id="<?php echo esc_attr($wid); ?>"
     data-recipient="<?php echo esc_attr($recipient_name); ?>"
     data-wo-override="<?php echo esc_attr($atts['allow_wo_override']); ?>"
     data-assignment-lock="<?php echo $assignment_lock ? '1' : '0'; ?>"
     data-auto-track="<?php echo esc_attr($atts['auto_track']); ?>"
     data-technician-lock="<?php echo esc_attr($atts['technician_lock']); ?>">

  <div class="hvac-header">
    <div class="hvac-header-main">
      <div class="hvac-brand">
        <h2><?php echo esc_html($company); ?></h2>
        <p>Technician Service Checklist — <?php echo $unit_count; ?> Unit Split System</p>
      </div>
      <div class="hvac-contact">
        <span class="hvac-phone"><?php echo esc_html($settings['phone'] ?? '(786) 322-0638'); ?></span>
        <span class="hvac-site"><?php echo esc_html($settings['website'] ?? 'servicepro.tools'); ?></span>
      </div>
    </div>
    <div class="hvac-progress">
      <div class="hvac-progress-track">
        <div class="hvac-progress-fill" id="hvac_pf_<?php echo esc_attr($wid); ?>"></div>
      </div>
      <div class="hvac-progress-label" id="hvac_pl_<?php echo esc_attr($wid); ?>">0 / <?php echo $total_items; ?> items</div>
    </div>
  </div>

  <div class="hvac-card">
    <div class="hvac-section-label">▸ Job Information</div>
    <div class="hvac-job-grid">
      <div class="hvac-job-field"><label>Property / Address</label><input type="text" id="hvac_ji_property_<?php echo esc_attr($wid); ?>" placeholder="Enter property name or address"></div>
      <div class="hvac-job-field"><label>Date of Service</label><input type="date" id="hvac_ji_date_<?php echo esc_attr($wid); ?>"></div>
      <div class="hvac-job-field"><label>Technician Name</label><input type="text" id="hvac_ji_tech_<?php echo esc_attr($wid); ?>" placeholder="Full name"<?php echo $tech_readonly; ?>></div>
      <div class="hvac-job-field"><label>Work Order #</label><input type="text" id="hvac_ji_wo_<?php echo esc_attr($wid); ?>" placeholder="&mdash;" value="<?php echo esc_attr($wo_value); ?>"<?php echo $wo_readonly; ?>></div>
      <div class="hvac-job-field"><label>Contract #</label><input type="text" id="hvac_ji_contract_<?php echo esc_attr($wid); ?>" placeholder="&mdash;" value="<?php echo esc_attr($contract_value); ?>"></div>
      <div class="hvac-job-field"><label>Visit Type</label>
        <select id="hvac_ji_visit_<?php echo esc_attr($wid); ?>">
          <option value="">Select...</option>
          <option>Quarterly</option>
          <option>Semi-Annual</option>
          <option>Annual</option>
          <option>Follow-Up</option>
          <option>Emergency</option>
        </select>
      </div>
    </div>
  </div>

  <div class="hvac-card">
    <button class="hvac-scope-toggle" onclick="HVACChecklist.toggleScope(this)">
      ? Scope of Service — Each Visit Includes
      <span class="hvac-arrow">?</span>
    </button>
    <div class="hvac-scope-body">
      <div class="hvac-scope-grid">
        <div class="hvac-scope-item"><span class="hvac-tick">?</span><span>Light inspect &amp; surface-level clean of evaporator coil*</span></div>
        <div class="hvac-scope-item"><span class="hvac-tick">?</span><span>Check refrigerant levels and system condition</span></div>
        <div class="hvac-scope-item"><span class="hvac-tick">?</span><span>Flush and treat condensate drain lines</span></div>
        <div class="hvac-scope-item"><span class="hvac-tick">?</span><span>Inspect for visible leaks or abnormal condensation</span></div>
        <div class="hvac-scope-item"><span class="hvac-tick">?</span><span>Inspect control systems and safety devices</span></div>
        <div class="hvac-scope-item"><span class="hvac-tick">?</span><span>Evaluate overall system performance</span></div>
        <div class="hvac-scope-item"><span class="hvac-tick">?</span><span>Check contactors and electrical components for wear</span></div>
        <div class="hvac-scope-item"><span class="hvac-tick">?</span><span>Replace all air filters (included)</span></div>
        <div class="hvac-scope-item"><span class="hvac-tick">?</span><span>Inspect condenser fan blades and motors</span></div>
        <div class="hvac-scope-item"><span class="hvac-tick">?</span><span>Provide service report after each visit</span></div>
      </div>
      <div class="hvac-coil-note">* Evaporator coil scope: Surface-level cleaning of accessible coil only. This is NOT a deep clean. Deep cleaning requires additional labor and is billed separately.</div>
    </div>
  </div>

  <div class="hvac-units-label">? Unit-by-Unit Service Checklist</div>
  <div class="hvac-units-container"></div>

  <div class="hvac-card">
    <div class="hvac-section-label">? Final Sign-Off</div>
    <div class="hvac-signoff-grid">
      <label class="hvac-signoff-item"><input type="checkbox" onchange="HVACChecklist.toggleSignoff(this)"><span>All <?php echo $unit_count; ?> units serviced and checklist complete</span></label>
      <label class="hvac-signoff-item"><input type="checkbox" onchange="HVACChecklist.toggleSignoff(this)"><span>All filters replaced</span></label>
      <label class="hvac-signoff-item"><input type="checkbox" onchange="HVACChecklist.toggleSignoff(this)"><span>Service report prepared</span></label>
      <label class="hvac-signoff-item"><input type="checkbox" onchange="HVACChecklist.toggleSignoff(this)"><span>No units require follow-up</span></label>
      <label class="hvac-signoff-item"><input type="checkbox" onchange="HVACChecklist.toggleSignoff(this)"><span>Follow-up required &mdash; see notes</span></label>
      <label class="hvac-signoff-item"><input type="checkbox" onchange="HVACChecklist.toggleSignoff(this)"><span>Quote to follow for additional work</span></label>
    </div>
    <div class="hvac-sig-row">
      <div class="hvac-sig-block">
        <label>Technician Signature</label>
        <input class="hvac-sig-line" type="text" placeholder="Sign here" id="hvac_tech_sig_<?php echo esc_attr($wid); ?>">
        <div class="hvac-sig-date">Date: <input type="date" id="hvac_tech_sig_date_<?php echo esc_attr($wid); ?>"></div>
      </div>
      <div class="hvac-sig-block">
        <label>Client / Authorized Rep Signature</label>
        <input class="hvac-sig-line" type="text" placeholder="Sign here" id="hvac_client_sig_<?php echo esc_attr($wid); ?>">
        <div class="hvac-sig-date">Date: <input type="date" id="hvac_client_sig_date_<?php echo esc_attr($wid); ?>"></div>
      </div>
    </div>
    <div class="hvac-disclaimer">Technician signature confirms all items above were inspected and serviced. Client/rep signature confirms work completed to satisfaction.</div>
  </div>

  <div id="hvac_submit_banner_<?php echo esc_attr($wid); ?>"
       style="display:none;margin:0 0 10px;padding:12px 16px;border-radius:10px;font-size:13px;line-height:1.5;font-family:Arial,sans-serif;">
  </div>

  <div class="hvac-action-bar">
    <button class="hvac-btn hvac-btn-secondary" onclick="HVACChecklist.clearAll('<?php echo esc_js($storage_key); ?>')">Clear All</button>
    <button class="hvac-btn hvac-btn-secondary" onclick="window.print()">? Print</button>
    <button class="hvac-btn hvac-btn-submit" id="hvac_submit_btn_<?php echo esc_attr($wid); ?>"
      onclick="HVACChecklist.submitREST('<?php echo esc_js($wid); ?>',<?php echo $unit_count; ?>,'<?php echo esc_js($storage_key); ?>','<?php echo esc_js($rest_url); ?>','<?php echo esc_js($nonce); ?>')">
      ? Submit Report
    </button>
    <button class="hvac-btn hvac-btn-primary" onclick="HVACChecklist.expandAll('<?php echo esc_attr($wid); ?>',<?php echo $unit_count; ?>)">Expand All</button>
  </div>

</div>
        <?php
        return ob_get_clean();
    }

    public static function enqueue_assets() {
        global $post;
        if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'hvac_checklist')) {
            return;
        }

        $css_file = SERVICEOS_IP_PATH . 'assets/css/checklist.css';
        wp_enqueue_style(
            'hvac-checklist',
            SERVICEOS_IP_URL . 'assets/css/checklist.css',
            [],
            file_exists($css_file) ? filemtime($css_file) : SERVICEOS_IP_VERSION
        );

        $core_file = SERVICEOS_IP_PATH . 'assets/js/checklist-core.js';
        wp_enqueue_script(
            'hvac-checklist-core',
            SERVICEOS_IP_URL . 'assets/js/checklist-core.js',
            [],
            file_exists($core_file) ? filemtime($core_file) : SERVICEOS_IP_VERSION,
            true
        );

        $ten_file = SERVICEOS_IP_PATH . 'assets/js/checklist-10.js';
        wp_enqueue_script(
            'hvac-checklist-10',
            SERVICEOS_IP_URL . 'assets/js/checklist-10.js',
            ['hvac-checklist-core'],
            file_exists($ten_file) ? filemtime($ten_file) : SERVICEOS_IP_VERSION,
            true
        );

        $fiftytwo_file = SERVICEOS_IP_PATH . 'assets/js/checklist-52.js';
        wp_enqueue_script(
            'hvac-checklist-52',
            SERVICEOS_IP_URL . 'assets/js/checklist-52.js',
            ['hvac-checklist-core'],
            file_exists($fiftytwo_file) ? filemtime($fiftytwo_file) : SERVICEOS_IP_VERSION,
            true
        );
    }

    public static function handle_submission($request) {
        $params = $request->get_json_params();
        if (!$params) {
            $raw = $request->get_body();
            $params = json_decode($raw, true);
        }
        if (!$params || !is_array($params)) {
            return new \WP_Error('rest_invalid_json', 'No checklist data received.', ['status' => 400]);
        }

        $submission_id = self::save_submission($params);
        if (!$submission_id) {
            return new \WP_Error('rest_save_failed', 'Failed to save submission.', ['status' => 500]);
        }

        $deal_id = self::register_equipment_and_create_deal($params, $submission_id);

        Email::send($params);

        $response = [
            'success'       => true,
            'message'       => 'Report submitted successfully.',
            'submission_id' => $submission_id,
        ];
        if ($deal_id) {
            $response['deal_id'] = $deal_id;
        }

        return rest_ensure_response($response);
    }

    public static function handle_get_submissions($request) {
        global $wpdb;
        $table = $wpdb->prefix . 'hvac_submissions';
        $per_page = absint($request->get_param('per_page')) ?: 20;
        $page = max(1, absint($request->get_param('page')) ?: 1);
        $offset = ($page - 1) * $per_page;

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");

        $submissions = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, u.display_name AS technician_name
             FROM {$table} s
             LEFT JOIN {$wpdb->users} u ON s.technician_id = u.ID
             ORDER BY s.created_at DESC
             LIMIT %d OFFSET %d",
            $per_page, $offset
        ));

        error_log('[HVAC] GET submissions: total=' . $total . ', returning=' . count($submissions) . ', page=' . $page . ', per_page=' . $per_page);

        return rest_ensure_response([
            'data'     => $submissions,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $per_page,
        ]);
    }

    public static function handle_get_submission_detail($request) {
        global $wpdb;

        $submission_id = absint($request->get_param('id'));
        if ($submission_id === 0) {
            return new \WP_Error('rest_not_found', 'Submission not found.', ['status' => 404]);
        }

        $sub_table = $wpdb->prefix . 'hvac_submissions';
        $items_table = $wpdb->prefix . 'hvac_unit_items';
        $signoffs_table = $wpdb->prefix . 'hvac_signoffs';

        $submission = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, u.display_name AS technician_name
             FROM {$sub_table} s
             LEFT JOIN {$wpdb->users} u ON s.technician_id = u.ID
             WHERE s.id = %d",
            $submission_id
        ));

        if (!$submission) {
            return new \WP_Error('rest_not_found', 'Submission not found.', ['status' => 404]);
        }

        $submission->units = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$items_table} WHERE submission_id = %d ORDER BY unit_number",
            $submission_id
        ));

        $submission->signoffs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$signoffs_table} WHERE submission_id = %d",
            $submission_id
        ));

        return rest_ensure_response($submission);
    }

    private static function register_equipment_and_create_deal($payload, $submission_id) {
        global $wpdb;
        $business_id = (int) get_option('service_os_crm_business_id', 0);

        $units = $payload['units'] ?? [];

        foreach ($units as $unit) {
            $serial = sanitize_text_field($unit['serial_number'] ?? '');
            if (empty($serial)) {
                continue;
            }

            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}crm_resources
                 WHERE business_id = %d AND title = %s AND type = 'equipment'",
                $business_id, $serial
            ));

            if (!$existing) {
                require_once WP_PLUGIN_DIR . '/service-os-crm/src/Models/class-resource.php';
                require_once WP_PLUGIN_DIR . '/service-os-crm/src/Repositories/class-resource-repository.php';

                $resource = new \Service_OS_CRM\Models\Resource();
                $resource->business_id   = $business_id;
                $resource->client_id     = !empty($payload['client_id']) ? absint($payload['client_id']) : null;
                $resource->title         = $serial;
                $resource->type          = 'equipment';
                $resource->external_url  = '';
                $resource->attachment_id = null;

                $repo = new \Service_OS_CRM\Repositories\Resource_Repository();
                $repo->create($resource);
            }
        }

        $pipeline_name = 'Service & Repair Pipeline';
        $pipeline = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}crm_pipelines
             WHERE business_id = %d AND name = %s",
            $business_id, $pipeline_name
        ));

        if (!$pipeline) {
            return null;
        }

        $first_stage = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}crm_pipeline_stages
             WHERE pipeline_id = %d ORDER BY stage_order ASC LIMIT 1",
            $pipeline->id
        ));

        if (!$first_stage) {
            return null;
        }

        require_once WP_PLUGIN_DIR . '/service-os-crm/src/Models/class-deal.php';
        require_once WP_PLUGIN_DIR . '/service-os-crm/src/Repositories/class-deal-repository.php';

        $deal = new \Service_OS_CRM\Models\Deal();
        $deal->business_id = $business_id;
        $deal->client_id   = !empty($payload['client_id']) ? absint($payload['client_id']) : null;
        $deal->pipeline_id = (int) $pipeline->id;
        $deal->stage_id    = (int) $first_stage->id;
        $deal->title       = sanitize_text_field(
            ($payload['ji_wo'] ?? 'Checklist') . ' — Submission #' . $submission_id
        );
        $deal->value       = 0;
        $deal->status      = 'new';
        $deal->milestone   = 0;
        $deal->notes       = 'Generated from HVAC Field Checklist submission #' . $submission_id;

        $repo = new \Service_OS_CRM\Repositories\Deal_Repository();
        return $repo->create($deal);
    }

    public static function create_tables() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $tables = [
            self::submissions_table_sql(),
            self::unit_items_table_sql(),
            self::signoffs_table_sql(),
        ];

        dbDelta($tables);

        self::update_schema_version(2);
    }

    const SCHEMA_VERSION = 2;

    public static function maybe_migrate_tables() {
        $current = (int) get_option('hvac_schema_version', 1);
        if ($current >= self::SCHEMA_VERSION) {
            return;
        }

        global $wpdb;

        $sub_table = $wpdb->prefix . 'hvac_submissions';
        $items_table = $wpdb->prefix . 'hvac_unit_items';

        if ($current < 2) {
            $cols = $wpdb->get_col("SHOW COLUMNS FROM {$sub_table}");
            $sub_migrations = [
                'ji_property' => "ALTER TABLE {$sub_table} ADD COLUMN ji_property VARCHAR(255) DEFAULT NULL AFTER ji_wo",
                'ji_date'     => "ALTER TABLE {$sub_table} ADD COLUMN ji_date DATE DEFAULT NULL AFTER ji_property",
                'ji_tech'     => "ALTER TABLE {$sub_table} ADD COLUMN ji_tech VARCHAR(255) DEFAULT NULL AFTER ji_date",
                'ji_visit'    => "ALTER TABLE {$sub_table} ADD COLUMN ji_visit VARCHAR(100) DEFAULT NULL AFTER ji_tech",
            ];
            foreach ($sub_migrations as $col => $sql) {
                if (!in_array($col, $cols, true)) {
                    $wpdb->query($sql);
                }
            }

            $icols = $wpdb->get_col("SHOW COLUMNS FROM {$items_table}");
            $items_migrations = [
                'sup'    => "ALTER TABLE {$items_table} ADD COLUMN sup VARCHAR(20) DEFAULT '' AFTER checks_json",
                'ret'    => "ALTER TABLE {$items_table} ADD COLUMN ret VARCHAR(20) DEFAULT '' AFTER sup",
                'dt'     => "ALTER TABLE {$items_table} ADD COLUMN dt VARCHAR(20) DEFAULT '' AFTER ret",
                'fs'     => "ALTER TABLE {$items_table} ADD COLUMN fs VARCHAR(50) DEFAULT '' AFTER dt",
                'notes'  => "ALTER TABLE {$items_table} ADD COLUMN notes TEXT AFTER fs",
                'init'   => "ALTER TABLE {$items_table} ADD COLUMN init VARCHAR(10) DEFAULT '' AFTER notes",
                'status' => "ALTER TABLE {$items_table} ADD COLUMN status VARCHAR(20) DEFAULT '' AFTER init",
            ];
            foreach ($items_migrations as $col => $sql) {
                if (!in_array($col, $icols, true)) {
                    $wpdb->query($sql);
                }
            }
        }

        self::update_schema_version(self::SCHEMA_VERSION);
    }

    private static function update_schema_version($version) {
        update_option('hvac_schema_version', $version);
    }

    private static function submissions_table_sql() {
        global $wpdb;
        $table = $wpdb->prefix . 'hvac_submissions';

        return "CREATE TABLE {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ji_contract VARCHAR(100) DEFAULT NULL,
            ji_wo VARCHAR(100) DEFAULT NULL,
            ji_property VARCHAR(255) DEFAULT NULL,
            ji_date DATE DEFAULT NULL,
            ji_tech VARCHAR(255) DEFAULT NULL,
            ji_visit VARCHAR(100) DEFAULT NULL,
            technician_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            client_id BIGINT(20) UNSIGNED DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) {$wpdb->get_charset_collate()}";
    }

    private static function unit_items_table_sql() {
        global $wpdb;
        $table = $wpdb->prefix . 'hvac_unit_items';

        return "CREATE TABLE {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            submission_id BIGINT(20) UNSIGNED NOT NULL,
            unit_number INT NOT NULL DEFAULT 0,
            equipment_type VARCHAR(100) DEFAULT '',
            serial_number VARCHAR(100) DEFAULT '',
            model_number VARCHAR(100) DEFAULT '',
            checks_json LONGTEXT,
            sup VARCHAR(20) DEFAULT '',
            ret VARCHAR(20) DEFAULT '',
            dt VARCHAR(20) DEFAULT '',
            fs VARCHAR(50) DEFAULT '',
            notes TEXT,
            init VARCHAR(10) DEFAULT '',
            status VARCHAR(20) DEFAULT '',
            PRIMARY KEY (id),
            KEY idx_submission_id (submission_id),
            KEY idx_serial_number (serial_number)
        ) {$wpdb->get_charset_collate()}";
    }

    private static function signoffs_table_sql() {
        global $wpdb;
        $table = $wpdb->prefix . 'hvac_signoffs';

        return "CREATE TABLE {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            submission_id BIGINT(20) UNSIGNED NOT NULL,
            signoff_type VARCHAR(50) DEFAULT '',
            printed_name VARCHAR(255) DEFAULT '',
            signature_data LONGTEXT,
            signed_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_submission_id (submission_id)
        ) {$wpdb->get_charset_collate()}";
    }

    private static function save_submission($payload) {
        global $wpdb;

        $result = $wpdb->insert(
            $wpdb->prefix . 'hvac_submissions',
            [
                'ji_contract'   => sanitize_text_field($payload['ji_contract'] ?? ''),
                'ji_wo'         => sanitize_text_field($payload['ji_wo'] ?? ''),
                'ji_property'   => sanitize_text_field($payload['ji_property'] ?? ''),
                'ji_date'       => sanitize_text_field($payload['ji_date'] ?? ''),
                'ji_tech'       => sanitize_text_field($payload['ji_tech'] ?? ''),
                'ji_visit'      => sanitize_text_field($payload['ji_visit'] ?? ''),
                'technician_id' => absint($payload['technician_id'] ?? 0),
                'client_id'     => !empty($payload['client_id']) ? absint($payload['client_id']) : null,
                'created_at'    => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s']
        );

        if (!$result) {
            error_log('HVAC Submission Error: ' . $wpdb->last_error);
            return false;
        }

        $submission_id = $wpdb->insert_id;

        error_log('[HVAC] Submission #' . $submission_id . ' saved. WO: ' . ($payload['ji_wo'] ?? '') . ', Property: ' . ($payload['ji_property'] ?? ''));

        if (!empty($payload['units'])) {
            foreach ($payload['units'] as $unit) {
                $wpdb->insert(
                    $wpdb->prefix . 'hvac_unit_items',
                    [
                        'submission_id'   => $submission_id,
                        'unit_number'     => intval($unit['unit_number'] ?? 0),
                        'equipment_type'  => sanitize_text_field($unit['equipment_type'] ?? ''),
                        'serial_number'   => sanitize_text_field($unit['serial_number'] ?? ''),
                        'model_number'    => sanitize_text_field($unit['model_number'] ?? ''),
                        'checks_json'     => is_array($unit['checks_json'] ?? null)
                            ? json_encode($unit['checks_json'])
                            : sanitize_textarea_field($unit['checks_json'] ?? ''),
                        'sup'             => sanitize_text_field($unit['sup'] ?? ''),
                        'ret'             => sanitize_text_field($unit['ret'] ?? ''),
                        'dt'              => sanitize_text_field($unit['dt'] ?? ''),
                        'fs'              => sanitize_text_field($unit['fs'] ?? ''),
                        'notes'           => sanitize_textarea_field($unit['notes'] ?? ''),
                        'init'            => sanitize_text_field($unit['init'] ?? ''),
                        'status'          => sanitize_text_field($unit['status'] ?? ''),
                    ],
                    ['%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
                );
            }
        }

        if (!empty($payload['signoffs'])) {
            foreach ($payload['signoffs'] as $so) {
                $wpdb->insert(
                    $wpdb->prefix . 'hvac_signoffs',
                    [
                        'submission_id'  => $submission_id,
                        'signoff_type'   => sanitize_text_field($so['signoff_type'] ?? ''),
                        'printed_name'   => sanitize_text_field($so['printed_name'] ?? ''),
                        'signature_data' => sanitize_textarea_field($so['signature_data'] ?? ''),
                        'signed_at'      => sanitize_text_field($so['signed_at'] ?? current_time('mysql')),
                    ],
                    ['%d', '%s', '%s', '%s', '%s']
                );
            }
        }

        return $submission_id;
    }
}
