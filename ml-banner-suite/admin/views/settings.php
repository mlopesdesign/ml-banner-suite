<?php
if (! defined('ABSPATH')) {
    exit;
}
$alignment_options = MLBS_Helpers::get_alignment_options();
?>
<div class="wrap mlbs-wrap">
    <div class="mlbs-shell-header">
        <div>
            <div class="mlbs-eyebrow"><?php echo esc_html__('Parâmetros do produto', MLBS_TEXT_DOMAIN); ?></div>
            <h1><?php echo esc_html__('Configurações', MLBS_TEXT_DOMAIN); ?> <span class="mlbs-version">v<?php echo esc_html(MLBS_VERSION); ?></span></h1>
            <p class="mlbs-subtitle"><?php echo esc_html__('Resumo administrativo dos defaults globais, pronto para evoluir para uma tela completa de opções.', MLBS_TEXT_DOMAIN); ?></p>
        </div>
        <div class="mlbs-badges">
            <span class="mlbs-badge is-dark"><?php echo esc_html__('Admin safe', MLBS_TEXT_DOMAIN); ?></span>
        </div>
    </div>

    <nav class="mlbs-tabs">
        <a class="mlbs-tab" href="<?php echo esc_url(MLBS_Helpers::admin_url('mlbs-dashboard')); ?>"><?php echo esc_html__('Dashboard', MLBS_TEXT_DOMAIN); ?></a>
        <a class="mlbs-tab" href="<?php echo esc_url(admin_url('edit.php?post_type=' . MLBS_Post_Types::POST_TYPE)); ?>"><?php echo esc_html__('Banners', MLBS_TEXT_DOMAIN); ?></a>
        <a class="mlbs-tab" href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=mlbs_group&post_type=' . MLBS_Post_Types::POST_TYPE)); ?>"><?php echo esc_html__('Grupos', MLBS_TEXT_DOMAIN); ?></a>
        <a class="mlbs-tab" href="<?php echo esc_url(MLBS_Helpers::admin_url('mlbs-statistics')); ?>"><?php echo esc_html__('Estatísticas', MLBS_TEXT_DOMAIN); ?></a>
        <a class="mlbs-tab" href="<?php echo esc_url(MLBS_Helpers::admin_url('mlbs-shortcodes')); ?>"><?php echo esc_html__('Shortcodes', MLBS_TEXT_DOMAIN); ?></a>
        <a class="mlbs-tab is-active" href="<?php echo esc_url(MLBS_Helpers::admin_url('mlbs-settings')); ?>"><?php echo esc_html__('Configurações', MLBS_TEXT_DOMAIN); ?></a>
        <a class="mlbs-tab" href="<?php echo esc_url(MLBS_Helpers::admin_url('mlbs-license')); ?>"><?php echo esc_html__('Licença e Planos', MLBS_TEXT_DOMAIN); ?></a>
    </nav>

    <div class="mlbs-premium-grid">
        <section class="mlbs-premium-card">
            <div class="mlbs-card-head"><h2><?php echo esc_html__('Geral', MLBS_TEXT_DOMAIN); ?></h2><span class="mlbs-pill is-soft">Core</span></div>
            <div class="mlbs-info-grid">
                <div><span><?php echo esc_html__('Assets condicionais', MLBS_TEXT_DOMAIN); ?></span><strong><?php echo esc_html(MLBS_Helpers::yes_no((bool) $settings['load_assets_conditionally'])); ?></strong></div>
                <div><span><?php echo esc_html__('Status padrão', MLBS_TEXT_DOMAIN); ?></span><strong><?php echo esc_html($settings['default_new_banner_status']); ?></strong></div>
                <div><span><?php echo esc_html__('Clones', MLBS_TEXT_DOMAIN); ?></span><strong><?php echo esc_html($settings['default_clone_status']); ?></strong></div>
                <div><span><?php echo esc_html__('Desinstalação limpa', MLBS_TEXT_DOMAIN); ?></span><strong><?php echo esc_html(MLBS_Helpers::yes_no((bool) $settings['remove_data_on_uninstall'])); ?></strong></div>
            </div>
        </section>

        <section class="mlbs-premium-card">
            <div class="mlbs-card-head"><h2><?php echo esc_html__('Exibição', MLBS_TEXT_DOMAIN); ?></h2><span class="mlbs-pill"><?php echo esc_html__('Front defaults', MLBS_TEXT_DOMAIN); ?></span></div>
            <div class="mlbs-info-grid">
                <div><span><?php echo esc_html__('Autoplay', MLBS_TEXT_DOMAIN); ?></span><strong><?php echo esc_html(MLBS_Helpers::yes_no((bool) $settings['autoplay_enabled'])); ?></strong></div>
                <div><span><?php echo esc_html__('Velocidade', MLBS_TEXT_DOMAIN); ?></span><strong><?php echo esc_html($settings['autoplay_speed']); ?> ms</strong></div>
                <div><span><?php echo esc_html__('Transição', MLBS_TEXT_DOMAIN); ?></span><strong><?php echo esc_html($settings['transition_speed']); ?> ms</strong></div>
                <div><span><?php echo esc_html__('Breakpoints', MLBS_TEXT_DOMAIN); ?></span><strong><?php echo esc_html($settings['default_desktop_items'] . ' / ' . $settings['default_tablet_items'] . ' / ' . $settings['default_mobile_items']); ?></strong></div>
                <div><span><?php echo esc_html__('Alinhamento', MLBS_TEXT_DOMAIN); ?></span><strong><?php echo esc_html($alignment_options[MLBS_Helpers::normalize_alignment($settings['default_alignment'])]); ?></strong></div>
            </div>
        </section>
    </div>
</div>
