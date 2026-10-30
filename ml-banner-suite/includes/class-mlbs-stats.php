<?php
/**
 * Leitura das estatísticas (impressões e cliques por banner e por dia).
 *
 * Fonte: tabela {prefix}mlbs_stats (um registro por banner + tipo + dia, gravado pelo
 * tracking do front) e, para o total "desde o início", os contadores _mlbs_impressions /
 * _mlbs_clicks de cada banner (existem desde antes da tabela). Só leitura.
 *
 * @package MLBannerSuite
 * @since 3.2.0
 */

if (! defined('ABSPATH')) {
    exit;
}

class MLBS_Stats
{
    const PERIODS = array('7', '30', '90', '365', 'all', 'custom');

    /**
     * Lê e valida o filtro vindo da URL.
     *
     * @return array{period:string,from:string,to:string,group:int,orderby:string,order:string,days:int}
     */
    public static function get_filters()
    {
        $period = isset($_GET['period']) ? sanitize_key(wp_unslash($_GET['period'])) : '30';
        if (! in_array($period, self::PERIODS, true)) {
            $period = '30';
        }

        $today = current_time('Y-m-d');
        $from  = '';
        $to    = $today;

        if ($period === 'custom') {
            $from = isset($_GET['from']) ? self::sanitize_date(wp_unslash($_GET['from'])) : '';
            $to   = isset($_GET['to']) ? self::sanitize_date(wp_unslash($_GET['to'])) : '';
            if ($from === '' || $to === '') {
                $period = '30';
            } elseif ($from > $to) {
                list($from, $to) = array($to, $from);
            }
        }

        if ($period === 'all') {
            $from = self::first_date();
            if ($from === '') {
                $from = $today;
            }
        } elseif ($period !== 'custom') {
            $from = gmdate('Y-m-d', strtotime($today . ' -' . ((int) $period - 1) . ' days'));
            $to   = $today;
        }

        $orderby = isset($_GET['orderby']) ? sanitize_key(wp_unslash($_GET['orderby'])) : 'impressions';
        if (! in_array($orderby, array('title', 'impressions', 'clicks', 'ctr', 'total_impressions', 'total_clicks'), true)) {
            $orderby = 'impressions';
        }
        $order = isset($_GET['order']) && strtolower((string) wp_unslash($_GET['order'])) === 'asc' ? 'asc' : 'desc';

        $days = (int) floor((strtotime($to) - strtotime($from)) / DAY_IN_SECONDS) + 1;

        return array(
            'period'  => $period,
            'from'    => $from,
            'to'      => $to,
            'group'   => isset($_GET['group']) ? absint($_GET['group']) : 0,
            'orderby' => $orderby,
            'order'   => $order,
            'days'    => max(1, $days),
        );
    }

