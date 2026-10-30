<?php
/**
 * Assets loader.
 *
 * @package MLBannerSuite
 */

if (! defined('ABSPATH')) {
    exit;
}

class MLBS_Assets
{
    private $settings;

    public function __construct(MLBS_Settings $settings)
    {
        $this->settings = $settings;
    }

    public function enqueue_admin_assets($hook_suffix)
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (! $screen) {
            return;
        }

        $allowed_screens = array(
            'toplevel_page_mlbs-dashboard',
            MLBS_Post_Types::POST_TYPE,
            'edit-' . MLBS_Post_Types::POST_TYPE,
            MLBS_Post_Types::POST_TYPE . '_page_mlbs-statistics',
            MLBS_Post_Types::POST_TYPE . '_page_mlbs-shortcodes',
            MLBS_Post_Types::POST_TYPE . '_page_mlbs-settings',
            'ml-banner-suite_page_mlbs-statistics',
            'ml-banner-suite_page_mlbs-shortcodes',
            'ml-banner-suite_page_mlbs-settings',
            'ml-banner-suite_page_mlbs-license',
            'edit-mlbs_group',
            'edit-mlbs_category',
        );

        if (! in_array($screen->id, $allowed_screens, true) && strpos((string) $hook_suffix, 'mlbs') === false) {
            return;
        }

