<?php
/**
 * XO Slider template base.
 *
 * @package xo-slider
 * @since 2.0.0
 */

/**
 * XO Slider Template base class.
 */
abstract class XO_Slider_Template_Base {
	/**
	 * Value that indicates whether it is a theme template.
	 *
	 * @var bool
	 */
	public $use_theme_template;

	/**
	 * Constructor.
	 *
	 * @param string $use_theme_template Value that indicates whether it is a theme template.
	 */
	public function __construct( $use_theme_template = false ) {
		$this->use_theme_template = $use_theme_template;

		add_action( 'xo_slider_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10, 1 );
	}

	/**
	 * Get the slider HTML.
	 *
	 * @abstract
	 *
	 * @param array $slide Slide data.
	 * @return string
	 */
	abstract public function get_html( $slide );

	/**
	 * Get the slider script.
	 *
	 * @abstract
	 *
	 * @param array $slide Slide data.
	 * @return string
	 */
	abstract public function get_script( $slide );

	/**
	 * Enqueue scripts and styles.
	 *
	 * @param string $template_slug Template slug.
	 */
	public function enqueue_scripts( $template_slug ) {
	}

	/**
	 * Gets the description HTML.
	 *
	 * @return string
	 */
	public function get_description() {
		return '';
	}

	/**
	 * Get the URI of the current template.
	 *
	 * @return string
	 */
	public function get_template_uri() {
		$template_url = $this->use_theme_template ? get_stylesheet_directory_uri() . '/xo-liteslider/' : plugin_dir_url( __DIR__ );
		return $template_url . "templates/{$this->id}/";
	}

	/**
	 * Get the dirctory of the current template.
	 *
	 * @return string
	 */
	public function get_template_dir() {
		$template_dir = $this->use_theme_template ? get_stylesheet_directory() . '/xo-liteslider/' : plugin_dir_path( __DIR__ );
		return $template_dir . "templates/{$this->id}/";
	}

	/**
	 * Get the URL of the screenshot image.
	 *
	 * @return string|false Screenshot file. False if the template does not have a screenshot.
	 */
	public function get_screenshot() {
		if ( $this->use_theme_template ) {
			$template_path = get_stylesheet_directory() . "/xo-liteslider/templates/{$this->id}/";
			$directory_uri = get_stylesheet_directory_uri() . '/xo-liteslider/';
		} else {
			$template_path = plugin_dir_path( __DIR__ ) . "templates/{$this->id}/";
			$directory_uri = plugin_dir_url( __DIR__ );
		}
		foreach ( array( 'png', 'gif', 'jpg', 'jpeg' ) as $ext ) {
			if ( file_exists( $template_path . "screenshot.{$ext}" ) ) {
				return "{$directory_uri}/templates/{$this->id}/screenshot.{$ext}";
			}
		}
		return false;
	}

	/**
	 * Get an HTML video element representing an image attachment.
	 *
	 * @param array $slide slide data.
	 *
	 * @return string HTML video element or empty string on failure.
	 */
	public function get_attachment_video( $slide ) {
		if ( 'video' !== $slide['media_type'] ) {
			return '';
		}

		$url = wp_get_attachment_url( $slide['media_id'] );
		if ( ! $url ) {
			return '';
		}

		$attr  = '';
		$attr .= ! empty( $slide['video_autoplay'] ) ? ' autoplay' : '';
		$attr .= ! empty( $slide['video_loop'] ) ? ' loop' : '';
		$attr .= ! empty( $slide['video_muted'] ) ? ' muted' : '';
		$attr .= ! empty( $slide['video_controls'] ) ? ' controls' : '';
		$attr .= ! empty( $slide['video_inline'] ) ? ' playsinline' : '';
		$attr .= ( isset( $slide['video_metadata'] ) && in_array( $slide['video_metadata'], array( 'auto', 'metadata', 'none' ), true ) ) ? " preload=\"{$slide['metadata']}\"" : '';

		return '<video src="' . esc_attr( $url ) . '" ' . $attr . ' style="width: 100%;"></video>';
	}

	/**
	 * Get the YouTube embed code.
	 *
	 * @param array $slide slide data.
	 *
	 * @return string YouTube Embed code or empty string on failure.
	 */
	public function get_embed_youtube( $slide ) {
		if ( 'youtube' !== $slide['media_type'] ) {
			return '';
		}

		if ( isset( $slide['media_link'] ) && 1 !== preg_match( '/[\/?=]([a-zA-Z0-9_\-]{11})[&\?]?/', $slide['media_link'], $matches ) ) {
			return '';
		}

		$youtube_id = $matches[1];

		$url_params = array();
		if ( ! empty( $slide['video_autoplay'] ) ) {
			$url_params[] = 'autoplay=1';
		}
		if ( ! empty( $slide['video_muted'] ) ) {
			$url_params[] = 'mute=1';
		}
		if ( ! empty( $slide['video_loop'] ) ) {
			$url_params[] = 'loop=1';
		}
		$url_params[]   = ( ! empty( $slide['video_controls'] ) && $slide['video_controls'] ) ? 'controls=1' : 'controls=0';
		$url_params_str = ( ! empty( $url_params ) ) ? '?' . implode( '&', $url_params ) : '';

		$format = '<iframe src="https://www.youtube.com/embed/%s%s" ' .
			'allow="accelerometer; autoplay; encrypted-media; gyroscope;" ' .
			'width="%s" height="%s" frameborder="0" unselectable="on"></iframe>';

		return sprintf( $format, $youtube_id, $url_params_str, 640, 360 );
	}
}
