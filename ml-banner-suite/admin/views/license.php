<?php
if (! defined('ABSPATH')) {
    exit;
}

$notice = isset($_GET['mlbs_notice']) ? sanitize_text_field(wp_unslash($_GET['mlbs_notice'])) : '';
$notice_type = isset($_GET['mlbs_notice_type']) ? sanitize_key(wp_unslash($_GET['mlbs_notice_type'])) : 'success';
$tone_class = array('full' => 'is-green', 'trial' => 'is-blue', 'free' => 'is-amber');
$badge_class = $tone_class[$license['tone']] ?? 'is-blue';
$days_left = $license['days_left'];
$post_url = esc_url(admin_url('admin-post.php'));
$is_lifetime = 'Vitalícia' === (string) $license['plan_label'];
if ($license['active'] && ! $is_lifetime && $license['expires_label']) {
    $valid_until = $license['expires_label'];
} else {
    $valid_until = $license['active'] ? __('Sem vencimento', MLBS_TEXT_DOMAIN) : '—';
}
$days_value = ($license['active'] && null !== $days_left && $license['expires_label']) ? (string) (int) $days_left : '—';
?>
<div class="wrap mlbs-wrap">
    <div class="mlbs-shell-header">
        <div>
            <div class="mlbs-eyebrow"><?php echo esc_html__('Painel profissional', MLBS_TEXT_DOMAIN); ?></div>
            <h1><?php echo esc_html__('ML Banner Suite', MLBS_TEXT_DOMAIN); ?> <span class="mlbs-version">v<?php echo esc_html(MLBS_VERSION); ?></span></h1>
            <p class="mlbs-subtitle"><?php echo esc_html__('Licença pelo site tools.mlopesdesign.com.br: teste grátis de 30 dias por site, licença anual ou Pacote Agência.', MLBS_TEXT_DOMAIN); ?></p>
        </div>
        <div class="mlbs-badges">
            <span class="mlbs-badge is-dark">GitHub Ready</span>
            <span class="mlbs-badge is-blue">PT-BR / EN / ES</span>
            <span class="mlbs-badge <?php echo esc_attr($badge_class); ?>"><?php echo esc_html($license['label']); ?></span>
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
        <div class="notice notice-<?php echo esc_attr($notice_type === 'error' ? 'error' : 'success'); ?> mlbs-license-notice is-dismissible"><p><?php echo esc_html($notice); ?></p></div>
    <?php endif; ?>

    <div class="mlbs-kpi-grid">
        <div class="mlbs-kpi-card">
            <span class="mlbs-kpi-label"><?php echo esc_html__('Situação', MLBS_TEXT_DOMAIN); ?></span>
            <strong class="mlbs-kpi-value"><?php echo esc_html($license['label']); ?></strong>
        </div>
        <div class="mlbs-kpi-card">
            <span class="mlbs-kpi-label"><?php echo esc_html__('Plano', MLBS_TEXT_DOMAIN); ?></span>
            <strong class="mlbs-kpi-value"><?php echo esc_html($license['plan_label']); ?></strong>
        </div>
        <div class="mlbs-kpi-card">
            <span class="mlbs-kpi-label"><?php echo esc_html__('Válida até', MLBS_TEXT_DOMAIN); ?></span>
            <strong class="mlbs-kpi-value"><?php echo esc_html($valid_until); ?></strong>
        </div>
        <div class="mlbs-kpi-card">
            <span class="mlbs-kpi-label"><?php echo esc_html__('Dias restantes', MLBS_TEXT_DOMAIN); ?></span>
            <strong class="mlbs-kpi-value"><?php echo esc_html($days_value); ?></strong>
        </div>
    </div>

    <div class="mlbs-license-layout">
        <section class="mlbs-premium-card mlbs-license-card">
            <div class="mlbs-card-head">
                <h2><?php echo esc_html__('Licença', MLBS_TEXT_DOMAIN); ?></h2>
                <span class="mlbs-pill is-soft"><?php echo esc_html($license['domain']); ?></span>
            </div>
            <p class="mlbs-license-copy"><?php echo esc_html($license['message']); ?></p>

            <?php if ('license' === $license['mode'] && '' !== $license['key_masked']) : ?>
                <div class="mlbs-license-meta">
                    <div><span><?php echo esc_html__('Chave neste site', MLBS_TEXT_DOMAIN); ?></span><strong><?php echo esc_html($license['key_masked']); ?></strong></div>
                </div>
                <div class="mlbs-license-inline-actions">
                    <form method="post" action="<?php echo $post_url; ?>">
                        <?php wp_nonce_field('mlbs_sync_license'); ?>
                        <input type="hidden" name="action" value="mlbs_sync_license">
                        <input type="hidden" name="mlbs_op" value="check">
                        <button type="submit" class="button button-primary"><?php echo esc_html__('Conferir agora', MLBS_TEXT_DOMAIN); ?></button>
                    </form>
                    <form method="post" action="<?php echo $post_url; ?>">
                        <?php wp_nonce_field('mlbs_remove_license'); ?>
                        <input type="hidden" name="action" value="mlbs_remove_license">
                        <button type="submit" class="button"><?php echo esc_html__('Remover deste site', MLBS_TEXT_DOMAIN); ?></button>
                    </form>
                    <a class="button" href="<?php echo esc_url(MLBS_License_Manager::ACCOUNT_URL); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__('Minha conta', MLBS_TEXT_DOMAIN); ?></a>
                </div>
            <?php else : ?>
                <form method="post" action="<?php echo $post_url; ?>" class="mlbs-license-form">
                    <?php wp_nonce_field('mlbs_activate_license'); ?>
                    <input type="hidden" name="action" value="mlbs_activate_license">
                    <label class="mlbs-field mlbs-field-full">
                        <span><?php echo esc_html__('Chave da licença', MLBS_TEXT_DOMAIN); ?></span>
                        <input type="text" name="license_key" value="" placeholder="<?php echo esc_attr__('Cole aqui a sua chave', MLBS_TEXT_DOMAIN); ?>" autocomplete="off" spellcheck="false">
                    </label>
                    <div class="mlbs-license-actions">
                        <button type="submit" class="button button-primary"><?php echo esc_html__('Ativar licença', MLBS_TEXT_DOMAIN); ?></button>
                    </div>
                </form>
                <div class="mlbs-license-inline-actions">
                    <?php if ($license['can_start_trial']) : ?>
                        <form method="post" action="<?php echo $post_url; ?>">
                            <?php wp_nonce_field('mlbs_sync_license'); ?>
                            <input type="hidden" name="action" value="mlbs_sync_license">
                            <input type="hidden" name="mlbs_op" value="trial">
                            <button type="submit" class="button"><?php echo esc_html__('Começar teste grátis de 30 dias', MLBS_TEXT_DOMAIN); ?></button>
                        </form>
                    <?php elseif ($license['is_trial']) : ?>
                        <form method="post" action="<?php echo $post_url; ?>">
                            <?php wp_nonce_field('mlbs_sync_license'); ?>
                            <input type="hidden" name="action" value="mlbs_sync_license">
                            <input type="hidden" name="mlbs_op" value="check">
                            <button type="submit" class="button"><?php echo esc_html__('Conferir agora', MLBS_TEXT_DOMAIN); ?></button>
                        </form>
                    <?php endif; ?>
                    <a class="button" href="<?php echo esc_url(MLBS_License_Manager::BUY_URL); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__('Comprar licença', MLBS_TEXT_DOMAIN); ?></a>
                    <a class="button" href="<?php echo esc_url(MLBS_License_Manager::ACCOUNT_URL); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__('Minha conta', MLBS_TEXT_DOMAIN); ?></a>
                </div>
            <?php endif; ?>
        </section>

        <section class="mlbs-premium-card mlbs-license-plans">
            <div class="mlbs-card-head">
                <h2><?php echo esc_html__('Como funciona', MLBS_TEXT_DOMAIN); ?></h2>
                <span class="mlbs-pill is-soft">tools.mlopesdesign.com.br</span>
            </div>
            <table class="widefat striped mlbs-plan-table">
                <tbody>
                    <tr><td><strong><?php echo esc_html__('Teste grátis', MLBS_TEXT_DOMAIN); ?></strong></td><td><?php echo esc_html__('30 dias com tudo liberado, uma vez por site. Reinstalar não reinicia.', MLBS_TEXT_DOMAIN); ?></td></tr>
                    <tr><td><strong><?php echo esc_html__('Licença anual', MLBS_TEXT_DOMAIN); ?></strong></td><td><?php echo esc_html__('Vale em 1 site. Para usar em outro, remova deste site.', MLBS_TEXT_DOMAIN); ?></td></tr>
                    <tr><td><strong><?php echo esc_html__('Pacote Agência', MLBS_TEXT_DOMAIN); ?></strong></td><td><?php echo esc_html__('Uma chave para todos os plugins ML em 5, 10 ou 50 sites.', MLBS_TEXT_DOMAIN); ?></td></tr>
                    <tr><td><strong><?php echo esc_html__('Sem licença', MLBS_TEXT_DOMAIN); ?></strong></td><td><?php echo esc_html__('Os banners e anúncios publicados continuam no ar. Só o painel fica bloqueado para criar, editar, apagar e salvar.', MLBS_TEXT_DOMAIN); ?></td></tr>
                </tbody>
            </table>
            <p class="mlbs-license-note"><?php echo esc_html__('A licença é conferida uma vez por dia. Sem internet, o site continua liberado por até 7 dias.', MLBS_TEXT_DOMAIN); ?></p>
        </section>
    </div>
</div>
