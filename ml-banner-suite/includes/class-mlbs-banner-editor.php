<?php
/**
 * Banner editor and admin UX.
 *
 * @package MLBannerSuite
 */

if (! defined('ABSPATH')) {
    exit;
}

class MLBS_Banner_Editor
{
    private $settings;

    public function __construct(MLBS_Settings $settings)
    {
        $this->settings = $settings;
    }

    public function register_meta_boxes()
    {
        remove_meta_box('slugdiv', MLBS_Post_Types::POST_TYPE, 'normal');

        add_meta_box('mlbs-banner-editor', __('Dados do banner', MLBS_TEXT_DOMAIN), array($this, 'render_editor_meta_box'), MLBS_Post_Types::POST_TYPE, 'normal', 'high');
        add_meta_box('mlbs-banner-shortcodes', __('Shortcodes do banner', MLBS_TEXT_DOMAIN), array($this, 'render_shortcodes_meta_box'), MLBS_Post_Types::POST_TYPE, 'side', 'default');
        add_meta_box('mlbs-banner-status', __('Status e desempenho', MLBS_TEXT_DOMAIN), array($this, 'render_status_meta_box'), MLBS_Post_Types::POST_TYPE, 'side', 'default');
    }

    public function render_editor_meta_box($post)
    {
        $meta                = MLBS_Helpers::get_banner_meta($post->ID);
        $groups = get_terms(array('taxonomy' => 'mlbs_group', 'hide_empty' => false));
        if (is_wp_error($groups)) {
            $groups = array();
        }
        $selected_groups = wp_get_post_terms($post->ID, 'mlbs_group', array('fields' => 'ids'));
        if (is_wp_error($selected_groups)) {
            $selected_groups = array();
        }
        $selected_group = ! empty($selected_groups) ? (int) reset($selected_groups) : 0;
        $alignment_options   = MLBS_Helpers::get_alignment_options();
        $gif_timing          = MLBS_Helpers::get_attachment_timing_data((int) $meta['_mlbs_image_id']);
        $library_data        = $this->get_wp_library_page(1, 20);

        wp_nonce_field('mlbs_save_banner', 'mlbs_save_banner_nonce');
        ?>
        <div class="mlbs-editor">
            <div class="mlbs-editor-grid">
                <section class="mlbs-panel">
                    <h2><?php echo esc_html__('Identificação e mídia', MLBS_TEXT_DOMAIN); ?></h2>
                    <div class="mlbs-field-grid mlbs-field-grid-2">
                        <div class="mlbs-field">
                            <label for="title"><?php echo esc_html__('Título interno', MLBS_TEXT_DOMAIN); ?></label>
                            <input type="text" id="title" name="post_title" value="<?php echo esc_attr($post->post_title); ?>" class="widefat" />
                        </div>
                        <div class="mlbs-field">
                            <label for="mlbs_size_label"><?php echo esc_html__('Tamanho base do criativo', MLBS_TEXT_DOMAIN); ?></label>
                            <input type="text" id="mlbs_size_label" name="mlbs_meta[_mlbs_size_label]" value="<?php echo esc_attr($meta['_mlbs_size_label']); ?>" class="widefat" placeholder="1920x1080" />
                        </div>
                    </div>

                    <div class="mlbs-media-box">
                        <input type="hidden" name="mlbs_meta[_mlbs_image_id]" id="mlbs_image_id" value="<?php echo esc_attr($meta['_mlbs_image_id']); ?>" />
                        <input type="hidden" name="mlbs_meta[_mlbs_image_url]" id="mlbs_image_url" value="<?php echo esc_url($meta['_mlbs_image_url']); ?>" />
                        <div class="mlbs-media-preview <?php echo empty($meta['_mlbs_image_url']) ? 'is-empty' : ''; ?>" id="mlbs-media-preview">
                            <?php if (! empty($meta['_mlbs_image_url'])) : ?>
                                <img src="<?php echo esc_url($meta['_mlbs_image_url']); ?>" alt="" />
                            <?php else : ?>
                                <div class="mlbs-media-empty"><?php echo esc_html__('Nenhuma imagem selecionada.', MLBS_TEXT_DOMAIN); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="mlbs-media-actions">
                            <button type="button" class="button button-secondary mlbs-select-media"><?php echo esc_html__('Escolher da biblioteca do WordPress', MLBS_TEXT_DOMAIN); ?></button>
                            <button type="button" class="button mlbs-remove-image" <?php disabled(empty($meta['_mlbs_image_id'])); ?>><?php echo esc_html__('Remover seleção', MLBS_TEXT_DOMAIN); ?></button>
                        </div>
                        <div class="mlbs-gif-timing <?php echo empty($gif_timing['is_gif']) ? 'is-hidden' : ''; ?>" id="mlbs-gif-timing" data-default-label="<?php echo esc_attr__('Selecione um GIF animado para ver a duração total aqui.', MLBS_TEXT_DOMAIN); ?>" data-no-gif-label="<?php echo esc_attr__('GIF detectado, mas não foi possível calcular a duração automaticamente.', MLBS_TEXT_DOMAIN); ?>">
                            <strong><?php echo esc_html__('Tempo detectado do GIF', MLBS_TEXT_DOMAIN); ?></strong>
                            <div class="mlbs-gif-timing__meta" id="mlbs-gif-timing-meta">
                                <?php if (! empty($gif_timing['is_gif']) && ! empty($gif_timing['duration_ms'])) : ?>
                                    <span><?php echo esc_html(sprintf(__('Duração total: %d ms', MLBS_TEXT_DOMAIN), (int) $gif_timing['duration_ms'])); ?></span>
                                    <span><?php echo esc_html(sprintf(__('Frames: %d', MLBS_TEXT_DOMAIN), (int) $gif_timing['frames'])); ?></span>
                                    <?php if (! empty($gif_timing['frame_delay_ms'])) : ?>
                                        <span><?php echo esc_html(sprintf(__('Média por frame: %d ms', MLBS_TEXT_DOMAIN), (int) $gif_timing['frame_delay_ms'])); ?></span>
                                    <?php endif; ?>
                                <?php elseif (! empty($gif_timing['is_gif'])) : ?>
                                    <span><?php echo esc_html__('GIF detectado, mas não foi possível calcular a duração automaticamente.', MLBS_TEXT_DOMAIN); ?></span>
                                <?php else : ?>
                                    <span><?php echo esc_html__('Selecione um GIF animado para ver a duração total aqui.', MLBS_TEXT_DOMAIN); ?></span>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="button button-secondary mlbs-apply-gif-duration <?php echo (empty($gif_timing['duration_ms']) ? 'is-hidden' : ''); ?>" id="mlbs-apply-gif-duration" data-duration="<?php echo esc_attr((int) $gif_timing['duration_ms']); ?>"><?php echo esc_html__('Usar duração do GIF no tempo de exibição', MLBS_TEXT_DOMAIN); ?></button>
                        </div>
                    </div>

                    <div class="mlbs-library-head">
                        <div>
                            <h3><?php echo esc_html__('Biblioteca de mídia do WordPress', MLBS_TEXT_DOMAIN); ?></h3>
                            <p class="description"><?php echo esc_html__('As imagens da biblioteca aparecem aqui em lotes de 20. Você pode selecionar visualmente abaixo e carregar mais quando precisar.', MLBS_TEXT_DOMAIN); ?></p>
                        </div>
                        <button type="button" class="button button-secondary mlbs-select-media"><?php echo esc_html__('Abrir biblioteca completa do WordPress', MLBS_TEXT_DOMAIN); ?></button>
                    </div>
                    <div class="mlbs-wp-library" id="mlbs-wp-library" data-page="1" data-per-page="20" data-has-more="<?php echo esc_attr(! empty($library_data['has_more']) ? '1' : '0'); ?>">
                        <div class="mlbs-library-grid" id="mlbs-library-grid">
                            <?php if (! empty($library_data['items'])) : ?>
                                <?php foreach ($library_data['items'] as $library_item) : ?>
                                    <?php echo $this->render_library_card($library_item, (int) $meta['_mlbs_image_id']); ?>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <div class="mlbs-library-empty" id="mlbs-library-empty"><?php echo esc_html__('Nenhuma imagem encontrada na biblioteca do WordPress.', MLBS_TEXT_DOMAIN); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="mlbs-library-footer">
                            <button type="button" class="button button-secondary" id="mlbs-load-more-library" <?php disabled(empty($library_data['has_more'])); ?>><?php echo esc_html__('Carregar mais', MLBS_TEXT_DOMAIN); ?></button>
                        </div>
                    </div>
                </section>

                <section class="mlbs-panel">
                    <h2><?php echo esc_html__('Destino e configuração', MLBS_TEXT_DOMAIN); ?></h2>
                    <div class="mlbs-field-grid mlbs-field-grid-2">
                        <div class="mlbs-field mlbs-field-full">
                            <label for="mlbs_target_url"><?php echo esc_html__('Link de destino', MLBS_TEXT_DOMAIN); ?></label>
                            <input type="url" id="mlbs_target_url" name="mlbs_meta[_mlbs_target_url]" value="<?php echo esc_attr($meta['_mlbs_target_url']); ?>" class="widefat" placeholder="https://" />
                        </div>
                        <div class="mlbs-check-grid">
                            <label class="mlbs-check"><input type="checkbox" name="mlbs_meta[_mlbs_target_blank]" value="1" <?php checked((int) $meta['_mlbs_target_blank'], 1); ?> /> <?php echo esc_html__('Abrir em nova aba', MLBS_TEXT_DOMAIN); ?></label>
                            <label class="mlbs-check"><input type="checkbox" name="mlbs_meta[_mlbs_lifetime]" id="mlbs_lifetime" value="1" <?php checked((int) $meta['_mlbs_lifetime'], 1); ?> /> <?php echo esc_html__('Vitalício', MLBS_TEXT_DOMAIN); ?></label>
                            <label class="mlbs-check"><input type="checkbox" name="mlbs_meta[_mlbs_is_active]" value="1" <?php checked((int) $meta['_mlbs_is_active'], 1); ?> /> <?php echo esc_html__('Banner ativo', MLBS_TEXT_DOMAIN); ?></label>
                        </div>
                    </div>

                    <h2><?php echo esc_html__('Organização', MLBS_TEXT_DOMAIN); ?></h2>
                    <div class="mlbs-field-grid mlbs-field-grid-2">
                        <div class="mlbs-field">
                            <label for="mlbs_group"><?php echo esc_html__('Grupo', MLBS_TEXT_DOMAIN); ?></label>
                            <select id="mlbs_group" name="mlbs_group" class="widefat">
                                <option value="0"><?php echo esc_html__('Selecione um grupo', MLBS_TEXT_DOMAIN); ?></option>
                                <?php foreach ((array) $groups as $group) : ?>
                                    <?php if (is_wp_error($group)) { continue; } ?>
                                    <option value="<?php echo esc_attr($group->term_id); ?>" <?php selected($selected_group, (int) $group->term_id); ?>><?php echo esc_html($group->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mlbs-field">
                            <label for="mlbs_alignment"><?php echo esc_html__('Alinhamento', MLBS_TEXT_DOMAIN); ?></label>
                            <select id="mlbs_alignment" name="mlbs_meta[_mlbs_alignment]" class="widefat">
                                <?php foreach ($alignment_options as $alignment_value => $alignment_label) : ?>
                                    <option value="<?php echo esc_attr($alignment_value); ?>" <?php selected(MLBS_Helpers::normalize_alignment($meta['_mlbs_alignment']), $alignment_value); ?>><?php echo esc_html($alignment_label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <h2><?php echo esc_html__('Exibição', MLBS_TEXT_DOMAIN); ?></h2>
                    <div class="mlbs-field-grid mlbs-field-grid-3">
                        <div class="mlbs-field">
                            <label for="mlbs_width"><?php echo esc_html__('Largura opcional', MLBS_TEXT_DOMAIN); ?></label>
                            <input type="text" id="mlbs_width" name="mlbs_meta[_mlbs_width]" value="<?php echo esc_attr($meta['_mlbs_width']); ?>" class="widefat" placeholder="100%" />
                        </div>
                        <div class="mlbs-field">
                            <label for="mlbs_height"><?php echo esc_html__('Altura opcional', MLBS_TEXT_DOMAIN); ?></label>
                            <input type="text" id="mlbs_height" name="mlbs_meta[_mlbs_height]" value="<?php echo esc_attr($meta['_mlbs_height']); ?>" class="widefat" placeholder="auto" />
                        </div>
                        <div class="mlbs-field">
                            <label for="mlbs_duration"><?php echo esc_html__('Tempo de exibição (ms)', MLBS_TEXT_DOMAIN); ?></label>
                            <input type="number" min="1" step="1" id="mlbs_duration" name="mlbs_meta[_mlbs_duration]" value="<?php echo esc_attr($meta['_mlbs_duration']); ?>" class="widefat" />
                        </div>
                    </div>

                    <h2><?php echo esc_html__('Agendamento', MLBS_TEXT_DOMAIN); ?></h2>
                    <p class="description mlbs-schedule-note"><?php echo esc_html__('Se Vitalício estiver marcado, as datas serão ignoradas no front, mas continuam editáveis aqui.', MLBS_TEXT_DOMAIN); ?></p>
                    <div class="mlbs-field-grid mlbs-field-grid-2 mlbs-schedule-fields" id="mlbs-schedule-fields">
                        <div class="mlbs-field">
                            <label for="mlbs_start_at"><?php echo esc_html__('Data/hora de início', MLBS_TEXT_DOMAIN); ?></label>
                            <input type="datetime-local" id="mlbs_start_at" name="mlbs_meta[_mlbs_start_at]" value="<?php echo esc_attr($this->format_datetime_local($meta['_mlbs_start_at'])); ?>" class="widefat" />
                        </div>
                        <div class="mlbs-field">
                            <label for="mlbs_end_at"><?php echo esc_html__('Data/hora de término', MLBS_TEXT_DOMAIN); ?></label>
                            <input type="datetime-local" id="mlbs_end_at" name="mlbs_meta[_mlbs_end_at]" value="<?php echo esc_attr($this->format_datetime_local($meta['_mlbs_end_at'])); ?>" class="widefat" />
                        </div>
                    </div>
                </section>
            </div>
        </div>
        <?php
    }

    public function render_shortcodes_meta_box($post)
    {
        $groups = wp_get_post_terms($post->ID, 'mlbs_group');
        ?>
        <p><strong><?php echo esc_html__('Banner individual', MLBS_TEXT_DOMAIN); ?></strong></p>
        <p><code>[ml_banner_suite id="<?php echo esc_attr($post->ID); ?>"]</code></p>
        <?php if (! empty($groups) && ! is_wp_error($groups)) : ?>
            <p><strong><?php echo esc_html__('Shortcode do grupo', MLBS_TEXT_DOMAIN); ?></strong></p>
            <?php foreach ($groups as $group) : ?>
                <p><code><?php echo esc_html(MLBS_Helpers::get_group_shortcode($group->slug)); ?></code></p>
            <?php endforeach; ?>
        <?php else : ?>
            <p class="description"><?php echo esc_html__('Associe este banner a um grupo para liberar o shortcode do grupo.', MLBS_TEXT_DOMAIN); ?></p>
        <?php endif; ?>
        <?php
    }

    public function render_status_meta_box($post)
    {
        $meta = MLBS_Helpers::get_banner_meta($post->ID);
        ?>
        <ul class="mlbs-status-list">
            <li><strong><?php echo esc_html__('Situação real:', MLBS_TEXT_DOMAIN); ?></strong> <?php echo esc_html(MLBS_Helpers::get_banner_status_label($post->ID)); ?></li>
            <li><strong><?php echo esc_html__('Impressões:', MLBS_TEXT_DOMAIN); ?></strong> <?php echo esc_html((string) $meta['_mlbs_impressions']); ?></li>
            <li><strong><?php echo esc_html__('Cliques:', MLBS_TEXT_DOMAIN); ?></strong> <?php echo esc_html((string) $meta['_mlbs_clicks']); ?></li>
            <li><strong><?php echo esc_html__('Status lógico:', MLBS_TEXT_DOMAIN); ?></strong> <?php echo esc_html((int) $meta['_mlbs_is_active'] === 1 ? __('Ativo', MLBS_TEXT_DOMAIN) : __('Inativo', MLBS_TEXT_DOMAIN)); ?></li>
        </ul>
        <?php
    }

    public function save_banner($post_id, $post)
    {
        if (! isset($_POST['mlbs_save_banner_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mlbs_save_banner_nonce'])), 'mlbs_save_banner')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (! current_user_can('edit_post', $post_id)) {
            return;
        }

        $defaults = MLBS_Helpers::get_banner_meta($post_id);
        $incoming = isset($_POST['mlbs_meta']) ? (array) wp_unslash($_POST['mlbs_meta']) : array();
        $data     = wp_parse_args($incoming, $defaults);

        $sanitized = array(
            '_mlbs_image_id'     => absint($data['_mlbs_image_id']),
            '_mlbs_image_url'    => esc_url_raw($data['_mlbs_image_url']),
            '_mlbs_size_label'   => sanitize_text_field($data['_mlbs_size_label']),
            '_mlbs_target_url'   => esc_url_raw($data['_mlbs_target_url']),
            '_mlbs_target_blank' => empty($data['_mlbs_target_blank']) ? 0 : 1,
            '_mlbs_start_at'     => $this->sanitize_datetime($data['_mlbs_start_at']),
            '_mlbs_end_at'       => $this->sanitize_datetime($data['_mlbs_end_at']),
            '_mlbs_lifetime'     => empty($data['_mlbs_lifetime']) ? 0 : 1,
            '_mlbs_is_active'    => empty($data['_mlbs_is_active']) ? 0 : 1,
            '_mlbs_width'        => sanitize_text_field($data['_mlbs_width']),
            '_mlbs_height'       => sanitize_text_field($data['_mlbs_height']),
            '_mlbs_duration'     => max(1, absint($data['_mlbs_duration'])),
            '_mlbs_alignment'    => MLBS_Helpers::normalize_alignment($data['_mlbs_alignment']),
        );

        if ((int) $sanitized['_mlbs_lifetime'] === 1) {
            $sanitized['_mlbs_start_at'] = '';
            $sanitized['_mlbs_end_at']   = '';
        }

        foreach ($sanitized as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }

        if (! metadata_exists('post', $post_id, '_mlbs_clicks')) {
            update_post_meta($post_id, '_mlbs_clicks', 0);
        }
        if (! metadata_exists('post', $post_id, '_mlbs_impressions')) {
            update_post_meta($post_id, '_mlbs_impressions', 0);
        }

        if (! empty($sanitized['_mlbs_image_id'])) {
            set_post_thumbnail($post_id, $sanitized['_mlbs_image_id']);
        } else {
            delete_post_thumbnail($post_id);
        }

        $group_id = isset($_POST['mlbs_group']) ? absint(wp_unslash($_POST['mlbs_group'])) : 0;
        wp_set_post_terms($post_id, $group_id ? array($group_id) : array(), 'mlbs_group', false);

        if (isset($_POST['post_title'])) {
            remove_action('save_post_' . MLBS_Post_Types::POST_TYPE, array($this, 'save_banner'), 10);
            wp_update_post(array(
                'ID'         => $post_id,
                'post_title' => sanitize_text_field(wp_unslash($_POST['post_title'])),
            ));
            add_action('save_post_' . MLBS_Post_Types::POST_TYPE, array($this, 'save_banner'), 10, 2);
        }
    }

    public function ajax_upload_banner_image()
    {
        check_ajax_referer('mlbs_admin_nonce', 'nonce');
        if (! current_user_can('upload_files')) {
            wp_send_json_error(array('message' => __('Permissão negada.', MLBS_TEXT_DOMAIN)), 403);
        }
        if (empty($_FILES['banner_file'])) {
            wp_send_json_error(array('message' => __('Nenhum arquivo enviado.', MLBS_TEXT_DOMAIN)), 400);
        }

        MLBS_Helpers::ensure_banner_directory();
        add_filter('upload_dir', array($this, 'filter_banner_upload_dir'));
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $overrides = array(
            'test_form' => false,
            'mimes'     => array(
                'jpg|jpeg' => 'image/jpeg',
                'png'      => 'image/png',
                'gif'      => 'image/gif',
                'webp'     => 'image/webp',
            ),
        );

        $uploaded = wp_handle_upload($_FILES['banner_file'], $overrides);
        remove_filter('upload_dir', array($this, 'filter_banner_upload_dir'));

        if (isset($uploaded['error'])) {
            wp_send_json_error(array('message' => $uploaded['error']), 400);
        }

        $attachment = array(
            'guid'           => $uploaded['url'],
            'post_mime_type' => $uploaded['type'],
            'post_title'     => sanitize_file_name(pathinfo($uploaded['file'], PATHINFO_FILENAME)),
            'post_content'   => '',
            'post_status'    => 'inherit',
        );

        $attachment_id = wp_insert_attachment($attachment, $uploaded['file']);
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(array('message' => $attachment_id->get_error_message()), 400);
        }

        $metadata = wp_generate_attachment_metadata($attachment_id, $uploaded['file']);
        wp_update_attachment_metadata($attachment_id, $metadata);
        update_post_meta($attachment_id, '_mlbs_is_banner_asset', 1);

        wp_send_json_success($this->get_attachment_payload($attachment_id, true));
    }

