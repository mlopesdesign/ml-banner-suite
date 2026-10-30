<?php
/**
 * Plugin Name:       ML Banner Suite
 * Plugin URI:        https://tools.mlopesdesign.com.br
 * Description:       Gestão profissional de banners e integração isolada com Google AdSense, com grupos, shortcodes, estatísticas e atualização pelo GitHub.
 * Version:           3.2.0
 * Author:            Marcio Lopes
 * Author URI:        https://mlopesdesign.com.br
 * Text Domain:       ml-banner-suite
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Update URI:        https://github.com/mlopesdesign/ml-banner-suite-releases
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package MLBannerSuite
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('MLBS_VERSION')) {
    define('MLBS_VERSION', '3.2.0');
}

if (! defined('MLBS_PLUGIN_FILE')) {
    define('MLBS_PLUGIN_FILE', __FILE__);
}

if (! defined('MLBS_PLUGIN_BASENAME')) {
    define('MLBS_PLUGIN_BASENAME', plugin_basename(__FILE__));
}

if (! defined('MLBS_PLUGIN_DIR')) {
    define('MLBS_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

if (! defined('MLBS_PLUGIN_URL')) {
    define('MLBS_PLUGIN_URL', plugin_dir_url(__FILE__));
}

if (! defined('MLBS_TEXT_DOMAIN')) {
    define('MLBS_TEXT_DOMAIN', 'ml-banner-suite');
}

if (! defined('MLBS_WEBSITE_URL')) {
    define('MLBS_WEBSITE_URL', 'https://tools.mlopesdesign.com.br');
}

if (! defined('MLBS_GITHUB_REPO_URL')) {
    // Repositorio PUBLICO so com os ZIPs; o codigo-fonte sera privado (PUC sem token recebe 404 em repo privado).
    define('MLBS_GITHUB_REPO_URL', 'https://github.com/mlopesdesign/ml-banner-suite-releases');
}

if (! defined('MLBS_GITHUB_REPO_BRANCH')) {
    define('MLBS_GITHUB_REPO_BRANCH', 'main');
}

if (! defined('MLBS_PURCHASE_URL')) {
    define('MLBS_PURCHASE_URL', 'https://tools.mlopesdesign.com.br');
}

require_once MLBS_PLUGIN_DIR . 'includes/class-mlbs-loader.php';
require_once MLBS_PLUGIN_DIR . 'includes/class-mlbs-i18n.php';
require_once MLBS_PLUGIN_DIR . 'includes/class-mlbs-activator.php';
require_once MLBS_PLUGIN_DIR . 'includes/class-mlbs-deactivator.php';
require_once MLBS_PLUGIN_DIR . 'includes/class-mlbs-database.php';
require_once MLBS_PLUGIN_DIR . 'includes/class-mlbs-post-types.php';
require_once MLBS_PLUGIN_DIR . 'includes/class-mlbs-taxonomies.php';
require_once MLBS_PLUGIN_DIR . 'includes/class-mlbs-settings.php';
require_once MLBS_PLUGIN_DIR . 'includes/class-mlbs-helpers.php';
require_once MLBS_PLUGIN_DIR . 'includes/class-mlbs-assets.php';
require_once MLBS_PLUGIN_DIR . 'includes/class-mlbs-admin-menu.php';
require_once MLBS_PLUGIN_DIR . 'includes/class-mlbs-admin-pages.php';
require_once MLBS_PLUGIN_DIR . 'includes/class-mlbs-banner-editor.php';
require_once MLBS_PLUGIN_DIR . 'includes/class-mlbs-shortcodes.php';
require_once MLBS_PLUGIN_DIR . 'includes/class-mlbs-stats.php';
require_once MLBS_PLUGIN_DIR . 'includes/class-mlbs-license-manager.php';
MLBS_License_Manager::boot();
require_once MLBS_PLUGIN_DIR . 'includes/class-mlbs-plugin-updater.php';
require_once MLBS_PLUGIN_DIR . 'modules/adsense/class-mlbs-adsense-module.php';

register_activation_hook(__FILE__, array('MLBS_Activator', 'activate'));
register_deactivation_hook(__FILE__, array('MLBS_Deactivator', 'deactivate'));

final class ML_Banner_Suite
{
    private $loader;
    private $settings;
    private $plugin_updater;
    private $adsense_module;

    public function __construct()
    {
        $this->loader         = new MLBS_Loader();
        $this->settings       = new MLBS_Settings();
        $this->plugin_updater = new MLBS_Plugin_Updater(
            MLBS_PLUGIN_FILE,
            'ml-banner-suite',
            MLBS_GITHUB_REPO_URL,
            MLBS_GITHUB_REPO_BRANCH
        );
        $this->adsense_module = new MLBS_AdSense_Module();

        $this->set_locale();
        $this->define_core_hooks();
        $this->define_admin_hooks();
        $this->adsense_module->register_hooks();
        $this->plugin_updater->bootstrap();
    }

    private function set_locale()
    {
        $plugin_i18n = new MLBS_I18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    private function define_core_hooks()
    {
        $post_types    = new MLBS_Post_Types();
        $taxonomies    = new MLBS_Taxonomies();
        $assets        = new MLBS_Assets($this->settings);
        $banner_editor = new MLBS_Banner_Editor($this->settings);
        $shortcodes    = new MLBS_Shortcodes($this->settings);
        $license_manager = new MLBS_License_Manager($this->settings);

        $this->loader->add_action('init', $post_types, 'register_post_type', 5);
        $this->loader->add_action('init', $taxonomies, 'register_taxonomies', 6);
        $this->loader->add_action('init', $shortcodes, 'register_shortcodes', 9);
        $this->loader->add_action('wp_enqueue_scripts', $assets, 'enqueue_front_assets');
        $this->loader->add_action('admin_enqueue_scripts', $assets, 'enqueue_admin_assets');
        $this->loader->add_action('admin_notices', $assets, 'render_native_admin_shell');
        $this->loader->add_action('wp_ajax_mlbs_track_impression', $shortcodes, 'ajax_track_impression');
        $this->loader->add_action('wp_ajax_nopriv_mlbs_track_impression', $shortcodes, 'ajax_track_impression');
        $this->loader->add_action('admin_post_mlbs_banner_click', $shortcodes, 'handle_banner_click');
        $this->loader->add_action('admin_post_nopriv_mlbs_banner_click', $shortcodes, 'handle_banner_click');
        add_action('admin_post_mlbs_export_stats', array('MLBS_Stats', 'handle_export'));
        $this->loader->add_action('admin_post_mlbs_activate_license', $license_manager, 'handle_activate_request');
        $this->loader->add_action('admin_post_mlbs_sync_license', $license_manager, 'handle_sync_request');
        $this->loader->add_action('admin_post_mlbs_remove_license', $license_manager, 'handle_remove_request');

        $this->loader->add_action('add_meta_boxes', $banner_editor, 'register_meta_boxes');
        $this->loader->add_action('save_post_' . MLBS_Post_Types::POST_TYPE, $banner_editor, 'save_banner', 10, 2);
        $this->loader->add_action('admin_init', $banner_editor, 'register_admin_actions');
        $this->loader->add_action('wp_ajax_mlbs_upload_banner_image', $banner_editor, 'ajax_upload_banner_image');
        $this->loader->add_action('wp_ajax_mlbs_delete_banner_image', $banner_editor, 'ajax_delete_banner_image');
        $this->loader->add_action('wp_ajax_mlbs_save_banner_order', $banner_editor, 'ajax_save_banner_order');
        $this->loader->add_action('wp_ajax_mlbs_get_attachment_details', $banner_editor, 'ajax_get_attachment_details');
        $this->loader->add_action('wp_ajax_mlbs_load_wp_library', $banner_editor, 'ajax_load_wp_library');
        $this->loader->add_filter('manage_edit-' . MLBS_Post_Types::POST_TYPE . '_columns', $banner_editor, 'register_banner_columns');
        $this->loader->add_action('manage_' . MLBS_Post_Types::POST_TYPE . '_posts_custom_column', $banner_editor, 'render_banner_columns', 10, 2);
        $this->loader->add_filter('manage_edit-' . MLBS_Post_Types::POST_TYPE . '_sortable_columns', $banner_editor, 'register_sortable_columns');
        $this->loader->add_action('pre_get_posts', $banner_editor, 'handle_admin_sorting');
        $this->loader->add_filter('post_row_actions', $banner_editor, 'register_row_actions', 10, 2);

        $this->loader->add_action('mlbs_group_add_form_fields', $taxonomies, 'render_group_add_fields');
        $this->loader->add_action('mlbs_group_edit_form_fields', $taxonomies, 'render_group_edit_fields');
        $this->loader->add_action('created_mlbs_group', $taxonomies, 'save_group_fields');
        $this->loader->add_action('edited_mlbs_group', $taxonomies, 'save_group_fields');
        $this->loader->add_filter('manage_edit-mlbs_group_columns', $taxonomies, 'register_group_columns');
        $this->loader->add_filter('manage_mlbs_group_custom_column', $taxonomies, 'render_group_columns', 10, 3);
    }

    private function define_admin_hooks()
    {
        $admin_pages = new MLBS_Admin_Pages($this->settings, new MLBS_License_Manager($this->settings));
        $admin_menu  = new MLBS_Admin_Menu($admin_pages);

        $this->loader->add_action('admin_menu', $admin_menu, 'register_menu');
        $this->loader->add_filter('plugin_action_links_' . MLBS_PLUGIN_BASENAME, $admin_menu, 'add_plugin_action_links');
        $this->loader->add_filter('plugin_row_meta', $admin_menu, 'add_plugin_row_meta', 10, 2);
        $this->loader->add_filter('parent_file', $admin_menu, 'highlight_admin_parent');
        $this->loader->add_filter('submenu_file', $admin_menu, 'highlight_admin_submenu');
    }

    public function run()
    {
        $this->loader->run();
    }
}

function ml_banner_suite_run()
{
    $plugin = new ML_Banner_Suite();
    $plugin->run();
}

ml_banner_suite_run();
