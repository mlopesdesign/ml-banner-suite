<?php
/**
 * Isolated AdSense settings storage.
 *
 * @package MLBannerSuite
 */

if (! defined('ABSPATH')) {
    exit;
}

class MLBS_AdSense_Settings
{
    const OPTION_KEY = 'mlbs_adsense_settings';
    const MODULE_VERSION_KEY = 'mlbs_adsense_module_version';
    const MODULE_VERSION = '1.0.0';

    public static function get_defaults()
    {
        return array(
            'enabled'                    => 0,
            'publisher_id'               => '',
            'auto_ads_enabled'           => 0,
            'hide_for_administrators'    => 1,
            'show_ad_label'              => 1,
            'ad_label'                   => 'Publicidade',
            'content_injection_enabled'  => 0,
            'content_unit'               => '',
            'content_position'           => 'after_paragraph',
            'content_paragraph'          => 3,
            'content_post_types'         => array('post'),
            'ads_txt_enabled'            => 1,
            'ads_txt_additional_lines'   => '',
            'units'                      => array(),
        );
    }

    public static function maybe_install()
    {
        $saved = get_option(self::OPTION_KEY, false);

        if (false === $saved) {
            add_option(self::OPTION_KEY, self::get_defaults(), '', false);
        } elseif (is_array($saved)) {
            update_option(self::OPTION_KEY, wp_parse_args($saved, self::get_defaults()), false);
        }

        if (get_option(self::MODULE_VERSION_KEY) !== self::MODULE_VERSION) {
            update_option(self::MODULE_VERSION_KEY, self::MODULE_VERSION, false);
        }
    }

    public function get_all()
    {
        $saved = get_option(self::OPTION_KEY, array());
        $settings = wp_parse_args(is_array($saved) ? $saved : array(), self::get_defaults());
        $settings['units'] = is_array($settings['units']) ? array_values($settings['units']) : array();
        $settings['content_post_types'] = is_array($settings['content_post_types']) ? array_values($settings['content_post_types']) : array('post');

        return $settings;
    }

    public function get($key, $default = null)
    {
        $settings = $this->get_all();
        return array_key_exists($key, $settings) ? $settings[$key] : $default;
    }

    public function update(array $values)
    {
        $settings = wp_parse_args($values, self::get_defaults());
        update_option(self::OPTION_KEY, $settings, false);
        return $settings;
    }

    public function get_active_units()
    {
        $active = array();

        foreach ($this->get_all()['units'] as $unit) {
            if (! empty($unit['enabled']) && ! empty($unit['id']) && ! empty($unit['slot'])) {
                $active[$unit['id']] = $unit;
            }
        }

        return $active;
    }

    public function get_unit($unit_id)
    {
        $unit_id = sanitize_key((string) $unit_id);
        $units = $this->get_active_units();
        return isset($units[$unit_id]) ? $units[$unit_id] : null;
    }

    public static function sanitize_publisher_id($publisher_id)
    {
        $publisher_id = strtolower(trim((string) $publisher_id));
        return preg_match('/^ca-pub-[0-9]{16}$/', $publisher_id) ? $publisher_id : '';
    }

    public static function is_valid_publisher_id($publisher_id)
    {
        return self::sanitize_publisher_id($publisher_id) !== '';
    }