    public function ajax_delete_banner_image()
    {
        check_ajax_referer('mlbs_admin_nonce', 'nonce');
        if (! current_user_can('upload_files')) {
            wp_send_json_error(array('message' => __('Permissão negada.', MLBS_TEXT_DOMAIN)), 403);
        }

        $attachment_id = isset($_POST['attachment_id']) ? absint($_POST['attachment_id']) : 0;
        if (! $attachment_id) {
            wp_send_json_error(array('message' => __('Imagem inválida.', MLBS_TEXT_DOMAIN)), 400);
        }

        $deleted = wp_delete_attachment($attachment_id, true);
        if (! $deleted) {
            wp_send_json_error(array('message' => __('Não foi possível excluir a imagem.', MLBS_TEXT_DOMAIN)), 400);
        }

        wp_send_json_success(array('attachment_id' => $attachment_id));
    }

    public function ajax_get_attachment_details()
    {
        check_ajax_referer('mlbs_admin_nonce', 'nonce');
        if (! current_user_can('upload_files')) {
            wp_send_json_error(array('message' => __('Permissão negada.', MLBS_TEXT_DOMAIN)), 403);
        }

        $attachment_id = isset($_POST['attachment_id']) ? absint($_POST['attachment_id']) : 0;
        if (! $attachment_id) {
            wp_send_json_error(array('message' => __('Imagem inválida.', MLBS_TEXT_DOMAIN)), 400);
        }

        wp_send_json_success($this->get_attachment_payload($attachment_id));
    }

