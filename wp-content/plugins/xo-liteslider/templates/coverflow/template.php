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
class XO_Slider_Template_Coverflow extends XO_Slider_Template_Base {
	/**
	 * Template ID.
	 *
	 * @var string
	 */
	public $id = 'coverflow';

	/**
	 * Template name.
	 *
	 * @var string
	 */
	public $name = 'Coverflow';

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
		return __( 'A template that displays a slider for a 3D coverflow effect. Video is not supported.', 'xo-liteslider' );
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

		$html .= '<div class="swiper-wrapper">' . "\n";
		foreach ( $slide->slides as $slide_data ) {
			if ( empty( $slide_data['media_id'] ) ) {
				continue;
			}

			$attr = array( 'class' => 'slide-image' );
			if ( $slide_data['alt'] ) {
				$attr['alt'] = $slide_data['alt'];
			}
			if ( $slide_data['title_attr'] ) {
				$attr['title'] = $slide_data['title_attr'];
			}
			$img = wp_get_attachment_image( $slide_data['media_id'], 'full', false, $attr );

			if ( $img ) {
				$html .= '<div class="swiper-slide mime-type-image">';

				if ( $slide_data['link'] ) {
					$target = ( ! empty( $slide_data['link_newwin'] ) && $slide_data['link_newwin'] ) ? ' target="_blank" rel="noopener noreferrer"' : '';
					$html  .= '<a href="' . esc_url( $slide_data['link'] ) . '"' . $target . '>' . $img . '</a>';
				} else {
					$html .= $img;
				}

				if ( ! empty( $slide->params['content'] ) ) {
					$html .= '<div class="slide-content">' . wp_kses_post( $slide_data['content'] ) . '</div>';
				}

				$html .= "</div>\n"; // <!-- .swiper-slide -->
			}
		}
		$html .= "</div>\n"; // <!-- .swiper-wrapper -->

		if ( $slide->params['pagination'] ) {
			$html .= "<div class=\"swiper-pagination\"></div>\n";
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
		$params = array(
			'effect'          => 'coverflow',
			'grabCursor'      => true,
			'centeredSlides'  => true,
			'slidesPerView'   => 2,
			'coverflowEffect' => array(
				'rotate'       => 50,
				'stretch'      => 0,
				'depth'        => 100,
				'modifier'     => 1,
				'slideShadows' => true,
			),
			'pagination'      => array( 'el' => $slide->params['pagination'] ? '.swiper-pagination' : null ),
			'speed'           => (int) $slide->params['speed'],
			'loop'            => true,
		);

		if ( ! empty( $slide->params['autoplay'] ) ) {
			$params['autoplay'] = array(
				'delay'                => (int) ( $slide->params['delay'] ?? 3000 ),
				'stopOnLastSlide'      => (bool) ( $slide->params['stop_on_last_slide'] ?? false ),
				'disableOnInteraction' => (bool) ( $slide->params['disable_on_interaction'] ?? true ),
			);
		} else {
			$params['autoplay'] = false;
		}

		if ( isset( $slide->params['auto_height'] ) && ! empty( $slide->params['auto_height'] ) ) {
			$params['autoHeight'] = (float) $slide->params['auto_height'];
		}

		if ( isset( $slide->params['slides_per_group'] ) && ! empty( $slide->params['slides_per_group'] ) ) {
			$params['slidesPerGroup'] = (int) $slide->params['slides_per_group'];
		}

		if ( isset( $slide->params['space_between'] ) && ! empty( $slide->params['space_between'] ) ) {
			$params['spaceBetween'] = (int) $slide->params['space_between'];
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