    public static function sanitize_settings(array $raw)
    {
        $defaults = self::get_defaults();
        $allowed_positions = array('before_content', 'after_paragraph', 'after_content');
        $allowed_post_types = array_keys(get_post_types(array('public' => true), 'names'));
        $post_types = isset($raw['content_post_types']) && is_array($raw['content_post_types']) ? $raw['content_post_types'] : array();
        $post_types = array_values(array_intersect(array_map('sanitize_key', $post_types), $allowed_post_types));

        if (empty($post_types)) {
            $post_types = array('post');
        }

        $settings = array(
            'enabled'                   => empty($raw['enabled']) ? 0 : 1,
            'publisher_id'              => self::sanitize_publisher_id(isset($raw['publisher_id']) ? $raw['publisher_id'] : ''),
            'auto_ads_enabled'          => empty($raw['auto_ads_enabled']) ? 0 : 1,
            'hide_for_administrators'   => empty($raw['hide_for_administrators']) ? 0 : 1,
            'show_ad_label'             => empty($raw['show_ad_label']) ? 0 : 1,
            'ad_label'                  => sanitize_text_field(isset($raw['ad_label']) ? $raw['ad_label'] : $defaults['ad_label']),
            'content_injection_enabled' => empty($raw['content_injection_enabled']) ? 0 : 1,
            'content_unit'              => sanitize_key(isset($raw['content_unit']) ? $raw['content_unit'] : ''),
            'content_position'          => isset($raw['content_position']) && in_array($raw['content_position'], $allowed_positions, true) ? $raw['content_position'] : $defaults['content_position'],
            'content_paragraph'         => min(50, max(1, absint(isset($raw['content_paragraph']) ? $raw['content_paragraph'] : $defaults['content_paragraph']))),
            'content_post_types'        => $post_types,
            'ads_txt_enabled'           => empty($raw['ads_txt_enabled']) ? 0 : 1,
            'ads_txt_additional_lines'  => self::sanitize_ads_txt_lines(isset($raw['ads_txt_additional_lines']) ? $raw['ads_txt_additional_lines'] : ''),
            'units'                     => self::sanitize_units(isset($raw['units']) && is_array($raw['units']) ? $raw['units'] : array()),
        );

        if ($settings['ad_label'] === '') {
            $settings['ad_label'] = $defaults['ad_label'];
        }

        $valid_unit_ids = array();
        foreach ($settings['units'] as $unit) {
            if (! empty($unit['enabled']) && ! empty($unit['slot'])) {
                $valid_unit_ids[] = $unit['id'];
            }
        }
        if (! in_array($settings['content_unit'], $valid_unit_ids, true)) {
            $settings['content_unit'] = '';
            $settings['content_injection_enabled'] = 0;
        }

        return $settings;
    }

    private static function sanitize_units(array $units)
    {
        $clean = array();
        $used_ids = array();
        $allowed_formats = array('auto', 'rectangle', 'horizontal', 'vertical');
        $allowed_alignments = array('left', 'center', 'right');

        foreach ($units as $unit) {
            if (! is_array($unit)) {
                continue;
            }

            $name = sanitize_text_field(isset($unit['name']) ? $unit['name'] : '');
            $slot = preg_replace('/\D+/', '', isset($unit['slot']) ? (string) $unit['slot'] : '');
            $id = sanitize_key(isset($unit['id']) ? $unit['id'] : '');

            if ($name === '' && $slot === '') {
                continue;
            }

            if ($id === '') {
                $id = sanitize_title($name);
            }
            if ($id === '') {
                $id = 'unit-' . substr(str_replace('-', '', wp_generate_uuid4()), 0, 12);
            }

            $original_id = $id;
            $suffix = 2;
            while (in_array($id, $used_ids, true)) {
                $id = $original_id . '-' . $suffix;
                $suffix++;
            }
            $used_ids[] = $id;

            $format = isset($unit['format']) && in_array($unit['format'], $allowed_formats, true) ? $unit['format'] : 'auto';
            $alignment = isset($unit['alignment']) && in_array($unit['alignment'], $allowed_alignments, true) ? $unit['alignment'] : 'center';

            $clean[] = array(
                'id'                    => $id,
                'name'                  => $name !== '' ? $name : $id,
                'slot'                  => substr($slot, 0, 32),
                'format'                => $format,
                'full_width_responsive' => empty($unit['full_width_responsive']) ? 0 : 1,
                'alignment'             => $alignment,
                'margin_top'            => min(200, max(0, absint(isset($unit['margin_top']) ? $unit['margin_top'] : 16))),
                'margin_bottom'         => min(200, max(0, absint(isset($unit['margin_bottom']) ? $unit['margin_bottom'] : 16))),
                'enabled'               => empty($unit['enabled']) ? 0 : 1,
            );
        }

        return array_slice($clean, 0, 100);
    }

    private static function sanitize_ads_txt_lines($lines)
    {
        $lines = str_replace(array("\r\n", "\r"), "\n", (string) $lines);
        $clean = array();

        foreach (array_slice(explode("\n", $lines), 0, 200) as $line) {
            $line = trim(wp_strip_all_tags($line));
            if ($line === '' || strpos($line, '#') === 0) {
                if ($line !== '') {
                    $clean[] = substr($line, 0, 500);
                }
                continue;
            }

            $line = preg_replace('/[^a-zA-Z0-9._,:\-\s]/', '', $line);
            if ($line !== '') {
                $clean[] = substr($line, 0, 500);
            }
        }

        return implode("\n", $clean);
    }
}
