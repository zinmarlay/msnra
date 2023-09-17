<?php
/**
 * XO Slider main.
 *
 * @package xo-slider
 * @since 1.0.0
 */

/**
 * XO Slider main class.
 */
class XO_Slider {
	const DEFAULT_TEMPLATE_SLUG = 'default';

	/**
	 * Plugin dir.
	 *
	 * @var string
	 */
	public $plugin_dir;

	/**
	 * Options.
	 *
	 * @var array
	 */
	public $options;

	/**
	 * XO Slider admin.
	 *
	 * @var XO_Slider_Admin
	 */
	public $admin;

	/**
	 * Array of template names.
	 *
	 * @var array
	 */
	public $templates;

	/**
	 * Array of render counts per slide ID.
	 *
	 * @var array
	 */
	private $render_counts = array();

	/**
	 * Construction.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		load_plugin_textdomain( 'xo-liteslider' );

		$this->plugin_dir = dirname( __DIR__ );

		if ( is_admin() ) {
			require_once $this->plugin_dir . '/inc/class-xo-slider-admin.php';
			$this->admin = new XO_Slider_Admin( $this );
		}

		require_once $this->plugin_dir . '/inc/class-xo-slider-widget.php';
		require_once $this->plugin_dir . '/inc/class-xo-slider-template-base.php';

		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
	}

	/**
	 * Plugins loaded process.
	 *
	 * @since 1.0.0
	 */
	public function plugins_loaded() {
		$this->options = get_option( 'xo_slider_options' );
		if ( false === $this->options ) {
			$this->options = array(
				'version'        => XO_SLIDER_VERSION,
				'css_load'       => 'body', // CSS load type. 'none', 'head' or 'body'.
				'swiper_version' => '',     // Swiper version. '': Default or '8': 8 series.
				'delete_data'    => false,
			);
			update_option( 'xo_slider_options', $this->options );
		}

		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_header' ) );
		add_action( 'widgets_init', array( $this, 'register_widget' ) );
		add_shortcode( 'xo_liteslider', array( $this, 'get_liteslider_shortcode' ) );
		add_shortcode( 'xo_slider', array( $this, 'get_slider_shortcode' ) );

		if ( function_exists( 'register_block_type' ) ) {
			add_action( 'rest_api_init', array( $this, 'initialize_rest_api' ) );
			add_action( 'init', array( $this, 'initialize_blocks' ) );
			add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
		}
	}

	/**
	 * Enqueues scripts for header.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_header() {
		$min = SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style( 'xo-slider', plugins_url( "css/base{$min}.css", __DIR__ ), array(), XO_SLIDER_VERSION );

		$swiper = ( isset( $this->options['swiper_version'] ) && '8' === $this->options['swiper_version'] ) ? 'swiper8' : 'swiper';
		wp_enqueue_style( 'xo-swiper', plugins_url( "assets/vendor/{$swiper}/swiper-bundle.min.css", __DIR__ ), array(), XO_SLIDER_VERSION );

		if ( ! empty( $this->options['css_load'] ) && 'head' === $this->options['css_load'] ) {
			// Enqueue all templates.
			foreach ( $this->templates as $template ) {
				$file = $template->get_template_dir() . 'style.css';
				if ( file_exists( $file ) ) {
					wp_enqueue_style( "xo-slider-template-{$template->id}", $template->get_template_uri() . 'style.css', array(), $template->version );
				}
			}
		}
	}

	/**
	 * Enqueues scripts.
	 *
	 * @since 1.0.0
	 *
	 * @param string $template_slug Template slug.
	 */
	public function enqueue_scripts( $template_slug ) {
		$swiper = ( isset( $this->options['swiper_version'] ) && '8' === $this->options['swiper_version'] ) ? 'swiper8' : 'swiper';
		wp_enqueue_script( 'xo-swiper', plugins_url( "assets/vendor/{$swiper}/swiper-bundle.min.js", __DIR__ ), array(), XO_SLIDER_VERSION, false );

		if ( ! empty( $this->options['css_load'] ) && 'body' === $this->options['css_load'] ) {
			do_action( 'xo_slider_enqueue_scripts', $template_slug );
		}
	}

