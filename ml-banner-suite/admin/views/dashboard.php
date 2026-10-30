<?php
if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap mlbs-wrap">
    <div class="mlbs-shell-header">
        <div>
            <div class="mlbs-eyebrow"><?php echo esc_html__('Painel profissional', MLBS_TEXT_DOMAIN); ?></div>
            <h1><?php echo esc_html__('ML Banner Suite', MLBS_TEXT_DOMAIN); ?> <span class="mlbs-version">v<?php echo esc_html(MLBS_VERSION); ?></span></h1>
            <p class="mlbs-subtitle"><?php echo esc_html__('Gestão comercial de banners com grupos, tracking, slideshow, carrossel e controle visual pronto para distribuição.', MLBS_TEXT_DOMAIN); ?></p>
        </div>
        <div class="mlbs-badges">
            <span class="mlbs-badge is-dark">GitHub Ready</span>
            <span class="mlbs-badge is-blue">PT-BR / EN / ES</span>
            <span class="mlbs-badge is-green"><?php echo esc_html__('Base estável', MLBS_TEXT_DOMAIN); ?></span>
        </div>
    </div>

    <?php MLBS_Helpers::render_admin_nav('dashboard'); ?>

    <div class="mlbs-kpi-grid">
        <article class="mlbs-kpi-card">
            <span class="mlbs-kpi-label"><?php echo esc_html__('Banners publicados', MLBS_TEXT_DOMAIN); ?></span>
            <strong class="mlbs-kpi-value"><?php echo esc_html((string) $published_total); ?></strong>
        </article>
        <article class="mlbs-kpi-card">
            <span class="mlbs-kpi-label"><?php echo esc_html__('Banners ativos', MLBS_TEXT_DOMAIN); ?></span>
            <strong class="mlbs-kpi-value"><?php echo esc_html((string) $active_total); ?></strong>
        </article>
        <article class="mlbs-kpi-card">
            <span class="mlbs-kpi-label"><?php echo esc_html__('Grupos', MLBS_TEXT_DOMAIN); ?></span>
            <strong class="mlbs-kpi-value"><?php echo esc_html((string) ((int) $group_count)); ?></strong>
        </article>
        <article class="mlbs-kpi-card">
            <span class="mlbs-kpi-label"><?php echo esc_html__('Shortcodes úteis', MLBS_TEXT_DOMAIN); ?></span>
            <strong class="mlbs-kpi-value"><?php echo esc_html((string) $shortcode_total); ?></strong>
        </article>
    </div>

    <div class="mlbs-premium-grid">
        <section class="mlbs-premium-card">
            <div class="mlbs-card-head">
                <h2><?php echo esc_html__('Resumo da base atual', MLBS_TEXT_DOMAIN); ?></h2>
                <span class="mlbs-pill"><?php echo esc_html__('Produção', MLBS_TEXT_DOMAIN); ?></span>
            </div>
            <ul class="mlbs-check-list">
                <li><?php echo esc_html__('Grupos governam a exibição do front.', MLBS_TEXT_DOMAIN); ?></li>
                <li><?php echo esc_html__('Tracking de impressões e cliques ativo.', MLBS_TEXT_DOMAIN); ?></li>
                <li><?php echo esc_html__('Leitura de GIF em milissegundos no editor.', MLBS_TEXT_DOMAIN); ?></li>
                <li><?php echo esc_html__('Responsive do banner isolado do template.', MLBS_TEXT_DOMAIN); ?></li>
            </ul>
        </section>

        <section class="mlbs-premium-card">
            <div class="mlbs-card-head">
                <h2><?php echo esc_html__('Configuração global', MLBS_TEXT_DOMAIN); ?></h2>
                <span class="mlbs-pill is-soft"><?php echo esc_html__('Defaults', MLBS_TEXT_DOMAIN); ?></span>
            </div>
            <div class="mlbs-info-grid">
                <div><span><?php echo esc_html__('Autoplay', MLBS_TEXT_DOMAIN); ?></span><strong><?php echo esc_html(MLBS_Helpers::yes_no((bool) $settings['autoplay_enabled'])); ?></strong></div>
                <div><span><?php echo esc_html__('Hover pause', MLBS_TEXT_DOMAIN); ?></span><strong><?php echo esc_html(MLBS_Helpers::yes_no((bool) $settings['pause_on_hover'])); ?></strong></div>
                <div><span><?php echo esc_html__('Breakpoints', MLBS_TEXT_DOMAIN); ?></span><strong><?php echo esc_html($settings['default_desktop_items'] . ' / ' . $settings['default_tablet_items'] . ' / ' . $settings['default_mobile_items']); ?></strong></div>
                <div><span><?php echo esc_html__('Status de assets', MLBS_TEXT_DOMAIN); ?></span><strong><?php echo esc_html__('Admin only', MLBS_TEXT_DOMAIN); ?></strong></div>
            </div>
        </section>
    

        <section class="mlbs-premium-card">
            <div class="mlbs-card-head">
                <h2><?php echo esc_html__('Ambiente e requisitos', MLBS_TEXT_DOMAIN); ?></h2>
                <span class="mlbs-pill is-soft"><?php echo esc_html__('Compatibilidade', MLBS_TEXT_DOMAIN); ?></span>
            </div>
            <div class="mlbs-env-grid">
                <?php foreach ($environment_items as $item) : ?>
                    <div class="mlbs-env-item">
                        <span class="mlbs-env-label"><?php echo esc_html($item['label']); ?></span>
                        <strong class="mlbs-env-value"><?php echo esc_html($item['value'] !== '' ? $item['value'] : __('Não detectado', MLBS_TEXT_DOMAIN)); ?></strong>
                        <em class="mlbs-env-status"><?php echo esc_html($item['status']); ?></em>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

    </div>
</div>
