/**
 * ServiceOS HVAC — Field Checklist
 * Shared JS utility. Uses REST API for submission.
 * Version 2.0.0 — dynamic items + add/remove units + client search
 */

window.HVACChecklist = window.HVACChecklist || {};

HVACChecklist._items = [];
HVACChecklist._unitCount = 10;
HVACChecklist._allowAdd = false;
HVACChecklist._maxUnits = 10;
HVACChecklist._clientSource = 'none';
HVACChecklist._wid = '';
HVACChecklist._storageKey = '';
HVACChecklist._restUrl = '';
HVACChecklist._nonce = '';
HVACChecklist._navy = '#001C32';
HVACChecklist._orange = '#E07820';

/**
 * Initialize the checklist.
 */
HVACChecklist.init = function(wid, items, unitCount, allowAdd, maxUnits, clientSource, storageKey, restUrl, nonce, navy, orange) {
  this._wid = wid;
  this._items = items || [];
  this._unitCount = unitCount || 1;
  this._allowAdd = allowAdd || false;
  this._maxUnits = maxUnits || unitCount;
  this._clientSource = clientSource || 'none';
  this._storageKey = storageKey || 'hvac_checklist_v2';
  this._restUrl = restUrl || '';
  this._nonce = nonce || '';
  this._navy = navy || '#001C32';
  this._orange = orange || '#E07820';

  var wrap = document.getElementById(wid);
  if (!wrap) return;

  // Build units HTML
  this._buildUnits();

  // Load saved data
  this._loadData();

  // Set today's date
  var dateEl = document.getElementById('hvac_ji_date_' + wid);
  if (dateEl && !dateEl.value) {
    dateEl.value = new Date().toISOString().split('T')[0];
  }

  // Auto-open unit 1 if nothing saved
  if (!localStorage.getItem(storageKey)) {
    var u1 = document.getElementById('hvac_unit_' + wid + '_1');
    if (u1) u1.classList.add('hvac-open');
  }

  // Load clients if needed
  if (clientSource === 'search_dropdown') {
    this._loadClients();
  }
};

