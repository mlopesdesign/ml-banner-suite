<?php
if (! defined('ABSPATH')) {
    exit;
}

$notice = isset($_GET['mlbs_notice']) ? sanitize_text_field(wp_unslash($_GET['mlbs_notice'])) : '';
$notice_type = isset($_GET['mlbs_notice_type']) ? sanitize_key(wp_unslash($_GET['mlbs_notice_type'])) : 'success';
$plan_label = $plan_label ?? (! empty($license_state['plan']) ? $license_state['plan'] : 'Free');
$status_label = $status_label ?? (! empty($license_state['status']) ? $license_state['status'] : 'free');
$days_left = $license_state['days_left'] !== '' && $license_state['days_left'] !== null ? (int) $license_state['days_left'] : null;
$license_source = ! empty($license_state['license_source']) ? $license_state['license_source'] : 'free';
$premium_enabled = ! empty($license_state['premium']);
$trial_enabled = in_array($license_state['status'], array('trial_active', 'trial_expired'), true);
$badge_class = $premium_enabled ? 'is-green' : 'is-blue';
?>
<div class="wrap mlbs-wrap">
    <div class="mlbs-shell-header">
        <div>
            <div class="mlbs-eyebrow"><?php echo esc_html__('Painel profissional', MLBS_TEXT_DOMAIN); ?></div>
            <h1><?php echo esc_html__('ML Banner Suite', MLBS_TEXT_DOMAIN); ?> <span class="mlbs-version">v<?php echo esc_html(MLBS_VERSION); ?></span></h1>
            <p class="mlbs-subtitle"><?php echo esc_html__('Licenciamento conectado ao ML License Hub com Trial, Free, Full e Vitalício no mesmo fluxo comercial.', MLBS_TEXT_DOMAIN); ?></p>
        </div>
        <div class="mlbs-badges">
            <span class="mlbs-badge is-dark">GitHub Ready</span>
            <span class="mlbs-badge is-blue">PT-BR / EN / ES</span>
            <span class="mlbs-badge <?php echo esc_attr($badge_class); ?>"><?php echo esc_html($plan_label); ?></span>
        </div>
    </div>

    <nav class="mlbs-tabs">
        <a class="mlbs-tab" href="<?php echo esc_url(MLBS_Helpers::admin_url('mlbs-dashboard')); ?>"><?php echo esc_html__('Dashboard', MLBS_TEXT_DOMAIN); ?></a>
        <a class="mlbs-tab" href="<?php echo esc_url(admin_url('edit.php?post_type=' . MLBS_Post_Types::POST_TYPE)); ?>"><?php echo esc_html__('Banners', MLBS_TEXT_DOMAIN); ?></a>
        <a class="mlbs-tab" href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=mlbs_group&post_type=' . MLBS_Post_Types::POST_TYPE)); ?>"><?php echo esc_html__('Grupos', MLBS_TEXT_DOMAIN); ?></a>
        <a class="mlbs-tab" href="<?php echo esc_url(MLBS_Helpers::admin_url('mlbs-statistics')); ?>"><?php echo esc_html__('Estatísticas', MLBS_TEXT_DOMAIN); ?></a>
        <a class="mlbs-tab" href="<?php echo esc_url(MLBS_Helpers::admin_url('mlbs-shortcodes')); ?>"><?php echo esc_html__('Shortcodes', MLBS_TEXT_DOMAIN); ?></a>
        <a class="mlbs-tab" href="<?php echo esc_url(MLBS_Helpers::admin_url('mlbs-settings')); ?>"><?php echo esc_html__('Configurações', MLBS_TEXT_DOMAIN); ?></a>
        <a class="mlbs-tab is-active" href="<?php echo esc_url(MLBS_Helpers::admin_url('mlbs-license')); ?>"><?php echo esc_html__('Licença e Planos', MLBS_TEXT_DOMAIN); ?></a>
    </nav>

    <?php if ($notice) : ?>
        <div class="notice notice-<?php echo esc_attr($notice_type === 'error' ? 'error' : 'success'); ?> is-dismissible"><p><?php echo esc_html($notice); ?></p></div>
    <?php endif; ?>

    <div class="mlbs-kpi-grid">
        <div class="mlbs-kpi-card">
            <span class="mlbs-kpi-label"><?php echo esc_html__('Plano atual', MLBS_TEXT_DOMAIN); ?></span>
            <strong class="mlbs-kpi-value"><?php echo esc_html($plan_label); ?></strong>
        </div>
        <div class="mlbs-kpi-card">
            <span class="mlbs-kpi-label"><?php echo esc_html__('Status', MLBS_TEXT_DOMAIN); ?></span>
            <strong class="mlbs-kpi-value"><?php echo esc_html($status_label); ?></strong>
        </div>
        <div class="mlbs-kpi-card">
            <span class="mlbs-kpi-label"><?php echo esc_html__('Premium', MLBS_TEXT_DOMAIN); ?></span>
            <strong class="mlbs-kpi-value"><?php echo esc_html($premium_enabled ? __('Liberado', MLBS_TEXT_DOMAIN) : __('Free', MLBS_TEXT_DOMAIN)); ?></strong>
        </div>
        <div class="mlbs-kpi-card">
            <span class="mlbs-kpi-label"><?php echo esc_html__('Dias restantes', MLBS_TEXT_DOMAIN); ?></span>
            <strong class="mlbs-kpi-value"><?php echo esc_html($days_left !== null ? $days_left : '—'); ?></strong>
        </div>
    </div>

    <div class="mlbs-license-layout">
        <section class="mlbs-premium-card mlbs-license-card">
            <div class="mlbs-card-head">
                <h2><?php echo esc_html__('Licença', MLBS_TEXT_DOMAIN); ?></h2>
                <span class="mlbs-pill is-soft"><?php echo esc_html($status_label); ?></span>
            </div>
            <p class="mlbs-license-copy"><?php echo esc_html__('Ative uma licença Full ou Vitalícia, ou sincronize o site com o Hub para receber Trial de 30 dias e depois degradar com elegância para Free.', MLBS_TEXT_DOMAIN); ?></p>

            <div class="mlbs-license-meta">
                <div><span><?php echo esc_html__('Produto', MLBS_TEXT_DOMAIN); ?></span><strong><?php echo esc_html(! empty($license_state['product_name']) ? $license_state['product_name'] : $license_product_name); ?></strong></div>
                <div><span><?php echo esc_html__('Product ID', MLBS_TEXT_DOMAIN); ?></span><strong><?php echo esc_html($license_product_id); ?></strong></div>
                <div><span><?php echo esc_html__('Domínio atual', MLBS_TEXT_DOMAIN); ?></span><strong><?php echo esc_html($license_domain); ?></strong></div>
                <div><span><?php echo esc_html__('Site fingerprint', MLBS_TEXT_DOMAIN); ?></span><strong style="word-break:break-all"><?php echo esc_html($site_fingerprint); ?></strong></div>
                <div><span><?php echo esc_html__('Origem', MLBS_TEXT_DOMAIN); ?></span><strong><?php echo esc_html($license_source); ?></strong></div>
                <div><span><?php echo esc_html__('Mensagem', MLBS_TEXT_DOMAIN); ?></span><strong><?php echo esc_html($license_state['message']); ?></strong></div>
                <div><span><?php echo esc_html__('Última validação', MLBS_TEXT_DOMAIN); ?></span><strong><?php echo esc_html($license_state['last_validated_at'] ? $license_state['last_validated_at'] : '—'); ?></strong></div>
                <div><span><?php echo esc_html__('Expira em', MLBS_TEXT_DOMAIN); ?></span><strong><?php echo esc_html($license_state['expires_at'] ? $license_state['expires_at'] : '—'); ?></strong></div>
            </div>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="mlbs-license-form">
                <?php wp_nonce_field('mlbs_activate_license'); ?>
                <input type="hidden" name="action" value="mlbs_activate_license">
                <div class="mlbs-field-grid mlbs-field-grid-2">
                    <label class="mlbs-field">
                        <span><?php echo esc_html__('Servidor de licença', MLBS_TEXT_DOMAIN); ?></span>
                        <input type="url" name="license_server_url" value="<?php echo esc_attr($license_server_url); ?>" placeholder="https://license.mlopesdesign.com.br/api/license.php">
                    </label>
                    <label class="mlbs-field">
                        <span><?php echo esc_html__('Product ID', MLBS_TEXT_DOMAIN); ?></span>
                        <input type="text" name="license_product_id" value="<?php echo esc_attr($license_product_id); ?>" placeholder="ml-banner-suite">
                    </label>
                </div>

                <label class="mlbs-field mlbs-field-full">
                    <span><?php echo esc_html__('Serial / License Key', MLBS_TEXT_DOMAIN); ?></span>
                    <input type="text" name="license_key" value="<?php echo esc_attr($license_state['license_key']); ?>" placeholder="MLI-XXXXX-XXXXX-XXXXX-XXXXX">
                </label>

                <div class="mlbs-license-actions" style="display:flex;gap:10px;flex-wrap:wrap;">
                    <button type="submit" class="button button-primary"><?php echo esc_html__('Ativar licença', MLBS_TEXT_DOMAIN); ?></button>
                </div>
            </form>

            <div class="mlbs-license-inline-actions" style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px;">
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('mlbs_sync_license'); ?>
                    <input type="hidden" name="action" value="mlbs_sync_license">
                    <input type="hidden" name="license_server_url" value="<?php echo esc_attr($license_server_url); ?>">
                    <input type="hidden" name="license_product_id" value="<?php echo esc_attr($license_product_id); ?>">
                    <input type="hidden" name="license_key" value="<?php echo esc_attr($license_state['license_key']); ?>">
                    <button type="submit" class="button"><?php echo esc_html($license_state['license_key'] !== '' ? __('Validar / sincronizar status', MLBS_TEXT_DOMAIN) : __('Iniciar / sincronizar Trial', MLBS_TEXT_DOMAIN)); ?></button>
                </form>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('mlbs_remove_license'); ?>
                    <input type="hidden" name="action" value="mlbs_remove_license">
                    <button type="submit" class="button"><?php echo esc_html__('Remover licença deste site', MLBS_TEXT_DOMAIN); ?></button>
                </form>
            </div>
        </section>

        <section class="mlbs-premium-card mlbs-license-plans">
            <div class="mlbs-card-head">
                <h2><?php echo esc_html__('Planos', MLBS_TEXT_DOMAIN); ?></h2>
                <span class="mlbs-pill is-soft"><?php echo esc_html__('Free / Trial / Full / Vitalício', MLBS_TEXT_DOMAIN); ?></span>
            </div>
            <table class="widefat striped mlbs-plan-table">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Recurso', MLBS_TEXT_DOMAIN); ?></th>
                        <th><?php echo esc_html__('Free', MLBS_TEXT_DOMAIN); ?></th>
                        <th><?php echo esc_html__('Trial', MLBS_TEXT_DOMAIN); ?></th>
                        <th><?php echo esc_html__('Full / Vitalício', MLBS_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td><?php echo esc_html__('Cadastro e exibição de banners', MLBS_TEXT_DOMAIN); ?></td><td><?php echo esc_html__('Sim', MLBS_TEXT_DOMAIN); ?></td><td><?php echo esc_html__('Sim', MLBS_TEXT_DOMAIN); ?></td><td><?php echo esc_html__('Sim', MLBS_TEXT_DOMAIN); ?></td></tr>
                    <tr><td><?php echo esc_html__('Slideshow, carrossel e grid', MLBS_TEXT_DOMAIN); ?></td><td><?php echo esc_html__('Sim', MLBS_TEXT_DOMAIN); ?></td><td><?php echo esc_html__('Sim', MLBS_TEXT_DOMAIN); ?></td><td><?php echo esc_html__('Sim', MLBS_TEXT_DOMAIN); ?></td></tr>
                    <tr><td><?php echo esc_html__('Tracking básico', MLBS_TEXT_DOMAIN); ?></td><td><?php echo esc_html__('Sim', MLBS_TEXT_DOMAIN); ?></td><td><?php echo esc_html__('Sim', MLBS_TEXT_DOMAIN); ?></td><td><?php echo esc_html__('Sim', MLBS_TEXT_DOMAIN); ?></td></tr>
                    <tr><td><?php echo esc_html__('Janela Full de avaliação', MLBS_TEXT_DOMAIN); ?></td><td><?php echo esc_html__('Não', MLBS_TEXT_DOMAIN); ?></td><td><?php echo esc_html__('30 dias', MLBS_TEXT_DOMAIN); ?></td><td><?php echo esc_html__('—', MLBS_TEXT_DOMAIN); ?></td></tr>
                    <tr><td><?php echo esc_html__('Premium liberado', MLBS_TEXT_DOMAIN); ?></td><td><?php echo esc_html__('Não', MLBS_TEXT_DOMAIN); ?></td><td><?php echo esc_html__('Sim', MLBS_TEXT_DOMAIN); ?></td><td><?php echo esc_html__('Sim', MLBS_TEXT_DOMAIN); ?></td></tr>
                    <tr><td><?php echo esc_html__('Degradação elegante para Free', MLBS_TEXT_DOMAIN); ?></td><td><?php echo esc_html__('—', MLBS_TEXT_DOMAIN); ?></td><td><?php echo esc_html__('Sim', MLBS_TEXT_DOMAIN); ?></td><td><?php echo esc_html__('—', MLBS_TEXT_DOMAIN); ?></td></tr>
                </tbody>
            </table>
            <p class="mlbs-license-note"><?php echo esc_html__('O Trial é controlado no servidor de licença. Reinstalar o plugin não concede um novo período no mesmo site.', MLBS_TEXT_DOMAIN); ?></p>

            <div class="mlbs-license-cta" style="margin-top:18px;display:flex;gap:10px;flex-wrap:wrap;">
                <a class="button button-primary" href="<?php echo esc_url(MLBS_PURCHASE_URL); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__('Assinar / Comprar agora', MLBS_TEXT_DOMAIN); ?></a>
                <a class="button" href="<?php echo esc_url(MLBS_WEBSITE_URL); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__('Abrir site oficial', MLBS_TEXT_DOMAIN); ?></a>
            </div>
        </section>
    </div>
</div>