    public function ajax_load_wp_library()
    {
        check_ajax_referer('mlbs_admin_nonce', 'nonce');
        if (! current_user_can('upload_files')) {
            wp_send_json_error(array('message' => __('Permissão negada.', MLBS_TEXT_DOMAIN)), 403);
        }

        $page     = isset($_POST['page']) ? max(1, absint($_POST['page'])) : 1;
        $per_page = isset($_POST['per_page']) ? max(1, min(100, absint($_POST['per_page']))) : 20;
        $selected = isset($_POST['selected_id']) ? absint($_POST['selected_id']) : 0;

        $data = $this->get_wp_library_page($page, $per_page);
        $html = '';

        foreach ($data['items'] as $item) {
            $html .= $this->render_library_card($item, $selected);
        }

        wp_send_json_success(array(
            'html'      => $html,
            'page'      => $page,
            'per_page'  => $per_page,
            'has_more'  => ! empty($data['has_more']) ? 1 : 0,
            'returned'  => count($data['items']),
        ));
    }

    public function ajax_save_banner_order()
    {
        check_ajax_referer('mlbs_admin_nonce', 'nonce');
        if (! current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Permissão negada.', MLBS_TEXT_DOMAIN)), 403);
        }

        $ids = isset($_POST['ids']) ? (array) wp_unslash($_POST['ids']) : array();
        $ids = array_values(array_filter(array_map('absint', $ids)));
        if (empty($ids)) {
            wp_send_json_error(array('message' => __('Nenhum banner recebido para ordenar.', MLBS_TEXT_DOMAIN)), 400);
        }

        foreach ($ids as $index => $post_id) {
            update_post_meta($post_id, '_mlbs_manual_order', (int) $index + 1);
            wp_update_post(array('ID' => $post_id, 'menu_order' => (int) $index + 1));
        }

        wp_send_json_success(array('message' => __('Ordem atualizada com sucesso.', MLBS_TEXT_DOMAIN)));
    }

