<?php
/**
 * Post types.
 *
 * @package MLBannerSuite
 */

if (! defined('ABSPATH')) {
    exit;
}

class MLBS_Post_Types
{
    const POST_TYPE = 'mlbs_banner';

    public function register_post_type()
    {
        $labels = array(
            'name'                  => __('Banners', MLBS_TEXT_DOMAIN),
            'singular_name'         => __('Banner', MLBS_TEXT_DOMAIN),
            'menu_name'             => __('Banners', MLBS_TEXT_DOMAIN),
            'name_admin_bar'        => __('Banner', MLBS_TEXT_DOMAIN),
            'add_new'               => __('Adicionar novo', MLBS_TEXT_DOMAIN),
            'add_new_item'          => __('Adicionar novo banner', MLBS_TEXT_DOMAIN),
            'new_item'              => __('Novo banner', MLBS_TEXT_DOMAIN),
            'edit_item'             => __('Editar banner', MLBS_TEXT_DOMAIN),
            'view_item'             => __('Ver banner', MLBS_TEXT_DOMAIN),
            'all_items'             => __('Todos os banners', MLBS_TEXT_DOMAIN),
            'search_items'          => __('Buscar banners', MLBS_TEXT_DOMAIN),
            'not_found'             => __('Nenhum banner encontrado.', MLBS_TEXT_DOMAIN),
            'not_found_in_trash'    => __('Nenhum banner encontrado na lixeira.', MLBS_TEXT_DOMAIN),
            'featured_image'        => __('Imagem do banner', MLBS_TEXT_DOMAIN),
            'set_featured_image'    => __('Definir imagem do banner', MLBS_TEXT_DOMAIN),
            'remove_featured_image' => __('Remover imagem do banner', MLBS_TEXT_DOMAIN),
            'use_featured_image'    => __('Usar como imagem do banner', MLBS_TEXT_DOMAIN),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'show_in_admin_bar'   => false,
            'show_in_nav_menus'   => false,
            'show_in_rest'        => false,
            'supports'            => array('title'),
            'has_archive'         => false,
            'rewrite'             => false,
            'capability_type'     => 'post',
            'menu_icon'           => 'dashicons-format-image',
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'can_export'          => true,
        );

        register_post_type(self::POST_TYPE, $args);
    }
}
