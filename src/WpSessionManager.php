<?php
/**
 * WordPress session management.
 *
 * Standardizes WordPress session data and uses either database transients or in-memory caching
 * for storing user session information.
 *
 * @package wpfolk/wp-session-manager
 * @since   3.7.0
 */

namespace WPFolk\WPSessionManager;

use WPFolk\WPSessionManager\WP_Session\WP_Session;

class WpSessionManager
{
    /**
     * Return the current cache expire setting.
     *
     * @return int
     */
    public static function wp_session_cache_expire() {
        $wp_session = WP_Session::get_instance();

        return $wp_session->cache_expiration();
    }

    /**
     * Alias of wp_session_write_close()
     */
    public static function wp_session_commit() {
        self::wp_session_write_close();
    }

    /**
     * Load a JSON-encoded string into the current session.
     *
     * @param string $data
     */
    public static function wp_session_decode( $data ) {
        $wp_session = WP_Session::get_instance();

        return $wp_session->json_in( $data );
    }

    /**
     * Encode the current session's data as a JSON string.
     *
     * @return string
     */
    public static function wp_session_encode() {
        $wp_session = WP_Session::get_instance();

        return $wp_session->json_out();
    }

    /**
     * Regenerate the session ID.
     *
     * @param bool $delete_old_session
     *
     * @return bool
     */
    public static function wp_session_regenerate_id( $delete_old_session = false ) {
        $wp_session = WP_Session::get_instance();

        $wp_session->regenerate_id( $delete_old_session );

        return true;
    }

    /**
     * Start new or resume existing session.
     *
     * Resumes an existing session based on a value sent by the _wp_session cookie.
     *
     * @return bool
     */
    public static function wp_session_start() {
        $wp_session = WP_Session::get_instance();
        do_action( 'wp_session_start' );

        return $wp_session->session_started();
    }

    /**
     * Return the current session status.
     *
     * @return int
     */
    public static function wp_session_status() {
        $wp_session = WP_Session::get_instance();

        if ( $wp_session->session_started() ) {
            return PHP_SESSION_ACTIVE;
        }

        return PHP_SESSION_NONE;
    }

    /**
     * Unset all session variables.
     */
    public static function wp_session_unset() {
        $wp_session = WP_Session::get_instance();

        $wp_session->reset();
    }

    /**
     * Write session data and end session
     */
    public static function wp_session_write_close() {
        $wp_session = WP_Session::get_instance();

        $wp_session->write_data();
        do_action( 'wp_session_commit' );
    }

    /**
     * Clean up expired sessions by removing data and their expiration entries from
     * the WordPress options table.
     *
     * This method should never be called directly and should instead be triggered as part
     * of a scheduled task or cron job.
     */
    public static function wp_session_cleanup() {
        if ( defined( 'WP_SETUP_CONFIG' ) ) {
            return;
        }

        if ( ! defined( 'WP_INSTALLING' ) ) {
            $batch_size = apply_filters( 'wp_session_delete_batch_size', 1000 );  // Determine the size of each batch for deletion.

            WP_Session_Utils::delete_old_sessions( $batch_size );  // Delete a batch of old sessions
        }

        do_action( 'wp_session_cleanup' );  // Allow other plugins to hook in to the garbage collection process.
    }

    /**
     * Register the garbage collector as a twice daily event.
     */
    public static function wp_session_register_garbage_collection() {
        if ( ! wp_next_scheduled( 'wp_session_garbage_collection' ) ) {
            wp_schedule_event( time(), 'hourly', 'wp_session_garbage_collection' );
        }
    }
}

if ( ! defined( 'WP_CLI' ) || false === WP_CLI ) {
    add_action( 'plugins_loaded', [ WpSessionManager::class, 'wp_session_start' ] );
    add_action( 'shutdown', [ WpSessionManager::class, 'wp_session_write_close' ] );
}

add_action( 'wp_session_garbage_collection', [ WpSessionManager::class, 'wp_session_cleanup' ] );
add_action( 'wp', [ WpSessionManager::class, 'wp_session_register_garbage_collection' ] );
