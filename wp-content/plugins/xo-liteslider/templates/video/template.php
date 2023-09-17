<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * XO Slider Template.
 *
 * @version 2.2.0
 *
 * If you need to edit this file, copy this template.php and style.css files to the following directory
 * in the theme. The files in the theme take precedence.
 *
 * Directory: "(Theme directory)/xo-liteslider/templates/(Template ID)/"
 */
class XO_Slider_Template_Video extends XO_Slider_Template_Base {
	/**
	 * Template ID.
	 *
	 * @var string
	 */
	public $id = 'video';

	/**
	 * Template name.
	 *
	 * @var string
	 */
	public $name = 'Video';

	/**
	 * Template version.
	 *
	 * @var string
	 */
	public $version = '2.2.0';

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
		return __( 'A template that displays a video.', 'xo-liteslider' );
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
			if ( empty( $slide_data['media_id'] ) && empty( $slide_data['media_link'] ) && empty( $slide->params['content'] ) ) {
				continue;
			}

			if ( isset( $slide_data['media_type'] ) && in_array( $slide_data['media_type'], array( 'image', 'video', 'youtube' ), true ) ) {
				$mime_type_class = " mime-type-{$slide_data['media_type']}";
			} else {
				$mime_type_class = '';
			}

			$html .= "<div class=\"swiper-slide{$mime_type_class}\">";

			if ( 'image' === $slide_data['media_type'] ) {
				$attr = array( 'class' => 'slide-image' );
				if ( isset( $slide_data['alt'] ) ) {
					$attr['alt'] = $slide_data['alt'];
				}
				if ( isset( $slide_data['title_attr'] ) ) {
					$attr['title'] = $slide_data['title_attr'];
				}
				$img = wp_get_attachment_image( $slide_data['media_id'], 'full', false, $attr );
				if ( $img ) {
					$html .= '<div class="slide-image">';
					if ( ! empty( $slide_data['link'] ) ) {
						$target = ( ! empty( $slide_data['link_newwin'] ) && $slide_data['link_newwin'] ) ? ' target="_blank" rel="noopener noreferrer"' : '';
						$html  .= '<a href="' . esc_url( $slide_data['link'] ) . '"' . $target . '>' . $img . '</a>';
					} else {
						$html .= $img;
					}
					$html .= '</div>';
				}
			} elseif ( 'video' === $slide_data['media_type'] ) {
				$html .= $this->get_attachment_video( $slide_data );
			} elseif ( 'youtube' === $slide_data['media_type'] ) {
				$html .= $this->get_embed_youtube( $slide_data );
			}

			if ( ! empty( $slide->params['content'] && 'youtube' !== $slide_data['media_type'] ) ) {
				$html .= '<div class="slide-content">' . wp_kses_post( $slide_data['content'] ) . '</div>';
			}

			$html .= "</div>\n"; // <!-- .swiper-slide -->
		}
		$html .= "</div>\n"; // <!-- .swiper-wrapper -->

		if ( isset( $slide->params['pagination'] ) && $slide->params['pagination'] ) {
			$html .= "<div class=\"swiper-pagination swiper-pagination-white\"></div>\n";
		}

		if ( isset( $slide->params['navigation'] ) && $slide->params['navigation'] ) {
			$html .= "<div class=\"swiper-button-prev swiper-button-white\"></div>\n";
			$html .= "<div class=\"swiper-button-next swiper-button-white\"></div>\n";
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

		$params['speed']          = (int) isset( $slide->params['speed'] ) ? $slide->params['speed'] : 600;
		$params['scrollbar']      = array( 'hide' => true );
		$params['loop']           = (bool) isset( $slide->params['loop'] ) ? $slide->params['loop'] : true;
		$params['centeredSlides'] = (bool) isset( $slide->params['centered_slides'] ) ? $slide->params['centered_slides'] : false;

		if ( isset( $slide->params['effect'] ) && in_array( $slide->params['effect'], array( 'slide', 'fade', 'cube', 'coverflow', 'flip', 'cards', 'creative' ), true ) ) {
			$params['effect'] = $slide->params['effect'];
		}

		if ( ! empty( $slide->params['autoplay'] ) ) {
			$params['autoplay'] = array(
				'delay'                => (int) isset( $slide->params['delay'] ) ? $slide->params['delay'] : 3000,
				'stopOnLastSlide'      => (bool) isset( $slide->params['stop_on_last_slide'] ) ? $slide->params['stop_on_last_slide'] : false,
				'disableOnInteraction' => (bool) isset( $slide->params['disable_on_interaction'] ) ? $slide->params['disable_on_interaction'] : true,
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
		$params = apply_filters( 'xo_slider_script_parameter', $params, $slide, null );

		$json = wp_json_encode( $params );

		$script = "var xoSlider{$slide->id} = new Swiper('#xo-slider-{$slide->id} .swiper-container', {$json});";

		$script .= <<<SCRIPT
xoSlider{$slide->id}.on( 'transitionStart', function() {
	var videos = document.querySelectorAll('#xo-slider-{$slide->id} .swiper-container video');
	Array.prototype.forEach.call(videos, function(video){
		video.pause();
	} );
} );
xoSlider{$slide->id}.on( 'transitionEnd', function() {
	var activeIndex = this.activeIndex;
	var activeSlide = document.getElementsByClassName('swiper-slide')[activeIndex];
	var activeSlideVideo = activeSlide.getElementsByTagName('video')[0];
	activeSlideVideo.play();
} );
SCRIPT;

		return $script;
	}
}
