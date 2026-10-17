<?php
/**
 * Licença do ML Banner Suite.
 *
 * 3.1.0: a licença passa a ser emitida e conferida pelo site tools.mlopesdesign.com.br
 * (módulo ML License Hub, cliente ML_License_Client_V2). O servidor antigo
 * (license.mlopesdesign.com.br / api/license.php) não é mais consultado. Regras do padrão ML:
 * 1 licença = 1 site (o Pacote Agência vale em vários sites); teste grátis de 30 dias
 * por site; sem teste/licença válidos, o painel fica travado para criar/editar/apagar/
 * ordenar/salvar — os banners e anúncios publicados continuam no ar e nenhum dado é apagado.
 *
 * Quem já usava o plugin antes da 3.1.0 recebe o teste grátis automaticamente na
 * primeira visita ao painel (o site não trava de surpresa após a atualização).
 *
 * A option antiga (mlbs_license_state) e os campos antigos de servidor/Product ID em
 * mlbs_settings são preservados no banco (não são mais usados).
 *
 * @package MLBannerSuite
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/class-ml-license-client.php';

class MLBS_License_Manager
{
    const API_BASE    = 'https://tools.mlopesdesign.com.br/wp-json/mllh/v1';
    const PUBLIC_KEY  = 'VGZQp2X3RJMuDmQBlIEXS4fqsZD1CnFlIJgfVtY/pHo=';
    const PRODUCT_ID  = 'ml-banner-suite';
    const BUY_URL     = 'https://tools.mlopesdesign.com.br/precos/';
    const ACCOUNT_URL = 'https://tools.mlopesdesign.com.br/minha-conta/';
    const AUTO_TRIAL  = 'mlbs_auto_trial_tried';
    const GROUP_TAX   = 'mlbs_group';
    const CAT_TAX     = 'mlbs_category';

    /** @var ML_License_Client_V2|null */
    private static $client = null;

    /** @var bool|null Resultado guardado durante a requisição (zera quando a licença muda). */
    private static $locked = null;

    /** @var bool */
    private static $booted = false;

    private $settings;

    public function __construct($settings = null)
    {
        $this->settings = $settings;
    }

    public static function client()
    {
        if (null === self::$client) {
            self::$client = new ML_License_Client_V2(array(
                'api_base'   => self::API_BASE,
                'product'    => self::PRODUCT_ID,
                'public_key' => self::PUBLIC_KEY,
                'version'    => MLBS_VERSION,
            ));
        }
        return self::$client;
    }

    /** Teste ou licença válidos neste site. */
    public static function is_active()
    {
        return self::client()->is_premium();
    }

    /** Painel travado (sem teste nem licença). */
    public static function is_locked()
    {
        if (null === self::$locked) {
            self::$locked = ! self::is_active();
        }
        return self::$locked;
    }

    public static function forget()
    {
        self::$locked = null;
    }

    /* ------------------------------------------------------------------ ganchos */

    public static function boot()
    {
        if (self::$booted) {
            return;
        }
        self::$booted = true;

        self::client(); // agenda a renovação diária
        add_action('admin_init', array(__CLASS__, 'maybe_auto_trial'), 5);
        add_action('current_screen', array(__CLASS__, 'guard_screens'));
        add_filter('map_meta_cap', array(__CLASS__, 'lock_caps'), 10, 4);
        add_filter('pre_insert_term', array(__CLASS__, 'lock_new_term'), 10, 2);
        add_action('admin_notices', array(__CLASS__, 'locked_notice'), 5);
        $opt = self::client()->option_name();
        foreach (array('added_option', 'updated_option', 'deleted_option') as $hook) {
            add_action($hook, function ($name) use ($opt) {
                if ($name === $opt) {
                    MLBS_License_Manager::forget();
                }
            });
        }
    }

    /** Primeira visita ao painel: começa o teste grátis sozinho (uma tentativa por site). */
    public static function maybe_auto_trial()
    {
        if (wp_doing_ajax() || ! current_user_can('manage_options') || get_option(self::AUTO_TRIAL)) {
            return;
        }
        update_option(self::AUTO_TRIAL, time(), false);
        $state = self::client()->state();
        if (empty($state['active']) && 'none' === $state['mode'] && empty($state['trial_used'])) {
            self::client()->start_trial();
        }
    }

    /** Sem licença: não abre as telas de criar/editar banner e grupo. */
    public static function guard_screens($screen)
    {
        if (! $screen || ! self::is_locked()) {
            return;
        }
        $editing_banner = MLBS_Post_Types::POST_TYPE === $screen->post_type && 'post' === $screen->base;
        $editing_group  = in_array($screen->taxonomy, array(self::GROUP_TAX, self::CAT_TAX), true) && 'term' === $screen->base;
        if ($editing_banner || $editing_group) {
            self::redirect_with_notice('error', __('Ative a licença (ou comece o teste grátis) para criar e editar banners e grupos. Os banners publicados continuam no ar.', MLBS_TEXT_DOMAIN));
        }
    }

    /** Sem licença: banners e grupos ficam só para leitura (as listas continuam visíveis). */
    public static function lock_caps($caps, $cap, $user_id, $args)
    {
        $post_caps = array('edit_post', 'delete_post', 'publish_post');
        $term_caps = array('edit_term', 'delete_term');
        if (! in_array($cap, $post_caps, true) && ! in_array($cap, $term_caps, true)) {
            return $caps;
        }
        $id = isset($args[0]) ? (int) $args[0] : 0;
        if (! $id) {
            return $caps;
        }
        if (in_array($cap, $post_caps, true)) {
            if (get_post_type($id) !== MLBS_Post_Types::POST_TYPE) {
                return $caps;
            }
        } else {
            $term = get_term($id);
            if (! $term || is_wp_error($term) || ! in_array($term->taxonomy, array(self::GROUP_TAX, self::CAT_TAX), true)) {
                return $caps;
            }
        }
        return self::is_locked() ? array('do_not_allow') : $caps;
    }

    public static function lock_new_term($term, $taxonomy)
    {
        if (in_array($taxonomy, array(self::GROUP_TAX, self::CAT_TAX), true) && self::is_locked()) {
            return new WP_Error('mlbs_locked', __('Ative a licença do ML Banner Suite para criar grupos.', MLBS_TEXT_DOMAIN));
        }
        return $term;
    }

    /** Para ações de gravação do próprio painel (clonar, ordenar, imagens, AdSense). */
    public static function guard_write($ajax = false)
    {
        if (! self::is_locked()) {
            return;
        }
        $msg = __('Ative a licença (ou comece o teste grátis) para alterar o ML Banner Suite. Os banners e anúncios publicados continuam no ar.', MLBS_TEXT_DOMAIN);
        if ($ajax) {
            wp_send_json_error(array('message' => $msg), 403);
        }
        self::redirect_with_notice('error', $msg);
    }

    public static function locked_notice()
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (! $screen || ! current_user_can('manage_options') || ! self::is_locked()) {
            return;
        }
        $ours = MLBS_Post_Types::POST_TYPE === $screen->post_type
            || in_array($screen->taxonomy, array(self::GROUP_TAX, self::CAT_TAX), true)
            || false !== strpos((string) $screen->id, 'mlbs');
        if (! $ours || false !== strpos((string) $screen->id, 'mlbs-license')) {
            return;
        }
        printf(
            '<div class="notice notice-warning inline mlbs-lock-notice"><p><strong>ML Banner Suite:</strong> %s <a href="%s">%s</a></p></div>',
            esc_html__('sem licença ativa — o painel está só para leitura. Os banners e anúncios publicados continuam no ar.', MLBS_TEXT_DOMAIN),
            esc_url(MLBS_Helpers::admin_url('mlbs-license')),
            esc_html__('Ativar licença', MLBS_TEXT_DOMAIN)
        );
    }

    /* ------------------------------------------------------------------ tela */

    /** Estado pronto para a tela. */
    public function get_license_summary()
    {
        $state  = self::client()->state();
        $active = ! empty($state['active']);
        $mode   = (string) ($state['mode'] ?? 'none');
        $plan   = strtolower((string) ($state['plan'] ?? ''));
        $days   = $state['days_left'] ?? null;

        $plans = array(
            'trial'    => 'Teste grátis',
            'monthly'  => 'Mensal',
            'annual'   => 'Anual',
            'yearly'   => 'Anual',
            'lifetime' => 'Vitalícia',
            'manual'   => 'Cortesia',
            'courtesy' => 'Cortesia',
        );
        $plan_label = $plans[$plan] ?? ('' !== $plan ? ucfirst($plan) : '—');

        if ($active && 'trial' === $mode) {
            $label   = 'Teste';
            $tone    = 'trial';
            $message = null === $days ? 'Teste grátis ativo neste site.' : sprintf(1 === (int) $days ? 'Teste grátis ativo: falta %d dia.' : 'Teste grátis ativo: faltam %d dias.', (int) $days);
        } elseif ($active) {
            $label   = 'Ativa';
            $tone    = 'full';
            $message = 'Licença ativa neste site.';
        } else {
            $label = 'Sem licença';
            $tone  = 'free';
            if ('trial_expired' === ($state['status'] ?? '')) {
                $message = 'O teste grátis terminou. Ative uma licença para voltar a editar os banners. Os banners e anúncios publicados continuam no ar.';
            } elseif ('' !== (string) ($state['message'] ?? '')) {
                $message = (string) $state['message'];
            } else {
                $message = 'Comece o teste grátis de 30 dias ou ative a sua licença.';
            }
        }

        $expires_label = '';
        if (! empty($state['expires_at'])) {
            $ts = strtotime((string) $state['expires_at']);
            if ($ts) {
                $expires_label = wp_date(get_option('date_format', 'd/m/Y'), $ts);
            }
        }

        return array(
            'active'          => $active,
            'mode'            => $mode,
            'is_trial'        => $active && 'trial' === $mode,
            'label'           => $label,
            'tone'            => $tone,
            'message'         => $message,
            'plan_label'      => $plan_label,
            'expires_label'   => $expires_label,
            'days_left'       => $days,
            'key_masked'      => (string) ($state['key_masked'] ?? ''),
            'can_start_trial' => ! $active && empty($state['trial_used']) && 'license' !== $mode,
            'domain'          => (string) wp_parse_url(home_url(), PHP_URL_HOST),
        );
    }

    /* ------------------------------------------------------------------ ações */

    public function handle_activate_request()
    {
        $this->guard_admin_request('mlbs_activate_license');
        $key    = isset($_POST['license_key']) ? sanitize_text_field(wp_unslash($_POST['license_key'])) : '';
        $result = self::client()->activate($key);
        if (is_wp_error($result)) {
            self::redirect_with_notice('error', $result->get_error_message());
        }
        self::redirect_with_notice('success', __('Licença ativada neste site.', MLBS_TEXT_DOMAIN));
    }

    /** Começa o teste grátis ou confere a licença agora. */
    public function handle_sync_request()
    {
        $this->guard_admin_request('mlbs_sync_license');
        $op     = isset($_POST['mlbs_op']) ? sanitize_key(wp_unslash($_POST['mlbs_op'])) : 'check';
        $result = 'trial' === $op ? self::client()->start_trial() : self::client()->refresh();
        if (is_wp_error($result)) {
            self::redirect_with_notice('error', $result->get_error_message());
        }
        self::redirect_with_notice('success', 'trial' === $op ? __('Teste grátis de 30 dias ativo neste site.', MLBS_TEXT_DOMAIN) : __('Licença conferida.', MLBS_TEXT_DOMAIN));
    }

    public function handle_remove_request()
    {
        $this->guard_admin_request('mlbs_remove_license');
        self::client()->deactivate();
        self::redirect_with_notice('success', __('Licença removida deste site. A chave pode ser usada em outro site.', MLBS_TEXT_DOMAIN));
    }

    private function guard_admin_request($nonce_action)
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Permissão insuficiente.', MLBS_TEXT_DOMAIN));
        }
        check_admin_referer($nonce_action);
    }

    public static function redirect_with_notice($type, $message)
    {
        $url = add_query_arg(array(
            'page'             => 'mlbs-license',
            'mlbs_notice_type' => rawurlencode($type),
            'mlbs_notice'      => rawurlencode($message),
        ), admin_url('admin.php'));
        wp_safe_redirect($url);
        exit;
    }
}
