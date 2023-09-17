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
class XO_Slider_Template_Thumbnail extends XO_Slider_Template_Base {
	/**
	 * Template ID.
	 *
	 * @var string
	 */
	public $id = 'thumbnail';

	/**
	 * Template name.
	 *
	 * @var string
	 */
	public $name = 'Thumbnail';

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
			wp_enqueue_style( 'xo-slider-template-thumbnail', plugins_url( 'style.css', __FILE__ ), array(), $this->version );
		}
	}

	/**
	 * Gets the description HTML.
	 */
	public function get_description() {
		return __( 'This template displays a slider with thumbnails. Video is not supported.', 'xo-liteslider' );
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

		$imgs = array();
		foreach ( $slide->slides as $key => $slide_data ) {
			if ( isset( $slide_data['image_id'] ) && $slide_data['image_id'] ) {
				$attr = array( 'class' => 'slide-image' );
				if ( isset( $slide_data['alt'] ) ) {
					$attr['alt'] = $slide_data['alt'];
				}
				if ( isset( $slide_data['title_attr'] ) ) {
					$attr['title'] = $slide_data['title_attr'];
				}
				$img = wp_get_attachment_image( $slide_data['image_id'], 'full', false, $attr );
				if ( $img ) {
					$imgs[ $key ] = $img;
				}
			}
		}

		$html  = "<div class=\"swiper swiper-container gallery-main\"{$style}>\n";
		$html .= '<div class="swiper-wrapper">' . "\n";

		foreach ( $imgs as $key => $img ) {
			$slide_data = $slide->slides[ $key ];

			$html .= '<div class="swiper-slide mime-type-image">';

			if ( ! empty( $slide_data['link'] ) ) {
				$target = ( ! empty( $slide_data['link_newwin'] ) && $slide_data['link_newwin'] ) ? ' target="_blank" rel="noopener noreferrer"' : '';
				$html  .= '<a href="' . esc_url( $slide_data['link'] ) . '"' . $target . '>' . $img . '</a>';
			} else {
				$html .= $img;
			}

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
					$html  .= '<div class="slide-content-button"><a href="' . esc_url( $slide_data['button_link'] ) . '" class="slide-content-button-main"' . $target . '>' . wp_kses_post( $slide_data['button_text'] ) . '</a></div>' . "\n";
				}

				$html .= "</div>\n"; // <!-- .slide-content -->
			}

			$html .= '</div>' . "\n"; // <!-- .swiper-slide -->
		}

		$html .= '</div>' . "\n"; // <!-- .swiper-wrapper -->

		if ( isset( $slide->params['pagination'] ) && $slide->params['pagination'] ) {
			$html .= '<div class="swiper-pagination swiper-pagination-white"></div>' . "\n";
		}

		if ( isset( $slide->params['navigation'] ) && $slide->params['navigation'] ) {
			$html .= '<div class="swiper-button-next swiper-button-white"></div>' . "\n";
			$html .= '<div class="swiper-button-prev swiper-button-white"></div>' . "\n";
		}

		$html .= '</div>' . "\n"; // <!-- swiper-container -->

		$thumbs_style = '';
		if ( ! empty( $slide->params['thumbs_width'] ) ) {
			$thumbs_style .= "max-width:{$slide->params['thumbs_width']}px;";
		}
		if ( ! empty( $slide->params['thumbs_height'] ) ) {
			$thumbs_style .= "height:{$slide->params['thumbs_height']}px;";
		}
		if ( ! empty( $slide->params['thumbs_margin_top'] ) ) {
			$thumbs_style .= "margin-top:{$slide->params['thumbs_margin_top']}px;";
		}
		if ( $thumbs_style ) {
			$thumbs_style = " style=\"{$thumbs_style}\"";
		}

		$html .= '<div class="swiper swiper-container gallery-thumbs"' . $thumbs_style . ">\n";
		$html .= '<div class="swiper-wrapper">' . "\n";
		foreach ( $imgs as $key => $img ) {
			$html .= "<div class=\"swiper-slide\">{$img}</div>\n";
		}

		$html .= "</div>\n"; // <!-- .swiper-wrapper -->
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
		$thumb_params = array(
			'slidesPerView'         => (int) ( isset( $slide->params['thumbs_per_view'] ) && $slide->params['thumbs_per_view'] ) ? $slide->params['thumbs_per_view'] : count( $slide->slides ),
			'freeMode'              => true,
			'watchSlidesVisibility' => true,
			'watchSlidesProgress'   => true,
		);

		if ( ! empty( $slide->params['thumbs_space_between'] ) ) {
			$thumb_params['spaceBetween'] = $slide->params['thumbs_space_between'];
		}

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

		$params['speed']          = (int) ( $slide->params['speed'] ?? 600 );
		$params['scrollbar']      = array( 'hide' => true );
		$params['loop']           = (bool) ( $slide->params['loop'] ?? true );
		$params['centeredSlides'] = (bool) ( $slide->params['centered_slides'] ?? false );

		if ( isset( $slide->params['effect'] ) && in_array( $slide->params['effect'], array( 'slide', 'fade', 'cube', 'coverflow', 'flip', 'cards', 'creative' ), true ) ) {
			$params['effect'] = $slide->params['effect'];
		}

		if ( ! empty( $slide->params['autoplay'] ) ) {
			$params['autoplay'] = array(
				'delay'                => (int) ( $slide->params['delay'] ?? 3000 ),
				'stopOnLastSlide'      => (bool) ( $slide->params['stop_on_last_slide'] ?? false ),
				'disableOnInteraction' => (bool) ( $slide->params['disable_on_interaction'] ?? true ),
			);
		} else {
			$params['autoplay'] = false;
		}

		if ( isset( $slide->params['slides_per_view'] ) && ! empty( $slide->params['slides_per_view'] ) ) {
			$params['slidesPerView'] = (float) $slide->params['slides_per_view'];
		}

		if ( isset( $slide->params['auto_height'] ) && ! empty( $slide->params['auto_height'] ) ) {
			$params['autoHeight'] = (bool) $slide->params['auto_height'];
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
		$thumb_params = apply_filters( 'xo_slider_script_parameter', $thumb_params, $slide, 'thumbs' );
		$params       = apply_filters( 'xo_slider_script_parameter', $params, $slide, '' );

		$thumb_json = wp_json_encode( $thumb_params );
		$json       = substr( substr( wp_json_encode( $params ), 1 ), 0, -1 );

		$script =
			"var xoSlider{$slide->id}Thumbs = new Swiper('#xo-slider-{$slide->id} .gallery-thumbs', {$thumb_json});" .
			"var xoSlider{$slide->id} = new Swiper('#xo-slider-{$slide->id} .gallery-main', {{$json}, thumbs: {swiper: xoSlider{$slide->id}Thumbs}});";

		return $script;
	}
}
