<?php
/**
 * License integration.
 *
 * @package MLBannerSuite
 */

if (! defined('ABSPATH')) {
    exit;
}

class MLBS_License_Manager
{
    const OPTION_KEY = 'mlbs_license_state';

    private $settings;

    public function __construct(MLBS_Settings $settings)
    {
        $this->settings = $settings;
    }

    public static function get_default_state()
    {
        return array(
            'license_key'       => '',
            'status'            => 'free',
            'plan'              => 'free',
            'message'           => __('Versão Free funcionando por padrão.', MLBS_TEXT_DOMAIN),
            'domain'            => '',
            'site_url'          => home_url('/'),
            'home_url'          => home_url('/'),
            'last_validated_at' => '',
            'expires_at'        => '',
            'grace_until'       => '',
            'days_left'         => '',
            'valid'             => 0,
            'premium'           => 0,
            'feature_set'       => 'free',
            'license_source'    => 'local_free',
            'site_fingerprint'  => '',
            'http_code'         => '',
            'product_name'      => '',
            'trial_started'     => 0,
        );
    }

    public function get_state()
    {
        $saved = get_option(self::OPTION_KEY, array());
        $state = wp_parse_args(is_array($saved) ? $saved : array(), self::get_default_state());

        if ($state['site_fingerprint'] === '') {
            $state['site_fingerprint'] = $this->get_site_fingerprint();
        }
        if ($state['domain'] === '') {
            $state['domain'] = $this->get_domain();
        }
        if ($state['site_url'] === '') {
            $state['site_url'] = home_url('/');
        }
        if ($state['home_url'] === '') {
            $state['home_url'] = home_url('/');
        }

        return $state;
    }

    public function save_state(array $state)
    {
        $current = $this->get_state();
        $merged = array_merge($current, $state);
        update_option(self::OPTION_KEY, $merged);
        return $merged;
    }

    public function clear_state()
    {
        update_option(self::OPTION_KEY, self::get_default_state());
    }

    public function get_domain()
    {
        $host = wp_parse_url(home_url('/'), PHP_URL_HOST);
        $host = is_string($host) ? strtolower(trim($host)) : '';
        return preg_replace('/^www\./', '', $host);
    }

    public function get_site_fingerprint()
    {
        $product_id = strtolower(trim((string) $this->settings->get('license_product_id', 'ml-banner-suite')));
        $domain = strtolower(trim((string) $this->get_domain()));
        $identity = $domain !== '' ? $domain : strtolower(trim((string) home_url('/')));

        return hash('sha256', $product_id . '|' . $identity);
    }

    public function get_license_summary()
    {
        $state = $this->get_state();
        return array(
            'product_name'     => (string) $this->settings->get('license_product_name', 'ML Banner Suite'),
            'product_id'       => (string) $this->settings->get('license_product_id', 'ml-banner-suite'),
            'server_url'       => (string) $this->settings->get('license_server_url', ''),
            'domain'           => $this->get_domain(),
            'site_fingerprint' => $this->get_site_fingerprint(),
            'state'            => $state,
            'plan_label'       => $this->get_plan_label($state['plan']),
            'status_label'     => $this->get_status_label($state['status']),
        );
    }

    public function handle_activate_request()
    {
        $this->guard_admin_request('mlbs_activate_license');

        $server_url  = isset($_POST['license_server_url']) ? esc_url_raw(wp_unslash($_POST['license_server_url'])) : '';
        $product_id  = isset($_POST['license_product_id']) ? sanitize_text_field(wp_unslash($_POST['license_product_id'])) : '';
        $license_key = isset($_POST['license_key']) ? sanitize_text_field(wp_unslash($_POST['license_key'])) : '';

        $this->settings->update(array(
            'license_server_url' => $server_url,
            'license_product_id' => $product_id,
        ));

        if ($server_url === '' || $product_id === '' || $license_key === '') {
            $message = __('Preencha servidor, Product ID e chave de licença.', MLBS_TEXT_DOMAIN);
            $this->save_state(array(
                'license_key'       => $license_key,
                'status'            => 'bad_request',
                'plan'              => 'free',
                'message'           => $message,
                'domain'            => $this->get_domain(),
                'site_url'          => site_url('/'),
                'home_url'          => home_url('/'),
                'last_validated_at' => current_time('mysql'),
                'valid'             => 0,
                'premium'           => 0,
            ));
            $this->redirect_with_notice('error', $message);
        }

        $payload = $this->request_license_api('activate_license', $license_key, $server_url, $product_id);
        $this->consume_api_result($payload, $license_key, __('Licença ativada.', MLBS_TEXT_DOMAIN));
    }

