<?php
/**
 * Official Plugin Update Checker integration plus reliable plugin details modal.
 *
 * @package MLBannerSuite
 */

if (! defined('ABSPATH')) {
    exit;
}

final class MLBS_Plugin_Updater
{
    const INFO_TRANSIENT = 'mlbs_plugin_release_info_v2';

    private $plugin_file;
    private $slug;
    private $repo_url;
    private $branch;
    private $checker = null;

    public function __construct($plugin_file, $slug, $repo_url, $branch = 'main')
    {
        $this->plugin_file = (string) $plugin_file;
        $this->slug        = (string) $slug;
        $this->repo_url    = untrailingslashit((string) $repo_url);
        $this->branch      = (string) $branch;
    }

    public function bootstrap()
    {
        $library = MLBS_PLUGIN_DIR . 'lib/plugin-update-checker/plugin-update-checker.php';

        if (file_exists($library) && is_readable($library)) {
            require_once $library;

            if (class_exists('YahnisElsts\PluginUpdateChecker\v5\PucFactory')) {
                $this->checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
                    $this->repo_url,
                    $this->plugin_file,
                    $this->slug
                );

                if (is_object($this->checker)) {
                    if (method_exists($this->checker, 'setBranch')) {
                        $this->checker->setBranch($this->branch);
                    }

                    if (method_exists($this->checker, 'getVcsApi')) {
                        $api = $this->checker->getVcsApi();

                        if (is_object($api) && method_exists($api, 'enableReleaseAssets')) {
                            $api->enableReleaseAssets(
                                '/(^|\/)(ml-banner-suite|ml-banner-suite-[^\/]+)\.zip($|[?&#])/i',
                                \YahnisElsts\PluginUpdateChecker\v5p6\Vcs\Api::REQUIRE_RELEASE_ASSETS
                            );
                        }
                    }

                    add_filter('puc_request_info_result-' . $this->slug, array($this, 'inject_puc_plugin_info'), 10, 2);
                }
            }
        }

