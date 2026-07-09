<?php
/**
 * Taxonomies.
 *
 * @package MLBannerSuite
 */

if (! defined('ABSPATH')) {
    exit;
}

class MLBS_Taxonomies
{
    public function register_taxonomies()
    {
        $this->register_group_taxonomy();
    }

    public function render_group_add_fields()
    {
        $options = MLBS_Helpers::get_display_mode_options();
        $settings = MLBS_Helpers::get_group_setting_defaults();
        $transitions = MLBS_Helpers::get_transition_options();
        ?>
        <div class="form-field term-mlbs-group-display-mode-wrap">
            <label for="mlbs_group_display_mode"><?php echo esc_html__('Tipo de exibição do grupo', MLBS_TEXT_DOMAIN); ?></label>
            <select name="mlbs_group_display_mode" id="mlbs_group_display_mode">
                <?php foreach ($options as $value => $label) : ?>
                    <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
            <p><?php echo esc_html__('O grupo decide se a saída será grid, slideshow ou carrossel.', MLBS_TEXT_DOMAIN); ?></p>
        </div>
        <div class="form-field">
            <label for="mlbs_group_transition"><?php echo esc_html__('Transição do slideshow', MLBS_TEXT_DOMAIN); ?></label>
            <select name="mlbs_group_transition" id="mlbs_group_transition">
                <?php foreach ($transitions as $value => $label) : ?>
                    <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
            <p><?php echo esc_html__('Define a diferença visual entre slideshow e carrossel.', MLBS_TEXT_DOMAIN); ?></p>
        </div>
        <div class="form-field">
            <label><input type="checkbox" name="mlbs_group_autoplay" value="1" <?php checked($settings['autoplay'], 1); ?> /> <?php echo esc_html__('Autoplay', MLBS_TEXT_DOMAIN); ?></label>
        </div>
        <div class="form-field">
            <label><input type="checkbox" name="mlbs_group_show_arrows" value="1" <?php checked($settings['show_arrows'], 1); ?> /> <?php echo esc_html__('Exibir setas', MLBS_TEXT_DOMAIN); ?></label>
        </div>
        <div class="form-field">
            <label for="mlbs_group_desktop_items"><?php echo esc_html__('Itens visíveis no desktop', MLBS_TEXT_DOMAIN); ?></label>
            <input type="number" min="1" step="1" name="mlbs_group_desktop_items" id="mlbs_group_desktop_items" value="<?php echo esc_attr($settings['desktop_items']); ?>" />
        </div>
        <div class="form-field">
            <label for="mlbs_group_tablet_items"><?php echo esc_html__('Itens visíveis no tablet', MLBS_TEXT_DOMAIN); ?></label>
            <input type="number" min="1" step="1" name="mlbs_group_tablet_items" id="mlbs_group_tablet_items" value="<?php echo esc_attr($settings['tablet_items']); ?>" />
        </div>
        <div class="form-field">
            <label for="mlbs_group_mobile_items"><?php echo esc_html__('Itens visíveis no mobile', MLBS_TEXT_DOMAIN); ?></label>
            <input type="number" min="1" step="1" name="mlbs_group_mobile_items" id="mlbs_group_mobile_items" value="<?php echo esc_attr($settings['mobile_items']); ?>" />
        </div>
        <div class="form-field">
            <label for="mlbs_group_grid_columns"><?php echo esc_html__('Colunas do grid', MLBS_TEXT_DOMAIN); ?></label>
            <input type="number" min="1" step="1" name="mlbs_group_grid_columns" id="mlbs_group_grid_columns" value="<?php echo esc_attr($settings['grid_columns']); ?>" />
        </div>
        <div class="form-field">
            <label for="mlbs_group_grid_rows"><?php echo esc_html__('Linhas do grid', MLBS_TEXT_DOMAIN); ?></label>
            <input type="number" min="1" step="1" name="mlbs_group_grid_rows" id="mlbs_group_grid_rows" value="<?php echo esc_attr($settings['grid_rows']); ?>" />
        </div>
        <?php
    }

