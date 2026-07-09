<?php
if (! defined('ABSPATH')) {
    exit;
}
$shortcode = '[mlbs_adsense unit="' . $unit['id'] . '"]';
?>
<article class="mlbs-adsense-unit" data-unit-index="<?php echo esc_attr((string) $index); ?>">
    <div class="mlbs-adsense-unit-head">
        <div>
            <strong class="mlbs-adsense-unit-title"><?php echo esc_html($unit['name']); ?></strong>
            <code class="mlbs-adsense-shortcode-preview"><?php echo esc_html($shortcode); ?></code>
        </div>
        <div class="mlbs-adsense-unit-actions">
            <button type="button" class="button button-secondary mlbs-adsense-copy-shortcode" data-shortcode="<?php echo esc_attr($shortcode); ?>"><?php echo esc_html__('Copiar shortcode', MLBS_TEXT_DOMAIN); ?></button>
            <button type="button" class="button-link-delete mlbs-adsense-remove-unit"><?php echo esc_html__('Remover', MLBS_TEXT_DOMAIN); ?></button>
        </div>
    </div>

    <div class="mlbs-adsense-unit-grid">
        <label class="mlbs-adsense-field">
            <span><?php echo esc_html__('Nome interno', MLBS_TEXT_DOMAIN); ?></span>
            <input type="text" class="mlbs-adsense-unit-name" name="mlbs_adsense[units][<?php echo esc_attr((string) $index); ?>][name]" value="<?php echo esc_attr($unit['name']); ?>" maxlength="100">
        </label>

        <label class="mlbs-adsense-field">
            <span><?php echo esc_html__('Identificador do shortcode', MLBS_TEXT_DOMAIN); ?></span>
            <input type="text" class="mlbs-adsense-unit-id" name="mlbs_adsense[units][<?php echo esc_attr((string) $index); ?>][id]" value="<?php echo esc_attr($unit['id']); ?>" pattern="[a-z0-9_-]+" maxlength="80" spellcheck="false">
        </label>

        <label class="mlbs-adsense-field">
            <span><?php echo esc_html__('Slot ID', MLBS_TEXT_DOMAIN); ?></span>
            <input type="text" name="mlbs_adsense[units][<?php echo esc_attr((string) $index); ?>][slot]" value="<?php echo esc_attr($unit['slot']); ?>" inputmode="numeric" pattern="[0-9]+" maxlength="32" placeholder="1234567890" spellcheck="false">
        </label>

        <label class="mlbs-adsense-field">
            <span><?php echo esc_html__('Formato responsivo', MLBS_TEXT_DOMAIN); ?></span>
            <select name="mlbs_adsense[units][<?php echo esc_attr((string) $index); ?>][format]">
                <option value="auto" <?php selected($unit['format'], 'auto'); ?>><?php echo esc_html__('Automático', MLBS_TEXT_DOMAIN); ?></option>
                <option value="rectangle" <?php selected($unit['format'], 'rectangle'); ?>><?php echo esc_html__('Retangular', MLBS_TEXT_DOMAIN); ?></option>
                <option value="horizontal" <?php selected($unit['format'], 'horizontal'); ?>><?php echo esc_html__('Horizontal', MLBS_TEXT_DOMAIN); ?></option>
                <option value="vertical" <?php selected($unit['format'], 'vertical'); ?>><?php echo esc_html__('Vertical', MLBS_TEXT_DOMAIN); ?></option>
            </select>
        </label>

        <label class="mlbs-adsense-field">
            <span><?php echo esc_html__('Alinhamento do contêiner', MLBS_TEXT_DOMAIN); ?></span>
            <select name="mlbs_adsense[units][<?php echo esc_attr((string) $index); ?>][alignment]">
                <option value="left" <?php selected($unit['alignment'], 'left'); ?>><?php echo esc_html__('Esquerda', MLBS_TEXT_DOMAIN); ?></option>
                <option value="center" <?php selected($unit['alignment'], 'center'); ?>><?php echo esc_html__('Centro', MLBS_TEXT_DOMAIN); ?></option>
                <option value="right" <?php selected($unit['alignment'], 'right'); ?>><?php echo esc_html__('Direita', MLBS_TEXT_DOMAIN); ?></option>
            </select>
        </label>

        <label class="mlbs-adsense-field">
            <span><?php echo esc_html__('Margem superior', MLBS_TEXT_DOMAIN); ?></span>
            <input type="number" min="0" max="200" name="mlbs_adsense[units][<?php echo esc_attr((string) $index); ?>][margin_top]" value="<?php echo esc_attr((string) $unit['margin_top']); ?>">
        </label>

        <label class="mlbs-adsense-field">
            <span><?php echo esc_html__('Margem inferior', MLBS_TEXT_DOMAIN); ?></span>
            <input type="number" min="0" max="200" name="mlbs_adsense[units][<?php echo esc_attr((string) $index); ?>][margin_bottom]" value="<?php echo esc_attr((string) $unit['margin_bottom']); ?>">
        </label>

        <div class="mlbs-adsense-unit-toggles">
            <label><input type="checkbox" name="mlbs_adsense[units][<?php echo esc_attr((string) $index); ?>][full_width_responsive]" value="1" <?php checked(! empty($unit['full_width_responsive'])); ?>> <?php echo esc_html__('Largura total responsiva', MLBS_TEXT_DOMAIN); ?></label>
            <label><input type="checkbox" name="mlbs_adsense[units][<?php echo esc_attr((string) $index); ?>][enabled]" value="1" <?php checked(! empty($unit['enabled'])); ?>> <?php echo esc_html__('Unidade ativa', MLBS_TEXT_DOMAIN); ?></label>
        </div>
    </div>
</article>
