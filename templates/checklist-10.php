<?php
/**
 * Template: 10-Unit Split System Checklist
 * Rendered via [hvac_field_checklist_10] shortcode
 */
$wid        = 'hvac10_' . uniqid();
$unit_count = 10;
$storage_key = 'hvac_10unit_v1';

$settings = get_option('hvac_settings', array());
$company = $settings['company'] ?? 'ServicePro Field';
$recipient_name = $settings['recipient_name'] ?? 'the administrator';
?>
<div class="hvac-wrap hvac-wrap-10unit" id="<?php echo esc_attr($wid); ?>" data-recipient="<?php echo esc_attr($recipient_name); ?>">

  <!-- HEADER -->
  <div class="hvac-header">
    <div class="hvac-header-main">
      <div class="hvac-brand">
        <h2><?php echo esc_html($company); ?></h2>
        <p>Technician Service Checklist — 10 Unit Split System</p>
      </div>
      <div class="hvac-contact">
        <span class="hvac-phone"><?php echo esc_html( get_option( 'hvac_settings', array() )['phone'] ?? '(786) 322-0638' ); ?></span>
        <span class="hvac-site"><?php echo esc_html( get_option( 'hvac_settings', array() )['website'] ?? 'servicepro.tools' ); ?></span>
      </div>
    </div>
    <div class="hvac-progress">
      <div class="hvac-progress-track">
        <div class="hvac-progress-fill" id="hvac_pf_<?php echo esc_attr($wid); ?>"></div>
      </div>
      <div class="hvac-progress-label" id="hvac_pl_<?php echo esc_attr($wid); ?>">0 / 100 items</div>
    </div>
  </div>

  <!-- JOB INFO -->
  <div class="hvac-card">
    <div class="hvac-section-label">▸ Job Information</div>
    <div class="hvac-job-grid">
      <div class="hvac-job-field"><label>Property / Address</label><input type="text" id="hvac_ji_property_<?php echo esc_attr($wid); ?>" placeholder="Enter property name or address"></div>
      <div class="hvac-job-field"><label>Date of Service</label><input type="date" id="hvac_ji_date_<?php echo esc_attr($wid); ?>"></div>
      <div class="hvac-job-field"><label>Technician Name</label><input type="text" id="hvac_ji_tech_<?php echo esc_attr($wid); ?>" placeholder="Full name"></div>
      <div class="hvac-job-field"><label>Work Order #</label><input type="text" id="hvac_ji_wo_<?php echo esc_attr($wid); ?>" placeholder="—"></div>
      <div class="hvac-job-field"><label>Contract #</label><input type="text" id="hvac_ji_contract_<?php echo esc_attr($wid); ?>" placeholder="—"></div>
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

  <!-- SCOPE -->
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
      <div class="hvac-coil-note">* Evaporator coil scope: Surface-level cleaning of accessible coil only — removes loose dust and debris affecting airflow. This is NOT a deep clean. Deep cleaning requires additional labor and is billed separately.</div>
    </div>
  </div>

  <!-- UNITS LABEL -->
  <div class="hvac-units-label">▸ Unit-by-Unit Service Checklist</div>

  <!-- UNITS CONTAINER -->
  <div class="hvac-units-container"></div>

  <!-- SIGN-OFF -->
  <div class="hvac-card">
    <div class="hvac-section-label">▸ Final Sign-Off</div>
    <div class="hvac-signoff-grid">
      <label class="hvac-signoff-item"><input type="checkbox" onchange="HVACChecklist.toggleSignoff(this)"><span>All 10 units serviced and checklist complete</span></label>
      <label class="hvac-signoff-item"><input type="checkbox" onchange="HVACChecklist.toggleSignoff(this)"><span>All filters replaced</span></label>
      <label class="hvac-signoff-item"><input type="checkbox" onchange="HVACChecklist.toggleSignoff(this)"><span>Service report prepared</span></label>
      <label class="hvac-signoff-item"><input type="checkbox" onchange="HVACChecklist.toggleSignoff(this)"><span>No units require follow-up</span></label>
      <label class="hvac-signoff-item"><input type="checkbox" onchange="HVACChecklist.toggleSignoff(this)"><span>Follow-up required — see notes</span></label>
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

  <!-- SUBMIT FEEDBACK BANNER -->
  <div id="hvac_submit_banner_<?php echo esc_attr($wid); ?>"
       style="display:none;margin:0 0 10px;padding:12px 16px;border-radius:10px;font-size:13px;line-height:1.5;font-family:Arial,sans-serif;">
  </div>

  <!-- ACTION BAR -->
  <div class="hvac-action-bar">
    <button class="hvac-btn hvac-btn-secondary" onclick="HVACChecklist.clearAll('<?php echo esc_js($storage_key); ?>')">Clear All</button>
    <button class="hvac-btn hvac-btn-secondary" onclick="window.print()">🖨 Print</button>
    <button class="hvac-btn hvac-btn-submit" id="hvac_submit_btn_<?php echo esc_attr($wid); ?>"
      onclick="HVACChecklist.submit('<?php echo esc_attr($wid); ?>',<?php echo $unit_count; ?>,'<?php echo esc_js($storage_key); ?>')">
      ✉ Submit Report
    </button>
    <button class="hvac-btn hvac-btn-primary" onclick="HVACChecklist.expandAll('<?php echo esc_attr($wid); ?>',<?php echo $unit_count; ?>)">Expand All</button>
  </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  HVACChecklist.init('<?php echo esc_js($wid); ?>', <?php echo $unit_count; ?>, '<?php echo esc_js($storage_key); ?>');
});
</script>