	/**
	 * Registers the post type.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		register_post_type(
			'xo_liteslider',
			array(
				'labels'              => array(
					'name'               => __( 'Sliders', 'xo-liteslider' ),
					'singular_name'      => __( 'Slider', 'xo-liteslider' ),
					'menu_name'          => __( 'Sliders', 'xo-liteslider' ),
					'name_admin_bar'     => __( 'Sliders', 'xo-liteslider' ),
					'all_items'          => __( 'All Sliders', 'xo-liteslider' ),
					'add_new'            => __( 'Add New', 'xo-liteslider' ),
					'add_new_item'       => __( 'Add New', 'xo-liteslider' ),
					'edit_item'          => __( 'Edit Slider', 'xo-liteslider' ),
					'new_item'           => __( 'New Slider', 'xo-liteslider' ),
					'view_item'          => __( 'View Slider', 'xo-liteslider' ),
					'search_items'       => __( 'Search Slider', 'xo-liteslider' ),
					'not_found'          => __( 'No sliders found', 'xo-liteslider' ),
					'not_found_in_trash' => __( 'No sliders found in Trash', 'xo-liteslider' ),
				),
				'public'              => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'query_var'           => false,
				'rewrite'             => false,
				'capability_type'     => 'post',
				'has_archive'         => false,
				'hierarchical'        => false,
				'menu_position'       => 25,
				'menu_icon'           => 'dashicons-slides',
				'supports'            => array( 'title', 'author' ),
				'show_in_rest'        => true,
				'rest_base'           => 'xo_liteslider',
			)
		);

		$this->templates = $this->get_templates();
	}

	/**
	 * Get instances of all templates.
	 *
	 * @since 3.2.0
	 *
	 * @return array Array of template objects.
	 */
	private function get_templates() {
		$templates = array();

		$dirs = scandir( $this->plugin_dir . '/templates' );
		foreach ( $dirs as $dir ) {
			if ( '.' === $dir[0] || ! is_dir( $this->plugin_dir . '/templates/' . $dir ) ) {
				continue;
			}

			$file = $this->plugin_dir . '/templates/' . $dir . '/template.php';
			if ( ! file_exists( $file ) ) {
				continue;
			}

			$templates[ $dir ] = array( false, $file );
		}

		$template_dir = get_stylesheet_directory() . '/xo-liteslider/templates';
		if ( is_dir( $template_dir ) ) {
			$dirs = scandir( $template_dir );
			foreach ( $dirs as $dir ) {
				if ( '.' === $dir[0] || ! is_dir( $template_dir . '/' . $dir ) ) {
					continue;
				}

				$file = $template_dir . '/' . $dir . '/template.php';

				if ( ! file_exists( $file ) ) {
					continue;
				}

				$templates[ $dir ] = array( true, $file );
			}
		}

		$results = array();

		foreach ( $templates as $key => $value ) {
			require_once $value[1];
			$class = 'XO_Slider_Template_' . ucfirst( $key );
			if ( class_exists( $class ) ) {
				$template                 = new $class( $value[0] );
				$results[ $template->id ] = $template;
			}
		}

		return $results;
	}

	/**
	 * Get the slider HTML.
	 *
	 * @param int   $id         Slider id.
	 * @param array $attributes Attributes.
	 */
	public function get_slider( $id = 0, $attributes = null ) {
		if ( 0 === $id ) {
			// If no ID is specified, use the first slide.
			$slider_posts = get_posts(
				array(
					'post_type'   => 'xo_liteslider',
					'orderby'     => 'date',
					'order'       => 'ASC',
					'numberposts' => 1,
				)
			);
			if ( $slider_posts ) {
				$id = $slider_posts[0]->ID;
			}
		}

		$slides = get_post_meta( $id, 'slides', true );
		if ( empty( $slides ) ) {
			return;
		}

		$params = get_post_meta( $id, 'parameters', true );

		switch ( $params['sort'] ) {
			case 'random':
				shuffle( $slides );
				break;
			case 'desc':
				$slides = array_reverse( $slides );
				break;
		}

		// Measures against v2.1.0 or data below.
		foreach ( $slides as $key => $slide_data ) {
			if ( ! isset( $slide_data['media_id'] ) && isset( $slide_data['image_id'] ) ) {
				$slides[ $key ]['media_id']   = $slide_data['image_id'];
				$slides[ $key ]['media_type'] = 'image';
			}
		}

		$slide = (object) array(
			'id'     => $id,
			'slides' => $slides,
			'params' => $params,
		);

		$template_slug = empty( $slide->params['template'] ) ? self::DEFAULT_TEMPLATE_SLUG : $slide->params['template'];

		if ( ! isset( $this->templates[ $template_slug ] ) ) {
			return '';
		}

		$template = $this->templates[ $template_slug ];

		if ( empty( $template ) ) {
			return '';
		}

		$this->enqueue_scripts( $template_slug );

		$class = 'xo-slider';
		if ( ! empty( $attributes['className'] ) ) {
			$class .= ' ' . $attributes['className'];
		}
		if ( ! empty( $attributes['align'] ) ) {
			$class .= ' align' . $attributes['align'];
		}

		$html  = "<div id=\"xo-slider-{$slide->id}\" class=\"{$class} xo-slider-template-{$template->id}\">\n";
		$html .= $template->get_html( $slide );
		$html .= "</div>\n";

		/**
		 * Filter the slider HTML.
		 *
		 * @since 2.0.0
		 *
		 * @param string $script HTML.
		 * @param object $slide {
		 *     Slide data.
		 *
		 *     @type int   $id     Slide ID.
		 *     @type array $slides Slides data.
		 *     @type array $params Slide parameters.
		 * }
		 */
		$html = apply_filters( 'xo_slider_html', $html, $slide );

		if ( ! isset( $this->render_counts[ $id ] ) ) {
			$this->render_counts[ $id ] = 1;

			$script = 'window.addEventListener("load", function() { ' . $template->get_script( $slide ) . ' });';

			/**
			 * Filters the slider script.
			 *
			 * @since 2.0.0
			 *
			 * @param string $script Script.
			 * @param object $slide {
			 *     Slide data.
			 *
			 *     @type int   $id     Slide ID.
			 *     @type array $slides Slides data.
			 *     @type array $params Slide parameters.
			 * }
			 */
			$script = apply_filters( 'xo_slider_script', $script, $slide );

			if ( $script ) {
				wp_add_inline_script( 'xo-swiper', $script, 'after' );
			}
		} else {
			++$this->render_counts[ $id ];
		}

		return $html;
	}

