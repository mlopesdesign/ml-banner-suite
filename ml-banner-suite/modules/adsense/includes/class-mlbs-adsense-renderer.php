<?php
/**
 * Front-end AdSense renderer.
 *
 * @package MLBannerSuite
 */

if (! defined('ABSPATH')) {
    exit;
}

class MLBS_AdSense_Renderer
{
    private $settings;
    private $injected_posts = array();
    private static $global_script_rendered = false;

    public function __construct(MLBS_AdSense_Settings $settings)
    {
        $this->settings = $settings;
    }

    public function register_shortcode()
    {
        add_shortcode('mlbs_adsense', array($this, 'render_shortcode'));
    }

    public function enqueue_front_style()
    {
        if (! $this->is_module_ready()) {
            return;
        }

        wp_enqueue_style(
            'mlbs-adsense-front',
            MLBS_PLUGIN_URL . 'modules/adsense/assets/css/front.css',
            array(),
            MLBS_VERSION
        );
    }

    public function render_auto_ads_script()
    {
        $settings = $this->settings->get_all();
        if (empty($settings['auto_ads_enabled']) || ! $this->can_render('auto_ads')) {
            return;
        }

        echo $this->get_global_script_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    public function render_shortcode($atts)
    {
        $atts = shortcode_atts(array('unit' => ''), $atts, 'mlbs_adsense');
        return $this->render_unit($atts['unit'], 'shortcode');
    }

    public function inject_into_content($content)
    {
        $settings = $this->settings->get_all();

        if (empty($settings['content_injection_enabled']) || ! $this->can_inject_content()) {
            return $content;
        }

        $ad = $this->render_unit($settings['content_unit'], 'content');
        if ($ad === '') {
            return $content;
        }

        if ($settings['content_position'] === 'before_content') {
            return $ad . $content;
        }

        if ($settings['content_position'] === 'after_content') {
            return $content . $ad;
        }

        return $this->insert_after_paragraph($ad, $content, (int) $settings['content_paragraph']);
    }

    public function render_unit($unit_id, $context = 'manual')
    {
        if (! $this->can_render($context)) {
            return '';
        }

        $unit = $this->settings->get_unit($unit_id);
        if (! is_array($unit)) {
            return '';
        }

        $settings = $this->settings->get_all();
        $publisher_id = $settings['publisher_id'];
        $classes = array('mlbs-adsense', 'mlbs-adsense--' . $unit['alignment']);
        $style = sprintf('margin-top:%dpx;margin-bottom:%dpx;', (int) $unit['margin_top'], (int) $unit['margin_bottom']);
        $script = self::$global_script_rendered ? '' : $this->get_global_script_markup();
        $label = '';

        if (! empty($settings['show_ad_label'])) {
            $label = sprintf('<span class="mlbs-adsense__label">%s</span>', esc_html($settings['ad_label']));
        }

        $markup = sprintf(
            '%1$s<div class="%2$s" style="%3$s" data-mlbs-adsense-unit="%4$s">%5$s<ins class="adsbygoogle" style="display:block" data-ad-client="%6$s" data-ad-slot="%7$s" data-ad-format="%8$s" data-full-width-responsive="%9$s"></ins><script>(adsbygoogle=window.adsbygoogle||[]).push({});</script></div>',
            $script,
            esc_attr(implode(' ', $classes)),
            esc_attr($style),
            esc_attr($unit['id']),
            $label,
            esc_attr($publisher_id),
            esc_attr($unit['slot']),
            esc_attr($unit['format']),
            ! empty($unit['full_width_responsive']) ? 'true' : 'false'
        );

        return apply_filters('mlbs_adsense_unit_html', $markup, $unit, $context);
    }

    private function is_module_ready()
    {
        $settings = $this->settings->get_all();
        return ! empty($settings['enabled']) && MLBS_AdSense_Settings::is_valid_publisher_id($settings['publisher_id']);
    }

    private function can_render($context)
    {
        if (! $this->is_module_ready() || is_admin() || is_feed()) {
            return false;
        }

        $settings = $this->settings->get_all();
        if (! empty($settings['hide_for_administrators']) && is_user_logged_in() && current_user_can('manage_options')) {
            return false;
        }

        return (bool) apply_filters('mlbs_adsense_can_load', true, $context, $settings);
    }

    private function can_inject_content()
    {
        if (! $this->can_render('content') || ! is_singular() || ! in_the_loop() || ! is_main_query()) {
            return false;
        }

        if ((function_exists('post_password_required') && post_password_required()) || (function_exists('wp_is_json_request') && wp_is_json_request())) {
            return false;
        }

        if (function_exists('is_amp_endpoint') && is_amp_endpoint()) {
            return false;
        }

        $post_id = function_exists('get_the_ID') ? (int) get_the_ID() : 0;
        if ($post_id > 0 && isset($this->injected_posts[$post_id])) {
            return false;
        }

        $settings = $this->settings->get_all();
        if (! in_array(get_post_type(), $settings['content_post_types'], true)) {
            return false;
        }

        if ($post_id > 0) {
            $this->injected_posts[$post_id] = true;
        }

        return true;
    }

    private function get_global_script_markup()
    {
        if (self::$global_script_rendered) {
            return '';
        }

        $publisher_id = $this->settings->get('publisher_id', '');
        if (! MLBS_AdSense_Settings::is_valid_publisher_id($publisher_id)) {
            return '';
        }

        self::$global_script_rendered = true;
        $src = add_query_arg('client', $publisher_id, 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js');

        return sprintf(
            '<script async src="%s" crossorigin="anonymous"></script>',
            esc_url($src)
        );
    }

    private function insert_after_paragraph($insertion, $content, $paragraph_number)
    {
        $closing_tag = '</p>';
        $paragraphs = explode($closing_tag, $content);
        $output = '';
        $inserted = false;
        $paragraph_count = count($paragraphs);

        foreach ($paragraphs as $index => $paragraph) {
            if (trim($paragraph) === '') {
                continue;
            }

            $output .= $paragraph;
            if ($index < ($paragraph_count - 1)) {
                $output .= $closing_tag;
            }

            if (! $inserted && ($index + 1) === $paragraph_number) {
                $output .= $insertion;
                $inserted = true;
            }
        }

        return $inserted ? $output : $content . $insertion;
    }
}
