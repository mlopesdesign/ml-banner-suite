<?php
/**
 * Uninstall ML Banner Suite.
 *
 * @package MLBannerSuite
 */

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// 3.1.0: licença pelo site tools — a licença local fica (reinstalar recupera); só a
// renovação diária é desagendada.
wp_clear_scheduled_hook('mllc_ml_banner_suite_refresh');

$settings = get_option('mlbs_settings', array());
if (empty($settings['remove_data_on_uninstall'])) {
    return;
}

global $wpdb;

$stats_table = $wpdb->prefix . 'mlbs_stats';
$wpdb->query("DROP TABLE IF EXISTS {$stats_table}");

delete_option('mlbs_settings');
delete_option('mlbs_db_version');
