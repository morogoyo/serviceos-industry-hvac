<?php
namespace ServiceOS_Industry_Plugin;

class Seeder {
    const SERVICE_PIPELINE_NAME = 'Service & Repair Pipeline';

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
            'pipelines' => [
                [
                    'name' => 'HVAC Sales Pipeline',
                    'stages' => [
                        'Lead',
                        'Qualified',
                        'Site Survey',
                        'Quote Sent',
                        'Negotiation',
                        'Contract Signed',
                        'Permitting',
                        'Equipment Ordered',
                        'Installation',
                        'Inspection',
                        'Final Walkthrough',
                        'Completed',
                    ],
                ],
                [
                    'name' => 'Service & Repair Pipeline',
                    'stages' => [
                        'Scheduled',
                        'Dispatched',
                        'Diagnosed',
                        'Repair Complete',
                        'Invoiced',
                        'Paid',
                    ],
                ],
            ],
            'categories' => [
                [
                    'name' => 'AC & Cooling',
                    'slug' => 'ac-cooling',
                    'singular_label' => 'AC Job',
                    'plural_label' => 'AC & Cooling',
                    'icon' => 'ac_unit',
                    'color' => '#1565c0',
                    'pipeline' => 'HVAC Sales Pipeline',
                ],
                [
                    'name' => 'Heating',
                    'slug' => 'heating',
                    'singular_label' => 'Heating Job',
                    'plural_label' => 'Heating',
                    'icon' => 'whatshot',
                    'color' => '#d84315',
                    'pipeline' => 'HVAC Sales Pipeline',
                ],
                [
                    'name' => 'Repair & Diagnostics',
                    'slug' => 'repair-diagnostics',
                    'singular_label' => 'Repair',
                    'plural_label' => 'Repairs & Diagnostics',
                    'icon' => 'handyman',
                    'color' => '#e65100',
                    'pipeline' => 'Service & Repair Pipeline',
                ],
                [
                    'name' => 'Air Quality',
                    'slug' => 'air-quality',
                    'singular_label' => 'IAQ Job',
                    'plural_label' => 'Air Quality',
                    'icon' => 'air',
                    'color' => '#2e7d32',
                    'pipeline' => 'HVAC Sales Pipeline',
                ],
                [
                    'name' => 'Ductwork',
                    'slug' => 'ductwork',
                    'singular_label' => 'Duct Job',
                    'plural_label' => 'Ductwork',
                    'icon' => 'layers',
                    'color' => '#795548',
                    'pipeline' => 'HVAC Sales Pipeline',
                ],
                [
                    'name' => 'Thermostats & Controls',
                    'slug' => 'thermostats-controls',
                    'singular_label' => 'Controls Job',
                    'plural_label' => 'Thermostats & Controls',
                    'icon' => 'sensors',
                    'color' => '#00695c',
                    'pipeline' => 'HVAC Sales Pipeline',
                ],
                [
                    'name' => 'Maintenance Plans',
                    'slug' => 'maintenance-plans',
                    'singular_label' => 'Maintenance',
                    'plural_label' => 'Maintenance Plans',
                    'icon' => 'event_repeat',
                    'color' => '#283593',
                    'pipeline' => 'Service & Repair Pipeline',
                ],
                [
                    'name' => 'Commercial HVAC',
                    'slug' => 'commercial-hvac',
                    'singular_label' => 'Commercial Job',
                    'plural_label' => 'Commercial HVAC',
                    'icon' => 'business',
                    'color' => '#37474f',
                    'pipeline' => 'HVAC Sales Pipeline',
                ],
                [
                    'name' => 'New Construction',
                    'slug' => 'new-construction',
                    'singular_label' => 'New Build',
                    'plural_label' => 'New Construction',
                    'icon' => 'construction',
                    'color' => '#bf360c',
                    'pipeline' => 'HVAC Sales Pipeline',
                ],
                [
                    'name' => 'General',
                    'slug' => 'general',
                    'singular_label' => 'General',
                    'plural_label' => 'General',
                    'icon' => 'folder',
                    'color' => '#0073aa',
                    'pipeline' => 'HVAC Sales Pipeline',
                ],
            ],
            'services' => [
                // AC & Cooling
                ['title' => 'Central AC Install (3-ton)', 'category_slug' => 'ac-cooling', 'value' => 6500, 'pipeline' => 'HVAC Sales Pipeline', 'stage' => 'Quote Sent'],
                ['title' => 'Central AC Install (5-ton)', 'category_slug' => 'ac-cooling', 'value' => 9000, 'pipeline' => 'HVAC Sales Pipeline', 'stage' => 'Quote Sent'],
                ['title' => 'Heat Pump Install', 'category_slug' => 'ac-cooling', 'value' => 8500, 'pipeline' => 'HVAC Sales Pipeline', 'stage' => 'Quote Sent'],
                ['title' => 'Mini-Split Single Zone', 'category_slug' => 'ac-cooling', 'value' => 4000, 'pipeline' => 'HVAC Sales Pipeline', 'stage' => 'Quote Sent'],
                ['title' => 'Mini-Split Multi-Zone', 'category_slug' => 'ac-cooling', 'value' => 10000, 'pipeline' => 'HVAC Sales Pipeline', 'stage' => 'Quote Sent'],
                ['title' => 'Condenser Replacement', 'category_slug' => 'ac-cooling', 'value' => 3500, 'pipeline' => 'Service & Repair Pipeline', 'stage' => 'Invoiced'],
                ['title' => 'Evaporator Coil Replacement', 'category_slug' => 'ac-cooling', 'value' => 2200, 'pipeline' => 'Service & Repair Pipeline', 'stage' => 'Invoiced'],

                // Heating
                ['title' => 'Gas Furnace Install (80% AFUE)', 'category_slug' => 'heating', 'value' => 4000, 'pipeline' => 'HVAC Sales Pipeline', 'stage' => 'Quote Sent'],
                ['title' => 'Gas Furnace Install (95%+ AFUE)', 'category_slug' => 'heating', 'value' => 6500, 'pipeline' => 'HVAC Sales Pipeline', 'stage' => 'Quote Sent'],
                ['title' => 'Electric Furnace Install', 'category_slug' => 'heating', 'value' => 3000, 'pipeline' => 'HVAC Sales Pipeline', 'stage' => 'Quote Sent'],
                ['title' => 'Boiler Install', 'category_slug' => 'heating', 'value' => 8000, 'pipeline' => 'HVAC Sales Pipeline', 'stage' => 'Quote Sent'],
                ['title' => 'Heat Exchanger Replacement', 'category_slug' => 'heating', 'value' => 2500, 'pipeline' => 'Service & Repair Pipeline', 'stage' => 'Invoiced'],

                // Repair & Diagnostics
                ['title' => 'Diagnostic Service Call', 'category_slug' => 'repair-diagnostics', 'value' => 99, 'pipeline' => 'Service & Repair Pipeline', 'stage' => 'Scheduled'],
                ['title' => 'Capacitor Replacement', 'category_slug' => 'repair-diagnostics', 'value' => 300, 'pipeline' => 'Service & Repair Pipeline', 'stage' => 'Repair Complete'],
                ['title' => 'Blower Motor Replacement', 'category_slug' => 'repair-diagnostics', 'value' => 1000, 'pipeline' => 'Service & Repair Pipeline', 'stage' => 'Repair Complete'],
                ['title' => 'Compressor Replacement', 'category_slug' => 'repair-diagnostics', 'value' => 2500, 'pipeline' => 'Service & Repair Pipeline', 'stage' => 'Repair Complete'],
                ['title' => 'Refrigerant Leak Repair', 'category_slug' => 'repair-diagnostics', 'value' => 800, 'pipeline' => 'Service & Repair Pipeline', 'stage' => 'Repair Complete'],
                ['title' => 'Emergency After-Hours Service', 'category_slug' => 'repair-diagnostics', 'value' => 249, 'pipeline' => 'Service & Repair Pipeline', 'stage' => 'Scheduled'],

                // Air Quality
                ['title' => 'Whole-Home Air Purifier', 'category_slug' => 'air-quality', 'value' => 1500, 'pipeline' => 'HVAC Sales Pipeline', 'stage' => 'Quote Sent'],
                ['title' => 'Humidifier Install', 'category_slug' => 'air-quality', 'value' => 800, 'pipeline' => 'HVAC Sales Pipeline', 'stage' => 'Quote Sent'],
                ['title' => 'ERV/HRV Install', 'category_slug' => 'air-quality', 'value' => 3000, 'pipeline' => 'HVAC Sales Pipeline', 'stage' => 'Quote Sent'],
                ['title' => 'Dehumidifier Install', 'category_slug' => 'air-quality', 'value' => 1800, 'pipeline' => 'HVAC Sales Pipeline', 'stage' => 'Quote Sent'],

                // Ductwork
                ['title' => 'Duct Replacement (Full Home)', 'category_slug' => 'ductwork', 'value' => 5500, 'pipeline' => 'HVAC Sales Pipeline', 'stage' => 'Quote Sent'],
                ['title' => 'Duct Cleaning', 'category_slug' => 'ductwork', 'value' => 800, 'pipeline' => 'Service & Repair Pipeline', 'stage' => 'Invoiced'],
                ['title' => 'Duct Sealing', 'category_slug' => 'ductwork', 'value' => 1200, 'pipeline' => 'Service & Repair Pipeline', 'stage' => 'Invoiced'],
                ['title' => 'Duct Insulation', 'category_slug' => 'ductwork', 'value' => 2000, 'pipeline' => 'HVAC Sales Pipeline', 'stage' => 'Quote Sent'],

                // Thermostats & Controls
                ['title' => 'Smart Thermostat Install', 'category_slug' => 'thermostats-controls', 'value' => 500, 'pipeline' => 'HVAC Sales Pipeline', 'stage' => 'Quote Sent'],
                ['title' => 'Zoning System Install', 'category_slug' => 'thermostats-controls', 'value' => 3500, 'pipeline' => 'HVAC Sales Pipeline', 'stage' => 'Quote Sent'],
                ['title' => 'Wi-Fi Thermostat Install', 'category_slug' => 'thermostats-controls', 'value' => 350, 'pipeline' => 'HVAC Sales Pipeline', 'stage' => 'Quote Sent'],

                // Maintenance Plans
                ['title' => 'AC Tune-Up', 'category_slug' => 'maintenance-plans', 'value' => 159, 'pipeline' => 'Service & Repair Pipeline', 'stage' => 'Scheduled'],
                ['title' => 'Furnace Tune-Up', 'category_slug' => 'maintenance-plans', 'value' => 159, 'pipeline' => 'Service & Repair Pipeline', 'stage' => 'Scheduled'],
                ['title' => 'Annual Plan (1 Visit)', 'category_slug' => 'maintenance-plans', 'value' => 299, 'pipeline' => 'Service & Repair Pipeline', 'stage' => 'Scheduled'],
                ['title' => 'Annual Plan (2 Visits)', 'category_slug' => 'maintenance-plans', 'value' => 449, 'pipeline' => 'Service & Repair Pipeline', 'stage' => 'Scheduled'],

                // Commercial HVAC
                ['title' => 'Rooftop Unit Install (5-ton)', 'category_slug' => 'commercial-hvac', 'value' => 25000, 'pipeline' => 'HVAC Sales Pipeline', 'stage' => 'Quote Sent'],
                ['title' => 'VRF System Install', 'category_slug' => 'commercial-hvac', 'value' => 40000, 'pipeline' => 'HVAC Sales Pipeline', 'stage' => 'Quote Sent'],
                ['title' => 'Rooftop Unit Repair', 'category_slug' => 'commercial-hvac', 'value' => 3500, 'pipeline' => 'Service & Repair Pipeline', 'stage' => 'Repair Complete'],

                // New Construction
                ['title' => 'New Build HVAC Rough-In', 'category_slug' => 'new-construction', 'value' => 8000, 'pipeline' => 'HVAC Sales Pipeline', 'stage' => 'Quote Sent'],
                ['title' => 'New Build Full HVAC System', 'category_slug' => 'new-construction', 'value' => 12000, 'pipeline' => 'HVAC Sales Pipeline', 'stage' => 'Quote Sent'],
            ],
        ];
    }
}
