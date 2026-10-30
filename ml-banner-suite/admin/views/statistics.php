<?php
/**
 * Tela Estatísticas (3.2.0): impressões, cliques e CTR por período, por dia, por banner e por grupo.
 *
 * Variáveis: $filters, $table_exists, $totals, $previous, $daily, $rows, $group_rows, $groups,
 * $first_date, $settings (MLBS_Admin_Pages::render_statistics_page).
 */
if (! defined('ABSPATH')) {
    exit;
}

$mlbs_num = function ($n) {
    return number_format_i18n((int) $n);
};
$mlbs_pct = function ($n) {
    return number_format_i18n((float) $n, 2) . '%';
};
$mlbs_date = function ($ymd) {
    return $ymd ? date_i18n('d/m/Y', strtotime($ymd)) : '—';
};
$mlbs_delta = function ($current, $prev) {
    $d = MLBS_Stats::delta($current, $prev);
    if ($d === null) {
        return '<span class="mlbs-delta is-neutral">' . esc_html__('sem base anterior', MLBS_TEXT_DOMAIN) . '</span>';
    }
    $class = $d > 0.05 ? 'is-up' : ($d < -0.05 ? 'is-down' : 'is-neutral');
    $sign  = $d > 0.05 ? '▲ ' : ($d < -0.05 ? '▼ ' : '');
    return '<span class="mlbs-delta ' . esc_attr($class) . '">' . esc_html($sign . number_format_i18n(abs($d), 1) . '% ' . __('vs período anterior', MLBS_TEXT_DOMAIN)) . '</span>';
};

$base_args = array('page' => 'mlbs-statistics', 'period' => $filters['period'], 'group' => $filters['group']);
if ($filters['period'] === 'custom') {
    $base_args['from'] = $filters['from'];
    $base_args['to']   = $filters['to'];
}
$sort_link = function ($key, $label) use ($base_args, $filters) {
    $order = ($filters['orderby'] === $key && $filters['order'] === 'desc') ? 'asc' : 'desc';
    $url   = add_query_arg(array_merge($base_args, array('orderby' => $key, 'order' => $order)), admin_url('admin.php'));
    $arrow = $filters['orderby'] === $key ? ($filters['order'] === 'desc' ? ' ↓' : ' ↑') : '';
    return '<a href="' . esc_url($url) . '">' . esc_html($label . $arrow) . '</a>';
};

$period_labels = array(
    '7'      => __('7 dias', MLBS_TEXT_DOMAIN),
    '30'     => __('30 dias', MLBS_TEXT_DOMAIN),
    '90'     => __('90 dias', MLBS_TEXT_DOMAIN),
    '365'    => __('12 meses', MLBS_TEXT_DOMAIN),
    'all'    => __('Tudo', MLBS_TEXT_DOMAIN),
);

/* Série do gráfico: diária até 120 dias, semanal até 730, mensal acima. */
$bucket = $filters['days'] > 730 ? 'month' : ($filters['days'] > 120 ? 'week' : 'day');
$points = array();
foreach ($daily as $day => $vals) {
    if ($bucket === 'day') {
        $key = $day;
        $label = date_i18n('d/m/Y', strtotime($day));
    } elseif ($bucket === 'week') {
        $ts = strtotime($day);
        $key = gmdate('o-\WW', $ts);
        $label = sprintf(__('Semana de %s', MLBS_TEXT_DOMAIN), date_i18n('d/m/Y', strtotime('monday this week', $ts)));
    } else {
        $key = substr($day, 0, 7);
        $label = date_i18n('F Y', strtotime($day));
    }
    if (! isset($points[$key])) {
        $points[$key] = array('label' => $label, 'impression' => 0, 'click' => 0, 'short' => $bucket === 'month' ? date_i18n('m/y', strtotime($day)) : date_i18n('d/m', strtotime($day)));
    }
    $points[$key]['impression'] += $vals['impression'];
    $points[$key]['click']      += $vals['click'];
}
$points = array_values($points);
$max_imp = 0;
$max_clk = 0;
foreach ($points as $pt) {
    $max_imp = max($max_imp, $pt['impression']);
    $max_clk = max($max_clk, $pt['click']);
}
$nice = function ($v) {
    if ($v <= 0) {
        return 1;
    }
    $p = pow(10, floor(log10($v)));
    foreach (array(1, 2, 2.5, 5, 10) as $m) {
        if ($v <= $m * $p) {
            return $m * $p;
        }
    }
    return 10 * $p;
};
$scale_imp = $nice($max_imp);
$scale_clk = $nice($max_clk);