    public function filter_banner_upload_dir($dirs)
    {
        $base = MLBS_Helpers::get_upload_base();
        $uploads = wp_upload_dir();
        $dirs['basedir'] = $uploads['basedir'];
        $dirs['baseurl'] = $uploads['baseurl'];
        $dirs['subdir']  = '/ml-banner-suite/banners';
        $dirs['path']    = $base['path'];
        $dirs['url']     = $base['url'];
        return $dirs;
    }

    public function register_banner_columns($columns)
    {
        return array(
            'cb'               => $columns['cb'],
            'mlbs_image'       => __('Imagem', MLBS_TEXT_DOMAIN),
            'title'            => __('Título', MLBS_TEXT_DOMAIN),
            'mlbs_groups'      => __('Grupo', MLBS_TEXT_DOMAIN),
            'mlbs_group_type'  => __('Exibição do grupo', MLBS_TEXT_DOMAIN),
            'mlbs_size'        => __('Tamanho', MLBS_TEXT_DOMAIN),
            'mlbs_alignment'   => __('Alinhamento', MLBS_TEXT_DOMAIN),
            'mlbs_period'      => __('Período', MLBS_TEXT_DOMAIN),
            'mlbs_order'       => __('Ordem', MLBS_TEXT_DOMAIN),
            'mlbs_impressions' => __('Impressões', MLBS_TEXT_DOMAIN),
            'mlbs_clicks'      => __('Cliques', MLBS_TEXT_DOMAIN),
            'mlbs_status'      => __('Status', MLBS_TEXT_DOMAIN),
            'mlbs_shortcode'   => __('Shortcode', MLBS_TEXT_DOMAIN),
            'date'             => __('Data', MLBS_TEXT_DOMAIN),
        );
    }

