<?php
namespace ServiceOS_Industry_Plugin;

use Service_OS_CRM\Harness\Service_OS_CRM_Harness;

class Harness extends Service_OS_CRM_Harness {
    protected $module_slug = 'hvac';
    protected $module_name = 'HVAC';
    protected $module_icon = 'ac_unit';
    protected $industry = 'HVAC';

    private $checklist_labels = [
        'Air Filter Replaced',
        'Thermostat Checked',
        'Condenser Coil Cleaned',
        'Evaporator Coil Checked',
        'Blower Operation Verified',
        'Refrigerant Level Checked',
        'Electrical Connections Checked',
        'Drain Line Inspected',
        'Safety Controls Tested',
        'System Performance Verified',
    ];

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
            ['slug' => 'submissions', 'title' => 'Field Checklists', 'icon' => 'checklist'],
            ['slug' => 'submission-detail', 'title' => 'Submission Detail', 'icon' => 'visibility'],
        ];
    }

    public function get_page_data(string $page_slug, array $params = []) {
        if ($page_slug === 'list') {
            return $this->get_list_data($params);
        } elseif ($page_slug === 'detail') {
            return $this->get_detail_data($params);
        } elseif ($page_slug === 'submissions') {
            return $this->get_submission_list($params);
        } elseif ($page_slug === 'submission-detail') {
            return $this->get_submission_detail($params);
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

    // ===== FIELD CHECKLIST SUBMISSIONS =====

    protected function get_submission_list(array $params): array {
        global $wpdb;

        $table = $wpdb->prefix . 'hvac_submissions';
        $per_page = 20;
        $page = max(1, absint($params['page'] ?? ($_GET['page_num'] ?? 1)));
        $offset = ($page - 1) * $per_page;

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");

        $submissions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} ORDER BY submitted_at DESC LIMIT %d OFFSET %d",
            $per_page, $offset
        ));

        $tech_count = (int) $wpdb->get_var("SELECT COUNT(DISTINCT technician_name) FROM {$table}");
        $recent = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE submitted_at >= %s",
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ));

        $rows = [];
        $list_url = admin_url('admin.php?page=service-os-crm-module-hvac&action=submission-detail');
        foreach ($submissions as $s) {
            $view_link = '<a href="' . esc_url(add_query_arg('id', $s->id, $list_url)) . '">View</a>';
            $delete_link = '<a href="' . esc_url(add_query_arg(['action' => 'delete', 'id' => $s->id], $list_url)) . '" onclick="return confirm(\'Delete this submission?\')">Delete</a>';
            $rows[] = [
                '#' . $s->id,
                $s->property_address,
                $s->date_of_service,
                $s->technician_name,
                $s->work_order ?: '—',
                $s->submitted_at,
                $view_link . ' | ' . $delete_link,
            ];
        }

        $data = $this->get_standard_schema();
        $data['type'] = 'list';
        $data['title'] = 'Field Checklists';
        $data['subtitle'] = 'ServicePro Field Checklist submissions';
        $data['hero_stat'] = ['label' => 'Total Submissions', 'value' => (string) $total];
        $data['tags'] = [(string) $tech_count . ' Technicians', (string) $recent . ' Last 30 Days'];
        $data['sections'] = [
            [
                'type' => 'data_table',
                'label' => 'Submissions',
                'cols' => ['ID', 'Property', 'Date', 'Technician', 'Work Order', 'Submitted', 'Actions'],
                'rows' => $rows,
            ],
        ];

        return $data;
    }

    protected function get_submission_detail(array $params): array {
        global $wpdb;

        $submission_id = absint($params['id'] ?? 0);
        if ($submission_id === 0) {
            return ['type' => 'detail', 'title' => 'Not Found'];
        }

        $sub_table = $wpdb->prefix . 'hvac_submissions';
        $items_table = $wpdb->prefix . 'hvac_unit_items';
        $signoffs_table = $wpdb->prefix . 'hvac_signoffs';

        $submission = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$sub_table} WHERE id = %d", $submission_id
        ));

        if (!$submission) {
            return ['type' => 'detail', 'title' => 'Not Found'];
        }

        $unit_items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$items_table} WHERE submission_id = %d ORDER BY unit_number, item_index",
            $submission_id
        ));

        $signoffs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$signoffs_table} WHERE submission_id = %d",
            $submission_id
        ));

        $list_url = admin_url('admin.php?page=service-os-crm-module-hvac&action=submission-detail');
        $back_url = admin_url('admin.php?page=service-os-crm-module-hvac&action=submissions');
        $delete_url = admin_url('admin.php?page=service-os-crm-module-hvac&action=submission-detail&delete=1&id=' . $submission_id);

        $data = $this->get_standard_schema();
        $data['type'] = 'detail';
        $data['title'] = 'Submission #' . $submission->id;
        $data['subtitle'] = $submission->technician_name . ' · ' . $submission->date_of_service;
        $data['hero_stat'] = ['label' => 'Units', 'value' => (string) (int) $submission->unit_count];
        $data['tags'] = ['HVAC', $submission->company_name ?: 'ServicePro'];
        $data['toolbar'] = [
            ['type' => 'back', 'url' => $back_url, 'label' => 'Back to Submissions'],
            ['type' => 'action', 'label' => 'Print', 'onclick' => 'window.print()'],
            ['type' => 'delete', 'url' => $delete_url, 'confirm' => 'Delete this submission?'],
        ];

        // info_table section — Job Info
        $data['sections'][] = [
            'type' => 'info_table',
            'label' => 'Job Info',
            'columns' => ['Property', 'Date', 'Technician', 'Work Order', 'Contract', 'Visit Type', 'Company'],
            'rows' => [[
                $submission->property_address,
                $submission->date_of_service,
                $submission->technician_name,
                $submission->work_order ?: '—',
                $submission->contract_number ?: '—',
                $submission->visit_type ?: '—',
                $submission->company_name ?: '—',
            ]],
        ];

        // unit_overview section — grouped by unit_number
        $units_overview = $this->group_units($unit_items, (int) $submission->unit_count);
        $data['sections'][] = [
            'type' => 'unit_overview',
            'label' => 'Unit Status Overview',
            'units' => $units_overview,
        ];

        // expandable_units section — full checklist details per unit
        $units_detail = $this->group_units_detail($unit_items, (int) $submission->unit_count);
        $data['sections'][] = [
            'type' => 'expandable_units',
            'label' => 'Unit Details',
            'units' => $units_detail,
        ];

        // signoffs section
        $signoff_items = [];
        foreach ($signoffs as $so) {
            $signoff_items[] = [
                'label' => $so->item_label,
                'checked' => (bool) $so->checked,
            ];
        }
        $data['sections'][] = [
            'type' => 'signoffs',
            'label' => 'Sign-offs',
            'items' => $signoff_items,
        ];

        $data['sidebar_meta'] = [
            ['label' => 'Work Order', 'value' => $submission->work_order ?: '—'],
            ['label' => 'Contract', 'value' => $submission->contract_number ?: '—'],
            ['label' => 'Visit Type', 'value' => $submission->visit_type ?: '—'],
            ['label' => 'Company', 'value' => $submission->company_name ?: '—'],
            ['label' => 'Submitted', 'value' => $submission->submitted_at],
        ];

        return $data;
    }

    private function group_units(array $items, int $unit_count): array {
        $units = [];
        for ($i = 1; $i <= $unit_count; $i++) {
            $items_for_unit = $this->items_for_unit($items, $i);
            $checked = 0;
            $total = 0;
            foreach ($items_for_unit as $item) {
                $total++;
                if ($item->checked) $checked++;
            }
            $completion = $total > 0 ? ($checked . '/' . $total) : '0/0';
            $status = $this->compute_status($items_for_unit);
            $status_color = $this->status_color($checked, $total);
            $note = '';
            $initials = '';
            foreach ($items_for_unit as $item) {
                if (!empty($item->notes)) $note = $item->notes;
                if (!empty($item->initials)) $initials = $item->initials;
            }

            $units[] = [
                'num' => $i,
                'completion' => $completion,
                'status' => $status,
                'status_color' => $status_color,
                'notes' => $note,
                'initials' => $initials,
            ];
        }
        return $units;
    }

    private function group_units_detail(array $items, int $unit_count): array {
        $units = [];
        for ($i = 1; $i <= $unit_count; $i++) {
            $items_for_unit = $this->items_for_unit($items, $i);
            $checks = [];
            foreach ($this->checklist_labels as $idx => $label) {
                $found = false;
                foreach ($items_for_unit as $item) {
                    if ($item->item_index == $idx) {
                        $checks[] = (bool) $item->checked;
                        $found = true;
                        break;
                    }
                }
                if (!$found) $checks[] = false;
            }

            $sup_temp = '';
            $ret_temp = '';
            $delta_t = '';
            $filter_size = '';
            $notes = '';
            $initials = '';
            foreach ($items_for_unit as $item) {
                if (!empty($item->supply_temp)) $sup_temp = $item->supply_temp;
                if (!empty($item->return_temp)) $ret_temp = $item->return_temp;
                if (!empty($item->delta_t)) $delta_t = $item->delta_t;
                if (!empty($item->filter_size)) $filter_size = $item->filter_size;
                if (!empty($item->notes)) $notes = $item->notes;
                if (!empty($item->initials)) $initials = $item->initials;
            }

            $units[] = [
                'num' => $i,
                'checks' => $checks,
                'check_labels' => $this->checklist_labels,
                'sup_temp' => $sup_temp,
                'ret_temp' => $ret_temp,
                'delta_t' => $delta_t,
                'filter_size' => $filter_size,
                'notes' => $notes,
                'initials' => $initials,
            ];
        }
        return $units;
    }

    private function items_for_unit(array $items, int $unit_number): array {
        return array_values(array_filter($items, function ($item) use ($unit_number) {
            return (int) $item->unit_number === $unit_number;
        }));
    }

    private function compute_status(array $items): string {
        $checked = 0;
        $total = 0;
        foreach ($items as $item) {
            $total++;
            if ($item->checked) $checked++;
        }
        if ($total === 0) return 'none';
        $pct = ($checked / $total) * 100;
        if ($pct >= 70) return 'ok';
        if ($pct >= 30) return 'mon';
        return 'action';
    }

    private function status_color(int $checked, int $total): string {
        if ($total === 0) return '#999999';
        $pct = ($checked / $total) * 100;
        if ($pct >= 70) return '#2ecc71';
        if ($pct >= 30) return '#f59e0b';
        return '#ef4444';
    }
}
