<?php
/**
 * Helper utilities.
 *
 * @package MLBannerSuite
 */

if (! defined('ABSPATH')) {
    exit;
}

class MLBS_Helpers
{
    public static function admin_url($page = 'mlbs-dashboard')
    {
        return add_query_arg(array('page' => $page), admin_url('admin.php'));
    }

    /**
     * Barra de navegação única do painel (3.1.1): mesmas abas, mesma ordem e mesmo
     * estilo em todas as telas, com o botão "Adicionar novo banner" sempre visível.
     * Antes cada tela tinha a sua cópia (ordem e aba AdSense diferentes) e as telas
     * nativas do WP herdavam o link azul sublinhado do core.
     */
    public static function render_admin_nav($current = '')
    {
        $items = array(
            'dashboard'  => array(self::admin_url('mlbs-dashboard'), __('Dashboard', MLBS_TEXT_DOMAIN)),
            'banners'    => array(admin_url('edit.php?post_type=' . MLBS_Post_Types::POST_TYPE), __('Banners', MLBS_TEXT_DOMAIN)),
            'groups'     => array(admin_url('edit-tags.php?taxonomy=mlbs_group&post_type=' . MLBS_Post_Types::POST_TYPE), __('Grupos', MLBS_TEXT_DOMAIN)),
            'statistics' => array(self::admin_url('mlbs-statistics'), __('Estatísticas', MLBS_TEXT_DOMAIN)),
            'shortcodes' => array(self::admin_url('mlbs-shortcodes'), __('Shortcodes', MLBS_TEXT_DOMAIN)),
        );
        if (class_exists('MLBS_AdSense_Admin')) {
            $items['adsense'] = array(self::admin_url('mlbs-adsense'), __('AdSense', MLBS_TEXT_DOMAIN));
        }
        $items['settings'] = array(self::admin_url('mlbs-settings'), __('Configurações', MLBS_TEXT_DOMAIN));
        $items['license']  = array(self::admin_url('mlbs-license'), __('Licença e Planos', MLBS_TEXT_DOMAIN));

        echo '<div class="mlbs-navbar">';
        echo '<nav class="mlbs-tabs" aria-label="ML Banner Suite">';
        foreach ($items as $key => $item) {
            $active = $key === $current ? ' is-active' : '';
            $aria   = $key === $current ? ' aria-current="page"' : '';
            echo '<a class="mlbs-tab' . esc_attr($active) . '" href="' . esc_url($item[0]) . '"' . $aria . '>' . esc_html($item[1]) . '</a>';
        }
        echo '</nav>';
        if (current_user_can('edit_posts')) {
            echo '<a class="mlbs-add-banner" href="' . esc_url(admin_url('post-new.php?post_type=' . MLBS_Post_Types::POST_TYPE)) . '">';
            echo '<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>' . esc_html__('Adicionar novo banner', MLBS_TEXT_DOMAIN);
            echo '</a>';
        }
        echo '</div>';
    }

    public static function yes_no($value)
    {
        return $value ? __('Sim', MLBS_TEXT_DOMAIN) : __('Não', MLBS_TEXT_DOMAIN);
    }

    public static function admin_pages()
    {
        return array(
            'mlbs-dashboard',
            'edit.php?post_type=' . MLBS_Post_Types::POST_TYPE,
            'edit-tags.php?taxonomy=mlbs_group&post_type=' . MLBS_Post_Types::POST_TYPE,
            'mlbs-statistics',
            'mlbs-shortcodes',
            'mlbs-settings',
            'mlbs-license',
        );
    }

    public static function get_upload_base()
    {
        $uploads = wp_upload_dir();

        return array(
            'path'     => trailingslashit($uploads['basedir']) . 'ml-banner-suite/banners',
            'url'      => trailingslashit($uploads['baseurl']) . 'ml-banner-suite/banners',
            'relative' => 'ml-banner-suite/banners',
        );
    }

    public static function get_legacy_banner_base()
    {
        return array(
            'path'     => trailingslashit(WP_CONTENT_DIR) . 'banners',
            'url'      => trailingslashit(content_url()) . 'banners',
            'relative' => 'banners',
        );
    }

    public static function ensure_banner_directory()
    {
        $base = self::get_upload_base();

        if (! file_exists($base['path'])) {
            wp_mkdir_p($base['path']);
        }

        self::maybe_create_index_file($base['path']);
    }