    public function render_banner_columns($column, $post_id)
    {
        $meta = MLBS_Helpers::get_banner_meta($post_id);
        $alignment_options = MLBS_Helpers::get_alignment_options();

        switch ($column) {
            case 'mlbs_image':
                if (! empty($meta['_mlbs_image_id'])) {
                    echo wp_get_attachment_image((int) $meta['_mlbs_image_id'], array(90, 60), false, array('style' => 'width:90px;height:auto;'));
                } else {
                    echo '<span class="mlbs-dash">—</span>';
                }
                break;
            case 'mlbs_groups':
                echo esc_html($this->implode_terms($post_id, 'mlbs_group'));
                break;
            case 'mlbs_group_type':
                $groups = wp_get_post_terms($post_id, 'mlbs_group');
                if (! empty($groups) && ! is_wp_error($groups)) {
                    $options = MLBS_Helpers::get_display_mode_options();
                    $mode = MLBS_Helpers::get_group_mode($groups[0]->term_id);
                    echo isset($options[$mode]) ? esc_html($options[$mode]) : '—';
                } else {
                    echo '<span class="mlbs-dash">—</span>';
                }
                break;
            case 'mlbs_size':
                echo esc_html($meta['_mlbs_size_label'] ? (string) $meta['_mlbs_size_label'] : '—');
                break;
            case 'mlbs_alignment':
                $alignment = MLBS_Helpers::normalize_alignment($meta['_mlbs_alignment']);
                echo esc_html(isset($alignment_options[$alignment]) ? $alignment_options[$alignment] : '—');
                break;
            case 'mlbs_period':
                if ((int) $meta['_mlbs_lifetime'] === 1) {
                    echo esc_html__('Vitalício', MLBS_TEXT_DOMAIN);
                } else {
                    $start = $meta['_mlbs_start_at'] ? mysql2date('d/m/Y H:i', (string) $meta['_mlbs_start_at']) : '—';
                    $end   = $meta['_mlbs_end_at'] ? mysql2date('d/m/Y H:i', (string) $meta['_mlbs_end_at']) : '—';
                    echo esc_html($start . ' → ' . $end);
                }
                break;
            case 'mlbs_order':
                echo esc_html((string) $meta['_mlbs_manual_order']);
                break;
            case 'mlbs_impressions':
                echo esc_html((string) $meta['_mlbs_impressions']);
                break;
            case 'mlbs_clicks':
                echo esc_html((string) $meta['_mlbs_clicks']);
                break;
            case 'mlbs_status':
                echo esc_html(MLBS_Helpers::get_banner_status_label($post_id));
                break;
            case 'mlbs_shortcode':
                echo '<code>[ml_banner_suite id="' . esc_html((string) $post_id) . '"]</code>';
                break;
        }
    }