// ── BUILD UNITS HTML ───────────────────────────────────────────────────────
HVACChecklist._buildUnits = function() {
  var wid = this._wid;
  var items = this._items;
  var container = document.getElementById('hvac_units_' + wid);
  if (!container) return;

  var html = '';
  var half = Math.ceil(items.length / 2);

  for (var u = 1; u <= this._unitCount; u++) {
    var leftItems = items.slice(0, half);
    var rightItems = items.slice(half);

    var makeCol = function(arr, offset) {
      return arr.map(function(item, i) {
        return '<label class="hvac-check-item" id="hvac_item_' + wid + '_' + u + '_' + (i + offset) + '">' +
          '<input type="checkbox" onchange="HVACChecklist._onCheck(' + u + ',' + (i + offset) + ',this)">' +
          '<span>' + HVACChecklist._escHtml(item) + '</span>' +
          '</label>';
      }).join('');
    };

    html += '<div class="hvac-unit" id="hvac_unit_' + wid + '_' + u + '">' +
      '<div class="hvac-unit-header" onclick="HVACChecklist._toggleUnit(' + u + ')">' +
        '<div class="hvac-unit-header-left">' +
          '<span class="hvac-unit-badge">UNIT ' + u + '</span>' +
          '<span class="hvac-unit-title">Split System | R-410A</span>' +
        '</div>' +
        '<div class="hvac-unit-header-right">' +
          '<span class="hvac-status-pill" id="hvac_pill_' + wid + '_' + u + '">0/' + items.length + '</span>' +
          '<span class="hvac-chevron">▼</span>' +
        '</div>' +
      '</div>' +
      '<div class="hvac-unit-body" id="hvac_body_' + wid + '_' + u + '">' +
        '<div class="hvac-check-grid">' +
          makeCol(leftItems, 0) +
          makeCol(rightItems, half) +
        '</div>' +
        '<div class="hvac-unit-note">' +
          'Coil cleaning scope: Surface-level cleaning of accessible coil only. Removes loose dust and debris affecting airflow. NOT a deep clean — deep cleaning is billed separately.' +
        '</div>' +
        '<div class="hvac-readings">' +
          '<div class="hvac-reading"><label>Supply Temp (°F)</label><input type="number" placeholder="—" id="hvac_sup_' + wid + '_' + u + '" onchange="HVACChecklist._saveData()"></div>' +
          '<div class="hvac-reading"><label>Return Temp (°F)</label><input type="number" placeholder="—" id="hvac_ret_' + wid + '_' + u + '" onchange="HVACChecklist._saveData()"></div>' +
          '<div class="hvac-reading"><label>Delta T</label><input type="number" placeholder="—" id="hvac_dt_' + wid + '_' + u + '" onchange="HVACChecklist._saveData()"></div>' +
          '<div class="hvac-reading"><label>Filter Size</label><input type="text" placeholder="—" id="hvac_fs_' + wid + '_' + u + '" onchange="HVACChecklist._saveData()"></div>' +
        '</div>' +
        '<div class="hvac-unit-bottom">' +
          '<div class="hvac-notes">' +
            '<label>Notes / Concerns</label>' +
            '<input type="text" placeholder="Enter any notes..." id="hvac_notes_' + wid + '_' + u + '" onchange="HVACChecklist._saveData()">' +
          '</div>' +
          '<div class="hvac-initials">' +
            '<label>Tech Initials</label>' +
            '<input type="text" maxlength="3" placeholder="—" id="hvac_init_' + wid + '_' + u + '" onchange="HVACChecklist._saveData()">' +
          '</div>' +
          '<div class="hvac-status">' +
            '<label>Status</label>' +
            '<div class="hvac-status-btns">' +
              '<button class="hvac-status-btn hvac-ok"     onclick="HVACChecklist._setStatus(' + u + ',\'ok\')"     id="hvac_st_' + wid + '_' + u + '_ok">OK</button>' +
              '<button class="hvac-status-btn hvac-mon"    onclick="HVACChecklist._setStatus(' + u + ',\'mon\')"    id="hvac_st_' + wid + '_' + u + '_mon">Mon</button>' +
              '<button class="hvac-status-btn hvac-action" onclick="HVACChecklist._setStatus(' + u + ',\'action\')" id="hvac_st_' + wid + '_' + u + '_action">!</button>' +
            '</div>' +
          '</div>' +
        '</div>' +
      '</div>';
    if (this._allowAdd || this._unitCount > 1) {
      html += '<button type="button" class="hvac-btn-remove-unit" title="Remove this unit" ' +
        'onclick="HVACChecklist.removeUnit(' + u + ')" ' +
        (this._unitCount <= 1 ? 'style="display:none"' : '') + '>✕ Remove</button>';
    }
    html += '</div>';
  }

  container.innerHTML = html;
  this._updateAddButton();
  this._updateRemoveButtons();
};

// ── ADD / REMOVE UNIT ──────────────────────────────────────────────────────
HVACChecklist.addUnit = function(widOverride) {
  var self = this;
  var wid = widOverride || self._wid;

  var container = document.getElementById('hvac_units_' + wid);
  if (!container) return;

  var currentUnits = container.querySelectorAll('.hvac-unit').length;
  if (currentUnits >= self._maxUnits) return;

  var newNum = currentUnits + 1;

  // Build single unit HTML and append
  this._unitCount = newNum;
  this._buildUnits();
  this._loadData();
};

HVACChecklist.removeUnit = function(unitNum) {
  if (this._unitCount <= 1) return;

  var unitEl = document.getElementById('hvac_unit_' + this._wid + '_' + unitNum);
  if (!unitEl) return;

  this._unitCount--;
  this._buildUnits();
  this._loadData();
};

HVACChecklist._updateAddButton = function() {
  var btn = document.getElementById('hvac_add_unit_' + this._wid);
  if (!btn) return;
  if (this._unitCount >= this._maxUnits) {
    btn.style.display = 'none';
  } else {
    btn.style.display = '';
  }
};

