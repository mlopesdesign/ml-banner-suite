<?php
/**
 * Settings handler.
 *
 * @package MLBannerSuite
 */

if (! defined('ABSPATH')) {
    exit;
}

class MLBS_Settings
{
    const OPTION_KEY = 'mlbs_settings';

    public static function get_defaults()
    {
        return array(
            'load_assets_conditionally'  => 1,
            'global_css_prefix'          => '',
            'global_container_class'     => 'mlbs-wrapper',
            'remove_data_on_uninstall'   => 0,
            'default_new_banner_status'  => 'active',
            'default_clone_status'       => 'inactive',
            'autoplay_enabled'           => 1,
            'pause_on_hover'             => 1,
            'autoplay_speed'             => 4000,
            'transition_speed'           => 600,
            'default_desktop_items'      => 3,
            'default_tablet_items'       => 2,
            'default_mobile_items'       => 1,
            'banner_gap'                 => 16,
            'loop_enabled'               => 1,
            'hide_expired_banners'       => 1,
            'track_impressions'          => 1,
            'track_clicks'               => 1,
            'impression_count_method'    => 'render',
            'click_duplicate_window'     => 10,
            'stats_retention_days'       => '',
            'panel_language'             => '',
            'default_alignment'          => 'center',
            'license_server_url'         => 'https://license.mlopesdesign.com.br',
            'license_product_id'         => 'ml-banner-suite',
            'license_product_name'       => 'ML Banner Suite',
        );
    }

    public static function maybe_add_default_options()
    {
        if (false === get_option(self::OPTION_KEY, false)) {
            add_option(self::OPTION_KEY, self::get_defaults());
        }
    }

    public function get_all()
    {
        $saved = get_option(self::OPTION_KEY, array());
        return wp_parse_args(is_array($saved) ? $saved : array(), self::get_defaults());
    }

    public function get($key, $default = null)
    {
        $settings = $this->get_all();
        return array_key_exists($key, $settings) ? $settings[$key] : $default;
    }

    public function update(array $new_values)
    {
        $current = $this->get_all();
        $merged = array_merge($current, $new_values);
        update_option(self::OPTION_KEY, $merged);
        return $merged;
    }
}

