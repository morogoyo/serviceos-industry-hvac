/* ServicePro Field Checklist - 52 Unit Init */
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.hvac-wrap-52unit').forEach(function(el) {
    HVACChecklist.init(el.id, 52, 'hvac_52unit_v1');
  });
});