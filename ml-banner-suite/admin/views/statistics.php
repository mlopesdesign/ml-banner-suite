<?php
if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap mlbs-wrap">
    <div class="mlbs-shell-header">
        <div>
            <div class="mlbs-eyebrow"><?php echo esc_html__('Medição e desempenho', MLBS_TEXT_DOMAIN); ?></div>
            <h1><?php echo esc_html__('Estatísticas', MLBS_TEXT_DOMAIN); ?> <span class="mlbs-version">v<?php echo esc_html(MLBS_VERSION); ?></span></h1>
            <p class="mlbs-subtitle"><?php echo esc_html__('Visão estrutural da base de tracking e do volume atual de banners publicados e ativos.', MLBS_TEXT_DOMAIN); ?></p>
        </div>
        <div class="mlbs-badges">
            <span class="mlbs-badge is-blue"><?php echo esc_html__('Tracking AJAX', MLBS_TEXT_DOMAIN); ?></span>
            <span class="mlbs-badge is-dark"><?php echo esc_html__('Admin only', MLBS_TEXT_DOMAIN); ?></span>
        </div>
    </div>

    <nav class="mlbs-tabs">
        <a class="mlbs-tab" href="<?php echo esc_url(MLBS_Helpers::admin_url('mlbs-dashboard')); ?>"><?php echo esc_html__('Dashboard', MLBS_TEXT_DOMAIN); ?></a>
        <a class="mlbs-tab" href="<?php echo esc_url(admin_url('edit.php?post_type=' . MLBS_Post_Types::POST_TYPE)); ?>"><?php echo esc_html__('Banners', MLBS_TEXT_DOMAIN); ?></a>
        <a class="mlbs-tab" href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=mlbs_group&post_type=' . MLBS_Post_Types::POST_TYPE)); ?>"><?php echo esc_html__('Grupos', MLBS_TEXT_DOMAIN); ?></a>
        <a class="mlbs-tab is-active" href="<?php echo esc_url(MLBS_Helpers::admin_url('mlbs-statistics')); ?>"><?php echo esc_html__('Estatísticas', MLBS_TEXT_DOMAIN); ?></a>
        <a class="mlbs-tab" href="<?php echo esc_url(MLBS_Helpers::admin_url('mlbs-shortcodes')); ?>"><?php echo esc_html__('Shortcodes', MLBS_TEXT_DOMAIN); ?></a>
        <a class="mlbs-tab" href="<?php echo esc_url(MLBS_Helpers::admin_url('mlbs-settings')); ?>"><?php echo esc_html__('Configurações', MLBS_TEXT_DOMAIN); ?></a>
        <a class="mlbs-tab" href="<?php echo esc_url(MLBS_Helpers::admin_url('mlbs-license')); ?>"><?php echo esc_html__('Licença e Planos', MLBS_TEXT_DOMAIN); ?></a>
    </nav>

    <div class="mlbs-kpi-grid mlbs-kpi-grid-compact">
        <article class="mlbs-kpi-card"><span class="mlbs-kpi-label"><?php echo esc_html__('Tabela pronta', MLBS_TEXT_DOMAIN); ?></span><strong class="mlbs-kpi-value"><?php echo esc_html($table_exists ? __('Sim', MLBS_TEXT_DOMAIN) : __('Não', MLBS_TEXT_DOMAIN)); ?></strong></article>
        <article class="mlbs-kpi-card"><span class="mlbs-kpi-label"><?php echo esc_html__('Banners publicados', MLBS_TEXT_DOMAIN); ?></span><strong class="mlbs-kpi-value"><?php echo esc_html((string) $banner_total); ?></strong></article>
        <article class="mlbs-kpi-card"><span class="mlbs-kpi-label"><?php echo esc_html__('Banners ativos', MLBS_TEXT_DOMAIN); ?></span><strong class="mlbs-kpi-value"><?php echo esc_html((string) $active_total); ?></strong></article>
    </div>

    <section class="mlbs-premium-card">
        <div class="mlbs-card-head">
            <h2><?php echo esc_html__('Leitura rápida', MLBS_TEXT_DOMAIN); ?></h2>
            <span class="mlbs-pill"><?php echo esc_html__('Base pronta', MLBS_TEXT_DOMAIN); ?></span>
        </div>
        <p><?php echo esc_html__('A tabela agregada e os contadores rápidos estão habilitados. Esta tela fica como resumo administrativo e já no padrão visual profissional do ecossistema ML.', MLBS_TEXT_DOMAIN); ?></p>
    </section>
</div>
