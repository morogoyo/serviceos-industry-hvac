<?php
namespace ServiceOS_Industry_Plugin;

use Service_OS_CRM\Harness\Service_OS_CRM_Harness;

class Harness extends Service_OS_CRM_Harness {
    protected $module_slug = 'hvac';
    protected $module_name = 'HVAC';
    protected $module_icon = 'ac_unit';
    protected $industry = 'HVAC';

    protected function get_module_info(): array {
        return [
            'name' => $this->module_name,
            'slug' => $this->module_slug,
            'industry' => $this->industry,
            'description' => 'HVAC field service management with checklists, service reports, and pipeline tracking',
            'menu_label' => $this->module_name,
            'menu_icon' => $this->module_icon,
            'plugin_file' => 'serviceos-industry-hvac/serviceos-industry-hvac.php',
            'plugin_class' => __CLASS__,
            'version' => SERVICEOS_IP_VERSION,
        ];
    }

    protected function get_pages(): array {
        return [
            ['slug' => 'list', 'title' => 'HVAC Dashboard', 'icon' => $this->module_icon],
            ['slug' => 'detail', 'title' => 'Service Detail', 'icon' => 'visibility'],
        ];
    }

    public function get_page_data(string $page_slug, array $params = []) {
        if ($page_slug === 'list') {
            return $this->get_list_data($params);
        } elseif ($page_slug === 'detail') {
            return $this->get_detail_data($params);
        }
        return ['type' => 'detail', 'title' => 'Not Found'];
    }

