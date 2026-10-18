<?php
namespace ServiceOS_Industry_Plugin;

class Activator {
    public static function activate() {
        update_option('serviceos_ip_seed_pending', true);
        Public_Checklist::create_tables();
        Public_Checklist::maybe_migrate_tables();

        $pages = get_posts(array(
            'post_type'   => array('page', 'post'),
            'post_status' => 'publish',
            's'           => '[hvac_checklist',
            'numberposts' => 1,
        ));
        if (!empty($pages)) {
            update_option('serviceos_ip_checklist_page_url', get_permalink($pages[0]->ID));
        }
    }

    public static function deactivate() {
        // Cleanup if needed
    }
}