    public function render_group_edit_fields($term)
    {
        $options = MLBS_Helpers::get_display_mode_options();
        $settings = MLBS_Helpers::get_group_settings($term->term_id);
        $transitions = MLBS_Helpers::get_transition_options();
        ?>
        <tr class="form-field term-mlbs-group-display-mode-wrap">
            <th scope="row"><label for="mlbs_group_display_mode"><?php echo esc_html__('Tipo de exibição do grupo', MLBS_TEXT_DOMAIN); ?></label></th>
            <td>
                <select name="mlbs_group_display_mode" id="mlbs_group_display_mode">
                    <?php foreach ($options as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($settings['display_mode'], $value); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="mlbs_group_transition"><?php echo esc_html__('Transição do slideshow', MLBS_TEXT_DOMAIN); ?></label></th>
            <td>
                <select name="mlbs_group_transition" id="mlbs_group_transition">
                    <?php foreach ($transitions as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($settings['transition'], $value); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php echo esc_html__('Carrossel continua em rolagem horizontal; slideshow usa esta transição.', MLBS_TEXT_DOMAIN); ?></p>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><?php echo esc_html__('Autoplay', MLBS_TEXT_DOMAIN); ?></th>
            <td><label><input type="checkbox" name="mlbs_group_autoplay" value="1" <?php checked($settings['autoplay'], 1); ?> /> <?php echo esc_html__('Ativar rotação automática', MLBS_TEXT_DOMAIN); ?></label></td>
        </tr>
        <tr class="form-field">
            <th scope="row"><?php echo esc_html__('Setas', MLBS_TEXT_DOMAIN); ?></th>
            <td><label><input type="checkbox" name="mlbs_group_show_arrows" value="1" <?php checked($settings['show_arrows'], 1); ?> /> <?php echo esc_html__('Mostrar setas de navegação', MLBS_TEXT_DOMAIN); ?></label></td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="mlbs_group_desktop_items"><?php echo esc_html__('Itens visíveis no desktop', MLBS_TEXT_DOMAIN); ?></label></th>
            <td><input type="number" min="1" step="1" name="mlbs_group_desktop_items" id="mlbs_group_desktop_items" value="<?php echo esc_attr($settings['desktop_items']); ?>" /></td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="mlbs_group_tablet_items"><?php echo esc_html__('Itens visíveis no tablet', MLBS_TEXT_DOMAIN); ?></label></th>
            <td><input type="number" min="1" step="1" name="mlbs_group_tablet_items" id="mlbs_group_tablet_items" value="<?php echo esc_attr($settings['tablet_items']); ?>" /></td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="mlbs_group_mobile_items"><?php echo esc_html__('Itens visíveis no mobile', MLBS_TEXT_DOMAIN); ?></label></th>
            <td><input type="number" min="1" step="1" name="mlbs_group_mobile_items" id="mlbs_group_mobile_items" value="<?php echo esc_attr($settings['mobile_items']); ?>" /></td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="mlbs_group_grid_columns"><?php echo esc_html__('Colunas do grid', MLBS_TEXT_DOMAIN); ?></label></th>
            <td><input type="number" min="1" step="1" name="mlbs_group_grid_columns" id="mlbs_group_grid_columns" value="<?php echo esc_attr($settings['grid_columns']); ?>" /></td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="mlbs_group_grid_rows"><?php echo esc_html__('Linhas do grid', MLBS_TEXT_DOMAIN); ?></label></th>
            <td><input type="number" min="1" step="1" name="mlbs_group_grid_rows" id="mlbs_group_grid_rows" value="<?php echo esc_attr($settings['grid_rows']); ?>" /></td>
        </tr>
        <?php
    }

    public function save_group_fields($term_id)
    {
        if (! current_user_can('manage_categories')) {
            return;
        }

        update_term_meta((int) $term_id, 'mlbs_group_display_mode', MLBS_Helpers::normalize_display_mode(isset($_POST['mlbs_group_display_mode']) ? wp_unslash($_POST['mlbs_group_display_mode']) : 'static'));
        update_term_meta((int) $term_id, 'mlbs_group_transition', MLBS_Helpers::normalize_transition(isset($_POST['mlbs_group_transition']) ? wp_unslash($_POST['mlbs_group_transition']) : 'slide'));
        update_term_meta((int) $term_id, 'mlbs_group_autoplay', isset($_POST['mlbs_group_autoplay']) ? 1 : 0);
        update_term_meta((int) $term_id, 'mlbs_group_show_arrows', isset($_POST['mlbs_group_show_arrows']) ? 1 : 0);
        update_term_meta((int) $term_id, 'mlbs_group_desktop_items', max(1, absint(isset($_POST['mlbs_group_desktop_items']) ? wp_unslash($_POST['mlbs_group_desktop_items']) : 3)));
        update_term_meta((int) $term_id, 'mlbs_group_tablet_items', max(1, absint(isset($_POST['mlbs_group_tablet_items']) ? wp_unslash($_POST['mlbs_group_tablet_items']) : 2)));
        update_term_meta((int) $term_id, 'mlbs_group_mobile_items', max(1, absint(isset($_POST['mlbs_group_mobile_items']) ? wp_unslash($_POST['mlbs_group_mobile_items']) : 1)));
        update_term_meta((int) $term_id, 'mlbs_group_grid_columns', max(1, absint(isset($_POST['mlbs_group_grid_columns']) ? wp_unslash($_POST['mlbs_group_grid_columns']) : 3)));
        update_term_meta((int) $term_id, 'mlbs_group_grid_rows', max(1, absint(isset($_POST['mlbs_group_grid_rows']) ? wp_unslash($_POST['mlbs_group_grid_rows']) : 1)));
    }

    public function register_group_columns($columns)
    {
        $new = array();
        foreach ($columns as $key => $label) {
            $new[$key] = $label;
            if ($key === 'name') {
                $new['mlbs_group_shortcode'] = __('Shortcode', MLBS_TEXT_DOMAIN);
                $new['mlbs_group_display_mode'] = __('Exibição', MLBS_TEXT_DOMAIN);
                $new['mlbs_group_layout'] = __('Layout', MLBS_TEXT_DOMAIN);
            }
        }
        return $new;
    }

    public function render_group_columns($content, $column_name, $term_id)
    {
        if ($column_name === 'mlbs_group_shortcode') {
            $term = get_term($term_id, 'mlbs_group');
            return $term && ! is_wp_error($term) ? '<code>' . esc_html(MLBS_Helpers::get_group_shortcode($term->slug)) . '</code>' : '—';
        }
        if ($column_name === 'mlbs_group_display_mode') {
            $options = MLBS_Helpers::get_display_mode_options();
            $settings = MLBS_Helpers::get_group_settings($term_id);
            return isset($options[$settings['display_mode']]) ? esc_html($options[$settings['display_mode']]) : '—';
        }
        if ($column_name === 'mlbs_group_layout') {
            $settings = MLBS_Helpers::get_group_settings($term_id);
            if ($settings['display_mode'] === 'static') {
                return esc_html(sprintf('%1$dx%2$d', $settings['grid_columns'], $settings['grid_rows']));
            }
            $transition = MLBS_Helpers::get_transition_options();
            $suffix = $settings['display_mode'] === 'slideshow' ? ' · ' . $transition[$settings['transition']] : '';
            return esc_html(sprintf('D:%1$d T:%2$d M:%3$d%4$s%5$s', $settings['desktop_items'], $settings['tablet_items'], $settings['mobile_items'], $settings['show_arrows'] ? ' + setas' : '', $suffix));
        }
        return $content;
    }

    private function register_group_taxonomy()
    {
        $labels = array(
            'name'          => __('Grupos', MLBS_TEXT_DOMAIN),
            'singular_name' => __('Grupo', MLBS_TEXT_DOMAIN),
            'search_items'  => __('Buscar grupos', MLBS_TEXT_DOMAIN),
            'all_items'     => __('Todos os grupos', MLBS_TEXT_DOMAIN),
            'edit_item'     => __('Editar grupo', MLBS_TEXT_DOMAIN),
            'update_item'   => __('Atualizar grupo', MLBS_TEXT_DOMAIN),
            'add_new_item'  => __('Adicionar novo grupo', MLBS_TEXT_DOMAIN),
            'new_item_name' => __('Nome do novo grupo', MLBS_TEXT_DOMAIN),
            'menu_name'     => __('Grupos', MLBS_TEXT_DOMAIN),
        );

        register_taxonomy('mlbs_group', array(MLBS_Post_Types::POST_TYPE), array(
            'labels'            => $labels,
            'public'            => false,
            'show_ui'           => true,
            'show_admin_column' => false,
            'show_in_menu'      => false,
            'show_in_rest'      => false,
            'hierarchical'      => true,
            'rewrite'           => false,
            'meta_box_cb'       => false,
        ));
    }

    private function register_category_taxonomy()
    {
        register_taxonomy('mlbs_category', array(MLBS_Post_Types::POST_TYPE), array(
            'labels'            => array(
                'name'          => __('Categorias', MLBS_TEXT_DOMAIN),
                'singular_name' => __('Categoria', MLBS_TEXT_DOMAIN),
            ),
            'public'            => false,
            'show_ui'           => false,
            'show_admin_column' => false,
            'show_in_menu'      => false,
            'show_in_rest'      => false,
            'hierarchical'      => true,
            'rewrite'           => false,
            'meta_box_cb'       => false,
        ));
    }
}
