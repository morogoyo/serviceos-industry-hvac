/**
 * ServiceOS HVAC — Field Checklist
 * Shared JS utility. Loaded by both 10-unit and 52-unit scripts.
 * Uses REST API instead of admin-ajax for submission.
 * Version 1.1.0
 */

window.HVACChecklist = window.HVACChecklist || {};

HVACChecklist.ITEMS = [
  "Evaporator coil — light inspect & surface clean",
  "Flush and treat condensate drain lines",
  "Inspect control systems and safety devices",
  "Check contactors and electrical components",
  "Replace all air filters (included)",
  "Inspect condenser fan blades and motors",
  "Check refrigerant levels and system condition",
  "Inspect for visible leaks / abnormal condensation",
  "Evaluate overall system performance",
  "Service report provided after visit",
];

/**
 * Build the full checklist HTML inside a wrapper element.
 * @param {string} wrapperId   - ID of the outer .expressac-wrap div
 * @param {number} unitCount   - 10 or 52
 * @param {string} storageKey  - localStorage key
 */
HVACChecklist.init = function(wrapperId, unitCount, storageKey) {
  const wrap = document.getElementById(wrapperId);
  if (!wrap) return;

  const ITEMS = HVACChecklist.ITEMS;

  // ── BUILD UNITS HTML ──────────────────────────────────────────────────────
  let unitsHTML = '';
  for (let u = 1; u <= unitCount; u++) {
    const left  = ITEMS.slice(0, 5);
    const right = ITEMS.slice(5);
    const makeItems = (arr, offset) => arr.map((item, i) => `
      <label class="hvac-check-item" id="hvac_item_${wrapperId}_${u}_${i + offset}">
        <input type="checkbox" onchange="HVACChecklist.onCheck('${wrapperId}',${u},${i + offset},${unitCount},'${storageKey}',this)">
        <span>${item}</span>
      </label>`).join('');

    unitsHTML += `
    <div class="hvac-unit" id="hvac_unit_${wrapperId}_${u}">
      <div class="hvac-unit-header" onclick="HVACChecklist.toggleUnit('${wrapperId}',${u})">
        <div class="hvac-unit-header-left">
          <span class="hvac-unit-badge">UNIT ${u}</span>
          <span class="hvac-unit-title">Split System | R-410A</span>
        </div>
        <div class="hvac-unit-header-right">
          <span class="hvac-status-pill" id="hvac_pill_${wrapperId}_${u}">0/10</span>
          <span class="hvac-chevron">▼</span>
        </div>
      </div>
      <div class="hvac-unit-body" id="hvac_body_${wrapperId}_${u}">
        <div class="hvac-check-grid">
          ${makeItems(left, 0)}
          ${makeItems(right, 5)}
        </div>
        <div class="hvac-unit-note">
          Coil cleaning scope: Surface-level cleaning of accessible coil only. Removes loose dust and debris affecting airflow. NOT a deep clean — deep cleaning is billed separately.
        </div>
        <div class="hvac-readings">
          <div class="hvac-reading"><label>Supply Temp (°F)</label><input type="number" placeholder="—" id="hvac_sup_${wrapperId}_${u}" onchange="HVACChecklist.saveData('${wrapperId}',${unitCount},'${storageKey}')"></div>
          <div class="hvac-reading"><label>Return Temp (°F)</label><input type="number" placeholder="—" id="hvac_ret_${wrapperId}_${u}" onchange="HVACChecklist.saveData('${wrapperId}',${unitCount},'${storageKey}')"></div>
          <div class="hvac-reading"><label>Delta T</label><input type="number" placeholder="—" id="hvac_dt_${wrapperId}_${u}" onchange="HVACChecklist.saveData('${wrapperId}',${unitCount},'${storageKey}')"></div>
          <div class="hvac-reading"><label>Filter Size</label><input type="text" placeholder="—" id="hvac_fs_${wrapperId}_${u}" onchange="HVACChecklist.saveData('${wrapperId}',${unitCount},'${storageKey}')"></div>
        </div>
        <div class="hvac-unit-bottom">
          <div class="hvac-notes">
            <label>Notes / Concerns</label>
            <input type="text" placeholder="Enter any notes..." id="hvac_notes_${wrapperId}_${u}" onchange="HVACChecklist.saveData('${wrapperId}',${unitCount},'${storageKey}')">
          </div>
          <div class="hvac-initials">
            <label>Tech Initials</label>
            <input type="text" maxlength="3" placeholder="—" id="hvac_init_${wrapperId}_${u}" onchange="HVACChecklist.saveData('${wrapperId}',${unitCount},'${storageKey}')">
          </div>
          <div class="hvac-status">
            <label>Status</label>
            <div class="hvac-status-btns">
              <button class="hvac-status-btn hvac-ok"     onclick="HVACChecklist.setStatus('${wrapperId}',${u},'ok','${storageKey}',${unitCount})"     id="hvac_st_${wrapperId}_${u}_ok">OK</button>
              <button class="hvac-status-btn hvac-mon"    onclick="HVACChecklist.setStatus('${wrapperId}',${u},'mon','${storageKey}',${unitCount})"    id="hvac_st_${wrapperId}_${u}_mon">Mon</button>
              <button class="hvac-status-btn hvac-action" onclick="HVACChecklist.setStatus('${wrapperId}',${u},'action','${storageKey}',${unitCount})" id="hvac_st_${wrapperId}_${u}_action">!</button>
            </div>
          </div>
        </div>
      </div>
    </div>`;
  }

  // ── INJECT HTML INTO WRAPPER ──────────────────────────────────────────────
  wrap.querySelector('.hvac-units-container').innerHTML = unitsHTML;

  // ── LOAD SAVED DATA ───────────────────────────────────────────────────────
  HVACChecklist.loadData(wrapperId, unitCount, storageKey);

  // ── SET TODAY'S DATE ──────────────────────────────────────────────────────
  const dateEl = document.getElementById('hvac_ji_date_' + wrapperId);
  if (dateEl && !dateEl.value) {
    dateEl.value = new Date().toISOString().split('T')[0];
  }

  // Auto-open unit 1 if nothing saved
  if (!localStorage.getItem(storageKey)) {
    document.getElementById(`hvac_unit_${wrapperId}_1`).classList.add('hvac-open');
  }
};