    public static function maybe_create_index_file($path)
    {
        $index_file = trailingslashit($path) . 'index.php';
        if (! file_exists($index_file)) {
            file_put_contents($index_file, "<?php
// Silence is golden.
");
        }
    }

    public static function normalize_banner_file_to_uploads($file)
    {
        $file = wp_normalize_path((string) $file);
        if ($file === '' || ! file_exists($file)) {
            return '';
        }

        $base = self::get_upload_base();
        $base_path = wp_normalize_path(trailingslashit($base['path']));
        if (strpos($file, $base_path) === 0) {
            return $file;
        }

        self::ensure_banner_directory();

        $filename = wp_basename($file);
        $target_dir = wp_normalize_path($base['path']);
        $unique_filename = wp_unique_filename($target_dir, $filename);
        $target = wp_normalize_path(trailingslashit($target_dir) . $unique_filename);

        if (! file_exists($target)) {
            $copied = @copy($file, $target);

            if (! $copied) {
                return $file;
            }

            @chmod($target, 0644);
        }

        return file_exists($target) ? $target : $file;
    }


    public static function get_relative_upload_file($file)
    {
        $file = wp_normalize_path((string) $file);
        if ($file === '') {
            return '';
        }

        $upload_base = self::get_upload_base();
        $upload_path = wp_normalize_path(trailingslashit($upload_base['path']));
        if (strpos($file, $upload_path) === 0) {
            return trim(str_replace($upload_path, trailingslashit($upload_base['relative']), $file), '/');
        }

        $legacy_base = self::get_legacy_banner_base();
        $legacy_path = wp_normalize_path(trailingslashit($legacy_base['path']));
        if (strpos($file, $legacy_path) === 0) {
            return trim(str_replace($legacy_path, trailingslashit($legacy_base['relative']), $file), '/');
        }

        return '';
    }

    public static function repair_banner_attachment($attachment_id)
    {
        $attachment_id = absint($attachment_id);
        if (! $attachment_id) {
            return 0;
        }

        $attached_file = get_attached_file($attachment_id, true);
        if (! $attached_file || ! file_exists($attached_file)) {
            $relative = get_post_meta($attachment_id, '_wp_attached_file', true);
            if ($relative) {
                $legacy = wp_normalize_path(trailingslashit(WP_CONTENT_DIR) . ltrim((string) $relative, '/'));
                if (file_exists($legacy)) {
                    $attached_file = $legacy;
                }
            }
        }

        if (! $attached_file || ! file_exists($attached_file)) {
            return $attachment_id;
        }

        $attached_file = wp_normalize_path($attached_file);
        $uploads_base = wp_normalize_path(trailingslashit(self::get_upload_base()['path']));
        if (strpos($attached_file, $uploads_base) === 0) {
            return $attachment_id;
        }

        $normalized = self::normalize_banner_file_to_uploads($attached_file);
        if (! $normalized || ! file_exists($normalized)) {
            return $attachment_id;
        }

        $relative = trailingslashit(self::get_upload_base()['relative']) . basename($normalized);
        update_attached_file($attachment_id, $normalized);
        update_post_meta($attachment_id, '_wp_attached_file', $relative);
        wp_update_post(array(
            'ID'   => $attachment_id,
            'guid' => trailingslashit(self::get_upload_base()['url']) . basename($normalized),
        ));

        if (! function_exists('wp_generate_attachment_metadata')) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $metadata = wp_generate_attachment_metadata($attachment_id, $normalized);
        if (! is_wp_error($metadata) && ! empty($metadata)) {
            wp_update_attachment_metadata($attachment_id, $metadata);
        }

        update_post_meta($attachment_id, '_mlbs_is_banner_asset', 1);

        return $attachment_id;
    }

    public static function get_banner_meta($post_id)
    {
        $defaults = array(
            '_mlbs_image_id'       => 0,
            '_mlbs_image_url'      => '',
            '_mlbs_size_label'     => '',
            '_mlbs_target_url'     => '',
            '_mlbs_target_blank'   => 0,
            '_mlbs_start_at'       => '',
            '_mlbs_end_at'         => '',
            '_mlbs_lifetime'       => 0,
            '_mlbs_manual_order'   => 0,
            '_mlbs_is_active'      => 1,
            '_mlbs_width'          => '',
            '_mlbs_height'         => '',
            '_mlbs_duration'       => 4000,
            '_mlbs_alignment'      => 'center',
            '_mlbs_clicks'         => 0,
            '_mlbs_impressions'    => 0,
        );

        foreach ($defaults as $key => $default) {
            $value = get_post_meta($post_id, $key, true);
            $defaults[$key] = ($value === '') ? $default : $value;
        }

        if (! empty($defaults['_mlbs_image_id'])) {
            $defaults['_mlbs_image_id'] = self::repair_banner_attachment((int) $defaults['_mlbs_image_id']);
            $url = wp_get_attachment_image_url((int) $defaults['_mlbs_image_id'], 'full');
            if (! $url) {
                $url = wp_get_attachment_url((int) $defaults['_mlbs_image_id']);
            }
            if ($url) {
                $defaults['_mlbs_image_url'] = $url;
            }
        }

        return $defaults;
    }

    public static function get_banner_status_label($post_id)
    {
        $meta = self::get_banner_meta($post_id);
        if ((int) $meta['_mlbs_is_active'] !== 1 || empty($meta['_mlbs_image_id'])) {
            return __('Bloqueado', MLBS_TEXT_DOMAIN);
        }

        if ((int) $meta['_mlbs_lifetime'] === 1) {
            return __('Em exibição', MLBS_TEXT_DOMAIN);
        }

        $now = current_time('timestamp');
        $start = ! empty($meta['_mlbs_start_at']) ? strtotime((string) $meta['_mlbs_start_at']) : 0;
        $end   = ! empty($meta['_mlbs_end_at']) ? strtotime((string) $meta['_mlbs_end_at']) : 0;

        if ($start && $now < $start) {
            return __('Agendado', MLBS_TEXT_DOMAIN);
        }

        if ($end && $now > $end) {
            return __('Expirado', MLBS_TEXT_DOMAIN);
        }

        return __('Em exibição', MLBS_TEXT_DOMAIN);
    }

    public static function get_display_mode_options()
    {
        return array(
            'static'    => __('Grid / Estático', MLBS_TEXT_DOMAIN),
            'slideshow' => __('Slideshow', MLBS_TEXT_DOMAIN),
            'carousel'  => __('Carrossel', MLBS_TEXT_DOMAIN),
        );
    }

    public static function normalize_display_mode($mode)
    {
        $mode = sanitize_key((string) $mode);
        if ($mode === 'grid') {
            $mode = 'static';
        }
        if ($mode === 'slider') {
            $mode = 'slideshow';
        }
        return in_array($mode, array('static', 'slideshow', 'carousel'), true) ? $mode : 'static';
    }

    public static function get_alignment_options()
    {
        return array(
            'left'   => __('Esquerda', MLBS_TEXT_DOMAIN),
            'center' => __('Centro', MLBS_TEXT_DOMAIN),
            'right'  => __('Direita', MLBS_TEXT_DOMAIN),
        );
    }

    public static function normalize_alignment($alignment)
    {
        $alignment = sanitize_key((string) $alignment);
        return in_array($alignment, array('left', 'center', 'right'), true) ? $alignment : 'center';
    }

    public static function get_group_setting_defaults()
    {
        return array(
            'display_mode'  => 'static',
            'autoplay'      => 1,
            'show_arrows'   => 0,
            'desktop_items' => 3,
            'tablet_items'  => 2,
            'mobile_items'  => 1,
            'grid_columns'  => 3,
            'grid_rows'     => 1,
            'transition'    => 'slide',
        );
    }

    public static function get_group_settings($term_id)
    {
        $term_id = (int) $term_id;
        $defaults = self::get_group_setting_defaults();
        $settings = array(
            'display_mode'  => self::normalize_display_mode(get_term_meta($term_id, 'mlbs_group_display_mode', true)),
            'autoplay'      => get_term_meta($term_id, 'mlbs_group_autoplay', true),
            'show_arrows'   => get_term_meta($term_id, 'mlbs_group_show_arrows', true),
            'desktop_items' => get_term_meta($term_id, 'mlbs_group_desktop_items', true),
            'tablet_items'  => get_term_meta($term_id, 'mlbs_group_tablet_items', true),
            'mobile_items'  => get_term_meta($term_id, 'mlbs_group_mobile_items', true),
            'grid_columns'  => get_term_meta($term_id, 'mlbs_group_grid_columns', true),
            'grid_rows'     => get_term_meta($term_id, 'mlbs_group_grid_rows', true),
            'transition'    => get_term_meta($term_id, 'mlbs_group_transition', true),
        );

        if (! $settings['display_mode']) {
            $settings['display_mode'] = $defaults['display_mode'];
        }
        foreach (array('desktop_items', 'tablet_items', 'mobile_items', 'grid_columns', 'grid_rows') as $key) {
            $settings[$key] = absint($settings[$key]);
            if ($settings[$key] < 1) {
                $settings[$key] = $defaults[$key];
            }
        }
        $settings['autoplay'] = $settings['autoplay'] === '' ? $defaults['autoplay'] : (int) $settings['autoplay'];
        $settings['show_arrows'] = $settings['show_arrows'] === '' ? $defaults['show_arrows'] : (int) $settings['show_arrows'];
        $settings['transition'] = self::normalize_transition($settings['transition'] ?: $defaults['transition']);

        return $settings;
    }

    public static function get_transition_options()
    {
        return array(
            'slide' => __('Deslizar da direita para a esquerda', MLBS_TEXT_DOMAIN),
            'fade'  => __('Esmaecer', MLBS_TEXT_DOMAIN),
        );
    }

    public static function normalize_transition($transition)
    {
        $transition = sanitize_key((string) $transition);
        return in_array($transition, array('slide', 'fade'), true) ? $transition : 'slide';
    }

    public static function get_group_mode($term_id)
    {
        $settings = self::get_group_settings($term_id);
        return $settings['display_mode'];
    }

    public static function get_group_shortcode($slug, $type = '')
    {
        $slug = sanitize_title($slug);
        $shortcode = '[ml_banner_suite group="' . $slug . '"';
        if ($type) {
            $shortcode .= ' type="' . self::normalize_display_mode($type) . '"';
        }
        $shortcode .= ']';
        return $shortcode;
    }

    public static function get_attachment_timing_data($attachment_id)
    {
        $attachment_id = absint($attachment_id);
        $data = array(
            'is_gif'         => false,
            'is_animated'    => false,
            'frames'         => 0,
            'duration_ms'    => 0,
            'frame_delay_ms' => 0,
        );

        if (! $attachment_id) {
            return $data;
        }

        $mime = (string) get_post_mime_type($attachment_id);
        $file = get_attached_file($attachment_id, true);
        if (! $file || ! file_exists($file)) {
            return $data;
        }

        $ext = strtolower((string) pathinfo($file, PATHINFO_EXTENSION));
        if ($mime !== 'image/gif' && $ext !== 'gif') {
            return $data;
        }

        $data['is_gif'] = true;

        if (class_exists('Imagick')) {
            try {
                $imagick = new Imagick();
                $imagick->readImage($file);
                $frames = 0;
                $duration = 0;
                foreach ($imagick as $frame) {
                    $frames++;
                    $duration += ((int) $frame->getImageDelay()) * 10;
                }
                $imagick->clear();
                $imagick->destroy();
                $data['frames'] = $frames;
                $data['duration_ms'] = max(0, $duration);
                $data['frame_delay_ms'] = $frames > 0 ? (int) round($duration / $frames) : 0;
                $data['is_animated'] = $frames > 1 && $duration > 0;
                return $data;
            } catch (Exception $e) {
            }
        }

        if (function_exists('shell_exec')) {
            $cmd = 'identify -format "%T\n" ' . escapeshellarg($file) . ' 2>/dev/null';
            $output = shell_exec($cmd);
            if (is_string($output) && trim($output) !== '') {
                $delays = preg_split('/\R+/', trim($output));
                $delays = array_values(array_filter(array_map('intval', $delays), static function ($delay) {
                    return $delay >= 0;
                }));
                if (! empty($delays)) {
                    $duration = array_sum($delays) * 10;
                    $frames = count($delays);
                    $data['frames'] = $frames;
                    $data['duration_ms'] = max(0, (int) $duration);
                    $data['frame_delay_ms'] = $frames > 0 ? (int) round($duration / $frames) : 0;
                    $data['is_animated'] = $frames > 1 && $duration > 0;
                }
            }
        }

        return $data;
    }

}
