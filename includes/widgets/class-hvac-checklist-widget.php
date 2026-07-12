<?php
namespace ServiceOS_Industry_Plugin\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

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

        $this->start_controls_section('content_section', [
            'label' => __('Checklist Settings', 'serviceos-industry-hvac'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('unit_count', [
            'label'   => __('Number of Units', 'serviceos-industry-hvac'),
            'type'    => Controls_Manager::SELECT,
            'default' => '10',
            'options' => [
                '10' => __('10 Units', 'serviceos-industry-hvac'),
                '52' => __('52 Units', 'serviceos-industry-hvac'),
            ],
        ]);

        $this->add_control('allow_wo_override', [
            'label'        => __('Allow Work Order Override', 'serviceos-industry-hvac'),
            'description'  => __('When off, the WO field is locked if pre-populated via URL or shortcode.', 'serviceos-industry-hvac'),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __('Yes', 'serviceos-industry-hvac'),
            'label_off'    => __('No', 'serviceos-industry-hvac'),
            'return_value' => '1',
            'default'      => '1',
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

        $unit_count = $settings['unit_count'] ?? '10';
        if (!in_array($unit_count, ['10', '52'])) {
            $unit_count = '10';
        }

        $wo_id = '';
        if ('fixed_wo' === $settings['operational_mode']) {
            $wo_id = $settings['fixed_work_order_id'] ?? '';
        } elseif ('dynamic_url' === $settings['operational_mode']) {
            $wo_id = isset($_GET['wo_id']) ? sanitize_text_field(wp_unslash($_GET['wo_id'])) : '';
        }

        $auto_track = $settings['auto_provision_assets'] ?? '1';
        $technician_lock = $settings['enforce_technician_lock'] ?? '';

        $atts = [
            'units'                  => $unit_count,
            'ji_wo'                  => $wo_id,
            'ji_contract'            => $settings['ji_contract'] ?? '',
            'allow_wo_override'      => $settings['allow_wo_override'] ?? '1',
            'enforce_assignment_lock'=> $technician_lock,
            'auto_track'             => $auto_track,
            'technician_lock'        => $technician_lock,
            'navy_color'             => $settings['navy_color'] ?? '#001C32',
            'orange_color'           => $settings['orange_color'] ?? '#E07820',
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
            <p style="margin:4px 0 0;font-size:12px;"><?php esc_html_e('Unit count: ', 'serviceos-industry-hvac'); ?>{{{ settings.unit_count }}}</p>
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

        $ten_file = SERVICEOS_IP_PATH . 'assets/js/checklist-10.js';
        wp_enqueue_script(
            'hvac-checklist-10',
            SERVICEOS_IP_URL . 'assets/js/checklist-10.js',
            ['hvac-checklist-core'],
            file_exists($ten_file) ? filemtime($ten_file) : SERVICEOS_IP_VERSION,
            true
        );

        $ft_file = SERVICEOS_IP_PATH . 'assets/js/checklist-52.js';
        wp_enqueue_script(
            'hvac-checklist-52',
            SERVICEOS_IP_URL . 'assets/js/checklist-52.js',
            ['hvac-checklist-core'],
            file_exists($ft_file) ? filemtime($ft_file) : SERVICEOS_IP_VERSION,
            true
        );
    }
}
