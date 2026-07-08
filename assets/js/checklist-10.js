/* ServicePro Field Checklist - 10 Unit Init */
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.hvac-wrap-10unit').forEach(function(el) {
    HVACChecklist.init(el.id, 10, 'hvac_10unit_v1');
  });
});