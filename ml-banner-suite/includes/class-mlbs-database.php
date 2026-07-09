<?php
/**
 * Database utilities.
 *
 * @package MLBannerSuite
 */

if (! defined('ABSPATH')) {
    exit;
}

class MLBS_Database
{
    /**
     * Schema version option.
     */
    const SCHEMA_OPTION = 'mlbs_db_version';

    /**
     * Current schema version.
     */
    const SCHEMA_VERSION = '1.0.0';

    /**
     * Get stats table name.
     *
     * @return string
     */
    public static function get_stats_table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'mlbs_stats';
    }

    /**
     * Create or update plugin tables.
     *
     * @return void
     */
    public static function create_tables()
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table_name      = self::get_stats_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            banner_id bigint(20) unsigned NOT NULL,
            event_type varchar(20) NOT NULL,
            event_date date NOT NULL,
            event_count bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY banner_event_day (banner_id, event_type, event_date),
            KEY event_type (event_type),
            KEY event_date (event_date)
        ) {$charset_collate};";

        dbDelta($sql);

        update_option(self::SCHEMA_OPTION, self::SCHEMA_VERSION);
    }


    /**
     * Increment a daily event counter.
     *
     * @param int    $banner_id Banner ID.
     * @param string $event_type Event type.
     * @param int    $count Increment count.
     * @return void
     */
    public static function increment_event($banner_id, $event_type, $count = 1)
    {
        global $wpdb;

        $banner_id = absint($banner_id);
        $count = max(1, absint($count));
        $event_type = sanitize_key($event_type);
        if (! $banner_id || ! in_array($event_type, array('impression', 'click'), true)) {
            return;
        }

        $table_name = self::get_stats_table_name();
        $event_date = current_time('Y-m-d');
        $now = current_time('mysql');

        $updated = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table_name} SET event_count = event_count + %d, updated_at = %s WHERE banner_id = %d AND event_type = %s AND event_date = %s",
                $count,
                $now,
                $banner_id,
                $event_type,
                $event_date
            )
        );

        if ($updated === 0) {
            $wpdb->insert(
                $table_name,
                array(
                    'banner_id'   => $banner_id,
                    'event_type'  => $event_type,
                    'event_date'  => $event_date,
                    'event_count' => $count,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ),
                array('%d', '%s', '%s', '%d', '%s', '%s')
            );
        }
    }

}
