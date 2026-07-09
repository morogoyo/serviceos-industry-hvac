<?php
namespace ServiceOS_Industry_Plugin\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) {
    exit;
}

class HVAC_Checklist_Widget extends Widget_Base {

    public function get_name() {
        return 'hvac_field_checklist';
    }

    public function get_title() {
        return __('HVAC Field Checklist', 'serviceos-industry-hvac');
    }

    public function get_icon() {
        return 'eicon-checkbox';
    }

    public function get_categories() {
        return ['serviceos'];
    }

    public function get_keywords() {
        return ['hvac', 'checklist', 'service', 'field', 'inspection', 'maintenance'];
    }

    protected function register_controls() {
        $this->start_controls_section('content_section', [
            'label' => __('Checklist Settings', 'serviceos-industry-hvac'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('unit_count', [
            'label'   => __('Number of Units', 'serviceos-industry-hvac'),
            'type'    => Controls_Manager::SELECT,
            'default' => '10',
            'options' => [
                '10' => __('10 Units', 'serviceos-industry-hvac'),
                '52' => __('52 Units', 'serviceos-industry-hvac'),
            ],
        ]);

        $this->end_controls_section();

        $this->start_controls_section('style_section', [
            'label' => __('Brand Style', 'serviceos-industry-hvac'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('navy_color', [
            'label'     => __('Header Color', 'serviceos-industry-hvac'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#001C32',
        ]);

        $this->add_control('orange_color', [
            'label'     => __('Accent Color', 'serviceos-industry-hvac'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#E07820',
        ]);

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $unit_count = $settings['unit_count'] ?? '10';
        if (!in_array($unit_count, ['10', '52'])) {
            $unit_count = '10';
        }
        $unit_count_int = (int) $unit_count;

        $this->enqueue_checklist_assets();

        $wid = 'hvac' . $unit_count . '_' . uniqid();
        $storage_key = 'hvac_' . $unit_count . 'unit_v1';

        $hvac_settings = get_option('hvac_settings', []);
        $company = $hvac_settings['company'] ?? 'ServicePro Field';
        $recipient_name = $hvac_settings['recipient_name'] ?? 'the administrator';
        $rest_url = rest_url('crm/v1/hvac/checklist-submit');
        $nonce = wp_create_nonce('wp_rest');

        $navy = esc_attr($settings['navy_color'] ?? '#001C32');
        $orange = esc_attr($settings['orange_color'] ?? '#E07820');

        $total_items = $unit_count_int * 5;
        ?>
<style id="hvac-elementor-style-<?php echo esc_attr($wid); ?>">
#<?php echo esc_attr($wid); ?> { --navy: <?php echo $navy; ?>; --orange: <?php echo $orange; ?>; }
</style>
<div class="hvac-wrap hvac-wrap-<?php echo $unit_count; ?>unit" id="<?php echo esc_attr($wid); ?>" data-recipient="<?php echo esc_attr($recipient_name); ?>">

  <div class="hvac-header">
    <div class="hvac-header-main">
      <div class="hvac-brand">
        <h2><?php echo esc_html($company); ?></h2>
        <p>Technician Service Checklist — <?php echo $unit_count_int; ?> Unit Split System</p>
      </div>
      <div class="hvac-contact">
        <span class="hvac-phone"><?php echo esc_html($hvac_settings['phone'] ?? '(786) 322-0638'); ?></span>
        <span class="hvac-site"><?php echo esc_html($hvac_settings['website'] ?? 'servicepro.tools'); ?></span>
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
      ▸ Scope of Service — Each Visit Includes
      <span class="hvac-arrow">▾</span>
    </button>
    <div class="hvac-scope-body">
      <div class="hvac-scope-grid">
        <div class="hvac-scope-item"><span class="hvac-tick">✓</span><span>Light inspect &amp; surface-level clean of evaporator coil*</span></div>
        <div class="hvac-scope-item"><span class="hvac-tick">✓</span><span>Check refrigerant levels and system condition</span></div>
        <div class="hvac-scope-item"><span class="hvac-tick">✓</span><span>Flush and treat condensate drain lines</span></div>
        <div class="hvac-scope-item"><span class="hvac-tick">✓</span><span>Inspect for visible leaks or abnormal condensation</span></div>
        <div class="hvac-scope-item"><span class="hvac-tick">✓</span><span>Inspect control systems and safety devices</span></div>
        <div class="hvac-scope-item"><span class="hvac-tick">✓</span><span>Evaluate overall system performance</span></div>
        <div class="hvac-scope-item"><span class="hvac-tick">✓</span><span>Check contactors and electrical components for wear</span></div>
        <div class="hvac-scope-item"><span class="hvac-tick">✓</span><span>Replace all air filters (included)</span></div>
        <div class="hvac-scope-item"><span class="hvac-tick">✓</span><span>Inspect condenser fan blades and motors</span></div>
        <div class="hvac-scope-item"><span class="hvac-tick">✓</span><span>Provide service report after each visit</span></div>
      </div>
      <div class="hvac-coil-note">* Evaporator coil scope: Surface-level cleaning of accessible coil only. This is NOT a deep clean. Deep cleaning requires additional labor and is billed separately.</div>
    </div>
  </div>

  <div class="hvac-units-label">▸ Unit-by-Unit Service Checklist</div>
  <div class="hvac-units-container"></div>

  <div class="hvac-card">
    <div class="hvac-section-label">▸ Final Sign-Off</div>
    <div class="hvac-signoff-grid">
      <label class="hvac-signoff-item"><input type="checkbox" onchange="HVACChecklist.toggleSignoff(this)"><span>All <?php echo $unit_count_int; ?> units serviced and checklist complete</span></label>
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
      onclick="HVACChecklist.submitREST('<?php echo esc_js($wid); ?>',<?php echo $unit_count_int; ?>,'<?php echo esc_js($storage_key); ?>','<?php echo esc_js($rest_url); ?>','<?php echo esc_js($nonce); ?>')">
      ? Submit Report
    </button>
    <button class="hvac-btn hvac-btn-primary" onclick="HVACChecklist.expandAll('<?php echo esc_attr($wid); ?>',<?php echo $unit_count_int; ?>)">Expand All</button>
  </div>

</div>
        <?php
    }

    protected function content_template() {
        ?>
        <div style="padding:20px;background:var(--e-a-bg-default);border:1px dashed var(--e-a-border-color-bold);border-radius:8px;text-align:center;color:var(--e-a-color-txt);">
            <span class="eicon-checkbox" style="font-size:32px;display:block;margin-bottom:8px;"></span>
            <strong><?php esc_html_e('HVAC Field Checklist', 'serviceos-industry-hvac'); ?></strong>
            <p style="margin:4px 0 0;font-size:12px;"><?php esc_html_e('Unit count: ', 'serviceos-industry-hvac'); ?>{{{ settings.unit_count }}}</p>
            <p style="margin:2px 0 0;font-size:11px;opacity:0.6;"><?php esc_html_e('Interactive form renders on the frontend', 'serviceos-industry-hvac'); ?></p>
        </div>
        <?php
    }

    private function enqueue_checklist_assets() {
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

        $ft_file = SERVICEOS_IP_PATH . 'assets/js/checklist-52.js';
        wp_enqueue_script(
            'hvac-checklist-52',
            SERVICEOS_IP_URL . 'assets/js/checklist-52.js',
            ['hvac-checklist-core'],
            file_exists($ft_file) ? filemtime($ft_file) : SERVICEOS_IP_VERSION,
            true
        );
    }
}