	/**
	 * Get the slider shortcode.
	 *
	 * @since 1.0.0
	 * @deprecated 2.0.0 Use xo_slider()
	 * @see get_slider_shortcode()
	 *
	 * @param array $atts Shortcode attributes.
	 */
	public function get_liteslider_shortcode( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'xo_liteslider' );
		return $this->get_slider( $atts['id'] );
	}

	/**
	 * Get the slider shortcode.
	 *
	 * @since 2.0.0
	 *
	 * @param array $atts Shortcode attributes.
	 */
	public function get_slider_shortcode( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'xo_slider' );
		return $this->get_slider( $atts['id'] );
	}

	/**
	 * Register the widgets.
	 *
	 * @since 1.0.0
	 */
	public function register_widget() {
		register_widget( 'XO_Slider_Widget' );
	}

	/**
	 * Initialize REST API.
	 *
	 * @since 1.0.0
	 */
	public function initialize_rest_api() {
		register_rest_route(
			'xo-slider/v1',
			'/preview/(?P<id>[\d]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_preview' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Get the preview.
	 *
	 * @since 2.0.0
	 *
	 * @param array $data Datas.
	 */
	public function get_preview( $data ) {
		global $xo_slider;

		$slider_id = $data['id'];
		$file      = plugin_dir_path( __FILE__ ) . 'preview.php';
		$res       = ! empty( $file ) ? include_once $file : array();

		$response = new WP_REST_Response( $res );
		$response->set_status( 200 );

		return $response;
	}

	/**
	 * Initialize blocks.
	 *
	 * @since 2.0.0
	 */
	public function initialize_blocks() {
		$script_asset_path = "{$this->plugin_dir}/build/index.asset.php";
		$script_asset      = require $script_asset_path;
		wp_register_script( 'xo-liteslider-xo-slider-block-editor', plugins_url( 'build/index.js', __DIR__ ), $script_asset['dependencies'], $script_asset['version'], false );
		wp_register_style( 'xo-liteslider-xo-slider-block-editor', plugins_url( 'build/index.css', __DIR__ ), array(), $script_asset['version'] );

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'xo-liteslider-xo-slider-block-editor', 'xo-liteslider', "{$this->plugin_dir}/languages" );
		} elseif ( function_exists( 'gutenberg_get_jed_locale_data' ) ) {
			$locale = gutenberg_get_jed_locale_data( $_settings_plugin->text_domain );
			wp_add_inline_script( 'xo-liteslider', 'data', 'wp.i18n.setLocaleData( ' . wp_json_encode( $locale ) . ', "xo-liteslider" );' );
		}

		register_block_type(
			'xo-liteslider/xo-slider',
			array(
				'editor_script'   => 'xo-liteslider-xo-slider-block-editor',
				'editor_style'    => 'xo-liteslider-xo-slider-block-editor',
				'style'           => 'xo-liteslider-xo-slider-block',
				'attributes'      => array(
					'className' => array(
						'type'    => 'string',
						'default' => '',
					),
					'align'     => array(
						'type'    => 'string',
						'default' => '',
					),
					'sliderID'  => array(
						'type'    => 'number',
						'default' => 0,
					),
				),
				'render_callback' => array( $this, 'xo_slider_block_render_callback' ),
			)
		);
	}

	/**
	 * Render slider block.
	 *
	 * @since 2.0.0
	 *
	 * @param array $attributes Attributes.
	 */
	public function xo_slider_block_render_callback( $attributes ) {
		global $xo_slider;
		$slider_id = isset( $attributes['sliderID'] ) ? $attributes['sliderID'] : 0;
		return $xo_slider->get_slider( $slider_id, $attributes );
	}

	/**
	 * Enqueue block editor assets.
	 *
	 * @since 2.0.0
	 */
	public function enqueue_block_editor_assets() {
		$script = <<<SCRIPT
function xoSliderIframeLoaded(clientID) {
	var sliderBlock = document.getElementById('block-' + clientID);
	var sliderFrame = sliderBlock.getElementsByTagName('iframe')[0];
	var innerHeight = sliderFrame.contentWindow.document.body.scrollHeight;
	var innerWidth = sliderFrame.contentWindow.document.body.scrollWidth;
	var sliderFrameWrapper = sliderFrame.parentNode;
	if ( 0 < innerHeight && 0 < innerWidth ) {
		sliderFrameWrapper.style.paddingTop = (innerHeight / innerWidth * 100) + '%';
	}
};
SCRIPT;
		wp_add_inline_script( 'wp-blocks', $script );
	}
}