    public static function sanitize_date($value)
    {
        $value = sanitize_text_field((string) $value);
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return '';
        }
        list($y, $m, $d) = array_map('intval', explode('-', $value));
        return checkdate($m, $d, $y) ? $value : '';
    }

    public static function table_exists()
    {
        global $wpdb;
        $table = MLBS_Database::get_stats_table_name();
        return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
    }

    /** Primeiro dia com registro na tabela (para "Todo o período"). */
    public static function first_date()
    {
        global $wpdb;
        if (! self::table_exists()) {
            return '';
        }
        $table = MLBS_Database::get_stats_table_name();
        $date  = $wpdb->get_var("SELECT MIN(event_date) FROM {$table}"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $date ? (string) $date : '';
    }

    /** IDs dos banners do grupo (ou null = todos). */
    public static function group_banner_ids($group_id)
    {
        if (! $group_id) {
            return null;
        }
        $ids = get_posts(array(
            'post_type'      => MLBS_Post_Types::POST_TYPE,
            'post_status'    => array('publish', 'draft', 'pending', 'private', 'future'),
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'tax_query'      => array(array('taxonomy' => 'mlbs_group', 'field' => 'term_id', 'terms' => $group_id)),
        ));
        return array_map('absint', $ids);
    }

    /**
     * Totais por banner no intervalo: [banner_id => ['impression'=>n,'click'=>n]].
     */
    public static function per_banner($from, $to, $ids = null)
    {
        global $wpdb;
        if (! self::table_exists() || (is_array($ids) && ! $ids)) {
            return array();
        }
        $table = MLBS_Database::get_stats_table_name();
        $sql   = "SELECT banner_id, event_type, SUM(event_count) AS total FROM {$table} WHERE event_date BETWEEN %s AND %s";
        $args  = array($from, $to);
        if (is_array($ids)) {
            $sql .= ' AND banner_id IN (' . implode(',', array_fill(0, count($ids), '%d')) . ')';
            $args = array_merge($args, $ids);
        }
        $sql .= ' GROUP BY banner_id, event_type';
        $rows = $wpdb->get_results($wpdb->prepare($sql, $args), ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        $out = array();
        foreach ((array) $rows as $row) {
            $id = (int) $row['banner_id'];
            if (! isset($out[$id])) {
                $out[$id] = array('impression' => 0, 'click' => 0);
            }
            if (isset($out[$id][$row['event_type']])) {
                $out[$id][$row['event_type']] = (int) $row['total'];
            }
        }
        return $out;
    }

    /**
     * Série diária completa (dias sem registro = 0): [Y-m-d => ['impression'=>n,'click'=>n]].
     */
    public static function daily($from, $to, $ids = null)
    {
        global $wpdb;
        $series = array();
        $cursor = strtotime($from);
        $end    = strtotime($to);
        $guard  = 0;
        while ($cursor <= $end && $guard < 3700) {
            $series[gmdate('Y-m-d', $cursor)] = array('impression' => 0, 'click' => 0);
            $cursor += DAY_IN_SECONDS;
            $guard++;
        }

        if (! self::table_exists() || (is_array($ids) && ! $ids)) {
            return $series;
        }
        $table = MLBS_Database::get_stats_table_name();
        $sql   = "SELECT event_date, event_type, SUM(event_count) AS total FROM {$table} WHERE event_date BETWEEN %s AND %s";
        $args  = array($from, $to);
        if (is_array($ids)) {
            $sql .= ' AND banner_id IN (' . implode(',', array_fill(0, count($ids), '%d')) . ')';
            $args = array_merge($args, $ids);
        }
        $sql .= ' GROUP BY event_date, event_type';
        $rows = $wpdb->get_results($wpdb->prepare($sql, $args), ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        foreach ((array) $rows as $row) {
            $day = (string) $row['event_date'];
            if (isset($series[$day][$row['event_type']])) {
                $series[$day][$row['event_type']] = (int) $row['total'];
            }
        }
        return $series;
    }

    /** Soma de um per_banner(). */
    public static function sum($per_banner)
    {
        $t = array('impression' => 0, 'click' => 0);
        foreach ($per_banner as $row) {
            $t['impression'] += $row['impression'];
            $t['click']      += $row['click'];
        }
        return $t;
    }

    public static function ctr($clicks, $impressions)
    {
        return $impressions > 0 ? ($clicks / $impressions) * 100 : 0.0;
    }

    /** Variação percentual (null quando não há base de comparação). */
    public static function delta($current, $previous)
    {
        if ($previous <= 0) {
            return null;
        }
        return (($current - $previous) / $previous) * 100;
    }

    /**
     * Monta as linhas do ranking: todos os banners (do grupo, se filtrado), com período e total.
     */
    public static function banner_rows($filters)
    {
        $ids = self::group_banner_ids($filters['group']);
        $args = array(
            'post_type'      => MLBS_Post_Types::POST_TYPE,
            'post_status'    => array('publish', 'draft', 'pending', 'private', 'future'),
            'posts_per_page' => -1,
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
        );
        if (is_array($ids)) {
            $args['post__in'] = $ids ? $ids : array(0);
        }
        $posts  = get_posts($args);
        $period = self::per_banner($filters['from'], $filters['to'], $ids);

        $rows = array();
        foreach ($posts as $post) {
            $id     = (int) $post->ID;
            $p      = isset($period[$id]) ? $period[$id] : array('impression' => 0, 'click' => 0);
            $terms  = get_the_terms($id, 'mlbs_group');
            $groups = (is_array($terms)) ? wp_list_pluck($terms, 'name') : array();
            $rows[] = array(
                'id'                => $id,
                'title'             => get_the_title($id) !== '' ? get_the_title($id) : sprintf(__('Banner #%d', MLBS_TEXT_DOMAIN), $id),
                'status'            => $post->post_status,
                'display'           => MLBS_Helpers::get_banner_status_label($id),
                'groups'            => $groups,
                'impressions'       => $p['impression'],
                'clicks'            => $p['click'],
                'ctr'               => self::ctr($p['click'], $p['impression']),
                'total_impressions' => (int) get_post_meta($id, '_mlbs_impressions', true),
                'total_clicks'      => (int) get_post_meta($id, '_mlbs_clicks', true),
                'thumb'             => (int) get_post_meta($id, '_mlbs_image_id', true),
            );
        }

        $key = $filters['orderby'];
        $dir = $filters['order'] === 'asc' ? 1 : -1;
        usort($rows, function ($a, $b) use ($key, $dir) {
            if ($key === 'title') {
                return $dir * strcasecmp($a['title'], $b['title']);
            }
            if ($a[$key] == $b[$key]) {
                return strcasecmp($a['title'], $b['title']);
            }
            return $dir * (($a[$key] < $b[$key]) ? -1 : 1);
        });

        return $rows;
    }

    /** Totais por grupo no período. */
    public static function group_rows($filters)
    {
        $terms = get_terms(array('taxonomy' => 'mlbs_group', 'hide_empty' => false));
        if (is_wp_error($terms)) {
            return array();
        }
        $rows = array();
        foreach ($terms as $term) {
            $ids = self::group_banner_ids($term->term_id);
            $t   = self::sum(self::per_banner($filters['from'], $filters['to'], $ids));
            $rows[] = array(
                'id'          => (int) $term->term_id,
                'name'        => $term->name,
                'banners'     => count($ids),
                'impressions' => $t['impression'],
                'clicks'      => $t['click'],
                'ctr'         => self::ctr($t['click'], $t['impression']),
            );
        }
        usort($rows, function ($a, $b) {
            return $b['impressions'] <=> $a['impressions'];
        });
        return $rows;
    }

    /** Exportação CSV do ranking (admin-post, só administrador, com nonce). */
    public static function handle_export()
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Permissão insuficiente.', MLBS_TEXT_DOMAIN), 403);
        }
        check_admin_referer('mlbs_export_stats');

        $filters = self::get_filters();
        $rows    = self::banner_rows($filters);

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=ml-banner-suite-estatisticas-' . $filters['from'] . '-a-' . $filters['to'] . '.csv');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, array('ID', 'Banner', 'Grupo', 'Situação', 'Impressões (período)', 'Cliques (período)', 'CTR % (período)', 'Impressões (desde o início)', 'Cliques (desde o início)'), ';');
        // Evita que planilhas executem texto como fórmula (=, +, -, @).
        $cell = function ($v) {
            $v = (string) $v;
            return preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v;
        };
        foreach ($rows as $r) {
            fputcsv($out, array(
                $r['id'],
                $cell($r['title']),
                $cell(implode(', ', $r['groups'])),
                $r['display'],
                $r['impressions'],
                $r['clicks'],
                number_format($r['ctr'], 2, ',', ''),
                $r['total_impressions'],
                $r['total_clicks'],
            ), ';');
        }
        fclose($out);
        exit;
    }
}
