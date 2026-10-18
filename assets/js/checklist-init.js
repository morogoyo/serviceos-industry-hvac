/**
 * ServiceOS HVAC — Checklist Init
 * Replaces checklist-10.js and checklist-52.js.
 * Reads data attributes from .hvac-wrap and initializes the checklist.
 */
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.hvac-wrap').forEach(function(wrap) {
    var wid = wrap.id;
    if (!wid) return;

    var itemsJson = wrap.getAttribute('data-items') || '[]';
    var items = [];
    try { items = JSON.parse(itemsJson); } catch(e) {}

    var unitCount = parseInt(wrap.getAttribute('data-default-units')) || 1;
    var allowAdd = wrap.getAttribute('data-allow-add-remove') === '1';
    var maxUnits = parseInt(wrap.getAttribute('data-max-units')) || unitCount;
    var clientSource = wrap.getAttribute('data-client-source') || 'none';
    var storageKey = wrap.getAttribute('data-storage-key') || 'hvac_checklist_v2';
    var restUrl = wrap.getAttribute('data-rest-url') || '';
    var nonce = wrap.getAttribute('data-nonce') || '';
    var navy = '#001C32';
    var orange = '#E07820';

    var styleEl = document.getElementById('hvac-elementor-style-' + wid);
    if (styleEl) {
      var m = styleEl.textContent.match(/--navy:\s*([^;]+);.*--orange:\s*([^;]+);/);
      if (m) { navy = m[1].trim(); orange = m[2].trim(); }
    }

    HVACChecklist.init(wid, items, unitCount, allowAdd, maxUnits, clientSource, storageKey, restUrl, nonce, navy, orange);
  });
});
