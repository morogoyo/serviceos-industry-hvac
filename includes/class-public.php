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
    }

    public static function register_routes() {
        register_rest_route('crm/v1', '/hvac/checklist-submit', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'handle_submission'],
            'permission_callback' => '__return_true',
        ]);
    }

    public static function render_checklist($atts) {
        $atts = shortcode_atts(['units' => '10'], $atts);
        $unit_count = absint($atts['units']);
        if (!in_array($unit_count, [10, 52])) {
            $unit_count = 10;
        }

        $wid = 'hvac' . $unit_count . '_' . uniqid();
        $storage_key = 'hvac_' . $unit_count . 'unit_v1';

        $settings = get_option('hvac_settings', []);
        $company = $settings['company'] ?? 'ServicePro Field';
        $recipient_name = $settings['recipient_name'] ?? 'the administrator';
        $rest_url = rest_url('crm/v1/hvac/checklist-submit');
        $nonce = wp_create_nonce('wp_rest');

        ob_start();
        ?>
<div class="hvac-wrap hvac-wrap-<?php echo $unit_count; ?>unit" id="<?php echo esc_attr($wid); ?>" data-recipient="<?php echo esc_attr($recipient_name); ?>">

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
      <div class="hvac-progress-label" id="hvac_pl_<?php echo esc_attr($wid); ?>">0 / <?php echo $unit_count * 5; ?> items</div>
    </div>
  </div>

  <div class="hvac-card">
    <div class="hvac-section-label">? Job Information</div>
    <div class="hvac-job-grid">
      <div class="hvac-job-field"><label>Property / Address</label><input type="text" id="hvac_ji_property_<?php echo esc_attr($wid); ?>" placeholder="Enter property name or address"></div>
      <div class="hvac-job-field"><label>Date of Service</label><input type="date" id="hvac_ji_date_<?php echo esc_attr($wid); ?>"></div>
      <div class="hvac-job-field"><label>Technician Name</label><input type="text" id="hvac_ji_tech_<?php echo esc_attr($wid); ?>" placeholder="Full name"></div>
      <div class="hvac-job-field"><label>Work Order #</label><input type="text" id="hvac_ji_wo_<?php echo esc_attr($wid); ?>" placeholder="&mdash;"></div>
      <div class="hvac-job-field"><label>Contract #</label><input type="text" id="hvac_ji_contract_<?php echo esc_attr($wid); ?>" placeholder="&mdash;"></div>
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
        <input class="hvac-sig-line" type="text" placeholder="Sign here">
        <div class="hvac-sig-date">Date: <input type="date"></div>
      </div>
      <div class="hvac-sig-block">
        <label>Client / Authorized Rep Signature</label>
        <input class="hvac-sig-line" type="text" placeholder="Sign here">
        <div class="hvac-sig-date">Date: <input type="date"></div>
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

        Email::send($params);

        return rest_ensure_response([
            'success' => true,
            'message' => 'Report submitted successfully.',
            'submission_id' => $submission_id,
        ]);
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
    }

    private static function submissions_table_sql() {
        global $wpdb;
        $table = $wpdb->prefix . 'hvac_submissions';

        return "CREATE TABLE {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ji_contract VARCHAR(100) DEFAULT NULL,
            ji_wo VARCHAR(100) DEFAULT NULL,
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

        $uuid = wp_generate_uuid4();
        $settings = get_option('hvac_settings', []);
        $company = $settings['company'] ?? 'ServicePro';

        $result = $wpdb->insert(
            $wpdb->prefix . 'hvac_submissions',
            [
                'uuid' => $uuid,
                'property_address' => sanitize_text_field($payload['ji_property'] ?? ''),
                'date_of_service' => sanitize_text_field($payload['ji_date'] ?? date('Y-m-d')),
                'technician_name' => sanitize_text_field($payload['ji_tech'] ?? ''),
                'work_order' => sanitize_text_field($payload['ji_wo'] ?? ''),
                'contract_number' => sanitize_text_field($payload['ji_contract'] ?? ''),
                'visit_type' => sanitize_text_field($payload['ji_visit'] ?? ''),
                'unit_count' => intval($payload['unit_count'] ?? 10),
                'company_name' => sanitize_text_field($company),
                'raw_json' => json_encode($payload),
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s']
        );

        if (!$result) {
            error_log('HVAC Submission Error: ' . $wpdb->last_error);
            return false;
        }

        $submission_id = $wpdb->insert_id;

        if (!empty($payload['units'])) {
            foreach ($payload['units'] as $unit) {
                $unit_num = intval($unit['num'] ?? 0);
                $checks = $unit['checks'] ?? [];
                for ($i = 0; $i < 10; $i++) {
                    $wpdb->insert(
                        $wpdb->prefix . 'hvac_unit_items',
                        [
                            'submission_id' => $submission_id,
                            'unit_number' => $unit_num,
                            'item_index' => $i,
                            'checked' => !empty($checks[$i]) ? 1 : 0,
                            'status' => sanitize_text_field($unit['status'] ?? 'none'),
                            'supply_temp' => sanitize_text_field($unit['sup'] ?? ''),
                            'return_temp' => sanitize_text_field($unit['ret'] ?? ''),
                            'delta_t' => sanitize_text_field($unit['dt'] ?? ''),
                            'filter_size' => sanitize_text_field($unit['fs'] ?? ''),
                            'notes' => sanitize_textarea_field($unit['notes'] ?? ''),
                            'initials' => sanitize_text_field($unit['init'] ?? ''),
                        ],
                        ['%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
                    );
                }
            }
        }

        if (!empty($payload['signoff'])) {
            foreach ($payload['signoff'] as $item) {
                $wpdb->insert(
                    $wpdb->prefix . 'hvac_signoffs',
                    [
                        'submission_id' => $submission_id,
                        'item_label' => sanitize_text_field($item['label'] ?? ''),
                        'checked' => !empty($item['checked']) ? 1 : 0,
                    ],
                    ['%d', '%s', '%d']
                );
            }
        }

        return $submission_id;
    }
}
