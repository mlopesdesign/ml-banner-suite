<?php
/**
 * Isolated Google AdSense module.
 *
 * This module does not modify the banner engine, statistics engine, licensing,
 * or GitHub update engine.
 *
 * @package MLBannerSuite
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/class-mlbs-adsense-settings.php';
require_once __DIR__ . '/includes/class-mlbs-adsense-renderer.php';
require_once __DIR__ . '/includes/class-mlbs-adsense-admin.php';
require_once __DIR__ . '/includes/class-mlbs-adsense-ads-txt.php';

final class MLBS_AdSense_Module
{
    private $settings;
    private $renderer;
    private $admin;
    private $ads_txt;

    public function __construct()
    {
        $this->settings = new MLBS_AdSense_Settings();
        $this->renderer = new MLBS_AdSense_Renderer($this->settings);
        $this->admin = new MLBS_AdSense_Admin($this->settings);
        $this->ads_txt = new MLBS_AdSense_Ads_Txt($this->settings);
    }

    public function register_hooks()
    {
        add_action('plugins_loaded', array('MLBS_AdSense_Settings', 'maybe_install'));
        add_action('init', array($this->renderer, 'register_shortcode'), 10);
        add_action('wp_enqueue_scripts', array($this->renderer, 'enqueue_front_style'));
        add_action('wp_head', array($this->renderer, 'render_auto_ads_script'), 20);
        add_filter('the_content', array($this->renderer, 'inject_into_content'), 20);

        add_action('admin_menu', array($this->admin, 'register_menu'), 20);
        add_action('admin_enqueue_scripts', array($this->admin, 'enqueue_assets'));
        add_action('admin_post_mlbs_save_adsense_settings', array($this->admin, 'handle_save'));

        add_action('template_redirect', array($this->ads_txt, 'maybe_serve'), 0);
    }
}