HVACChecklist._updateRemoveButtons = function() {
  var wid = this._wid;
  var units = document.querySelectorAll('#hvac_units_' + wid + ' .hvac-unit');
  var btns = document.querySelectorAll('#hvac_units_' + wid + ' .hvac-btn-remove-unit');
  if (units.length <= 1) {
    btns.forEach(function(b) { b.style.display = 'none'; });
  } else {
    btns.forEach(function(b) { b.style.display = ''; });
  }
};

// ── TOGGLE UNIT ────────────────────────────────────────────────────────────
HVACChecklist._toggleUnit = function(u) {
  document.getElementById('hvac_unit_' + this._wid + '_' + u).classList.toggle('hvac-open');
};

HVACChecklist.expandAll = function(widOverride) {
  var wid = widOverride || this._wid;
  var units = document.querySelectorAll('#hvac_units_' + wid + ' .hvac-unit');
  units.forEach(function(u) { u.classList.add('hvac-open'); });
};

// ── CHECKBOX HANDLER ───────────────────────────────────────────────────────
HVACChecklist._onCheck = function(u, i, el) {
  el.closest('.hvac-check-item').classList.toggle('hvac-checked', el.checked);
  this._updatePill(u);
  this._updateProgress();
  this._saveData();
};

HVACChecklist._updatePill = function(u) {
  var wid = this._wid;
  var checks = document.querySelectorAll('#hvac_unit_' + wid + '_' + u + ' .hvac-check-item input[type="checkbox"]');
  var done = Array.from(checks).filter(function(c) { return c.checked; }).length;
  var pill = document.getElementById('hvac_pill_' + wid + '_' + u);
  var card = document.getElementById('hvac_unit_' + wid + '_' + u);
  if (pill) {
    pill.textContent = done + '/' + checks.length;
    pill.className = 'hvac-status-pill' + (done === checks.length ? ' hvac-done' : done > 0 ? ' hvac-partial' : '');
  }
  if (card) {
    card.classList.toggle('hvac-complete', done === checks.length);
    card.classList.toggle('hvac-partial', done > 0 && done < checks.length);
  }
};

HVACChecklist._updateProgress = function() {
  var wid = this._wid;
  var all = document.querySelectorAll('#hvac_units_' + wid + ' .hvac-check-item input[type="checkbox"]');
  var done = Array.from(all).filter(function(c) { return c.checked; }).length;
  var pct = all.length ? Math.round((done / all.length) * 100) : 0;
  var fill = document.getElementById('hvac_pf_' + wid);
  var lbl = document.getElementById('hvac_pl_' + wid);
  if (fill) fill.style.width = pct + '%';
  if (lbl) lbl.textContent = done + ' / ' + all.length + ' items';
};

// ── STATUS BUTTONS ─────────────────────────────────────────────────────────
HVACChecklist._setStatus = function(u, status) {
  var wid = this._wid;
  ['ok','mon','action'].forEach(function(s) {
    var btn = document.getElementById('hvac_st_' + wid + '_' + u + '_' + s);
    if (btn) btn.classList.toggle('hvac-active', s === status);
  });
  this._saveData();
};

// ── CLIENT SELECT DROPDOWN ─────────────────────────────────────────────────
HVACChecklist._loadClients = function() {
  var self = this;
  var wid = self._wid;
  var select = document.getElementById('hvac_client_select_' + wid);
  if (!select) return;

  var params = new URLSearchParams(window.location.search);
  var cid = params.get('client_id');
  if (cid) select.setAttribute('data-prefill-id', cid);

  fetch('/wp-json/crm/v1/hvac/client-search?q=')
    .then(function(r) { return r.json(); })
    .then(function(data) {
      var list = Array.isArray(data) ? data : [];
      if (list.length === 0) {
        select.innerHTML = '<option value="">No clients found</option>';
        return;
      }
      var html = '<option value="">— Select Client —</option>';
      list.forEach(function(c) {
        var name = ((c.first_name || '') + ' ' + (c.last_name || '')).trim();
        html += '<option value="' + c.id + '" data-address="' + HVACChecklist._escAttr(c.address || '') + '">' +
          HVACChecklist._escHtml(name) + (c.company ? ' — ' + HVACChecklist._escHtml(c.company) : '') +
          '</option>';
      });
      select.innerHTML = html;
      self._clientList = list;

      var prefillId = parseInt(select.getAttribute('data-prefill-id'));
      if (prefillId) {
        select.value = prefillId;
        HVACChecklist._onClientSelect(wid);
      }
    })
    .catch(function(err) {
      console.error('HVAC client load error:', err);
    });
};