    protected function get_list_data(array $params): array {
        global $wpdb;

        $business_id = (int) get_option('service_os_crm_business_id', 0);

        $services = $wpdb->get_results($wpdb->prepare(
            "SELECT s.id, s.title, s.status, s.value, s.pipeline_id, s.stage_id,
                    c.name AS category_name, c.slug AS category_slug, c.color AS category_color
             FROM {$wpdb->prefix}crm_services s
             LEFT JOIN {$wpdb->prefix}crm_service_categories c ON s.category_id = c.id
             WHERE s.module_slug = 'hvac'
             AND s.business_id = %d
             ORDER BY c.name, s.title",
            $business_id
        ));

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}crm_services
             WHERE module_slug = 'hvac' AND business_id = %d",
            $business_id
        ));

        $rows = [];
        foreach ($services as $svc) {
            $rows[] = [
                $svc->id,
                $svc->title,
                $svc->category_name ?? '—',
                $svc->status,
                '$' . number_format((float) $svc->value, 2),
            ];
        }

        $data = $this->get_standard_schema();
        $data['type'] = 'list';
        $data['title'] = 'HVAC Dashboard';
        $data['subtitle'] = 'Service catalog and deal pipeline for HVAC operations';
        $data['hero_stat'] = ['label' => 'Services', 'value' => (string) $total];
        $data['toolbar'] = [
            ['type' => 'action', 'label' => 'New Service', 'onclick' => 'window.ServiceOSHVAC.openNewServiceModal()'],
        ];
        $data['sections'] = [
            [
                'type' => 'data_table',
                'label' => 'HVAC Services',
                'cols' => ['ID', 'Service', 'Category', 'Status', 'Value'],
                'rows' => $rows,
            ],
            [
                'type' => 'html',
                'content' => '
<div id="crm-modal-hvac-service" class="crm-modal" style="display: none;">
    <div class="crm-modal-dialog" style="max-width: 500px;">
        <div class="crm-modal-header">
            <div>
                <h3>New HVAC Service</h3>
                <p>Add a service to the HVAC catalog</p>
            </div>
            <button class="crm-modal-close" onclick="ServiceOSModal.close(\'crm-modal-hvac-service\')">&times;</button>
        </div>
        <form id="hvac-service-form" onsubmit="ServiceOSHVAC.saveService(event)">
            <div class="crm-modal-body">
                <div class="crm-form-group">
                    <label for="hvac-svc-title">Service Title *</label>
                    <input type="text" id="hvac-svc-title" name="title" required placeholder="e.g., Central AC Install (3-ton)">
                </div>
                <div class="crm-form-row">
                    <div class="crm-form-group">
                        <label for="hvac-svc-category">Category</label>
                        <select id="hvac-svc-category" name="category_id">
                            <option value="">Select Category</option>
                        </select>
                    </div>
                    <div class="crm-form-group">
                        <label for="hvac-svc-value">Value ($)</label>
                        <input type="number" id="hvac-svc-value" name="value" min="0" step="0.01" placeholder="0.00">
                    </div>
                </div>
                <div class="crm-form-group">
                    <label for="hvac-svc-pipeline">Pipeline</label>
                    <select id="hvac-svc-pipeline" name="pipeline_id">
                        <option value="">None (no pipeline)</option>
                    </select>
                </div>
            </div>
            <div class="crm-modal-footer">
                <button type="button" class="crm-btn-cancel" onclick="ServiceOSModal.close(\'crm-modal-hvac-service\')">Cancel</button>
                <button type="submit" class="crm-btn-save">Save Service</button>
            </div>
        </form>
    </div>
</div>',
            ],
        ];

        return $data;
    }

    protected function get_detail_data(array $params): array {
        global $wpdb;

        $item_id = absint($params['id'] ?? 0);
        if ($item_id === 0) {
            return ['type' => 'detail', 'title' => 'Not Found'];
        }

        $svc = $wpdb->get_row($wpdb->prepare(
            "SELECT s.id, s.title, s.status, s.value, s.milestone, s.duration,
                    s.pipeline_id, s.stage_id,
                    c.name AS category_name, c.slug AS category_slug, c.color AS category_color,
                    p.name AS pipeline_name,
                    ps.name AS stage_name
             FROM {$wpdb->prefix}crm_services s
             LEFT JOIN {$wpdb->prefix}crm_service_categories c ON s.category_id = c.id
             LEFT JOIN {$wpdb->prefix}crm_pipelines p ON s.pipeline_id = p.id
             LEFT JOIN {$wpdb->prefix}crm_pipeline_stages ps ON s.stage_id = ps.id
             WHERE s.id = %d",
            $item_id
        ));

        if (!$svc) {
            return ['type' => 'detail', 'title' => 'Not Found'];
        }

        $data = $this->get_standard_schema();
        $data['type'] = 'detail';
        $data['title'] = $svc->title;
        $data['subtitle'] = ($svc->category_name ?? 'Uncategorized') . ' · ' . ucfirst($svc->status);
        $data['tags'] = [$svc->category_name ?? 'General'];
        $data['hero_stat'] = ['label' => 'Value', 'value' => '$' . number_format((float) $svc->value, 2)];
        $data['toolbar'] = [
            ['type' => 'back', 'url' => admin_url('admin.php?page=service-os-crm-module-hvac'), 'label' => 'Back to Dashboard'],
            ['type' => 'action', 'label' => 'Create Deal', 'onclick' => 'window.ServiceOSHVAC.createDeal(' . $item_id . ')'],
        ];

        $data['sections'] = [
            [
                'type' => 'info_table',
                'label' => 'Service Info',
                'columns' => ['Field', 'Value'],
                'rows' => [
                    ['Status', ucfirst($svc->status)],
                    ['Category', $svc->category_name ?? '—'],
                    ['Pipeline', $svc->pipeline_name ?? '—'],
                    ['Current Stage', $svc->stage_name ?? '—'],
                    ['Milestone', $svc->milestone ? $svc->milestone . '%' : '0%'],
                    ['Duration', $svc->duration ? $svc->duration . ' hrs' : '—'],
                ],
            ],
        ];

        $data['sidebar_meta'] = [
            ['label' => 'Service ID', 'value' => (string) $svc->id],
            ['label' => 'Category', 'value' => $svc->category_name ?? '—'],
            ['label' => 'Pipeline', 'value' => $svc->pipeline_name ?? '—'],
            ['label' => 'Stage', 'value' => $svc->stage_name ?? '—'],
            ['label' => 'Value', 'value' => '$' . number_format((float) $svc->value, 2)],
        ];

        return $data;
    }
}
