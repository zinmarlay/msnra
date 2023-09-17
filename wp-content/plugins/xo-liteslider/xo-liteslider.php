<?php
/**
 * XO Slider plugin for WordPress.
 *
 * @package xo-slider
 * @author  ishitaka
 * @license GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       XO Slider
 * Plugin URI:        https://xakuro.com/wordpress/
 * Description:       XO Slider is a plugin that allows you to easily create sliders.
 * Author:            Xakuro
 * Author URI:        https://xakuro.com/
 * License:           GPL v2 or later
 * Requires at least: 4.9
 * Requires PHP:      7.0
 * Version:           3.7.4
 * Text Domain:       xo-liteslider
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'XO_SLIDER_VERSION', '3.7.4' );

require_once __DIR__ . '/inc/class-xo-slider.php';

$xo_slider = new XO_Slider();

/**
 * Display slider.
 *
 * @since 2.0.0
 *
 * @param int  $slider_id Optional, default is latest slider. Slider ID.
 * @param bool $echo      Optional, default is true. Set to false for return.
 * @return void|string Void if `$echo` argument is true, slider HTML if `$echo` is false.
 */
function xo_slider( $slider_id = 0, $echo = true ) {
	global $xo_slider;
	$slider = $xo_slider->get_slider( $slider_id );
	if ( $echo ) {
		echo $slider; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return;
	}
	return $slider;
}

/**
 * Display slider.
 *
 * @since 1.0.0
 * @deprecated 2.0.0 Use xo_slider()
 * @see xo_slider()
 *
 * @param int $slider_id Optional, default is latest slider. Slider ID.
 */
function xo_liteslider( $slider_id = 0 ) {
	_deprecated_function( __FUNCTION__, '2.0.0', 'xo_slider' );
	xo_slider( $slider_id, true );
}