HVACChecklist._onClientSelect = function(wid) {
  var over = wid || this._wid;
  var select = document.getElementById('hvac_client_select_' + over);
  if (!select) return;

  var option = select.options[select.selectedIndex];
  if (!option || !option.value) return;

  var address = option.getAttribute('data-address') || '';
  var propEl = document.getElementById('hvac_ji_property_' + over);
  var contractEl = document.getElementById('hvac_ji_contract_' + over);

  if (propEl && !propEl.value && address) propEl.value = address;
  if (contractEl && !contractEl.value) contractEl.value = '';

  this._selectedClientId = parseInt(option.value);
  this._saveData();
};

// ── SCOPE TOGGLE ──────────────────────────────────────────────────────────
HVACChecklist.toggleScope = function(btn) {
  btn.classList.toggle('hvac-open');
  btn.nextElementSibling.classList.toggle('hvac-open');
};

// ── SIGN-OFF ──────────────────────────────────────────────────────────────
HVACChecklist.toggleSignoff = function(el) {
  el.closest('.hvac-signoff-item').classList.toggle('hvac-checked', el.checked);
};

// ── LOCALSTORAGE ──────────────────────────────────────────────────────────
HVACChecklist._saveData = function() {
  var wid = this._wid;
  var data = {};
  var items = this._items;
  var count = this._unitCount;

  ['property','date','tech','wo','contract','visit'].forEach(function(k) {
    var el = document.getElementById('hvac_ji_' + k + '_' + wid);
    if (el) data['ji_' + k] = el.value;
  });

  for (var u = 1; u <= count; u++) {
    var unitEl = document.getElementById('hvac_unit_' + wid + '_' + u);
    if (!unitEl) continue;
    data['open_' + u] = unitEl.classList.contains('hvac-open');

    var checks = document.querySelectorAll('#hvac_unit_' + wid + '_' + u + ' .hvac-check-item input[type="checkbox"]');
    data['checks_' + u] = Array.from(checks).map(function(c) { return c.checked; });

    ['sup','ret','dt','fs','notes','init'].forEach(function(f) {
      var el = document.getElementById('hvac_' + f + '_' + wid + '_' + u);
      if (el) data[f + '_' + u] = el.value;
    });

    var active = ['ok','mon','action'].find(function(s) {
      var btn = document.getElementById('hvac_st_' + wid + '_' + u + '_' + s);
      return btn && btn.classList.contains('hvac-active');
    });
    if (active) data['status_' + u] = active;
  }

  try { localStorage.setItem(this._storageKey, JSON.stringify(data)); } catch(e) {}
};

HVACChecklist._loadData = function() {
  var wid = this._wid;
  var items = this._items;
  var data;
  try { data = JSON.parse(localStorage.getItem(this._storageKey) || 'null'); } catch(e) {}
  if (!data) return;

  ['property','date','tech','wo','contract','visit','client_name'].forEach(function(k) {
    var el = document.getElementById('hvac_ji_' + k + '_' + wid);
    if (el && data['ji_' + k] !== undefined) el.value = data['ji_' + k];
  });

  for (var u = 1; u <= 99; u++) {
    var unitEl = document.getElementById('hvac_unit_' + wid + '_' + u);
    if (!unitEl) break;

    if (data['open_' + u]) unitEl.classList.add('hvac-open');

    var checks = document.querySelectorAll('#hvac_unit_' + wid + '_' + u + ' .hvac-check-item input[type="checkbox"]');
    (data['checks_' + u] || []).forEach(function(v, i) {
      if (checks[i]) {
        checks[i].checked = v;
        checks[i].closest('.hvac-check-item').classList.toggle('hvac-checked', v);
      }
    });

    ['sup','ret','dt','fs','notes','init'].forEach(function(f) {
      var el = document.getElementById('hvac_' + f + '_' + wid + '_' + u);
      if (el && data[f + '_' + u] !== undefined) el.value = data[f + '_' + u];
    });

    if (data['status_' + u]) HVACChecklist._setStatus(u, data['status_' + u]);
    HVACChecklist._updatePill(u);
  }
  HVACChecklist._updateProgress();
};

