<?php
/**
 * Uninstall ML Banner Suite.
 *
 * @package MLBannerSuite
 */

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$settings = get_option('mlbs_settings', array());
if (empty($settings['remove_data_on_uninstall'])) {
    return;
}

global $wpdb;

$stats_table = $wpdb->prefix . 'mlbs_stats';
$wpdb->query("DROP TABLE IF EXISTS {$stats_table}");

delete_option('mlbs_settings');
delete_option('mlbs_db_version');
