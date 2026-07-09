<?php
/**
 * Internationalization handler.
 *
 * @package MLBannerSuite
 */

if (! defined('ABSPATH')) {
    exit;
}

class MLBS_I18n
{
    /**
     * Load plugin textdomain.
     *
     * @return void
     */
    public function load_plugin_textdomain()
    {
        load_plugin_textdomain(
            MLBS_TEXT_DOMAIN,
            false,
            dirname(MLBS_PLUGIN_BASENAME) . '/languages/'
        );
    }
}