HVACChecklist.clearAll = function(key) {
  if (!confirm('Clear all checklist data? This cannot be undone.')) return;
  try { localStorage.removeItem(this._storageKey || key); } catch(e) {}
  location.reload();
};

// ── SUBMIT VIA REST API ────────────────────────────────────────────────────
HVACChecklist.submitREST = function(widOverride, storageKeyOverride) {
  var wid = widOverride || this._wid;
  var key = storageKeyOverride || this._storageKey;
  var items = this._items;

  var tech = document.getElementById('hvac_ji_tech_' + wid);
  var prop = document.getElementById('hvac_ji_property_' + wid);
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

  var wrap = document.getElementById(wid);
  var recipient = (wrap && wrap.dataset.recipient) || 'the administrator';
  if (!confirm('Submit this completed checklist to ' + recipient + '? This will send an email report immediately.')) return;

  var btn = document.getElementById('hvac_submit_btn_' + wid);
  if (btn) { btn.disabled = true; btn.textContent = 'Sending...'; }

  var unitEls = document.querySelectorAll('#hvac_units_' + wid + ' .hvac-unit');
  var count = unitEls.length;

  var payload = {
    ji_contract: (document.getElementById('hvac_ji_contract_' + wid) || {}).value || '',
    ji_wo: (document.getElementById('hvac_ji_wo_' + wid) || {}).value || '',
    technician_id: 0,
    client_id: this._selectedClientId || null,
    auto_track: (wrap && wrap.dataset.autoTrack) || '1',
    ji_property: (document.getElementById('hvac_ji_property_' + wid) || {}).value || '',
    ji_date: (document.getElementById('hvac_ji_date_' + wid) || {}).value || '',
    ji_tech: (document.getElementById('hvac_ji_tech_' + wid) || {}).value || '',
    ji_visit: (document.getElementById('hvac_ji_visit_' + wid) || {}).value || '',
    unit_count: count,
    units: [],
    signoffs: [],
  };

  for (var u = 1; u <= count; u++) {
    var checks = Array.from(document.querySelectorAll('#hvac_unit_' + wid + '_' + u + ' .hvac-check-item input[type="checkbox"]')).map(function(c) { return c.checked; });
    var active = ['ok','mon','action'].find(function(s) {
      var btn = document.getElementById('hvac_st_' + wid + '_' + u + '_' + s);
      return btn && btn.classList.contains('hvac-active');
    }) || 'none';

    var checksMap = {};
    if (items && items.length > 0) {
      checks.forEach(function(checked, idx) {
        checksMap[items[idx]] = checked;
      });
    } else {
      checks.forEach(function(checked, idx) {
        checksMap['Item ' + (idx + 1)] = checked;
      });
    }

    payload.units.push({
      unit_number: u,
      equipment_type: 'Split System',
      serial_number: '',
      model_number: '',
      checks_json: checksMap,
      status: active,
      sup: (document.getElementById('hvac_sup_' + wid + '_' + u) || {}).value || '',
      ret: (document.getElementById('hvac_ret_' + wid + '_' + u) || {}).value || '',
      dt: (document.getElementById('hvac_dt_' + wid + '_' + u) || {}).value || '',
      fs: (document.getElementById('hvac_fs_' + wid + '_' + u) || {}).value || '',
      notes: (document.getElementById('hvac_notes_' + wid + '_' + u) || {}).value || '',
      init: (document.getElementById('hvac_init_' + wid + '_' + u) || {}).value || '',
    });
  }

  var signoffItems = document.querySelectorAll('#' + wid + ' .hvac-signoff-item');
  signoffItems.forEach(function(item) {
    var cb = item.querySelector('input[type="checkbox"]');
    var lbl = item.querySelector('span');
    payload.signoffs.push({
      signoff_type: 'technician',
      printed_name: (lbl ? lbl.textContent.trim() : ''),
      signature_data: '',
      signed_at: new Date().toISOString(),
      checked: cb ? cb.checked : false,
      label: lbl ? lbl.textContent.trim() : '',
    });
  });

  var techSig = document.getElementById('hvac_tech_sig_' + wid);
  var techSigDate = document.getElementById('hvac_tech_sig_date_' + wid);
  payload.signoffs.push({
    signoff_type: 'technician_signature',
    printed_name: techSig ? techSig.value.trim() : '',
    signature_data: techSig ? techSig.value.trim() : '',
    signed_at: techSigDate ? techSigDate.value : '',
  });

  var clientSig = document.getElementById('hvac_client_sig_' + wid);
  var clientSigDate = document.getElementById('hvac_client_sig_date_' + wid);
  payload.signoffs.push({
    signoff_type: 'client_signature',
    printed_name: clientSig ? clientSig.value.trim() : '',
    signature_data: clientSig ? clientSig.value.trim() : '',
    signed_at: clientSigDate ? clientSigDate.value : '',
  });

  var restUrl = this._restUrl || '/wp-json/crm/v1/hvac/checklist-submit';
  var nonce = this._nonce || '';

  fetch(restUrl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
    body: JSON.stringify(payload),
    credentials: 'same-origin',
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    if (data && data.success) {
      HVACChecklist._showSubmitSuccess(wid, data.message || 'Report submitted successfully.');
    } else {
      HVACChecklist._showSubmitError(wid, (data && data.message) || 'Submission failed. Please try again.');
      if (btn) { btn.disabled = false; btn.textContent = '\u2709 Submit Report'; }
    }
  })
  .catch(function(err) {
    console.error('Submit error:', err);
    HVACChecklist._showSubmitError(wid, 'Network error. Please check your connection and try again.');
    if (btn) { btn.disabled = false; btn.textContent = '\u2709 Submit Report'; }
  });
};

