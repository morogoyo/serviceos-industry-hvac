/**
 * HVAC Module — Custom JavaScript
 *
 * Inherits from api.js:
 *   ServiceOSAPI  — REST API helper
 *   ServiceOSModal — Modal open/close/closeAll
 *   ServiceOSToast — Toast notifications
 */

(function () {
    'use strict';

    var businessId = 1;

    function init() {
        if (window.ServiceOSHVACConfig && window.ServiceOSHVACConfig.businessId) {
            businessId = window.ServiceOSHVACConfig.businessId;
        }
    }

    window.ServiceOSHVAC = {
        openNewServiceModal: openNewServiceModal,
        saveService: saveService,
    };

    function openNewServiceModal() {
        loadCategories();
        loadPipelines();
        document.getElementById('hvac-service-form').reset();
        ServiceOSModal.open('crm-modal-hvac-service');
    }

    function saveService(event) {
        event.preventDefault();

        var title = document.getElementById('hvac-svc-title').value.trim();
        var categoryId = document.getElementById('hvac-svc-category').value;
        var value = parseFloat(document.getElementById('hvac-svc-value').value) || 0;
        var pipelineId = document.getElementById('hvac-svc-pipeline').value || null;

        if (!title) {
            ServiceOSToast.error('Please enter a service title');
            return;
        }

        var data = {
            business_id: businessId,
            title: title,
            value: value,
            module_slug: 'hvac',
            status: 'active',
        };

        if (categoryId) data.category_id = parseInt(categoryId);
        if (pipelineId) data.pipeline_id = parseInt(pipelineId);

        ServiceOSAPI.services.create(data)
            .then(function () {
                ServiceOSModal.close('crm-modal-hvac-service');
                ServiceOSToast.success('Service created');
                setTimeout(function () { location.reload(); }, 600);
            })
            .catch(function (err) {
                ServiceOSToast.error('Failed to create service: ' + (err.message || ''));
            });
    }

    function loadCategories() {
        var select = document.getElementById('hvac-svc-category');
        if (!select) return;

        ServiceOSAPI.categories.list(businessId)
            .then(function (data) {
                var cats = Array.isArray(data) ? data : (data.data || []);
                select.innerHTML = '<option value="">Select Category</option>';
                cats.forEach(function (c) {
                    var item = c.data || c;
                    select.innerHTML += '<option value="' + item.id + '">' +
                        (item.name || '') + '</option>';
                });
            })
            .catch(function () {
                select.innerHTML = '<option value="">— Could not load —</option>';
            });
    }

    function loadPipelines() {
        var select = document.getElementById('hvac-svc-pipeline');
        if (!select) return;

        ServiceOSAPI.pipelines.list(businessId)
            .then(function (data) {
                var pipes = Array.isArray(data) ? data : (data.data || []);
                select.innerHTML = '<option value="">None (no pipeline)</option>';
                pipes.forEach(function (p) {
                    var item = p.data || p;
                    select.innerHTML += '<option value="' + item.id + '">' +
                        (item.name || '') + '</option>';
                });
            })
            .catch(function () {
                select.innerHTML = '<option value="">— Could not load —</option>';
            });
    }

    document.addEventListener('DOMContentLoaded', init);
})();