// ── TOGGLE UNIT ───────────────────────────────────────────────────────────────
HVACChecklist.toggleUnit = function(wid, u) {
  document.getElementById(`hvac_unit_${wid}_${u}`).classList.toggle('hvac-open');
};

HVACChecklist.expandAll = function(wid, count) {
  for (let u = 1; u <= count; u++) {
    document.getElementById(`hvac_unit_${wid}_${u}`).classList.add('hvac-open');
  }
};

// ── CHECKBOX HANDLER ──────────────────────────────────────────────────────────
HVACChecklist.onCheck = function(wid, u, i, count, key, el) {
  el.closest('.hvac-check-item').classList.toggle('hvac-checked', el.checked);
  HVACChecklist.updatePill(wid, u);
  HVACChecklist.updateProgress(wid, count);
  HVACChecklist.saveData(wid, count, key);
};

HVACChecklist.updatePill = function(wid, u) {
  const checks = document.querySelectorAll(`#hvac_unit_${wid}_${u} .hvac-check-item input[type="checkbox"]`);
  const done   = [...checks].filter(c => c.checked).length;
  const pill   = document.getElementById(`hvac_pill_${wid}_${u}`);
  const card   = document.getElementById(`hvac_unit_${wid}_${u}`);
  pill.textContent = `${done}/${checks.length}`;
  pill.className   = 'hvac-status-pill' + (done === checks.length ? ' hvac-done' : done > 0 ? ' hvac-partial' : '');
  card.classList.toggle('hvac-complete', done === checks.length);
  card.classList.toggle('hvac-partial',  done > 0 && done < checks.length);
};