    public function handle_sync_request()
    {
        $this->guard_admin_request('mlbs_sync_license');

        $server_url  = isset($_POST['license_server_url']) ? esc_url_raw(wp_unslash($_POST['license_server_url'])) : (string) $this->settings->get('license_server_url', '');
        $product_id  = isset($_POST['license_product_id']) ? sanitize_text_field(wp_unslash($_POST['license_product_id'])) : (string) $this->settings->get('license_product_id', '');
        $license_key = isset($_POST['license_key']) ? sanitize_text_field(wp_unslash($_POST['license_key'])) : (string) $this->get_state()['license_key'];

        $this->settings->update(array(
            'license_server_url' => $server_url,
            'license_product_id' => $product_id,
        ));

        if ($server_url === '' || $product_id === '') {
            $message = __('Preencha servidor e Product ID para sincronizar com o Hub.', MLBS_TEXT_DOMAIN);
            $this->redirect_with_notice('error', $message);
        }

        $action = $license_key !== '' ? 'validate_license' : 'start_trial';
        $payload = $this->request_license_api($action, $license_key, $server_url, $product_id);
        $success_message = $license_key !== '' ? __('Status da licença sincronizado.', MLBS_TEXT_DOMAIN) : __('Status Free / Trial sincronizado.', MLBS_TEXT_DOMAIN);
        $this->consume_api_result($payload, $license_key, $success_message);
    }

    public function handle_remove_request()
    {
        $this->guard_admin_request('mlbs_remove_license');

        $state      = $this->get_state();
        $server_url = (string) $this->settings->get('license_server_url', '');
        $product_id = (string) $this->settings->get('license_product_id', '');
        $license_key = (string) $state['license_key'];

        if ($server_url !== '' && $product_id !== '' && $license_key !== '') {
            $payload = $this->request_license_api('deactivate_license', $license_key, $server_url, $product_id);
            if (! empty($payload['ok'])) {
                $data = is_array($payload['body']) ? $payload['body'] : array();
                $this->save_state(array(
                    'license_key'       => '',
                    'status'            => isset($data['status']) ? sanitize_key((string) $data['status']) : 'free',
                    'plan'              => isset($data['plan']) ? sanitize_text_field((string) $data['plan']) : 'free',
                    'message'           => isset($data['message']) ? sanitize_text_field((string) $data['message']) : __('Licença removida deste site.', MLBS_TEXT_DOMAIN),
                    'domain'            => $this->get_domain(),
                    'site_url'          => site_url('/'),
                    'home_url'          => home_url('/'),
                    'last_validated_at' => current_time('mysql'),
                    'expires_at'        => '',
                    'grace_until'       => '',
                    'days_left'         => '',
                    'valid'             => 0,
                    'premium'           => 0,
                    'feature_set'       => isset($data['feature_set']) ? sanitize_text_field((string) $data['feature_set']) : 'free',
                    'license_source'    => isset($data['license_source']) ? sanitize_text_field((string) $data['license_source']) : 'free',
                    'http_code'         => isset($payload['http_code']) ? (int) $payload['http_code'] : '',
                    'trial_started'     => 0,
                ));
                $this->redirect_with_notice('success', __('Licença removida deste site.', MLBS_TEXT_DOMAIN));
            }
        }

        $this->clear_state();
        $this->redirect_with_notice('success', __('Licença removida deste site.', MLBS_TEXT_DOMAIN));
    }

