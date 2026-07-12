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

        $submission_count = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}hvac_submissions"
        );

        $open_deals = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}crm_deals d
             WHERE d.business_id = %d
             AND d.status NOT IN ('won', 'lost')",
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
        $data['tags'] = [
            (string) $submission_count . ' Field Checklists',
            (string) $open_deals . ' Open Deals',
        ];
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
            "SELECT s.*, u.display_name AS technician_name
             FROM {$table} s
             LEFT JOIN {$wpdb->users} u ON s.technician_id = u.ID
             ORDER BY s.created_at DESC
             LIMIT %d OFFSET %d",
            $per_page, $offset
        ));

        $recent = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE created_at >= %s",
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ));

        $unique_techs = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT technician_id) FROM {$table} WHERE technician_id > 0"
        );

        $data = $this->get_standard_schema();
        $data['type'] = 'list';
        $data['title'] = 'Field Checklists';
        $data['subtitle'] = 'Technician field checklist submissions';
        $data['hero_stat'] = ['label' => 'Total Submissions', 'value' => (string) $total];
        $data['tags'] = [(string) $unique_techs . ' Technicians', (string) $recent . ' Last 30 Days'];

        $detail_url = admin_url('admin.php?page=service-os-crm-module-hvac&action=submission-detail');

        $table_html = '<div class="crm-card crm-section-card">';
        $table_html .= '<h3 class="crm-section-label">Submissions</h3>';

        if (empty($submissions)) {
            $table_html .= '<p style="padding:20px;text-align:center;color:var(--on-surface);opacity:0.6;">No submissions yet.</p>';
        } else {
            $table_html .= '<div style="overflow-x:auto;">';
            $table_html .= '<table class="crm-table">';
            $table_html .= '<thead><tr><th>ID</th><th>Contract</th><th>Work Order</th><th>Technician</th><th>Submitted</th><th>Actions</th></tr></thead>';
            $table_html .= '<tbody>';
            foreach ($submissions as $s) {
                $view_url = esc_url(add_query_arg('id', $s->id, $detail_url));
                $tech_name = esc_html($s->technician_name ?: 'User #' . $s->technician_id);
                $table_html .= '<tr>';
                $table_html .= '<td><a href="' . $view_url . '">#' . (int) $s->id . '</a></td>';
                $table_html .= '<td>' . esc_html($s->ji_contract ?: '—') . '</td>';
                $table_html .= '<td>' . esc_html($s->ji_wo ?: '—') . '</td>';
                $table_html .= '<td>' . $tech_name . '</td>';
                $table_html .= '<td>' . esc_html($s->created_at) . '</td>';
                $table_html .= '<td><a href="' . $view_url . '">View</a></td>';
                $table_html .= '</tr>';
            }
            $table_html .= '</tbody></table></div>';
        }
        $table_html .= '</div>';

        $data['sections'][] = ['type' => 'html', 'content' => $table_html];

        $total_pages = (int) ceil($total / $per_page);
        if ($total_pages > 1) {
            $base_url = admin_url('admin.php?page=service-os-crm-module-hvac&action=submissions');
            $pages_html = '<div style="display:flex;gap:6px;margin-top:12px;flex-wrap:wrap;">';
            for ($i = 1; $i <= $total_pages; $i++) {
                $class = ($i === $page) ? 'crm-btn-primary' : 'crm-btn-back';
                $pages_html .= sprintf(
                    '<a href="%s" class="%s" style="min-width:36px;text-align:center;">%d</a>',
                    esc_url(add_query_arg('page_num', $i, $base_url)),
                    $class,
                    $i
                );
            }
            $pages_html .= '</div>';
            $data['sections'][] = ['type' => 'html', 'content' => $pages_html];
        }

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
            "SELECT s.*, u.display_name AS technician_name
             FROM {$sub_table} s
             LEFT JOIN {$wpdb->users} u ON s.technician_id = u.ID
             WHERE s.id = %d",
            $submission_id
        ));

        if (!$submission) {
            return ['type' => 'detail', 'title' => 'Not Found'];
        }

        $unit_items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$items_table} WHERE submission_id = %d ORDER BY unit_number",
            $submission_id
        ));

        $signoffs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$signoffs_table} WHERE submission_id = %d",
            $submission_id
        ));

        $back_url = admin_url('admin.php?page=service-os-crm-module-hvac&action=submissions');
        $list_url = admin_url('admin.php?page=service-os-crm-module-hvac&action=submission-detail');
        $delete_url = admin_url('admin.php?page=service-os-crm-module-hvac&action=submission-detail&delete=1&id=' . $submission_id);

        $tech_name = $submission->technician_name ?: 'User #' . $submission->technician_id;

        $data = $this->get_standard_schema();
        $data['type'] = 'detail';
        $data['title'] = 'Submission #' . $submission->id;
        $data['subtitle'] = $tech_name . ' · ' . $submission->created_at;
        $data['hero_stat'] = ['label' => 'Equipment Units', 'value' => (string) count($unit_items)];
        $data['tags'] = ['HVAC', 'Field Checklist'];
        $data['toolbar'] = [
            ['type' => 'back', 'url' => $back_url, 'label' => 'Back to Submissions'],
            ['type' => 'action', 'label' => 'Print', 'onclick' => 'window.print()'],
            ['type' => 'delete', 'url' => $delete_url, 'confirm' => 'Delete this submission?'],
        ];

        $data['sections'][] = [
            'type' => 'info_table',
            'label' => 'Job Info',
            'columns' => ['Contract', 'Work Order', 'Technician', 'Client ID', 'Submitted'],
            'rows' => [[
                $submission->ji_contract ?: '—',
                $submission->ji_wo ?: '—',
                $tech_name,
                $submission->client_id ?: '—',
                $submission->created_at,
            ]],
        ];

        $units_overview = $this->group_units_new($unit_items);
        if (!empty($units_overview)) {
            $data['sections'][] = [
                'type' => 'unit_overview',
                'label' => 'Equipment Overview',
                'units' => $units_overview,
            ];

            $units_detail = $this->group_units_detail_new($unit_items);
            $data['sections'][] = [
                'type' => 'expandable_units',
                'label' => 'Equipment Details',
                'units' => $units_detail,
            ];
        }

        $signoff_items = [];
        foreach ($signoffs as $so) {
            $sig_label = ucfirst($so->signoff_type) . ': ' . ($so->printed_name ?: '—');
            if (!empty($so->signature_data)) {
                $sig_label .= ' <img src="' . esc_attr($so->signature_data) . '" style="max-height:60px;display:block;margin-top:4px;" alt="Signature">';
            }
            $signoff_items[] = [
                'label'   => $sig_label,
                'checked' => true,
            ];
        }
        if (!empty($signoff_items)) {
            $data['sections'][] = [
                'type' => 'signoffs',
                'label' => 'Sign-offs',
                'items' => $signoff_items,
            ];
        }

        $data['sidebar_meta'] = [
            ['label' => 'Submission ID', 'value' => (string) $submission->id],
            ['label' => 'Contract', 'value' => $submission->ji_contract ?: '—'],
            ['label' => 'Work Order', 'value' => $submission->ji_wo ?: '—'],
            ['label' => 'Technician', 'value' => $tech_name],
            ['label' => 'Client ID', 'value' => $submission->client_id ?: '—'],
            ['label' => 'Submitted', 'value' => $submission->created_at],
        ];

        return $data;
    }

    private function group_units_new(array $items): array {
        $units = [];
        $grouped = [];
        foreach ($items as $item) {
            $grouped[(int) $item->unit_number][] = $item;
        }
        foreach ($grouped as $unit_num => $unit_items) {
            $checks = json_decode($unit_items[0]->checks_json, true) ?: [];
            $checked = 0;
            $total = count($checks);
            foreach ($checks as $v) {
                if ($v) $checked++;
            }
            $completion = $total > 0 ? ($checked . '/' . $total) : '0/0';
            $status = $checked >= $total ? 'ok' : ($checked > 0 ? 'mon' : 'action');
            $colors = ['ok' => '#2ecc71', 'mon' => '#f59e0b', 'action' => '#ef4444'];

            $units[] = [
                'num'          => $unit_num,
                'completion'   => $completion,
                'status'       => $status,
                'status_color' => $colors[$status] ?? '#999',
                'notes'        => $unit_items[0]->model_number ?: '',
                'initials'     => $unit_items[0]->serial_number ?: '',
            ];
        }
        return $units;
    }

    private function group_units_detail_new(array $items): array {
        $units = [];
        $grouped = [];
        foreach ($items as $item) {
            $grouped[(int) $item->unit_number][] = $item;
        }
        foreach ($grouped as $unit_num => $unit_items) {
            $item = $unit_items[0];
            $checks_map = json_decode($item->checks_json, true) ?: [];
            $labels = array_keys($checks_map);
            $checks = array_values($checks_map);

            $units[] = [
                'num'          => $unit_num,
                'checks'       => $checks,
                'check_labels' => $labels,
                'sup_temp'     => $item->equipment_type ?: '',
                'ret_temp'     => $item->model_number ?: '',
                'delta_t'      => $item->serial_number ?: '',
                'filter_size'  => '',
                'notes'        => $item->serial_number ? 'S/N: ' . $item->serial_number : '',
                'initials'     => $item->model_number ?: '',
            ];
        }
        return $units;
    }

}