        add_filter('plugins_api', array($this, 'inject_plugin_info_fallback'), 5, 3);
        add_action('install_plugins_pre_plugin-information', array($this, 'render_plugin_information_page'));
        add_action('upgrader_process_complete', array($this, 'clear_cached_release_info'), 10, 2);
        add_action('admin_init', array($this, 'maybe_force_refresh_release_info'));
    }

    public function clear_cached_release_info()
    {
        delete_site_transient(self::INFO_TRANSIENT);
    }

    public function maybe_force_refresh_release_info()
    {
        if (! is_admin() || ! current_user_can('update_plugins')) {
            return;
        }

        if (empty($_GET['force-check']) && empty($_GET['mlbs-refresh-release-info'])) {
            return;
        }

        $this->clear_cached_release_info();
    }

    public function inject_puc_plugin_info($plugin_info, $result = null)
    {
        if (is_object($plugin_info) && ! empty($plugin_info->name) && ! empty($plugin_info->version)) {
            return $plugin_info;
        }

        $fallback = $this->build_puc_plugin_info();
        return $fallback ? $fallback : $plugin_info;
    }


    public function render_plugin_information_page()
    {
        if (! is_admin()) {
            return;
        }

        $tab    = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : '';
        $plugin = isset($_GET['plugin']) ? sanitize_text_field(wp_unslash($_GET['plugin'])) : '';

        if ($tab !== 'plugin-information' || ! $this->is_matching_slug($plugin)) {
            return;
        }

        $plugin_info = $this->build_puc_plugin_info();
        if (! is_object($plugin_info)) {
            return;
        }

        $section  = isset($_GET['section']) ? sanitize_key(wp_unslash($_GET['section'])) : 'description';
        $sections = isset($plugin_info->sections) && is_array($plugin_info->sections) ? $plugin_info->sections : array();

        if (! isset($sections[$section])) {
            $section = isset($sections['description']) ? 'description' : key($sections);
        }

        if (! is_string($section) || $section === '') {
            $section = 'description';
        }

        $name         = isset($plugin_info->name) ? (string) $plugin_info->name : 'ML Banner Suite';
        $version      = isset($plugin_info->version) ? (string) $plugin_info->version : '';
        $author       = isset($plugin_info->author) ? (string) $plugin_info->author : 'Marcio Lopes';
        $homepage     = isset($plugin_info->homepage) ? (string) $plugin_info->homepage : MLBS_WEBSITE_URL;
        $requires     = isset($plugin_info->requires) ? (string) $plugin_info->requires : '6.0';
        $requires_php = isset($plugin_info->requires_php) ? (string) $plugin_info->requires_php : '7.4';
        $tested       = isset($plugin_info->tested) ? (string) $plugin_info->tested : '';
        $updated      = isset($plugin_info->last_updated) ? (string) $plugin_info->last_updated : '';
        $download     = isset($plugin_info->download_url) ? (string) $plugin_info->download_url : '';
        $icon_url     = MLBS_PLUGIN_URL . 'assets/icon-256x256.png';

        $base_url = add_query_arg(
            array(
                'tab'       => 'plugin-information',
                'plugin'    => $this->slug,
                'TB_iframe' => 'true',
                'width'     => isset($_GET['width']) ? absint($_GET['width']) : 772,
                'height'    => isset($_GET['height']) ? absint($_GET['height']) : 851,
            ),
            admin_url('plugin-install.php')
        );

        $labels = array(
            'description'  => __('Descrição', 'ml-banner-suite'),
            'installation' => __('Instalação', 'ml-banner-suite'),
            'faq'          => __('FAQ', 'ml-banner-suite'),
            'changelog'    => __('Registro de alterações', 'ml-banner-suite'),
            'screenshots'  => __('Telas', 'ml-banner-suite'),
            'reviews'      => __('Comentários', 'ml-banner-suite'),
        );

        echo '<!doctype html><html ' . get_language_attributes() . '><head>';
        echo '<meta charset="' . esc_attr(get_bloginfo('charset')) . '">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<title>' . esc_html($name) . '</title>';
        wp_admin_css('install', true);
        wp_admin_css('plugin-install', true);
        wp_admin_css('thickbox', true);
        echo '<style>
            :root{
                --mlbs-accent:#155e6f;
                --mlbs-accent-strong:#0e596a;
                --mlbs-accent-soft:#dcedf1;
                --mlbs-border:#bfd3d8;
                --mlbs-text:#0f2b33;
                --mlbs-muted:#5c7279;
                --mlbs-bg:#f4f7f8;
                --mlbs-card:#ffffff;
                --mlbs-radius-xl:22px;
                --mlbs-radius-lg:18px;
                --mlbs-radius-pill:999px;
            }
            html,body#plugin-information{background:var(--mlbs-bg);color:var(--mlbs-text);}
            body#plugin-information{margin:0;padding:12px;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;}
            #plugin-information-scrollable{background:transparent;border:0;overflow:visible;padding:0;}
            .mlbs-plugin-modal{max-width:1120px;margin:0 auto;}
            .mlbs-shell{
                background:var(--mlbs-card);
                border:1px solid var(--mlbs-border);
                border-left:8px solid var(--mlbs-accent);
                border-radius:var(--mlbs-radius-xl);
                padding:28px 28px 10px;
                box-shadow:0 10px 28px rgba(15,43,51,.06);
            }
            .mlbs-shell-top{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;}
            .mlbs-kicker{margin:0 0 12px;font-size:13px;line-height:1.2;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--mlbs-accent-strong);}
            .mlbs-title-row{display:flex;align-items:center;gap:12px;flex-wrap:wrap;}
            .mlbs-title{margin:0;font-size:34px;line-height:1.08;font-weight:500;color:var(--mlbs-text);}
            .mlbs-version{
                display:inline-flex;align-items:center;justify-content:center;min-height:36px;padding:0 14px;border-radius:var(--mlbs-radius-pill);
                background:var(--mlbs-accent-soft);color:var(--mlbs-accent-strong);font-size:13px;font-weight:700;
            }
            .mlbs-subtitle{margin:12px 0 0;font-size:15px;line-height:1.7;color:#35525b;max-width:860px;}
            .mlbs-badges{display:flex;flex-wrap:wrap;gap:10px;justify-content:flex-end;}
            .mlbs-badge{
                display:inline-flex;align-items:center;justify-content:center;min-height:36px;padding:0 14px;border-radius:var(--mlbs-radius-pill);
                border:1px solid var(--mlbs-border);background:#fff;color:var(--mlbs-accent-strong);font-size:12px;font-weight:700;text-decoration:none;
            }
            .mlbs-hero{margin-top:18px;display:grid;grid-template-columns:112px minmax(0,1fr);gap:18px;align-items:start;}
            .mlbs-brand-mark{
                background:#fff;border:1px solid var(--mlbs-border);border-radius:26px;padding:16px;height:112px;width:112px;
                display:flex;align-items:center;justify-content:center;box-shadow:0 6px 18px rgba(15,43,51,.05);
            }
            .mlbs-brand-mark img{display:block;max-width:100%;max-height:100%;object-fit:contain;}
            .mlbs-stats{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;}
            .mlbs-stat{
                background:linear-gradient(180deg, rgba(27,111,130,.90) 0%, rgba(21,94,111,.88) 100%);
                border:1px solid rgba(255,255,255,.12);border-radius:20px;padding:18px 18px 16px;color:#fff;
                box-shadow:inset 0 1px 0 rgba(255,255,255,.06);
            }
            .mlbs-stat-label{display:block;margin-bottom:8px;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:rgba(255,255,255,.82);}
            .mlbs-stat-value{display:block;font-size:18px;line-height:1.25;font-weight:700;color:#fff;}
            .mlbs-tabbar{display:flex;gap:10px;flex-wrap:wrap;margin:18px 0 18px;padding:0;}
            .mlbs-tab{
                display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:0 16px;border-radius:var(--mlbs-radius-pill);
                border:1px solid var(--mlbs-border);background:#fff;color:var(--mlbs-text);text-decoration:none;font-weight:600;
            }
            .mlbs-tab:hover{border-color:var(--mlbs-accent);color:var(--mlbs-accent);}
            .mlbs-tab.is-active{background:var(--mlbs-accent);border-color:var(--mlbs-accent);color:#fff;}
            .mlbs-content{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(280px,.75fr);gap:18px;align-items:start;}
            .mlbs-card{
                background:var(--mlbs-card);border:1px solid var(--mlbs-border);border-radius:24px;
                box-shadow:0 10px 26px rgba(15,43,51,.05);
            }
            .mlbs-main-card{padding:22px 22px 24px;overflow:hidden;}
            .mlbs-main-card h1,.mlbs-main-card h2,.mlbs-main-card h3,.mlbs-main-card h4{color:var(--mlbs-text);}
            .mlbs-main-card p,.mlbs-main-card li{font-size:15px;line-height:1.75;color:var(--mlbs-text);}
            .mlbs-main-card ul{padding-left:20px;}
            .mlbs-main-card code{background:#f5f8f9;border:1px solid #d8e5e8;border-radius:12px;padding:3px 8px;color:var(--mlbs-accent-strong);}
            .mlbs-side-card{padding:22px;}
            .mlbs-side-title{margin:0 0 14px;font-size:14px;font-weight:700;letter-spacing:.10em;text-transform:uppercase;color:var(--mlbs-accent-strong);}
            .mlbs-meta-list{display:grid;gap:12px;margin:0;padding:0;list-style:none;}
            .mlbs-meta-item{background:#f5f8f9;border:1px solid #dbe7ea;border-radius:16px;padding:14px 15px;}
            .mlbs-meta-label{display:block;font-size:12px;line-height:1.3;color:var(--mlbs-muted);margin-bottom:6px;}
            .mlbs-meta-value{display:block;font-size:15px;line-height:1.5;color:var(--mlbs-text);font-weight:600;word-break:break-word;}
            .mlbs-actions{display:grid;gap:10px;margin-top:18px;}
            .mlbs-btn{
                display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:0 16px;border-radius:16px;
                text-decoration:none;font-weight:700;border:1px solid var(--mlbs-border);transition:all .15s ease;
            }
            .mlbs-btn-primary{background:var(--mlbs-accent);border-color:var(--mlbs-accent);color:#fff;}
            .mlbs-btn-primary:hover{background:var(--mlbs-accent-strong);border-color:var(--mlbs-accent-strong);color:#fff;}
            .mlbs-btn-secondary{background:#fff;color:var(--mlbs-accent-strong);}
            .mlbs-btn-secondary:hover{background:#f1f7f8;color:var(--mlbs-accent-strong);}
            .mlbs-side-note{margin:14px 0 0;font-size:13px;line-height:1.6;color:var(--mlbs-muted);}
            @media (max-width: 900px){
                .mlbs-shell{padding:22px 18px 10px;}
                .mlbs-shell-top,.mlbs-content{display:block;}
                .mlbs-badges{justify-content:flex-start;margin-top:10px;}
                .mlbs-hero{grid-template-columns:1fr;}
                .mlbs-stats,.mlbs-content{grid-template-columns:1fr;}
                .mlbs-title{font-size:30px;}
                .mlbs-side-card{margin-top:18px;}
            }
            @media (max-width: 640px){
                body#plugin-information{padding:8px;}
                .mlbs-shell{padding:18px 14px 8px;}
                .mlbs-title{font-size:28px;}
                .mlbs-hero{gap:14px;}
                .mlbs-brand-mark{height:92px;width:92px;border-radius:22px;}
                .mlbs-stats{grid-template-columns:1fr;}
                .mlbs-main-card,.mlbs-side-card{padding:18px;}
                .mlbs-tab{width:100%;}
            }
        </style>';
        echo '</head><body id="plugin-information" class="wp-core-ui">';
        echo '<div class="mlbs-plugin-modal">';
        echo '<section class="mlbs-shell">';
        echo '<div class="mlbs-shell-top">';
        echo '<div class="mlbs-shell-head">';
        echo '<p class="mlbs-kicker">' . esc_html__('Painel profissional', 'ml-banner-suite') . '</p>';
        echo '<div class="mlbs-title-row"><h1 class="mlbs-title">' . esc_html($name) . '</h1>' . (! empty($version) ? '<span class="mlbs-version">v' . esc_html($version) . '</span>' : '') . '</div>';
        echo '<p class="mlbs-subtitle">' . esc_html__('Gestão comercial de banners com grupos, shortcodes, agendamento, organização visual e painel administrativo com identidade própria.', 'ml-banner-suite') . '</p>';
        echo '</div>';
        echo '<div class="mlbs-badges">';
        echo '<span class="mlbs-badge">' . esc_html__('GitHub Ready', 'ml-banner-suite') . '</span>';
        echo '<span class="mlbs-badge">PT-BR / EN / ES</span>';
        echo '<span class="mlbs-badge">' . esc_html__('Base estável', 'ml-banner-suite') . '</span>';
        echo '</div>';
        echo '</div>';
        echo '<div class="mlbs-hero">';
        echo '<div class="mlbs-brand-mark"><img src="' . esc_url($icon_url) . '" alt="' . esc_attr($name) . '"></div>';
        echo '<div class="mlbs-stats">';
        echo '<div class="mlbs-stat"><span class="mlbs-stat-label">' . esc_html__('Autor', 'ml-banner-suite') . '</span><span class="mlbs-stat-value">' . esc_html(wp_strip_all_tags($author)) . '</span></div>';
        echo '<div class="mlbs-stat"><span class="mlbs-stat-label">' . esc_html__('Última atualização', 'ml-banner-suite') . '</span><span class="mlbs-stat-value">' . esc_html($updated !== '' ? $updated : gmdate('Y-m-d')) . '</span></div>';
        echo '<div class="mlbs-stat"><span class="mlbs-stat-label">' . esc_html__('WordPress mínimo', 'ml-banner-suite') . '</span><span class="mlbs-stat-value">' . esc_html($requires) . '</span></div>';
        echo '<div class="mlbs-stat"><span class="mlbs-stat-label">' . esc_html__('PHP mínimo', 'ml-banner-suite') . '</span><span class="mlbs-stat-value">' . esc_html($requires_php) . '</span></div>';
        echo '</div>';
        echo '</div>';
        echo '</section>';

        echo '<nav class="mlbs-tabbar" aria-label="' . esc_attr__('Seções do plugin', 'ml-banner-suite') . '">';
        foreach ($sections as $slug => $unused_content) {
            $label = isset($labels[$slug]) ? $labels[$slug] : ucfirst((string) $slug);
            $class = $slug === $section ? 'mlbs-tab is-active' : 'mlbs-tab';
            echo '<a class="' . esc_attr($class) . '" href="' . esc_url(add_query_arg('section', $slug, $base_url)) . '">' . esc_html($label) . '</a>';
        }
        echo '</nav>';

        echo '<div class="mlbs-content">';
        echo '<article class="mlbs-card mlbs-main-card">';
        echo isset($sections[$section]) ? wp_kses_post($sections[$section]) : '';
        echo '</article>';

        echo '<aside class="mlbs-card mlbs-side-card">';
        echo '<h2 class="mlbs-side-title">' . esc_html__('Resumo técnico', 'ml-banner-suite') . '</h2>';
        echo '<ul class="mlbs-meta-list">';
        echo '<li class="mlbs-meta-item"><span class="mlbs-meta-label">' . esc_html__('Versão atual', 'ml-banner-suite') . '</span><span class="mlbs-meta-value">' . esc_html($version) . '</span></li>';
        echo '<li class="mlbs-meta-item"><span class="mlbs-meta-label">' . esc_html__('Site oficial', 'ml-banner-suite') . '</span><span class="mlbs-meta-value">' . ($homepage !== '' ? '<a href="' . esc_url($homepage) . '" target="_blank" rel="noopener noreferrer">' . esc_html($homepage) . '</a>' : '—') . '</span></li>';
        echo '<li class="mlbs-meta-item"><span class="mlbs-meta-label">' . esc_html__('Compatível até', 'ml-banner-suite') . '</span><span class="mlbs-meta-value">' . esc_html($tested !== '' ? $tested : '—') . '</span></li>';
        echo '<li class="mlbs-meta-item"><span class="mlbs-meta-label">' . esc_html__('Slug', 'ml-banner-suite') . '</span><span class="mlbs-meta-value">' . esc_html($this->slug) . '</span></li>';
        echo '</ul>';

        echo '<div class="mlbs-actions">';
        if ($homepage !== '') {
            echo '<a class="mlbs-btn mlbs-btn-primary" href="' . esc_url($homepage) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Abrir site oficial', 'ml-banner-suite') . '</a>';
        }
        if ($download !== '') {
            echo '<a class="mlbs-btn mlbs-btn-secondary" href="' . esc_url($download) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Baixar pacote desta versão', 'ml-banner-suite') . '</a>';
        } else {
            echo '<a class="mlbs-btn mlbs-btn-secondary" href="' . esc_url(add_query_arg('section', 'changelog', $base_url)) . '">' . esc_html__('Ver changelog completo', 'ml-banner-suite') . '</a>';
        }
        echo '</div>';

        echo '<p class="mlbs-side-note">' . esc_html__('Modal alinhado ao padrão clean do painel, com contraste correto do ícone e sem alterar o motor de update.', 'ml-banner-suite') . '</p>';
        echo '</aside>';
        echo '</div>';
        echo '</div>';
        echo '</body></html>';
        exit;
    }

    public function inject_plugin_info_fallback($result, $action, $args)
    {
        if ($action !== 'plugin_information' || ! is_object($args)) {
            return $result;
        }

        $requested = '';
        if (! empty($args->slug)) {
            $requested = (string) $args->slug;
        } elseif (! empty($args->plugin)) {
            $requested = (string) $args->plugin;
        }

        if (! $this->is_matching_slug($requested)) {
            return $result;
        }

        $plugin_info = $this->build_puc_plugin_info();
        if (is_object($plugin_info)) {
            return (object) array(
                'name'          => isset($plugin_info->name) ? $plugin_info->name : 'ML Banner Suite',
                'slug'          => isset($plugin_info->slug) ? $plugin_info->slug : $this->slug,
                'version'       => isset($plugin_info->version) ? $plugin_info->version : '',
                'author'        => isset($plugin_info->author) ? $plugin_info->author : 'Marcio Lopes',
                'homepage'      => isset($plugin_info->homepage) ? $plugin_info->homepage : MLBS_WEBSITE_URL,
                'requires'      => isset($plugin_info->requires) ? $plugin_info->requires : '6.0',
                'requires_php'  => isset($plugin_info->requires_php) ? $plugin_info->requires_php : '7.4',
                'tested'        => isset($plugin_info->tested) ? $plugin_info->tested : '',
                'last_updated'  => isset($plugin_info->last_updated) ? $plugin_info->last_updated : '',
                'download_link' => isset($plugin_info->download_url) ? $plugin_info->download_url : '',
                'sections'      => isset($plugin_info->sections) && is_array($plugin_info->sections) ? $plugin_info->sections : array(),
                'banners'       => array(),
                'icons'         => array(
                    '1x' => MLBS_PLUGIN_URL . 'assets/icon-128x128.png',
                    '2x' => MLBS_PLUGIN_URL . 'assets/icon-256x256.png',
                ),
                'external'      => true,
            );
        }

        return $result;
    }

    private function is_matching_slug($requested)
    {
        $requested = trim((string) $requested);
        if ($requested === '') {
            return false;
        }

        $plugin_basename = plugin_basename($this->plugin_file);
        $plugin_dir      = dirname($plugin_basename);
        $plugin_file     = basename($plugin_basename, '.php');

        $candidates = array_filter(array_unique(array(
            $this->slug,
            $plugin_dir,
            $plugin_basename,
            $plugin_file,
            rawurlencode($this->slug),
            rawurlencode($plugin_dir),
            rawurlencode($plugin_basename),
        )));

        return in_array($requested, $candidates, true);
    }

    private function build_puc_plugin_info()
    {
        if (! function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugin_data = get_plugin_data($this->plugin_file, false, false);
        if (empty($plugin_data)) {
            return null;
        }

        $release      = $this->get_latest_release();
        $local_readme = $this->get_local_readme_sections();
        $version      = ! empty($release['version']) ? $release['version'] : (! empty($plugin_data['Version']) ? $plugin_data['Version'] : MLBS_VERSION);
        $description  = ! empty($plugin_data['Description']) ? wpautop(wp_kses_post($plugin_data['Description'])) : '';
        $changelog    = ! empty($release['body'])
            ? wpautop(wp_kses_post($release['body']))
            : (! empty($local_readme['changelog']) ? wpautop(wp_kses_post($local_readme['changelog'])) : __('Sem changelog disponível no momento.', 'ml-banner-suite'));

        $sections = array(
            'description'  => $description,
            'installation' => ! empty($local_readme['installation']) ? wpautop(wp_kses_post($local_readme['installation'])) : '',
            'changelog'    => $changelog,
        );

        if (! empty($local_readme['faq'])) {
            $sections['faq'] = wpautop(wp_kses_post($local_readme['faq']));
        }

        if (class_exists('YahnisElsts\PluginUpdateChecker\v5p6\Plugin\PluginInfo')) {
            $info = new \YahnisElsts\PluginUpdateChecker\v5p6\Plugin\PluginInfo();
            $info->name            = ! empty($plugin_data['Name']) ? $plugin_data['Name'] : 'ML Banner Suite';
            $info->slug            = $this->slug;
            $info->version         = $version;
            $info->homepage        = ! empty($plugin_data['PluginURI']) ? $plugin_data['PluginURI'] : MLBS_WEBSITE_URL;
            $info->author          = ! empty($plugin_data['AuthorName']) ? $plugin_data['AuthorName'] : (! empty($plugin_data['Author']) ? wp_strip_all_tags($plugin_data['Author']) : 'Marcio Lopes');
            $info->author_homepage = ! empty($plugin_data['AuthorURI']) ? $plugin_data['AuthorURI'] : 'https://mlopesdesign.com.br';
            $info->requires        = ! empty($plugin_data['RequiresWP']) ? $plugin_data['RequiresWP'] : '6.0';
            $info->requires_php    = ! empty($plugin_data['RequiresPHP']) ? $plugin_data['RequiresPHP'] : '7.4';
            $info->tested          = ! empty($plugin_data['Tested']) ? $plugin_data['Tested'] : '';
            $info->last_updated    = ! empty($release['published_at']) ? gmdate('Y-m-d', strtotime($release['published_at'])) : gmdate('Y-m-d');
            $info->download_url    = ! empty($release['package']) ? $release['package'] : '';
            $info->filename        = plugin_basename($this->plugin_file);
            $info->sections        = $sections;
            $info->icons           = array(
                '1x' => MLBS_PLUGIN_URL . 'assets/icon-128x128.png',
                '2x' => MLBS_PLUGIN_URL . 'assets/icon-256x256.png',
            );
            $info->banners         = array();
            return $info;
        }

        return (object) array(
            'name'            => ! empty($plugin_data['Name']) ? $plugin_data['Name'] : 'ML Banner Suite',
            'slug'            => $this->slug,
            'version'         => $version,
            'homepage'        => ! empty($plugin_data['PluginURI']) ? $plugin_data['PluginURI'] : MLBS_WEBSITE_URL,
            'author'          => ! empty($plugin_data['AuthorName']) ? $plugin_data['AuthorName'] : (! empty($plugin_data['Author']) ? wp_strip_all_tags($plugin_data['Author']) : 'Marcio Lopes'),
            'author_homepage' => ! empty($plugin_data['AuthorURI']) ? $plugin_data['AuthorURI'] : 'https://mlopesdesign.com.br',
            'requires'        => ! empty($plugin_data['RequiresWP']) ? $plugin_data['RequiresWP'] : '6.0',
            'requires_php'    => ! empty($plugin_data['RequiresPHP']) ? $plugin_data['RequiresPHP'] : '7.4',
            'tested'          => ! empty($plugin_data['Tested']) ? $plugin_data['Tested'] : '',
            'last_updated'    => ! empty($release['published_at']) ? gmdate('Y-m-d', strtotime($release['published_at'])) : gmdate('Y-m-d'),
            'download_url'    => ! empty($release['package']) ? $release['package'] : '',
            'filename'        => plugin_basename($this->plugin_file),
            'sections'        => $sections,
            'icons'           => array(
                '1x' => MLBS_PLUGIN_URL . 'assets/icon-128x128.png',
                '2x' => MLBS_PLUGIN_URL . 'assets/icon-256x256.png',
            ),
            'banners'         => array(),
        );
    }

    private function get_local_readme_sections()
    {
        $path = MLBS_PLUGIN_DIR . 'readme.txt';
        if (! file_exists($path) || ! is_readable($path)) {
            return array();
        }

        $content = file_get_contents($path);
        if (! is_string($content) || $content === '') {
            return array();
        }

        return array(
            'installation' => $this->extract_readme_section($content, 'Installation'),
            'faq'          => $this->extract_readme_section($content, 'FAQ'),
            'changelog'    => $this->extract_readme_section($content, 'Changelog'),
        );
    }

    private function extract_readme_section($content, $section)
    {
        $pattern = '/==\s*' . preg_quote($section, '/') . '\s*==\R(.*?)(?=\R==\s*[^=]+\s*==|\z)/is';
        if (! preg_match($pattern, (string) $content, $matches)) {
            return '';
        }

        return trim((string) $matches[1]);
    }

    private function get_latest_release()
    {
        $cached = get_site_transient(self::INFO_TRANSIENT);
        if (is_array($cached) && ! empty($cached['version'])) {
            return $cached;
        }

        $api_url = $this->build_release_api_url();
        if (! $api_url) {
            return array();
        }

        $response = wp_remote_get(
            $api_url,
            array(
                'timeout' => 20,
                'headers' => array(
                    'Accept'     => 'application/vnd.github+json',
                    'User-Agent' => 'ML-Banner-Suite/' . MLBS_VERSION,
                ),
            )
        );

        if (is_wp_error($response)) {
            return array();
        }

        if ((int) wp_remote_retrieve_response_code($response) !== 200) {
            return array();
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (! is_array($data)) {
            return array();
        }

        $version = '';
        if (! empty($data['tag_name'])) {
            $version = ltrim((string) $data['tag_name'], 'vV ');
        }

        if ($version === '' && ! empty($data['name']) && preg_match('/(\d+(?:\.\d+)+[A-Za-z0-9._-]*)/', (string) $data['name'], $m)) {
            $version = $m[1];
        }

        $package = '';
        if (! empty($data['assets']) && is_array($data['assets'])) {
            foreach ($data['assets'] as $asset) {
                if (empty($asset['browser_download_url']) || empty($asset['name'])) {
                    continue;
                }

                if (preg_match('/(^|\/)(ml-banner-suite|ml-banner-suite-[^\/]+)\.zip$/i', (string) $asset['name'])) {
                    $package = (string) $asset['browser_download_url'];
                    break;
                }
            }
        }

        $release = array(
            'version'      => $version,
            'body'         => ! empty($data['body']) ? (string) $data['body'] : '',
            'published_at' => ! empty($data['published_at']) ? (string) $data['published_at'] : '',
            'package'      => $package,
        );

        if (! empty($release['version'])) {
            set_site_transient(self::INFO_TRANSIENT, $release, 6 * HOUR_IN_SECONDS);
        }

        return $release;
    }

    private function build_release_api_url()
    {
        $parts = wp_parse_url($this->repo_url);
        if (! is_array($parts) || empty($parts['path'])) {
            return '';
        }

        $path = trim((string) $parts['path'], '/');
        if ($path === '') {
            return '';
        }

        return 'https://api.github.com/repos/' . $path . '/releases/latest';
    }
}