    private function guard_admin_request($nonce_action)
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Permissão insuficiente.', MLBS_TEXT_DOMAIN));
        }

        check_admin_referer($nonce_action);
    }

    private function normalize_license_endpoints($server_url)
    {
        $server_url = trim((string) $server_url);
        if ($server_url === '') {
            return array();
        }

        $server_url = preg_replace('~\s+~', '', $server_url);
        $server_url = untrailingslashit($server_url);
        $server_url = preg_replace('~/admin/api/license\.php$~', '', $server_url);
        $server_url = preg_replace('~/api/license\.php$~', '', $server_url);
        $server_url = preg_replace('~/admin$~', '', $server_url);

        $endpoint = esc_url_raw($server_url . '/api/license.php');

        return $endpoint !== '' ? array($endpoint) : array();
    }

    private function decode_license_payload($body)
    {
        if (! is_string($body) || $body === '') {
            return null;
        }

        $body = trim((string) preg_replace('/^\xEF\xBB\xBF/', '', $body));

        $payload = json_decode($body, true);
        if (is_array($payload)) {
            return $payload;
        }

        $first = strpos($body, '{');
        $last  = strrpos($body, '}');
        if ($first !== false && $last !== false && $last > $first) {
            $snippet = substr($body, $first, ($last - $first) + 1);
            $payload = json_decode($snippet, true);
            if (is_array($payload)) {
                return $payload;
            }
        }

        return null;
    }

    private function request_license_api($action, $license_key, $server_url, $product_id)
    {
        $endpoints = $this->normalize_license_endpoints($server_url);
        $last_http_code = 0;
        $last_error_message = __('Resposta inválida do servidor de licença.', MLBS_TEXT_DOMAIN);

        foreach ($endpoints as $endpoint) {
            $response = wp_remote_post($endpoint, array(
                'timeout'     => 20,
                'redirection' => 3,
                'sslverify'   => false,
                'headers'     => array(
                    'Accept'     => 'application/json',
                    'User-Agent' => 'ML-Banner-Suite/' . MLBS_VERSION . '; ' . home_url('/'),
                ),
                'body'        => array(
                    'action'           => $action,
                    'product_id'       => $product_id,
                    'license_key'      => $license_key,
                    'domain'           => $this->get_domain(),
                    'site_url'         => site_url('/'),
                    'home_url'         => home_url('/'),
                    'version'          => MLBS_VERSION,
                    'admin_email'      => get_option('admin_email', ''),
                    'site_fingerprint' => $this->get_site_fingerprint(),
                ),
            ));

            if (is_wp_error($response)) {
                $last_error_message = $response->get_error_message();
                continue;
            }

            $last_http_code = (int) wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $payload = $this->decode_license_payload($body);

            if (is_array($payload)) {
                return array(
                    'ok'        => $last_http_code >= 200 && $last_http_code < 300,
                    'http_code' => $last_http_code,
                    'body'      => $payload,
                );
            }

            if ($last_http_code >= 200 && $last_http_code < 300) {
                $last_error_message = __('Resposta inválida do servidor de licença.', MLBS_TEXT_DOMAIN);
            } else {
                $last_error_message = sprintf(
                    __('Falha na comunicação com o servidor de licença (HTTP %d).', MLBS_TEXT_DOMAIN),
                    $last_http_code
                );
            }
        }

        return array(
            'ok'        => false,
            'http_code' => $last_http_code,
            'body'      => array(
                'valid'   => false,
                'status'  => 'invalid_response',
                'message' => $last_error_message,
            ),
        );
    }

    private function consume_api_result(array $payload, $license_key, $fallback_success)
    {
        $data = is_array($payload['body']) ? $payload['body'] : array();
        $valid = ! empty($data['valid']);
        $message = isset($data['message']) && $data['message'] !== '' ? sanitize_text_field((string) $data['message']) : ($valid ? $fallback_success : __('Falha ao validar a licença.', MLBS_TEXT_DOMAIN));
        $status = isset($data['status']) ? sanitize_key((string) $data['status']) : ($valid ? 'active' : 'invalid');
        $plan = isset($data['plan']) && $data['plan'] !== '' ? sanitize_text_field((string) $data['plan']) : ($valid ? 'full' : 'free');
        $product_name = isset($data['product']['name']) ? sanitize_text_field((string) $data['product']['name']) : (string) $this->settings->get('license_product_name', 'ML Banner Suite');
        $feature_set = isset($data['feature_set']) ? sanitize_text_field((string) $data['feature_set']) : ($valid ? 'full' : 'free');
        $license_source = isset($data['license_source']) ? sanitize_text_field((string) $data['license_source']) : ($license_key !== '' ? 'license' : 'free');
        $days_left = isset($data['days_left']) && $data['days_left'] !== null ? (int) $data['days_left'] : '';
        $expires_at = isset($data['expires_at']) && $data['expires_at'] !== null ? sanitize_text_field((string) $data['expires_at']) : '';
        $grace_until = isset($data['grace_until']) && $data['grace_until'] !== null ? sanitize_text_field((string) $data['grace_until']) : '';
        $premium = ! empty($data['premium']) ? 1 : 0;
        $trial_started = in_array($status, array('trial_active', 'trial_expired'), true) ? 1 : 0;

        $this->save_state(array(
            'license_key'       => $license_key,
            'status'            => $status,
            'plan'              => $plan,
            'message'           => $message,
            'domain'            => isset($data['domain']) && $data['domain'] !== '' ? sanitize_text_field((string) $data['domain']) : $this->get_domain(),
            'site_url'          => isset($data['site_url']) && $data['site_url'] !== '' ? esc_url_raw((string) $data['site_url']) : site_url('/'),
            'home_url'          => isset($data['home_url']) && $data['home_url'] !== '' ? esc_url_raw((string) $data['home_url']) : home_url('/'),
            'last_validated_at' => current_time('mysql'),
            'expires_at'        => $expires_at,
            'grace_until'       => $grace_until,
            'days_left'         => $days_left,
            'valid'             => $valid ? 1 : 0,
            'premium'           => $premium,
            'feature_set'       => $feature_set,
            'license_source'    => $license_source,
            'site_fingerprint'  => $this->get_site_fingerprint(),
            'http_code'         => isset($payload['http_code']) ? (int) $payload['http_code'] : '',
            'product_name'      => $product_name,
            'trial_started'     => $trial_started,
        ));

        $notice_type = ($valid || in_array($status, array('free', 'trial_expired'), true)) ? 'success' : 'error';
        $this->redirect_with_notice($notice_type, $message);
    }

    public function get_plan_label($plan)
    {
        $plan = strtolower((string) $plan);
        $map = array(
            'free'     => __('Free', MLBS_TEXT_DOMAIN),
            'trial'    => __('Trial', MLBS_TEXT_DOMAIN),
            'full'     => __('Full', MLBS_TEXT_DOMAIN),
            'premium'  => __('Premium', MLBS_TEXT_DOMAIN),
            'lifetime' => __('Vitalício', MLBS_TEXT_DOMAIN),
        );

        return isset($map[$plan]) ? $map[$plan] : ucfirst($plan);
    }

    public function get_status_label($status)
    {
        $status = strtolower((string) $status);
        $map = array(
            'free'           => __('Free ativo', MLBS_TEXT_DOMAIN),
            'trial_active'   => __('Trial ativo', MLBS_TEXT_DOMAIN),
            'trial_expired'  => __('Trial expirado', MLBS_TEXT_DOMAIN),
            'active'         => __('Licença ativa', MLBS_TEXT_DOMAIN),
            'lifetime'       => __('Vitalícia ativa', MLBS_TEXT_DOMAIN),
            'expired'        => __('Licença expirada', MLBS_TEXT_DOMAIN),
            'blocked'        => __('Bloqueada', MLBS_TEXT_DOMAIN),
            'domain_mismatch'=> __('Domínio divergente', MLBS_TEXT_DOMAIN),
            'inactive'       => __('Inativa', MLBS_TEXT_DOMAIN),
            'bad_request'    => __('Dados incompletos', MLBS_TEXT_DOMAIN),
            'error'          => __('Erro de conexão', MLBS_TEXT_DOMAIN),
            'invalid'        => __('Inválida', MLBS_TEXT_DOMAIN),
            'not_found'      => __('Não encontrada', MLBS_TEXT_DOMAIN),
            'deactivated'    => __('Desativada', MLBS_TEXT_DOMAIN),
        );

        return isset($map[$status]) ? $map[$status] : ucfirst(str_replace('_', ' ', $status));
    }

    private function redirect_with_notice($type, $message)
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
