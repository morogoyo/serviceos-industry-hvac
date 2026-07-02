/**
 * HVAC Module — Custom JavaScript
 *
 * Inherits from api.js:
 *   ServiceOSAPI  — REST API helper
 *   ServiceOSModal — Modal open/close/closeAll
 *   ServiceOSToast — Toast notifications
 *   ServiceOSTheme — Dark/light theme toggle
 */

(function () {
    'use strict';

    window.ServiceOSHVAC = {
        openNewServiceModal: openNewServiceModal,
        createDeal: createDeal,
    };

    var businessId = 1;

    function init() {
        if (window.ServiceOSHVACConfig && window.ServiceOSHVACConfig.businessId) {
            businessId = window.ServiceOSHVACConfig.businessId;
        }
    }

    function openNewServiceModal() {
        var modalId = 'hvac-new-service-modal';
        var existing = document.getElementById(modalId);

        if (!existing) {
            existing = document.createElement('div');
            existing.id = modalId;
            existing.className = 'crm-modal';
            existing.style.display = 'none';
            existing.innerHTML =
                '<div class="crm-modal-dialog">' +
                '<div class="crm-modal-header">' +
                '<h2>New HVAC Service</h2>' +
                '<button class="crm-modal-close" onclick="ServiceOSModal.close(\'' + modalId + '\')">' +
                '<span class="material-symbols-outlined">close</span></button>' +
                '</div>' +
                '<div class="crm-modal-body">' +
                '<div class="crm-form-group">' +
                '<label>Service Title</label>' +
                '<input type="text" id="hvac-svc-title" class="crm-input" placeholder="e.g. Central AC Install (3-ton)">' +
                '</div>' +
                '<div class="crm-form-group">' +
                '<label>Category</label>' +
                '<select id="hvac-svc-category" class="crm-select"></select>' +
                '</div>' +
                '<div class="crm-form-group">' +
                '<label>Pipeline</label>' +
                '<select id="hvac-svc-pipeline" class="crm-select"></select>' +
                '</div>' +
                '<div class="crm-form-group">' +
                '<label>Value ($)</label>' +
                '<input type="number" id="hvac-svc-value" class="crm-input" placeholder="0.00" step="0.01">' +
                '</div>' +
                '</div>' +
                '<div class="crm-modal-footer">' +
                '<button class="crm-btn-secondary" onclick="ServiceOSModal.close(\'' + modalId + '\')">Cancel</button>' +
                '<button class="crm-btn-primary" onclick="window.ServiceOSHVAC.submitNewService()">Create Service</button>' +
                '</div>' +
                '</div>';
            document.body.appendChild(existing);
        }

        ServiceOSModal.open(modalId);
        loadCategories(businessId);
        loadPipelines(businessId);
    }

    window.ServiceOSHVAC.submitNewService = function () {
        var title = document.getElementById('hvac-svc-title').value.trim();
        var categoryId = parseInt(document.getElementById('hvac-svc-category').value) || null;
        var pipelineId = parseInt(document.getElementById('hvac-svc-pipeline').value) || null;
        var value = parseFloat(document.getElementById('hvac-svc-value').value) || 0;

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

        if (categoryId) {
            data.category_id = categoryId;
        }

        ServiceOSAPI.deals.create(data)
            .then(function (result) {
                ServiceOSModal.close('hvac-new-service-modal');
                ServiceOSToast.success('Service created');
                setTimeout(function () { location.reload(); }, 500);
            })
            .catch(function (err) {
                ServiceOSToast.error('Failed to create service: ' + (err.message || ''));
            });
    };

    function createDeal(serviceId) {
        ServiceOSAPI.deals.create({
            business_id: businessId,
            service_id: serviceId,
            title: '',
            status: 'new',
        })
            .then(function (result) {
                var deal = result.data || result;
                var dealId = deal.id;
                ServiceOSToast.success('Deal created #' + dealId);
                if (dealId) {
                    window.location.href = window.ServiceOSCRM.ajaxEndpoint.replace(
                        'admin-ajax.php',
                        'admin.php?page=service-os-crm-dashboard'
                    );
                }
            })
            .catch(function (err) {
                ServiceOSToast.error('Failed to create deal: ' + (err.message || ''));
            });
    }

    function loadCategories(bizId) {
        var select = document.getElementById('hvac-svc-category');
        if (!select) return;

        ServiceOSAPI.categories.list(bizId)
            .then(function (data) {
                var cats = Array.isArray(data) ? data : (data.data || []);
                select.innerHTML = '<option value="">— Select Category —</option>';
                cats.forEach(function (c) {
                    var item = c.data || c;
                    select.innerHTML += '<option value="' + item.id + '">' + (item.name || '') + '</option>';
                });
            })
            .catch(function () {
                select.innerHTML = '<option value="">— Could not load categories —</option>';
            });
    }

    function loadPipelines(bizId) {
        var select = document.getElementById('hvac-svc-pipeline');
        if (!select) return;

        ServiceOSAPI.pipelines.list(bizId)
            .then(function (data) {
                var pipes = Array.isArray(data) ? data : (data.data || []);
                select.innerHTML = '<option value="">— Select Pipeline —</option>';
                pipes.forEach(function (p) {
                    var item = p.data || p;
                    select.innerHTML += '<option value="' + item.id + '">' + (item.name || '') + '</option>';
                });
            })
            .catch(function () {
                select.innerHTML = '<option value="">— Could not load pipelines —</option>';
            });
    }

    document.addEventListener('DOMContentLoaded', init);
})();
