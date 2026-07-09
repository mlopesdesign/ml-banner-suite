<?php
/**
 * Plugin deactivator.
 *
 * @package MLBannerSuite
 */

if (! defined('ABSPATH')) {
    exit;
}

class MLBS_Deactivator
{
    /**
     * Deactivate plugin.
     *
     * @return void
     */
    public static function deactivate()
    {
        flush_rewrite_rules();
    }
}
