<?php
/**
 * Plugin Name: ServiceOS HVAC
 * Description: HVAC-specific module for ServiceOS CRM (service categories, pipeline stages, field checklists)
 * Version: 1.0.0
 * Author: ServiceOS
 * Requires Plugins: service-os-crm
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SERVICEOS_IP_VERSION', '1.0.0');
define('SERVICEOS_IP_PATH', plugin_dir_path(__FILE__));
define('SERVICEOS_IP_URL', plugin_dir_url(__FILE__));

require_once SERVICEOS_IP_PATH . 'includes/class-activator.php';
require_once SERVICEOS_IP_PATH . 'includes/class-seeder.php';
require_once SERVICEOS_IP_PATH . 'includes/class-harness.php';
require_once SERVICEOS_IP_PATH . 'includes/class-assets.php';
require_once SERVICEOS_IP_PATH . 'includes/class-email.php';
require_once SERVICEOS_IP_PATH . 'includes/class-public.php';

register_activation_hook(__FILE__, ['ServiceOS_Industry_Plugin\\Activator', 'activate']);
register_deactivation_hook(__FILE__, ['ServiceOS_Industry_Plugin\\Activator', 'deactivate']);

ServiceOS_Industry_Plugin\Public_Checklist::register();

add_action('plugins_loaded', function () {
    if (!class_exists('Service_OS_CRM\\Harness\\Service_OS_CRM_Harness')) {
        return;
    }

    ServiceOS_Industry_Plugin\Seeder::register();
    ServiceOS_Industry_Plugin\Assets::register();

    add_action('init', function () {
        $harness = new ServiceOS_Industry_Plugin\Harness();
        $harness->register_with_crm();
    }, 99);
});

add_action('elementor/elements/categories_registered', function ($elements_manager) {
    $elements_manager->add_category('serviceos', [
        'title' => __('ServiceOS', 'serviceos-industry-hvac'),
        'icon'  => 'fa fa-wrench',
    ]);
});

add_action('elementor/widgets/register', function ($widgets_manager) {
    require_once SERVICEOS_IP_PATH . 'includes/widgets/class-hvac-checklist-widget.php';
    $widgets_manager->register(new \ServiceOS_Industry_Plugin\Widgets\HVAC_Checklist_Widget());
});
