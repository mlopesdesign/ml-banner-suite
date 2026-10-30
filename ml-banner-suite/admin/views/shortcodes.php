<?php
if (! defined('ABSPATH')) {
    exit;
}
$groups = get_terms(array('taxonomy' => 'mlbs_group', 'hide_empty' => false));
?>
<div class="wrap mlbs-wrap">
    <div class="mlbs-shell-header">
        <div>
            <div class="mlbs-eyebrow"><?php echo esc_html__('Entrega para Nicepage e WordPress', MLBS_TEXT_DOMAIN); ?></div>
            <h1><?php echo esc_html__('Shortcodes', MLBS_TEXT_DOMAIN); ?> <span class="mlbs-version">v<?php echo esc_html(MLBS_VERSION); ?></span></h1>
            <p class="mlbs-subtitle"><?php echo esc_html__('Todos os atalhos do plugin em formato comercial, limpo e pronto para copiar.', MLBS_TEXT_DOMAIN); ?></p>
        </div>
        <div class="mlbs-badges">
            <span class="mlbs-badge is-green"><?php echo esc_html__('Pronto para copiar', MLBS_TEXT_DOMAIN); ?></span>
        </div>
    </div>

    <?php MLBS_Helpers::render_admin_nav('shortcodes'); ?>

    <section class="mlbs-premium-card">
        <div class="mlbs-card-head"><h2><?php echo esc_html__('Shortcode mestre', MLBS_TEXT_DOMAIN); ?></h2><span class="mlbs-pill is-soft"><?php echo esc_html__('Base', MLBS_TEXT_DOMAIN); ?></span></div>
        <div class="mlbs-code-stack">
            <code>[ml_banner_suite]</code>
            <code>[ml_banner_suite id="123"]</code>
            <code>[ml_banner_suite group="home-topo"]</code>
            <code>[ml_banner_suite group="home-topo" type="carousel" desktop="4" tablet="2" mobile="1"]</code>
        </div>
    </section>

    <section class="mlbs-premium-card">
        <div class="mlbs-card-head"><h2><?php echo esc_html__('Shortcodes por grupo', MLBS_TEXT_DOMAIN); ?></h2><span class="mlbs-pill"><?php echo esc_html__('Operação rápida', MLBS_TEXT_DOMAIN); ?></span></div>
        <table class="widefat striped mlbs-shortcode-table">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Grupo', MLBS_TEXT_DOMAIN); ?></th>
                    <th><?php echo esc_html__('Slug', MLBS_TEXT_DOMAIN); ?></th>
                    <th><?php echo esc_html__('Padrão', MLBS_TEXT_DOMAIN); ?></th>
                    <th><?php echo esc_html__('Slideshow', MLBS_TEXT_DOMAIN); ?></th>
                    <th><?php echo esc_html__('Carrossel', MLBS_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (! empty($groups) && ! is_wp_error($groups)) : ?>
                    <?php foreach ($groups as $group) : ?>
                        <tr>
                            <td><?php echo esc_html($group->name); ?></td>
                            <td><code><?php echo esc_html($group->slug); ?></code></td>
                            <td><code><?php echo esc_html(MLBS_Helpers::get_group_shortcode($group->slug)); ?></code></td>
                            <td><code><?php echo esc_html(MLBS_Helpers::get_group_shortcode($group->slug, 'slideshow')); ?></code></td>
                            <td><code><?php echo esc_html(MLBS_Helpers::get_group_shortcode($group->slug, 'carousel')); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="5"><?php echo esc_html__('Nenhum grupo cadastrado ainda.', MLBS_TEXT_DOMAIN); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</div>
