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
        $business_id = $this->get_business_id();

        $services_data = $this->call_rest('services', [
            'business_id' => $business_id,
            'module_slug' => 'hvac',
        ]);

        $deals_data = $this->call_rest('deals', [
            'business_id' => $business_id,
        ]);

        $submissions_data = $this->call_rest('hvac/submissions', ['per_page' => 1]);

        $services = [];
        $total = 0;
        if (is_array($services_data)) {
            $total = count($services_data);
            $services = $services_data;
        }

        $open_deals = 0;
        if (is_array($deals_data)) {
            foreach ($deals_data as $d) {
                $status = $d['status'] ?? '';
                if ($status !== 'won' && $status !== 'lost') {
                    $open_deals++;
                }
            }
        }

        $submission_count = 0;
        if (is_array($submissions_data) && isset($submissions_data['total'])) {
            $submission_count = (int) $submissions_data['total'];
        }

        $rows = [];
        foreach ($services as $svc) {
            $rows[] = [
                $svc['id'] ?? '',
                $svc['title'] ?? '',
                $svc['category_name'] ?? '—',
                $svc['status'] ?? '',
                '$' . number_format((float) ($svc['value'] ?? 0), 2),
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
        $item_id = absint($params['id'] ?? 0);
        if ($item_id === 0) {
            return ['type' => 'detail', 'title' => 'Not Found'];
        }

        $svc = $this->call_rest('services/' . $item_id);
        if (!$svc || !is_array($svc)) {
            return ['type' => 'detail', 'title' => 'Not Found'];
        }

        $data = $this->get_standard_schema();
        $data['type'] = 'detail';
        $data['title'] = $svc['title'] ?? '';
        $data['subtitle'] = ($svc['category_name'] ?? 'Uncategorized') . ' · ' . ucfirst($svc['status'] ?? '');
        $data['tags'] = [$svc['category_name'] ?? 'General'];
        $data['hero_stat'] = ['label' => 'Value', 'value' => '$' . number_format((float) ($svc['value'] ?? 0), 2)];
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
                    ['Status', ucfirst($svc['status'] ?? '')],
                    ['Category', $svc['category_name'] ?? '—'],
                    ['Pipeline', $svc['pipeline_name'] ?? '—'],
                    ['Current Stage', $svc['stage_name'] ?? '—'],
                    ['Milestone', isset($svc['milestone']) && $svc['milestone'] ? $svc['milestone'] . '%' : '0%'],
                    ['Duration', isset($svc['duration']) && $svc['duration'] ? $svc['duration'] . ' hrs' : '—'],
                ],
            ],
        ];

        $data['sidebar_meta'] = [
            ['label' => 'Service ID', 'value' => (string) $item_id],
            ['label' => 'Category', 'value' => $svc['category_name'] ?? '—'],
            ['label' => 'Pipeline', 'value' => $svc['pipeline_name'] ?? '—'],
            ['label' => 'Stage', 'value' => $svc['stage_name'] ?? '—'],
            ['label' => 'Value', 'value' => '$' . number_format((float) ($svc['value'] ?? 0), 2)],
        ];

        return $data;
    }

    // ===== FIELD CHECKLIST SUBMISSIONS =====

    protected function get_submission_list(array $params): array {
        $per_page = 20;
        $page = max(1, absint($params['page'] ?? ($_GET['page_num'] ?? 1)));

        $submissions_data = $this->call_rest('hvac/submissions', [
            'page'     => $page,
            'per_page' => $per_page,
        ]);

        $submissions = [];
        $total = 0;
        $recent = 0;
        $unique_techs = 0;

        if (is_array($submissions_data)) {
            $submissions = $submissions_data['data'] ?? [];
            $total      = (int) ($submissions_data['total'] ?? 0);
        }

        foreach ($submissions as $s) {
            $created = $s['created_at'] ?? '';
            if ($created >= date('Y-m-d H:i:s', strtotime('-30 days'))) {
                $recent++;
            }
        }

        $tech_ids = [];
        foreach ($submissions as $s) {
            $tid = $s['technician_id'] ?? 0;
            if ($tid > 0) {
                $tech_ids[$tid] = true;
            }
        }
        $unique_techs = count($tech_ids);

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
            $table_html .= '<thead><tr><th>ID</th><th>Property</th><th>Date</th><th>Contract</th><th>Work Order</th><th>Technician</th><th>Visit</th><th>Submitted</th><th>Actions</th></tr></thead>';
            $table_html .= '<tbody>';
            foreach ($submissions as $s) {
                $view_url = esc_url(add_query_arg('id', $s['id'], $detail_url));
                $tech_name = esc_html($s['technician_name'] ?: 'User #' . ($s['technician_id'] ?? 0));
                $table_html .= '<tr>';
                $table_html .= '<td><a href="' . $view_url . '">#' . (int) $s['id'] . '</a></td>';
                $table_html .= '<td>' . esc_html($s['ji_property'] ?: '—') . '</td>';
                $table_html .= '<td>' . esc_html($s['ji_date'] ?: '—') . '</td>';
                $table_html .= '<td>' . esc_html($s['ji_contract'] ?: '—') . '</td>';
                $table_html .= '<td>' . esc_html($s['ji_wo'] ?: '—') . '</td>';
                $table_html .= '<td>' . $tech_name . '</td>';
                $table_html .= '<td>' . esc_html($s['ji_visit'] ?: '—') . '</td>';
                $table_html .= '<td>' . esc_html($s['created_at']) . '</td>';
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
        $submission_id = absint($params['id'] ?? 0);
        if ($submission_id === 0) {
            return ['type' => 'detail', 'title' => 'Not Found'];
        }

        $submission = $this->call_rest('hvac/submissions/' . $submission_id);
        if (!$submission || !is_array($submission)) {
            return ['type' => 'detail', 'title' => 'Not Found'];
        }

        $unit_items = $submission['units'] ?? [];
        $signoffs = $submission['signoffs'] ?? [];

        $back_url = admin_url('admin.php?page=service-os-crm-module-hvac&action=submissions');
        $list_url = admin_url('admin.php?page=service-os-crm-module-hvac&action=submission-detail');
        $delete_url = admin_url('admin.php?page=service-os-crm-module-hvac&action=submission-detail&delete=1&id=' . $submission_id);

        $tech_name = $submission['technician_name'] ?: 'User #' . ($submission['technician_id'] ?? 0);

        $data = $this->get_standard_schema();
        $data['type'] = 'detail';
        $data['title'] = 'Submission #' . $submission['id'];
        $data['subtitle'] = $tech_name . ' · ' . ($submission['created_at'] ?? '');
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
            'columns' => ['Property', 'Date', 'Work Order', 'Contract', 'Technician', 'Visit Type', 'Submitted'],
            'rows' => [[
                $submission['ji_property'] ?: '—',
                $submission['ji_date'] ?: '—',
                $submission['ji_wo'] ?: '—',
                $submission['ji_contract'] ?: '—',
                $tech_name,
                $submission['ji_visit'] ?: '—',
                $submission['created_at'],
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
            $sig_label = '';
            $so_type = $so['signoff_type'] ?? '';
            if ($so_type === 'technician_signature' || $so_type === 'client_signature') {
                $label = $so_type === 'technician_signature' ? 'Technician Signature' : 'Client / Rep Signature';
                $name = $so['printed_name'] ?: '—';
                $date = $so['signed_at'] ?: '—';
                $sig_label = $label . ': ' . $name . ' (' . $date . ')';
            } else {
                $sig_label = $so['printed_name'] ?: '—';
                if (!empty($so['signature_data'])) {
                    $sig_label .= ' <img src="' . esc_attr($so['signature_data']) . '" style="max-height:60px;display:block;margin-top:4px;" alt="Signature">';
                }
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
            ['label' => 'Submission ID', 'value' => (string) $submission['id']],
            ['label' => 'Contract', 'value' => $submission['ji_contract'] ?: '—'],
            ['label' => 'Work Order', 'value' => $submission['ji_wo'] ?: '—'],
            ['label' => 'Technician', 'value' => $tech_name],
            ['label' => 'Client ID', 'value' => $submission['client_id'] ?: '—'],
            ['label' => 'Submitted', 'value' => $submission['created_at']],
        ];

        return $data;
    }

    private function group_units_new(array $items): array {
        $units = [];
        $grouped = [];
        foreach ($items as $item) {
            $grouped[(int) $item['unit_number']][] = $item;
        }
        foreach ($grouped as $unit_num => $unit_items) {
            $first = $unit_items[0];
            $checks = json_decode($first['checks_json'], true) ?: [];
            $checked = 0;
            $total = count($checks);
            foreach ($checks as $v) {
                if ($v) $checked++;
            }
            $completion = $total > 0 ? ($checked . '/' . $total) : '0/0';
            $db_status = $first['status'] ?: 'none';
            $status = in_array($db_status, ['ok', 'mon', 'action']) ? $db_status : ($checked >= $total ? 'ok' : ($checked > 0 ? 'mon' : 'action'));
            $colors = ['ok' => '#2ecc71', 'mon' => '#f59e0b', 'action' => '#ef4444'];

            $units[] = [
                'num'          => $unit_num,
                'completion'   => $completion,
                'status'       => $status,
                'status_color' => $colors[$status] ?? '#999',
                'notes'        => $first['notes'] ?: '',
                'initials'     => $first['init'] ?: '',
            ];
        }
        return $units;
    }

    private function group_units_detail_new(array $items): array {
        $units = [];
        $grouped = [];
        foreach ($items as $item) {
            $grouped[(int) $item['unit_number']][] = $item;
        }
        foreach ($grouped as $unit_num => $unit_items) {
            $item = $unit_items[0];
            $checks_map = json_decode($item['checks_json'], true) ?: [];
            $labels = array_keys($checks_map);
            $checks = array_values($checks_map);

            $units[] = [
                'num'          => $unit_num,
                'checks'       => $checks,
                'check_labels' => $labels,
                'sup_temp'     => $item['sup'] ?: '',
                'ret_temp'     => $item['ret'] ?: '',
                'delta_t'      => $item['dt'] ?: '',
                'filter_size'  => $item['fs'] ?: '',
                'notes'        => $item['notes'] ?: '',
                'initials'     => $item['init'] ?: '',
            ];
        }
        return $units;
    }

    private function call_rest($route, $params = []) {
        $request = new \WP_REST_Request('GET', '/crm/v1/' . $route);
        foreach ($params as $key => $value) {
            $request->set_param($key, $value);
        }
        $response = rest_do_request($request);
        if ($response->is_error()) {
            return null;
        }
        return $response->get_data();
    }

    private function get_business_id(): int {
        return (int) get_option('service_os_crm_business_id', 0);
    }
}
