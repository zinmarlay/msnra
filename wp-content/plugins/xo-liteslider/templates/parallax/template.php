<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * XO Slider Template.
 *
 * @version 2.1.0
 *
 * If you need to edit this file, copy this template.php and style.css files to the following directory
 * in the theme. The files in the theme take precedence.
 *
 * Directory: "(Theme directory)/xo-liteslider/templates/(Template ID)/"
 */
class XO_Slider_Template_Parallax extends XO_Slider_Template_Base {
	/**
	 * Template ID.
	 *
	 * @var string
	 */
	public $id = 'parallax';

	/**
	 * Template name.
	 *
	 * @var string
	 */
	public $name = 'Parallax';

	/**
	 * Template version.
	 *
	 * @var string
	 */
	public $version = '2.1.0';

	/**
	 * Enqueue scripts and styles.
	 *
	 * @param string $template_slug Template slug.
	 */
	public function enqueue_scripts( $template_slug ) {
		if ( $this->id === $template_slug ) {
			wp_enqueue_style( "xo-slider-template-{$this->id}", $this->get_template_uri() . 'style.css', array(), $this->version );
		}
	}

	/**
	 * Gets the description HTML.
	 */
	public function get_description() {
		return __( 'This template displays the parallax effect slider. The first image will be used. The only animation effect is "Slide". Video is not supported.', 'xo-liteslider' );
	}

	/**
	 * Get the slider HTML.
	 *
	 * @param object $slide {
	 *     Slide data.
	 *
	 *     @type int   $id     Slide ID.
	 *     @type array $slides Slides data.
	 *     @type array $params Slide parameters.
	 * }
	 * @return string Slider HTML.
	 */
	public function get_html( $slide ) {
		// Get the first valid image.
		foreach ( $slide->slides as $slide_data ) {
			if ( isset( $slide_data['image_id'] ) && $slide_data['image_id'] ) {
				$image_src = wp_get_attachment_image_src( $slide_data['image_id'], 'full' );
				if ( $image_src ) {
					$image_url = $image_src['0'];
					break;
				}
			}
		}
		if ( empty( $image_url ) ) {
			return;
		}

		$style = '';
		if ( ! empty( $slide->params['width'] ) ) {
			$style .= "width:{$slide->params['width']}px;";
		}
		if ( ! empty( $slide->params['height'] ) ) {
			$style .= "height:{$slide->params['height']}px;";
		}
		if ( $style ) {
			$style = " style=\"{$style}\"";
		}

		$html = "<div class=\"swiper swiper-container\"{$style}>\n";

		$html .= '<div class="parallax-bg" style="background-image:url(' . esc_url( $image_url ) . ')" data-swiper-parallax="-23%"></div>';

		$html .= '<div class="swiper-wrapper mime-type-image">' . "\n";
		foreach ( $slide->slides as $slide_data ) {
			$html .= '<div class="swiper-slide">' . "\n";

			if ( ! empty( $slide->params['content'] ) ) {
				$html .= '<div class="slide-content">' . "\n";

				if ( ! empty( $slide_data['title'] ) ) {
					$html .= '<div class="slide-content-title">' . wp_kses_post( $slide_data['title'] ) . '</div>' . "\n";
				}
				if ( ! empty( $slide_data['subtitle'] ) ) {
					$html .= '<div class="slide-content-subtitle">' . wp_kses_post( $slide_data['subtitle'] ) . '</div>' . "\n";
				}
				if ( ! empty( $slide_data['content'] ) ) {
					$html .= '<div class="slide-content-text">' . wp_kses_post( $slide_data['content'] ) . '</div>' . "\n";
				}
				if ( ! empty( $slide_data['button_text'] ) && ! empty( $slide_data['button_link'] ) ) {
					$target = ( ! empty( $slide_data['button_newwin'] ) && $slide_data['button_newwin'] ) ? ' target="_blank" rel="noopener noreferrer"' : '';
					$html  .= '<div class="slide-content-button"><a href="' . esc_url( $slide_data['button_link'] ) . '" class="slide-button-main"' . $target . '>' . wp_kses_post( $slide_data['button_text'] ) . '</a></div>' . "\n";
				}

				$html .= "</div>\n"; // <!-- .slide-content -->
			}

			$html .= "</div>\n"; // <!-- swiper-slide -->
		}
		$html .= "</div>\n"; // <!-- .swiper-wrapper -->

		if ( isset( $slide->params['pagination'] ) && $slide->params['pagination'] ) {
			$html .= '<div class="swiper-pagination swiper-pagination-white"></div>';
		}
		if ( isset( $slide->params['navigation'] ) && $slide->params['navigation'] ) {
			$html .= '<div class="swiper-button-prev swiper-button-white"></div>';
			$html .= '<div class="swiper-button-next swiper-button-white"></div>';
		}

		$html .= "</div>\n"; // <!-- .swiper-container -->

		return $html;
	}

	/**
	 * Get the slider script.
	 *
	 * @param object $slide {
	 *     Slide data.
	 *
	 *     @type int   $id     Slide ID.
	 *     @type array $slides Slides data.
	 *     @type array $params Slide parameters.
	 * }
	 * @return array|false Slider script, false to not output the script.
	 */
	public function get_script( $slide ) {
		$params = array();

		if ( isset( $slide->params['pagination'] ) ) {
			$params['pagination'] = array(
				'el'        => '.swiper-pagination',
				'clickable' => true,
			);
		}

		if ( isset( $slide->params['navigation'] ) ) {
			$params['navigation'] = array(
				'nextEl' => '.swiper-button-next',
				'prevEl' => '.swiper-button-prev',
			);
		}

		$params['parallax'] = true;
		$params['loop']     = false;
		$params['speed']    = (int) isset( $slide->params['speed'] ) ? $slide->params['speed'] : 600;

		if ( ! empty( $slide->params['autoplay'] ) ) {
			$params['autoplay'] = array(
				'delay'                => (int) isset( $slide->params['delay'] ) ? $slide->params['delay'] : 3000,
				'stopOnLastSlide'      => (bool) isset( $slide->params['stop_on_last_slide'] ) ? $slide->params['stop_on_last_slide'] : false,
				'disableOnInteraction' => (bool) isset( $slide->params['disable_on_interaction'] ) ? $slide->params['disable_on_interaction'] : true,
			);
		} else {
			$params['autoplay'] = false;
		}

		if ( isset( $slide->params['auto_height'] ) && ! empty( $slide->params['auto_height'] ) ) {
			$params['autoHeight'] = (bool) $slide->params['auto_height'];
		}

		/**
		 * Filter slider script parameters.
		 *
		 * @since 2.0.0
		 *
		 * @param string      $params Script parameters.
		 * @param array       $slide  Slide data.
		 * @param string|null $key    Script parameter key.
		 */
		$params = apply_filters( 'xo_slider_script_parameter', $params, $slide, null );

		$json   = wp_json_encode( $params );
		$script = "var xoSlider{$slide->id} = new Swiper('#xo-slider-{$slide->id} .swiper-container', {$json});";

		return $script;
	}
}
