<?php
/**
 * Admin page renderer.
 *
 * @package MLBannerSuite
 */

if (! defined('ABSPATH')) {
    exit;
}

class MLBS_Admin_Pages
{
    private $settings;
    private $license_manager;

    public function __construct(MLBS_Settings $settings, MLBS_License_Manager $license_manager)
    {
        $this->settings = $settings;
        $this->license_manager = $license_manager;
    }

    public function render_dashboard_page()
    {
        $settings = $this->settings->get_all();
        $banner_counts = wp_count_posts(MLBS_Post_Types::POST_TYPE);
        $group_count = wp_count_terms(array('taxonomy' => 'mlbs_group', 'hide_empty' => false));
        $published_total = (int) $banner_counts->publish;
        $active_ids = get_posts(array(
            'post_type' => MLBS_Post_Types::POST_TYPE,
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields' => 'ids',
            'meta_key' => '_mlbs_is_active',
            'meta_value' => '1',
        ));
        $active_total = is_array($active_ids) ? count($active_ids) : 0;
        $shortcode_total = max(0, (int) $group_count) + (int) $published_total;

        $php_version = PHP_VERSION;
        $min_php = '7.4';
        $wp_version = get_bloginfo('version');
        $min_wp = '6.0';
        $environment_items = array(
            array(
                'label' => __('PHP atual / mínimo', MLBS_TEXT_DOMAIN),
                'value' => $php_version . ' / ' . $min_php,
                'status' => version_compare($php_version, $min_php, '>=') ? __('OK', MLBS_TEXT_DOMAIN) : __('Atenção', MLBS_TEXT_DOMAIN),
            ),
            array(
                'label' => __('WordPress atual / mínimo', MLBS_TEXT_DOMAIN),
                'value' => $wp_version . ' / ' . $min_wp,
                'status' => version_compare($wp_version, $min_wp, '>=') ? __('OK', MLBS_TEXT_DOMAIN) : __('Atenção', MLBS_TEXT_DOMAIN),
            ),
            array(
                'label' => __('Uploads graváveis', MLBS_TEXT_DOMAIN),
                'value' => is_writable(wp_upload_dir()['basedir']) ? __('Sim', MLBS_TEXT_DOMAIN) : __('Não', MLBS_TEXT_DOMAIN),
                'status' => is_writable(wp_upload_dir()['basedir']) ? __('OK', MLBS_TEXT_DOMAIN) : __('Atenção', MLBS_TEXT_DOMAIN),
            ),
            array(
                'label' => __('GD / Imagick', MLBS_TEXT_DOMAIN),
                'value' => (extension_loaded('gd') ? 'GD ' : '') . (class_exists('Imagick') ? 'Imagick' : ''),
                'status' => (extension_loaded('gd') || class_exists('Imagick')) ? __('OK', MLBS_TEXT_DOMAIN) : __('Atenção', MLBS_TEXT_DOMAIN),
            ),
            array(
                'label' => __('Fileinfo', MLBS_TEXT_DOMAIN),
                'value' => extension_loaded('fileinfo') ? __('Disponível', MLBS_TEXT_DOMAIN) : __('Indisponível', MLBS_TEXT_DOMAIN),
                'status' => extension_loaded('fileinfo') ? __('OK', MLBS_TEXT_DOMAIN) : __('Atenção', MLBS_TEXT_DOMAIN),
            ),
            array(
                'label' => __('identify / exec para GIF', MLBS_TEXT_DOMAIN),
                'value' => ((function_exists('exec') ? __('Exec habilitado', MLBS_TEXT_DOMAIN) : __('Exec bloqueado', MLBS_TEXT_DOMAIN)) . ' • ' . (class_exists('Imagick') ? __('Imagick detectado', MLBS_TEXT_DOMAIN) : __('Imagick ausente', MLBS_TEXT_DOMAIN))),
                'status' => (function_exists('exec') || class_exists('Imagick')) ? __('OK', MLBS_TEXT_DOMAIN) : __('Opcional', MLBS_TEXT_DOMAIN),
            ),
        );

        include MLBS_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    public function render_statistics_page()
    {
        global $wpdb;

        $stats_table = MLBS_Database::get_stats_table_name();
        $banner_total = (int) wp_count_posts(MLBS_Post_Types::POST_TYPE)->publish;
        $active_query = new WP_Query(array(
            'post_type'      => MLBS_Post_Types::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'   => '_mlbs_is_active',
                    'value' => '1',
                ),
            ),
        ));
        $active_total = (int) $active_query->found_posts;
        wp_reset_postdata();
        $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $stats_table));
        include MLBS_PLUGIN_DIR . 'admin/views/statistics.php';
    }

    public function render_shortcodes_page()
    {
        include MLBS_PLUGIN_DIR . 'admin/views/shortcodes.php';
    }

    public function render_settings_page()
    {
        $settings = $this->settings->get_all();
        include MLBS_PLUGIN_DIR . 'admin/views/settings.php';
    }

    public function render_license_page()
    {
        $summary = $this->license_manager->get_license_summary();
        $license_state = $summary['state'];
        $license_product_name = $summary['product_name'];
        $license_product_id = $summary['product_id'];
        $license_server_url = $summary['server_url'];
        $license_domain = $summary['domain'];
        $site_fingerprint = $summary['site_fingerprint'];
        $plan_label = $summary['plan_label'];
        $status_label = $summary['status_label'];
        include MLBS_PLUGIN_DIR . 'admin/views/license.php';
    }
}