        wp_enqueue_style('mlbs-admin', MLBS_PLUGIN_URL . 'assets/css/admin.css', array(), MLBS_VERSION);
        wp_enqueue_script('mlbs-admin', MLBS_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'jquery-ui-sortable'), MLBS_VERSION, true);
        wp_enqueue_media();

        wp_localize_script('mlbs-admin', 'mlbsAdmin', array(
            'pluginName'        => __('ML Banner Suite', MLBS_TEXT_DOMAIN),
            'version'           => MLBS_VERSION,
            'ajaxUrl'           => admin_url('admin-ajax.php'),
            'nonce'             => wp_create_nonce('mlbs_admin_nonce'),
            'uploading'         => __('Enviando imagem...', MLBS_TEXT_DOMAIN),
            'processing'        => __('Processando...', MLBS_TEXT_DOMAIN),
            'selectImage'       => __('Selecionar imagem', MLBS_TEXT_DOMAIN),
            'removeImage'       => __('Remover imagem', MLBS_TEXT_DOMAIN),
            'chooseImage'       => __('Escolher da biblioteca do WordPress', MLBS_TEXT_DOMAIN),
            'useImage'          => __('Usar esta imagem', MLBS_TEXT_DOMAIN),
            'emptyImage'        => __('Nenhuma imagem selecionada.', MLBS_TEXT_DOMAIN),
            'uploadError'       => __('Não foi possível enviar a imagem.', MLBS_TEXT_DOMAIN),
            'orderSaved'        => __('Ordem atualizada.', MLBS_TEXT_DOMAIN),
            'saved'             => __('Salvo com sucesso.', MLBS_TEXT_DOMAIN),
            'copied'            => __('Copiado para a área de transferência.', MLBS_TEXT_DOMAIN),
            'orderSaveError'    => __('Não foi possível salvar a ordem.', MLBS_TEXT_DOMAIN),
            'dragHandleText'    => __('Arraste para ordenar', MLBS_TEXT_DOMAIN),
            'gifDefault'        => __('Selecione um GIF animado para ver a duração total aqui.', MLBS_TEXT_DOMAIN),
            'gifNotAnimated'    => __('GIF detectado, mas não foi possível calcular a duração automaticamente.', MLBS_TEXT_DOMAIN),
            'gifDurationLabel'  => __('Duração total: %d ms', MLBS_TEXT_DOMAIN),
            'gifFramesLabel'    => __('Frames: %d', MLBS_TEXT_DOMAIN),
            'gifFrameDelayLabel'=> __('Média por frame: %d ms', MLBS_TEXT_DOMAIN),
            'deleteImage'       => __('Excluir imagem', MLBS_TEXT_DOMAIN),
            'deleteImageConfirm'=> __('Deseja excluir esta imagem da biblioteca de banners? Essa ação remove o arquivo do servidor.', MLBS_TEXT_DOMAIN),
            'deleteImageError'  => __('Não foi possível excluir a imagem.', MLBS_TEXT_DOMAIN),
            'loadMore'          => __('Carregar mais', MLBS_TEXT_DOMAIN),
            'loadingMore'       => __('Carregando...', MLBS_TEXT_DOMAIN),
            'libraryLoadError'  => __('Não foi possível carregar mais imagens da biblioteca.', MLBS_TEXT_DOMAIN),
            'libraryEmpty'      => __('Nenhuma imagem encontrada na biblioteca do WordPress.', MLBS_TEXT_DOMAIN),
            'libraryEnd'        => __('Você chegou ao fim da biblioteca.', MLBS_TEXT_DOMAIN),
            'currentScreenId'   => $screen->id,
            'bannerListScreen'  => 'edit-' . MLBS_Post_Types::POST_TYPE,
            'navUrls'           => array(
                'dashboard'  => MLBS_Helpers::admin_url('mlbs-dashboard'),
                'banners'    => admin_url('edit.php?post_type=' . MLBS_Post_Types::POST_TYPE),
                'groups'     => admin_url('edit-tags.php?taxonomy=mlbs_group&post_type=' . MLBS_Post_Types::POST_TYPE),
                'statistics' => MLBS_Helpers::admin_url('mlbs-statistics'),
                'shortcodes' => MLBS_Helpers::admin_url('mlbs-shortcodes'),
                'settings'   => MLBS_Helpers::admin_url('mlbs-settings'),
                'license'    => MLBS_Helpers::admin_url('mlbs-license'),
            ),
            'navLabels'         => array(
                'dashboard'  => __('Dashboard', MLBS_TEXT_DOMAIN),
                'banners'    => __('Banners', MLBS_TEXT_DOMAIN),
                'groups'     => __('Grupos', MLBS_TEXT_DOMAIN),
                'statistics' => __('Estatísticas', MLBS_TEXT_DOMAIN),
                'shortcodes' => __('Shortcodes', MLBS_TEXT_DOMAIN),
                'settings'   => __('Configurações', MLBS_TEXT_DOMAIN),
                'license'    => __('Licença e Planos', MLBS_TEXT_DOMAIN),
            ),
            'shellEyebrow'      => __('Painel profissional', MLBS_TEXT_DOMAIN),
            'baseStableLabel'   => __('Base estável', MLBS_TEXT_DOMAIN),
            'shellBannerSubtitle' => __('Gestão operacional dos banners cadastrados, com ordem, tracking e atalhos rápidos.', MLBS_TEXT_DOMAIN),
            'shellGroupSubtitle'  => __('Gestão dos grupos que governam exibição, layout e shortcodes do front.', MLBS_TEXT_DOMAIN),
            'shellEditBannerSubtitle' => __('Edição rápida do banner com biblioteca do WordPress embutida, seleção visual e carregamento progressivo.', MLBS_TEXT_DOMAIN),
        ));
    }



    public function render_native_admin_shell()
    {
        if (! function_exists('get_current_screen')) {
            return;
        }

        $screen = get_current_screen();
        if (! $screen) {
            return;
        }

        $current = '';
        $subtitle = '';

        if ($screen->id === 'edit-' . MLBS_Post_Types::POST_TYPE) {
            $current = 'banners';
            $subtitle = __('Gestão operacional dos banners cadastrados, com ordem, tracking e atalhos rápidos.', MLBS_TEXT_DOMAIN);
        } elseif ($screen->id === MLBS_Post_Types::POST_TYPE) {
            $current = 'banners';
            $subtitle = __('Edição rápida do banner com biblioteca do WordPress embutida, seleção visual e carregamento progressivo.', MLBS_TEXT_DOMAIN);
        } elseif ($screen->id === 'edit-mlbs_group' || (($screen->taxonomy ?? '') === 'mlbs_group')) {
            $current = 'groups';
            $subtitle = __('Gestão dos grupos que governam exibição, layout e shortcodes do front.', MLBS_TEXT_DOMAIN);
        }

        if ($current === '') {
            return;
        }

        echo '<div class="notice notice-info mlbs-native-shell-notice">';
        echo '<div class="mlbs-native-shell">';
        echo '<div class="mlbs-shell-header">';
        echo '<div>';
        echo '<div class="mlbs-eyebrow">' . esc_html__('Painel profissional', MLBS_TEXT_DOMAIN) . '</div>';
        echo '<h1>' . esc_html__('ML Banner Suite', MLBS_TEXT_DOMAIN) . ' <span class="mlbs-version">v' . esc_html(MLBS_VERSION) . '</span></h1>';
        echo '<p class="mlbs-subtitle">' . esc_html($subtitle) . '</p>';
        echo '</div>';
        echo '<div class="mlbs-badges">';
        echo '<span class="mlbs-badge is-dark">GitHub Ready</span>';
        echo '<span class="mlbs-badge is-blue">PT-BR / EN / ES</span>';
        echo '<span class="mlbs-badge is-green">' . esc_html__('Base estável', MLBS_TEXT_DOMAIN) . '</span>';
        echo '</div>';
        echo '</div>';
        MLBS_Helpers::render_admin_nav($current);
        echo '</div>';
        echo '</div>';
    }

    public function enqueue_front_assets()
    {
        wp_register_style('mlbs-front', MLBS_PLUGIN_URL . 'assets/css/front.css', array(), MLBS_VERSION);
        wp_register_script('mlbs-front', MLBS_PLUGIN_URL . 'assets/js/front.js', array(), MLBS_VERSION, true);
        wp_localize_script('mlbs-front', 'mlbsFront', array(
            'pauseOnHover'   => (int) $this->settings->get('pause_on_hover', 1),
            'defaultGap'     => (int) $this->settings->get('banner_gap', 16),
            'autoplaySpeed'  => (int) $this->settings->get('autoplay_speed', 4000),
            'ajaxUrl'        => admin_url('admin-ajax.php'),
            'trackNonce'     => wp_create_nonce('mlbs_track_impression'),
        ));
    }
}