HVACChecklist.updateProgress = function(wid, count) {
  const all  = document.querySelectorAll(`#${wid} .hvac-check-item input[type="checkbox"]`);
  const done = [...all].filter(c => c.checked).length;
  const pct  = Math.round((done / all.length) * 100);
  const fill = document.getElementById(`hvac_pf_${wid}`);
  const lbl  = document.getElementById(`hvac_pl_${wid}`);
  if (fill) fill.style.width = pct + '%';
  if (lbl)  lbl.textContent  = `${done} / ${all.length} items`;
};

// ── STATUS BUTTONS ────────────────────────────────────────────────────────────
HVACChecklist.setStatus = function(wid, u, status, key, count) {
  ['ok','mon','action'].forEach(s => {
    const btn = document.getElementById(`hvac_st_${wid}_${u}_${s}`);
    if (btn) btn.classList.toggle('hvac-active', s === status);
  });
  HVACChecklist.saveData(wid, count, key);
};

// ── SIGN-OFF ──────────────────────────────────────────────────────────────────
HVACChecklist.toggleSignoff = function(el) {
  el.closest('.hvac-signoff-item').classList.toggle('hvac-checked', el.checked);
};

// ── SCOPE TOGGLE ──────────────────────────────────────────────────────────────
HVACChecklist.toggleScope = function(btn) {
  btn.classList.toggle('hvac-open');
  btn.nextElementSibling.classList.toggle('hvac-open');
};

// ── LOCALSTORAGE ──────────────────────────────────────────────────────────────
HVACChecklist.saveData = function(wid, count, key) {
  const data = {};
  ['property','date','tech','wo','contract','visit'].forEach(k => {
    const el = document.getElementById(`hvac_ji_${k}_${wid}`);
    if (el) data[`ji_${k}`] = el.value;
  });
  for (let u = 1; u <= count; u++) {
    const checks = document.querySelectorAll(`#hvac_unit_${wid}_${u} .hvac-check-item input[type="checkbox"]`);
    data[`open_${u}`]   = document.getElementById(`hvac_unit_${wid}_${u}`).classList.contains('hvac-open');
    data[`checks_${u}`] = [...checks].map(c => c.checked);
    ['sup','ret','dt','fs','notes','init'].forEach(f => {
      const el = document.getElementById(`hvac_${f}_${wid}_${u}`);
      if (el) data[`${f}_${u}`] = el.value;
    });
    const active = ['ok','mon','action'].find(s =>
      document.getElementById(`hvac_st_${wid}_${u}_${s}`)?.classList.contains('hvac-active')
    );
    if (active) data[`status_${u}`] = active;
  }
  try { localStorage.setItem(key, JSON.stringify(data)); } catch(e) {}
};

HVACChecklist.loadData = function(wid, count, key) {
  let data;
  try { data = JSON.parse(localStorage.getItem(key) || 'null'); } catch(e) {}
  if (!data) return;
  ['property','date','tech','wo','contract','visit'].forEach(k => {
    const el = document.getElementById(`hvac_ji_${k}_${wid}`);
    if (el && data[`ji_${k}`] !== undefined) el.value = data[`ji_${k}`];
  });
  for (let u = 1; u <= count; u++) {
    if (data[`open_${u}`]) document.getElementById(`hvac_unit_${wid}_${u}`).classList.add('hvac-open');
    const checks = document.querySelectorAll(`#hvac_unit_${wid}_${u} .hvac-check-item input[type="checkbox"]`);
    (data[`checks_${u}`] || []).forEach((v, i) => {
      if (checks[i]) {
        checks[i].checked = v;
        checks[i].closest('.hvac-check-item').classList.toggle('hvac-checked', v);
      }
    });
    ['sup','ret','dt','fs','notes','init'].forEach(f => {
      const el = document.getElementById(`hvac_${f}_${wid}_${u}`);
      if (el && data[`${f}_${u}`] !== undefined) el.value = data[`${f}_${u}`];
    });
    if (data[`status_${u}`]) HVACChecklist.setStatus(wid, u, data[`status_${u}`], key, count);
    HVACChecklist.updatePill(wid, u);
  }
  HVACChecklist.updateProgress(wid, count);
};

