<?php
/**
 * Plugin activator.
 *
 * @package MLBannerSuite
 */

if (! defined('ABSPATH')) {
    exit;
}

class MLBS_Activator
{
    public static function activate()
    {
        MLBS_Database::create_tables();
        MLBS_Settings::maybe_add_default_options();
        MLBS_Helpers::ensure_banner_directory();

        $post_types = new MLBS_Post_Types();
        $taxonomies = new MLBS_Taxonomies();

        $post_types->register_post_type();
        $taxonomies->register_taxonomies();

        flush_rewrite_rules();
    }
}
