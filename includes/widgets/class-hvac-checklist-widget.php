<?php
namespace ServiceOS_Industry_Plugin\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

if (!defined('ABSPATH')) {
    exit;
}

class HVAC_Checklist_Widget extends Widget_Base {

    public function get_name() {
        return 'hvac_field_checklist';
    }

    public function get_title() {
        return __('HVAC Field Checklist', 'serviceos-industry-hvac');
    }

    public function get_icon() {
        return 'eicon-checkbox';
    }

    public function get_categories() {
        return ['serviceos'];
    }

    public function get_keywords() {
        return ['hvac', 'checklist', 'service', 'field', 'inspection', 'maintenance'];
    }

    protected function register_controls() {
        $this->start_controls_section('section_context', [
            'label' => __('Work Order Context', 'serviceos-industry-hvac'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('operational_mode', [
            'label'   => __('Inspection Mode', 'serviceos-industry-hvac'),
            'type'    => Controls_Manager::SELECT,
            'default' => 'dynamic_url',
            'options' => [
                'dynamic_url' => __('Dynamic (Detect from URL parameters like ?wo_id=)', 'serviceos-industry-hvac'),
                'standalone'  => __('Standalone New Ticket', 'serviceos-industry-hvac'),
                'fixed_wo'    => __('Bind to Explicit Work Order ID', 'serviceos-industry-hvac'),
            ],
        ]);

        $this->add_control('fixed_work_order_id', [
            'label'       => __('Target Work Order / Ticket ID', 'serviceos-industry-hvac'),
            'type'        => Controls_Manager::TEXT,
            'input_type'  => 'number',
            'placeholder' => 'e.g. 1024',
            'condition'   => [
                'operational_mode' => 'fixed_wo',
            ],
        ]);

        $this->add_control('ji_contract', [
            'label'       => __('Contract #', 'serviceos-industry-hvac'),
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => __('Optional — auto-fills Contract field', 'serviceos-industry-hvac'),
        ]);

        $this->add_control('client_source', [
            'label'   => __('Client Source', 'serviceos-industry-hvac'),
            'type'    => Controls_Manager::SELECT,
            'default' => 'search_dropdown',
            'options' => [
                'none'            => __('None (no client lookup)', 'serviceos-industry-hvac'),
                'url_param'       => __('URL Parameter (?client_id=)', 'serviceos-industry-hvac'),
                'search_dropdown' => __('Search Dropdown (tech selects client)', 'serviceos-industry-hvac'),
            ],
        ]);

        $this->end_controls_section();

        $this->start_controls_section('section_equipment', [
            'label' => __('Equipment Ledger Options', 'serviceos-industry-hvac'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('auto_provision_assets', [
            'label'        => __('Auto-Track New Assets', 'serviceos-industry-hvac'),
            'description'  => __('If a serial number is unknown, automatically register it into the ServiceOS Equipment Tracking catalog.', 'serviceos-industry-hvac'),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __('Yes', 'serviceos-industry-hvac'),
            'label_off'    => __('No', 'serviceos-industry-hvac'),
            'return_value' => '1',
            'default'      => '1',
        ]);

        $this->end_controls_section();

        $this->start_controls_section('section_assignments', [
            'label' => __('Team Assignments', 'serviceos-industry-hvac'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('enforce_technician_lock', [
            'label'        => __('Enforce Assignment Lock', 'serviceos-industry-hvac'),
            'description'  => __('Only allows the assigned Field Technician user to view or submit this interaction panel.', 'serviceos-industry-hvac'),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __('Lock', 'serviceos-industry-hvac'),
            'label_off'    => __('Open', 'serviceos-industry-hvac'),
            'return_value' => '1',
            'default'      => '',
        ]);

        $this->end_controls_section();

        $this->start_controls_section('section_units', [
            'label' => __('Unit Configuration', 'serviceos-industry-hvac'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('unit_count', [
            'label'   => __('Default Units', 'serviceos-industry-hvac'),
            'type'    => Controls_Manager::NUMBER,
            'default' => 10,
            'min'     => 1,
            'max'     => 99,
            'step'    => 1,
        ]);

        $this->add_control('allow_unit_add_remove', [
            'label'        => __('Allow Add/Remove Units', 'serviceos-industry-hvac'),
            'description'  => __('Let technicians add or remove equipment unit sections in the field.', 'serviceos-industry-hvac'),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __('Yes', 'serviceos-industry-hvac'),
            'label_off'    => __('No', 'serviceos-industry-hvac'),
            'return_value' => '1',
            'default'      => '',
        ]);

        $this->add_control('max_units', [
            'label'     => __('Max Units', 'serviceos-industry-hvac'),
            'type'      => Controls_Manager::NUMBER,
            'default'   => 99,
            'min'       => 1,
            'max'       => 99,
            'step'      => 1,
            'condition' => [
                'allow_unit_add_remove' => '1',
            ],
        ]);

        $this->end_controls_section();

        $repeater = new Repeater();

        $repeater->add_control('item_label', [
            'label'       => __('Task Label', 'serviceos-industry-hvac'),
            'type'        => Controls_Manager::TEXT,
            'default'     => __('New checklist item', 'serviceos-industry-hvac'),
            'label_block' => true,
        ]);

        $repeater->add_control('item_default', [
            'label'        => __('Checked by Default', 'serviceos-industry-hvac'),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __('Yes', 'serviceos-industry-hvac'),
            'label_off'    => __('No', 'serviceos-industry-hvac'),
            'return_value' => '1',
            'default'      => '',
        ]);

        $this->start_controls_section('section_checklist_items', [
            'label' => __('Checklist Items', 'serviceos-industry-hvac'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('checklist_items', [
            'label'       => __('Tasks per Unit', 'serviceos-industry-hvac'),
            'type'        => Controls_Manager::REPEATER,
            'fields'      => $repeater->get_controls(),
            'default'     => [
                ['item_label' => __('Evaporator coil — light inspect & surface clean', 'serviceos-industry-hvac')],
                ['item_label' => __('Flush and treat condensate drain lines', 'serviceos-industry-hvac')],
                ['item_label' => __('Inspect control systems and safety devices', 'serviceos-industry-hvac')],
                ['item_label' => __('Check contactors and electrical components', 'serviceos-industry-hvac')],
                ['item_label' => __('Replace all air filters (included)', 'serviceos-industry-hvac')],
                ['item_label' => __('Inspect condenser fan blades and motors', 'serviceos-industry-hvac')],
                ['item_label' => __('Check refrigerant levels and system condition', 'serviceos-industry-hvac')],
                ['item_label' => __('Inspect for visible leaks / abnormal condensation', 'serviceos-industry-hvac')],
                ['item_label' => __('Evaluate overall system performance', 'serviceos-industry-hvac')],
                ['item_label' => __('Service report provided after visit', 'serviceos-industry-hvac')],
            ],
            'title_field' => '{{{ item_label }}}',
            'prevent_empty' => true,
        ]);

        $this->end_controls_section();

        $this->start_controls_section('style_section', [
            'label' => __('Brand Style', 'serviceos-industry-hvac'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('navy_color', [
            'label'     => __('Header Color', 'serviceos-industry-hvac'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#001C32',
        ]);

        $this->add_control('orange_color', [
            'label'     => __('Accent Color', 'serviceos-industry-hvac'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#E07820',
        ]);

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        $unit_count = absint($settings['unit_count'] ?? 10);
        $unit_count = max(1, min(99, $unit_count));

        $wo_id = '';
        if ('fixed_wo' === $settings['operational_mode']) {
            $wo_id = $settings['fixed_work_order_id'] ?? '';
        } elseif ('dynamic_url' === $settings['operational_mode']) {
            $wo_id = isset($_GET['wo_id']) ? sanitize_text_field(wp_unslash($_GET['wo_id'])) : '';
        }

        $auto_track = $settings['auto_provision_assets'] ?? '1';
        $technician_lock = $settings['enforce_technician_lock'] ?? '';

        $items = $settings['checklist_items'] ?? [];
        if (empty($items)) {
            $items = [
                ['item_label' => __('Evaporator coil — light inspect & surface clean', 'serviceos-industry-hvac')],
                ['item_label' => __('Flush and treat condensate drain lines', 'serviceos-industry-hvac')],
                ['item_label' => __('Inspect control systems and safety devices', 'serviceos-industry-hvac')],
                ['item_label' => __('Check contactors and electrical components', 'serviceos-industry-hvac')],
                ['item_label' => __('Replace all air filters (included)', 'serviceos-industry-hvac')],
                ['item_label' => __('Inspect condenser fan blades and motors', 'serviceos-industry-hvac')],
                ['item_label' => __('Check refrigerant levels and system condition', 'serviceos-industry-hvac')],
                ['item_label' => __('Inspect for visible leaks / abnormal condensation', 'serviceos-industry-hvac')],
                ['item_label' => __('Evaluate overall system performance', 'serviceos-industry-hvac')],
                ['item_label' => __('Service report provided after visit', 'serviceos-industry-hvac')],
            ];
        }
        $items_json = json_encode($items);

        $max_units = absint($settings['max_units'] ?? $unit_count);
        $allow_add = $settings['allow_unit_add_remove'] === '1';

        $atts = [
            'units'                     => $unit_count,
            'max_units'                 => $max_units,
            'allow_unit_add_remove'     => $allow_add ? '1' : '0',
            'items'                     => $items_json,
            'client_source'             => $settings['client_source'] ?? 'search_dropdown',
            'ji_wo'                     => $wo_id,
            'ji_contract'               => $settings['ji_contract'] ?? '',
            'allow_wo_override'         => $settings['allow_wo_override'] ?? '1',
            'enforce_assignment_lock'   => $technician_lock,
            'auto_track'                => $auto_track,
            'technician_lock'           => $technician_lock,
            'navy_color'                => $settings['navy_color'] ?? '#001C32',
            'orange_color'              => $settings['orange_color'] ?? '#E07820',
        ];

        $shortcode_str = '[hvac_checklist';
        foreach ($atts as $key => $value) {
            if ($value !== '') {
                $shortcode_str .= ' ' . $key . '="' . esc_attr($value) . '"';
            }
        }
        $shortcode_str .= ']';

        $this->enqueue_checklist_assets();

        echo do_shortcode($shortcode_str);
    }

    protected function content_template() {
        ?>
        <div style="padding:20px;background:var(--e-a-bg-default);border:1px dashed var(--e-a-border-color-bold);border-radius:8px;text-align:center;color:var(--e-a-color-txt);">
            <span class="eicon-checkbox" style="font-size:32px;display:block;margin-bottom:8px;"></span>
            <strong><?php esc_html_e('HVAC Field Checklist', 'serviceos-industry-hvac'); ?></strong>
            <p style="margin:4px 0 0;font-size:12px;"><?php esc_html_e('Units: ', 'serviceos-industry-hvac'); ?>{{{ settings.unit_count }}}</p>
            <p style="margin:2px 0 0;font-size:11px;opacity:0.6;"><?php esc_html_e('Interactive form renders on the frontend', 'serviceos-industry-hvac'); ?></p>
        </div>
        <?php
    }

    private function enqueue_checklist_assets() {
        $css_file = SERVICEOS_IP_PATH . 'assets/css/checklist.css';
        wp_enqueue_style(
            'hvac-checklist',
            SERVICEOS_IP_URL . 'assets/css/checklist.css',
            [],
            file_exists($css_file) ? filemtime($css_file) : SERVICEOS_IP_VERSION
        );

        $core_file = SERVICEOS_IP_PATH . 'assets/js/checklist-core.js';
        wp_enqueue_script(
            'hvac-checklist-core',
            SERVICEOS_IP_URL . 'assets/js/checklist-core.js',
            [],
            file_exists($core_file) ? filemtime($core_file) : SERVICEOS_IP_VERSION,
            true
        );

        $init_file = SERVICEOS_IP_PATH . 'assets/js/checklist-init.js';
        wp_enqueue_script(
            'hvac-checklist-init',
            SERVICEOS_IP_URL . 'assets/js/checklist-init.js',
            ['hvac-checklist-core'],
            file_exists($init_file) ? filemtime($init_file) : SERVICEOS_IP_VERSION,
            true
        );
    }
}