    public function filter_title_column_content($title, $post_id)
    {
        return $title;
    }

    public function register_sortable_columns($columns)
    {
        $columns['mlbs_order'] = 'mlbs_order';
        return $columns;
    }

    public function handle_admin_sorting($query)
    {
        if (! is_admin() || ! $query->is_main_query()) {
            return;
        }
        if ($query->get('post_type') !== MLBS_Post_Types::POST_TYPE) {
            return;
        }

        $meta_query = array(
            'relation' => 'OR',
            array(
                'key'     => '_mlbs_manual_order',
                'compare' => 'EXISTS',
            ),
            array(
                'key'     => '_mlbs_manual_order',
                'compare' => 'NOT EXISTS',
            ),
        );

        if ($query->get('orderby') === 'mlbs_order') {
            $query->set('meta_query', $meta_query);
            $query->set('orderby', array(
                'meta_value_num' => 'ASC',
                'date'           => 'DESC',
            ));
            $query->set('order', 'ASC');
            return;
        }

        if (! $query->get('orderby')) {
            $query->set('meta_query', $meta_query);
            $query->set('orderby', array(
                'meta_value_num' => 'ASC',
                'date'           => 'DESC',
            ));
            $query->set('order', 'ASC');
        }
    }

    public function register_row_actions($actions, $post)
    {
        if ($post->post_type !== MLBS_Post_Types::POST_TYPE) {
            return $actions;
        }

        $clone_url = wp_nonce_url(add_query_arg(array(
            'mlbs_action' => 'clone_banner',
            'post_id'     => $post->ID,
        ), admin_url('edit.php?post_type=' . MLBS_Post_Types::POST_TYPE)), 'mlbs_clone_banner_' . $post->ID);

        $actions['mlbs_clone'] = '<a href="' . esc_url($clone_url) . '">' . esc_html__('Clonar', MLBS_TEXT_DOMAIN) . '</a>';
        return $actions;
    }