HVACChecklist.clearAll = function(key) {
  if (!confirm('Clear all checklist data? This cannot be undone.')) return;
  try { localStorage.removeItem(key); } catch(e) {}
  location.reload();
};


// ═══════════════════════════════════════════════════════════════════════════
// SUBMIT — collects all data and POSTs to WordPress via AJAX
// ═══════════════════════════════════════════════════════════════════════════
HVACChecklist.submit = function(wid, count, key) {
  // Validate job info
  const tech = document.getElementById(`hvac_ji_tech_${wid}`);
  const prop = document.getElementById(`hvac_ji_property_${wid}`);
  if ( tech && !tech.value.trim() ) {
    alert('Please enter the technician name before submitting.');
    tech.focus();
    return;
  }
  if ( prop && !prop.value.trim() ) {
    alert('Please enter the property name before submitting.');
    prop.focus();
    return;
  }

  const recipientEl = document.getElementById(wid);
  const recipient = recipientEl?.dataset?.recipient || 'the administrator';
  if ( !confirm(`Submit this completed checklist to ${recipient}? This will send an email report immediately.`) ) return;

  const btn = document.getElementById(`hvac_submit_btn_${wid}`);
  if (btn) {
    btn.disabled = true;
    btn.textContent = 'Sending...';
  }

  // ── Build payload ───────────────────────────────────────────────────────
  const payload = {
    unit_count:   count,
    ji_property:  document.getElementById(`hvac_ji_property_${wid}`)?.value || '',
    ji_date:      document.getElementById(`hvac_ji_date_${wid}`)?.value     || '',
    ji_tech:      document.getElementById(`hvac_ji_tech_${wid}`)?.value     || '',
    ji_wo:        document.getElementById(`hvac_ji_wo_${wid}`)?.value       || '',
    ji_contract:  document.getElementById(`hvac_ji_contract_${wid}`)?.value || '',
    ji_visit:     document.getElementById(`hvac_ji_visit_${wid}`)?.value    || '',
    units:        [],
    signoff:      [],
  };

  // Units
  for (let u = 1; u <= count; u++) {
    const checks = [...document.querySelectorAll(`#hvac_unit_${wid}_${u} .hvac-check-item input[type="checkbox"]`)].map(c => c.checked);
    const active  = ['ok','mon','action'].find(s => document.getElementById(`hvac_st_${wid}_${u}_${s}`)?.classList.contains('hvac-active')) || 'none';
    payload.units.push({
      num:    u,
      checks: checks,
      status: active,
      sup:    document.getElementById(`hvac_sup_${wid}_${u}`)?.value   || '',
      ret:    document.getElementById(`hvac_ret_${wid}_${u}`)?.value   || '',
      dt:     document.getElementById(`hvac_dt_${wid}_${u}`)?.value    || '',
      fs:     document.getElementById(`hvac_fs_${wid}_${u}`)?.value    || '',
      notes:  document.getElementById(`hvac_notes_${wid}_${u}`)?.value || '',
      init:   document.getElementById(`hvac_init_${wid}_${u}`)?.value  || '',
    });
  }

  // Sign-off checkboxes
  const signoffItems = document.querySelectorAll(`#${wid} .hvac-signoff-item`);
  signoffItems.forEach(item => {
    const cb  = item.querySelector('input[type="checkbox"]');
    const lbl = item.querySelector('span');
    payload.signoff.push({
      checked: cb?.checked || false,
      label:   lbl?.textContent?.trim() || '',
    });
  });

  // ── POST to WordPress AJAX ───────────────────────────────────────────────
  // Try direct handler first (bypasses security plugin 403 errors), fallback to admin-ajax
  const ajax = window.hvac_ajax || {};
  const submitUrl = '/wp-content/hvac-submit-handler.php';
  const formData = new FormData();
  formData.append('action',  'hvac_submit');
  formData.append('nonce',   ajax.nonce || '');
  formData.append('payload', JSON.stringify(payload));

  // First try direct handler
  fetch(submitUrl, {
    method: 'POST',
    body:   formData,
    credentials: 'same-origin',
  })
  .then(function(response) {
    if (!response.ok) throw new Error('HTTP ' + response.status);
    return response.json();
  })
  .then(function(data) {
    if (data.success) {
      HVACChecklist.showSubmitSuccess(wid, data.data?.message || 'Report submitted successfully.');
    } else {
      HVACChecklist.showSubmitError(wid, data.data?.message || 'Submission failed. Trying alternate...');
      // Retry with admin-ajax as fallback
      retryWithAdminAjax(wid, payload, ajax);
    }
  })
  .catch(function(err) {
    console.error('Direct submit error:', err);
    // Try admin-ajax as fallback
    retryWithAdminAjax(wid, payload, ajax);
  });
};

