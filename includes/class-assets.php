<?php
namespace ServiceOS_Industry_Plugin;

class Assets {
    public static function register() {
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_public']);
    }

    public static function enqueue() {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        if (strpos($page, 'service-os-crm') !== 0) {
            return;
        }

        $css_file = SERVICEOS_IP_PATH . 'assets/css/module.css';
        $js_file  = SERVICEOS_IP_PATH . 'assets/js/module.js';

        wp_enqueue_style(
            'serviceos-ip-module',
            SERVICEOS_IP_URL . 'assets/css/module.css',
            ['service-os-crm-dashboard'],
            file_exists($css_file) ? filemtime($css_file) : SERVICEOS_IP_VERSION
        );

        wp_enqueue_script(
            'serviceos-ip-module',
            SERVICEOS_IP_URL . 'assets/js/module.js',
            ['service-os-crm-api'],
            file_exists($js_file) ? filemtime($js_file) : SERVICEOS_IP_VERSION,
            true
        );

        wp_localize_script('serviceos-ip-module', 'ServiceOSHVACConfig', [
            'businessId' => (int) get_option('service_os_crm_business_id', 1),
            'moduleSlug' => 'hvac',
            'modulePage' => admin_url('admin.php?page=service-os-crm-module-hvac'),
        ]);
    }

    public static function enqueue_public() {
        if (!wp_script_is('hvac-checklist-core', 'enqueued')) {
            return;
        }

        wp_localize_script('hvac-checklist-core', 'HVACChecklistConfig', [
            'restUrl'    => rest_url('crm/v1/hvac/checklist-submit'),
            'restNonce'  => wp_create_nonce('wp_rest'),
            'businessId' => (int) get_option('service_os_crm_business_id', 0),
        ]);
    }
}