HVACChecklist._showSubmitSuccess = function(wid, msg) {
  var banner = document.getElementById('hvac_submit_banner_' + wid);
  if (!banner) return;
  var wrap = document.getElementById(wid);
  var recipient = (wrap && wrap.dataset.recipient) || 'the administrator';
  banner.style.display = 'block';
  banner.style.background = '#D1FAE5';
  banner.style.border = '1px solid #10B981';
  banner.style.color = '#065F46';
  banner.innerHTML = '<strong>\u2713 ' + msg + '</strong><br><small>A formatted report has been emailed to ' + recipient + '. You may now print this page or clear it for your next visit.</small>';
  banner.scrollIntoView({ behavior: 'smooth', block: 'center' });
  var btn = document.getElementById('hvac_submit_btn_' + wid);
  if (btn) { btn.disabled = false; btn.textContent = '\u2713 Submitted'; btn.style.background = '#10B981'; }
};

HVACChecklist._showSubmitError = function(wid, msg) {
  var banner = document.getElementById('hvac_submit_banner_' + wid);
  if (!banner) return;
  banner.style.display = 'block';
  banner.style.background = '#FEE2E2';
  banner.style.border = '#EF4444';
  banner.style.color = '#991B1B';
  banner.innerHTML = '<strong>\u2717 ' + msg + '</strong>';
  banner.scrollIntoView({ behavior: 'smooth', block: 'center' });
};

// ── UTIL ───────────────────────────────────────────────────────────────────
HVACChecklist._escHtml = function(str) {
  var div = document.createElement('div');
  div.appendChild(document.createTextNode(str));
  return div.innerHTML;
};

HVACChecklist._escAttr = function(str) {
  return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
};
