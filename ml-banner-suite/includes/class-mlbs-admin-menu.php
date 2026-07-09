<?php
/**
 * Admin menu registration.
 *
 * @package MLBannerSuite
 */

if (! defined('ABSPATH')) {
    exit;
}

class MLBS_Admin_Menu
{
    private $admin_pages;
    const TOP_MENU_SLUG = 'mlbs-dashboard';

    public function __construct(MLBS_Admin_Pages $admin_pages)
    {
        $this->admin_pages = $admin_pages;
    }

    public function register_menu()
    {
        $capability = 'manage_options';

        add_menu_page(
            __('ML Banner Suite', MLBS_TEXT_DOMAIN),
            __('ML Banner Suite', MLBS_TEXT_DOMAIN),
            $capability,
            self::TOP_MENU_SLUG,
            array($this->admin_pages, 'render_dashboard_page'),
            'dashicons-images-alt2',
            58
        );

        add_submenu_page(self::TOP_MENU_SLUG, __('Dashboard', MLBS_TEXT_DOMAIN), __('Dashboard', MLBS_TEXT_DOMAIN), $capability, self::TOP_MENU_SLUG, array($this->admin_pages, 'render_dashboard_page'));
        add_submenu_page(self::TOP_MENU_SLUG, __('Banners', MLBS_TEXT_DOMAIN), __('Banners', MLBS_TEXT_DOMAIN), 'edit_posts', 'edit.php?post_type=' . MLBS_Post_Types::POST_TYPE);
        add_submenu_page(self::TOP_MENU_SLUG, __('Grupos', MLBS_TEXT_DOMAIN), __('Grupos', MLBS_TEXT_DOMAIN), 'manage_categories', 'edit-tags.php?taxonomy=mlbs_group&post_type=' . MLBS_Post_Types::POST_TYPE);
        add_submenu_page(self::TOP_MENU_SLUG, __('Estatísticas', MLBS_TEXT_DOMAIN), __('Estatísticas', MLBS_TEXT_DOMAIN), $capability, 'mlbs-statistics', array($this->admin_pages, 'render_statistics_page'));
        add_submenu_page(self::TOP_MENU_SLUG, __('Shortcodes', MLBS_TEXT_DOMAIN), __('Shortcodes', MLBS_TEXT_DOMAIN), $capability, 'mlbs-shortcodes', array($this->admin_pages, 'render_shortcodes_page'));
        add_submenu_page(self::TOP_MENU_SLUG, __('Configurações', MLBS_TEXT_DOMAIN), __('Configurações', MLBS_TEXT_DOMAIN), $capability, 'mlbs-settings', array($this->admin_pages, 'render_settings_page'));
        add_submenu_page(self::TOP_MENU_SLUG, __('Licença e Planos', MLBS_TEXT_DOMAIN), __('Licença e Planos', MLBS_TEXT_DOMAIN), $capability, 'mlbs-license', array($this->admin_pages, 'render_license_page'));
    }

    public function add_plugin_action_links($links)
    {
        array_unshift($links, sprintf('<a href="%s">%s</a>', esc_url(MLBS_Helpers::admin_url(self::TOP_MENU_SLUG)), esc_html__('Dashboard', MLBS_TEXT_DOMAIN)));
        $links[] = sprintf('<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url(MLBS_PURCHASE_URL), esc_html__('Assinar / Comprar', MLBS_TEXT_DOMAIN));
        return $links;
    }


    public function add_plugin_row_meta($links, $file)
    {
        if ($file !== MLBS_PLUGIN_BASENAME) {
            return $links;
        }

        $links[] = sprintf('<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url(MLBS_WEBSITE_URL), esc_html__('Site oficial', MLBS_TEXT_DOMAIN));
        $links[] = sprintf('<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url(MLBS_PURCHASE_URL), esc_html__('Assinar / Comprar', MLBS_TEXT_DOMAIN));

        return $links;
    }

    public function highlight_admin_parent($parent_file)
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (! $screen) {
            return $parent_file;
        }

        $plugin_screens = array(
            MLBS_Post_Types::POST_TYPE,
            'edit-' . MLBS_Post_Types::POST_TYPE,
            'edit-mlbs_group',
            'toplevel_page_mlbs-dashboard',
            'ml-banner-suite_page_mlbs-statistics',
            'ml-banner-suite_page_mlbs-shortcodes',
            'ml-banner-suite_page_mlbs-settings',
            'ml-banner-suite_page_mlbs-license',
        );

        if (in_array($screen->id, $plugin_screens, true)) {
            return self::TOP_MENU_SLUG;
        }

        return $parent_file;
    }

    public function highlight_admin_submenu($submenu_file)
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (! $screen) {
            return $submenu_file;
        }

        if ($screen->id === MLBS_Post_Types::POST_TYPE || $screen->id === 'edit-' . MLBS_Post_Types::POST_TYPE) {
            return 'edit.php?post_type=' . MLBS_Post_Types::POST_TYPE;
        }
        if ($screen->id === 'edit-mlbs_group') {
            return 'edit-tags.php?taxonomy=mlbs_group&post_type=' . MLBS_Post_Types::POST_TYPE;
        }
        return $submenu_file;
    }
}
