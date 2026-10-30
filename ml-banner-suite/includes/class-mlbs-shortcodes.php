<?php
/**
 * Shortcodes and front rendering.
 *
 * @package MLBannerSuite
 */

if (! defined('ABSPATH')) {
    exit;
}

class MLBS_Shortcodes
{
    private $settings;

    public function __construct(MLBS_Settings $settings)
    {
        $this->settings = $settings;
    }

    public function register_shortcodes()
    {
        add_shortcode('ml_banner_suite', array($this, 'render_shortcode'));
    }

    public function render_shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'id'      => 0,
            'group'   => '',
            'type'    => '',
            'width'   => '',
            'height'  => '',
            'desktop' => '',
            'tablet'  => '',
            'mobile'  => '',
            'align'   => $this->settings->get('default_alignment', 'center'),
            'class'   => '',
            'arrows'  => '',
        ), $atts, 'ml_banner_suite');

        $atts['id']    = absint($atts['id']);
        $atts['group'] = sanitize_title($atts['group']);
        $atts['align'] = MLBS_Helpers::normalize_alignment($atts['align']);
        $atts['class'] = sanitize_html_class($atts['class']);

        $group_term = null;
        $group_settings = MLBS_Helpers::get_group_setting_defaults();
        if (! empty($atts['group'])) {
            $group_term = get_term_by('slug', $atts['group'], 'mlbs_group');
            if ($group_term && ! is_wp_error($group_term)) {
                $group_settings = MLBS_Helpers::get_group_settings($group_term->term_id);
            }
        }

        if ($atts['id']) {
            $atts['type'] = 'static';
        } elseif (! empty($atts['type'])) {
            $atts['type'] = MLBS_Helpers::normalize_display_mode($atts['type']);
        } elseif ($group_term && ! is_wp_error($group_term)) {
            $atts['type'] = $group_settings['display_mode'];
        } else {
            $atts['type'] = 'static';
        }

        $atts['desktop'] = max(1, absint($atts['desktop'] !== '' ? $atts['desktop'] : ($group_settings['desktop_items'] ?: $this->settings->get('default_desktop_items', 3))));
        $atts['tablet']  = max(1, absint($atts['tablet'] !== '' ? $atts['tablet'] : ($group_settings['tablet_items'] ?: $this->settings->get('default_tablet_items', 2))));
        $atts['mobile']  = max(1, absint($atts['mobile'] !== '' ? $atts['mobile'] : ($group_settings['mobile_items'] ?: $this->settings->get('default_mobile_items', 1))));

        if ($atts['type'] === 'slideshow') {
            $atts['desktop'] = 1;
            $atts['tablet'] = 1;
            $atts['mobile'] = 1;
        }

        $show_arrows = $atts['arrows'] !== '' ? (int) in_array(strtolower((string) $atts['arrows']), array('1', 'true', 'yes', 'sim'), true) : (int) $group_settings['show_arrows'];
        $autoplay = (int) $group_settings['autoplay'];
        $transition = $group_settings['transition'];
        $grid_columns = max(1, absint($group_settings['grid_columns']));
        $grid_rows = max(1, absint($group_settings['grid_rows']));

        $banner_ids = $this->resolve_banner_ids($atts, $group_term);
        if ($atts['type'] === 'static') {
            $banner_ids = array_slice($banner_ids, 0, max(1, $grid_columns * $grid_rows));
            $atts['desktop'] = $grid_columns;
            $atts['tablet'] = min($atts['tablet'], $grid_columns);
            $atts['mobile'] = min($atts['mobile'], $grid_columns);
        }
        if (empty($banner_ids)) {
            return '';
        }

        wp_enqueue_style('mlbs-front');
        wp_enqueue_script('mlbs-front');

        $uid    = 'mlbs-' . wp_rand(1000, 99999);
        $styles = array(
            '--mlbs-desktop:' . (int) $atts['desktop'],
            '--mlbs-tablet:' . (int) $atts['tablet'],
            '--mlbs-mobile:' . (int) $atts['mobile'],
            '--mlbs-grid-columns:' . (int) $grid_columns,
            '--mlbs-gap:' . (int) $this->settings->get('banner_gap', 16) . 'px',
        );
        if ($atts['width']) {
            $styles[] = '--mlbs-width:' . sanitize_text_field($atts['width']);
        }
        if ($atts['height']) {
            $styles[] = '--mlbs-height:' . sanitize_text_field($atts['height']);
        }

        $wrapper_classes = array(
            'mlbs-front',
            'mlbs-type-' . $atts['type'],
            'mlbs-align-' . $atts['align'],
        );
        if ($atts['class']) {
            $wrapper_classes[] = $atts['class'];
        }

        ob_start();
        ?>
        <div id="<?php echo esc_attr($uid); ?>"
            class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>"
            style="<?php echo esc_attr(implode(';', $styles)); ?>"
            data-mlbs-type="<?php echo esc_attr($atts['type']); ?>"
            data-mlbs-autoplay="<?php echo esc_attr($autoplay); ?>"
            data-mlbs-arrows="<?php echo esc_attr($show_arrows); ?>"
            data-mlbs-desktop="<?php echo esc_attr((string) $atts['desktop']); ?>"
            data-mlbs-tablet="<?php echo esc_attr((string) $atts['tablet']); ?>"
            data-mlbs-mobile="<?php echo esc_attr((string) $atts['mobile']); ?>"
            data-mlbs-grid-columns="<?php echo esc_attr((string) $grid_columns); ?>"
            data-mlbs-grid-rows="<?php echo esc_attr((string) $grid_rows); ?>"
            data-mlbs-transition="<?php echo esc_attr($transition); ?>"
            data-mlbs-banner-ids="<?php echo esc_attr(implode(',', array_map('intval', $banner_ids))); ?>">
            <?php if ($atts['type'] !== 'static' && $show_arrows) : ?>
                <button type="button" class="mlbs-nav mlbs-nav-prev" aria-label="<?php echo esc_attr__('Banner anterior', MLBS_TEXT_DOMAIN); ?>">&#10094;</button>
            <?php endif; ?>
            <div class="mlbs-viewport">
                <div class="mlbs-track">
                    <?php foreach ($banner_ids as $banner_id) : ?>
                        <?php $this->render_banner_card($banner_id, $atts['align']); ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php if ($atts['type'] !== 'static' && $show_arrows) : ?>
                <button type="button" class="mlbs-nav mlbs-nav-next" aria-label="<?php echo esc_attr__('Próximo banner', MLBS_TEXT_DOMAIN); ?>">&#10095;</button>
            <?php endif; ?>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    private function resolve_banner_ids($atts, $group_term)
    {
        $meta_query = array(
            'relation' => 'AND',
            array(
                'key'   => '_mlbs_is_active',
                'value' => '1',
            ),
            array(
                'key'     => '_mlbs_image_id',
                'value'   => 0,
                'compare' => '>',
                'type'    => 'NUMERIC',
            ),
        );

        if ($atts['id']) {
            $ids = array($atts['id']);
        } elseif ($group_term && ! is_wp_error($group_term)) {
            $query = new WP_Query(array(
                'post_type'      => MLBS_Post_Types::POST_TYPE,
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'meta_key'       => '_mlbs_manual_order',
                'orderby'        => array(
                    'meta_value_num' => 'ASC',
                    'menu_order'     => 'ASC',
                    'title'          => 'ASC',
                    'date'           => 'DESC',
                ),
                'meta_query'     => $meta_query,
                'tax_query'      => array(
                    array(
                        'taxonomy' => 'mlbs_group',
                        'field'    => 'term_id',
                        'terms'    => array((int) $group_term->term_id),
                    ),
                ),
            ));
            $ids = $query->posts;
        } else {
            return array();
        }

        $valid = array();
        foreach ($ids as $banner_id) {
            if ($this->can_render_banner((int) $banner_id)) {
                $valid[] = (int) $banner_id;
            }
        }

        return $valid;
    }

    private function can_render_banner($banner_id)
    {
        $post = get_post($banner_id);
        if (! $post || $post->post_type !== MLBS_Post_Types::POST_TYPE || $post->post_status !== 'publish') {
            return false;
        }

        $meta = MLBS_Helpers::get_banner_meta($banner_id);
        if ((int) $meta['_mlbs_is_active'] !== 1 || empty($meta['_mlbs_image_id'])) {
            return false;
        }

        if ((int) $meta['_mlbs_lifetime'] !== 1) {
            $now   = current_time('timestamp');
            $start = ! empty($meta['_mlbs_start_at']) ? strtotime((string) $meta['_mlbs_start_at']) : 0;
            $end   = ! empty($meta['_mlbs_end_at']) ? strtotime((string) $meta['_mlbs_end_at']) : 0;
            if ($start && $now < $start) {
                return false;
            }
            if ($end && $now > $end) {
                return false;
            }
        }

        return true;
    }

    private function render_banner_card($banner_id, $fallback_align)
    {
        $meta  = MLBS_Helpers::get_banner_meta($banner_id);
        $image = wp_get_attachment_image($meta['_mlbs_image_id'], 'full', false, array('class' => 'mlbs-image'));
        if (! $image) {
            return;
        }

        $alignment = ! empty($meta['_mlbs_alignment']) ? MLBS_Helpers::normalize_alignment($meta['_mlbs_alignment']) : $fallback_align;
        $duration  = max(1000, absint($meta['_mlbs_duration']));
        $click_url = '';
        if (! empty($meta['_mlbs_target_url'])) {
            // 3.2.0: o destino vem do cadastro do banner (sem parâmetro "redirect" e sem nonce).
            // O nonce vencia em até 24 h e, em página com cache, o visitante caía em "Link inválido"
            // e o clique não era contado.
            $click_url = (int) $this->settings->get('track_clicks', 1) === 1
                ? add_query_arg(array(
                    'action'    => 'mlbs_banner_click',
                    'banner_id' => $banner_id,
                ), admin_url('admin-post.php'))
                : $meta['_mlbs_target_url'];
        }
        ?>
        <div class="mlbs-item mlbs-item-align-<?php echo esc_attr($alignment); ?>" data-mlbs-banner-id="<?php echo esc_attr((string) $banner_id); ?>" style="<?php echo esc_attr('--mlbs-item-duration:' . $duration . 'ms'); ?>">
            <?php if ($click_url) : ?>
                <a class="mlbs-link" href="<?php echo esc_url($click_url); ?>" <?php echo (int) $meta['_mlbs_target_blank'] === 1 ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo $image; ?></a>
            <?php else : ?>
                <div class="mlbs-link"><?php echo $image; ?></div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Visita automatizada (robôs de busca, monitores, pré-carregamento) não conta.
     */
    private static function is_bot_request()
    {
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower((string) wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';
        if ($ua === '') {
            return true;
        }
        foreach (array('bot', 'crawl', 'spider', 'slurp', 'preview', 'facebookexternalhit', 'whatsapp', 'headless', 'lighthouse', 'pingdom', 'uptime', 'monitor', 'curl', 'wget', 'python-', 'httpclient', 'okhttp') as $needle) {
            if (strpos($ua, $needle) !== false) {
                return true;
            }
        }
        $purpose = isset($_SERVER['HTTP_SEC_PURPOSE']) ? (string) $_SERVER['HTTP_SEC_PURPOSE'] : (isset($_SERVER['HTTP_PURPOSE']) ? (string) $_SERVER['HTTP_PURPOSE'] : '');
        return stripos($purpose, 'prefetch') !== false;
    }

    /**
     * Impressão: contador público. Sem exigir nonce (3.2.0) — em página com cache o nonce
     * vencia e todas as impressões daquela página se perdiam. Só conta banner que pode ser
     * exibido, no máximo 50 por chamada, e ignora robôs.
     */
    public function ajax_track_impression()
    {
        if ((int) $this->settings->get('track_impressions', 1) !== 1 || self::is_bot_request()) {
            wp_send_json_success(array('tracked' => 0));
        }

        $raw_ids = isset($_POST['banner_ids']) ? (array) wp_unslash($_POST['banner_ids']) : array();
        $raw_ids = array_slice(array_unique(array_map('absint', $raw_ids)), 0, 50);
        $tracked = 0;

        foreach ($raw_ids as $banner_id) {
            if (! $banner_id || get_post_type($banner_id) !== MLBS_Post_Types::POST_TYPE || ! $this->can_render_banner($banner_id)) {
                continue;
            }

            $current = (int) get_post_meta($banner_id, '_mlbs_impressions', true);
            update_post_meta($banner_id, '_mlbs_impressions', $current + 1);
            MLBS_Database::increment_event($banner_id, 'impression', 1);
            $tracked++;
        }

        wp_send_json_success(array('tracked' => $tracked));
    }

    /**
     * Clique: conta e redireciona para o destino CADASTRADO no banner (nunca para um endereço
     * vindo da URL — sem redirecionamento aberto). Links antigos já em cache (com redirect e
     * nonce) continuam funcionando: o nonce não é mais exigido e o destino é sempre o cadastrado.
     */
    public function handle_banner_click()
    {
        $banner_id = isset($_GET['banner_id']) ? absint($_GET['banner_id']) : 0;
        $target    = '';

        if ($banner_id && get_post_type($banner_id) === MLBS_Post_Types::POST_TYPE) {
            $target = esc_url_raw((string) get_post_meta($banner_id, '_mlbs_target_url', true));
        }

        if ($target === '') {
            wp_safe_redirect(home_url('/'));
            exit;
        }

        if ((int) $this->settings->get('track_clicks', 1) === 1 && ! self::is_bot_request()) {
            $current = (int) get_post_meta($banner_id, '_mlbs_clicks', true);
            update_post_meta($banner_id, '_mlbs_clicks', $current + 1);
            MLBS_Database::increment_event($banner_id, 'click', 1);
        }

        nocache_headers();
        wp_redirect($target, 302, 'ML Banner Suite');
        exit;
    }
}