var retryWithAdminAjax = function(wid, payload, ajax) {
  var btn = document.getElementById('hvac_submit_btn_' + wid);
  var formData = new FormData();
  formData.append('action',  'hvac_submit');
  formData.append('nonce',   ajax.nonce || '');
  formData.append('payload', JSON.stringify(payload));

  fetch(ajax.url || '/wp-admin/admin-ajax.php', {
    method: 'POST',
    body:   formData,
    credentials: 'same-origin',
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    if (data.success) {
      HVACChecklist.showSubmitSuccess(wid, data.data?.message || 'Report submitted successfully.');
    } else {
      HVACChecklist.showSubmitError(wid, data.data?.message || 'Submission failed. Please try again.');
      if (btn) { btn.disabled = false; btn.textContent = '✉ Submit Report'; }
    }
  })
  .catch(function(err) {
    HVACChecklist.showSubmitError(wid, 'Network error. Please check your connection and try again.');
    if (btn) { btn.disabled = false; btn.textContent = '✉ Submit Report'; }
  });
};

HVACChecklist.showSubmitSuccess = function(wid, msg) {
  const banner = document.getElementById(`hvac_submit_banner_${wid}`);
  if (!banner) return;
  const wrap = document.getElementById(wid);
  const recipient = wrap?.dataset?.recipient || 'the administrator';
  banner.style.display  = 'block';
  banner.style.background = '#D1FAE5';
  banner.style.border   = '1px solid #10B981';
  banner.style.color    = '#065F46';
  banner.innerHTML = `<strong>✓ ${msg}</strong><br><small>A formatted report has been emailed to ${recipient}. You may now print this page or clear it for your next visit.</small>`;
  banner.scrollIntoView({ behavior: 'smooth', block: 'center' });
  const btn = document.getElementById(`hvac_submit_btn_${wid}`);
  if (btn) { btn.textContent = '✓ Submitted'; btn.style.background = '#10B981'; }
};

HVACChecklist.showSubmitError = function(wid, msg) {
  const banner = document.getElementById(`hvac_submit_banner_${wid}`);
  if (!banner) return;
  banner.style.display  = 'block';
  banner.style.background = '#FEE2E2';
  banner.style.border   = '#EF4444';
  banner.style.color    = '#991B1B';
  banner.innerHTML = `<strong>✗ ${msg}</strong>`;
  banner.scrollIntoView({ behavior: 'smooth', block: 'center' });
};

