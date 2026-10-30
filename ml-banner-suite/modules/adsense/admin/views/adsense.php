<?php
if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap mlbs-wrap mlbs-adsense-admin-wrap">
    <div class="mlbs-shell-header">
        <div>
            <div class="mlbs-eyebrow"><?php echo esc_html__('Monetização integrada', MLBS_TEXT_DOMAIN); ?></div>
            <h1><?php echo esc_html__('Google AdSense', MLBS_TEXT_DOMAIN); ?> <span class="mlbs-version">v<?php echo esc_html(MLBS_VERSION); ?></span></h1>
            <p class="mlbs-subtitle"><?php echo esc_html__('Módulo isolado para Auto Ads, unidades manuais, inserção editorial e ads.txt, sem interferir no motor de banners ou no atualizador pelo GitHub.', MLBS_TEXT_DOMAIN); ?></p>
        </div>
        <div class="mlbs-badges">
            <span class="mlbs-badge is-dark"><?php echo esc_html__('Módulo isolado', MLBS_TEXT_DOMAIN); ?></span>
            <span class="mlbs-badge is-green"><?php echo esc_html__('Sem rotação automática', MLBS_TEXT_DOMAIN); ?></span>
        </div>
    </div>

    <?php MLBS_Helpers::render_admin_nav('adsense'); ?>

    <?php if ($status === 'saved') : ?>
        <div class="mlbs-adsense-notice is-success"><strong><?php echo esc_html__('Configurações salvas.', MLBS_TEXT_DOMAIN); ?></strong> <?php echo esc_html__('O módulo foi atualizado sem alterar os banners existentes.', MLBS_TEXT_DOMAIN); ?></div>
    <?php elseif ($status === 'invalid-publisher') : ?>
        <div class="mlbs-adsense-notice is-warning"><strong><?php echo esc_html__('Configuração salva, mas o módulo permaneceu sem veicular anúncios.', MLBS_TEXT_DOMAIN); ?></strong> <?php echo esc_html__('Informe um Publisher ID válido no formato ca-pub-0000000000000000.', MLBS_TEXT_DOMAIN); ?></div>
    <?php endif; ?>

    <div class="mlbs-kpi-grid mlbs-kpi-grid-compact mlbs-adsense-kpis">
        <article class="mlbs-kpi-card">
            <span class="mlbs-kpi-label"><?php echo esc_html__('Módulo', MLBS_TEXT_DOMAIN); ?></span>
            <strong class="mlbs-kpi-value"><?php echo ! empty($settings['enabled']) ? esc_html__('Ativo', MLBS_TEXT_DOMAIN) : esc_html__('Inativo', MLBS_TEXT_DOMAIN); ?></strong>
        </article>
        <article class="mlbs-kpi-card">
            <span class="mlbs-kpi-label"><?php echo esc_html__('Publisher ID', MLBS_TEXT_DOMAIN); ?></span>
            <strong class="mlbs-kpi-value is-text"><?php echo $publisher_valid ? esc_html__('Validado', MLBS_TEXT_DOMAIN) : esc_html__('Pendente', MLBS_TEXT_DOMAIN); ?></strong>
        </article>
        <article class="mlbs-kpi-card">
            <span class="mlbs-kpi-label"><?php echo esc_html__('Unidades ativas', MLBS_TEXT_DOMAIN); ?></span>
            <strong class="mlbs-kpi-value"><?php echo esc_html((string) count($active_units)); ?></strong>
        </article>
    </div>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="mlbs-adsense-form">
        <input type="hidden" name="action" value="mlbs_save_adsense_settings">
        <?php wp_nonce_field('mlbs_save_adsense_settings', 'mlbs_adsense_nonce'); ?>

        <section class="mlbs-premium-card mlbs-adsense-section">
            <div class="mlbs-card-head">
                <div>
                    <h2><?php echo esc_html__('Configuração principal', MLBS_TEXT_DOMAIN); ?></h2>
                    <p><?php echo esc_html__('Ative o módulo somente depois de informar o identificador correto da conta.', MLBS_TEXT_DOMAIN); ?></p>
                </div>
                <span class="mlbs-pill is-soft">Core</span>
            </div>

            <div class="mlbs-adsense-fields-grid">
                <label class="mlbs-adsense-toggle-card">
                    <input type="checkbox" name="mlbs_adsense[enabled]" value="1" <?php checked(! empty($settings['enabled'])); ?>>
                    <span><strong><?php echo esc_html__('Ativar integração AdSense', MLBS_TEXT_DOMAIN); ?></strong><small><?php echo esc_html__('Chave geral do módulo. Desativada, nenhum código é emitido no site.', MLBS_TEXT_DOMAIN); ?></small></span>
                </label>

                <label class="mlbs-adsense-field mlbs-adsense-field-wide">
                    <span><?php echo esc_html__('Publisher ID', MLBS_TEXT_DOMAIN); ?></span>
                    <input type="text" name="mlbs_adsense[publisher_id]" value="<?php echo esc_attr($settings['publisher_id']); ?>" placeholder="ca-pub-0000000000000000" autocomplete="off" spellcheck="false">
                    <small><?php echo esc_html__('Formato obrigatório: ca-pub- seguido de 16 números.', MLBS_TEXT_DOMAIN); ?></small>
                </label>

                <label class="mlbs-adsense-toggle-card">
                    <input type="checkbox" name="mlbs_adsense[auto_ads_enabled]" value="1" <?php checked(! empty($settings['auto_ads_enabled'])); ?>>
                    <span><strong><?php echo esc_html__('Carregar Auto Ads em todo o site', MLBS_TEXT_DOMAIN); ?></strong><small><?php echo esc_html__('Insere o script global no cabeçalho. Os formatos e posições continuam controlados na conta AdSense.', MLBS_TEXT_DOMAIN); ?></small></span>
                </label>

                <label class="mlbs-adsense-toggle-card">
                    <input type="checkbox" name="mlbs_adsense[hide_for_administrators]" value="1" <?php checked(! empty($settings['hide_for_administrators'])); ?>>
                    <span><strong><?php echo esc_html__('Ocultar para administradores conectados', MLBS_TEXT_DOMAIN); ?></strong><small><?php echo esc_html__('Evita impressões e cliques acidentais durante edição e homologação.', MLBS_TEXT_DOMAIN); ?></small></span>
                </label>

                <label class="mlbs-adsense-toggle-card">
                    <input type="checkbox" name="mlbs_adsense[show_ad_label]" value="1" <?php checked(! empty($settings['show_ad_label'])); ?>>
                    <span><strong><?php echo esc_html__('Exibir identificação do anúncio', MLBS_TEXT_DOMAIN); ?></strong><small><?php echo esc_html__('Mostra um rótulo discreto acima das unidades manuais.', MLBS_TEXT_DOMAIN); ?></small></span>
                </label>

                <label class="mlbs-adsense-field">
                    <span><?php echo esc_html__('Texto do rótulo', MLBS_TEXT_DOMAIN); ?></span>
                    <input type="text" name="mlbs_adsense[ad_label]" value="<?php echo esc_attr($settings['ad_label']); ?>" maxlength="60">
                </label>
            </div>
        </section>

        <section class="mlbs-premium-card mlbs-adsense-section">
            <div class="mlbs-card-head">
                <div>
                    <h2><?php echo esc_html__('Unidades manuais', MLBS_TEXT_DOMAIN); ?></h2>
                    <p><?php echo esc_html__('Cadastre slots criados na conta AdSense e posicione-os com shortcode. O módulo nunca coloca essas unidades em slider, carrossel ou autoplay.', MLBS_TEXT_DOMAIN); ?></p>
                </div>
                <button type="button" class="button button-secondary mlbs-adsense-add-unit"><?php echo esc_html__('Adicionar unidade', MLBS_TEXT_DOMAIN); ?></button>
            </div>

            <div class="mlbs-adsense-units" data-next-index="<?php echo esc_attr((string) count($settings['units'])); ?>">
                <?php foreach ($settings['units'] as $index => $unit) : ?>
                    <?php include MLBS_PLUGIN_DIR . 'modules/adsense/admin/views/unit-row.php'; ?>
                <?php endforeach; ?>
            </div>

            <div class="mlbs-adsense-empty-state<?php echo empty($settings['units']) ? '' : ' is-hidden'; ?>">
                <strong><?php echo esc_html__('Nenhuma unidade manual cadastrada.', MLBS_TEXT_DOMAIN); ?></strong>
                <span><?php echo esc_html__('Adicione uma unidade e informe o Slot ID fornecido pelo Google AdSense.', MLBS_TEXT_DOMAIN); ?></span>
            </div>
        </section>

        <section class="mlbs-premium-card mlbs-adsense-section">
            <div class="mlbs-card-head">
                <div>
                    <h2><?php echo esc_html__('Inserção editorial automática', MLBS_TEXT_DOMAIN); ?></h2>
                    <p><?php echo esc_html__('Insere uma única unidade estática no conteúdo principal, sem atualizar, rotacionar ou contabilizar cliques pelo ML Banner Suite.', MLBS_TEXT_DOMAIN); ?></p>
                </div>
                <span class="mlbs-pill is-soft"><?php echo esc_html__('Opcional', MLBS_TEXT_DOMAIN); ?></span>
            </div>

            <div class="mlbs-adsense-fields-grid">
                <label class="mlbs-adsense-toggle-card mlbs-adsense-field-wide">
                    <input type="checkbox" name="mlbs_adsense[content_injection_enabled]" value="1" <?php checked(! empty($settings['content_injection_enabled'])); ?>>
                    <span><strong><?php echo esc_html__('Ativar inserção no conteúdo', MLBS_TEXT_DOMAIN); ?></strong><small><?php echo esc_html__('Funciona somente em páginas singulares e na consulta principal do WordPress.', MLBS_TEXT_DOMAIN); ?></small></span>
                </label>

                <label class="mlbs-adsense-field">
                    <span><?php echo esc_html__('Unidade', MLBS_TEXT_DOMAIN); ?></span>
                    <select name="mlbs_adsense[content_unit]">
                        <option value=""><?php echo esc_html__('Selecione uma unidade ativa', MLBS_TEXT_DOMAIN); ?></option>
                        <?php foreach ($settings['units'] as $unit) : ?>
                            <?php if (! empty($unit['enabled']) && ! empty($unit['slot'])) : ?>
                                <option value="<?php echo esc_attr($unit['id']); ?>" <?php selected($settings['content_unit'], $unit['id']); ?>><?php echo esc_html($unit['name']); ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="mlbs-adsense-field">
                    <span><?php echo esc_html__('Posição', MLBS_TEXT_DOMAIN); ?></span>
                    <select name="mlbs_adsense[content_position]" class="mlbs-adsense-content-position">
                        <option value="before_content" <?php selected($settings['content_position'], 'before_content'); ?>><?php echo esc_html__('Antes do conteúdo', MLBS_TEXT_DOMAIN); ?></option>
                        <option value="after_paragraph" <?php selected($settings['content_position'], 'after_paragraph'); ?>><?php echo esc_html__('Depois de um parágrafo', MLBS_TEXT_DOMAIN); ?></option>
                        <option value="after_content" <?php selected($settings['content_position'], 'after_content'); ?>><?php echo esc_html__('Depois do conteúdo', MLBS_TEXT_DOMAIN); ?></option>
                    </select>
                </label>

                <label class="mlbs-adsense-field mlbs-adsense-paragraph-field">
                    <span><?php echo esc_html__('Após o parágrafo', MLBS_TEXT_DOMAIN); ?></span>
                    <input type="number" min="1" max="50" name="mlbs_adsense[content_paragraph]" value="<?php echo esc_attr((string) $settings['content_paragraph']); ?>">
                </label>

                <div class="mlbs-adsense-field mlbs-adsense-field-wide">
                    <span><?php echo esc_html__('Tipos de conteúdo', MLBS_TEXT_DOMAIN); ?></span>
                    <div class="mlbs-adsense-checkboxes">
                        <?php foreach ($public_post_types as $post_type) : ?>
                            <label><input type="checkbox" name="mlbs_adsense[content_post_types][]" value="<?php echo esc_attr($post_type->name); ?>" <?php checked(in_array($post_type->name, $settings['content_post_types'], true)); ?>> <?php echo esc_html($post_type->labels->singular_name); ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="mlbs-premium-card mlbs-adsense-section">
            <div class="mlbs-card-head">
                <div>
                    <h2><?php echo esc_html__('ads.txt', MLBS_TEXT_DOMAIN); ?></h2>
                    <p><?php echo esc_html__('Publica virtualmente a autorização do Google quando não existe um arquivo ads.txt físico na raiz do WordPress.', MLBS_TEXT_DOMAIN); ?></p>
                </div>
                <a class="button button-secondary" href="<?php echo esc_url($ads_txt_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__('Abrir ads.txt', MLBS_TEXT_DOMAIN); ?></a>
            </div>

            <?php if ($physical_ads_txt_exists) : ?>
                <div class="mlbs-adsense-notice is-warning"><strong><?php echo esc_html__('Arquivo físico detectado.', MLBS_TEXT_DOMAIN); ?></strong> <?php echo esc_html__('O módulo não o substitui nem o altera. A linha do Google deve ser mantida diretamente nesse arquivo.', MLBS_TEXT_DOMAIN); ?></div>
            <?php endif; ?>

            <div class="mlbs-adsense-fields-grid">
                <label class="mlbs-adsense-toggle-card mlbs-adsense-field-wide">
                    <input type="checkbox" name="mlbs_adsense[ads_txt_enabled]" value="1" <?php checked(! empty($settings['ads_txt_enabled'])); ?>>
                    <span><strong><?php echo esc_html__('Ativar ads.txt virtual', MLBS_TEXT_DOMAIN); ?></strong><small><?php echo esc_html__('Não sobrescreve arquivo físico e não remove linhas adicionais informadas abaixo.', MLBS_TEXT_DOMAIN); ?></small></span>
                </label>

                <div class="mlbs-adsense-field mlbs-adsense-field-wide">
                    <span><?php echo esc_html__('Linha gerada para o Google', MLBS_TEXT_DOMAIN); ?></span>
                    <code class="mlbs-adsense-code"><?php echo $generated_ads_txt_line !== '' ? esc_html($generated_ads_txt_line) : esc_html__('Informe um Publisher ID válido para gerar a linha.', MLBS_TEXT_DOMAIN); ?></code>
                </div>

                <label class="mlbs-adsense-field mlbs-adsense-field-wide">
                    <span><?php echo esc_html__('Linhas adicionais', MLBS_TEXT_DOMAIN); ?></span>
                    <textarea name="mlbs_adsense[ads_txt_additional_lines]" rows="6" spellcheck="false" placeholder="example.com, pub-0000000000000000, DIRECT, certification-id"><?php echo esc_textarea($settings['ads_txt_additional_lines']); ?></textarea>
                    <small><?php echo esc_html__('Uma entrada por linha. Use somente registros válidos fornecidos pelas plataformas autorizadas.', MLBS_TEXT_DOMAIN); ?></small>
                </label>
            </div>
        </section>

        <section class="mlbs-premium-card mlbs-adsense-section mlbs-adsense-compliance">
            <div class="mlbs-card-head">
                <div>
                    <h2><?php echo esc_html__('Segurança operacional e consentimento', MLBS_TEXT_DOMAIN); ?></h2>
                    <p><?php echo esc_html__('O módulo emite o código oficial, mas não substitui as políticas do Google nem uma plataforma de consentimento certificada quando ela for exigida.', MLBS_TEXT_DOMAIN); ?></p>
                </div>
                <span class="mlbs-pill"><?php echo esc_html__('Proteção', MLBS_TEXT_DOMAIN); ?></span>
            </div>
            <ul class="mlbs-check-list">
                <li><?php echo esc_html__('Nenhum anúncio AdSense participa de autoplay, slider, carrossel ou atualização automática.', MLBS_TEXT_DOMAIN); ?></li>
                <li><?php echo esc_html__('O ML Banner Suite não registra cliques nem impressões das unidades do Google.', MLBS_TEXT_DOMAIN); ?></li>
                <li><?php echo esc_html__('A veiculação pode ser bloqueada por uma CMP usando o filtro técnico mlbs_adsense_can_load.', MLBS_TEXT_DOMAIN); ?></li>
                <li><?php echo esc_html__('Não existe pré-visualização clicável de anúncios dentro do painel administrativo.', MLBS_TEXT_DOMAIN); ?></li>
            </ul>
        </section>

        <div class="mlbs-adsense-savebar">
            <div>
                <strong><?php echo esc_html__('Configuração independente', MLBS_TEXT_DOMAIN); ?></strong>
                <span><?php echo esc_html__('Salva somente na opção mlbs_adsense_settings.', MLBS_TEXT_DOMAIN); ?></span>
            </div>
            <button type="submit" class="button button-primary button-hero"><?php echo esc_html__('Salvar AdSense', MLBS_TEXT_DOMAIN); ?></button>
        </div>
    </form>

    <script type="text/template" id="mlbs-adsense-unit-template">
        <?php
        $index = '__INDEX__';
        $unit = array(
            'id'                    => 'unit-__TIMESTAMP__',
            'name'                  => __('Nova unidade', MLBS_TEXT_DOMAIN),
            'slot'                  => '',
            'format'                => 'auto',
            'full_width_responsive' => 1,
            'alignment'             => 'center',
            'margin_top'            => 16,
            'margin_bottom'         => 16,
            'enabled'               => 1,
        );
        include MLBS_PLUGIN_DIR . 'modules/adsense/admin/views/unit-row.php';
        ?>
    </script>
</div>