$svg_w = 1100;
$svg_h = 300;
$pad_l = 56;
$pad_r = 56;
$pad_t = 16;
$pad_b = 34;
$plot_w = $svg_w - $pad_l - $pad_r;
$plot_h = $svg_h - $pad_t - $pad_b;
$n = max(1, count($points));
$slot = $plot_w / $n;
$bar_w = max(1.5, min(28, $slot * 0.64));
$label_every = (int) max(1, ceil($n / 12));
$has_data = ($totals['impression'] + $totals['click']) > 0;
?>
<div class="wrap mlbs-wrap mlbs-stats-wrap">
    <div class="mlbs-shell-header">
        <div>
            <div class="mlbs-eyebrow"><?php echo esc_html__('Medição e desempenho', MLBS_TEXT_DOMAIN); ?></div>
            <h1><?php echo esc_html__('Estatísticas', MLBS_TEXT_DOMAIN); ?> <span class="mlbs-version">v<?php echo esc_html(MLBS_VERSION); ?></span></h1>
            <p class="mlbs-subtitle"><?php echo esc_html__('Impressões, cliques e taxa de cliques (CTR) de cada banner, por dia, por grupo e por período.', MLBS_TEXT_DOMAIN); ?></p>
        </div>
        <div class="mlbs-badges">
            <span class="mlbs-badge is-blue"><?php echo esc_html((int) $settings['track_impressions'] === 1 ? __('Impressões: medindo', MLBS_TEXT_DOMAIN) : __('Impressões: desligado', MLBS_TEXT_DOMAIN)); ?></span>
            <span class="mlbs-badge is-green"><?php echo esc_html((int) $settings['track_clicks'] === 1 ? __('Cliques: medindo', MLBS_TEXT_DOMAIN) : __('Cliques: desligado', MLBS_TEXT_DOMAIN)); ?></span>
        </div>
    </div>

    <?php MLBS_Helpers::render_admin_nav('statistics'); ?>

    <?php if (! $table_exists) : ?>
        <div class="mlbs-stats-alert"><?php echo esc_html__('A tabela de estatísticas ainda não existe neste site. Desative e ative o plugin uma vez para criá-la; os totais "desde o início" continuam aparecendo abaixo.', MLBS_TEXT_DOMAIN); ?></div>
    <?php endif; ?>

    <form class="mlbs-stats-filter" method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
        <input type="hidden" name="page" value="mlbs-statistics">
        <div class="mlbs-stats-periods" role="group" aria-label="<?php echo esc_attr__('Período', MLBS_TEXT_DOMAIN); ?>">
            <?php foreach ($period_labels as $key => $label) : ?>
                <a class="mlbs-period<?php echo $filters['period'] === (string) $key ? ' is-active' : ''; ?>" href="<?php echo esc_url(add_query_arg(array('page' => 'mlbs-statistics', 'period' => $key, 'group' => $filters['group']), admin_url('admin.php'))); ?>"><?php echo esc_html($label); ?></a>
            <?php endforeach; ?>
        </div>
        <div class="mlbs-stats-fields">
            <label><span><?php echo esc_html__('De', MLBS_TEXT_DOMAIN); ?></span><input type="date" name="from" value="<?php echo esc_attr($filters['from']); ?>" max="<?php echo esc_attr(current_time('Y-m-d')); ?>"></label>
            <label><span><?php echo esc_html__('Até', MLBS_TEXT_DOMAIN); ?></span><input type="date" name="to" value="<?php echo esc_attr($filters['to']); ?>" max="<?php echo esc_attr(current_time('Y-m-d')); ?>"></label>
            <label><span><?php echo esc_html__('Grupo', MLBS_TEXT_DOMAIN); ?></span>
                <select name="group">
                    <option value="0"><?php echo esc_html__('Todos os grupos', MLBS_TEXT_DOMAIN); ?></option>
                    <?php if (! is_wp_error($groups)) : foreach ($groups as $term) : ?>
                        <option value="<?php echo esc_attr((string) $term->term_id); ?>" <?php selected($filters['group'], (int) $term->term_id); ?>><?php echo esc_html($term->name); ?></option>
                    <?php endforeach; endif; ?>
                </select>
            </label>
            <input type="hidden" name="period" value="custom">
            <button type="submit" class="mlbs-btn"><?php echo esc_html__('Aplicar', MLBS_TEXT_DOMAIN); ?></button>
            <a class="mlbs-btn is-ghost" href="<?php echo esc_url(wp_nonce_url(add_query_arg(array_merge($base_args, array('action' => 'mlbs_export_stats', 'orderby' => $filters['orderby'], 'order' => $filters['order'])), admin_url('admin-post.php')), 'mlbs_export_stats')); ?>"><span class="dashicons dashicons-download" aria-hidden="true"></span><?php echo esc_html__('Exportar CSV', MLBS_TEXT_DOMAIN); ?></a>
        </div>
    </form>

    <p class="mlbs-stats-range">
        <?php
        /* translators: 1: data inicial, 2: data final, 3: número de dias */
        echo esc_html(sprintf(__('Período: %1$s a %2$s (%3$s dias)', MLBS_TEXT_DOMAIN), $mlbs_date($filters['from']), $mlbs_date($filters['to']), number_format_i18n($filters['days'])));
        if ($first_date) {
            echo ' · ' . esc_html(sprintf(__('medição diária desde %s', MLBS_TEXT_DOMAIN), $mlbs_date($first_date)));
        }
        ?>
    </p>

    <div class="mlbs-kpi-grid">
        <article class="mlbs-kpi-card">
            <span class="mlbs-kpi-label"><?php echo esc_html__('Impressões', MLBS_TEXT_DOMAIN); ?></span>
            <strong class="mlbs-kpi-value"><?php echo esc_html($mlbs_num($totals['impression'])); ?></strong>
            <?php echo $mlbs_delta($totals['impression'], $previous['impression']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapado na função. ?>
        </article>
        <article class="mlbs-kpi-card">
            <span class="mlbs-kpi-label"><?php echo esc_html__('Cliques', MLBS_TEXT_DOMAIN); ?></span>
            <strong class="mlbs-kpi-value"><?php echo esc_html($mlbs_num($totals['click'])); ?></strong>
            <?php echo $mlbs_delta($totals['click'], $previous['click']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </article>
        <article class="mlbs-kpi-card">
            <span class="mlbs-kpi-label"><?php echo esc_html__('CTR (cliques ÷ impressões)', MLBS_TEXT_DOMAIN); ?></span>
            <strong class="mlbs-kpi-value"><?php echo esc_html($mlbs_pct(MLBS_Stats::ctr($totals['click'], $totals['impression']))); ?></strong>
            <?php echo $mlbs_delta(MLBS_Stats::ctr($totals['click'], $totals['impression']), MLBS_Stats::ctr($previous['click'], $previous['impression'])); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </article>
        <article class="mlbs-kpi-card">
            <span class="mlbs-kpi-label"><?php echo esc_html__('Média por dia', MLBS_TEXT_DOMAIN); ?></span>
            <strong class="mlbs-kpi-value"><?php echo esc_html($mlbs_num(round($totals['impression'] / $filters['days']))); ?></strong>
            <span class="mlbs-delta is-neutral"><?php echo esc_html(sprintf(__('%s cliques por dia', MLBS_TEXT_DOMAIN), number_format_i18n($totals['click'] / $filters['days'], 1))); ?></span>
        </article>
    </div>

    <section class="mlbs-premium-card mlbs-stats-chart-card">
        <div class="mlbs-card-head">
            <h2><?php echo esc_html($bucket === 'day' ? __('Por dia', MLBS_TEXT_DOMAIN) : ($bucket === 'week' ? __('Por semana', MLBS_TEXT_DOMAIN) : __('Por mês', MLBS_TEXT_DOMAIN))); ?></h2>
            <div class="mlbs-chart-legend">
                <span><i class="is-imp"></i><?php echo esc_html__('Impressões', MLBS_TEXT_DOMAIN); ?></span>
                <span><i class="is-clk"></i><?php echo esc_html__('Cliques', MLBS_TEXT_DOMAIN); ?></span>
            </div>
        </div>
        <?php if (! $has_data) : ?>
            <div class="mlbs-stats-empty"><?php echo esc_html__('Nenhuma impressão ou clique registrado neste período.', MLBS_TEXT_DOMAIN); ?></div>
        <?php else : ?>
            <div class="mlbs-chart-scroll">
                <svg class="mlbs-chart" viewBox="0 0 <?php echo (int) $svg_w; ?> <?php echo (int) $svg_h; ?>" role="img" aria-label="<?php echo esc_attr__('Gráfico de impressões e cliques no período', MLBS_TEXT_DOMAIN); ?>">
                    <?php for ($i = 0; $i <= 4; $i++) :
                        $y = $pad_t + $plot_h - ($plot_h * $i / 4); ?>
                        <line class="mlbs-grid" x1="<?php echo (int) $pad_l; ?>" x2="<?php echo (int) ($svg_w - $pad_r); ?>" y1="<?php echo esc_attr((string) round($y, 1)); ?>" y2="<?php echo esc_attr((string) round($y, 1)); ?>"/>
                        <text class="mlbs-axis is-imp" x="<?php echo (int) ($pad_l - 8); ?>" y="<?php echo esc_attr((string) round($y + 4, 1)); ?>" text-anchor="end"><?php echo esc_html(number_format_i18n($scale_imp * $i / 4)); ?></text>
                        <text class="mlbs-axis is-clk" x="<?php echo (int) ($svg_w - $pad_r + 8); ?>" y="<?php echo esc_attr((string) round($y + 4, 1)); ?>" text-anchor="start"><?php echo esc_html(number_format_i18n($scale_clk * $i / 4, $scale_clk < 4 ? 1 : 0)); ?></text>
                    <?php endfor; ?>
                    <?php
                    $line = array();
                    foreach ($points as $i => $pt) :
                        $cx = $pad_l + $slot * $i + $slot / 2;
                        $bh = $plot_h * ($pt['impression'] / $scale_imp);
                        $cy = $pad_t + $plot_h - $plot_h * ($pt['click'] / $scale_clk);
                        $line[] = round($cx, 1) . ',' . round($cy, 1);
                        ?>
                        <g class="mlbs-pt">
                            <title><?php echo esc_html($pt['label'] . ' — ' . sprintf(__('%1$s impressões · %2$s cliques · CTR %3$s', MLBS_TEXT_DOMAIN), number_format_i18n($pt['impression']), number_format_i18n($pt['click']), $mlbs_pct(MLBS_Stats::ctr($pt['click'], $pt['impression'])))); ?></title>
                            <rect class="mlbs-hit" x="<?php echo esc_attr((string) round($pad_l + $slot * $i, 1)); ?>" y="<?php echo (int) $pad_t; ?>" width="<?php echo esc_attr((string) round($slot, 1)); ?>" height="<?php echo (int) $plot_h; ?>"/>
                            <rect class="mlbs-bar" x="<?php echo esc_attr((string) round($cx - $bar_w / 2, 1)); ?>" y="<?php echo esc_attr((string) round($pad_t + $plot_h - $bh, 1)); ?>" width="<?php echo esc_attr((string) round($bar_w, 1)); ?>" height="<?php echo esc_attr((string) round(max(0, $bh), 1)); ?>" rx="2"/>
                        </g>
                        <?php if ($i % $label_every === 0) : ?>
                            <text class="mlbs-axis" x="<?php echo esc_attr((string) round($cx, 1)); ?>" y="<?php echo (int) ($svg_h - 10); ?>" text-anchor="middle"><?php echo esc_html($pt['short']); ?></text>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <polyline class="mlbs-line" points="<?php echo esc_attr(implode(' ', $line)); ?>"/>
                    <?php if ($n <= 62) : foreach ($line as $xy) : list($lx, $ly) = explode(',', $xy); ?>
                        <circle class="mlbs-dot" cx="<?php echo esc_attr($lx); ?>" cy="<?php echo esc_attr($ly); ?>" r="3"/>
                    <?php endforeach; endif; ?>
                </svg>
            </div>
        <?php endif; ?>
    </section>

    <section class="mlbs-premium-card mlbs-stats-table-card">
        <div class="mlbs-card-head">
            <h2><?php echo esc_html__('Desempenho por banner', MLBS_TEXT_DOMAIN); ?></h2>
            <span class="mlbs-pill is-soft"><?php echo esc_html(sprintf(_n('%s banner', '%s banners', count($rows), MLBS_TEXT_DOMAIN), number_format_i18n(count($rows)))); ?></span>
        </div>
        <?php if (! $rows) : ?>
            <div class="mlbs-stats-empty"><?php echo esc_html__('Nenhum banner cadastrado neste filtro.', MLBS_TEXT_DOMAIN); ?></div>
        <?php else : ?>
            <div class="mlbs-table-scroll">
                <table class="mlbs-stats-table">
                    <thead>
                        <tr>
                            <th class="col-rank">#</th>
                            <th class="col-banner"><?php echo $sort_link('title', __('Banner', MLBS_TEXT_DOMAIN)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></th>
                            <th><?php echo esc_html__('Grupo', MLBS_TEXT_DOMAIN); ?></th>
                            <th><?php echo esc_html__('Situação', MLBS_TEXT_DOMAIN); ?></th>
                            <th class="num"><?php echo $sort_link('impressions', __('Impressões', MLBS_TEXT_DOMAIN)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></th>
                            <th class="num"><?php echo $sort_link('clicks', __('Cliques', MLBS_TEXT_DOMAIN)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></th>
                            <th class="num col-ctr"><?php echo $sort_link('ctr', __('CTR', MLBS_TEXT_DOMAIN)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></th>
                            <th class="num is-muted"><?php echo $sort_link('total_impressions', __('Impressões desde o início', MLBS_TEXT_DOMAIN)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></th>
                            <th class="num is-muted"><?php echo $sort_link('total_clicks', __('Cliques desde o início', MLBS_TEXT_DOMAIN)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $max_row_imp = 0;
                        foreach ($rows as $r) {
                            $max_row_imp = max($max_row_imp, $r['impressions']);
                        }
                        foreach ($rows as $i => $r) :
                            $share = $max_row_imp > 0 ? round(100 * $r['impressions'] / $max_row_imp, 1) : 0;
                            ?>
                            <tr>
                                <td class="col-rank"><?php echo (int) ($i + 1); ?></td>
                                <td class="col-banner">
                                    <a class="mlbs-stats-banner" href="<?php echo esc_url(get_edit_post_link($r['id'])); ?>">
                                        <span class="mlbs-stats-thumb"><?php echo $r['thumb'] ? wp_get_attachment_image($r['thumb'], 'thumbnail', false, array('alt' => '')) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                                        <span class="mlbs-stats-title"><?php echo esc_html($r['title']); ?></span>
                                    </a>
                                </td>
                                <td><?php echo esc_html($r['groups'] ? implode(', ', $r['groups']) : '—'); ?></td>
                                <td><span class="mlbs-status"><?php echo esc_html($r['display']); ?></span></td>
                                <td class="num">
                                    <strong><?php echo esc_html($mlbs_num($r['impressions'])); ?></strong>
                                    <span class="mlbs-share" aria-hidden="true"><i style="width:<?php echo esc_attr((string) $share); ?>%"></i></span>
                                </td>
                                <td class="num"><strong><?php echo esc_html($mlbs_num($r['clicks'])); ?></strong></td>
                                <td class="num col-ctr"><?php echo esc_html($mlbs_pct($r['ctr'])); ?></td>
                                <td class="num is-muted"><?php echo esc_html($mlbs_num($r['total_impressions'])); ?></td>
                                <td class="num is-muted"><?php echo esc_html($mlbs_num($r['total_clicks'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="mlbs-stats-note"><?php echo esc_html__('Impressão = o banner foi carregado na página de um visitante. Clique = o visitante clicou no banner (só banners com link). Robôs e pré-carregamentos não contam. "Desde o início" inclui o histórico anterior à medição diária.', MLBS_TEXT_DOMAIN); ?></p>
        <?php endif; ?>
    </section>

    <?php if ($group_rows) : ?>
        <section class="mlbs-premium-card mlbs-stats-table-card">
            <div class="mlbs-card-head">
                <h2><?php echo esc_html__('Por grupo', MLBS_TEXT_DOMAIN); ?></h2>
            </div>
            <div class="mlbs-table-scroll">
                <table class="mlbs-stats-table">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Grupo', MLBS_TEXT_DOMAIN); ?></th>
                            <th class="num"><?php echo esc_html__('Banners', MLBS_TEXT_DOMAIN); ?></th>
                            <th class="num"><?php echo esc_html__('Impressões', MLBS_TEXT_DOMAIN); ?></th>
                            <th class="num"><?php echo esc_html__('Cliques', MLBS_TEXT_DOMAIN); ?></th>
                            <th class="num col-ctr"><?php echo esc_html__('CTR', MLBS_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($group_rows as $g) : ?>
                            <tr>
                                <td><a href="<?php echo esc_url(add_query_arg(array_merge($base_args, array('group' => $g['id'])), admin_url('admin.php'))); ?>"><?php echo esc_html($g['name']); ?></a></td>
                                <td class="num"><?php echo esc_html($mlbs_num($g['banners'])); ?></td>
                                <td class="num"><strong><?php echo esc_html($mlbs_num($g['impressions'])); ?></strong></td>
                                <td class="num"><strong><?php echo esc_html($mlbs_num($g['clicks'])); ?></strong></td>
                                <td class="num col-ctr"><?php echo esc_html($mlbs_pct($g['ctr'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
</div>
