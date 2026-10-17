<?php
/**
 * AdSense administration.
 *
 * @package MLBannerSuite
 */

if (! defined('ABSPATH')) {
    exit;
}

class MLBS_AdSense_Admin
{
    const PAGE_SLUG = 'mlbs-adsense';

    private $settings;

    public function __construct(MLBS_AdSense_Settings $settings)
    {
        $this->settings = $settings;
    }

    public function register_menu()
    {
        add_submenu_page(
            MLBS_Admin_Menu::TOP_MENU_SLUG,
            __('Google AdSense', MLBS_TEXT_DOMAIN),
            __('AdSense', MLBS_TEXT_DOMAIN),
            'manage_options',
            self::PAGE_SLUG,
            array($this, 'render_page')
        );
    }

    public function enqueue_assets($hook_suffix)
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (! $screen || ! $this->is_mlbs_screen($screen->id, $hook_suffix)) {
            return;
        }

        if ($screen->id === 'ml-banner-suite_page_' . self::PAGE_SLUG) {
            wp_enqueue_style('mlbs-admin', MLBS_PLUGIN_URL . 'assets/css/admin.css', array(), MLBS_VERSION);
        }

        wp_enqueue_style(
            'mlbs-adsense-admin',
            MLBS_PLUGIN_URL . 'modules/adsense/assets/css/admin.css',
            array('mlbs-admin'),
            MLBS_VERSION
        );
        wp_enqueue_script(
            'mlbs-adsense-admin',
            MLBS_PLUGIN_URL . 'modules/adsense/assets/js/admin.js',
            array('jquery'),
            MLBS_VERSION,
            true
        );
        wp_localize_script('mlbs-adsense-admin', 'mlbsAdsenseAdmin', array(
            'pageUrl'        => MLBS_Helpers::admin_url(self::PAGE_SLUG),
            'label'          => __('AdSense', MLBS_TEXT_DOMAIN),
            'copySuccess'    => __('Shortcode copiado.', MLBS_TEXT_DOMAIN),
            'copyError'      => __('Não foi possível copiar automaticamente.', MLBS_TEXT_DOMAIN),
            'removeConfirm'  => __('Remover esta unidade de anúncio?', MLBS_TEXT_DOMAIN),
            'unitName'       => __('Nova unidade', MLBS_TEXT_DOMAIN),
        ));
    }

    public function handle_save()
    {
        MLBS_License_Manager::guard_write();
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Permissão insuficiente.', MLBS_TEXT_DOMAIN));
        }

        check_admin_referer('mlbs_save_adsense_settings', 'mlbs_adsense_nonce');
        $raw = isset($_POST['mlbs_adsense']) && is_array($_POST['mlbs_adsense']) ? wp_unslash($_POST['mlbs_adsense']) : array();
        $settings = MLBS_AdSense_Settings::sanitize_settings($raw);
        $this->settings->update($settings);

        $status = 'saved';
        if (! empty($settings['enabled']) && empty($settings['publisher_id'])) {
            $status = 'invalid-publisher';
        }

        wp_safe_redirect(add_query_arg('mlbs_adsense_status', $status, MLBS_Helpers::admin_url(self::PAGE_SLUG)));
        exit;
    }

    public function render_page()
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $settings = $this->settings->get_all();
        $active_units = $this->settings->get_active_units();
        $public_post_types = get_post_types(array('public' => true), 'objects');
        unset($public_post_types['attachment']);
        $ads_txt_url = home_url('/ads.txt');
        $physical_ads_txt = $this->get_physical_ads_txt_path();
        $physical_ads_txt_exists = $physical_ads_txt !== '' && file_exists($physical_ads_txt);
        $publisher_valid = MLBS_AdSense_Settings::is_valid_publisher_id($settings['publisher_id']);
        $generated_ads_txt_line = $publisher_valid ? $this->get_google_ads_txt_line($settings['publisher_id']) : '';
        $status = isset($_GET['mlbs_adsense_status']) ? sanitize_key(wp_unslash($_GET['mlbs_adsense_status'])) : '';

        include MLBS_PLUGIN_DIR . 'modules/adsense/admin/views/adsense.php';
    }

    private function is_mlbs_screen($screen_id, $hook_suffix)
    {
        if (strpos((string) $screen_id, 'mlbs') !== false || strpos((string) $hook_suffix, 'mlbs') !== false) {
            return true;
        }

        return in_array($screen_id, array(MLBS_Post_Types::POST_TYPE, 'edit-' . MLBS_Post_Types::POST_TYPE, 'edit-mlbs_group'), true);
    }

    private function get_physical_ads_txt_path()
    {
        if (! defined('ABSPATH')) {
            return '';
        }
        return trailingslashit(ABSPATH) . 'ads.txt';
    }

    private function get_google_ads_txt_line($publisher_id)
    {
        return 'google.com, ' . str_replace('ca-', '', $publisher_id) . ', DIRECT, f08c47fec0942fa0';
    }
}
