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
            ['slug' => 'detail', 'title' => 'Detail View', 'icon' => 'visibility'],
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
        $data = $this->get_standard_schema();
        $data['type'] = 'list';
        $data['title'] = $this->module_name;
        $data['subtitle'] = 'Manage your ' . $this->module_name . ' items';
        $data['hero_stat'] = ['label' => 'Total', 'value' => '0'];
        $data['toolbar'] = [
            ['type' => 'action', 'label' => 'Add New', 'onclick' => 'alert(\'Add new item\')'],
        ];
        $data['sections'] = [
            [
                'type' => 'data_table',
                'label' => 'Items',
                'columns' => ['ID', 'Title', 'Status', 'Value'],
                'rows' => [],
            ],
        ];

        return $data;
    }

    protected function get_detail_data(array $params): array {
        $item_id = $params['id'] ?? 0;
        $data = $this->get_standard_schema();
        $data['type'] = 'detail';
        $data['title'] = 'Item #' . esc_html($item_id);
        $data['toolbar'] = [
            ['type' => 'back', 'url' => admin_url('admin.php?page=service-os-crm-module-hvac'), 'label' => 'Back to List'],
        ];
        $data['sidebar_meta'] = [
            ['label' => 'ID', 'value' => $item_id],
        ];

        return $data;
    }
}