    public function register_admin_actions()
    {
        if (! is_admin() || ! current_user_can('edit_posts')) {
            return;
        }

        $action  = isset($_GET['mlbs_action']) ? sanitize_key(wp_unslash($_GET['mlbs_action'])) : '';
        $post_id = isset($_GET['post_id']) ? absint($_GET['post_id']) : 0;

        if ($action === 'clone_banner' && $post_id && isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'mlbs_clone_banner_' . $post_id)) {
            $new_id = $this->clone_banner($post_id);
            if ($new_id) {
                wp_safe_redirect(admin_url('post.php?post=' . $new_id . '&action=edit'));
                exit;
            }
        }
    }

    private function clone_banner($post_id)
    {
        $post = get_post($post_id);
        if (! $post || $post->post_type !== MLBS_Post_Types::POST_TYPE) {
            return 0;
        }

        $new_id = wp_insert_post(array(
            'post_type'   => MLBS_Post_Types::POST_TYPE,
            'post_status' => 'draft',
            'post_title'  => sprintf(__('Cópia de %s', MLBS_TEXT_DOMAIN), $post->post_title),
        ));

        if (is_wp_error($new_id) || ! $new_id) {
            return 0;
        }

        $meta = MLBS_Helpers::get_banner_meta($post_id);
        unset($meta['_mlbs_clicks'], $meta['_mlbs_impressions']);
        foreach ($meta as $key => $value) {
            if ($key === '_mlbs_manual_order') {
                $value = 0;
            }
            update_post_meta($new_id, $key, $value);
        }

        update_post_meta($new_id, '_mlbs_clicks', 0);
        update_post_meta($new_id, '_mlbs_impressions', 0);
        update_post_meta($new_id, '_mlbs_is_active', 0);
        set_post_thumbnail($new_id, (int) get_post_thumbnail_id($post_id));

        $groups = wp_get_post_terms($post_id, 'mlbs_group', array('fields' => 'ids'));
        if (is_wp_error($groups)) {
            $groups = array();
        }
        wp_set_post_terms($new_id, $groups, 'mlbs_group', false);

        return $new_id;
    }

    private function get_library_items()
    {
        $this->sync_banner_directory_to_library();

        $attachments = get_posts(array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 120,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => array(
                array(
                    'key'   => '_mlbs_is_banner_asset',
                    'value' => 1,
                ),
            ),
        ));

        if (! is_array($attachments)) {
            $attachments = array();
        }

        return $attachments;
    }

    private function sync_banner_directory_to_library()
    {
        $directories = array(
            MLBS_Helpers::get_upload_base()['path'],
            MLBS_Helpers::get_legacy_banner_base()['path'],
        );

        foreach ($directories as $directory) {
            $directory = wp_normalize_path((string) $directory);
            if ($directory === '' || ! is_dir($directory)) {
                continue;
            }

            foreach (glob(trailingslashit($directory) . '*') as $file) {
                $file = wp_normalize_path((string) $file);
                if (! is_file($file)) {
                    continue;
                }

                $extension = strtolower((string) pathinfo($file, PATHINFO_EXTENSION));
                if (! in_array($extension, array('jpg', 'jpeg', 'png', 'gif', 'webp'), true)) {
                    continue;
                }

                $this->get_or_create_attachment_for_banner_file($file);
            }
        }
    }

    private function get_or_create_attachment_for_banner_file($file)
    {
        $file = MLBS_Helpers::normalize_banner_file_to_uploads($file);
        if (! $file || ! file_exists($file)) {
            return 0;
        }

        $base     = MLBS_Helpers::get_upload_base();
        $relative = MLBS_Helpers::get_relative_upload_file($file);
        if ($relative === '') {
            return 0;
        }

        $existing = get_posts(array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_key'       => '_wp_attached_file',
            'meta_value'     => $relative,
        ));

        if (! empty($existing)) {
            $attachment_id = (int) $existing[0];
            update_post_meta($attachment_id, '_mlbs_is_banner_asset', 1);
            MLBS_Helpers::repair_banner_attachment($attachment_id);
            return $attachment_id;
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';

        $mime = wp_check_filetype($file);
        $attachment = array(
            'guid'           => trailingslashit($base['url']) . basename($file),
            'post_mime_type' => isset($mime['type']) ? $mime['type'] : '',
            'post_title'     => sanitize_file_name(pathinfo($file, PATHINFO_FILENAME)),
            'post_content'   => '',
            'post_status'    => 'inherit',
        );

        $attachment_id = wp_insert_attachment($attachment, $file);
        if (is_wp_error($attachment_id) || ! $attachment_id) {
            return 0;
        }

        update_post_meta($attachment_id, '_wp_attached_file', $relative);
        update_post_meta($attachment_id, '_mlbs_is_banner_asset', 1);
        $metadata = wp_generate_attachment_metadata($attachment_id, $file);
        if (! is_wp_error($metadata) && ! empty($metadata)) {
            wp_update_attachment_metadata($attachment_id, $metadata);
        }

        return (int) $attachment_id;
    }

    private function render_library_card($item, $selected_id = 0)
    {
        $attachment_id = is_object($item) ? (int) $item->ID : 0;
        $image         = wp_get_attachment_image_url($attachment_id, 'medium');
        $full          = wp_get_attachment_image_url($attachment_id, 'full');
        if (! $attachment_id || ! $full) {
            return '';
        }

        $timing = MLBS_Helpers::get_attachment_timing_data($attachment_id);
        $title  = get_the_title($attachment_id);
        if ($title === '') {
            $title = basename((string) get_attached_file($attachment_id));
        }

        ob_start();
        ?>
        <button type="button" class="mlbs-library-card <?php echo $attachment_id === (int) $selected_id ? 'is-selected' : ''; ?>" data-attachment-id="<?php echo esc_attr($attachment_id); ?>" data-image-url="<?php echo esc_url($full); ?>" data-is-gif="<?php echo esc_attr(! empty($timing['is_gif']) ? '1' : '0'); ?>" data-duration-ms="<?php echo esc_attr((int) $timing['duration_ms']); ?>" data-frames="<?php echo esc_attr((int) $timing['frames']); ?>" data-frame-delay-ms="<?php echo esc_attr((int) $timing['frame_delay_ms']); ?>">
            <span class="mlbs-library-thumb"><img src="<?php echo esc_url($image ? $image : $full); ?>" alt="" loading="lazy" /></span>
            <span class="mlbs-library-name"><?php echo esc_html($title); ?></span>
        </button>
        <?php
        return (string) ob_get_clean();
    }

    private function get_attachment_payload($attachment_id, $include_card_html = false)
    {
        $attachment_id = absint($attachment_id);
        $url = wp_get_attachment_image_url($attachment_id, 'large');
        if (! $url) {
            $url = wp_get_attachment_url($attachment_id);
        }

        $payload = array(
            'attachment_id' => $attachment_id,
            'url'           => $url,
            'gif_timing'    => MLBS_Helpers::get_attachment_timing_data($attachment_id),
        );

        if ($include_card_html) {
            $payload['card_html'] = $this->render_library_card(get_post($attachment_id), $attachment_id);
        }

        return $payload;
    }

    private function get_wp_library_page($page = 1, $per_page = 20)
    {
        $page     = max(1, absint($page));
        $per_page = max(1, min(100, absint($per_page)));

        $attachments = get_posts(array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'post_mime_type' => 'image',
            'posts_per_page' => $per_page + 1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'paged'          => $page,
        ));

        if (! is_array($attachments)) {
            $attachments = array();
        }

        $has_more = count($attachments) > $per_page;
        if ($has_more) {
            $attachments = array_slice($attachments, 0, $per_page);
        }

        return array(
            'items'    => $attachments,
            'has_more' => $has_more,
        );
    }

    private function implode_terms($post_id, $taxonomy)
    {
        $terms = get_the_terms($post_id, $taxonomy);
        if (empty($terms) || is_wp_error($terms)) {
            return '—';
        }
        return implode(', ', wp_list_pluck($terms, 'name'));
    }

    private function sanitize_datetime($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $timestamp = strtotime($value);
        if (! $timestamp) {
            return '';
        }

        return gmdate('Y-m-d H:i:s', $timestamp + (get_option('gmt_offset') * HOUR_IN_SECONDS));
    }

    private function format_datetime_local($value)
    {
        if (empty($value)) {
            return '';
        }
        $timestamp = strtotime((string) $value);
        return $timestamp ? gmdate('Y-m-d\TH:i', $timestamp) : '';
    }
}
