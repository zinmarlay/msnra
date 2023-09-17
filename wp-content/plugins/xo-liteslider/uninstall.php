<?php
/**
 * Plugin uninstaller logic.
 *
 * @package xo-slider
 * @since 1.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

$options = get_option( 'xo_slider_options' );
if ( false !== $options && isset( $options['delete_data'] ) && $options['delete_data'] ) {
	/**
	 * Uninstall palugin.
	 */
	function xo_liteslider_delete_plugin() {
		global $wpdb;

		$posts = get_posts(
			array(
				'numberposts' => -1,
				'post_type'   => 'xo_liteslider',
				'post_status' => 'any',
			)
		);

		foreach ( $posts as $post ) {
			wp_delete_post( $post->ID, true );
		}

		delete_option( 'xo_slider_version' );
		delete_option( 'xo_slider_options' );
	}

	if ( is_multisite() ) {
		$site_ids = get_sites( array( 'fields' => 'ids' ) );
		foreach ( $site_ids as $site_id ) {
			switch_to_blog( $site_id );
			xo_liteslider_delete_plugin();
		}
		restore_current_blog();
		switch_to_blog( $original_blog_id );
	} else {
		xo_liteslider_delete_plugin();
	}
}
