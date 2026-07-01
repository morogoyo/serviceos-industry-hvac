<?php
namespace ServiceOS_Industry_Plugin;

class Seeder {
    public static function register() {
        add_filter('serviceos_crm_module_seed', [__CLASS__, 'seed'], 10, 2);
    }

    public static function seed(array $seed_data, string $module_slug) {
        if ($module_slug !== 'hvac') {
            return $seed_data;
        }

        $business_id = (int) get_option('service_os_crm_business_id', 0);

        return [
            'business_id' => $business_id,
            'categories' => [
                [
                    'name' => 'General',
                    'singular_label' => 'General Service',
                    'plural_label' => 'General Services',
                    'icon' => 'folder',
                    'color' => '#0073aa',
                ],
                [
                    'name' => 'Installation',
                    'singular_label' => 'Installation',
                    'plural_label' => 'Installations',
                    'icon' => 'build',
                    'color' => '#2e7d32',
                ],
                [
                    'name' => 'Repair',
                    'singular_label' => 'Repair',
                    'plural_label' => 'Repairs',
                    'icon' => 'handyman',
                    'color' => '#d84315',
                ],
                [
                    'name' => 'Maintenance',
                    'singular_label' => 'Maintenance',
                    'plural_label' => 'Maintenance',
                    'icon' => 'settings',
                    'color' => '#1565c0',
                ],
                [
                    'name' => 'Inspection',
                    'singular_label' => 'Inspection',
                    'plural_label' => 'Inspections',
                    'icon' => 'visibility',
                    'color' => '#6a1b9a',
                ],
            ],
            'pipeline' => [
                'name' => 'HVAC Sales Pipeline',
                'stages' => [
                    'Lead',
                    'Site Survey',
                    'Quote',
                    'Approved',
                    'Installation',
                    'Inspection',
                    'Won',
                    'Lost',
                ],
            ],
            'services' => [
                ['title' => 'AC Installation', 'category_slug' => 'installation', 'value' => 4500],
                ['title' => 'Furnace Replacement', 'category_slug' => 'installation', 'value' => 3500],
                ['title' => 'Diagnostic Visit', 'category_slug' => 'repair', 'value' => 99],
                ['title' => 'Annual Tune-Up', 'category_slug' => 'maintenance', 'value' => 179],
            ],
        ];
    }
}
