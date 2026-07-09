<?php
/**
 * Virtual ads.txt endpoint.
 *
 * @package MLBannerSuite
 */

if (! defined('ABSPATH')) {
    exit;
}

class MLBS_AdSense_Ads_Txt
{
    private $settings;

    public function __construct(MLBS_AdSense_Settings $settings)
    {
        $this->settings = $settings;
    }

    public function maybe_serve()
    {
        if (! $this->is_ads_txt_request()) {
            return;
        }

        $settings = $this->settings->get_all();
        if (empty($settings['enabled']) || empty($settings['ads_txt_enabled']) || ! MLBS_AdSense_Settings::is_valid_publisher_id($settings['publisher_id'])) {
            return;
        }

        $physical_file = trailingslashit(ABSPATH) . 'ads.txt';
        if (file_exists($physical_file)) {
            return;
        }

        $lines = array(
            'google.com, ' . str_replace('ca-', '', $settings['publisher_id']) . ', DIRECT, f08c47fec0942fa0',
        );

        if ($settings['ads_txt_additional_lines'] !== '') {
            $lines[] = $settings['ads_txt_additional_lines'];
        }

        $content = implode("\n", $lines) . "\n";
        $content = apply_filters('mlbs_adsense_ads_txt_content', $content, $settings);

        status_header(200);
        nocache_headers();
        header('Content-Type: text/plain; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    private function is_ads_txt_request()
    {
        if (empty($_SERVER['REQUEST_URI'])) {
            return false;
        }

        $request_path = wp_parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH);
        if (! is_string($request_path)) {
            return false;
        }

        $request_path = '/' . ltrim($request_path, '/');
        $home_path = wp_parse_url(home_url('/'), PHP_URL_PATH);
        $home_path = is_string($home_path) ? '/' . trim($home_path, '/') : '';
        $expected = ($home_path === '/' || $home_path === '') ? '/ads.txt' : rtrim($home_path, '/') . '/ads.txt';

        return untrailingslashit($request_path) === untrailingslashit($expected) || untrailingslashit($request_path) === '/ads.txt';
    }
}
