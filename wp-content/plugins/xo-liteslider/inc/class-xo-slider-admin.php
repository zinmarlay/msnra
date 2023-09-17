<?php
/**
 * XO Slider admin.
 *
 * @package xo-slider
 * @since 1.0.0
 */

/**
 * XO Slider admin class.
 */
class XO_Slider_Admin {
	/**
	 * XO_Event_Calendar instance.
	 *
	 * @var XO_Slider
	 */
	private $parent;

	/**
	 * Slide parameters.
	 *
	 * @var array
	 */
	private $parameters;

	/**
	 * Construction.
	 *
	 * @since 1.0.0
	 *
	 * @param XO_Slider $parent Parent object.
	 */
	public function __construct( $parent ) {
		$this->parent = $parent;
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
	}

	/**
	 * Runs on plugins_loaded hook.
	 *
	 * @since 1.0.0
	 */
	public function plugins_loaded() {
		add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );
		add_filter( 'bulk_post_updated_messages', array( $this, 'bulk_updated_messages' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'remove_row_actions' ), 10, 2 );
		add_action( 'save_post', array( $this, 'save_post' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		// // add_action( 'edit_form_after_title', array( $this, 'edit_form_after_title' ) );
	}

	/**
	 * Get parameter from name.
	 *
	 * @param array  $array Parameters.
	 * @param string $name  Parameter name.
	 * @param void   $default Default value.
	 */
	private function get_value( &$array, $name, $default = '' ) {
		return isset( $array[ $name ] ) ? $array[ $name ] : $default;
	}

	/**
	 * Enqueue a script in the WordPress admin.
	 *
	 * @since 1.0.0
	 */
	public function admin_enqueue_scripts() {
		global $post, $post_type;

		if ( isset( $post ) && 'xo_liteslider' === $post_type ) {
			$min = SCRIPT_DEBUG ? '' : '.min';

			wp_enqueue_style( 'xo-slider-admin', plugins_url( "css/admin{$min}.css", __DIR__ ), array(), XO_SLIDER_VERSION );

			// Load the script for the media uploader.
			wp_enqueue_media( array( 'post' => $post->ID ) );
			wp_enqueue_script( 'xo-slider-admin', plugins_url( "js/admin{$min}.js", __DIR__ ), array( 'jquery' ), XO_SLIDER_VERSION, true );
			wp_localize_script(
				'xo-slider-admin',
				'messages',
				array(
					'title'  => __( 'Select Media', 'xo-liteslider' ),
					'button' => __( 'Select', 'xo-liteslider' ),
				)
			);
		}
	}

	/**
	 * Filters the post updated messages.
	 *
	 * @since 1.0.0
	 *
	 * @param array $messages Post updated messages. For defaults see `$messages` declarations above.
	 */
	public function updated_messages( $messages ) {
		global $post;
		$messages['xo_liteslider'] = array(
			0  => '',
			1  => __( 'Slider updated.', 'xo-liteslider' ),
			2  => __( 'Custom field updated.', 'xo-liteslider' ),
			3  => __( 'Custom field deleted.', 'xo-liteslider' ),
			4  => __( 'Slider updated.', 'xo-liteslider' ),
			/* translators: %s: Retrieves formatted date timestamp. */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Slider restored to revision from %s.', 'xo-liteslider' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false, // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Copying core message handling.
			6  => __( 'Slider published.', 'xo-liteslider' ),
			7  => __( 'Slider saved.', 'xo-liteslider' ),
			8  => __( 'Slider submitted.', 'xo-liteslider' ),
			/* translators: 1: Publishing time. */
			9  => sprintf( __( 'Slider scheduled for: <strong>%1$s</strong>.', 'xo-liteslider' ), date_i18n( __( 'M j, Y @ G:i', 'xo-liteslider' ), strtotime( $post->post_date ) ) ),
			10 => __( 'Slider draft updated.', 'xo-liteslider' ),
		);
		return $messages;
	}

	/**
	 * Bulk action updated messages.
	 *
	 * @param array[] $bulk_messages See bulk_post_updated_messages hook.
	 * @param int[]   $bulk_counts   See bulk_post_updated_messages hook.
	 */
	public function bulk_updated_messages( $bulk_messages, $bulk_counts ) {
		$bulk_messages['xo_liteslider'] = array(
			/* translators: %s: Number of posts. */
			'updated'   => _n( '%s slider updated.', '%s sliders updated.', $bulk_counts['updated'], 'xo-liteslider' ),
			/* translators: %s: Number of posts. */
			'locked'    => _n( '%s slidert not updated, somebody is editing it.', '%s slider not updated, somebody is editing them.', $bulk_counts['locked'], 'xo-liteslider' ),
			/* translators: %s: Number of posts. */
			'deleted'   => _n( '%s slidert permanently deleted.', '%s sliders permanently deleted.', $bulk_counts['deleted'], 'xo-liteslider' ),
			/* translators: %s: Number of posts. */
			'trashed'   => _n( '%s slider moved to the Trash.', '%s sliders moved to the Trash.', $bulk_counts['trashed'], 'xo-liteslider' ),
			/* translators: %s: Number of posts. */
			'untrashed' => _n( '%s slider restored from the Trash.', '%s sliders restored from the Trash.', $bulk_counts['untrashed'], 'xo-liteslider' ),
		);
		return $bulk_messages;
	}

	/**
	 * Add actions to feedback response rows in WP Admin.
	 *
	 * @param string[] $actions Default actions.
	 * @param WP_Post  $post    Post.
	 * @return string[]
	 */
	public function remove_row_actions( $actions, $post ) {
		if ( 'xo_liteslider' !== $post->post_type ) {
			return $actions;
		}
		unset( $actions['inline hide-if-no-js'] );
		return $actions;
	}

	/**
	 * Save the value of the event metabox.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id The current post ID.
	 */
	public function save_post( $post_id ) {
		if ( ! isset( $_POST['xo_slider_nonce'] ) ||
			! check_admin_referer( 'xo_slider_key', 'xo_slider_nonce' ) ||
			! current_user_can( 'edit_post', $post_id )
		) {
			return $post_id;
		}

		if ( isset( $_POST['xo_slider_slides'] ) && is_array( $_POST['xo_slider_slides'] ) ) {
			$slides       = array();
			$post_sliders = wp_unslash( $_POST['xo_slider_slides'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Manually validated.
			foreach ( $post_sliders as $index => $data ) {
				$slides[ $index ]['media_type'] = $data['media_type'];
				if ( 'youtube' === $data['media_type'] ) {
					$slides[ $index ]['media_id']   = 0;
					$slides[ $index ]['media_link'] = esc_url_raw( $data['media_link'] );
				} else {
					$slides[ $index ]['media_id']   = isset( $data['media_id'] ) ? (int) $data['media_id'] : 0;
					$slides[ $index ]['image_id']   = $slides[ $index ]['media_id'];
					$slides[ $index ]['media_link'] = null;
				}

				$slides[ $index ]['title_attr']  = sanitize_text_field( $data['title_attr'] );
				$slides[ $index ]['alt']         = sanitize_text_field( $data['alt'] );
				$slides[ $index ]['link']        = esc_url_raw( $data['link'] );
				$slides[ $index ]['link_newwin'] = isset( $data['link_newwin'] );

				$slides[ $index ]['content'] = wp_filter_post_kses( $data['content'] );

				$slides[ $index ]['title']         = wp_filter_post_kses( $data['title'] );
				$slides[ $index ]['subtitle']      = wp_filter_post_kses( $data['subtitle'] );
				$slides[ $index ]['button_text']   = wp_filter_post_kses( $data['button_text'] );
				$slides[ $index ]['button_link']   = esc_url_raw( $data['button_link'] );
				$slides[ $index ]['button_newwin'] = isset( $data['button_newwin'] );

				$slides[ $index ]['video_autoplay'] = isset( $data['video_autoplay'] );
				$slides[ $index ]['video_loop']     = isset( $data['video_loop'] );
				$slides[ $index ]['video_muted']    = isset( $data['video_muted'] );
				$slides[ $index ]['video_controls'] = isset( $data['video_controls'] );
				$slides[ $index ]['video_inline']   = isset( $data['video_inline'] );
				$slides[ $index ]['video_preload']  = sanitize_text_field( $data['video_preload'] );
			}

			update_post_meta( $post_id, 'slides', $slides );
		}

		if ( isset( $_POST['xo_slider_parameters'] ) && is_array( $_POST['xo_slider_parameters'] ) ) {
			$data = wp_unslash( $_POST['xo_slider_parameters'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Manually validated.

			$params = array();

			$params['template'] = isset( $data['template'] ) ? sanitize_text_field( $data['template'] ) : 'default';

			$params['width']            = ( isset( $data['width'] ) && '' !== trim( $data['width'] ) ) ? (int) $data['width'] : '';
			$params['height']           = ( isset( $data['height'] ) && '' !== trim( $data['height'] ) ) ? (int) $data['height'] : '';
			$params['effect']           = isset( $data['effect'] ) ? sanitize_text_field( $data['effect'] ) : 'slide';
			$params['navigation']       = isset( $data['navigation'] );
			$params['pagination']       = isset( $data['pagination'] );
			$params['content']          = isset( $data['content'] );
			$params['sort']             = isset( $data['sort'] ) ? sanitize_text_field( $data['sort'] ) : 'asc';
			$params['loop']             = isset( $data['loop'] );
			$params['speed']            = isset( $data['speed'] ) ? (int) $data['speed'] : 300;
			$params['auto_height']      = isset( $data['auto_height'] );
			$params['slides_per_group'] = ( isset( $data['slides_per_group'] ) && '' !== trim( $data['slides_per_group'] ) ) ? (int) $data['slides_per_group'] : '';
			$params['slides_per_view']  = ( isset( $data['slides_per_view'] ) && '' !== trim( $data['slides_per_view'] ) ) ? (float) $data['slides_per_view'] : '';
			$params['space_between']    = ( isset( $data['space_between'] ) && '' !== trim( $data['space_between'] ) ) ? (int) $data['space_between'] : '';
			$params['centered_slides']  = isset( $data['centered_slides'] );

			$params['autoplay']               = isset( $data['autoplay'] );
			$params['delay']                  = isset( $data['delay'] ) ? (int) $data['delay'] : 3000;
			$params['stop_on_last_slide']     = isset( $data['stop_on_last_slide'] );
			$params['disable_on_interaction'] = isset( $data['disable_on_interaction'] );

			$params['thumbs_width']         = ( isset( $data['thumbs_width'] ) && '' !== trim( $data['thumbs_width'] ) ) ? (int) $data['thumbs_width'] : '';
			$params['thumbs_height']        = ( isset( $data['thumbs_height'] ) && '' !== trim( $data['thumbs_height'] ) ) ? (int) $data['thumbs_height'] : '';
			$params['thumbs_per_view']      = ( isset( $data['thumbs_per_view'] ) && '' !== trim( $data['thumbs_per_view'] ) ) ? (float) $data['thumbs_per_view'] : '';
			$params['thumbs_space_between'] = ( isset( $data['thumbs_space_between'] ) && '' !== trim( $data['thumbs_space_between'] ) ) ? (int) $data['thumbs_space_between'] : '';
			$params['thumbs_margin_top']    = ( isset( $data['thumbs_margin_top'] ) && '' !== trim( $data['thumbs_margin_top'] ) ) ? (int) $data['thumbs_margin_top'] : '';

			update_post_meta( $post_id, 'parameters', $params );
		}
	}

	/**
	 * Add a menu.
	 *
	 * @since 1.0.0
	 */
	public function admin_menu() {
		$page = add_submenu_page( 'edit.php?post_type=xo_liteslider', 'Option Settings', __( 'Settings', 'xo-liteslider' ), 'manage_options', 'xo-liteslider-settings', array( $this, 'settings_page' ) );
		add_action( "load-{$page}", array( $this, 'add_settings_page_tabs' ) );
	}

	/**
	 * Add a help tab to the contextual help for the screen.
	 *
	 * @since 3.2.0
	 */
	public function add_settings_page_tabs() {
		$screen = get_current_screen();
		$screen->add_help_tab(
			array(
				'id'      => 'settings-help',
				'title'   => __( 'Overview', 'xo-liteslider' ),
				'content' => '<p>' . __( 'This screen allows you to configure the XO Slider.', 'xo-liteslider' ) . '</p>',
			)
		);
		$screen->add_help_tab(
			array(
				'id'      => 'general-config',
				'title'   => __( 'CSS location', 'xo-liteslider' ),
				'content' => '<p>' . __( 'CSS location specifies where to place the CSS for the template and slider. Placement in header means that all template styles will be incorporated into the header of all pages. Header placement means that the styles of all templates will be incorporated into the header of all pages.', 'xo-liteslider' ) . '</p>',
			)
		);
	}

	/**
	 * Output the settings page.
	 *
	 * @since 3.2.0
	 */
	public function settings_page() {
		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'XO Slider Settings', 'xo-liteslider' ) . '</h1>';

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- ignoring since we are just displaying that the settings have been saved and not making  any other changes to the site.
		if ( isset( $_GET['settings-updated'] ) ) {
			echo '<div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible">';
			echo '<p><strong>' . esc_html__( 'Settings saved.', 'xo-liteslider' ) . '</strong></p>';
			echo '</div>';
		}

		echo '<div id="xo-event-settings">';
		echo '<form method="post" action="options.php">';
		settings_fields( 'xo_slider_group' );
		do_settings_sections( 'xo_slider_group' );
		submit_button();
		echo '</form>';
		echo '</div>';

		echo "</div>\n"; // <!-- .wrap -->
	}

	/**
	 * Initialize slider page.
	 *
	 * @since 1.0.0
	 */
	public function admin_init() {
		// Slider edit page.
		remove_meta_box( 'submitdiv', 'xo_liteslider', 'core' );
		add_meta_box( 'xo-slider-meta-slide', __( 'Slides', 'xo-liteslider' ), array( $this, 'display_meta' ), 'xo_liteslider', 'normal', 'high' );
		add_meta_box( 'xo-slider-meta-template', __( 'Template', 'xo-liteslider' ), array( $this, 'display_meta_template' ), 'xo_liteslider', 'side', 'low' );
		add_meta_box( 'xo-slider-meta-parameter', __( 'Parameter', 'xo-liteslider' ), array( $this, 'display_meta_parameter' ), 'xo_liteslider', 'side', 'low' );
		add_meta_box( 'xo-slider-meta-usage', __( 'Usage', 'xo-liteslider' ), array( $this, 'display_meta_usage' ), 'xo_liteslider', 'side', 'low' );
		add_meta_box( 'submitdiv', __( 'Save', 'xo-liteslider' ), array( $this, 'submit_meta_box' ), 'xo_liteslider', 'side', 'high', null );

		// Settings page.
		register_setting( 'xo_slider_group', 'xo_slider_options', array( $this, 'sanitize_settings' ) );
		add_settings_section( 'xo_slider_general_section', '', '__return_empty_string', 'xo_slider_group' );
		add_settings_field( 'css_load', __( 'Templates CSS', 'xo-liteslider' ), array( $this, 'field_css_load' ), 'xo_slider_group', 'xo_slider_general_section' );
		add_settings_field( 'swiper_version', __( 'Swiper version', 'xo-liteslider' ), array( $this, 'field_swiper_version' ), 'xo_slider_group', 'xo_slider_general_section' );

		add_settings_field( 'delete_settings', __( 'Processing when deleting plugin', 'xo-liteslider' ), array( $this, 'field_delete_settings' ), 'xo_slider_group', 'xo_slider_general_section' );
	}

	/**
	 * Sanitize settings.
	 *
	 * @since 3.2.0
	 *
	 * @param array $input Input data.
	 */
	public function sanitize_settings( $input ) {
		$input['version']     = XO_SLIDER_VERSION;
		$input['delete_data'] = isset( $input['delete_data'] );
		return $input;
	}

	/**
	 * Display CSS load field.
	 *
	 * @since 3.2.0
	 */
	public function field_css_load() {
		$value = ! empty( $this->parent->options['css_load'] ) ? $this->parent->options['css_load'] : 'body';

		echo '<fieldset><legend class="screen-reader-text"><span>' . esc_html__( 'CSS location', 'xo-liteslider' ) . '</span></legend>';
		echo '<p>';
		echo '<label><input name="xo_slider_options[css_load]" type="radio" value="none" ' . checked( 'none', $value, false ) . '> ' . esc_html__( 'Do not include', 'xo-liteslider' ) . '</label><br>';
		echo '<label><input name="xo_slider_options[css_load]" type="radio" value="head" ' . checked( 'head', $value, false ) . '> ' . esc_html__( 'Place in header', 'xo-liteslider' ) . '</label><br>';
		echo '<label><input name="xo_slider_options[css_load]" type="radio" value="body" ' . checked( 'body', $value, false ) . '> ' . esc_html__( 'Place in footer', 'xo-liteslider' ) . '</label>';
		echo '</p>';
		echo '<p class="description">' . esc_html__( 'Typically, you should choose to place it in the footer.', 'xo-liteslider' ) . '</p>';
		echo '</fieldset>';
	}

	/**
	 * Display Swiper version field.
	 *
	 * @since 3.6.0
	 */
	public function field_swiper_version() {
		$value = ! empty( $this->parent->options['swiper_version'] ) ? $this->parent->options['swiper_version'] : '';

		echo '<fieldset><legend class="screen-reader-text"><span>' . esc_html__( 'Swiper version', 'xo-liteslider' ) . '</span></legend>';
		echo '<select name="xo_slider_options[swiper_version]">';
		echo '<option value=""' . ( '' === $value ? ' selected' : '' ) . '>' . esc_html__( 'Default', 'xo-liteslider' ) . '</option>';
		echo '<option value="8"' . ( '8' === $value ? ' selected' : '' ) . '>' . esc_html__( '8 series', 'xo-liteslider' ) . '</option>';
		echo '</select>';
		echo '</fieldset>';
	}

	/**
	 * Display delete data field.
	 *
	 * @since 3.6.0
	 */
	public function field_delete_settings() {
		$value = ! empty( $this->parent->options['delete_data'] ) ? $this->parent->options['delete_data'] : false;

		echo '<fieldset><legend class="screen-reader-text"><span>' . esc_html__( 'Delete plugin data', 'xo-liteslider' ) . '</span></legend>';
		echo '<label><input name="xo_slider_options[delete_data]" type="checkbox" value="1" ' . checked( true, $value, false ) . '> ' . esc_html__( 'Delete plugin data', 'xo-liteslider' ) . '</label><br>';
		echo '</fieldset>';
	}

	/**
	 * Display submit meta box,
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post Post.
	 * @param array   $args Extra meta box arguments.
	 */
	public function submit_meta_box( $post, $args = array() ) {
		echo '<div class="submitbox" id="submitpost">';
		echo '<div id="major-publishing-actions" style="border-top: 0;">';

		echo '<div style="display:none;">' . get_submit_button( __( 'Save' ), 'button', 'save' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- See get_submit_button()

		do_action( 'post_submitbox_start' );

		echo '<div id="delete-action">';
		if ( current_user_can( 'delete_post', $post->ID ) ) {
			if ( ! EMPTY_TRASH_DAYS ) {
				$delete_text = __( 'Delete Permanently' );
			} else {
				$delete_text = __( 'Move to Trash' );
			}
			echo '<a class="submitdelete deletion" href="' . esc_url( get_delete_post_link( $post->ID ) ) . '">' . esc_html( $delete_text ) . '</a>';
		}
		echo '</div>';

		echo '<div id="publishing-action">';
		echo '<span class="spinner"></span>';
		if ( ! in_array( $post->post_status, array( 'publish', 'future', 'private' ), true ) || 0 === $post->ID ) {
			echo '<input name="original_publish" type="hidden" id="original_publish" value="' . esc_attr__( 'Publish' ) . '" />';
			submit_button( __( 'Save' ), 'primary button-large', 'publish', false );
		} else {
			echo '<input name="original_publish" type="hidden" id="original_publish" value="' . esc_attr__( 'Update' ) . '" />';
			echo '<input name="save" type="submit" class="button button-primary button-large" id="publish" value="' . esc_attr__( 'Update' ) . '" />';
		}
		echo '</div>';
		echo '<div class="clear"></div>';

		echo '</div>';
		echo '</div>';
	}

	/**
	 * Display shortcode code.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_POst $post Post.
	 */
	public function edit_form_after_title( $post ) {
		if ( 'xo_liteslider' !== $post->post_type || 'auto-draft' === $post->post_status ) {
			return;
		}
		?>
		<p class="description">
			<label for="xo-slider-shortcode"><?php esc_html_e( 'Copy this shortcode, please paste it into the post or page:', 'xo-liteslider' ); ?></label>
			<span class="shortcode wp-ui-primary">
			<input id="xo-slider-shortcode" onfocus="this.select();" readonly="readonly" class="large-text code" value="[xo_slider id=&quot;<?php echo esc_attr( $post->ID ); ?>&quot;]" type="text">
			</span>
		</p>
		<?php
	}

	/**
	 * Display meta boxs.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_POst $post Post.
	 */
	public function display_meta( $post ) {
		$slides = get_post_meta( $post->ID, 'slides', true );
		if ( empty( $slides ) ) {
			$slides = array(
				array(
					'media_type'     => '',
					'media_id'       => 0,
					'media_link'     => '',
					'title_attr'     => '',
					'alt'            => '',
					'link'           => '',
					'link_newwin'    => false,
					'content'        => '',
					'title'          => '',
					'subtitle'       => '',
					'button_text'    => '',
					'button_link'    => '',
					'button_newwin'  => false,
					'video_autoplay' => true,
					'video_loop'     => true,
					'video_muted'    => true,
					'video_controls' => false,
					'video_inline'   => true,
					'video_preload'  => 'metadata',
				),
			);
		}

		echo '<div id="xo-slider-slide">';
		echo '<p class="howto">' . esc_html__( 'Drag the header of each item to change the order.', 'xo-liteslider' ) . '</p>';
		echo '<ul class="slide-repeat ui-sortable">';

		$counter = 0;
		foreach ( $slides as $key => $value ) {
			$media_link = ! empty( $value['media_link'] ) ? $value['media_link'] : null;
			if ( ! isset( $value['media_id'] ) && isset( $value['image_id'] ) ) {
				$media_id   = $value['image_id'];
				$media_type = 'image';
			} else {
				$media_type = ! empty( $value['media_type'] ) ? $value['media_type'] : 'image';
				$media_id   = isset( $value['media_id'] ) ? (int) $value['media_id'] : 0;
			}

			$link_newwin = ! empty( $value['link_newwin'] ) ? $value['link_newwin'] : false;

			$video_autoplay = ! empty( $value['video_autoplay'] ) ? $value['video_autoplay'] : false;
			$video_loop     = ! empty( $value['video_loop'] ) ? $value['video_loop'] : false;
			$video_muted    = ! empty( $value['video_muted'] ) ? $value['video_muted'] : false;
			$video_controls = ! empty( $value['video_controls'] ) ? $value['video_controls'] : false;
			$video_inline   = ! empty( $value['video_inline'] ) ? $value['video_inline'] : false;
			$video_preload  = ! empty( $value['video_preload'] ) ? $value['video_preload'] : 'metadata';

			$button_newwin = ! empty( $value['button_newwin'] ) ? $value['button_newwin'] : false;

			$image_src = false;
			if ( 'youtube' === $media_type ) {
				if ( 1 === preg_match( '/[\/?=]([a-zA-Z0-9_\-]{11})[&\?]?/', $media_link, $matches ) ) {
					$image_src = 'http://img.youtube.com/vi/' . $matches[1] . '/default.jpg';
				}
			} else {
				if ( ! empty( $media_id ) ) {
					$image_src = wp_get_attachment_image_src( $media_id, array( 150, 150 ), true )[0];
				}
			}

			echo '<li class="slide">';

			echo '<div class="slide-header">';
			echo '<span class="slide-header-title"></span>';
			echo '<span class="slide-header-button slide-header-append-button dashicons dashicons-plus-alt" title="' . esc_attr( __( 'Add Slide', 'xo-liteslider' ) ) . '"></span>';
			echo '<span class="slide-header-button slide-header-remove-button dashicons dashicons-trash" title="' . esc_attr( __( 'Delete Slide', 'xo-liteslider' ) ) . '"></span>';
			echo '<span class="slide-header-button slide-header-toggle-button dashicons dashicons-arrow-up"></span>';
			echo '</div>';

			echo '<div class="slide-inner">';

			echo '<table class="slide-table"><tbody>';
			echo '<tr>';
			echo '<td style="width: 160px;">';
			echo '<div class="slide-image" title="' . esc_attr( __( 'Select Media', 'xo-liteslider' ) ) . '">';
			if ( $image_src ) {
				echo '<img loading="lazy" src="' . esc_url( $image_src ) . '" alt="" title="' . esc_attr( __( 'Select Media', 'xo-liteslider' ) ) . '" />';
				if ( 'video' === $media_type ) {
					$filename = basename( get_attached_file( $media_id ) );
					echo '<div class="filename"><div>' . esc_html( $filename ) . '</div></div>';
				} elseif ( 'youtube' === $media_type ) {
					echo '<div class="filename"><div>' . esc_html( $media_link ) . '</div></div>';
				}
				echo '<div class="message" style="display: none;"><span>' . esc_html__( 'Select Media', 'xo-liteslider' ) . '</span></div>';
			} else {
				echo '<div class="message"><span>' . esc_html__( 'Select Media', 'xo-liteslider' ) . '</span></div>';
			}

			echo '</div>';
			echo '<input class="slide-media-type" name="xo_slider_slides[' . (int) $counter . '][media_type]" type="hidden" value="' . esc_attr( $media_type ) . '" />';
			echo '<input class="slide-media-id" name="xo_slider_slides[' . (int) $counter . '][media_id]" type="hidden" value="' . esc_attr( $media_id ) . '" />';
			echo '<input class="slide-media-link" name="xo_slider_slides[' . (int) $counter . '][media_link]" type="hidden" value="' . esc_attr( $media_link ) . '" />';
			echo '<span class="slide-image-button slide-image-clear" title="' . esc_attr( __( 'Clear Media', 'xo-liteslider' ) ) . '"></span>';
			echo '</td>';
			echo '<td>';

			echo '<div class="slide-panel-wrapper">';

			echo '<ul class="slide-panel-tabs">';
			echo '<li class="tabs"><a class="nav-tab-link" data-type="slide-panel-general" href="#">' . esc_html__( 'Image', 'xo-liteslider' ) . '</a></li>';
			echo '<li><a class="nav-tab-link" data-type="slide-panel-content" href="#">' . esc_html__( 'Content', 'xo-liteslider' ) . '</a></li>';
			echo '<li><a class="nav-tab-link" data-type="slide-panel-option" href="#">' . esc_html__( 'Option', 'xo-liteslider' ) . '</a></li>';
			echo '<li><a class="nav-tab-link" data-type="slide-panel-video" href="#">' . esc_html__( 'Video', 'xo-liteslider' ) . '</a></li>';
			echo '</ul>';

			echo '<div class="slide-panel tabs-slide-panel-general tabs-panel-active">';
			echo '<p><label for="xo_slider_slides[' . (int) $counter . '][title_attr]">' . esc_html__( 'Title Attribute:', 'xo-liteslider' ) . '</label></p>' .
				'<p><input id="xo_slider_slides[' . (int) $counter . '][title_attr]" name="xo_slider_slides[' . (int) $counter . '][title_attr]" type="text" value="' . esc_attr( $this->get_value( $value, 'title_attr' ) ) . '" /></p>';
			echo '<p><label for="xo_slider_slides[' . (int) $counter . '][alt]">' . esc_html__( 'Alt text (alternative text):', 'xo-liteslider' ) . '</label></p>' .
				'<p><input id="xo_slider_slides[' . (int) $counter . '][alt]" name="xo_slider_slides[' . (int) $counter . '][alt]" type="text" value="' . esc_attr( $this->get_value( $value, 'alt', '' ) ) . '" /></p>';
			echo '<p><label for="xo_slider_slides[' . (int) $counter . '][link]">' . esc_html__( 'Link (URL):', 'xo-liteslider' ) . '</label>' .
				'<span style="float: right;"><label for="xo_slider_slides[' . (int) $counter . '][link_newwin]">' . esc_html__( 'Open in new window:', 'xo-liteslider' ) . '</label> ' .
				'<input id="xo_slider_slides[' . (int) $counter . '][link_newwin]" name="xo_slider_slides[' . (int) $counter . '][link_newwin]" type="checkbox" value="1" ' . checked( $link_newwin, true, false ) . '/><label for="xo_slider_slides[' . (int) $counter . '][link_newwin]"></label></span></p>' .
				'<p><input id="xo_slider_slides[' . (int) $counter . '][link]" name="xo_slider_slides[' . (int) $counter . '][link]" type="text" value="' . esc_attr( $this->get_value( $value, 'link' ) ) . '" /></p>';
			echo '</div>';

			echo '<div class="slide-panel tabs-slide-panel-content tabs-panel-inactive">';
			echo '<p><label for="xo_slider_slides[' . (int) $counter . '][content]">' . esc_html__( 'Content (HTML):', 'xo-liteslider' ) . '</label></p>' .
				'<textarea id="xo_slider_slides[' . (int) $counter . '][content]" name="xo_slider_slides[' . (int) $counter . '][content]" rows="8">' . esc_attr( $this->get_value( $value, 'content' ) ) . '</textarea></p>';
			echo '</div>';

			echo '<div class="slide-panel tabs-slide-panel-option tabs-panel-inactive">';
			echo '<p><label for="xo_slider_slides[' . (int) $counter . '][title]">' . esc_html__( 'Title:', 'xo-liteslider' ) . '</label></p>' .
				'<p><input id="xo_slider_slides[' . (int) $counter . '][title]" name="xo_slider_slides[' . (int) $counter . '][title]" type="text" value="' . esc_attr( $this->get_value( $value, 'title' ) ) . '" /></p>';
			echo '<p><label for="xo_slider_slides[' . (int) $counter . '][subtitle]">' . esc_html__( 'Subtitle:', 'xo-liteslider' ) . '</label></p>' .
				'<p><input id="xo_slider_slides[' . (int) $counter . '][subtitle]" name="xo_slider_slides[' . (int) $counter . '][subtitle]" type="text" value="' . esc_attr( $this->get_value( $value, 'subtitle' ) ) . '" /></p>';
			echo '<p><label for="xo_slider_slides[' . (int) $counter . '][button_text]">' . esc_html__( 'Button text:', 'xo-liteslider' ) . '</label></p>' .
				'<p><input id="xo_slider_slides[' . (int) $counter . '][button_text]" name="xo_slider_slides[' . (int) $counter . '][button_text]" type="text" value="' . esc_attr( $this->get_value( $value, 'button_text' ) ) . '" /></p>';
			echo '<p><label for="xo_slider_slides[' . (int) $counter . '][button_link]">' . esc_html__( 'Button link (URL):', 'xo-liteslider' ) . '</label>' .
				'<span style="float: right;"><label for="xo_slider_slides[' . (int) $counter . '][button_newwin]">' . esc_html__( 'Open in new window:', 'xo-liteslider' ) . '</label> ' .
				'<input id="xo_slider_slides[' . (int) $counter . '][button_newwin]" name="xo_slider_slides[' . (int) $counter . '][button_newwin]" type="checkbox" value="1" ' . checked( $button_newwin, true, false ) . '/><label for="xo_slider_slides[' . (int) $counter . '][button_newwin]"></label></span></p>' .
				'<p><input id="xo_slider_slides[' . (int) $counter . '][button_link]" name="xo_slider_slides[' . (int) $counter . '][button_link]" type="text" value="' . esc_attr( $this->get_value( $value, 'button_link' ) ) . '" /></p>';
			echo '</div>';

			echo '<div class="slide-panel tabs-slide-panel-video tabs-panel-inactive">';
			echo '<table class="table-video"><tbody>';
			echo '<tr><th><label for="xo_slider_slides[' . (int) $counter . '][video_autoplay]">' . esc_html__( 'Autoplay:', 'xo-liteslider' ) . '</label></th>';
			echo '<td><input id="xo_slider_slides[' . (int) $counter . '][video_autoplay]" name="xo_slider_slides[' . (int) $counter . '][video_autoplay]" type="checkbox" value="1" ' . checked( $video_autoplay, true, false ) . '/><label for="xo_slider_slides[' . (int) $counter . '][video_autoplay]"></label></td></tr>';
			echo '<tr><th><label for="xo_slider_slides[' . (int) $counter . '][video_loop]">' . esc_html__( 'Loop:', 'xo-liteslider' ) . '</label></th>';
			echo '<td><input id="xo_slider_slides[' . (int) $counter . '][video_loop]" name="xo_slider_slides[' . (int) $counter . '][video_loop]" type="checkbox" value="1" ' . checked( $video_loop, true, false ) . '/><label for="xo_slider_slides[' . (int) $counter . '][video_loop]"></label></td></tr>';
			echo '<tr><th><label for="xo_slider_slides[' . (int) $counter . '][video_muted]">' . esc_html__( 'Muted:', 'xo-liteslider' ) . '</label></th>';
			echo '<td><input id="xo_slider_slides[' . (int) $counter . '][video_muted]" name="xo_slider_slides[' . (int) $counter . '][video_muted]" type="checkbox" value="1" ' . checked( $video_muted, true, false ) . '/><label for="xo_slider_slides[' . (int) $counter . '][video_muted]"></label></td></tr>';
			echo '<tr><th><label for="xo_slider_slides[' . (int) $counter . '][video_controls]">' . esc_html__( 'Playback controls:', 'xo-liteslider' ) . '</label></th>';
			echo '<td><input id="xo_slider_slides[' . (int) $counter . '][video_controls]" name="xo_slider_slides[' . (int) $counter . '][video_controls]" type="checkbox" value="1" ' . checked( $video_controls, true, false ) . '/><label for="xo_slider_slides[' . (int) $counter . '][video_controls]"></label></td></tr>';
			echo '<tr><th><label for="xo_slider_slides[' . (int) $counter . '][video_inline]">' . esc_html__( 'Play inline:', 'xo-liteslider' ) . '</label></th>';
			echo '<td><input id="xo_slider_slides[' . (int) $counter . '][video_inline]" name="xo_slider_slides[' . (int) $counter . '][video_inline]" type="checkbox" value="1" ' . checked( $video_inline, true, false ) . '/><label for="xo_slider_slides[' . (int) $counter . '][video_inline]"></label></td></tr>';
			echo '<tr><th><label for="xo_slider_slides[' . (int) $counter . '][video_preload]">' . esc_html__( 'Preload:', 'xo-liteslider' ) . '</label></th><td>';
			echo '<select id="xo_slider_slides[' . (int) $counter . '][video_preload]" name="xo_slider_slides[' . (int) $counter . '][video_preload]">';
			echo '<option value="auto"' . ( 'auto' === $video_preload ? ' selected' : '' ) . '>' . esc_html__( 'Auto', 'xo-liteslider' ) . '</option>';
			echo '<option value="metadata"' . ( 'metadata' === $video_preload ? ' selected' : '' ) . '>' . esc_html__( 'Metadata', 'xo-liteslider' ) . '</option>';
			echo '<option value="none"' . ( 'none' === $video_preload ? ' selected' : '' ) . '>' . esc_html__( 'None', 'xo-liteslider' ) . '</option>';
			echo '</select>';
			echo '</td></tr>';

			echo '</tbody></table>';
			echo '</div>';

			echo '</div>'; // <!-- .slide-panel-wrapper -->

			echo '</td>';
			echo '</tr>';
			echo '</tbody></table>';

			echo '</div>'; // <!-- .slide-inner -->

			echo '</li>';

			$counter++;
		}
		echo '</ul>';
		echo '</div>' . "\n";

		wp_nonce_field( 'xo_slider_key', 'xo_slider_nonce' );

		// Countermeasure against the problem that the width of the date select control becomes narrow.
		echo '<style type="text/css">.media-frame select.attachment-filters { min-width: 102px; }</style>';
	}

	/**
	 * Get the slider parameters.
	 *
	 * @since 2.0.0
	 *
	 * @return array Array of slider parameters.
	 */
	private function get_parameters() {
		if ( empty( $this->parameters ) ) {
			$this->parameters = get_post_meta( get_the_ID(), 'parameters', true );
			if ( empty( $this->parameters ) ) {
				$this->parameters = array(
					'template'               => XO_Slider::DEFAULT_TEMPLATE_SLUG,
					'effect'                 => 'slide',
					'navigation'             => true,
					'pagination'             => true,
					'content'                => true,
					'sort'                   => 'asc',
					'loop'                   => true,
					'speed'                  => 600,
					'auto_height'            => false,
					'slides_per_group'       => '',
					'slides_per_view'        => '',
					'space_between'          => '',
					'centered_slides'        => true,
					'autoplay'               => true,
					'delay'                  => 3000,
					'stop_on_last_slide'     => false,
					'disable_on_interaction' => true,
					'thumbs_width'           => '',
					'thumbs_height'          => '',
					'thumbs_per_view'        => '',
					'thumbs_space_between'   => '',
					'thumbs_margin_top'      => '',
					'width'                  => '',
					'height'                 => '',
				);
			}
		}
		return $this->parameters;
	}

	/**
	 * Display parameters metabox.
	 *
	 * @since 1.0.0
	 */
	public function display_meta_parameter() {
		$params = $this->get_parameters();

		$template               = isset( $params['template'] ) ? $params['template'] : '';
		$width                  = isset( $params['width'] ) ? $params['width'] : '';
		$height                 = isset( $params['height'] ) ? $params['height'] : '';
		$effect                 = isset( $params['effect'] ) ? $params['effect'] : 'slide';
		$navigation             = isset( $params['navigation'] ) ? $params['navigation'] : true;
		$pagination             = isset( $params['pagination'] ) ? $params['pagination'] : true;
		$content                = isset( $params['content'] ) ? $params['content'] : true;
		$sort                   = isset( $params['sort'] ) ? $params['sort'] : 'asc';
		$loop                   = isset( $params['loop'] ) ? $params['loop'] : true;
		$speed                  = isset( $params['speed'] ) ? $params['speed'] : 600;
		$auto_height            = isset( $params['auto_height'] ) ? $params['auto_height'] : false;
		$slides_per_group       = isset( $params['slides_per_group'] ) ? $params['slides_per_group'] : 0;
		$slides_per_view        = isset( $params['slides_per_view'] ) ? $params['slides_per_view'] : 0;
		$space_between          = isset( $params['space_between'] ) ? $params['space_between'] : 0;
		$centered_slides        = isset( $params['centered_slides'] ) ? $params['centered_slides'] : true;
		$autoplay               = isset( $params['autoplay'] ) ? $params['autoplay'] : true;
		$delay                  = isset( $params['delay'] ) ? $params['delay'] : 3000;
		$stop_on_last_slide     = isset( $params['stop_on_last_slide'] ) ? $params['stop_on_last_slide'] : false;
		$disable_on_interaction = isset( $params['disable_on_interaction'] ) ? $params['disable_on_interaction'] : true;
		$thumbs_width           = isset( $params['thumbs_width'] ) ? $params['thumbs_width'] : '';
		$thumbs_height          = isset( $params['thumbs_height'] ) ? $params['thumbs_height'] : '';
		$thumbs_per_view        = isset( $params['thumbs_per_view'] ) ? $params['thumbs_per_view'] : 0;
		$thumbs_space_between   = isset( $params['thumbs_space_between'] ) ? $params['thumbs_space_between'] : 0;
		$thumbs_margin_top      = isset( $params['thumbs_margin_top'] ) ? $params['thumbs_margin_top'] : '';

		echo '<div id="xo-slider-parameter">';

		echo '<div class="parameter-panel-wrapper">';

		echo '<ul class="parameter-panel-tabs">';
		echo '<li class="tabs"><a class="nav-tab-link" data-type="parameter-panel-basic" href="#">' . esc_html__( 'Basic', 'xo-liteslider' ) . '</a></li>';
		echo '<li><a class="nav-tab-link" data-type="parameter-panel-autoplay" href="#">' . esc_html__( 'Autoplay', 'xo-liteslider' ) . '</a></li>';
		echo '<li><a class="nav-tab-link" data-type="parameter-panel-thumbnail" href="#">' . esc_html__( 'Thumbnail', 'xo-liteslider' ) . '</a></li>';
		echo '</ul>';

		echo '<div class="parameter-panel tabs-parameter-panel-basic tabs-panel-active">';
		echo '<table class="table-parameter"><tbody>';

		echo '<tr><th><label for="xo_slider_parameters[width]">' . esc_html__( 'Width:', 'xo-liteslider' ) . '</label></th>';
		echo '<td><input id="xo_slider_parameters[width]" name="xo_slider_parameters[width]" type="number" value="' . esc_attr( $width ) . '" class="small-text" min="0" step="1" /> px</td></tr>';
		echo '<tr><th><label for="xo_slider_parameters[height]">' . esc_html__( 'Height:', 'xo-liteslider' ) . '</label></th>';
		echo '<td><input id="xo_slider_parameters[height]" name="xo_slider_parameters[height]" type="number" value="' . esc_attr( $height ) . '" class="small-text" min="0" step="1" /> px</td></tr>';
		echo '<tr><th><label for="xo_slider_parameters[effect]">' . esc_html__( 'Effect:', 'xo-liteslider' ) . '</label></th><td>';
		echo '<select id="xo_slider_parameters[effect]" name="xo_slider_parameters[effect]">';
		echo '<option value="slide"' . ( 'slide' === $effect ? ' selected' : '' ) . '>Slide</option>';
		echo '<option value="fade"' . ( 'fade' === $effect ? ' selected' : '' ) . '>Fade</option>';
		echo '<option value="cube"' . ( 'cube' === $effect ? ' selected' : '' ) . '>Cube</option>';
		echo '<option value="coverflow"' . ( 'coverflow' === $effect ? ' selected' : '' ) . '>Coverflow</option>';
		echo '<option value="flip"' . ( 'flip' === $effect ? ' selected' : '' ) . '>Flip</option>';
		echo '<option value="cards"' . ( 'cards' === $effect ? ' selected' : '' ) . '>Cards</option>';
		echo '<option value="creative"' . ( 'creative' === $effect ? ' selected' : '' ) . '>Creative</option>';
		echo '</select>';
		echo '</td></tr>';
		echo '<tr><th><label for="xo_slider_parameters[navigation]">' . esc_html__( 'Navigation:', 'xo-liteslider' ) . '</label></th>';
		echo '<td><input id="xo_slider_parameters[navigation]" name="xo_slider_parameters[navigation]" type="checkbox" value="1" ' . checked( $navigation, true, false ) . '/><label for="xo_slider_parameters[navigation]"></label></td></tr>';
		echo '<tr><th><label for="xo_slider_parameters[pagination]">' . esc_html__( 'Pagination:', 'xo-liteslider' ) . '</label></th>';
		echo '<td><input id="xo_slider_parameters[pagination]" name="xo_slider_parameters[pagination]" type="checkbox" value="1" ' . checked( $pagination, true, false ) . '/><label for="xo_slider_parameters[pagination]"></label></td></tr>';
		echo '<tr><th><label for="xo_slider_parameters[content]">' . esc_html__( 'Content:', 'xo-liteslider' ) . '</label></th>';
		echo '<td><input id="xo_slider_parameters[content]" name="xo_slider_parameters[content]" type="checkbox" value="1" ' . checked( $content, true, false ) . '/><label for="xo_slider_parameters[content]"></label></td></tr>';
		echo '<tr><th><label for="xo_slider_parameters[sort]">' . esc_html__( 'Order:', 'xo-liteslider' ) . '</label></th><td>';
		echo '<select id="xo_slider_parameters[sort]" name="xo_slider_parameters[sort]">';
		echo '<option value="asc"' . ( 'asc' === $sort ? ' selected' : '' ) . '>' . esc_html__( 'Ascending order', 'xo-liteslider' ) . '</option>';
		echo '<option value="desc"' . ( 'desc' === $sort ? ' selected' : '' ) . '>' . esc_html__( 'Descending order', 'xo-liteslider' ) . '</option>';
		echo '<option value="random"' . ( 'random' === $sort ? ' selected' : '' ) . '>' . esc_html__( 'Random', 'xo-liteslider' ) . '</option>';
		echo '</select>';
		echo '</td></tr>';
		echo '<tr><th><label for="xo_slider_parameters[loop]">' . esc_html__( 'Loop:', 'xo-liteslider' ) . '</label></th>';
		echo '<td><input id="xo_slider_parameters[loop]" name="xo_slider_parameters[loop]" type="checkbox" value="1" ' . checked( $loop, true, false ) . '/><label for="xo_slider_parameters[loop]"></label></td></tr>';
		echo '<tr><th><label for="xo_slider_parameters[speed]">' . esc_html__( 'Effect speed:', 'xo-liteslider' ) . '</label></th>';
		echo '<td><input id="xo_slider_parameters[speed]" name="xo_slider_parameters[speed]" type="number" value="' . esc_attr( $speed ) . '" class="small-text" min="0" step="100" /> ' . esc_html__( 'ms', 'xo-liteslider' ) . '</td></tr>';
		echo '<tr><th><label for="xo_slider_parameters[auto_height]">' . esc_html__( 'Auto height:', 'xo-liteslider' ) . '</label></th>';
		echo '<td><input id="xo_slider_parameters[auto_height]" name="xo_slider_parameters[auto_height]" type="checkbox" value="1" ' . checked( $auto_height, true, false ) . '/><label for="xo_slider_parameters[auto_height]"></label></td></tr>';
		echo '<tr><th><label for="xo_slider_parameters[slides_per_group]">' . esc_html__( 'Group unit:', 'xo-liteslider' ) . '</label></th>';
		echo '<td><input id="xo_slider_parameters[slides_per_group]" name="xo_slider_parameters[slides_per_group]" type="number" value="' . esc_attr( $slides_per_group ) . '" class="small-text" min="0" step="0.01" /></td></tr>';
		echo '<tr><th><label for="xo_slider_parameters[slides_per_view]">' . esc_html__( 'View unit:', 'xo-liteslider' ) . '</label></th>';
		echo '<td><input id="xo_slider_parameters[slides_per_view]" name="xo_slider_parameters[slides_per_view]" type="number" value="' . esc_attr( $slides_per_view ) . '" class="small-text" min="0" step="0.01" /></td></tr>';
		echo '<tr><th><label for="xo_slider_parameters[space_between]">' . esc_html__( 'Space between:', 'xo-liteslider' ) . '</label></th>';
		echo '<td><input id="xo_slider_parameters[space_between]" name="xo_slider_parameters[space_between]" type="number" value="' . esc_attr( $space_between ) . '" class="small-text" min="0" step="1" /> px</td></tr>';
		echo '<tr><th><label for="xo_slider_parameters[centered_slides]">' . esc_html__( 'Centered Slides:', 'xo-liteslider' ) . '</label></th>';
		echo '<td><input id="xo_slider_parameters[centered_slides]" name="xo_slider_parameters[centered_slides]" type="checkbox" value="1" ' . checked( $centered_slides, true, false ) . '/><label for="xo_slider_parameters[centered_slides]"></label></td></tr>';

		echo '</tbody></table>';
		echo '<p class="howto">' . esc_html__( 'Width and height are optional. Some parameters may not be reflected in some templates.', 'xo-liteslider' ) . '</p>';
		echo '</div>'; // <!-- tabs-parameter-panel-basic -->

		echo '<div class="parameter-panel tabs-parameter-panel-autoplay tabs-panel-inactive">';
		echo '<table class="table-parameter"><tbody>';

		echo '<tr><th><label for="xo_slider_parameters[autoplay]">' . esc_html__( 'Autoplay:', 'xo-liteslider' ) . '</label></th>';
		echo '<td><input id="xo_slider_parameters[autoplay]" name="xo_slider_parameters[autoplay]" type="checkbox" value="1" ' . checked( $autoplay, true, false ) . '/><label for="xo_slider_parameters[autoplay]"></label></td></tr>';
		echo '<tr><th><label for="xo_slider_parameters[delay]">' . esc_html__( 'Delay:', 'xo-liteslider' ) . '</label></th>';
		echo '<td><input id="xo_slider_parameters[delay]" name="xo_slider_parameters[delay]" type="number" value="' . esc_attr( $delay ) . '" class="small-text" min="0" step="100" /> ' . esc_html__( 'ms', 'xo-liteslider' ) . '</td></tr>';

		echo '<tr><th><label for="xo_slider_parameters[stop_on_last_slide]">' . esc_html__( 'Stop on last slide:', 'xo-liteslider' ) . '</label></th>';
		echo '<td><input id="xo_slider_parameters[stop_on_last_slide]" name="xo_slider_parameters[stop_on_last_slide]" type="checkbox" value="1" ' . checked( $stop_on_last_slide, true, false ) . '/><label for="xo_slider_parameters[stop_on_last_slide]"></label></td></tr>';
		echo '<tr><th><label for="xo_slider_parameters[disable_on_interaction]">' . esc_html__( 'Disable on interaction:', 'xo-liteslider' ) . '</label></th>';
		echo '<td><input id="xo_slider_parameters[disable_on_interaction]" name="xo_slider_parameters[disable_on_interaction]" type="checkbox" value="1" ' . checked( $disable_on_interaction, true, false ) . '/><label for="xo_slider_parameters[disable_on_interaction]"></label></td></tr>';

		echo '</tbody></table>';
		echo '</div>'; // <!-- tabs-parameter-panel-basic -->

		echo '<div class="parameter-panel tabs-parameter-panel-thumbnail tabs-panel-inactive">';
		echo '<table class="table-parameter"><tbody>';

		echo '<tr><th><label for="xo_slider_parameters[thumbs_width]">' . esc_html__( 'Width:', 'xo-liteslider' ) . '</label></th>';
		echo '<td><input id="xo_slider_parameters[thumbs_width]" name="xo_slider_parameters[thumbs_width]" type="number" value="' . esc_attr( $thumbs_width ) . '" class="small-text" min="0" step="1" /> px</td></tr>';
		echo '<tr><th><label for="xo_slider_parameters[thumbs_height]">' . esc_html__( 'Height:', 'xo-liteslider' ) . '</label></th>';
		echo '<td><input id="xo_slider_parameters[thumbs_height]" name="xo_slider_parameters[thumbs_height]" type="number" value="' . esc_attr( $thumbs_height ) . '" class="small-text" min="0" step="1" /> px</td></tr>';
		echo '<tr><th><label for="xo_slider_parameters[thumbs_per_view]">' . esc_html__( 'View unit:', 'xo-liteslider' ) . '</label></th>';
		echo '<td><input id="xo_slider_parameters[thumbs_per_view]" name="xo_slider_parameters[thumbs_per_view]" type="number" value="' . esc_attr( $thumbs_per_view ) . '" class="small-text" min="0" step="1" /></td></tr>';
		echo '<tr><th><label for="xo_slider_parameters[thumbs_space_between]">' . esc_html__( 'Space between:', 'xo-liteslider' ) . '</label></th>';
		echo '<td><input id="xo_slider_parameters[thumbs_space_between]" name="xo_slider_parameters[thumbs_space_between]" type="number" value="' . esc_attr( $thumbs_space_between ) . '" class="small-text" min="0" step="1" /> px</td></tr>';
		echo '<tr><th><label for="xo_slider_parameters[thumbs_margin_top]">' . esc_html__( 'Top margin:', 'xo-liteslider' ) . '</label></th>';
		echo '<td><input id="xo_slider_parameters[thumbs_margin_top]" name="xo_slider_parameters[thumbs_margin_top]" type="number" value="' . esc_attr( $thumbs_margin_top ) . '" class="small-text" step="1" /> px</td></tr>';

		echo '</tbody></table>';
		echo '</div>'; // <!-- tabs-parameter-panel-thumbnail -->

		echo '</div>'; // <!-- parameter-panel-wrapper -->
		echo '</div>' . "\n";
	}

	/**
	 * Display template metabox.
	 *
	 * @since 1.0.0
	 */
	public function display_meta_template() {
		$params = $this->get_parameters();

		if ( 0 === count( $this->parent->templates ) ) {
			echo '<p class="howto">' . esc_html__( 'Template not found.', 'xo-liteslider' ) . '</p>';
			return;
		}

		$template_id = isset( $params['template'] ) ? $params['template'] : 'default';

		echo '<div id="xo-slider-template">';

		echo '<table class="table-template"><tbody>';
		echo '<tr><th><label for="template-select">' . esc_html__( 'Template:', 'xo-liteslider' ) . '</label></th><td>';
		echo '<select id="template-select" name="xo_slider_parameters[template]">';
		foreach ( $this->parent->templates as $template ) {
			echo '<option value="' . esc_attr( $template->id ) . '"' . ( $template->id === $template_id ? ' selected' : '' ) . '>' . esc_html( $template->name ) . '</option>';
		}
		if ( ! isset( $this->parent->templates[ $template_id ] ) ) {
			echo '<option value="' . esc_attr( $template_id ) . '" selected>' . esc_html( $template_id ) . '</option>';
		}
		echo '</select>';
		echo '</td></tr>';
		echo '</tbody></table>';

		foreach ( $this->parent->templates as $template ) {
			echo '<div class="template-description template-description-' . esc_attr( $template->id ) . ( $template->id === $template_id ? ' active' : ' inactive' ) . '">';
			$url = $template->get_screenshot();
			if ( ! $url ) {
				$url = plugins_url( 'img/custom.jpg', __DIR__ );
			}
			echo '<p class="template-image"><img loading="lazy" src="' . esc_url( $url ) . '" class="" alt=""></p>';
			echo '<p class="howto">' . esc_html( $template->get_description() ) . '</p>';
			echo '</div>';
		}

		echo '</div>' . "\n";
	}

	/**
	 * Display usage metabox.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post Post.
	 */
	public function display_meta_usage( $post ) {
		echo '<div id="xo-slider-usage">';
		echo '<p><label>' . esc_html__( 'Shortcode:', 'xo-liteslider' );
		echo '<input id="xo-slider-usage-shortcode" onfocus="this.select();" readonly="readonly" class="large-text code" value="[xo_slider id=&quot;' . esc_attr( $post->ID ) . '&quot;]" type="text">';
		echo '</label></p>';
		echo '<p><label>' . esc_html__( 'Template tag:', 'xo-liteslider' );
		echo '<input id="xo-slider-usage-template" onfocus="this.select();" readonly="readonly" class="large-text code" value="&lt;?php xo_slider( ' . esc_attr( $post->ID ) . ' ); ?&gt;" type="text">';
		echo '</label></p>';
		echo '<p class="howto">' . esc_html__( 'Parameter default will be the oldest slider.', 'xo-liteslider' ) . '</p>';
		echo '</div>' . "\n";
	}
}