// ── SUBMIT VIA REST API ──────────────────────────────────────────────────────
HVACChecklist.submitREST = function(wid, count, key, restUrl, nonce) {
  const tech = document.getElementById(`hvac_ji_tech_${wid}`);
  const prop = document.getElementById(`hvac_ji_property_${wid}`);
  if (tech && !tech.value.trim()) {
    alert('Please enter the technician name before submitting.');
    tech.focus();
    return;
  }
  if (prop && !prop.value.trim()) {
    alert('Please enter the property name before submitting.');
    prop.focus();
    return;
  }

  const recipientEl = document.getElementById(wid);
  const recipient = recipientEl?.dataset?.recipient || 'the administrator';
  if (!confirm(`Submit this completed checklist to ${recipient}? This will send an email report immediately.`)) return;

  const btn = document.getElementById(`hvac_submit_btn_${wid}`);
  if (btn) {
    btn.disabled = true;
    btn.textContent = 'Sending...';
  }

  const payload = {
    ji_contract: document.getElementById(`hvac_ji_contract_${wid}`)?.value || '',
    ji_wo: document.getElementById(`hvac_ji_wo_${wid}`)?.value || '',
    technician_id: 0,
    client_id: null,
    auto_track: recipientEl.dataset.autoTrack || '1',
    ji_property: document.getElementById(`hvac_ji_property_${wid}`)?.value || '',
    ji_date: document.getElementById(`hvac_ji_date_${wid}`)?.value || '',
    ji_tech: document.getElementById(`hvac_ji_tech_${wid}`)?.value || '',
    ji_visit: document.getElementById(`hvac_ji_visit_${wid}`)?.value || '',
    unit_count: count,
    units: [],
    signoffs: [],
  };

  for (let u = 1; u <= count; u++) {
    const checks = [...document.querySelectorAll(`#hvac_unit_${wid}_${u} .hvac-check-item input[type="checkbox"]`)].map(c => c.checked);
    const active = ['ok','mon','action'].find(s => document.getElementById(`hvac_st_${wid}_${u}_${s}`)?.classList.contains('hvac-active')) || 'none';

    const checksMap = {};
    checks.forEach((checked, idx) => {
      checksMap[HVACChecklist.ITEMS[idx]] = checked;
    });

    payload.units.push({
      unit_number: u,
      equipment_type: 'Split System',
      serial_number: '',
      model_number: '',
      checks_json: checksMap,
      status: active,
      sup: document.getElementById(`hvac_sup_${wid}_${u}`)?.value || '',
      ret: document.getElementById(`hvac_ret_${wid}_${u}`)?.value || '',
      dt: document.getElementById(`hvac_dt_${wid}_${u}`)?.value || '',
      fs: document.getElementById(`hvac_fs_${wid}_${u}`)?.value || '',
      notes: document.getElementById(`hvac_notes_${wid}_${u}`)?.value || '',
      init: document.getElementById(`hvac_init_${wid}_${u}`)?.value || '',
    });
  }

  const signoffItems = document.querySelectorAll(`#${wid} .hvac-signoff-item`);
  signoffItems.forEach(item => {
    const cb = item.querySelector('input[type="checkbox"]');
    const lbl = item.querySelector('span');
    payload.signoffs.push({
      signoff_type: 'technician',
      printed_name: (lbl?.textContent?.trim() || ''),
      signature_data: '',
      signed_at: new Date().toISOString(),
      checked: cb?.checked || false,
      label: lbl?.textContent?.trim() || '',
    });
  });

  const techSig = document.getElementById('hvac_tech_sig_' + wid);
  const techSigDate = document.getElementById('hvac_tech_sig_date_' + wid);
  payload.signoffs.push({
    signoff_type: 'technician_signature',
    printed_name: techSig ? techSig.value.trim() : '',
    signature_data: techSig ? techSig.value.trim() : '',
    signed_at: techSigDate ? techSigDate.value : '',
  });

  const clientSig = document.getElementById('hvac_client_sig_' + wid);
  const clientSigDate = document.getElementById('hvac_client_sig_date_' + wid);
  payload.signoffs.push({
    signoff_type: 'client_signature',
    printed_name: clientSig ? clientSig.value.trim() : '',
    signature_data: clientSig ? clientSig.value.trim() : '',
    signed_at: clientSigDate ? clientSigDate.value : '',
  });

  fetch(restUrl, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': nonce,
    },
    body: JSON.stringify(payload),
    credentials: 'same-origin',
  })
  .then(r => r.json())
  .then(data => {
    if (data && data.success) {
      HVACChecklist.showSubmitSuccess(wid, data.message || 'Report submitted successfully.');
    } else {
      HVACChecklist.showSubmitError(wid, (data && data.message) || 'Submission failed. Please try again.');
      if (btn) { btn.disabled = false; btn.textContent = '\u2709 Submit Report'; }
    }
  })
  .catch(err => {
    console.error('Submit error:', err);
    HVACChecklist.showSubmitError(wid, 'Network error. Please check your connection and try again.');
    if (btn) { btn.disabled = false; btn.textContent = '\u2709 Submit Report'; }
  });
};